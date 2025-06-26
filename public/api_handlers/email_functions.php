<?php

require __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Define log_message function if it doesn't exist
if (!function_exists('log_message')) {
    function log_message($message, $file = 'emails.log')
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_dir = __DIR__ . '/../../logs/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        file_put_contents($log_dir . $file, "[{$timestamp}] {$message}\n", FILE_APPEND);
    }
}

// Define EMAIL_SETTINGS_LOG_FILE constant if it doesn't exist
if (!defined('EMAIL_SETTINGS_LOG_FILE')) {
    define('EMAIL_SETTINGS_LOG_FILE', 'email_settings.log');
}


// --- IMAP Functions Below ---

// Function to decode email body parts
function decode_email_part($part, $encoding)
{
    switch ($encoding) {
        case 0: // 7BIT
        case 1: // 8BIT
            return imap_utf8($part);
        case 2: // BINARY
            return $part;
        case 3: // BASE64
            return imap_base64($part);
        case 4: // QUOTED-PRINTABLE
            return imap_qprint($part);
        case 5: // OTHER
            return $part;
        default:
            return $part;
    }
}

// Function to get the email body, handling multipart messages
function get_email_body($inbox, $email_number, $structure)
{
    $body = '';
    if ($structure->type == TYPETEXT) { // Plain text or HTML part
        $body = imap_fetchbody($inbox, $email_number, 1);
        $body = decode_email_part($body, $structure->encoding);
    } else if ($structure->type == TYPEMULTIPART) { // Multipart message
        foreach ($structure->parts as $part_number => $part) {
            $part_index = $part_number + 1;
            if ($part->type == TYPETEXT) {
                $sub_body = imap_fetchbody($inbox, $email_number, $part_index);
                $decoded_sub_body = decode_email_part($sub_body, $part->encoding);

                if (strtolower($part->subtype) == 'html') {
                    return $decoded_sub_body; // Prioritize HTML
                } else if (strtolower($part->subtype) == 'plain') {
                    $body = $decoded_sub_body; // Keep plain text as fallback
                }
            }
        }
    }
    return $body;
}

// Helper function to map IMAP type numbers to MIME type strings
function get_mime_type_from_number($type_number)
{
    switch ($type_number) {
        case 0:
            return 'text';
        case 1:
            return 'multipart';
        case 2:
            return 'message';
        case 3:
            return 'application';
        case 4:
            return 'audio';
        case 5:
            return 'image';
        case 6:
            return 'video';
        case 7:
            return 'other';
        default:
            return 'application'; // Default for unknown types
    }
}

// Function to get email attachments
function get_email_attachments($inbox, $email_uid, $structure)
{
    $attachments = [];
    if (isset($structure->parts)) {
        foreach ($structure->parts as $part_number => $part) {
            $part_index = $part_number + 1;
            $filename = '';
            $is_attachment = false;

            // Check for attachment disposition
            if (isset($part->disposition) && strtolower($part->disposition) == 'attachment') {
                $is_attachment = true;
            }
            // Check for attachment filename in parameters
            if (isset($part->dparameters)) {
                foreach ($part->dparameters as $param) {
                    if (strtolower($param->attribute) == 'filename') {
                        $filename = $param->value;
                        $is_attachment = true;
                        break;
                    }
                }
            }
            // Check for attachment filename in parameters (alternative)
            if (!$is_attachment && isset($part->parameters)) {
                foreach ($part->parameters as $param) {
                    if (strtolower($param->attribute) == 'name') {
                        $filename = $param->value;
                        $is_attachment = true;
                        break;
                    }
                }
            }

            if ($is_attachment && !empty($filename)) {
                $mime_type_str = get_mime_type_from_number($part->type);
                $mime_type = $mime_type_str . '/' . strtolower($part->subtype);

                // We no longer download the file, just store metadata
                $attachments[] = [
                    'filename' => $filename,
                    'mime_type' => $mime_type,
                    'size' => $part->bytes ?? 0,
                    'email_uid' => $email_uid,
                    'part_index' => $part_index,
                    'file_path' => null // Set to null as it's not downloaded yet
                ];
                log_message("Attachment metadata: {$filename}, MIME: {$mime_type}");
            }
        }
    }
    return $attachments;
}
// Function to get the date of the last email stored in the database
function get_last_email_date($db, $user_id)
{
    try {
        $stmt = $db->prepare("SELECT MAX(date_received) as last_date FROM emails WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_date'] ? (int) $result['last_date'] : 0;
    } catch (PDOException $e) {
        log_message("DB error get_last_email_date: " . $e->getMessage());
        error_log("Database error in get_last_email_date: " . $e->getMessage());
        return 0;
    }
}

// Function to store fetched emails in the database
function store_emails($db, $emails)
{
    if (empty($emails)) {
        return 0;
    }

    $sql = "INSERT OR IGNORE INTO emails (uid, message_id, subject, from_address, from_name, to_address, date_received, body, is_read, folder, user_id)
            VALUES (:uid, :message_id, :subject, :from_address, :from_name, :to_address, :date_received, :body, :is_read, :folder, :user_id)";

    $attachment_sql = "INSERT INTO email_attachments (email_id, filename, mime_type, size, file_path, email_uid, part_index)
                       VALUES (:email_id, :filename, :mime_type, :size, :file_path, :email_uid, :part_index)";

    try {
        $stmt = $db->prepare($sql);
        $attachment_stmt = $db->prepare($attachment_sql);
        $inserted_count = 0;

        foreach ($emails as $email) {
            // Extract email and name from 'from' field
            preg_match('/<(.*?)>/', $email['from'], $matches);
            $from_email = isset($matches[1]) ? $matches[1] : $email['from'];
            $from_name = trim(preg_replace('/<.*?>/', '', $email['from']));

            // Ensure user_id is passed with the email data
            if (!isset($email['user_id'])) {
                log_message("User ID missing for email storage. Skipping.");
                continue;
            }

            $stmt->bindValue(':uid', $email['id']);
            $stmt->bindValue(':message_id', $email['message_id'] ?? null);
            $stmt->bindValue(':subject', $email['subject']);
            $stmt->bindValue(':from_address', $from_email);
            $stmt->bindValue(':from_name', $from_name);
            $stmt->bindValue(':to_address', $email['to']);
            $stmt->bindValue(':date_received', $email['date']);
            $stmt->bindValue(':body', $email['body']);
            $stmt->bindValue(':is_read', $email['is_read'], PDO::PARAM_BOOL);
            $stmt->bindValue(':folder', 'INBOX');
            $stmt->bindValue(':user_id', $email['user_id']);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $inserted_count++;
                    $email_id = $db->lastInsertId();

                    // Store attachments if any
                    if (!empty($email['attachments'])) {
                        foreach ($email['attachments'] as $attachment) {
                            $attachment_stmt->bindValue(':email_id', $email_id);
                            $attachment_stmt->bindValue(':filename', $attachment['filename']);
                            $attachment_stmt->bindValue(':mime_type', $attachment['mime_type']);
                            $attachment_stmt->bindValue(':size', $attachment['size']);
                            $attachment_stmt->bindValue(':file_path', $attachment['file_path']);
                            $attachment_stmt->bindValue(':email_uid', $attachment['email_uid']);
                            $attachment_stmt->bindValue(':part_index', $attachment['part_index']);
                            $attachment_stmt->execute();
                        }
                        log_message("Stored " . count($email['attachments']) . " attachments for email ID {$email_id}.");
                    }
                }
            }
        }
        log_message("Stored {$inserted_count} new emails.");
        return $inserted_count;
    } catch (PDOException $e) {
        log_message("DB error store_emails: " . $e->getMessage());
        error_log("Database error in store_emails: " . $e->getMessage());
        return 0;
    }
}

// Function to connect to the IMAP server and fetch *new* emails since the last check
function fetch_new_emails($db, $user_id, $last_email_date = 0)
{
    $settings = get_user_email_settings($db, $user_id);
    if (!$settings) {
        log_message("IMAP settings not found for user {$user_id}. Cannot fetch emails.");
        error_log("IMAP settings not found for user_id: {$user_id}.");
        return [];
    }
    // Always use standard IMAP SSL port and secure setting for IMAP connection
    $imap_host_base = $settings['imap_host'] ?? $_ENV['EMAIL_HOST'];
    $imap_user = $settings['imap_user'] ?? $_ENV['EMAIL_USERNAME'];
    $imap_pass = $settings['imap_pass'] ?? $_ENV['EMAIL_PASSWORD'];

    // Construct IMAP host string with standard IMAP SSL port (993) and /ssl flag
    $imap_host = '{' . $imap_host_base . ':993/imap/ssl}INBOX';

    log_message("Attempting IMAP connection for user {$user_id}.");
    $inbox = imap_open($imap_host, $imap_user, $imap_pass);

    if (!$inbox) {
        $error = imap_last_error();
        log_message("IMAP connection failed: {$error}.");
        error_log("IMAP connection failed: {$error}");
        return [];
    }
    log_message("IMAP connection successful for user {$user_id}.");

    // Search for emails since the last stored email date
    // Always fetch all emails to ensure no emails are missed due to IMAP search limitations.
    // Filtering by date will be done after fetching.
    $search_criteria = 'ALL';
    $emails = imap_search($inbox, $search_criteria);

    if (!$emails) {
        log_message("No emails found or search error.");
        imap_close($inbox);
        return [];
    }
    log_message("Found " . count($emails) . " emails on server for user {$user_id}.");

    // Sort emails from newest to oldest
    rsort($emails);

    $email_data = [];
    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        if ($overview && !empty($overview)) {
            $email_date = isset($overview[0]->date) ? strtotime($overview[0]->date) : time();

            // Only process emails newer than the last_email_date
            if ($email_date > $last_email_date) {
                $header = imap_headerinfo($inbox, $email_number);
                $structure = imap_fetchstructure($inbox, $email_number);
                $body = get_email_body($inbox, $email_number, $structure);
                $attachments = get_email_attachments($inbox, $overview[0]->uid, $structure);

                $from_raw = isset($overview[0]->from) ? $overview[0]->from : 'Unknown Sender';
                $subject_raw = isset($overview[0]->subject) ? $overview[0]->subject : 'No Subject';

                $email_entry = [
                    'id' => $overview[0]->uid,
                    'message_id' => isset($overview[0]->message_id) ? $overview[0]->message_id : null,
                    'subject' => iconv_mime_decode($subject_raw, 0, "UTF-8"),
                    'from' => imap_utf8($from_raw),
                    'to' => isset($header->toaddress) ? $header->toaddress : 'Unknown Recipient',
                    'date' => $email_date,
                    'body' => $body,
                    'is_read' => (bool) $overview[0]->seen,
                    'attachments' => $attachments, // Add attachments here
                ];
                $email_data[] = $email_entry;
                log_message("Fetched new email: '{$email_entry['subject']}' from '{$email_entry['from']}'.");
            } else {
                log_message("Skipping old email: {$email_number}.");
            }
        } else {
            log_message("Failed to fetch overview for email {$email_number}.");
        }
    }

    // Close the IMAP stream
    imap_close($inbox);
    log_message("Fetched " . count($email_data) . " new emails for user {$user_id}.");

    return $email_data;
}

// Function to connect to the IMAP server and fetch emails from the Junk folder
function fetch_junk_emails($db, $user_id)
{
    $settings = get_user_email_settings($db, $user_id);
    $junk_folder = 'Junk'; // Common name for junk folder, might need to be configurable

    // Always use standard IMAP SSL port and secure setting for IMAP connection
    $imap_host_base = $settings['smtp_host'] ?? $_ENV['EMAIL_HOST'];
    $imap_user = $settings['smtp_username'] ?? $_ENV['EMAIL_USERNAME'];
    $imap_pass = $settings['smtp_password'] ?? $_ENV['EMAIL_PASSWORD'];

    // Construct IMAP host string with standard IMAP SSL port (993) and /ssl flag
    $imap_host = '{' . $imap_host_base . ':993/imap/ssl}' . $junk_folder;

    log_message("Attempting IMAP connection to Junk folder for user {$user_id}.");
    $inbox = imap_open($imap_host, $imap_user, $imap_pass);

    if (!$inbox) {
        $error = imap_last_error();
        log_message("IMAP connection to Junk folder failed: {$error}.");
        error_log("IMAP connection to Junk folder failed: {$error}");
        return [];
    }
    log_message("IMAP connection to Junk folder successful.");

    $emails = imap_search($inbox, 'ALL');

    if (!$emails) {
        log_message("No emails found in Junk folder or search error.");
        imap_close($inbox);
        return [];
    }
    log_message("Found " . count($emails) . " emails in Junk folder.");

    rsort($emails);

    $email_data = [];
    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $header = imap_headerinfo($inbox, $email_number);
        $structure = imap_fetchstructure($inbox, $email_number);
        $body = get_email_body($inbox, $email_number, $structure);
        $attachments = get_email_attachments($inbox, $email_number, $structure);

        if ($overview && !empty($overview)) {
            $from_raw = isset($overview[0]->from) ? $overview[0]->from : 'Unknown Sender';
            $subject_raw = isset($overview[0]->subject) ? $overview[0]->subject : 'No Subject';

            $email_entry = [
                'id' => $overview[0]->uid,
                'message_id' => isset($overview[0]->message_id) ? $overview[0]->message_id : null,
                'subject' => iconv_mime_decode($subject_raw, 0, "UTF-8"),
                'from' => imap_utf8($from_raw),
                'to' => isset($header->toaddress) ? $header->toaddress : 'Unknown Recipient',
                'date' => isset($overview[0]->date) ? strtotime($overview[0]->date) : time(),
                'body' => $body,
                'is_read' => (bool) $overview[0]->seen,
                'attachments' => $attachments,
            ];
            $email_data[] = $email_entry;
        }
    }
    imap_close($inbox);

    return $email_data;
}
// Function to deactivate all emails from a specific sender in the database
function deactivate_emails_by_sender($db, $sender_email, $user_id)
{
    // Check if 'is_active' column exists, if not, add it.
    // This is a defensive check for migration purposes. In a production environment,
    // schema migrations should be handled separately.
    try {
        $stmt = $db->query("PRAGMA table_info(emails)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1); // Get column names
        if (!in_array('is_active', $columns)) {
            $db->exec("ALTER TABLE emails ADD COLUMN is_active INTEGER DEFAULT 1");
            log_message("Added 'is_active' column to 'emails' table.");
        }
    } catch (PDOException $e) {
        log_message("DB error checking/adding 'is_active' column: " . $e->getMessage());
        error_log("Database error checking/adding 'is_active' column: " . $e->getMessage());
        return false;
    }

    $sql = "UPDATE emails SET is_active = 0 WHERE from_address = :sender_email AND user_id = :user_id AND is_active = 1";
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':sender_email', $sender_email);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $affected_rows = $stmt->rowCount();
        log_message("Deactivated {$affected_rows} emails for sender {$sender_email}.");
        return $affected_rows;
    } catch (PDOException $e) {
        log_message("DB error deactivate_emails_by_sender: " . $e->getMessage());
        error_log("Database error in deactivate_emails_by_sender: " . $e->getMessage());
        return false;
    }
}

// Function to get all emails from the local database
function get_emails_from_db($db, $user_id, $is_active = null)
{
    try {
        $sql = "SELECT * FROM emails WHERE user_id = :user_id";
        if ($is_active !== null) {
            $sql .= " AND is_active = :is_active";
        }
        $sql .= " ORDER BY date_received DESC";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id);
        if ($is_active !== null) {
            $stmt->bindValue(':is_active', $is_active, PDO::PARAM_INT);
        }
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attachment_stmt = $db->prepare("SELECT id, filename, mime_type, size, file_path FROM email_attachments WHERE email_id = :email_id");

        // Re-format for consistency with the original email structure and add attachments
        $formatted_emails = [];
        foreach ($emails as $email) {
            $current_email_id = $email['id']; // Assuming 'id' is the primary key of the emails table

            $attachment_stmt->bindValue(':email_id', $current_email_id);
            $attachment_stmt->execute();
            $attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);

            $formatted_emails[] = [
                'id' => $email['uid'],
                'message_id' => $email['message_id'],
                'subject' => $email['subject'],
                'from' => $email['from_name'] . ' <' . $email['from_address'] . '>',
                'to' => $email['to_address'],
                'date' => (int) $email['date_received'],
                'body' => $email['body'],
                'is_read' => (bool) $email['is_read'],
                'attachments' => $attachments, // Add attachments here
            ];
        }
        return $formatted_emails;
    } catch (PDOException $e) {
        log_message("DB error get_emails_from_db: " . $e->getMessage());
        error_log("Database error in get_emails_from_db: " . $e->getMessage());
        return [];
    }
}

// Function to group emails by conversation (sender)
function group_emails_by_sender($emails)
{
    $conversations = [];
    foreach ($emails as $email) {
        // Extract email address from the 'from' string
        preg_match('/<(.*?)>/', $email['from'], $matches);
        $sender_email = isset($matches[1]) ? $matches[1] : $email['from'];
        $sender_name = trim(preg_replace('/<.*?>/', '', $email['from']));

        // Fallback for sender_name if it's empty after stripping email
        if (empty($sender_name)) {
            $sender_name = $sender_email;
        }

        if (!isset($conversations[$sender_email])) {
            $conversations[$sender_email] = [
                'sender_name' => $sender_name,
                'sender_email' => $sender_email,
                'emails' => [],
                'latest_date' => 0,
                'unread_count' => 0,
            ];
        }

        $conversations[$sender_email]['emails'][] = $email;
        if ($email['date'] > $conversations[$sender_email]['latest_date']) {
            $conversations[$sender_email]['latest_date'] = $email['date'];
        }
        if (!$email['is_read']) {
            $conversations[$sender_email]['unread_count']++;
        }
    }

    // Sort conversations by the date of the latest email
    uasort($conversations, function ($a, $b) {
        return $b['latest_date'] - $a['latest_date'];
    });

    return $conversations;
}

// Function to mark all emails in a conversation as read in the database
function mark_conversation_as_read($db, $sender_email, $user_id)
{
    $sql = "UPDATE emails SET is_read = 1 WHERE from_address = :sender_email AND user_id = :user_id AND is_read
            = 0";
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':sender_email', $sender_email);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $affected_rows = $stmt->rowCount();
        log_message("Marked {$affected_rows} emails as read for sender {$sender_email}.");
        return $affected_rows;
    } catch (PDOException $e) {
        log_message("DB error mark_conversation_as_read: " . $e->getMessage());
        error_log("Database error in mark_conversation_as_read: " . $e->getMessage());
        return false;
    }
}

// Function to delete all emails from a specific sender from the database
function delete_emails_by_sender($db, $sender_email, $user_id)
{
    // First, get the UIDs of the emails to be deleted
    $get_uids_sql = "SELECT uid FROM emails WHERE from_address = :sender_email AND user_id = :user_id";
    try {
        $stmt = $db->prepare($get_uids_sql);
        $stmt->bindValue(':sender_email', $sender_email);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $uids_to_delete = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        log_message("DB error delete_emails_by_sender (UIDs): " . $e->getMessage());
        error_log("Database error in delete_emails_by_sender (getting UIDs): " . $e->getMessage());
        return false;
    }

    if (empty($uids_to_delete)) {
        log_message("No emails found for sender {$sender_email}. Nothing to delete.");
        return 0; // No emails to delete
    }

    // Now, delete from the IMAP server
    $settings = get_user_email_settings($db, $user_id);
    if (!$settings) {
        $imap_host = '{' . $_ENV['EMAIL_HOST'] . ':993/imap/ssl}INBOX';
        $imap_user = $_ENV['EMAIL_USERNAME'];
        $imap_pass = $_ENV['EMAIL_PASSWORD'];
    } else {
        // Correctly construct the IMAP host string for deletion
        $imap_host = '{' . $settings['imap_host'] . ':993/imap/ssl}INBOX';
        $imap_user = $settings['imap_user'];
        $imap_pass = $settings['imap_pass'];
    }

    log_message("Attempting IMAP deletion connection for user {$imap_user}.");
    $inbox = imap_open($imap_host, $imap_user, $imap_pass);

    if ($inbox) {
        log_message("IMAP deletion connection successful. UIDs: " . implode(',', $uids_to_delete));
        foreach ($uids_to_delete as $uid) {
            imap_delete($inbox, $uid, FT_UID);
        }
        imap_expunge($inbox);
        imap_close($inbox);
        log_message("Emails deleted from IMAP for sender {$sender_email}.");
    } else {
        $error = imap_last_error();
        log_message("IMAP deletion failed: {$error}.");
        error_log("IMAP connection failed for deletion: {$error}");
        // Decide if you want to proceed with DB deletion even if server deletion fails.
        // For now, we'll stop to ensure data consistency.
        return false;
    }

    // Finally, delete from the database
    $sql = "DELETE FROM emails WHERE from_address = :sender_email AND user_id = :user_id";
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':sender_email', $sender_email);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $affected_rows = $stmt->rowCount();
        log_message("Deleted {$affected_rows} emails from DB for sender {$sender_email}.");
        return $affected_rows;
    } catch (PDOException $e) {
        log_message("DB error delete_emails_by_sender (DB deletion): " . $e->getMessage());
        error_log("Database error in delete_emails_by_sender (deleting from DB): " . $e->getMessage());
        return false;
    }
}
function get_user_email_settings($db, $user_id)
{
    $stmt = $db->prepare("SELECT * FROM user_email_settings WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $user_id);
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    return $settings;
}

// Function to get a specific draft email by its ID
function get_draft_by_id($db, $draft_id, $user_id)
{
    try {
        $stmt = $db->prepare("SELECT * FROM emails WHERE id = :id AND user_id = :user_id AND is_draft = 1");
        $stmt->bindValue(':id', $draft_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_message("DB error get_draft_by_id: " . $e->getMessage());
        error_log("Database error in get_draft_by_id: " . $e->getMessage());
        return false;
    }
}

// Function to save an email (insert new or update existing draft)
function save_email($db, $data)
{
    $is_update = !empty($data['draft_id']);

    if ($is_update) {
        // Update existing draft
        $sql = "UPDATE emails SET to_address = :to_address, subject = :subject, body = :body, is_draft = :is_draft, from_address = :from_address, from_name = :from_name WHERE id = :id AND user_id = :user_id";
    } else {
        // Insert new email
        $sql = "INSERT INTO emails (user_id, staff_id, to_address, subject, body, is_draft, from_address, from_name, uid, date_received) VALUES (:user_id, :staff_id, :to_address, :subject, :body, :is_draft, :from_address, :from_name, :uid, :date_received)";
    }

    try {
        $stmt = $db->prepare($sql);

        // Bind common parameters
        $stmt->bindValue(':to_address', $data['to']);
        $stmt->bindValue(':subject', $data['subject']);
        $stmt->bindValue(':body', $data['body']);
        $stmt->bindValue(':is_draft', $data['is_draft'], PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);

        // Hardcoded from_address and from_name for now
        $stmt->bindValue(':from_address', 'hardcoded@example.com');
        $stmt->bindValue(':from_name', 'Hardcoded Name');


        if ($is_update) {
            $stmt->bindValue(':id', $data['draft_id'], PDO::PARAM_INT);
        } else {
            // Bind parameters specific to new emails
            $stmt->bindValue(':staff_id', $data['staff_id'], PDO::PARAM_INT);
            $stmt->bindValue(':uid', uniqid(), PDO::PARAM_STR); // Generate a unique ID
            $stmt->bindValue(':date_received', time(), PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            return $is_update ? $data['draft_id'] : $db->lastInsertId();
        } else {
            return false;
        }
    } catch (PDOException $e) {
        log_message("DB error save_email: " . $e->getMessage());
        error_log("Database error in save_email: " . $e->getMessage());
        return false;
    }
}

<?php
require_once __DIR__ . '/email_functions.php';

function handle_emails($action, $method, $db, $input)
{
    // Increase execution time for potentially long-running email operations
    set_time_limit(300); // 5 minutes

    // Check if the IMAP extension is loaded
    if (!function_exists('imap_open')) {
        $error_message = 'The IMAP extension is not installed or enabled. Please enable it in your php.ini file to use email functionality.';
        error_log($error_message);
        return ['success' => false, 'message' => $error_message];
    }
    switch ($action) {
        case 'check_new_emails':
            // Set headers for Server-Sent Events (SSE)
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            // ob_implicit_flush(true); // Removed as it can interfere with other requests
            // ob_end_clean(); // Removed as it can interfere with other requests

            try {
                $user_id = $_SESSION['user_id'];
                // Get the date of the last email stored locally
                $last_email_date = get_last_email_date($db, $user_id);

                // Fetch only new emails from the server
                $new_emails = fetch_new_emails($db, $user_id, $last_email_date);

                // Add user_id to each email before storing
                foreach ($new_emails as &$email) {
                    $email['user_id'] = $user_id;
                }
                unset($email); // Break the reference

                // Store the new emails in the database
                $new_email_count = store_emails($db, $new_emails);

                // Send final success message
                echo "data: " . json_encode(['status' => 'complete', 'new_email_count' => $new_email_count]) . "\n\n";
                flush();
                exit; // Exit after sending SSE
            } catch (Exception $e) {
                error_log("API Error (check_new_emails): " . $e->getMessage());
                echo "data: " . json_encode(['status' => 'error', 'message' => 'Failed to check for new emails.']) . "\n\n";
                flush();
                exit; // Exit after sending SSE
            }
            break;

        case 'list_senders':
            try {
                $user_id = $_SESSION['user_id'];
                // Now fetches emails from the local database
                $emails = get_emails_from_db($db, $user_id);
                $conversations = group_emails_by_sender($emails);
                return ['success' => true, 'conversations' => array_values($conversations)];
            } catch (Exception $e) {
                error_log("API Error (list_senders): " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to retrieve email conversations from the database.'];
            }
            break;

        case 'get_conversation':
            if (!isset($input['sender_email'])) {
                error_log("handle_emails: Missing sender_email for get_conversation.");
                return ['success' => false, 'message' => 'Sender email is required.'];
            }
            try {
                $user_id = $_SESSION['user_id'];
                // Now fetches emails from the local database
                $emails = get_emails_from_db($db, $user_id);
                $conversations = group_emails_by_sender($emails);
                $sender_email = $input['sender_email'];

                if (isset($conversations[$sender_email])) {
                    // Sort emails within the conversation from newest to oldest
                    usort($conversations[$sender_email]['emails'], function ($a, $b) {
                        return $b['date'] - $a['date'];
                    });
                    return ['success' => true, 'conversation' => $conversations[$sender_email]];
                } else {
                    return ['success' => false, 'message' => 'Conversation not found.'];
                }
            } catch (Exception $e) {
                error_log("API Error (get_conversation): " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to retrieve conversation details.'];
            }
            break;

        case 'mark_as_read':
            if (!isset($input['sender_email'])) {
                error_log("handle_emails: Missing sender_email for mark_as_read.");
                return ['success' => false, 'message' => 'Sender email is required.'];
            }
            try {
                $sender_email = $input['sender_email'];
                $user_id = $_SESSION['user_id'];
                $updated_count = mark_conversation_as_read($db, $sender_email, $user_id);
                if ($updated_count !== false) {
                    return ['success' => true, 'updated_count' => $updated_count];
                } else {
                    return ['success' => false, 'message' => 'Failed to mark conversation as read.'];
                }
            } catch (Exception $e) {
                error_log("API Error (mark_as_read): " . $e->getMessage());
                return ['success' => false, 'message' => 'An error occurred while marking emails as read.'];
            }
            break;

        case 'delete_conversation':
            if (!isset($input['sender_email'])) {
                error_log("handle_emails: Missing sender_email for delete_conversation.");
                return ['success' => false, 'message' => 'Sender email is required.'];
            }
            try {
                $sender_email = $input['sender_email'];
                $user_id = $_SESSION['user_id'];
                $deleted_count = delete_emails_by_sender($db, $sender_email, $user_id);
                if ($deleted_count !== false) {
                    return ['success' => true, 'deleted_count' => $deleted_count];
                } else {
                    return ['success' => false, 'message' => 'Failed to delete conversation.'];
                }
            } catch (Exception $e) {
                error_log("API Error (delete_conversation): " . $e->getMessage());
                return ['success' => false, 'message' => 'An error occurred while deleting the conversation.'];
            }
            break;
        case 'deactivate_conversation':
            if (!isset($input['sender_email'])) {
                error_log("handle_emails: Missing sender_email for deactivate_conversation.");
                return ['success' => false, 'message' => 'Sender email is required.'];
            }
            try {
                $sender_email = $input['sender_email'];
                $user_id = $_SESSION['user_id'];
                $deactivated_count = deactivate_emails_by_sender($db, $sender_email, $user_id);
                if ($deactivated_count !== false) {
                    return ['success' => true, 'deactivated_count' => $deactivated_count];
                } else {
                    return ['success' => false, 'message' => 'Failed to deactivate conversation.'];
                }
            } catch (Exception $e) {
                error_log("API Error (deactivate_conversation): " . $e->getMessage());
                return ['success' => false, 'message' => 'An error occurred while deactivating the conversation.'];
            }
            break;

        case 'list_deactivated_senders':
            try {
                $user_id = $_SESSION['user_id'];
                // Fetches emails from the local database where is_active = 0
                $emails = get_emails_from_db($db, $user_id, 0); // Assuming 0 for deactivated
                $conversations = group_emails_by_sender($emails);
                return ['success' => true, 'conversations' => array_values($conversations)];
            } catch (Exception $e) {
                error_log("API Error (list_deactivated_senders): " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to retrieve deactivated email conversations from the database.'];
            }
            break;

        default:
            return ['success' => false, 'message' => 'Invalid action for emails.'];
    }
}
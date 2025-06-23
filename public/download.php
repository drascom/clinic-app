<?php
// Secure file download script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/services/LogService.php';

// 1. Authentication and Authorization
if (!is_logged_in()) {
    http_response_code(401);
    exit('Unauthorized');
}

$logService = new LogService();
$attachment_id = $_GET['attachment_id'] ?? null;
$action = $_GET['action'] ?? 'download'; // Default to download

if (!$attachment_id) {
    $logService->log('download', 'error', 'No attachment ID specified for download.');
    http_response_code(400);
    exit('Bad Request: No attachment ID specified.');
}

try {
    $db = get_db();
    // 2. Verify user has permission to download this file
    $stmt = $db->prepare("SELECT ea.*, e.user_id
                          FROM email_attachments ea
                          JOIN emails e ON ea.email_id = e.id
                          WHERE ea.id = :id AND e.user_id = :user_id");
    $stmt->bindValue(':id', $attachment_id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attachment) {
        $logService->log('download', 'error', 'Attachment not found or permission denied.', ['attachment_id' => $attachment_id, 'user_id' => $_SESSION['user_id']]);
        http_response_code(404);
        exit('Not Found: Attachment not found or you do not have permission to access it.');
    }
    // 3. Check if the file is already downloaded
    $upload_dir = __DIR__ . '/../uploads/email_attachments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $local_file_path = $upload_dir . basename($attachment['filename']);

    if ($attachment['file_path'] === null || !file_exists($local_file_path)) {
        // 4. If not, fetch from IMAP server
        $settings_stmt = $db->prepare("SELECT * FROM user_email_settings WHERE user_id = :user_id");
        $settings_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $settings_stmt->execute();
        $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            throw new Exception("IMAP settings not found for user.");
        }

        $imap_host = '{' . $settings['imap_host'] . ':993/imap/ssl}INBOX';
        $inbox = imap_open($imap_host, $settings['imap_user'], $settings['imap_pass']);

        if (!$inbox) {
            throw new Exception("Failed to connect to IMAP server.");
        }

        $attachment_content = imap_fetchbody($inbox, $attachment['email_uid'], $attachment['part_index'], FT_UID);
        $decoded_content = base64_decode($attachment_content);
        imap_close($inbox);

        if ($decoded_content === false) {
            throw new Exception("Failed to decode attachment content.");
        }

        // Save the file locally
        file_put_contents($local_file_path, $decoded_content);

        // Update the database with the file path (just the filename)
        $update_stmt = $db->prepare("UPDATE email_attachments SET file_path = :file_path WHERE id = :id");
        $update_stmt->bindValue(':file_path', basename($attachment['filename']));
        $update_stmt->bindValue(':id', $attachment_id, PDO::PARAM_INT);
        $update_stmt->execute();
    }

    // 5. Serve the file
    if ($action === 'view') {
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: inline; filename="' . basename($attachment['filename']) . '"');
    } else {
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($attachment['filename']) . '"');
    }
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($local_file_path));
    flush(); // Flush system output buffer
    readfile($local_file_path);
    exit;
} catch (Exception $e) {
    $logService->log('download', 'error', "Download Error: " . $e->getMessage(), ['attachment_id' => $attachment_id]);
    http_response_code(500);
    exit('Internal Server Error: Could not process the file download.');
}

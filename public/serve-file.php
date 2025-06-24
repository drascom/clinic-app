<?php

/**
 * Secure File Serving Script
 * Serves files from the uploads directory outside the public folder
 * Includes security checks and proper headers
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    exit('Unauthorized');
}

require_once __DIR__ . '/services/LogService.php';

$logService = new LogService();

// Define the base directory for uploads, outside the public web root
$upload_dir = dirname(__DIR__) . '/uploads/';

// Get the file parameter from the URL
$file_path_from_url = $_GET['file'] ?? '';

// Sanitize the file path to prevent directory traversal
// Remove any '..' segments and ensure it's a relative path
$file_path_from_url = str_replace('..', '', $file_path_from_url);
$file_path_from_url = trim($file_path_from_url, '/'); // Remove leading/trailing slashes

// Construct the full absolute path to the requested file
$full_file_path = realpath($upload_dir . $file_path_from_url);

// Log the attempt to serve a file
$logService->log(
    'serve-file',
    'attempt',
    'File serving attempt',
    [
        'requested_file' => $file_path_from_url,
        'full_path_resolved' => $full_file_path,
        'upload_base_dir' => $upload_dir,
        'user_id' => $_SESSION['user_id'] ?? 'guest'
    ]
);

// Check if the file exists and is within the allowed uploads directory
if ($full_file_path === false || !file_exists($full_file_path) || strpos($full_file_path, $upload_dir) !== 0) {
    $logService->log(
        'serve-file',
        'error',
        'File not found or access denied',
        [
            'requested_file' => $file_path_from_url,
            'full_path_resolved' => $full_file_path
        ]
    );
    http_response_code(404);
    exit('File not found or access denied.');
}

// Determine content type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $full_file_path);
finfo_close($finfo);

if ($mime_type === false) {
    $logService->log(
        'serve-file',
        'error',
        'Could not determine MIME type',
        [
            'file' => $full_file_path
        ]
    );
    http_response_code(500);
    exit('Internal server error.');
}

// Set appropriate headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($full_file_path));
header('Content-Disposition: inline; filename="' . basename($full_file_path) . '"'); // 'inline' to display in browser, 'attachment' to download
header('X-Content-Type-Options: nosniff'); // Prevent MIME type sniffing

// Output the file content
readfile($full_file_path);

$logService->log(
    'serve-file',
    'success',
    'File served successfully',
    [
        'file' => $file_path_from_url,
        'mime_type' => $mime_type
    ]
);
exit;

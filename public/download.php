<?php

/**
 * Secure File Download Script
 * Serves email attachments from the uploads directory outside the public folder
 * Includes security checks and proper headers for download
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

// Get the requested file path and filename
$file_path = $_GET['file'] ?? '';
$filename = $_GET['filename'] ?? basename($file_path);

if (empty($file_path)) {
    http_response_code(400);
    exit('No file specified');
}

// Security: Remove any directory traversal attempts
// Construct the actual file path relative to the project root
// Assuming attachments are stored in 'uploads/email_attachments/'
// Define the absolute path to the base upload directory
$base_upload_dir = __DIR__ . '/../uploads/email_attachments/';

// Extract just the filename from the provided file_path to prevent directory traversal
$filename_from_path = basename($file_path);

// Construct the full file path using the known base upload directory and the extracted filename
$full_file_path = $base_upload_dir . $filename_from_path;


// Ensure the file exists and is within the allowed base upload directory
if (!$full_file_path || !file_exists($full_file_path) || !is_file($full_file_path)) {
    error_log("Download attempt failed: File not found or invalid path: " . $full_file_path);
    http_response_code(404);
    exit('File not found or invalid path.');
}

// Ensure the resolved path is still within the intended base directory
if (strpos($full_file_path, $base_upload_dir) !== 0) {
    error_log("Download attempt failed: Directory traversal detected for: " . $full_file_path);
    http_response_code(403);
    exit('Access denied.');
}

// Get file info
$file_size = filesize($full_file_path);
$mime_type = mime_content_type($full_file_path);

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $file_size);

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Output the file
readfile($full_file_path);
exit; // Terminate script after file is served
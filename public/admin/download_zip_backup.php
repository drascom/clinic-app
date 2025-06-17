<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: /auth/login.php');
    exit();
}

// Get ZIP file name from URL parameter
$zip_filename = $_GET['file'] ?? '';

if (empty($zip_filename)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'ZIP file name not specified';
    exit();
}

// Validate ZIP filename (security check)
if (!preg_match('/^backup_\d{8}_\d{6}\.zip$/', $zip_filename)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Invalid ZIP file name format';
    exit();
}

// Build ZIP file path
$zip_path = __DIR__ . '/../../db/backups/' . $zip_filename;

// Check if ZIP file exists
if (!file_exists($zip_path)) {
    header('HTTP/1.0 404 Not Found');
    echo 'ZIP backup file not found';
    exit();
}

// Verify it's actually a ZIP file
$file_info = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($file_info, $zip_path);
finfo_close($file_info);

if ($mime_type !== 'application/zip') {
    header('HTTP/1.0 400 Bad Request');
    echo 'File is not a valid ZIP archive';
    exit();
}

// Get file size for Content-Length header
$file_size = filesize($zip_path);

// Set headers for file download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output file contents
readfile($zip_path);

exit();
?>

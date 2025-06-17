<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: /auth/login.php');
    exit();
}

// Get backup name from URL parameter
$backup_name = $_GET['backup'] ?? '';

if (empty($backup_name)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Backup name not specified';
    exit();
}

// Validate backup name (security check)
if (!preg_match('/^backup_\d{8}_\d{6}$/', $backup_name)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Invalid backup name format';
    exit();
}

// Build backup directory path
$backup_dir = __DIR__ . '/../../db/backups/' . $backup_name;

// Check if backup directory exists
if (!is_dir($backup_dir)) {
    header('HTTP/1.0 404 Not Found');
    echo 'Backup not found';
    exit();
}

// Check if manifest file exists
$manifest_file = $backup_dir . '/backup_manifest.json';
if (!file_exists($manifest_file)) {
    header('HTTP/1.0 404 Not Found');
    echo 'Backup manifest not found';
    exit();
}

// Create temporary zip file
$temp_zip = tempnam(sys_get_temp_dir(), 'backup_') . '.zip';
$zip = new ZipArchive();

if ($zip->open($temp_zip, ZipArchive::CREATE) !== TRUE) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Could not create zip file';
    exit();
}

// Add all files from backup directory to zip
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($backup_dir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    // Skip directories (they would be added automatically)
    if (!$file->isDir()) {
        // Get real and relative path for current file
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($backup_dir) + 1);
        
        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
    }
}

// Close zip file
$zip->close();

// Get file size for Content-Length header
$file_size = filesize($temp_zip);

// Set headers for file download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $backup_name . '.zip"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output file contents
readfile($temp_zip);

// Clean up temporary file
unlink($temp_zip);

exit();
?>

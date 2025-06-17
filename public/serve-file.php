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

// Get the requested file path
$file_path = $_GET['file'] ?? '';

// Debug logging
error_log("serve-file.php: Requested file: " . $file_path);
error_log("serve-file.php: User logged in: " . (is_logged_in() ? 'yes' : 'no'));
error_log("serve-file.php: Session user_id: " . ($_SESSION['user_id'] ?? 'none'));

if (empty($file_path)) {
    http_response_code(400);
    exit('No file specified');
}

// Security: Remove any directory traversal attempts
$file_path = str_replace(['../', '..\\', '../', '..\\'], '', $file_path);

// Validate file path format (support both patient photos and avatars)
$is_patient_photo = preg_match('/^uploads\/patient_\d+\/[^\/]+\/[^\/]+$/', $file_path);
$is_patient_avatar = preg_match('/^uploads\/patient_avatars\/patient_\d+\/[^\/]+$/', $file_path);

if (!$is_patient_photo && !$is_patient_avatar) {
    error_log("serve-file.php: Invalid file path format: " . $file_path);
    http_response_code(403);
    exit('Invalid file path');
}

// Extract patient ID from path
if ($is_patient_photo) {
    if (!preg_match('/^uploads\/patient_(\d+)\//', $file_path, $matches)) {
        http_response_code(403);
        exit('Invalid patient photo path');
    }
} else if ($is_patient_avatar) {
    if (!preg_match('/^uploads\/patient_avatars\/patient_(\d+)\//', $file_path, $matches)) {
        http_response_code(403);
        exit('Invalid patient avatar path');
    }
}

$patient_id = (int) $matches[1];
error_log("serve-file.php: Extracted patient ID: " . $patient_id . " from path: " . $file_path);

// Verify the file exists in the database (additional security check)
try {
    $pdo = get_db();
    $serve_file_path = '/serve-file.php?file=' . $file_path;

    if ($is_patient_photo) {
        // Check patient photos table
        error_log("serve-file.php: Checking patient_photos table for: " . $serve_file_path);
        $stmt = $pdo->prepare("SELECT id, patient_id FROM patient_photos WHERE file_path = ?");
        $stmt->execute([$serve_file_path]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            error_log("serve-file.php: Photo not found in database: " . $serve_file_path);
            http_response_code(404);
            exit('Photo not found in database');
        }

        // Verify patient ID matches
        if ($record['patient_id'] != $patient_id) {
            error_log("serve-file.php: Patient ID mismatch for photo - Expected: " . $patient_id . ", Found: " . $record['patient_id']);
            http_response_code(403);
            exit('Patient ID mismatch');
        }

    } else if ($is_patient_avatar) {
        // Check patients table for avatar
        error_log("serve-file.php: Checking patients table for avatar: " . $serve_file_path);
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE id = ? AND avatar = ?");
        $stmt->execute([$patient_id, $serve_file_path]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            error_log("serve-file.php: Avatar not found in database for patient " . $patient_id . ": " . $serve_file_path);
            http_response_code(404);
            exit('Avatar not found in database');
        }
    }

    error_log("serve-file.php: Database verification successful for: " . $serve_file_path);

} catch (PDOException $e) {
    error_log("Database error in serve-file.php: " . $e->getMessage());
    http_response_code(500);
    exit('Database error');
}

// Construct the actual file path
$actual_file_path = __DIR__ . '/../' . $file_path;

// Check if file exists on disk
if (!file_exists($actual_file_path)) {
    http_response_code(404);
    exit('File not found on disk');
}

// Check if it's actually a file (not a directory)
if (!is_file($actual_file_path)) {
    http_response_code(403);
    exit('Not a valid file');
}

// Get file info
$file_size = filesize($actual_file_path);
$file_extension = strtolower(pathinfo($actual_file_path, PATHINFO_EXTENSION));

// Define allowed file types and their MIME types
$allowed_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'bmp' => 'image/bmp',
    'svg' => 'image/svg+xml'
];

// Check if file type is allowed
if (!isset($allowed_types[$file_extension])) {
    http_response_code(403);
    exit('File type not allowed');
}

$mime_type = $allowed_types[$file_extension];

// Set appropriate headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . $file_size);
header('Cache-Control: private, max-age=3600'); // Cache for 1 hour
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Set filename for download (optional)
$filename = basename($actual_file_path);
header('Content-Disposition: inline; filename="' . $filename . '"');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// Output the file
if ($file_size > 0) {
    // For large files, use readfile for better memory usage
    if ($file_size > 1024 * 1024) { // 1MB
        readfile($actual_file_path);
    } else {
        // For smaller files, read into memory
        echo file_get_contents($actual_file_path);
    }
} else {
    http_response_code(404);
    exit('Empty file');
}

// Log successful file access (optional)
error_log("File served: {$file_path} to user " . ($_SESSION['user_id'] ?? 'unknown'));
?>
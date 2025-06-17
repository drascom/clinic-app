<?php
require_once __DIR__ . '/../includes/upload_config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Enable error logging and debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/upload_errors.log');

// Debug mode - set to false in production
define('DEBUG_MODE', true);

/**
 * Log debug information to file
 */
function debug_log($message, $data = null)
{
    if (!DEBUG_MODE)
        return;

    $log_dir = __DIR__ . '/../../logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] DEBUG: $message";

    if ($data !== null) {
        $log_message .= " | Data: " . json_encode($data, JSON_PRETTY_PRINT);
    }

    file_put_contents($log_dir . 'upload_debug.log', $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Log error information to file
 */
function error_log_custom($message, $data = null)
{
    $log_dir = __DIR__ . '/../../logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] ERROR: $message";

    if ($data !== null) {
        $log_message .= " | Data: " . json_encode($data, JSON_PRETTY_PRINT);
    }

    file_put_contents($log_dir . 'upload_errors.log', $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);

    // Also log to PHP error log
    error_log($log_message);
}

/**
 * Check if a file is HEIC/HEIF format
 */
function is_heic_file($file_path, $original_name = '')
{
    // Check by file extension first
    $extension = strtolower(pathinfo($original_name ?: $file_path, PATHINFO_EXTENSION));
    if (in_array($extension, ['heic', 'heif'])) {
        return true;
    }

    // Check by MIME type if file exists
    if (file_exists($file_path)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file_path);

        if (in_array($mime_type, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'])) {
            return true;
        }
    }

    return false;
}

/**
 * Convert to bytes for comparison
 */
function convertToBytes($value)
{
    $value = trim($value);
    $last = strtolower($value[strlen($value) - 1]);
    $value = (int) $value;
    switch ($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    return $value;
}

/**
 * Convert HEIC/HEIF image to JPEG format
 */
function convert_heic_to_jpeg($source_path, $destination_path)
{
    debug_log("Starting HEIC to JPEG conversion", [
        'source' => $source_path,
        'destination' => $destination_path,
        'source_exists' => file_exists($source_path),
        'source_size' => file_exists($source_path) ? filesize($source_path) : 0
    ]);

    // Try ImageMagick first (preferred method)
    if (extension_loaded('imagick')) {
        try {
            $imagick = new Imagick();

            // Check if ImageMagick supports HEIC
            $formats = $imagick->queryFormats();
            if (in_array('HEIC', $formats) || in_array('HEIF', $formats)) {
                debug_log("Using ImageMagick for HEIC conversion");

                $imagick->readImage($source_path);
                $imagick->setImageFormat('JPEG');
                $imagick->setImageCompressionQuality(85); // Good quality JPEG
                $imagick->writeImage($destination_path);
                $imagick->clear();
                $imagick->destroy();

                debug_log("ImageMagick conversion successful", [
                    'destination_exists' => file_exists($destination_path),
                    'destination_size' => file_exists($destination_path) ? filesize($destination_path) : 0
                ]);

                return ['success' => true, 'method' => 'ImageMagick'];
            } else {
                debug_log("ImageMagick does not support HEIC format");
            }
        } catch (Exception $e) {
            error_log_custom("ImageMagick HEIC conversion failed", [
                'error' => $e->getMessage(),
                'source' => $source_path,
                'destination' => $destination_path
            ]);
        }
    }

    // Fallback: Try using system command if available (macOS/Linux)
    if (function_exists('exec') && !stripos(ini_get('disable_functions'), 'exec')) {
        try {
            debug_log("Attempting system command conversion");

            // Try sips command (macOS)
            $sips_cmd = "sips -s format jpeg " . escapeshellarg($source_path) . " --out " . escapeshellarg($destination_path) . " 2>&1";
            $output = [];
            $return_code = 0;
            exec($sips_cmd, $output, $return_code);

            if ($return_code === 0 && file_exists($destination_path) && filesize($destination_path) > 0) {
                debug_log("sips conversion successful", [
                    'command' => $sips_cmd,
                    'output' => $output,
                    'destination_size' => filesize($destination_path)
                ]);
                return ['success' => true, 'method' => 'sips'];
            }

            debug_log("sips conversion failed", [
                'command' => $sips_cmd,
                'return_code' => $return_code,
                'output' => $output
            ]);

        } catch (Exception $e) {
            error_log_custom("System command HEIC conversion failed", [
                'error' => $e->getMessage(),
                'source' => $source_path,
                'destination' => $destination_path
            ]);
        }
    }

    return ['success' => false, 'method' => 'none', 'error' => 'No suitable conversion method available'];
}

// Log request start
debug_log("Upload request started", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'post_data' => $_POST,
    'files_data' => $_FILES
]);

// Ensure user is logged in
if (!is_logged_in()) {
    error_log_custom("Unauthorized upload attempt", [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not authenticated.']);
    exit();
}

// Check for POST size limit exceeded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $displayMaxSize = ini_get('post_max_size');
    $actualSize = $_SERVER['CONTENT_LENGTH'];
    error_log_custom("POST size limit exceeded", [
        'content_length' => $actualSize,
        'post_max_size' => $displayMaxSize,
        'post_max_size_bytes' => ini_get('post_max_size')
    ]);
    http_response_code(413); // Payload Too Large
    echo json_encode([
        'error' => "Total upload size too large. Maximum total upload size is {$displayMaxSize}. Your upload is " . round($actualSize / 1024 / 1024, 2) . "MB. Try uploading fewer files or smaller files."
    ]);
    exit();
}

// Additional validation for file count and total size
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $max_files = ini_get('max_file_uploads');
    $max_individual_size = ini_get('upload_max_filesize');
    $max_total_size = ini_get('post_max_size');

    $max_individual_bytes = convertToBytes($max_individual_size);
    $max_total_bytes = convertToBytes($max_total_size);

    // Check file count
    $file_count = is_array($_FILES['file']['name']) ? count($_FILES['file']['name']) : 1;
    if ($file_count > $max_files) {
        error_log_custom("Too many files uploaded", [
            'file_count' => $file_count,
            'max_files' => $max_files
        ]);
        http_response_code(400); // Bad Request
        echo json_encode([
            'error' => "Too many files. Maximum {$max_files} files allowed. You tried to upload {$file_count} files."
        ]);
        exit();
    }

    // Check individual file sizes and calculate total
    $total_size = 0;
    $oversized_files = [];

    if (is_array($_FILES['file']['name'])) {
        for ($i = 0; $i < $file_count; $i++) {
            $file_size = $_FILES['file']['size'][$i];
            $file_name = $_FILES['file']['name'][$i];
            $total_size += $file_size;

            if ($file_size > $max_individual_bytes) {
                $oversized_files[] = [
                    'name' => $file_name,
                    'size_mb' => round($file_size / 1024 / 1024, 2),
                    'max_mb' => round($max_individual_bytes / 1024 / 1024, 2)
                ];
            }
        }
    } else {
        $file_size = $_FILES['file']['size'];
        $file_name = $_FILES['file']['name'];
        $total_size = $file_size;

        if ($file_size > $max_individual_bytes) {
            $oversized_files[] = [
                'name' => $file_name,
                'size_mb' => round($file_size / 1024 / 1024, 2),
                'max_mb' => round($max_individual_bytes / 1024 / 1024, 2)
            ];
        }
    }

    // Report oversized individual files
    if (!empty($oversized_files)) {
        $error_details = [];
        foreach ($oversized_files as $file) {
            $error_details[] = "{$file['name']} ({$file['size_mb']}MB exceeds {$file['max_mb']}MB limit)";
        }

        error_log_custom("Individual file size limit exceeded", [
            'oversized_files' => $oversized_files,
            'max_individual_size' => $max_individual_size
        ]);

        http_response_code(413); // Payload Too Large
        echo json_encode([
            'error' => "Individual file size limit exceeded. Maximum per file: {$max_individual_size}. Oversized files: " . implode(', ', $error_details)
        ]);
        exit();
    }

    // Check total size (with some overhead for form data)
    $form_overhead = 1024 * 1024; // 1MB overhead for form data
    if (($total_size + $form_overhead) > $max_total_bytes) {
        error_log_custom("Total upload size would exceed limit", [
            'total_file_size' => $total_size,
            'form_overhead' => $form_overhead,
            'combined_size' => $total_size + $form_overhead,
            'max_total_bytes' => $max_total_bytes,
            'max_total_size' => $max_total_size
        ]);

        http_response_code(413); // Payload Too Large
        echo json_encode([
            'error' => "Total upload size too large. Maximum total: {$max_total_size}. Your files total: " . round($total_size / 1024 / 1024, 2) . "MB. Try uploading fewer files at once."
        ]);
        exit();
    }

    debug_log("Upload validation passed", [
        'file_count' => $file_count,
        'total_size_mb' => round($total_size / 1024 / 1024, 2),
        'max_files' => $max_files,
        'max_individual_size' => $max_individual_size,
        'max_total_size' => $max_total_size
    ]);
}

// Ensure it's a POST request and a file is uploaded
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    error_log_custom("Invalid request method or missing file", [
        'method' => $_SERVER['REQUEST_METHOD'],
        'files_present' => isset($_FILES['file']),
        'post_data' => $_POST
    ]);
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

$patient_id = $_POST['patient_id'] ?? null;
$photo_album_type_id = $_POST['photo_album_type_id'] ?? null;
$uploaded_file = $_FILES['file'];

debug_log("Processing upload request", [
    'patient_id' => $patient_id,
    'photo_album_type_id' => $photo_album_type_id,
    'file_count' => is_array($uploaded_file['name']) ? count($uploaded_file['name']) : 1
]);

// Validate inputs
if (!$patient_id || !is_numeric($patient_id)) {
    error_log_custom("Invalid patient ID", [
        'patient_id' => $patient_id,
        'is_numeric' => is_numeric($patient_id)
    ]);
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid or missing patient ID.']);
    exit();
}

if (!$photo_album_type_id) {
    error_log_custom("Missing photo album type ID", [
        'photo_album_type_id' => $photo_album_type_id,
        'post_data' => $_POST
    ]);
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing photo album type name.']);
    exit();
}
// Fetch name from id
try {
    debug_log("Fetching photo album type", ['photo_album_type_id' => $photo_album_type_id]);

    $stmt = $pdo->prepare("SELECT name FROM photo_album_types WHERE id = ?");
    $stmt->execute([$photo_album_type_id]);
    $photo_album_type = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$photo_album_type) {
        error_log_custom("Invalid photo album type ID", [
            'photo_album_type_id' => $photo_album_type_id,
            'query_result' => $photo_album_type
        ]);
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid photo album type name.']);
        exit();
    }

    $photo_album_type_name = $photo_album_type['name'];
    debug_log("Photo album type found", [
        'photo_album_type_id' => $photo_album_type_id,
        'photo_album_type_name' => $photo_album_type_name
    ]);

} catch (\PDOException $e) {
    error_log_custom("Database error fetching photo album type", [
        'photo_album_type_id' => $photo_album_type_id,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error occurred.']);
    exit();
}

// Validate file upload
$files = [];
$file_count = count($uploaded_file['name']);
$files_results = [];

// Structure the $_FILES array for easier processing of multiple files
for ($i = 0; $i < $file_count; $i++) {
    $files[] = [
        'name' => $uploaded_file['name'][$i],
        'type' => $uploaded_file['type'][$i],
        'tmp_name' => $uploaded_file['tmp_name'][$i],
        'error' => $uploaded_file['error'][$i],
        'size' => $uploaded_file['size'][$i]
    ];
}

// Define upload directory paths
$upload_dir_base = __DIR__ . '/../../uploads/patient_' . $patient_id . '/' . $photo_album_type_name . '/';
$web_path_base = '/serve-file.php?file=uploads/patient_' . $patient_id . '/' . $photo_album_type_name . '/';

debug_log("Upload directory setup", [
    'upload_dir_base' => $upload_dir_base,
    'web_path_base' => $web_path_base,
    'patient_id' => $patient_id,
    'photo_album_type_name' => $photo_album_type_name
]);

// Create directory if it doesn't exist
if (!is_dir($upload_dir_base)) {
    debug_log("Creating upload directory", ['directory' => $upload_dir_base]);

    if (!mkdir($upload_dir_base, 0777, true)) {
        error_log_custom("Failed to create upload directory", [
            'directory' => $upload_dir_base,
            'permissions' => '0777',
            'recursive' => true,
            'parent_exists' => is_dir(dirname($upload_dir_base)),
            'parent_writable' => is_writable(dirname($upload_dir_base))
        ]);
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Failed to create upload directory.']);
        exit();
    }

    debug_log("Upload directory created successfully", ['directory' => $upload_dir_base]);
} else {
    debug_log("Upload directory already exists", [
        'directory' => $upload_dir_base,
        'is_writable' => is_writable($upload_dir_base)
    ]);
}

foreach ($files as $index => $file) {
    debug_log("Processing file", [
        'index' => $index,
        'filename' => $file['name'],
        'size' => $file['size'],
        'type' => $file['type'],
        'error' => $file['error']
    ]);

    $file_result = ['name' => $file['name'], 'success' => false];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        $error_message = $error_messages[$file['error']] ?? 'Unknown upload error';

        error_log_custom("File upload error", [
            'filename' => $file['name'],
            'error_code' => $file['error'],
            'error_message' => $error_message,
            'file_size' => $file['size']
        ]);

        $file_result['error'] = 'File upload failed: ' . $error_message;
        $files_results[] = $file_result;
        continue; // Skip to the next file
    }

    // Generate a unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $file_extension;
    $destination_path = $upload_dir_base . $new_filename;
    $web_file_path = $web_path_base . $new_filename;

    debug_log("File processing details", [
        'original_name' => $file['name'],
        'new_filename' => $new_filename,
        'destination_path' => $destination_path,
        'web_file_path' => $web_file_path,
        'file_extension' => $file_extension,
        'tmp_name' => $file['tmp_name']
    ]);

    // Move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination_path)) {
        error_log_custom("Failed to move uploaded file", [
            'filename' => $file['name'],
            'tmp_name' => $file['tmp_name'],
            'destination' => $destination_path,
            'tmp_exists' => file_exists($file['tmp_name']),
            'dest_dir_writable' => is_writable($upload_dir_base),
            'dest_dir_exists' => is_dir($upload_dir_base)
        ]);

        $file_result['error'] = 'Failed to move uploaded file.';
        $files_results[] = $file_result;
        continue; // Skip to the next file
    }

    debug_log("File moved successfully", [
        'filename' => $file['name'],
        'destination' => $destination_path,
        'file_size' => filesize($destination_path)
    ]);

    // Check if file is HEIC and convert to JPEG if needed
    $final_destination_path = $destination_path;
    $final_web_file_path = $web_file_path;
    $conversion_performed = false;

    if (is_heic_file($destination_path, $file['name'])) {
        debug_log("HEIC file detected, starting conversion", [
            'original_file' => $file['name'],
            'destination' => $destination_path
        ]);

        // Create new filename with .jpg extension
        $jpeg_filename = pathinfo($new_filename, PATHINFO_FILENAME) . '.jpg';
        $jpeg_destination_path = $upload_dir_base . $jpeg_filename;
        $jpeg_web_file_path = $web_path_base . $jpeg_filename;

        // Attempt conversion
        $conversion_result = convert_heic_to_jpeg($destination_path, $jpeg_destination_path);

        if ($conversion_result['success']) {
            debug_log("HEIC conversion successful", [
                'method' => $conversion_result['method'],
                'original_file' => $destination_path,
                'converted_file' => $jpeg_destination_path,
                'converted_size' => file_exists($jpeg_destination_path) ? filesize($jpeg_destination_path) : 0
            ]);

            // Remove original HEIC file
            if (file_exists($destination_path)) {
                if (unlink($destination_path)) {
                    debug_log("Original HEIC file removed", ['file' => $destination_path]);
                } else {
                    error_log_custom("Failed to remove original HEIC file", ['file' => $destination_path]);
                }
            }

            // Update paths to point to converted JPEG
            $final_destination_path = $jpeg_destination_path;
            $final_web_file_path = $jpeg_web_file_path;
            $conversion_performed = true;

        } else {
            error_log_custom("HEIC conversion failed", [
                'original_file' => $file['name'],
                'destination' => $destination_path,
                'conversion_error' => $conversion_result['error'] ?? 'Unknown error',
                'method_attempted' => $conversion_result['method'] ?? 'none'
            ]);

            // Clean up the original file and report error
            if (file_exists($destination_path)) {
                unlink($destination_path);
            }

            $file_result['error'] = 'HEIC file conversion failed. Please try uploading a JPEG or PNG file instead.';
            $files_results[] = $file_result;
            continue; // Skip to the next file
        }
    }

    // Insert record into patient_photos table
    try {
        debug_log("Inserting photo record to database", [
            'patient_id' => $patient_id,
            'photo_album_type_id' => $photo_album_type_id,
            'file_path' => $final_web_file_path,
            'server_path' => $final_destination_path,
            'filename' => $file['name'],
            'conversion_performed' => $conversion_performed
        ]);

        $stmt = $pdo->prepare("INSERT INTO patient_photos (patient_id, photo_album_type_id, file_path, created_at, updated_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))");
        $stmt->execute([$patient_id, $photo_album_type_id, $final_web_file_path]);

        $photo_id = $pdo->lastInsertId();

        debug_log("Photo record inserted successfully", [
            'photo_id' => $photo_id,
            'filename' => $file['name'],
            'web_file_path' => $final_web_file_path,
            'server_path' => $final_destination_path,
            'conversion_performed' => $conversion_performed
        ]);

        $file_result['success'] = true;
        $file_result['file_path'] = $final_web_file_path;
        $file_result['photo_id'] = $photo_id;
        if ($conversion_performed) {
            $file_result['converted_from_heic'] = true;
        }
        $files_results[] = $file_result;

    } catch (\PDOException $e) {
        error_log_custom("Database error inserting photo record", [
            'filename' => $file['name'],
            'patient_id' => $patient_id,
            'photo_album_type_id' => $photo_album_type_id,
            'web_file_path' => $final_web_file_path,
            'server_path' => $final_destination_path,
            'conversion_performed' => $conversion_performed,
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'sql_state' => $e->errorInfo[0] ?? 'Unknown'
        ]);

        // Attempt to remove the uploaded file if DB insertion fails
        if (file_exists($final_destination_path)) {
            if (unlink($final_destination_path)) {
                debug_log("Cleaned up uploaded file after DB error", ['file_path' => $final_destination_path]);
            } else {
                error_log_custom("Failed to clean up uploaded file after DB error", ['file_path' => $final_destination_path]);
            }
        }

        $file_result['error'] = 'Database error: Could not save photo record.';
        $files_results[] = $file_result;
    }
}

debug_log("Upload process completed", [
    'total_files' => count($files),
    'successful_uploads' => count(array_filter($files_results, function ($result) {
        return $result['success'];
    })),
    'failed_uploads' => count(array_filter($files_results, function ($result) {
        return !$result['success'];
    })),
    'results' => $files_results
]);

// Return consolidated results
http_response_code(200); // OK
echo json_encode(['results' => $files_results]);
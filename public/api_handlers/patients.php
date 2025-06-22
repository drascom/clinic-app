<?php
// Include upload configuration
require_once __DIR__ . '/../includes/upload_config.php';
require_once __DIR__ . '/../services/LogService.php';

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
 * Convert HEIC/HEIF image to JPEG format
 */
function convert_heic_to_jpeg($source_path, $destination_path)
{
    error_log("Starting HEIC to JPEG conversion - Source: $source_path, Destination: $destination_path");

    // Try ImageMagick first (preferred method)
    if (extension_loaded('imagick') && class_exists('Imagick')) {
        try {
            $imagick = new \Imagick();

            // Check if ImageMagick supports HEIC
            $formats = $imagick->queryFormats();
            if (in_array('HEIC', $formats) || in_array('HEIF', $formats)) {
                error_log("Using ImageMagick for HEIC conversion");

                $imagick->readImage($source_path);
                $imagick->setImageFormat('JPEG');
                $imagick->setImageCompressionQuality(85); // Good quality JPEG
                $imagick->writeImage($destination_path);
                $imagick->clear();
                $imagick->destroy();

                error_log("ImageMagick conversion successful - Destination size: " . (file_exists($destination_path) ? filesize($destination_path) : 0));

                return ['success' => true, 'method' => 'ImageMagick'];
            } else {
                error_log("ImageMagick does not support HEIC format");
            }
        } catch (Exception $e) {
            error_log("ImageMagick HEIC conversion failed: " . $e->getMessage());
        }
    }

    // Fallback: Try using system command if available (macOS/Linux)
    if (function_exists('exec') && !stripos(ini_get('disable_functions'), 'exec')) {
        try {
            error_log("Attempting system command conversion");

            // Try sips command (macOS)
            $sips_cmd = "sips -s format jpeg '" . escapeshellarg($source_path) . "' --out '" . escapeshellarg($destination_path) . "' 2>&1";
            $output = [];
            $return_code = 0;
            exec($sips_cmd, $output, $return_code);

            if ($return_code === 0 && file_exists($destination_path) && filesize($destination_path) > 0) {
                error_log("sips conversion successful - Output: " . implode(', ', $output));
                return ['success' => true, 'method' => 'sips'];
            }

            error_log("sips conversion failed - Return code: $return_code, Output: " . implode(', ', $output));
        } catch (Exception $e) {
            error_log("System command HEIC conversion failed: " . $e->getMessage());
        }
    }

    return ['success' => false, 'method' => 'none', 'error' => 'No suitable conversion method available'];
}

function handle_patients($action, $method, $db, $input = [])
{
    $logService = new LogService();
    switch ($action) {
        case 'add':
            if ($method === 'POST') {
                $name = trim($input['name'] ?? '');
                $dob = trim($input['dob'] ?? '');
                $agency_id = $input['agency_id'] ?? null;

                $phone = trim($input['phone'] ?? '');
                $email = trim($input['email'] ?? '');
                $city = trim($input['city'] ?? '');
                $occupation = trim($input['occupation'] ?? '');
                $gender = trim($input['gender'] ?? '');

                // Handle gender field - set to 'N/A' if empty to provide consistent default value
                $gender = !empty($gender) ? $gender : 'N/A';

                if (!empty($name) && !empty($agency_id)) {
                    try {
                        $stmt = $db->prepare("INSERT INTO patients (name, dob, phone, email, created_at, updated_at, agency_id, city, occupation, gender) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'), ?, ?, ?, ?)");
                        $stmt->execute([$name, $dob, $phone, $email, $agency_id, $city, $occupation, $gender]);
                        $new_patient_id = $db->lastInsertId();

                        // Fetch the newly created patient to return its data
                        $stmt_fetch = $db->prepare("SELECT id, name, avatar FROM patients WHERE id = ?");
                        $stmt_fetch->execute([$new_patient_id]);
                        $new_patient = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

                        return ['success' => true, 'message' => 'Patient added successfully.', 'id' => $new_patient_id, 'patient' => $new_patient];
                    } catch (PDOException $e) {
                        // Log the specific error for debugging
                        $logService->log('patients', 'error', "Patient creation failed: " . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                        return ['success' => false, 'error' => 'Failed to create patient: ' . $e->getMessage()];
                    }
                }
                $logService->log('patients', 'error', 'Name and agency are required for add patient.', $input);
                return ['success' => false, 'error' => 'Name and agency are required.'];
            }
            break;

        case 'update':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                $name = trim($input['name'] ?? '');
                $dob = trim($input['dob'] ?? '');
                $agency_id = $input['agency_id'] ?? null;

                $phone = trim($input['phone'] ?? '');
                $email = trim($input['email'] ?? '');
                $city = trim($input['city'] ?? '');
                $occupation = trim($input['occupation'] ?? '');
                $gender = trim($input['gender'] ?? '');

                // Handle gender field - set to 'N/A' if empty to provide consistent default value
                $gender = !empty($gender) ? $gender : 'N/A';

                if ($id && $name) {
                    try {
                        $sql = "UPDATE patients SET name = ?, dob = ?, agency_id = ?, phone = ?, email = ?, city = ?, occupation = ?, gender = ?, updated_at = datetime('now')";
                        $params = [$name, $dob, $agency_id, $phone, $email, $city, $occupation, $gender];

                        $sql .= " WHERE id = ?";
                        $params[] = $id;

                        $stmt = $db->prepare($sql);
                        $stmt->execute($params);

                        return ['success' => true, 'message' => 'Patient updated successfully.'];
                    } catch (PDOException $e) {
                        // Log the specific error for debugging
                        $logService->log('patients', 'error', "Patient update failed: " . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                        return ['success' => false, 'error' => 'Failed to update patient: ' . $e->getMessage()];
                    }
                }
                $logService->log('patients', 'error', 'ID and name are required for update patient.', $input);
                return ['success' => false, 'error' => 'ID and name are required.'];
            }
            break;
        case 'delete':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM patients WHERE id = ?");
                    $stmt->execute([$id]);
                    $logService->log('patients', 'success', 'Patient deleted successfully.', ['id' => $id]);
                    return ['success' => true, 'message' => 'Patient deleted successfully.'];
                }
                $logService->log('patients', 'error', 'ID is required for delete patient.', $input);
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'upload_avatar':
            if ($method === 'POST') {
                // Check for POST size limit exceeded (before processing any data)
                if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
                    $displayMaxSize = ini_get('post_max_size');
                    $actualSize = $_SERVER['CONTENT_LENGTH'];
                    error_log("Avatar upload - POST size limit exceeded: {$actualSize} bytes, limit: {$displayMaxSize}");
                    return [
                        'success' => false,
                        'error' => "File too large. Maximum upload size is {$displayMaxSize}. Your file is " . round($actualSize / 1024 / 1024, 2) . "MB."
                    ];
                }

                $patient_id = $input['id'] ?? null;

                // Debug logging
                error_log("Avatar upload request - Patient ID: " . ($patient_id ?? 'null'));
                error_log("Avatar upload request - Files: " . json_encode($_FILES));

                if ($patient_id && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    // Define upload directory paths (outside public directory)
                    $upload_dir = __DIR__ . '/../../uploads/patient_avatars/patient_' . $patient_id . '/';
                    $web_path_base = '/serve-file.php?file=uploads/patient_avatars/patient_' . $patient_id . '/';

                    error_log("Avatar upload - Upload dir: " . $upload_dir);
                    error_log("Avatar upload - Web path base: " . $web_path_base);

                    // Create directory if it doesn't exist
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            error_log("Failed to create avatar upload directory: " . $upload_dir);
                            return ['success' => false, 'error' => 'Failed to create upload directory.'];
                        }
                        error_log("Created avatar upload directory: " . $upload_dir);
                    }

                    // Validate file type (including HEIC/HEIF for conversion)
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'];
                    $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

                    if (!in_array($file_extension, $allowed_types)) {
                        error_log("Invalid avatar file type: " . $file_extension);
                        return ['success' => false, 'error' => 'Invalid file type. Only images are allowed.'];
                    }

                    // Generate unique filename
                    $file_name = uniqid('avatar_') . '.' . $file_extension;
                    $destination_path = $upload_dir . $file_name;
                    $web_file_path = $web_path_base . $file_name;

                    error_log("Avatar upload - Destination: " . $destination_path);
                    error_log("Avatar upload - Web path: " . $web_file_path);

                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination_path)) {
                        // Check if file is HEIC and convert to JPEG if needed
                        $final_destination_path = $destination_path;
                        $final_web_file_path = $web_file_path;
                        $conversion_performed = false;

                        if (is_heic_file($destination_path, $_FILES['avatar']['name'])) {
                            error_log("HEIC avatar detected, starting conversion");

                            // Create new filename with .jpg extension
                            $jpeg_filename = pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
                            $jpeg_destination_path = $upload_dir . $jpeg_filename;
                            $jpeg_web_file_path = $web_path_base . $jpeg_filename;

                            // Attempt conversion
                            $conversion_result = convert_heic_to_jpeg($destination_path, $jpeg_destination_path);

                            if ($conversion_result['success']) {
                                error_log("HEIC avatar conversion successful using " . $conversion_result['method']);

                                // Remove original HEIC file
                                if (file_exists($destination_path)) {
                                    if (unlink($destination_path)) {
                                        error_log("Original HEIC avatar file removed: " . $destination_path);
                                    } else {
                                        error_log("Failed to remove original HEIC avatar file: " . $destination_path);
                                    }
                                }

                                // Update paths to point to converted JPEG
                                $final_destination_path = $jpeg_destination_path;
                                $final_web_file_path = $jpeg_web_file_path;
                                $conversion_performed = true;
                            } else {
                                error_log("HEIC avatar conversion failed: " . ($conversion_result['error'] ?? 'Unknown error'));

                                // Clean up the original file and report error
                                if (file_exists($destination_path)) {
                                    unlink($destination_path);
                                }

                                return ['success' => false, 'error' => 'HEIC file conversion failed. Please try uploading a JPEG or PNG file instead.'];
                            }
                        }

                        // Delete old avatar if it exists
                        $stmt_old_avatar = $db->prepare("SELECT avatar FROM patients WHERE id = ?");
                        $stmt_old_avatar->execute([$patient_id]);
                        $old_avatar = $stmt_old_avatar->fetchColumn();

                        if ($old_avatar) {
                            error_log("Found old avatar: " . $old_avatar);

                            // Handle both old format and new format paths
                            if (strpos($old_avatar, 'serve-file.php') !== false) {
                                // New format: extract file parameter
                                if (preg_match('/file=([^&]+)/', $old_avatar, $matches)) {
                                    $old_file_path = __DIR__ . '/../../' . urldecode($matches[1]);
                                    if (file_exists($old_file_path)) {
                                        if (unlink($old_file_path)) {
                                            error_log("Deleted old avatar file: " . $old_file_path);
                                        } else {
                                            error_log("Failed to delete old avatar file: " . $old_file_path);
                                        }
                                    }
                                }
                            } else {
                                // Old format: direct path
                                $old_file_path = __DIR__ . '/../' . $old_avatar;
                                if (file_exists($old_file_path)) {
                                    if (unlink($old_file_path)) {
                                        error_log("Deleted old avatar file: " . $old_file_path);
                                    } else {
                                        error_log("Failed to delete old avatar file: " . $old_file_path);
                                    }
                                }
                            }
                        }

                        // Update the database with the new avatar path
                        $stmt = $db->prepare("UPDATE patients SET avatar = ? WHERE id = ?");
                        $stmt->execute([$final_web_file_path, $patient_id]);

                        error_log("Avatar uploaded successfully - Patient ID: " . $patient_id . ", Path: " . $final_web_file_path . ($conversion_performed ? " (converted from HEIC)" : ""));

                        return [
                            'success' => true,
                            'message' => 'Avatar uploaded successfully.' . ($conversion_performed ? ' HEIC file was automatically converted to JPEG.' : ''),
                            'avatar_url' => $final_web_file_path
                        ];
                    } else {
                        error_log("Failed to move uploaded avatar file from " . $_FILES['avatar']['tmp_name'] . " to " . $destination_path);
                        return ['success' => false, 'error' => 'Failed to upload avatar file.'];
                    }
                }

                $error_msg = 'Patient ID and avatar file are required for upload.';
                if (!$patient_id)
                    $error_msg = 'Patient ID is required.';
                else if (!isset($_FILES['avatar']))
                    $error_msg = 'Avatar file is required.';
                else if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK)
                    $error_msg = 'Avatar file upload error: ' . $_FILES['avatar']['error'];

                error_log("Avatar upload failed: " . $error_msg);
                return ['success' => false, 'error' => $error_msg];
            }
            break;

        case 'delete_avatar':
            if ($method === 'POST') {
                $patient_id = $input['patient_id'] ?? null;
                $avatar_url = $input['avatar_url'] ?? null;

                error_log("Avatar delete request - Patient ID: " . ($patient_id ?? 'null'));
                error_log("Avatar delete request - Avatar URL: " . ($avatar_url ?? 'null'));

                if ($patient_id) {
                    // Get current avatar from database
                    $stmt_get_avatar = $db->prepare("SELECT avatar FROM patients WHERE id = ?");
                    $stmt_get_avatar->execute([$patient_id]);
                    $current_avatar = $stmt_get_avatar->fetchColumn();

                    if ($current_avatar) {
                        error_log("Current avatar in database: " . $current_avatar);

                        // Handle both old format and new format paths
                        if (strpos($current_avatar, 'serve-file.php') !== false) {
                            // New format: extract file parameter
                            if (preg_match('/file=([^&]+)/', $current_avatar, $matches)) {
                                $file_path = __DIR__ . '/../../' . urldecode($matches[1]);
                                error_log("Attempting to delete new format avatar: " . $file_path);

                                if (file_exists($file_path)) {
                                    if (unlink($file_path)) {
                                        error_log("Successfully deleted avatar file: " . $file_path);
                                    } else {
                                        error_log("Failed to delete avatar file: " . $file_path);
                                        return ['success' => false, 'error' => 'Failed to delete avatar file.'];
                                    }
                                } else {
                                    error_log("Avatar file not found: " . $file_path);
                                }
                            }
                        } else {
                            // Old format: direct path
                            $file_path = __DIR__ . '/../' . $current_avatar;
                            error_log("Attempting to delete old format avatar: " . $file_path);

                            if (file_exists($file_path)) {
                                if (unlink($file_path)) {
                                    error_log("Successfully deleted old format avatar file: " . $file_path);
                                } else {
                                    error_log("Failed to delete old format avatar file: " . $file_path);
                                    return ['success' => false, 'error' => 'Failed to delete avatar file.'];
                                }
                            } else {
                                error_log("Old format avatar file not found: " . $file_path);
                            }
                        }
                    }

                    // Update the database to remove the avatar path
                    $stmt = $db->prepare("UPDATE patients SET avatar = NULL WHERE id = ?");
                    $stmt->execute([$patient_id]);

                    error_log("Avatar deleted successfully for patient ID: " . $patient_id);
                    return ['success' => true, 'message' => 'Avatar deleted successfully.'];
                }

                return ['success' => false, 'error' => 'Patient ID is required.'];
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    // Get patient info with agency name
                    $stmt = $db->prepare("
                        SELECT p.*, a.name AS agency_name
                        FROM patients p
                        LEFT JOIN agencies a ON p.agency_id = a.id
                        WHERE p.id = ?
                    ");
                    $stmt->execute([$id]);
                    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($patient) {
                        // Get all surgeries for this patient with room names
                        $stmt2 = $db->prepare("SELECT s.*, r.name as room_name FROM surgeries s LEFT JOIN rooms r ON s.room_id = r.id WHERE s.patient_id = ? ORDER BY s.date DESC");
                        $stmt2->execute([$id]);
                        $surgeries = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                        // Get all photos with album type names
                        $stmt3 = $db->prepare("
                                SELECT pp.*, pat.name AS album_type
                                FROM patient_photos pp
                                LEFT JOIN photo_album_types pat ON pp.photo_album_type_id = pat.id
                                WHERE pp.patient_id = ?
                                ORDER BY pp.created_at DESC
                            ");
                        $stmt3->execute([$id]);
                        $photos = $stmt3->fetchAll(PDO::FETCH_ASSOC);

                        // Get all appointments for this patient
                        $stmt4 = $db->prepare("
                            SELECT a.*, p.name AS procedure_name
                            FROM appointments a
                            LEFT JOIN procedures p ON a.procedure_id = p.id
                            WHERE a.patient_id = ?
                            ORDER BY a.appointment_date DESC
                        ");
                        $stmt4->execute([$id]);
                        $appointments = $stmt4->fetchAll(PDO::FETCH_ASSOC);

                        return [
                            'success' => true,
                            'patient' => $patient,
                            'surgeries' => $surgeries,
                            'photos' => $photos,
                            'appointments' => $appointments
                        ];
                    }

                    return ['success' => false, 'error' => 'Patient not found.'];
                }

                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'list':
            if ($method === 'POST') {
                $agency_id = $input['agency'] ?? null;

                if ($agency_id) {
                    // Filter by agency
                    $stmt = $db->prepare("
                        SELECT p.*, MAX(s.date) AS last_surgery_date, a.name AS agency_name
                        FROM patients p
                        LEFT JOIN surgeries s ON s.patient_id = p.id
                        LEFT JOIN agencies a ON p.agency_id = a.id
                        WHERE p.agency_id = ?
                        GROUP BY p.id
                        ORDER BY p.name
                    ");
                    $stmt->execute([$agency_id]);
                } else {
                    // Get all patients
                    $stmt = $db->query("
                        SELECT p.*, MAX(s.date) AS last_surgery_date, a.name AS agency_name
                        FROM patients p
                        LEFT JOIN surgeries s ON s.patient_id = p.id
                        LEFT JOIN agencies a ON p.agency_id = a.id
                        GROUP BY p.id
                        ORDER BY p.name
                    ");
                }

                return ['success' => true, 'patients' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            }
            break;

        case 'find_by_name':
            if ($method === 'POST') {
                $name = trim($input['name'] ?? '');
                if (!empty($name)) {
                    $stmt = $db->prepare("SELECT id, name, avatar FROM patients WHERE name = ?");
                    $stmt->execute([$name]);
                    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($patient) {
                        return ['success' => true, 'patient' => $patient];
                    } else {
                        return ['success' => false, 'error' => 'Patient not found.'];
                    }
                }
                return ['success' => false, 'error' => 'Name parameter is required.'];
            }
            break;
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

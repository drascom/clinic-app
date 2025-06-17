<?php
function handle_photos($action, $method, $db)
{
    switch ($action) {
        case 'add':
        case 'upload':
            if ($method === 'POST') {
                $patient_id = $_POST['patient_id'] ?? null;
                $album_type_id = $_POST['photo_album_type_id'] ?? null;
                $file_path = trim($_POST['file_path'] ?? '');

                if ($patient_id && $album_type_id && $file_path) {
                    $stmt = $db->prepare("INSERT INTO patient_photos (patient_id, photo_album_type_id, file_path, created_at, updated_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))");
                    $stmt->execute([$patient_id, $album_type_id, $file_path]);
                    $photo_id = $db->lastInsertId();

                    // Fetch the created photo
                    $stmt = $db->prepare("
                        SELECT pp.*, pat.name AS album_type
                        FROM patient_photos pp
                        LEFT JOIN photo_album_types pat ON pp.photo_album_type_id = pat.id
                        WHERE pp.id = ?
                    ");
                    $stmt->execute([$photo_id]);
                    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

                    return ['success' => true, 'id' => $photo_id, 'photo' => $photo];
                }
                return ['success' => false, 'error' => 'patient_id, photo_album_type_id, and file_path are required.'];
            }
            break;

        case 'update':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;
                $album_type_id = $_POST['photo_album_type_id'] ?? null;
                $file_path = trim($_POST['file_path'] ?? '');

                if ($id && $album_type_id && $file_path) {
                    // Check if photo exists
                    $check_stmt = $db->prepare("SELECT id FROM patient_photos WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'Photo not found.'];
                    }

                    $stmt = $db->prepare("UPDATE patient_photos SET photo_album_type_id = ?, file_path = ?, updated_at = datetime('now') WHERE id = ?");
                    $stmt->execute([$album_type_id, $file_path, $id]);
                    return ['success' => true];
                }
                return ['success' => false, 'error' => 'ID, album type, and file path are required.'];
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;
                error_log("Photos delete handler reached. ID: " . $id); // Add logging
                if ($id) {
                    $stmt = $db->prepare("SELECT file_path FROM patient_photos WHERE id = ?");
                    $stmt->execute([$id]);
                    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$photo) {
                        $response = ['success' => false, 'error' => 'Photo not found.'];
                        error_log("Photos delete failed: Photo not found. Response: " . json_encode($response)); // Add logging
                        return $response;
                    }

                    $db->beginTransaction();
                    try {
                        $stmt = $db->prepare("DELETE FROM patient_photos WHERE id = ?");
                        $stmt->execute([$id]);

                        $absolute_file_path = realpath($photo['file_path']);
                        $uploads_dir = realpath(dirname(__FILE__) . '/../uploads/');

                        if ($absolute_file_path && $uploads_dir && strpos($absolute_file_path, $uploads_dir) === 0) {
                            if (file_exists($absolute_file_path)) {
                                @unlink($absolute_file_path);
                                error_log("Deleted photo file: " . $absolute_file_path); // Add logging
                            } else {
                                error_log("Photo file not found for deletion: " . $absolute_file_path); // Add logging
                            }
                        } else {
                            error_log("Photo file path outside uploads directory or invalid: " . $absolute_file_path); // Add logging
                        }

                        $db->commit();
                        $response = ['success' => true];
                        error_log("Photos delete successful. Response: " . json_encode($response)); // Add logging
                        return $response;
                    } catch (\PDOException $e) {
                        $db->rollBack();
                        error_log("Database error during photo delete: " . $e->getMessage()); // Add logging
                        $response = ['success' => false, 'error' => 'Database error during deletion.'];
                        return $response;
                    }
                }
                $response = ['success' => false, 'error' => 'ID is required.'];
                error_log("Photos delete failed: ID missing. Response: " . json_encode($response)); // Add logging
                return $response;
            }
            break;

        case 'get':
            if ($method === 'GET') {
                $id = $input['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("
                        SELECT pp.*, pat.name AS album_type
                        FROM patient_photos pp
                        LEFT JOIN photo_album_types pat ON pp.photo_album_type_id = pat.id
                        WHERE pp.id = ?
                    ");
                    $stmt->execute([$id]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $data ? ['success' => true, 'photo' => $data] : ['success' => false, 'error' => "Photo not found with ID: {$id}"];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'list':
            if ($method === 'GET') {
                $patient_id = $_GET['patient_id'] ?? null;
                if ($patient_id) {
                    $stmt = $db->prepare("
                        SELECT pp.*, pat.name AS album_type
                        FROM patient_photos pp
                        LEFT JOIN photo_album_types pat ON pp.photo_album_type_id = pat.id
                        WHERE pp.patient_id = ?
                        ORDER BY pp.created_at DESC
                    ");
                    $stmt->execute([$patient_id]);
                    return ['success' => true, 'photos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                } else {
                    $stmt = $db->query("
                        SELECT pp.*, pat.name AS album_type
                        FROM patient_photos pp
                        LEFT JOIN photo_album_types pat ON pp.photo_album_type_id = pat.id
                        ORDER BY pp.created_at DESC
                    ");
                    return ['success' => true, 'photos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                }
            }
            break;
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}
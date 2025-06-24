<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_photos($action, $method, $db, $input = [])
{
    $logService = new LogService();
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
                    $logService->log('photos', 'success', 'Photo added successfully.', ['id' => $photo_id, 'patient_id' => $patient_id, 'album_type_id' => $album_type_id]);
                    return ['success' => true, 'id' => $photo_id, 'photo' => $photo];
                }
                $logService->log('photos', 'error', 'patient_id, photo_album_type_id, and file_path are required for add.', $_POST);
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
                    $logService->log('photos', 'success', 'Photo updated successfully.', ['id' => $id, 'album_type_id' => $album_type_id]);
                    return ['success' => true];
                }
                $logService->log('photos', 'error', 'ID, album type, and file path are required for update.', $_POST);
                return ['success' => false, 'error' => 'ID, album type, and file path are required.'];
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $input['id'] ?? $_POST['id'] ?? null; // Check both $input and $_POST
                $logService->log('photos', 'info', 'Photos delete handler reached.', ['id' => $id, 'method' => $method]);
                if ($id) {
                    $stmt = $db->prepare("SELECT file_path FROM patient_photos WHERE id = ?");
                    $stmt->execute([$id]);
                    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$photo) {
                        $response = ['success' => false, 'error' => 'Photo not found.'];
                        $logService->log('photos', 'warning', 'Photos delete failed: Photo not found.', ['id' => $id]);
                        return $response;
                    }

                    $db->beginTransaction();
                    try {
                        $stmt = $db->prepare("DELETE FROM patient_photos WHERE id = ?");
                        $stmt->execute([$id]);

                        // Construct the absolute path to the uploads directory
                        $uploads_base_dir = realpath(__DIR__ . '/../../uploads/');
                        $relative_file_path = $photo['file_path'];

                        // Ensure the file path is within the designated uploads directory
                        // and remove the 'uploads/' prefix if it exists in the stored path
                        $file_to_delete = $uploads_base_dir . '/' . str_replace('uploads/', '', $relative_file_path);

                        if (file_exists($file_to_delete)) {
                            @unlink($file_to_delete);
                            $logService->log('photos', 'info', 'Deleted photo file.', ['file_path' => $file_to_delete]);
                        } else {
                            $logService->log('photos', 'warning', 'Photo file not found for deletion (might have been deleted already or path is incorrect).', ['file_path' => $file_to_delete]);
                        }

                        $db->commit();
                        $response = ['success' => true];
                        $logService->log('photos', 'success', 'Photos delete successful.', ['id' => $id]);
                        return $response;
                    } catch (\PDOException $e) {
                        $db->rollBack();
                        $logService->log('photos', 'error', 'Database error during photo delete.', ['error' => $e->getMessage(), 'id' => $id]);
                        $response = ['success' => false, 'error' => 'Database error during deletion.'];
                        return $response;
                    }
                }
                $response = ['success' => false, 'error' => 'ID is required.'];
                $logService->log('photos', 'error', 'Photos delete failed: ID missing.', ['input' => $input]); // Use $input as it's the primary source for JSON
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

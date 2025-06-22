<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_photo_album_types($action, $method, $db)
{
    $logService = new LogService();
    switch ($action) {
        case 'add':
            if ($method === 'POST') {
                $name = trim($_POST['type_name'] ?? '');
                if (!empty($name)) {
                    $stmt = $db->prepare("INSERT INTO photo_album_types (name, created_at, updated_at) VALUES (?, datetime('now'), datetime('now'))");
                    $stmt->execute([$name]);
                    $newId = $db->lastInsertId();
                    $logService->log('photo_album_types', 'success', 'Photo album type added successfully.', ['id' => $newId, 'name' => $name]);
                    return ['success' => true, 'id' => $newId];
                }
                $logService->log('photo_album_types', 'error', 'type_name is required for add.', ['name' => $name]);
                return ['success' => false, 'error' => 'type_name is required.'];
            }
            break;

        case 'update':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;
                $name = trim($_POST['type_name'] ?? '');
                if (!empty($id) && !empty($name)) {
                    $stmt = $db->prepare("UPDATE photo_album_types SET name = ?, updated_at = datetime('now') WHERE id = ?");
                    $stmt->execute([$name, $id]);
                    $logService->log('photo_album_types', 'success', 'Photo album type updated successfully.', ['id' => $id, 'name' => $name]);
                    return ['success' => true];
                }
                $logService->log('photo_album_types', 'error', 'ID and type_name are required for update.', ['id' => $id, 'name' => $name]);
                return ['success' => false, 'error' => 'ID and type_name are required.'];
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM photo_album_types WHERE id = ?");
                    $stmt->execute([$id]);
                    $logService->log('photo_album_types', 'success', 'Photo album type deleted successfully.', ['id' => $id]);
                    return ['success' => true];
                }
                $logService->log('photo_album_types', 'error', 'ID is required for delete.', ['id' => $id]);
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("SELECT * FROM photo_album_types WHERE id = ?");
                    $stmt->execute([$id]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($data) {
                        $logService->log('photo_album_types', 'success', 'Photo album type retrieved successfully.', ['id' => $id]);
                        return ['success' => true, 'type' => $data];
                    } else {
                        $logService->log('photo_album_types', 'error', "Photo album type not found with ID: {$id}", ['id' => $id]);
                        return ['success' => false, 'error' => "Photo album type not found with ID: {$id}"];
                    }
                }
                $logService->log('photo_album_types', 'error', 'ID is required for get.', $_POST);
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'list':
            if ($method === 'POST') {
                $stmt = $db->query("SELECT id, name FROM photo_album_types");
                $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $logService->log('photo_album_types', 'success', 'Photo album types listed successfully.', ['count' => count($types)]);
                return ['success' => true, 'photo_album_types' => $types];
            }
            break;
    }

    $logService->log('photo_album_types', 'error', "Invalid request for action '{$action}' with method '{$method}'.", ['action' => $action, 'method' => $method]);
    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

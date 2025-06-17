<?php
function handle_photo_album_types($action, $method, $db)
{
    switch ($action) {
        case 'add':
            if ($method === 'POST') {
                $name = trim($_POST['type_name'] ?? '');
                if (!empty($name)) {
                    $stmt = $db->prepare("INSERT INTO photo_album_types (name, created_at, updated_at) VALUES (?, datetime('now'), datetime('now'))");
                    $stmt->execute([$name]);
                    return ['success' => true, 'id' => $db->lastInsertId()];
                }
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
                    return ['success' => true];
                }
                return ['success' => false, 'error' => 'ID and type_name are required.'];
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM photo_album_types WHERE id = ?");
                    $stmt->execute([$id]);
                    return ['success' => true];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("SELECT * FROM photo_album_types WHERE id = ?");
                    $stmt->execute([$id]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $data ? ['success' => true, 'type' => $data] : ['success' => false, 'error' => "Photo album type not found with ID: {$id}"];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'list':
            if ($method === 'POST') {
                $stmt = $db->query("SELECT id, name FROM photo_album_types");
                return ['success' => true, 'photo_album_types' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            }
            break;
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}
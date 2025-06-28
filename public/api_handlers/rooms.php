<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_rooms($action, $method, $db, $input = [])
{
    $logService = new LogService();
    switch ($action) {
        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;

                if (!$id) {
                    $logService->log('rooms', 'error', 'Room ID is required for get.', $input);
                    return ['success' => false, 'error' => 'Room ID is required.'];
                }

                $stmt = $db->prepare("SELECT id, name, type, is_active FROM rooms WHERE id = ?");
                $stmt->execute([$id]);
                $room = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$room) {
                    $logService->log('rooms', 'error', 'Room not found.', ['id' => $id]);
                    return ['success' => false, 'error' => 'Room not found.'];
                }
                $logService->log('rooms', 'success', 'Room retrieved successfully.', ['id' => $id]);
                return ['success' => true, 'room' => $room];
            }
            break;

        case 'list':
            if ($method === 'POST') {
                $date = $input['date'] ?? null;

                if ($date) {
                    // Get only available rooms for the specified date
                    $stmt = $db->prepare("
                        SELECT r.id, r.name,r.type, r.is_active,
                               CASE WHEN rr.id IS NOT NULL THEN 1 ELSE 0 END as is_booked
                        FROM rooms r
                        LEFT JOIN room_reservations rr ON r.id = rr.room_id AND rr.reserved_date = ?
                        WHERE r.is_active = 1 AND rr.id IS NULL
                        ORDER BY r.name
                    ");
                    $stmt->execute([$date]);
                } else {
                    // Get all rooms
                    $stmt = $db->prepare("SELECT id, name,
                     type, is_active FROM rooms ORDER BY name");
                    $stmt->execute();
                }

                $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $logService->log('rooms', 'success', 'Rooms listed successfully.', ['count' => count($rooms), 'date' => $date]);
                return ['success' => true, 'rooms' => $rooms];
            }
            break;

        case 'create':
        case 'add':
            if ($method === 'POST') {
                $name = trim($_POST['name'] ?? '');
                $type = trim($_POST['type'] ?? '');
                $created_by = $_POST['created_by'] ?? null;

                if (empty($name)) {
                    $logService->log('rooms', 'error', 'Room name is required for create.', $_POST);
                    return ['success' => false, 'error' => 'Room name is required.'];
                }

                try {
                    $stmt = $db->prepare("INSERT INTO rooms (name, type, created_at, updated_at, created_by) VALUES (?, ?, datetime('now'), datetime('now'), ?)");
                    $stmt->execute([$name, $type, $created_by]);
                    $room_id = $db->lastInsertId();

                    // Fetch the created room
                    $stmt = $db->prepare("SELECT id, name, type, is_active FROM rooms WHERE id = ?");
                    $stmt->execute([$room_id]);
                    $room = $stmt->fetch(PDO::FETCH_ASSOC);
                    $logService->log('rooms', 'success', 'Room created successfully.', ['id' => $room_id, 'name' => $name, 'type' => $type]);
                    return ['success' => true, 'message' => 'Room created successfully.', 'room' => $room];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        $logService->log('rooms', 'error', 'A room with this name already exists.', ['name' => $name]);
                        return ['success' => false, 'error' => 'A room with this name already exists.'];
                    }
                    $logService->log('rooms', 'error', 'Database error on create: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $_POST]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'update':
        case 'edit':
            if ($method === 'POST' || $method === 'PUT') {
                $id = $_POST['id'] ?? $input['id'] ?? null;
                $name = trim($_POST['name'] ?? $input['name'] ?? '');
                $type = trim($_POST['type'] ?? $input['type'] ?? '');
                $updated_by = $_POST['updated_by'] ?? $input['updated_by'] ?? null;

                if (!$id || empty($name)) {
                    $logService->log('rooms', 'error', 'Room ID and name are required for update.', $_POST);
                    return ['success' => false, 'error' => 'Room ID and name are required.'];
                }

                try {
                    $stmt = $db->prepare("UPDATE rooms SET name = ?, type = ?, updated_at = datetime('now'), updated_by = ? WHERE id = ?");
                    $stmt->execute([$name, $type, $updated_by, $id]);

                    if ($stmt->rowCount() === 0) {
                        $logService->log('rooms', 'error', 'Room not found for update.', ['id' => $id]);
                        return ['success' => false, 'error' => 'Room not found.'];
                    }
                    $logService->log('rooms', 'success', 'Room updated successfully.', ['id' => $id, 'name' => $name, 'type' => $type]);
                    return ['success' => true, 'message' => 'Room updated successfully.'];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        $logService->log('rooms', 'error', 'A room with this name already exists.', ['id' => $id, 'name' => $name]);
                        return ['success' => false, 'error' => 'A room with this name already exists.'];
                    }
                    $logService->log('rooms', 'error', 'Database error on update: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $_POST]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'toggle':
            if ($method === 'POST' || $method === 'DELETE') {
                $id = $_POST['id'] ?? $input['id'] ?? null;
                $status = $_POST['status'] ?? $input['status'] ?? null;
                $updated_by = $_POST['updated_by'] ?? $input['updated_by'] ?? null;

                if (!$id) {
                    $logService->log('rooms', 'error', 'Room ID is required for toggle.', $_POST);
                    return ['success' => false, 'error' => 'Room ID is required.'];
                }

                try {
                    // Soft delete - set is_active to 0
                    $stmt = $db->prepare("UPDATE rooms SET is_active = ?, updated_at = datetime('now'), updated_by = ? WHERE id = ?");
                    $stmt->execute([$status, $updated_by, $id]);

                    if ($stmt->rowCount() === 0) {
                        return ['success' => false, 'error' => 'Room not found.'];
                    }
                    if ($status === 0) {
                        $logService->log('rooms', 'success', 'Room archived successfully.', ['id' => $id]);
                        return ['success' => true, 'message' => 'Room archived successfully.'];
                    } else {
                        $logService->log('rooms', 'success', 'Room activated successfully.', ['id' => $id]);
                        return ['success' => true, 'message' => 'Room activated successfully.'];
                    }
                } catch (PDOException $e) {
                    $logService->log('rooms', 'error', 'Database error on toggle: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $_POST]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'delete':
            if ($method === 'POST' || $method === 'DELETE') {
                $id = $_POST['id'] ?? $input['id'] ?? null;

                if (!$id) {
                    $logService->log('rooms', 'error', 'Room ID is required for delete.', $_POST);
                    return ['success' => false, 'error' => 'Room ID is required.'];
                }

                try {
                    // Soft delete - set is_active to 0
                    $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
                    $stmt->execute([$id]);

                    if ($stmt->rowCount() === 0) {
                        $logService->log('rooms', 'error', 'Room not found for delete.', ['id' => $id]);
                        return ['success' => false, 'error' => 'Room not found.'];
                    }
                    $logService->log('rooms', 'success', 'Room deleted successfully.', ['id' => $id]);
                    return ['success' => true, 'message' => 'Room archived successfully.'];
                } catch (PDOException $e) {
                    $logService->log('rooms', 'error', 'Database error on delete: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $_POST]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;
        default:
            $logService->log('rooms', 'error', 'Invalid action for rooms entity.', ['action' => $action]);
            return ['success' => false, 'error' => 'Invalid action for rooms entity.'];
    }

    $logService->log('rooms', 'error', 'Invalid request method for this action.', ['action' => $action, 'method' => $method]);
    return ['success' => false, 'error' => 'Invalid request method for this action.'];
}

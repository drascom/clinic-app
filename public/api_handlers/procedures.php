<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_procedures($action, $method, $db, $input = [])
{
    $logService = new LogService();
    switch ($action) {
        case 'list':
            if ($method === 'POST') {
                try {
                    $stmt = $db->prepare("SELECT id, name, is_active, created_at, updated_at FROM procedures ORDER BY name");
                    $stmt->execute();
                    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $logService->log('procedures', 'success', 'Procedures listed successfully.', ['count' => count($procedures)]);
                    return ['success' => true, 'procedures' => $procedures];
                } catch (PDOException $e) {
                    $logService->log('procedures', 'error', 'Database error on list: ' . $e->getMessage(), ['error' => $e->getMessage()]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;

                if (!$id) {
                    $logService->log('procedures', 'error', 'Procedure ID is required for get.', $input);
                    return ['success' => false, 'error' => 'Procedure ID is required'];
                }

                try {
                    $stmt = $db->prepare("SELECT id, name, is_active, created_at, updated_at FROM procedures WHERE id = ?");
                    $stmt->execute([$id]);
                    $procedure = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($procedure) {
                        $logService->log('procedures', 'success', 'Procedure retrieved successfully.', ['id' => $id]);
                        return ['success' => true, 'procedure' => $procedure];
                    } else {
                        $logService->log('procedures', 'error', 'Procedure not found.', ['id' => $id]);
                        return ['success' => false, 'error' => 'Procedure not found'];
                    }
                } catch (PDOException $e) {
                    $logService->log('procedures', 'error', 'Database error on get: ' . $e->getMessage(), ['error' => $e->getMessage(), 'id' => $id]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'create':
            if ($method === 'POST') {
                $name = trim($_POST['name'] ?? $input['name'] ?? '');

                if (!$name) {
                    $logService->log('procedures', 'error', 'Procedure name is required for create.', $input);
                    return ['success' => false, 'error' => 'Procedure name is required'];
                }

                try {
                    $stmt = $db->prepare("INSERT INTO procedures (name, is_active, created_at, updated_at) VALUES (?, 1, datetime('now'), datetime('now'))");
                    $stmt->execute([$name]);
                    $newId = $db->lastInsertId();
                    $logService->log('procedures', 'success', 'Procedure created successfully.', ['id' => $newId, 'name' => $name]);
                    return ['success' => true, 'id' => $newId, 'message' => 'Procedure created successfully'];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        $logService->log('procedures', 'error', 'A procedure with this name already exists.', ['name' => $name]);
                        return ['success' => false, 'error' => 'A procedure with this name already exists'];
                    }
                    $logService->log('procedures', 'error', 'Database error on create: ' . $e->getMessage(), ['error' => $e->getMessage(), 'name' => $name]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'update':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? $input['id'] ?? null;
                $name = trim($_POST['name'] ?? $input['name'] ?? '');
                $is_active = $_POST['is_active'] ?? $input['is_active'] ?? 1;

                if (!$id || !$name) {
                    $logService->log('procedures', 'error', 'Procedure ID and name are required for update.', $input);
                    return ['success' => false, 'error' => 'Procedure ID and name are required'];
                }

                try {
                    // Check if procedure exists
                    $check_stmt = $db->prepare("SELECT id FROM procedures WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'Procedure not found'];
                    }

                    $stmt = $db->prepare("UPDATE procedures SET name = ?, is_active = ?, updated_at = datetime('now') WHERE id = ?");
                    $stmt->execute([$name, $is_active, $id]);
                    $logService->log('procedures', 'success', 'Procedure updated successfully.', ['id' => $id, 'name' => $name, 'is_active' => $is_active]);
                    return ['success' => true, 'message' => 'Procedure updated successfully'];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        $logService->log('procedures', 'error', 'A procedure with this name already exists.', ['id' => $id, 'name' => $name]);
                        return ['success' => false, 'error' => 'A procedure with this name already exists'];
                    }
                    $logService->log('procedures', 'error', 'Database error on update: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? $input['id'] ?? null;

                if (!$id) {
                    $logService->log('procedures', 'error', 'Procedure ID is required for delete.', $input);
                    return ['success' => false, 'error' => 'Procedure ID is required'];
                }

                try {
                    // Check if procedure exists
                    $check_stmt = $db->prepare("SELECT id FROM procedures WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'Procedure not found'];
                    }

                    // Check if procedure is being used in appointments
                    $usage_stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE procedure_id = ?");
                    $usage_stmt->execute([$id]);
                    $usage = $usage_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($usage['count'] > 0) {
                        return ['success' => false, 'error' => 'Cannot delete procedure: it is being used in ' . $usage['count'] . ' appointment(s)'];
                    }

                    $stmt = $db->prepare("DELETE FROM procedures WHERE id = ?");
                    $stmt->execute([$id]);
                    $logService->log('procedures', 'success', 'Procedure deleted successfully.', ['id' => $id]);
                    return ['success' => true, 'message' => 'Procedure deleted successfully'];
                } catch (PDOException $e) {
                    $logService->log('procedures', 'error', 'Database error on delete: ' . $e->getMessage(), ['error' => $e->getMessage(), 'id' => $id]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'active':
            if ($method === 'POST') {
                try {
                    $stmt = $db->prepare("SELECT id, name FROM procedures WHERE is_active = 1 ORDER BY name");
                    $stmt->execute();
                    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $logService->log('procedures', 'success', 'Active procedures listed successfully.', ['count' => count($procedures)]);
                    return ['success' => true, 'procedures' => $procedures];
                } catch (PDOException $e) {
                    $logService->log('procedures', 'error', 'Database error on list active: ' . $e->getMessage(), ['error' => $e->getMessage()]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        default:
            $logService->log('procedures', 'error', 'Invalid action for procedures entity.', ['action' => $action]);
            return ['success' => false, 'error' => 'Invalid action for procedures entity'];
    }

    $logService->log('procedures', 'error', 'Invalid request method for this action.', ['action' => $action, 'method' => $method]);
    return ['success' => false, 'error' => 'Invalid request method for this action'];
}

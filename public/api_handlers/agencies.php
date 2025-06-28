<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../services/LogService.php';

function handle_agencies($action, $method, $db, $input = [])
{
    $logService = new LogService();
    switch ($action) {
        case 'create':
            if ($method === 'POST') {
                $name = $_POST['name'] ?? null;

                if (!$name) {
                    return ['success' => false, 'error' => 'Agency name is required.'];
                }

                // Check if agency name already exists
                $stmt = $db->prepare("SELECT id FROM agencies WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'An agency with this name already exists.'];
                }

                $stmt = $db->prepare("INSERT INTO agencies (name, created_at, updated_at) VALUES (?, datetime('now'), datetime('now'))");
                $stmt->execute([$name]);
                $newId = $db->lastInsertId();
                $logService->log('agencies', 'success', 'Agency created successfully.', ['id' => $newId, 'name' => $name]);
                return [
                    'success' => true,
                    'id' => $newId,
                    'message' => 'Agency created successfully.'
                ];
            } else {
                $logService->log('agencies', 'error', 'Invalid method for create action.', ['method' => $method]);
            }
            break;

        case 'update':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;
                $name = $_POST['name'] ?? null;

                if (!$id || !$name) {
                    return ['success' => false, 'error' => 'Agency ID and name are required.'];
                }

                // Check if agency exists
                $check_stmt = $db->prepare("SELECT id FROM agencies WHERE id = ?");
                $check_stmt->execute([$id]);
                if (!$check_stmt->fetch()) {
                    return ['success' => false, 'error' => 'Agency not found.'];
                }

                // Check if agency name already exists for a different agency
                $stmt = $db->prepare("SELECT id FROM agencies WHERE name = ? AND id != ?");
                $stmt->execute([$name, $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'An agency with this name already exists.'];
                }

                $stmt = $db->prepare("UPDATE agencies SET name = ?, updated_at = datetime('now') WHERE id = ?");
                $stmt->execute([$name, $id]);
                $logService->log('agencies', 'success', 'Agency updated successfully.', ['id' => $id, 'name' => $name]);
                return ['success' => true, 'message' => 'Agency updated successfully.'];
            } else {
                $logService->log('agencies', 'error', 'Invalid method for update action.', ['method' => $method]);
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;

                if (!$id) {
                    return ['success' => false, 'error' => 'Agency ID is required.'];
                }

                // Check if agency exists
                $check_stmt = $db->prepare("SELECT id FROM agencies WHERE id = ?");
                $check_stmt->execute([$id]);
                if (!$check_stmt->fetch()) {
                    return ['success' => false, 'error' => 'Agency not found.'];
                }

                // Check if agency is associated with users or patients
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE agency_id = ?");
                $stmt->execute([$id]);
                $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                $stmt = $db->prepare("SELECT COUNT(*) as count FROM patients WHERE agency_id = ?");
                $stmt->execute([$id]);
                $patientCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                if ($userCount > 0 || $patientCount > 0) {
                    return [
                        'success' => false,
                        'error' => 'Cannot delete agency that has associated users or patients.'
                    ];
                }

                $stmt = $db->prepare("DELETE FROM agencies WHERE id = ?");
                $stmt->execute([$id]);
                $logService->log('agencies', 'success', 'Agency deleted successfully.', ['id' => $id]);
                return ['success' => true, 'message' => 'Agency deleted successfully.'];
            } else {
                $logService->log('agencies', 'error', 'Invalid method for delete action.', ['method' => $method]);
            }
            break;

        case 'get':
            if ($method === 'GET' || $method === 'POST') {
                $id = $method === 'GET' ? ($_GET['id'] ?? null) : ($input['id'] ?? null);

                if (!$id) {
                    return ['success' => false, 'error' => 'Agency ID is required.'];
                }

                $stmt = $db->prepare("SELECT * FROM agencies WHERE id = ?");
                $stmt->execute([$id]);
                $agency = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$agency) {
                    return ['success' => false, 'error' => 'Agency not found.'];
                }

                $logService->log('agencies', 'success', 'Agency retrieved successfully.', ['id' => $id]);
                return ['success' => true, 'agency' => $agency];
            } else {
                $logService->log('agencies', 'error', 'Invalid method for get action.', ['method' => $method]);
            }
            break;

        case 'list':
            if ($method === 'POST') {
                // Only admin and editor can see all agencies
                if (!is_admin() && !is_editor()) {
                    return ['success' => false, 'message' => 'Unauthorized access.', 'error' => 'Unauthorized access.'];
                }

                $stmt = $db->query("SELECT * FROM agencies ORDER BY name");
                $agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $logService->log('agencies', 'success', 'Agencies listed successfully.', ['count' => count($agencies)]);
                return ['success' => true, 'agencies' => $agencies];
            } else {
                $logService->log('agencies', 'error', 'Invalid method for list action.', ['method' => $method]);
            }
            break;
    }
    $logService->log('agencies', 'error', "Invalid request for action '{$action}' with method '{$method}'.", ['action' => $action, 'method' => $method]);
    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

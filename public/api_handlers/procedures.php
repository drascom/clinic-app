<?php
function handle_procedures($action, $method, $db, $input = [])
{
    switch ($action) {
        case 'list':
            if ($method === 'POST') {
                try {
                    $stmt = $db->prepare("SELECT id, name, is_active, created_at, updated_at FROM procedures ORDER BY name");
                    $stmt->execute();
                    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    return ['success' => true, 'procedures' => $procedures];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                
                if (!$id) {
                    return ['success' => false, 'error' => 'Procedure ID is required'];
                }
                
                try {
                    $stmt = $db->prepare("SELECT id, name, is_active, created_at, updated_at FROM procedures WHERE id = ?");
                    $stmt->execute([$id]);
                    $procedure = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($procedure) {
                        return ['success' => true, 'procedure' => $procedure];
                    } else {
                        return ['success' => false, 'error' => 'Procedure not found'];
                    }
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'create':
            if ($method === 'POST') {
                $name = trim($_POST['name'] ?? $input['name'] ?? '');
                
                if (!$name) {
                    return ['success' => false, 'error' => 'Procedure name is required'];
                }
                
                try {
                    $stmt = $db->prepare("INSERT INTO procedures (name, is_active, created_at, updated_at) VALUES (?, 1, datetime('now'), datetime('now'))");
                    $stmt->execute([$name]);
                    
                    return ['success' => true, 'id' => $db->lastInsertId(), 'message' => 'Procedure created successfully'];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        return ['success' => false, 'error' => 'A procedure with this name already exists'];
                    }
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
                    
                    return ['success' => true, 'message' => 'Procedure updated successfully'];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        return ['success' => false, 'error' => 'A procedure with this name already exists'];
                    }
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? $input['id'] ?? null;
                
                if (!$id) {
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
                    
                    return ['success' => true, 'message' => 'Procedure deleted successfully'];
                } catch (PDOException $e) {
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
                    
                    return ['success' => true, 'procedures' => $procedures];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        default:
            return ['success' => false, 'error' => 'Invalid action for procedures entity'];
    }

    return ['success' => false, 'error' => 'Invalid request method for this action'];
}

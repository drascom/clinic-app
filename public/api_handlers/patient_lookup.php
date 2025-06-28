<?php
require_once __DIR__ . '/../includes/db.php'; // Include database connection
require_once __DIR__ . '/../services/LogService.php';

function handle_patient_lookup($action, $method, $db, $input = [])
{
    $logService = new LogService();
    if ($method === 'POST' && $action === 'get') {
        $name = trim($input['name'] ?? '');
        if (!empty($name)) {
            $stmt = $db->prepare("SELECT id, name FROM patients WHERE name = ?");
            $stmt->execute([$name]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($patient) {
                $logService->log('patient_lookup', 'success', 'Patient found by name.', ['name' => $name, 'id' => $patient['id']]);
                return ['success' => true, 'patient' => $patient];
            } else {
                $logService->log('patient_lookup', 'info', 'Patient not found by name.', ['name' => $name]);
                return ['success' => false, 'error' => 'Patient not found.'];
            }
        }
        $logService->log('patient_lookup', 'error', 'Name parameter is required.', $input);
        return ['success' => false, 'error' => 'Name parameter is required.'];
    }

    $logService->log('patient_lookup', 'error', "Invalid request for action '{$action}' with method '{$method}'.", ['action' => $action, 'method' => $method]);
    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

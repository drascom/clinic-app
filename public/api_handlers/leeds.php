<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../services/LeedsService.php';
require_once __DIR__ . '/../services/LogService.php';

function handle_leeds($action, $method, $db, $input)
{
    $leedsService = new LeedsService($db);
    $logService = new LogService();

    switch ($action) {
        case 'get_submissions':
            $response = $leedsService->getSubmissions();
            return $response;

        case 'list_leeds':
            $stmt = $db->query("SELECT COUNT(*) FROM leeds");
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                $leedsService->getSubmissions();
            }

            $search = $input['search'] ?? '';
            $stmt = $db->prepare("SELECT * FROM leeds WHERE name LIKE :search OR email LIKE :search OR phone LIKE :search ORDER BY updated_at DESC");
            $stmt->execute(['search' => "%$search%"]);
            $leeds = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $logService->log('leeds_handler', 'success', 'list_leeds action', ['count' => count($leeds)]);
            return ['success' => true, 'leeds' => $leeds];

        case 'get_lead_details':
            $id = $input['id'] ?? null;
            if (!$id) {
                $logService->log('leeds_handler', 'error', 'get_lead_details action', ['message' => 'Lead ID is required']);
                return ['success' => false, 'message' => 'Lead ID is required'];
            }
            $stmt = $db->prepare("SELECT * FROM leeds WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT * FROM notes WHERE lead_id = :lead_id AND section_name = 'leeds' ORDER BY created_at ASC");
            $stmt->execute(['lead_id' => $id]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $logService->log('leeds_handler', 'success', 'get_lead_details action', ['lead_id' => $id]);
            return ['success' => true, 'lead' => $lead, 'notes' => $notes];

        case 'add_note':
            $lead_id = $input['lead_id'] ?? null;
            $note = $input['note'] ?? null;
            $user_id = $input['user_id'] ?? null;

            if (!$lead_id || !$note || !$user_id) {
                $logService->log('leeds_handler', 'error', 'add_note action', ['message' => 'Lead ID, note, and user ID are required']);
                return ['success' => false, 'message' => 'Lead ID, note, and user ID are required'];
            }

            $stmt = $db->prepare("INSERT INTO notes (lead_id, user_id, note, section_name) VALUES (:lead_id, :user_id, :note, 'leeds')");
            $result = $stmt->execute(['lead_id' => $lead_id, 'user_id' => $user_id, 'note' => $note]);

            if ($result) {
                // Also update the updated_at timestamp on the lead
                $stmt = $db->prepare("UPDATE leeds SET updated_at = CURRENT_TIMESTAMP WHERE id = :lead_id");
                $stmt->execute(['lead_id' => $lead_id]);
                $logService->log('leeds_handler', 'success', 'add_note action', ['lead_id' => $lead_id]);
                return ['success' => true];
            } else {
                $logService->log('leeds_handler', 'error', 'add_note action', ['lead_id' => $lead_id]);
                return ['success' => false];
            }

        case 'update_status':
            $lead_id = $input['lead_id'] ?? null;
            $status = $input['status'] ?? null;

            if (!$lead_id || !$status) {
                $logService->log('leeds_handler', 'error', 'update_status action', ['message' => 'Lead ID and status are required']);
                return ['success' => false, 'message' => 'Lead ID and status are required'];
            }
            
            $allowed_statuses = ['intake', 'not answered', 'not interested', 'qualified', 'converted'];
            if (!in_array($status, $allowed_statuses)) {
                $logService->log('leeds_handler', 'error', 'update_status action', ['message' => 'Invalid status value']);
                return ['success' => false, 'message' => 'Invalid status value'];
            }

            $stmt = $db->prepare("UPDATE leeds SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $result = $stmt->execute(['status' => $status, 'id' => $lead_id]);

            if ($result) {
                $logService->log('leeds_handler', 'success', 'update_status action', ['lead_id' => $lead_id, 'status' => $status]);
                return ['success' => true];
            } else {
                $logService->log('leeds_handler', 'error', 'update_status action', ['lead_id' => $lead_id, 'status' => $status]);
                return ['success' => false, 'message' => 'Failed to update status'];
            }

        case 'update_lead':
            $id = $input['id'] ?? null;
            if (!$id) {
                return ['success' => false, 'message' => 'Lead ID is required'];
            }

            $name = $input['name'] ?? null;
            $email = $input['email'] ?? null;
            $phone = $input['phone'] ?? null;
            $treatment = $input['treatment'] ?? null;
            $status = $input['status'] ?? null;

            $allowed_statuses = ['intake', 'not answered', 'not interested', 'qualified', 'converted'];
            if ($status && !in_array($status, $allowed_statuses)) {
                return ['success' => false, 'message' => 'Invalid status value'];
            }

            $stmt = $db->prepare("UPDATE leeds SET name = :name, email = :email, phone = :phone, treatment = :treatment, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $result = $stmt->execute([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'treatment' => $treatment,
                'status' => $status
            ]);

            if ($result) {
                $logService->log('leeds_handler', 'success', 'update_lead action', ['lead_id' => $id]);
                return ['success' => true];
            } else {
                $logService->log('leeds_handler', 'error', 'update_lead action', ['lead_id' => $id]);
                return ['success' => false, 'message' => 'Failed to update lead'];
            }

        case 'get_recent_leeds':
            $response = $leedsService->getSubmissions();
            $response = $leedsService->getRecentLeads();
            return $response;

        case 'delete_note':
            $id = $input['id'] ?? null;
            if (!$id) {
                $logService->log('leeds_handler', 'error', 'delete_note action', ['message' => 'Note ID is required']);
                return ['success' => false, 'message' => 'Note ID is required'];
            }

            $stmt = $db->prepare("DELETE FROM notes WHERE id = :id");
            $result = $stmt->execute(['id' => $id]);

            if ($result) {
                $logService->log('leeds_handler', 'success', 'delete_note action', ['note_id' => $id]);
                return ['success' => true];
            } else {
                $logService->log('leeds_handler', 'error', 'delete_note action', ['note_id' => $id]);
                return ['success' => false, 'message' => 'Failed to delete note'];
            }

        default:
            $logService->log('leeds_handler', 'error', 'Unknown action', ['action' => $action]);
            return ['success' => false, 'message' => 'Unknown action for leeds'];
    }
}
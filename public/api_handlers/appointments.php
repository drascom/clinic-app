<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_appointments($action, $method, $db, $input = [])
{
    $logService = new LogService();
    switch ($action) {
        case 'create':
            if ($method === 'POST') {
                $room_id = $_POST['room_id'] ?? $input['room_id'] ?? null;
                $patient_id = $_POST['patient_id'] ?? $input['patient_id'] ?? null;
                $appointment_date = $_POST['appointment_date'] ?? $input['appointment_date'] ?? null;
                $start_time = $_POST['start_time'] ?? $input['start_time'] ?? null;
                $end_time = $_POST['end_time'] ?? $input['end_time'] ?? null;
                $procedure_id = $_POST['procedure_id'] ?? $input['procedure_id'] ?? null;
                $notes = $_POST['notes'] ?? $input['notes'] ?? null;
                $created_by = $input['authenticated_user_id'] ?? null;
                $consultation_type = $_POST['consultation_type'] ?? $input['consultation_type'] ?? 'face-to-face';

                if (!$room_id || !$patient_id || !$appointment_date || !$start_time || !$end_time || !$procedure_id) {
                    $logService->log('appointments', 'error', 'Missing required fields for create appointment.', $input);
                    return ['success' => false, 'error' => 'Missing required fields'];
                }

                // Check for time overlap
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count FROM appointments 
                    WHERE room_id = ? AND appointment_date = ? 
                    AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))
                ");
                $stmt->execute([$room_id, $appointment_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result['count'] > 0) {
                    $logService->log('appointments', 'error', 'Time slot overlaps with existing appointment.', $input);
                    return ['success' => false, 'error' => 'Time slot overlaps with existing appointment'];
                }

                try {
                    $stmt = $db->prepare("
                        INSERT INTO appointments (room_id, patient_id, appointment_date, start_time, end_time, procedure_id, notes, created_by, consultation_type)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$room_id, $patient_id, $appointment_date, $start_time, $end_time, $procedure_id, $notes, $created_by, $consultation_type]);
                    $newId = $db->lastInsertId();
                    $logService->log('appointments', 'success', 'Appointment created successfully.', ['id' => $newId, 'patient_id' => $patient_id, 'date' => $appointment_date]);
                    return ['success' => true, 'id' => $newId];
                } catch (PDOException $e) {
                    $logService->log('appointments', 'error', 'Database error during create appointment: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            } else {
                $logService->log('appointments', 'error', 'Invalid method for create action.', ['method' => $method]);
            }
            break;

        case 'update':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? $input['id'] ?? null;
                $room_id = $_POST['room_id'] ?? $input['room_id'] ?? null;
                $patient_id = $_POST['patient_id'] ?? $input['patient_id'] ?? null;
                $appointment_date = $_POST['appointment_date'] ?? $input['appointment_date'] ?? null;
                $start_time = $_POST['start_time'] ?? $input['start_time'] ?? null;
                $end_time = $_POST['end_time'] ?? $input['end_time'] ?? null;
                $procedure_id = $_POST['procedure_id'] ?? $input['procedure_id'] ?? null;
                $notes = $_POST['notes'] ?? $input['notes'] ?? null;
                $updated_by = $input['authenticated_user_id'] ?? null;
                $consultation_type = $_POST['consultation_type'] ?? $input['consultation_type'] ?? 'face-to-face';

                if (!$id || !$room_id || !$patient_id || !$appointment_date || !$start_time || !$end_time || !$procedure_id) {
                    $logService->log('appointments', 'error', 'Missing required fields for update appointment.', $input);
                    return ['success' => false, 'error' => 'Missing required fields'];
                }

                // Check for time overlap (excluding current appointment)
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count FROM appointments
                    WHERE room_id = ? AND appointment_date = ? AND id != ?
                    AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))
                ");
                $stmt->execute([$room_id, $appointment_date, $id, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result['count'] > 0) {
                    $logService->log('appointments', 'error', 'Time slot overlaps with existing appointment during update.', $input);
                    return ['success' => false, 'error' => 'Time slot overlaps with existing appointment'];
                }

                try {
                    $stmt = $db->prepare("
                        UPDATE appointments
                        SET room_id = ?, patient_id = ?, appointment_date = ?, start_time = ?, end_time = ?,
                            procedure_id = ?, notes = ?, updated_at = datetime('now'), updated_by = ?, consultation_type = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$room_id, $patient_id, $appointment_date, $start_time, $end_time, $procedure_id, $notes, $updated_by, $consultation_type, $id]);
                    $logService->log('appointments', 'success', 'Appointment updated successfully.', ['id' => $id, 'patient_id' => $patient_id, 'date' => $appointment_date]);
                    return ['success' => true, 'message' => 'Appointment updated successfully'];
                } catch (PDOException $e) {
                    $logService->log('appointments', 'error', 'Database error during update appointment: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            } else {
                $logService->log('appointments', 'error', 'Invalid method for update action.', ['method' => $method]);
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? $input['id'] ?? null;

                if (!$id) {
                    $logService->log('appointments', 'error', 'Appointment ID is required for delete.', $input);
                    return ['success' => false, 'error' => 'Appointment ID is required'];
                }

                try {
                    $stmt = $db->prepare("DELETE FROM appointments WHERE id = ?");
                    $stmt->execute([$id]);

                    if ($stmt->rowCount() > 0) {
                        return ['success' => true, 'message' => 'Appointment deleted successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Appointment not found'];
                    }
                } catch (PDOException $e) {
                    $logService->log('appointments', 'error', 'Database error during delete appointment: ' . $e->getMessage(), ['error' => $e->getMessage(), 'id' => $id]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            } else {
                $logService->log('appointments', 'error', 'Invalid method for delete action.', ['method' => $method]);
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;

                if (!$id) {
                    $logService->log('appointments', 'error', 'Appointment ID is required for get.', $input);
                    return ['success' => false, 'error' => 'Appointment ID is required'];
                }

                try {
                    $stmt = $db->prepare("
                        SELECT a.*, p.name as patient_name, r.name as room_name, pr.name as procedure_name
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.id
                        JOIN rooms r ON a.room_id = r.id
                        LEFT JOIN procedures pr ON a.procedure_id = pr.id
                        WHERE a.id = ?
                    ");
                    $stmt->execute([$id]);
                    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($appointment) {
                        return ['success' => true, 'appointment' => $appointment];
                    } else {
                        return ['success' => false, 'error' => 'Appointment not found'];
                    }
                } catch (PDOException $e) {
                    $logService->log('appointments', 'error', 'Database error during get appointment: ' . $e->getMessage(), ['error' => $e->getMessage(), 'id' => $id]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            } else {
                $logService->log('appointments', 'error', 'Invalid method for get action.', ['method' => $method]);
            }
            break;

        case 'list':
            if ($method === 'POST') {
                $date = $input['date'] ?? null;
                $room_id = $input['room_id'] ?? null;
                $type = $input['type'] ?? null;

                try {
                    $sql = "
                        SELECT a.*, p.name as patient_name, r.name as room_name, pr.name as procedure_name
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.id
                        JOIN rooms r ON a.room_id = r.id
                        LEFT JOIN procedures pr ON a.procedure_id = pr.id
                        WHERE 1=1
                    ";
                    $params = [];

                    if ($date) {
                        $sql .= " AND a.appointment_date = ?";
                        $params[] = $date;
                    }

                    if ($room_id) {
                        $sql .= " AND a.room_id = ?";
                        $params[] = $room_id;
                    }

                    if ($type) {
                        $sql .= " AND a.procedure_id = ?";
                        $params[] = $type;
                    }

                    $sql .= " ORDER BY a.appointment_date DESC, a.start_time";

                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    return ['success' => true, 'appointments' => $appointments];
                } catch (PDOException $e) {
                    $logService->log('appointments', 'error', 'Database error during list appointments: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            } else {
                $logService->log('appointments', 'error', 'Invalid method for list action.', ['method' => $method]);
            }
            break;
    break;

case 'get_available_slots':
    if ($method === 'POST') {
        $date = $input['date'] ?? null;
        $room_id = $input['room_id'] ?? null;

        if (!$date || !$room_id) {
            return ['success' => false, 'error' => 'Date and Room ID are required'];
        }

        try {
            $stmt = $db->prepare("
                SELECT start_time, end_time FROM appointments
                WHERE appointment_date = ? AND room_id = ?
            ");
            $stmt->execute([$date, $room_id]);
            $booked_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'booked_slots' => $booked_slots];
        } catch (PDOException $e) {
            $logService->log('appointments', 'error', 'Database error during get_available_slots: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    } else {
        $logService->log('appointments', 'error', 'Invalid method for get_available_slots action.', ['method' => $method]);
    }
    break;
}
$logService->log('appointments', 'error', 'Invalid action for appointments entity.', ['action' => $action, 'method' => $method]);
return ['success' => false, 'error' => 'Invalid action for appointments entity'];
}

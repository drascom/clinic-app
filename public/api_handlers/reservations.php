<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_reservations($action, $method, $db, $input = [])
{
    $logService = new LogService();
    switch ($action) {
        case 'reserve':
            if ($method === 'POST') {
                $room_id = $input['room_id'] ?? null;
                $surgery_id = $input['surgery_id'] ?? null;
                $reserved_date = $input['reserved_date'] ?? null;

                if (!$room_id || !$surgery_id || !$reserved_date) {
                    $logService->log('reservations', 'error', 'Room ID, surgery ID, and reserved date are required for reserve.', $input);
                    return ['success' => false, 'error' => 'Room ID, surgery ID, and reserved date are required.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reserved_date)) {
                    $logService->log('reservations', 'error', 'Invalid date format for reserve.', ['date' => $reserved_date]);
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    // Check if room exists and is active
                    $stmt = $db->prepare("SELECT id, name, is_active FROM rooms WHERE id = ?");
                    $stmt->execute([$room_id]);
                    $room = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$room) {
                        return ['success' => false, 'error' => 'Room not found.'];
                    }

                    if (!$room['is_active']) {
                        return ['success' => false, 'error' => 'Room is not active.'];
                    }

                    // Check if surgery exists
                    $stmt = $db->prepare("SELECT id FROM surgeries WHERE id = ?");
                    $stmt->execute([$surgery_id]);
                    if (!$stmt->fetch()) {
                        return ['success' => false, 'error' => 'Surgery not found.'];
                    }

                    // Check if room is already booked for this date
                    $stmt = $db->prepare("SELECT id FROM room_reservations WHERE room_id = ? AND reserved_date = ?");
                    $stmt->execute([$room_id, $reserved_date]);
                    if ($stmt->fetch()) {
                        http_response_code(409); // Conflict
                        return ['success' => false, 'error' => 'Room already booked for this date.'];
                    }

                    // Create the reservation
                    $stmt = $db->prepare("INSERT INTO room_reservations (room_id, surgery_id, reserved_date, created_at) VALUES (?, ?, ?, datetime('now'))");
                    $stmt->execute([$room_id, $surgery_id, $reserved_date]);
                    $reservation_id = $db->lastInsertId();

                    // Fetch the created reservation with details
                    $stmt = $db->prepare("
                        SELECT
                            rr.id,
                            rr.room_id,
                            rr.surgery_id,
                            rr.reserved_date,
                            r.name as room_name,
                            p.name as patient_name,
                            s.graft_count
                        FROM room_reservations rr
                        JOIN rooms r ON rr.room_id = r.id
                        JOIN surgeries s ON rr.surgery_id = s.id
                        JOIN patients p ON s.patient_id = p.id
                        WHERE rr.id = ?
                    ");
                    $stmt->execute([$reservation_id]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                    $logService->log('reservations', 'success', 'Room reserved successfully.', ['id' => $reservation_id, 'room_id' => $room_id, 'surgery_id' => $surgery_id, 'date' => $reserved_date]);
                    return ['success' => true, 'message' => 'Room reserved successfully.', 'reservation' => $reservation];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        http_response_code(409); // Conflict
                        $logService->log('reservations', 'error', 'Room already booked for this date.', $input);
                        return ['success' => false, 'error' => 'Room already booked for this date.'];
                    }
                    $logService->log('reservations', 'error', 'Database error on reserve: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'cancel':
            if ($method === 'POST' || $method === 'DELETE') {
                $id = $_POST['id'] ?? $input['id'] ?? $_GET['id'] ?? null;

                if (!$id) {
                    $logService->log('reservations', 'error', 'Reservation ID is required for cancel.', $input);
                    return ['success' => false, 'error' => 'Reservation ID is required.'];
                }

                try {
                    // Check if reservation exists
                    $stmt = $db->prepare("
                        SELECT
                            rr.id,
                            r.name as room_name,
                            p.name as patient_name,
                            rr.reserved_date
                        FROM room_reservations rr
                        JOIN rooms r ON rr.room_id = r.id
                        JOIN surgeries s ON rr.surgery_id = s.id
                        JOIN patients p ON s.patient_id = p.id
                        WHERE rr.id = ?
                    ");
                    $stmt->execute([$id]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$reservation) {
                        return ['success' => false, 'error' => 'Reservation not found.'];
                    }

                    // Delete the reservation
                    $stmt = $db->prepare("DELETE FROM room_reservations WHERE id = ?");
                    $stmt->execute([$id]);
                    $logService->log('reservations', 'success', 'Reservation cancelled successfully.', ['id' => $id]);
                    return ['success' => true, 'message' => 'Reservation cancelled successfully.', 'cancelled_reservation' => $reservation];
                } catch (PDOException $e) {
                    $logService->log('reservations', 'error', 'Database error on cancel: ' . $e->getMessage(), ['error' => $e->getMessage(), 'id' => $id]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'get':
            if ($method === 'GET') {
                $id = $_GET['id'] ?? null;

                if (!$id) {
                    $logService->log('reservations', 'error', 'Reservation ID is required for get.', $_GET);
                    return ['success' => false, 'error' => 'Reservation ID is required.'];
                }

                try {
                    $stmt = $db->prepare("
                        SELECT
                            rr.id,
                            rr.room_id,
                            rr.surgery_id,
                            rr.reserved_date,
                            r.name as room_name,
                            p.name as patient_name,
                            s.graft_count,
                            s.status as surgery_status
                        FROM room_reservations rr
                        JOIN rooms r ON rr.room_id = r.id
                        JOIN surgeries s ON rr.surgery_id = s.id
                        JOIN patients p ON s.patient_id = p.id
                        WHERE rr.id = ?
                    ");
                    $stmt->execute([$id]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$reservation) {
                        return ['success' => false, 'error' => 'Reservation not found.'];
                    }

                    $logService->log('reservations', 'success', 'Reservation retrieved successfully.', ['id' => $id]);
                    return ['success' => true, 'reservation' => $reservation];
                } catch (PDOException $e) {
                    $logService->log('reservations', 'error', 'Database error on get: ' . $e->getMessage(), ['error' => $e->getMessage(), 'id' => $id]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'update':
            if ($method === 'POST' || $method === 'PUT') {
                $id = $_POST['id'] ?? $input['id'] ?? null;
                $room_id = $_POST['room_id'] ?? $input['room_id'] ?? null;
                $surgery_id = $_POST['surgery_id'] ?? $input['surgery_id'] ?? null;
                $reserved_date = $_POST['reserved_date'] ?? $input['reserved_date'] ?? null;

                if (!$id || !$room_id || !$surgery_id || !$reserved_date) {
                    $logService->log('reservations', 'error', 'Reservation ID, room ID, surgery ID, and reserved date are required for update.', $input);
                    return ['success' => false, 'error' => 'Reservation ID, room ID, surgery ID, and reserved date are required.'];
                }

                // Validate date format
                if (!DateTime::createFromFormat('Y-m-d', $reserved_date)) {
                    $logService->log('reservations', 'error', 'Invalid date format for update.', ['date' => $reserved_date]);
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    // Check if reservation exists
                    $check_stmt = $db->prepare("SELECT id FROM room_reservations WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'Reservation not found.'];
                    }

                    // Check if room is available on the new date (excluding current reservation)
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as count
                        FROM room_reservations
                        WHERE room_id = ? AND reserved_date = ? AND id != ?
                    ");
                    $stmt->execute([$room_id, $reserved_date, $id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($result['count'] > 0) {
                        http_response_code(409); // Conflict
                        return ['success' => false, 'error' => 'Room is already booked for this date.'];
                    }

                    // Update the reservation
                    $stmt = $db->prepare("
                        UPDATE room_reservations
                        SET room_id = ?, surgery_id = ?, reserved_date = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$room_id, $surgery_id, $reserved_date, $id]);
                    $logService->log('reservations', 'success', 'Reservation updated successfully.', ['id' => $id, 'room_id' => $room_id, 'surgery_id' => $surgery_id, 'date' => $reserved_date]);
                    return ['success' => true, 'message' => 'Reservation updated successfully.'];
                } catch (PDOException $e) {
                    $logService->log('reservations', 'error', 'Database error on update: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'list':
            if ($method === 'GET') {
                $room_id = $_GET['room_id'] ?? null;
                $surgery_id = $_GET['surgery_id'] ?? null;
                $date = $_GET['date'] ?? null;

                $sql = "
                    SELECT
                        rr.id,
                        rr.room_id,
                        rr.surgery_id,
                        rr.reserved_date,
                        r.name as room_name,
                        p.name as patient_name,
                        s.graft_count,
                        s.status as surgery_status
                    FROM room_reservations rr
                    JOIN rooms r ON rr.room_id = r.id
                    JOIN surgeries s ON rr.surgery_id = s.id
                    JOIN patients p ON s.patient_id = p.id
                    WHERE 1=1
                ";

                $params = [];

                if ($room_id) {
                    $sql .= " AND rr.room_id = ?";
                    $params[] = $room_id;
                }

                if ($surgery_id) {
                    $sql .= " AND rr.surgery_id = ?";
                    $params[] = $surgery_id;
                }

                if ($date) {
                    $sql .= " AND rr.reserved_date = ?";
                    $params[] = $date;
                }

                $sql .= " ORDER BY rr.reserved_date DESC, r.name";

                try {
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $logService->log('reservations', 'success', 'Reservations listed successfully.', ['count' => count($reservations)]);
                    return ['success' => true, 'reservations' => $reservations];
                } catch (PDOException $e) {
                    $logService->log('reservations', 'error', 'Database error on list: ' . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $_GET]);
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        default:
            $logService->log('reservations', 'error', 'Invalid action for reservations entity.', ['action' => $action]);
            return ['success' => false, 'error' => 'Invalid action for reservations entity.'];
    }

    $logService->log('reservations', 'error', 'Invalid request method for this action.', ['action' => $action, 'method' => $method]);
    return ['success' => false, 'error' => 'Invalid request method for this action.'];
}

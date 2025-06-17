<?php
function handle_surgeries($action, $method, $db, $input = [])
{
    switch ($action) {
        case 'add':
            if ($method === 'POST') {
                $date = trim($_POST['date'] ?? '');
                $notes = trim($_POST['notes'] ?? '');
                $status = trim($_POST['status'] ?? 'reserved'); // Default to 'reserved'
                $room_id = trim($_POST['room_id'] ?? '');
                $predicted_grafts_count = $_POST['predicted_grafts_count'] ?? null;
                $current_grafts_count = $_POST['current_grafts_count'] ?? null;
                $patient_id = $_POST['patient_id'] ?? null;
                $technician_ids = $_POST['technician_ids'] ?? [];
                $period = $_POST['period'] ?? 'full'; // Surgery period (am, pm, full)

                if (!$date || !$patient_id) {
                    return ['success' => false, 'error' => 'Date and patient_id are required.'];
                }

                // Check if this is a quick add (allows surgery without technicians)
                $is_quick_add = isset($_POST['quick_add']) && $_POST['quick_add'] === 'true';

                // Validate technicians (minimum 2) - skip for quick add
                if (!$is_quick_add && count($technician_ids) < 2) {
                    return ['success' => false, 'error' => 'At least 2 technicians must be assigned.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    $db->beginTransaction();

                    // Check if patient exists
                    $stmt = $db->prepare("SELECT id FROM patients WHERE id = ?");
                    $stmt->execute([$patient_id]);
                    if (!$stmt->fetch()) {
                        $db->rollBack();
                        return ['success' => false, 'error' => 'Patient not found.'];
                    }

                    // Validate room availability if room is specified
                    if ($room_id) {
                        $room_check = $db->prepare("
                            SELECT r.id, r.is_active, rr.id as reservation_id
                            FROM rooms r
                            LEFT JOIN room_reservations rr ON r.id = rr.room_id AND rr.reserved_date = ?
                            WHERE r.id = ?
                        ");
                        $room_check->execute([$date, $room_id]);
                        $room_data = $room_check->fetch(PDO::FETCH_ASSOC);

                        if (!$room_data) {
                            $db->rollBack();
                            return ['success' => false, 'error' => 'Room not found.'];
                        }

                        if (!$room_data['is_active']) {
                            $db->rollBack();
                            return ['success' => false, 'error' => 'Room is not active.'];
                        }

                        if ($room_data['reservation_id']) {
                            $db->rollBack();
                            return ['success' => false, 'error' => 'Room is already booked for this date.'];
                        }
                    }

                    // Validate technician availability (skip for quick add)
                    if (!$is_quick_add) {
                        foreach ($technician_ids as $staff_id) {
                            $tech_check = $db->prepare("
                                SELECT t.id, t.is_active, ta.id as availability_id
                                FROM staff t
                                LEFT JOIN staff_availability ta ON t.id = ta.staff_id
                                    AND ta.available_on = ?
                                    AND (ta.period = ? OR ta.period = 'full')
                                WHERE t.id = ?
                            ");
                            $tech_check->execute([$date, $period, $staff_id]);
                            $tech_data = $tech_check->fetch(PDO::FETCH_ASSOC);

                            if (!$tech_data) {
                                $db->rollBack();
                                return ['success' => false, 'error' => "Technician with ID {$staff_id} not found."];
                            }

                            if (!$tech_data['is_active']) {
                                $db->rollBack();
                                return ['success' => false, 'error' => "Technician with ID {$staff_id} is not active."];
                            }

                            if (!$tech_data['availability_id']) {
                                $db->rollBack();
                                return ['success' => false, 'error' => "Technician with ID {$staff_id} is not available for the selected date and period."];
                            }
                        }
                    }

                    // Auto-determine status based on business rules
                    if ($room_id && count($technician_ids) >= 2) {
                        $status = 'confirmed'; // Auto-confirm when room and 2+ technicians assigned
                    } elseif ($is_quick_add) {
                        $status = 'reserved'; // Quick add surgeries start as reserved
                    } else {
                        $status = 'scheduled'; // Default status for new surgeries with technicians
                    }

                    // Insert surgery
                    $stmt = $db->prepare("INSERT INTO surgeries (date, notes, status, room_id, predicted_grafts_count, current_grafts_count, patient_id, is_recorded, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
                    $stmt->execute([$date, $notes, $status, $room_id, $predicted_grafts_count, $current_grafts_count, $patient_id, TRUE]);
                    $surgery_id = $db->lastInsertId();

                    // Reserve room if provided
                    if ($room_id) {
                        $reserve_stmt = $db->prepare("INSERT INTO room_reservations (room_id, surgery_id, reserved_date) VALUES (?, ?, ?)");
                        $reserve_stmt->execute([$room_id, $surgery_id, $date]);
                    }

                    // Assign technicians
                    foreach ($technician_ids as $staff_id) {
                        $tech_stmt = $db->prepare("INSERT INTO surgery_staff (surgery_id, staff_id) VALUES (?, ?)");
                        $tech_stmt->execute([$surgery_id, $staff_id]);
                    }

                    $db->commit();
                    return ['success' => true, 'message' => 'Surgery added successfully.', 'id' => $surgery_id, 'status' => $status];
                } catch (PDOException $e) {
                    $db->rollBack();
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        return ['success' => false, 'error' => 'Room is already booked for this date or technician is already assigned.'];
                    }
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'update':
        case 'edit':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;
                $date = trim($_POST['date'] ?? '');
                $room_id = trim($_POST['room_id'] ?? '');
                $notes = trim($_POST['notes'] ?? '');
                $status = trim($_POST['status'] ?? '');
                $predicted_grafts_count = $_POST['predicted_grafts_count'] ?? null;
                $current_grafts_count = $_POST['current_grafts_count'] ?? null;
                $patient_id = $_POST['patient_id'] ?? null;
                $technician_ids = $_POST['technician_ids'] ?? [];
                $period = $_POST['period'] ?? 'full';

                if (!$id || !$date || !$patient_id) {
                    return ['success' => false, 'error' => 'ID, date, and patient_id are required.'];
                }

                // Check if this is a quick add (allows surgery without technicians)
                $is_quick_add = isset($_POST['quick_add']) && $_POST['quick_add'] === 'true';

                // Validate technicians (minimum 2) - skip for quick add
                if (!$is_quick_add && count($technician_ids) < 2) {
                    return ['success' => false, 'error' => 'At least 2 technicians must be assigned.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    $db->beginTransaction();

                    // Check if surgery exists and get current data
                    $check_stmt = $db->prepare("SELECT id, room_id, date FROM surgeries WHERE id = ?");
                    $check_stmt->execute([$id]);
                    $current_surgery = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$current_surgery) {
                        $db->rollBack();
                        return ['success' => false, 'error' => 'Surgery not found.'];
                    }

                    // Check if patient exists
                    $stmt = $db->prepare("SELECT id FROM patients WHERE id = ?");
                    $stmt->execute([$patient_id]);
                    if (!$stmt->fetch()) {
                        $db->rollBack();
                        return ['success' => false, 'error' => 'Patient not found.'];
                    }

                    // Validate room availability if room is specified and different from current
                    if ($room_id && ($room_id != $current_surgery['room_id'] || $date != $current_surgery['date'])) {
                        $room_check = $db->prepare("
                            SELECT r.id, r.is_active, rr.id as reservation_id, rr.surgery_id
                            FROM rooms r
                            LEFT JOIN room_reservations rr ON r.id = rr.room_id AND rr.reserved_date = ?
                            WHERE r.id = ?
                        ");
                        $room_check->execute([$date, $room_id]);
                        $room_data = $room_check->fetch(PDO::FETCH_ASSOC);

                        if (!$room_data) {
                            $db->rollBack();
                            return ['success' => false, 'error' => 'Room not found.'];
                        }

                        if (!$room_data['is_active']) {
                            $db->rollBack();
                            return ['success' => false, 'error' => 'Room is not active.'];
                        }

                        // Room is booked by another surgery
                        if ($room_data['reservation_id'] && $room_data['surgery_id'] != $id) {
                            $db->rollBack();
                            return ['success' => false, 'error' => 'Room is already booked for this date.'];
                        }
                    }

                    // Validate technician availability (skip for quick add)
                    if (!$is_quick_add) {
                        foreach ($technician_ids as $staff_id) {
                            $tech_check = $db->prepare("
                                SELECT t.id, t.is_active, ta.id as availability_id
                                FROM staff t
                                LEFT JOIN staff_availability ta ON t.id = ta.staff_id
                                    AND ta.available_on = ?
                                    AND (ta.period = ? OR ta.period = 'full')
                                WHERE t.id = ?
                            ");
                            $tech_check->execute([$date, $period, $staff_id]);
                            $tech_data = $tech_check->fetch(PDO::FETCH_ASSOC);

                            if (!$tech_data) {
                                $db->rollBack();
                                return ['success' => false, 'error' => "Technician with ID {$staff_id} not found."];
                            }

                            if (!$tech_data['is_active']) {
                                $db->rollBack();
                                return ['success' => false, 'error' => "Technician with ID {$staff_id} is not active."];
                            }

                            if (!$tech_data['availability_id']) {
                                $db->rollBack();
                                return ['success' => false, 'error' => "Technician with ID {$staff_id} is not available for the selected date and period."];
                            }
                        }
                    }

                    // Auto-determine status based on business rules (unless manually overridden)
                    if (!$status) {
                        if ($room_id && count($technician_ids) >= 2) {
                            $status = 'confirmed';
                        } elseif (count($technician_ids) === 0) {
                            $status = 'reserved'; // No technicians assigned
                        } else {
                            $status = 'scheduled'; // Some technicians but less than 2
                        }
                    }

                    // Remove old room reservation if room changed
                    if ($current_surgery['room_id'] && $current_surgery['room_id'] != $room_id) {
                        $remove_reservation = $db->prepare("DELETE FROM room_reservations WHERE surgery_id = ?");
                        $remove_reservation->execute([$id]);
                    }

                    // Update surgery
                    $stmt = $db->prepare("UPDATE surgeries SET date = ?, notes = ?, status = ?, predicted_grafts_count = ?, current_grafts_count = ?, room_id = ?, patient_id = ?, updated_at = datetime('now') WHERE id = ?");
                    $stmt->execute([$date, $notes, $status, $predicted_grafts_count, $current_grafts_count, $room_id, $patient_id, $id]);

                    // Add new room reservation if room is specified
                    if ($room_id) {
                        // Check if reservation already exists for this surgery
                        $existing_reservation = $db->prepare("SELECT id FROM room_reservations WHERE surgery_id = ?");
                        $existing_reservation->execute([$id]);
                        if (!$existing_reservation->fetch()) {
                            $reserve_stmt = $db->prepare("INSERT INTO room_reservations (room_id, surgery_id, reserved_date) VALUES (?, ?, ?)");
                            $reserve_stmt->execute([$room_id, $id, $date]);
                        } else {
                            // Update existing reservation
                            $update_reservation = $db->prepare("UPDATE room_reservations SET room_id = ?, reserved_date = ? WHERE surgery_id = ?");
                            $update_reservation->execute([$room_id, $date, $id]);
                        }
                    }

                    // Remove old technician assignments
                    $remove_techs = $db->prepare("DELETE FROM surgery_staff WHERE surgery_id = ?");
                    $remove_techs->execute([$id]);

                    // Add new technician assignments
                    foreach ($technician_ids as $staff_id) {
                        $tech_stmt = $db->prepare("INSERT INTO surgery_staff (surgery_id, staff_id) VALUES (?, ?)");
                        $tech_stmt->execute([$id, $staff_id]);
                    }

                    $db->commit();
                    return ['success' => true, 'message' => 'Surgery updated successfully.', 'status' => $status];
                } catch (PDOException $e) {
                    $db->rollBack();
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        return ['success' => false, 'error' => 'Room is already booked for this date or technician is already assigned.'];
                    }
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? null;

                if (!$id) {
                    return ['success' => false, 'error' => 'ID is required.'];
                }

                try {
                    $db->beginTransaction();

                    // Check if surgery exists
                    $check_stmt = $db->prepare("SELECT id FROM surgeries WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        $db->rollBack();
                        return ['success' => false, 'error' => 'Surgery not found.'];
                    }

                    // Delete related records (foreign key constraints will handle this automatically, but being explicit)
                    $db->prepare("DELETE FROM room_reservations WHERE surgery_id = ?")->execute([$id]);
                    $db->prepare("DELETE FROM surgery_staff WHERE surgery_id = ?")->execute([$id]);

                    // Delete the surgery
                    $stmt = $db->prepare("DELETE FROM surgeries WHERE id = ?");
                    $stmt->execute([$id]);

                    $db->commit();
                    return ['success' => true, 'message' => 'Surgery deleted successfully.'];
                } catch (PDOException $e) {
                    $db->rollBack();
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if (!$id) {
                    return ['success' => false, 'error' => 'ID is required.'];
                }

                try {
                    // Get surgery details with patient and agency info
                    $stmt = $db->prepare("
                        SELECT s.*, p.name as patient_name, a.name as agency_name, r.name as room_name
                        FROM surgeries s
                        LEFT JOIN patients p ON s.patient_id = p.id
                        LEFT JOIN agencies a ON p.agency_id = a.id
                        LEFT JOIN rooms r ON s.room_id = r.id
                        WHERE s.id = ?
                    ");
                    $stmt->execute([$id]);
                    $surgery = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$surgery) {
                        return ['success' => false, 'error' => "Surgery not found with ID: {$id}"];
                    }

                    // Get assigned technicians
                    $tech_stmt = $db->prepare("
                        SELECT 
                            s.id, 
                            s.name, 
                            s.phone,
                            sd.speciality as speciality,
                            sd.experience_level as experience
                        FROM surgery_staff st
                        JOIN staff s ON st.staff_id = s.id
                        LEFT JOIN staff_details sd ON s.id = sd.staff_id
                        WHERE st.surgery_id = ?
                        ORDER BY s.name
                    ");
                    $tech_stmt->execute([$id]);
                    $technicians = $tech_stmt->fetchAll(PDO::FETCH_ASSOC);

                    $surgery['technicians'] = $technicians;
                    $surgery['technician_ids'] = array_column($technicians, 'id');

                    return ['success' => true, 'surgery' => $surgery];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'list':
            if ($method === 'POST') {
                $patient_id = $input['patient_id'] ?? null;
                if ($patient_id) {
                    $stmt = $db->prepare("SELECT s.*, p.name as patient_name, a.id as agency_id, r.name as room_name FROM surgeries s JOIN patients p ON s.patient_id = p.id LEFT JOIN agencies a ON p.agency_id = a.id LEFT JOIN rooms r ON s.room_id = r.id WHERE s.patient_id = ? ORDER BY s.date DESC");
                    $stmt->execute([$patient_id]);
                    return ['success' => true, 'surgeries' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                } else {
                    // Apply agency filtering
                    $sql = "SELECT s.*, p.name as patient_name, a.id as agency_id,r.name as room_name FROM surgeries s LEFT JOIN patients p ON s.patient_id = p.id LEFT JOIN agencies a ON p.agency_id = a.id LEFT JOIN rooms r ON s.room_id = r.id";
                    $params = [];

                    $agency_id = $input['agency'] ?? null;

                    // If agency parameter is provided, filter by it
                    if ($agency_id) {
                        $sql .= " WHERE p.agency_id = ?";
                        $params[] = $agency_id;
                    }
                    // If user is not admin and no agency parameter, restrict to their agency
                    elseif (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin' && isset($_SESSION['agency_id'])) {
                        $sql .= " WHERE p.agency_id = ?";
                        $params[] = $_SESSION['agency_id'];
                    }

                    $sql .= " ORDER BY s.date DESC";

                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    return ['success' => true, 'surgeries' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                }
            }
            break;
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}
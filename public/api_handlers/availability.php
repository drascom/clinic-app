<?php
function handle_availability($action, $method, $db, $input = [])
{
    switch ($action) {
        case 'byDate':
            if ($method === 'POST') {
                $date = $input['date'] ?? null;

                if (!$date) {
                    return ['success' => false, 'error' => 'Date is required.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    // Get all active rooms with their reservation status for the given date
                    $sql = "
                        SELECT
                            r.id,
                            r.name,
                            r.type,
                            r.is_active,
                            CASE
                                WHEN rr.id IS NOT NULL THEN 'booked'
                                WHEN r.is_active = 0 THEN 'inactive'
                                ELSE 'available'
                            END as status,
                            s.predicted_grafts_count,
                            s.current_grafts_count,
                            s.id as surgery_id,
                            rr.surgery_id as reservation_surgery_id,
                            p.name as patient_name
                        FROM rooms r
                        LEFT JOIN room_reservations rr ON r.id = rr.room_id AND rr.reserved_date = ?
                        LEFT JOIN surgeries s ON rr.surgery_id = s.id
                        LEFT JOIN patients p ON s.patient_id = p.id
                        ORDER BY r.name
                    ";

                    $stmt = $db->prepare($sql);
                    $stmt->execute([$date]);
                    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Calculate availability statistics
                    $total_rooms = count($rooms);
                    $active_rooms = array_filter($rooms, function ($room) {
                        return $room['is_active'] == 1;
                    });
                    $total_active = count($active_rooms);
                    $available_rooms = array_filter($active_rooms, function ($room) {
                        return $room['status'] === 'available';
                    });
                    $available_count = count($available_rooms);

                    return [
                        'success' => true,
                        'date' => $date,
                        'rooms' => $rooms,
                        'statistics' => [
                            'total_rooms' => $total_rooms,
                            'active_rooms' => $total_active,
                            'available_rooms' => $available_count,
                            'booked_rooms' => $total_active - $available_count
                        ]
                    ];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'range':
            if ($method === 'POST') {
                $start = $input['start'] ?? null;
                $end = $input['end'] ?? null;

                if (!$start || !$end) {
                    return ['success' => false, 'error' => 'Start and end dates are required.'];
                }

                // Validate date formats
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    // Get all active rooms with their reservations for the date range
                    $sql = "
                        SELECT
                            r.id as room_id,
                            r.name as room_name,
                            rr.reserved_date,
                            CASE
                                WHEN rr.id IS NOT NULL THEN 'booked'
                                WHEN r.is_active = 0 THEN 'inactive'
                                ELSE 'available'
                            END as status,
                            p.name as patient_name,
                             s.predicted_grafts_count,
                            s.current_grafts_count,
                            s.id as surgery_id
                        FROM rooms r
                        LEFT JOIN room_reservations rr ON r.id = rr.room_id
                            AND rr.reserved_date BETWEEN ? AND ?
                        LEFT JOIN surgeries s ON rr.surgery_id = s.id
                        LEFT JOIN patients p ON s.patient_id = p.id
                        ORDER BY r.name, rr.reserved_date
                    ";

                    $stmt = $db->prepare($sql);
                    $stmt->execute([$start, $end]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Group results by date
                    $availability = [];
                    foreach ($results as $row) {
                        $date = $row['reserved_date'] ?? $start; // Use start date for rooms without reservations
                        if (!isset($availability[$date])) {
                            $availability[$date] = [];
                        }
                        $availability[$date][] = $row;
                    }

                    return ['success' => true, 'start' => $start, 'end' => $end, 'availability' => $availability];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        default:
            return ['success' => false, 'error' => 'Invalid action for availability entity.'];
    }

    return ['success' => false, 'error' => 'Invalid request method for this action.'];
}

<?php
function handle_calendar_summary($action, $method, $db, $input = [])
{
    if ($method === 'POST') {
        // Check if this is a month request
        $year = $input['year'] ?? null;
        $month = $input['month'] ?? null;

        if ($year && $month) {
            // Return all appointment summaries for the entire month
            return getMonthSummary($db, $year, $month);
        }

        // Legacy single room/date request
        $room_id = $input['room_id'] ?? null;
        $date = $input['date'] ?? null;

        if (!$room_id || !$date) {
            return ['success' => false, 'error' => 'Room ID and date are required'];
        }

        try {
            // Get consultation count (based on notes)
            $stmt = $db->prepare("
                SELECT COUNT(*) as count FROM appointments
                WHERE room_id = ? AND appointment_date = ? AND notes LIKE '%consultation%'
            ");
            $stmt->execute([$room_id, $date]);
            $consult_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Get cosmetic count (appointments without consultation in notes)
            $stmt = $db->prepare("
                SELECT COUNT(*) as count FROM appointments
                WHERE room_id = ? AND appointment_date = ? AND (notes NOT LIKE '%consultation%' OR notes IS NULL OR notes = '')
            ");
            $stmt->execute([$room_id, $date]);
            $cosmetic_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Check for surgery
            $stmt = $db->prepare("
                SELECT s.id, p.name as patient_name
                FROM room_reservations rr
                JOIN surgeries s ON rr.surgery_id = s.id
                JOIN patients p ON s.patient_id = p.id
                WHERE rr.room_id = ? AND rr.reserved_date = ?
            ");
            $stmt->execute([$room_id, $date]);
            $surgery = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'consult_count' => (int)$consult_count,
                'cosmetic_count' => (int)$cosmetic_count,
                'surgery' => $surgery ? true : false,
                'surgery_label' => $surgery ? 'Hair Transplant' : null
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    return ['success' => false, 'error' => 'Invalid method for calendar_summary entity'];
}

function getMonthSummary($db, $year, $month) {
    try {
        // Calculate date range for the month
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate)); // Last day of month

        $result = [
            'success' => true,
            'appointments' => [],
            'surgeries' => []
        ];

        // Get all appointments for the month with room types to determine appointment type
        $stmt = $db->prepare("
            SELECT
                a.room_id,
                a.appointment_date,
                r.types as room_type,
                COUNT(*) as count
            FROM appointments a
            JOIN rooms r ON a.room_id = r.id
            WHERE a.appointment_date BETWEEN ? AND ?
            GROUP BY a.room_id, a.appointment_date, r.types
            ORDER BY a.room_id, a.appointment_date
        ");
        $stmt->execute([$startDate, $endDate]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organize appointments by room_id and date
        foreach ($appointments as $apt) {
            $key = $apt['room_id'] . '-' . $apt['appointment_date'];
            if (!isset($result['appointments'][$key])) {
                $result['appointments'][$key] = [
                    'room_id' => $apt['room_id'],
                    'date' => $apt['appointment_date'],
                    'consult_count' => 0,
                    'cosmetic_count' => 0
                ];
            }

            // Determine type based on room type
            if ($apt['room_type'] === 'consultation') {
                $result['appointments'][$key]['consult_count'] += (int)$apt['count'];
            } elseif ($apt['room_type'] === 'treatment') {
                $result['appointments'][$key]['cosmetic_count'] += (int)$apt['count'];
            }
            // Note: surgery rooms are handled separately in the surgeries section
        }

        // Get all surgeries for the month
        $stmt = $db->prepare("
            SELECT
                rr.room_id,
                rr.reserved_date as date,
                p.name as patient_name,
                s.status
            FROM room_reservations rr
            JOIN surgeries s ON rr.surgery_id = s.id
            JOIN patients p ON s.patient_id = p.id
            WHERE rr.reserved_date BETWEEN ? AND ?
            ORDER BY rr.room_id, rr.reserved_date
        ");
        $stmt->execute([$startDate, $endDate]);
        $surgeries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organize surgeries by room_id and date
        foreach ($surgeries as $surgery) {
            $key = $surgery['room_id'] . '-' . $surgery['date'];
            $result['surgeries'][$key] = [
                'room_id' => $surgery['room_id'],
                'date' => $surgery['date'],
                'patient_name' => $surgery['patient_name'],
                'status' => $surgery['status']
            ];
        }

        return $result;

    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}
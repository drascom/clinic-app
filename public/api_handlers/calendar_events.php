<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_calendar_events($action, $method, $db, $input = [])
{
    $logService = new LogService();

    if ($method !== 'POST' || $action !== 'get') {
        $logService->log('calendar_events', 'error', 'Invalid request method or action.', ['method' => $method, 'action' => $action]);
        return ['success' => false, 'error' => 'Invalid request: Only POST method with "get" action is allowed.'];
    }

    $year = $input['year'] ?? null;
    $month = $input['month'] ?? null;

    if (!$year || !$month) {
        return ['success' => false, 'error' => 'Year and month are required parameters.'];
    }

    try {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $events = [];

        // Initialize all days of the month in the events array
        $currentDate = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);
        while ($currentDate <= $endDateObj) {
            $dateString = $currentDate->format('Y-m-d');
            $events[$dateString] = [
                'appointments' => [],
                'surgeries' => []
            ];
            $currentDate->modify('+1 day');
        }

        // Get all appointments for the specified month
        $stmt_app = $db->prepare("
            SELECT
                a.id,
                a.appointment_date,
                a.start_time,
                a.end_time,
                a.notes,
                p.id as patient_id,
                p.name as patient_name,
                r.name as room_name,
                pr.name as procedure_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            LEFT JOIN rooms r ON a.room_id = r.id
            LEFT JOIN procedures pr ON a.procedure_id = pr.id
            WHERE a.appointment_date BETWEEN ? AND ?
        ");
        $stmt_app->execute([$startDate, $endDate]);
        $appointments = $stmt_app->fetchAll(PDO::FETCH_ASSOC);

        foreach ($appointments as $apt) {
            $date = $apt['appointment_date'];
            if (isset($events[$date])) {
                $events[$date]['appointments'][] = [
                    'id' => $apt['id'],
                    'patient_id' => $apt['patient_id'],
                    'patient_name' => $apt['patient_name'],
                    'start_time' => $apt['start_time'],
                    'end_time' => $apt['end_time'],
                    'room_name' => $apt['room_name'],
                    'procedure_name' => $apt['procedure_name'],
                    'notes' => $apt['notes'],
                    'summary' => trim($apt['start_time'] . ' ' . $apt['notes'])
                ];
            }
        }

        // Get all surgeries for the specified month
        $stmt_surg = $db->prepare("
            SELECT
                s.id,
                s.date as surgery_date,
                s.notes,
                s.status,
                s.predicted_grafts_count,
                s.current_grafts_count,
                s.is_recorded,
                p.id as patient_id,
                p.name as patient_name,
                r.name as room_name
            FROM surgeries s
            JOIN patients p ON s.patient_id = p.id
            LEFT JOIN rooms r ON s.room_id = r.id
            WHERE s.date BETWEEN ? AND ?
        ");
        $stmt_surg->execute([$startDate, $endDate]);
        $surgeries = $stmt_surg->fetchAll(PDO::FETCH_ASSOC);

        foreach ($surgeries as $surg) {
            $date = $surg['surgery_date'];
            if (isset($events[$date])) {
                $events[$date]['surgeries'][] = [
                    'id' => $surg['id'],
                    'patient_id' => $surg['patient_id'],
                    'patient_name' => $surg['patient_name'],
                    'notes' => $surg['notes'],
                    'status' => $surg['status'],
                    'predicted_grafts_count' => $surg['predicted_grafts_count'],
                    'current_grafts_count' => $surg['current_grafts_count'],
                    'is_recorded' => $surg['is_recorded'],
                    'room_name' => $surg['room_name'],
                    'summary' => 'Surgery' // Keep summary for existing display logic
                ];
            }
        }

        return ['success' => true, 'events' => $events];
    } catch (PDOException $e) {
        $logService->log('calendar_events', 'error', 'Database error in handle_calendar_events: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        return ['success' => false, 'error' => 'A database error occurred. Please try again later.'];
    }
}

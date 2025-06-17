<?php
function handle_calendar_details($action, $method, $db, $input = [])
{
    if ($method === 'POST') {
        $room_id = $input['room_id'] ?? null;
        $date = $input['date'] ?? null;
        
        if (!$room_id || !$date) {
            return ['success' => false, 'error' => 'Room ID and date are required'];
        }
        
        try {
            // Get room type to determine how to categorize appointments
            $stmt = $db->prepare("SELECT types FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            $room_type = $room['types'] ?? '';

            // Get all appointments for this room and date
            $stmt = $db->prepare("
                SELECT a.id, p.name as name, a.start_time, a.end_time, a.notes
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                WHERE a.room_id = ? AND a.appointment_date = ?
                ORDER BY a.start_time
            ");
            $stmt->execute([$room_id, $date]);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Categorize appointments based on room type
            $consults = [];
            $cosmetics = [];

            if ($room_type === 'consultation') {
                $consults = $appointments;
            } elseif ($room_type === 'treatment') {
                $cosmetics = $appointments;
            }
            
            // Get surgery details
            $stmt = $db->prepare("
                SELECT s.id, p.name as patient_name, s.graft_count, s.status
                FROM room_reservations rr
                JOIN surgeries s ON rr.surgery_id = s.id
                JOIN patients p ON s.patient_id = p.id
                WHERE rr.room_id = ? AND rr.reserved_date = ?
            ");
            $stmt->execute([$room_id, $date]);
            $surgery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $surgery_details = null;
            if ($surgery) {
                $surgery_details = [
                    'patient_name' => $surgery['patient_name'],
                    'procedure' => 'Hair Transplant',
                    'graft_count' => $surgery['graft_count'],
                    'status' => $surgery['status'],
                    'time' => '08:00-17:00' // Default time for surgeries
                ];
            }
            
            return [
                'success' => true,
                'consult' => $consults,
                'cosmetic' => $cosmetics,
                'surgery' => $surgery_details
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    return ['success' => false, 'error' => 'Only POST method is supported for calendar_details entity'];
}
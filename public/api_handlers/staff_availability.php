<?php
require_once __DIR__ . '/../auth/auth.php';

/**
 * Handle staff availability API requests
 *
 * Endpoints:
 * - byRange: Get availability data for a date range
 * - byRangeAll: Get all staff's availability (admin/editor only)
 * - toggleDay: Toggle staff's own availability (staff only)
 * - toggleDayAdmin: Toggle any staff's availability (admin/editor only)
 * - set/add: Add availability record
 * - edit: Edit availability record
 * - unset/delete: Remove availability record
 * - list: List availability records with filters
 * - getAvailability: Get availability for a specific month (legacy)
 */
function handle_staff_availability($action, $method, $db, $input = [])
{
    switch ($action) {
        // STAFF ENDPOINTS - for staff managing their own availability
        case 'byRange':
            if ($method === 'POST') {
                $start = $input['start'] ?? null;
                $end = $input['end'] ?? null;

                if (is_admin() || is_editor()) {
                    $staff_id = $input['id'] ?? null;
                } else {
                    $staff_id = get_user_id(); // Get current user's id as staff id staff ID
                }
                if (!$staff_id) {
                    return ['success' => false, 'error' => 'Staff not found or not logged in.'];
                }

                if (!$start || !$end) {
                    return ['success' => false, 'error' => 'Start and end dates are required.'];
                }

                // Validate date formats
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    $sql = "
                        SELECT
                            sa.id,
                            sa.id,
                            sa.available_on as date,
                            sa.period,
                            s.name as staff_name
                        FROM staff_availability sa
                        JOIN staff s ON sa.staff_id = s.id
                        WHERE sa.staff_id = ?
                        AND sa.available_on BETWEEN ? AND ?
                        AND s.is_active = 1
                        ORDER BY sa.available_on, s.name, sa.period
                    ";

                    $stmt = $db->prepare($sql);
                    $stmt->execute([$staff_id, $start, $end]);
                    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    return ['success' => true, 'start' => $start, 'end' => $end, 'availability' => $availability];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;
        case 'toggleDay':
            if ($method === 'POST') {
                $date = $input['date'] ?? null;
                if (is_admin() || is_editor()) {
                    $staff_id = $input['id'] ?? null;
                } else {
                    $staff_id = get_user_id(); // Get current user's id as staff id staff ID
                }
                if (!$date) {
                    return ['success' => false, 'error' => 'Date is required.'];
                }

                if (!$staff_id) {
                    return ['success' => false, 'error' => 'Staff not found or not logged in.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    // Check if 'full' day availability exists for this staff and date
                    $stmt = $db->prepare("SELECT id FROM staff_availability WHERE staff_id = ? AND available_on = ? AND period = 'full'");
                    $stmt->execute([$staff_id, $date]);
                    $existing_availability = $stmt->fetch(PDO::FETCH_ASSOC);

                    $is_available = false;

                    if ($existing_availability) {
                        // If 'full' day exists, delete it
                        $stmt = $db->prepare("DELETE FROM staff_availability WHERE id = ?");
                        $stmt->execute([$existing_availability['id']]);
                        $is_available = false; // Now not available
                    } else {
                        // If 'full' day doesn't exist, insert it
                        // First, remove any existing am/pm entries for this date to ensure 'full' is the only entry
                        $stmt = $db->prepare("DELETE FROM staff_availability WHERE staff_id = ? AND available_on = ? AND period IN ('am', 'pm')");
                        $stmt->execute([$staff_id, $date]);

                        $stmt = $db->prepare("INSERT INTO staff_availability (staff_id, available_on, period, created_at) VALUES (?, ?, 'full', datetime('now'))");
                        $stmt->execute([$staff_id, $date]);
                        $is_available = true; // Now available
                    }

                    return ['success' => true, 'isAvailable' => $is_available];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;
        // ADMIN/EDITOR ENDPOINTS - for managing all staff's availability
        case 'byRangeAll':
            if ($method === 'POST') {
                // Check if user has admin/editor permissions
                if (!is_admin() && !is_editor()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

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
                    $sql = "
                        SELECT
                            sa.staff_id,
                            sa.available_on as date,
                            sa.period,
                            s.name as staff_name
                        FROM staff_availability sa
                        JOIN staff s ON sa.staff_id = s.id
                        WHERE sa.available_on BETWEEN ? AND ?
                        AND s.is_active = 1
                        ORDER BY s.name, sa.available_on
                    ";

                    $stmt = $db->prepare($sql);
                    $stmt->execute([$start, $end]);
                    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    return ['success' => true, 'availability' => $availability];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'toggleDayAdmin':
            if ($method === 'POST') {
                // Check if user has admin/editor permissions
                if (!is_admin() && !is_editor()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

                $staff_id = $input['staff_id'] ?? null;
                $date = $input['date'] ?? null;

                if (!$staff_id || !$date) {
                    return ['success' => false, 'error' => 'Staff ID and date are required.'];
                }
                // return ['success' => true, 'message' => 'Staff ID: ' . $staff_id];
                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    // Check if any availability exists for this staff and date
                    $stmt = $db->prepare("SELECT id FROM staff_availability WHERE staff_id = ? AND available_on = ?");
                    $stmt->execute([$staff_id, $date]);
                    $existing_availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $is_available = false;

                    if (!empty($existing_availability)) {
                        // If any availability exists, remove all for this date
                        $stmt = $db->prepare("DELETE FROM staff_availability WHERE staff_id = ? AND available_on = ?");
                        $stmt->execute([$staff_id, $date]);
                        $is_available = false; // Now not available
                    } else {
                        // If no availability exists, add full day availability
                        $stmt = $db->prepare("INSERT INTO staff_availability (staff_id, available_on, period, updated_by, created_at) VALUES (?, ?, 'full', ?, datetime('now'))");
                        $stmt->execute([$staff_id, $date, get_user_id()]);
                        $is_available = true; // Now available
                    }

                    return ['success' => true, 'isAvailable' => $is_available];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'byDate':
            if ($method === 'POST') {
                $date = $input['date'] ?? null;
                $period = $input['period'] ?? null; // Optional period filter for surgery scheduling

                if (!$date) {
                    return ['success' => false, 'error' => 'Date is required.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                // Validate period if provided
                if ($period && !in_array($period, ['am', 'pm', 'full'])) {
                    return ['success' => false, 'error' => 'Period must be am, pm, or full.'];
                }

                try {
                    $sql = "
                        SELECT
                            s.id,
                            s.name,
                            s.phone,
                            s.staff_type,
                            s.is_active,
                            sa.period,
                            sd.speciality as speciality,
                            sd.experience_level as experience,
                            sa.id as availability_id
                        FROM staff s
                        JOIN staff_availability sa ON s.id = sa.staff_id
                        LEFT JOIN staff_details sd ON s.id = sd.staff_id
                        WHERE sa.available_on = ?
                        AND s.is_active = 1
                    ";

                    $params = [$date];

                    // Add period filtering for surgery scheduling
                    if ($period) {
                        $sql .= " AND (sa.period = ? OR sa.period = 'full')";
                        $params[] = $period;
                    }

                    $sql .= " ORDER BY s.name, sa.period";

                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    // Group staff members by ID to handle multiple periods
                    $grouped_staff = [];
                    foreach ($staff_members as $staff) {
                        $staff_id = $staff['id'];
                        if (!isset($grouped_staff[$staff_id])) {
                            $grouped_staff[$staff_id] = [
                                'id' => $staff['id'],
                                'name' => $staff['name'],
                                'specialty' => $staff['specialty'],
                                'periods' => []
                            ];
                        }
                        $grouped_staff[$staff_id]['periods'][] = $staff['period'];

                    }

                    // Convert back to indexed array and add period display
                    $result_staff = array_values($grouped_staff);
                    foreach ($result_staff as &$staff) {
                        $staff['period'] = implode(', ', $staff['periods']);
                        $staff['available_periods'] = $staff['periods'];
                    }

                    return [
                        'success' => true,
                        'date' => $date,
                        'period' => $period,
                        'technicians' => $result_staff,
                        'count' => count($result_staff)
                    ];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'set':
        case 'add':
            if ($method === 'POST') {
                $staff_id = $_POST['staff_id'] ?? $input['staff_id'] ?? null;
                $date = $_POST['available_on'] ?? $_POST['date'] ?? $input['available_on'] ?? $input['date'] ?? null;
                $period = $_POST['period'] ?? $input['period'] ?? null;

                if (!$staff_id || !$date || !$period) {
                    return ['success' => false, 'error' => 'Staff ID, date, and period are required.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                // Validate period
                if (!in_array($period, ['am', 'pm', 'full'])) {
                    return ['success' => false, 'error' => 'Period must be am, pm, or full.'];
                }

                try {
                    // Check if staff exists and is active
                    $stmt = $db->prepare("SELECT id, name, is_active FROM staff WHERE id = ?");
                    $stmt->execute([$staff_id]);
                    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$staff) {
                        return ['success' => false, 'error' => 'Staff not found.'];
                    }

                    if (!$staff['is_active']) {
                        http_response_code(400);
                        return ['success' => false, 'error' => 'Cannot mark an archived staff as available.'];
                    }

                    // Handle full day availability - remove existing am/pm entries for this date
                    if ($period === 'full') {
                        $stmt = $db->prepare("DELETE FROM staff_availability WHERE staff_id = ? AND available_on = ? AND period IN ('am', 'pm')");
                        $stmt->execute([$staff_id, $date]);
                    } else {
                        // Remove full day entry if setting specific period
                        $stmt = $db->prepare("DELETE FROM staff_availability WHERE staff_id = ? AND available_on = ? AND period = 'full'");
                        $stmt->execute([$staff_id, $date]);
                    }

                    // Insert new availability
                    $stmt = $db->prepare("INSERT INTO staff_availability (staff_id, available_on, period, created_at) VALUES (?, ?, ?, datetime('now'))");
                    $stmt->execute([$staff_id, $date, $period]);
                    $availability_id = $db->lastInsertId();

                    // Fetch the created availability with details
                    $stmt = $db->prepare("
                        SELECT
                            sa.id,
                            sa.staff_id,
                            sa.available_on as date,
                            sa.period,
                            s.name as staff_name,
                            s.specialty
                        FROM staff sa
                        JOIN staff s ON sa.staff_id = s.id
                        WHERE sa.id = ?
                    ");
                    $stmt->execute([$availability_id]);
                    $availability = $stmt->fetch(PDO::FETCH_ASSOC);

                    return ['success' => true, 'message' => 'Availability set successfully.', 'availability' => $availability];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        http_response_code(409);
                        return ['success' => false, 'error' => 'Staff is already available for this period.'];
                    }
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'edit':
            if ($method === 'POST') {
                $id = $_POST['id'] ?? $input['id'] ?? null;
                $staff_id = $_POST['staff_id'] ?? $input['staff_id'] ?? null;
                $date = $_POST['available_on'] ?? $_POST['date'] ?? $input['available_on'] ?? $input['date'] ?? null;
                $period = $_POST['period'] ?? $input['period'] ?? null;

                if (!$id || !$staff_id || !$date || !$period) {
                    return ['success' => false, 'error' => 'ID, staff ID, date, and period are required.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                // Validate period
                if (!in_array($period, ['am', 'pm', 'full'])) {
                    return ['success' => false, 'error' => 'Period must be am, pm, or full.'];
                }

                try {
                    // Check if availability exists
                    $check_stmt = $db->prepare("SELECT id FROM staff_availability WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'Availability record not found.'];
                    }

                    // Update the availability
                    $stmt = $db->prepare("UPDATE staff_availability SET staff_id = ?, available_on = ?, period = ? WHERE id = ?");
                    $stmt->execute([$staff_id, $date, $period, $id]);

                    return ['success' => true, 'message' => 'Availability updated successfully.'];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation
                        return ['success' => false, 'error' => 'Staff is already available for this period.'];
                    }
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'unset':
        case 'delete':
            if ($method === 'POST' || $method === 'DELETE') {
                $id = $_POST['id'] ?? $input['id'] ?? $_GET['id'] ?? null;

                if (!$id) {
                    return ['success' => false, 'error' => 'Availability ID is required.'];
                }

                try {
                    // Check if availability exists
                    $stmt = $db->prepare("
                        SELECT
                            sa.id,
                            s.name as staff_name,
                            sa.available_on as date,
                            sa.period
                        FROM staff sa
                        JOIN staff s ON sa.staff_id = s.id
                        WHERE sa.id = ?
                    ");
                    $stmt->execute([$id]);
                    $availability = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$availability) {
                        return ['success' => false, 'error' => 'Availability record not found.'];
                    }

                    // Delete the availability
                    $stmt = $db->prepare("DELETE FROM staff_availability WHERE id = ?");
                    $stmt->execute([$id]);

                    return ['success' => true, 'message' => 'Availability removed successfully.', 'removed_availability' => $availability];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'list':
            if ($method === 'POST') {
                $staff_id = $input['staff_id'] ?? null;
                $date = $input['date'] ?? null;

                $sql = "
                    SELECT
                        sa.id,
                        sa.staff_id,
                        sa.available_on as date,
                        sa.period,
                        s.name as staff_name,
                        s.specialty
                    FROM staff sa
                    JOIN staff s ON sa.staff_id = s.id
                    WHERE s.is_active = 1
                ";

                $params = [];

                if ($staff_id) {
                    $sql .= " AND sa.staff_id = ?";
                    $params[] = $staff_id;
                }

                if ($date) {
                    $sql .= " AND sa.available_on = ?";
                    $params[] = $date;
                }

                $sql .= " ORDER BY sa.available_on DESC, s.name, sa.period";

                try {
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    return ['success' => true, 'availability' => $availability];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'getAvailability':
            if ($method === 'GET') {
                $month = $input['month'] ?? null;
                $staff_id = get_user_id(); // Get current user's staff ID

                if (!$month) {
                    return ['success' => false, 'error' => 'Month is required.'];
                }

                if (!$staff_id) {
                    return ['success' => false, 'error' => 'Staff not found or not logged in.'];
                }

                // Validate month format YYYY-MM
                if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                    return ['success' => false, 'error' => 'Invalid month format. Use YYYY-MM.'];
                }

                try {
                    // Calculate start and end dates for the month
                    $start_date = $month . '-01';
                    $end_date = date('Y-m-t', strtotime($start_date));

                    $sql = "
                        SELECT
                            available_on as date
                        FROM staff_availability
                        WHERE staff_id = ?
                        AND available_on BETWEEN ? AND ?
                    ";

                    $stmt = $db->prepare($sql);
                    $stmt->execute([$staff_id, $start_date, $end_date]);
                    $availability_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Format for app.js: { 'YYYY-MM-DD': true/false, ... }
                    $available_days = [];
                    foreach ($availability_records as $record) {
                        // Since app.js only cares if *any* availability exists for the day,
                        // we just mark the day as true if there's at least one entry.
                        // If we needed to handle am/pm/full separately, this logic would change.
                        $available_days[$record['date']] = true;
                    }

                    return ['success' => true, 'availability' => $available_days];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;



        default:
            return ['success' => false, 'error' => 'Invalid action for staffAvail entity.'];
    }

    return ['success' => false, 'error' => 'Invalid request method for this action.'];
}
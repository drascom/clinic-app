<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../services/LogService.php';

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
    $logService = new LogService();
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
                            sa.status,
                            s.name as staff_name
                        FROM staff_availability sa
                        JOIN staff s ON sa.staff_id = s.id
                        WHERE sa.staff_id = ?
                        AND sa.available_on BETWEEN ? AND ?
                        AND s.is_active = 1
                        ORDER BY sa.available_on, s.name, sa.status
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
                    $stmt = $db->prepare("SELECT id, status FROM staff_availability WHERE staff_id = ? AND available_on = ?");
                    $stmt->execute([$staff_id, $date]);
                    $existing_availability = $stmt->fetch(PDO::FETCH_ASSOC);

                    $new_status = 'unselected';

                    if (!$existing_availability) {
                        // From unselected to full_day
                        $stmt = $db->prepare("INSERT INTO staff_availability (staff_id, available_on, status, updated_by) VALUES (?, ?, 'full_day', ?)");
                        $stmt->execute([$staff_id, $date, get_user_id()]);
                        $new_status = 'full_day';
                    } elseif ($existing_availability['status'] === 'full_day') {
                        // From full_day to half_day
                        $stmt = $db->prepare("UPDATE staff_availability SET status = 'half_day', updated_by = ? WHERE id = ?");
                        $stmt->execute([get_user_id(), $existing_availability['id']]);
                        $new_status = 'half_day';
                    } elseif ($existing_availability['status'] === 'half_day') {
                        // From half_day to unavailable
                        $stmt = $db->prepare("UPDATE staff_availability SET status = 'unavailable', updated_by = ? WHERE id = ?");
                        $stmt->execute([get_user_id(), $existing_availability['id']]);
                        $new_status = 'unavailable';
                    } else { // status is 'unavailable'
                        // From unavailable to unselected (delete)
                        $stmt = $db->prepare("DELETE FROM staff_availability WHERE id = ?");
                        $stmt->execute([$existing_availability['id']]);
                        $new_status = 'unselected';
                    }

                    return ['success' => true, 'newStatus' => $new_status];
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
                            sa.status,
                            s.name as staff_name
                        FROM staff_availability sa
                        JOIN staff s ON sa.staff_id = s.id
                        WHERE sa.available_on BETWEEN ? AND ?
                        AND s.is_active = 1
                        ORDER BY s.name, sa.available_on, sa.status
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

                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                try {
                    $stmt = $db->prepare("SELECT id, status FROM staff_availability WHERE staff_id = ? AND available_on = ?");
                    $stmt->execute([$staff_id, $date]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                    $new_status = 'unselected';

                    if (!$existing) {
                        // 1. From unselected to full_day
                        $stmt = $db->prepare("INSERT INTO staff_availability (staff_id, available_on, status, updated_by,created_by) VALUES (?, ?, 'full_day', ?,?)");
                        $stmt->execute([$staff_id, $date, get_user_id(), get_user_id()]);
                        $new_status = 'full_day';
                    } elseif ($existing['status'] === 'full_day') {
                        // 2. From full_day to half_day
                        $stmt = $db->prepare("UPDATE staff_availability SET status = 'half_day', updated_by = ?  WHERE id = ?");
                        $stmt->execute([get_user_id(), $existing['id']]);
                        $new_status = 'half_day';
                    } elseif ($existing['status'] === 'half_day') {
                        // 3. From half_day to unavailable
                        $stmt = $db->prepare("UPDATE staff_availability SET status = 'unavailable', updated_by = ?  WHERE id = ?");
                        $stmt->execute([get_user_id(), $existing['id']]);
                        $new_status = 'unavailable';
                    } else {
                        // 4. From unavailable to unselected (delete)
                        $stmt = $db->prepare("DELETE FROM staff_availability WHERE id = ?");
                        $stmt->execute([$existing['id']]);
                        $new_status = 'unselected';
                    }

                    return ['success' => true, 'newStatus' => $new_status];
                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'byDate':
            if ($method === 'POST') {
                $date = $input['date'] ?? null;
                $status_filter = $input['status'] ?? 'full_day'; // Default to full_day

                if (!$date) {
                    return ['success' => false, 'error' => 'Date is required.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                // Validate status filter
                if (!in_array($status_filter, ['unavailable', 'half_day', 'full_day', 'all'])) {
                    return ['success' => false, 'error' => 'Status filter must be unavailable, half_day, full_day, or all.'];
                }

                try {
                    $sql = "
                        SELECT
                            s.id,
                            s.name,
                            s.phone,
                            s.staff_type,
                            s.is_active,
                            sa.status,
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

                    if ($status_filter !== 'all') {
                        $sql .= " AND sa.status = ?";
                        $params[] = $status_filter;
                    }

                    $sql .= " ORDER BY s.name, sa.status";

                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // For byDate, we now return staff with their specific status for that day
                    // No grouping by periods needed as 'period' column is removed.
                    $result_staff = [];
                    foreach ($staff_members as $staff) {
                        $result_staff[] = [
                            'id' => $staff['id'],
                            'name' => $staff['name'],
                            'specialty' => $staff['specialty'],
                            'status' => $staff['status'] // Now directly use status
                        ];
                    }

                    return [
                        'success' => true,
                        'date' => $date,
                        'status_filter' => $status_filter,
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
                $status = $_POST['status'] ?? $input['status'] ?? 'full_day'; // Default to 'full_day'

                if (!$staff_id || !$date) {
                    return ['success' => false, 'error' => 'Staff ID and date are required.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                // Validate status
                if (!in_array($status, ['unavailable', 'half_day', 'full_day'])) {
                    return ['success' => false, 'error' => 'Status must be unavailable, half_day, or full_day.'];
                }

                try {
                    // Check if availability record already exists for this staff and date
                    $stmt = $db->prepare("SELECT id FROM staff_availability WHERE staff_id = ? AND available_on = ?");
                    $stmt->execute([$staff_id, $date]);
                    $existing_availability = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing_availability) {
                        // Update existing availability
                        $stmt = $db->prepare("UPDATE staff_availability SET status = ?, updated_by = ? WHERE id = ?");
                        $stmt->execute([$status, get_user_id(), $existing_availability['id']]);
                        $availability_id = $existing_availability['id'];
                    } else {
                        // Insert new availability
                        $stmt = $db->prepare("INSERT INTO staff_availability (staff_id, available_on, status, updated_by) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$staff_id, $date, $status, get_user_id()]);
                        $availability_id = $db->lastInsertId();
                    }

                    // Fetch the created/updated availability with details
                    $stmt = $db->prepare("
                        SELECT
                            sa.id,
                            sa.staff_id,
                            sa.available_on as date,
                            sa.status,
                            s.name as staff_name,
                            s.specialty
                        FROM staff_availability sa
                        JOIN staff s ON sa.staff_id = s.id
                        WHERE sa.id = ?
                    ");
                    $stmt->execute([$availability_id]);
                    $availability = $stmt->fetch(PDO::FETCH_ASSOC);

                    return ['success' => true, 'message' => 'Availability set successfully.', 'availability' => $availability];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation (should be less common now with status update)
                        http_response_code(409);
                        return ['success' => false, 'error' => 'A record for this staff and date already exists.'];
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
                $status = $_POST['status'] ?? $input['status'] ?? null; // Status is now required for edit

                if (!$id || !$staff_id || !$date || !$status) {
                    return ['success' => false, 'error' => 'ID, staff ID, date, and status are required.'];
                }

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
                }

                // Validate status
                if (!in_array($status, ['unavailable', 'half_day', 'full_day'])) {
                    return ['success' => false, 'error' => 'Status must be unavailable, half_day, or full_day.'];
                }

                try {
                    // Check if availability exists
                    $check_stmt = $db->prepare("SELECT id FROM staff_availability WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'Availability record not found.'];
                    }

                    // Update the availability
                    $stmt = $db->prepare("UPDATE staff_availability SET staff_id = ?, available_on = ?, status = ?, updated_by = ? WHERE id = ?");
                    $stmt->execute([$staff_id, $date, $status, get_user_id(), $id]);

                    return ['success' => true, 'message' => 'Availability updated successfully.'];
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // UNIQUE constraint violation (if staff_id, available_on, status combination is unique)
                        return ['success' => false, 'error' => 'A record for this staff, date, and status already exists.'];
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
                            sa.status
                        FROM staff_availability sa
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
                        sa.status,
                        s.name as staff_name,
                        s.specialty
                    FROM staff_availability sa
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

                $sql .= " ORDER BY sa.available_on DESC, s.name, sa.status";

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
                            available_on as date,
                            status
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
                        // Mark the day as true only if the status is 'available'
                        if (in_array($record['status'], ['full_day', 'half_day'])) {
                            $available_days[$record['date']] = true;
                        }
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

<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../services/LogService.php';


function handle_dashboard($action, $method, $db, $input = [])
{
    $logService = new LogService();
    $logService->log('dashboard', 'info', "Dashboard API Request: Action=$action, Method=$method", ['input' => $input]);

    switch ($action) {
        case 'get_all_data':
            if ($method === 'POST') {
                if (is_staff()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }
                try {
                    $data = [
                        'today_overview' => get_today_overview_data($db),
                        'overall_stats' => get_overall_stats_data($db),
                        'staff_availability' => get_staff_availability_data($db),
                        'pending_tasks' => get_pending_tasks_data($db, get_user_id()),
                        'appointments' => get_recent_appointments_data($db),
                        'recent_surgeries' => get_recent_surgeries_data($db),
                        'recent_leads' => get_recent_leads_data($db),
                        'recent_activity' => get_recent_activity_data($db),
                    ];
                    return ['success' => true, 'data' => $data];
                } catch (PDOException $e) {
                    error_log("Dashboard API Error (get_all_data): " . $e->getMessage());
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        default:
            error_log("Dashboard API: Invalid action for dashboard entity: $action");
            return ['success' => false, 'error' => 'Invalid action for dashboard entity.'];
    }

    error_log("Dashboard API: Invalid request method for action: $action");
    return ['success' => false, 'error' => 'Invalid request method for this action.'];
}

/**
 * Get today's overview data (appointments and surgeries).
 */
function get_today_overview_data($db)
{
    $today = date('Y-m-d');
    $agencyFilter = '';
    $agencyParams = [];
    if (is_agent() && get_user_agency_id()) {
        $agencyFilter = ' AND p.agency_id = ?';
        $agencyParams = [get_user_agency_id()];
    }

    // Count appointments for today
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        WHERE DATE(a.appointment_date) = ?" . $agencyFilter . "
    ");
    $stmt->execute(array_merge([$today], $agencyParams));
    $appointmentsToday = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count surgeries for today
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM surgeries s
        LEFT JOIN patients p ON s.patient_id = p.id
        WHERE DATE(s.date) = ?" . $agencyFilter . "
    ");
    $stmt->execute(array_merge([$today], $agencyParams));
    $surgeriesToday = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    return [
        'appointments_today' => (int) $appointmentsToday,
        'surgeries_today' => (int) $surgeriesToday
    ];
}

/**
 * Get overall statistics data.
 */
function get_overall_stats_data($db)
{
    $agencyFilter = '';
    $agencyParams = [];
    if (is_agent() && get_user_agency_id()) {
        $agencyFilter = ' WHERE p.agency_id = ?';
        $agencyParams = [get_user_agency_id()];
    }

    // Total patients
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM patients p" . $agencyFilter);
    $stmt->execute($agencyParams);
    $totalPatients = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total procedures (treatments)
    $procedure_query = "SELECT COUNT(a.id) as count FROM appointments a";
    $procedure_params = [];
    $procedure_where = ["a.appointment_type = 'treatment'"];

    if (is_agent() && get_user_agency_id()) {
        // We need to join with patients to filter by agency
        $procedure_query .= " LEFT JOIN patients p ON a.patient_id = p.id";
        $procedure_where[] = "p.agency_id = ?";
        $procedure_params[] = get_user_agency_id();
    }

    $procedure_query .= " WHERE " . implode(' AND ', $procedure_where);

    $stmt = $db->prepare($procedure_query);
    $stmt->execute($procedure_params);
    $totalProcedures = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total surgeries
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM surgeries s LEFT JOIN patients p ON s.patient_id = p.id" . $agencyFilter);
    $stmt->execute($agencyParams);
    $totalSurgeries = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    return [
        'total_patients' => (int) $totalPatients,
        'total_procedures' => (int) $totalProcedures,
        'total_surgeries' => (int) $totalSurgeries
    ];
}

/**
 * Get staff availability data.
 */
function get_staff_availability_data($db)
{
    // Total active staff members
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM staff WHERE is_active = 1");
    $stmt->execute();
    $totalStaff = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Number of staff members available for the current month
    $currentMonth = date('Y-m');
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT staff_id) as count
        FROM staff_availability
        WHERE strftime('%Y-%m', available_on) = ?
    ");
    $stmt->execute([$currentMonth]);
    $availableStaff = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    return [
        'total_staff' => (int) $totalStaff,
        'available_staff_this_month' => (int) $availableStaff
    ];
}

/**
 * Get pending tasks data (unread messages and emails).
 */
function get_pending_tasks_data($db, $userId)
{
    // Count unread messages for the current user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadMessages = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count read messages for the current user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 1");
    $stmt->execute([$userId]);
    $readMessages = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count read messages for the current user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 1");
    $stmt->execute([$userId]);
    $readMessages = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count unread emails for the current user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM emails WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadEmails = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count read emails for the current user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM emails WHERE user_id = ? AND is_read = 1");
    $stmt->execute([$userId]);
    $readEmails = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    return [
        'unread_messages' => (int) $unreadMessages,
        'read_messages' => (int) $readMessages,
        'unread_emails' => (int) $unreadEmails,
        'read_emails' => (int) $readEmails
    ];
}

/**
 * Retrieve the last 10 records from relevant database tables where updated_at is today.
 */
function get_recent_activity_data($db)
{
    $today = date('Y-m-d');
    $agencyFilter = '';
    $agencyParams = [];
    if (is_agent() && get_user_agency_id()) {
        $agencyFilter = ' AND p.agency_id = ?';
        $agencyParams = [get_user_agency_id()];
    }

    $queries = [];

    // Patients
    $queries[] = "
        SELECT 'Patient' as type, p.name as description,
        CASE WHEN p.updated_at > p.created_at THEN u_updated.username ELSE u_created.username END as updated_by,
        p.updated_at as activity_timestamp,
        CASE WHEN p.updated_at = p.created_at THEN 'Created' ELSE 'Updated' END as process_type
        FROM patients p
        LEFT JOIN users u_updated ON p.updated_by = u_updated.id
        LEFT JOIN users u_created ON p.created_by = u_created.id
        WHERE DATE(p.updated_at) = ? " . $agencyFilter;

    // Appointments
    $queries[] = "
        SELECT 'Appointment' as type, p.name || ' appointment' as description,
        CASE WHEN a.updated_at > a.created_at THEN u_updated.username ELSE u_created.username END as updated_by,
        a.updated_at as activity_timestamp,
        CASE WHEN a.updated_at = a.created_at THEN 'Created' ELSE 'Updated' END as process_type
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN users u_updated ON a.updated_by = u_updated.id
        LEFT JOIN users u_created ON a.created_by = u_created.id
        WHERE DATE(a.updated_at) = ? " . $agencyFilter;

    // Surgeries
    $queries[] = "
        SELECT 'Surgery' as type, p.name || ' surgery' as description,
        CASE WHEN s.updated_at > s.created_at THEN u_updated.username ELSE u_created.username END as updated_by,
        s.updated_at as activity_timestamp,
        CASE WHEN s.updated_at = s.created_at THEN 'Created' ELSE 'Updated' END as process_type
        FROM surgeries s
        LEFT JOIN patients p ON s.patient_id = p.id
        LEFT JOIN users u_updated ON s.updated_by = u_updated.id
        LEFT JOIN users u_created ON s.created_by = u_created.id
        WHERE DATE(s.updated_at) = ? " . $agencyFilter;

    // Staff and Staff Details combined
    $queries[] = "
        SELECT 'Staff' as type, s.name as description,
        CASE WHEN s.updated_at > s.created_at THEN u_updated.username ELSE u_created.username END as updated_by,
        s.updated_at as activity_timestamp,
        CASE WHEN s.updated_at = s.created_at THEN 'Created' ELSE 'Updated' END as process_type
        FROM staff s
        LEFT JOIN users u_updated ON s.updated_by = u_updated.id
        LEFT JOIN users u_created ON s.created_by = u_created.id
        WHERE DATE(s.updated_at) = ?

        UNION ALL

        SELECT 'Staff Detail' as type, s.name || ' details' as description,
        CASE WHEN sd.updated_at > sd.created_at THEN u_updated.username ELSE u_created.username END as updated_by,
        sd.updated_at as activity_timestamp,
        CASE WHEN sd.updated_at = sd.created_at THEN 'Created' ELSE 'Updated' END as process_type
        FROM staff_details sd
        LEFT JOIN staff s ON sd.staff_id = s.id
        LEFT JOIN users u_updated ON sd.updated_by = u_updated.id
        LEFT JOIN users u_created ON sd.created_by = u_created.id
        WHERE DATE(sd.updated_at) = ?";

    // Rooms (assuming rooms table has updated_at and updated_by)
    $queries[] = "
        SELECT 'Room' as type, r.name as description,
        CASE WHEN r.updated_at > r.created_at THEN u_updated.username ELSE u_created.username END as updated_by,
        r.updated_at as activity_timestamp,
        CASE WHEN r.updated_at = r.created_at THEN 'Created' ELSE 'Updated' END as process_type
        FROM rooms r
        LEFT JOIN users u_updated ON r.updated_by = u_updated.id
        LEFT JOIN users u_created ON r.created_by = u_created.id
        WHERE DATE(r.updated_at) = ?";

    $unionQuery = implode(" UNION ALL ", $queries);
    $finalQuery = $unionQuery . " ORDER BY activity_timestamp DESC LIMIT 10";

    $params = [];
    foreach ($queries as $index => $query) {
        $params[] = $today; // Add parameter for the first DATE(?) in each query

        // For the combined Staff/Staff Details query (which is at index 3 in the $queries array),
        // it has two '?' placeholders for dates.
        if ($index === 3) { // Assuming the combined query is the 4th element (index 3)
            $params[] = $today; // Add parameter for the second DATE(?)
        }

        // Add agency_id filter if applicable. This filter is only for Patient, Appointment, and Surgery queries.
        // The combined Staff/Staff Details query does not have an agency_id filter.
        if (is_agent() && get_user_agency_id() && (strpos($query, 'p.agency_id') !== false || strpos($query, 'a.agency_id') !== false || strpos($query, 's.agency_id') !== false)) {
            $params[] = get_user_agency_id();
        }
    }

    $stmt = $db->prepare($finalQuery);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Retrieve appointments for the next 7 days.
 */
function get_recent_appointments_data($db)
{
    $today = date('Y-m-d');
    $sevenDaysLater = date('Y-m-d', strtotime('+15 days'));

    $agencyFilter = '';
    $agencyParams = [$today, $sevenDaysLater];
    if (is_agent() && get_user_agency_id()) {
        $agencyFilter = ' AND p.agency_id = ?';
        $agencyParams[] = get_user_agency_id();
    }

    $stmt = $db->prepare("
        SELECT
            a.id,
            a.appointment_date AS date,
            a.start_time AS time,
            p.name AS patient_name,
            a.appointment_type,
            a.procedure_id,
            pr.name AS procedure_name
        FROM
            appointments a
        JOIN
            patients p ON a.patient_id = p.id
        LEFT JOIN
            procedures pr ON a.procedure_id = pr.id
        WHERE
            a.appointment_date BETWEEN ? AND ?
        " . $agencyFilter . "
        ORDER BY
            a.appointment_date ASC
        LIMIT
            10
    ");
    $stmt->execute($agencyParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve the last 10 recent surgeries.
 */
function get_recent_surgeries_data($db)
{
    $today = date('Y-m-d');
    $sevenDaysLater = date('Y-m-d', strtotime('+15 days'));

    $agencyFilter = '';
    $agencyParams = [$today, $sevenDaysLater];
    if (is_agent() && get_user_agency_id()) {
        $agencyFilter = ' AND p.agency_id = ?';
        $agencyParams[] = get_user_agency_id();
    }

    $stmt = $db->prepare("
        SELECT s.id, s.date, p.name as patient_name, s.status, s.forms
        FROM surgeries s
        JOIN patients p ON s.patient_id = p.id
        WHERE s.date BETWEEN ? AND ?
        " . $agencyFilter . "
        ORDER BY s.date ASC
        LIMIT 10
    ");
    $stmt->execute($agencyParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Retrieve the last 5 recent leads.
 */
function get_recent_leads_data($db)
{
    $stmt = $db->prepare("
        SELECT id, name, email, phone, status
        FROM leeds
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

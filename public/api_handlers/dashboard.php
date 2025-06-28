<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../services/LogService.php';

/**
 * Handle dashboard API requests
 * 
 * Endpoints:
 * - stats: Get current month statistics
 * - yearlyChart: Get yearly surgery data for charts
 * - techAvailability: Get technician availability analysis
 */
function handle_dashboard($action, $method, $db, $input = [])
{
    $logService = new LogService();
    $logService->log('dashboard', 'info', "Dashboard API Request: Action=$action, Method=$method", ['input' => $input]);

    switch ($action) {
        case 'stats':
            if ($method === 'POST') {
                // Check if user has appropriate permissions
                if (is_staff()) {
                    error_log("Dashboard API: Insufficient permissions for technician.");
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

                $month = $input['month'] ?? date('Y-m');

                // Validate month format YYYY-MM
                if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                    error_log("Dashboard API: Invalid month format: $month");
                    return ['success' => false, 'error' => 'Invalid month format. Use YYYY-MM.'];
                }

                try {
                    $startDate = $month . '-01';
                    $endDate = date('Y-m-t', strtotime($startDate));

                    // Get current month statistics
                    $stats = [];

                    // Check if user is an agent and filter by agency
                    $agencyFilter = '';
                    $agencyParams = [];
                    if (is_agent() && get_user_agency_id()) {
                        $agencyFilter = ' AND p.agency_id = ?';
                        $agencyParams = [get_user_agency_id()];
                    }

                    // 1. Total patients this month (new patients)
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as count
                        FROM patients p
                        WHERE DATE(p.created_at) BETWEEN ? AND ?" . $agencyFilter . "
                    ");
                    $stmt->execute(array_merge([$startDate, $endDate], $agencyParams));
                    $stats['total_patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    // 2. Patients with surgeries this month
                    $stmt = $db->prepare("
                        SELECT COUNT(DISTINCT p.id) as count
                        FROM patients p
                        JOIN surgeries s ON p.id = s.patient_id
                        WHERE DATE(s.date) BETWEEN ? AND ?" . $agencyFilter . "
                    ");
                    $stmt->execute(array_merge([$startDate, $endDate], $agencyParams));
                    $stats['patients_with_surgeries'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    // 3. Patients with appointments this month
                    $stmt = $db->prepare("
                        SELECT COUNT(DISTINCT p.id) as count
                        FROM patients p
                        JOIN appointments a ON p.id = a.patient_id
                        WHERE DATE(a.appointment_date) BETWEEN ? AND ?" . $agencyFilter . "
                    ");
                    $stmt->execute(array_merge([$startDate, $endDate], $agencyParams));
                    $stats['patients_with_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    // 4. Total surgeries this month
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as count
                        FROM surgeries s
                        LEFT JOIN patients p ON s.patient_id = p.id
                        WHERE DATE(s.date) BETWEEN ? AND ?" . $agencyFilter . "
                    ");
                    $stmt->execute(array_merge([$startDate, $endDate], $agencyParams));
                    $stats['total_surgeries'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    // 5. Total grafts this month
                    $stmt = $db->prepare("
                        SELECT COALESCE(SUM(s.current_grafts_count), 0) as count
                        FROM surgeries s
                        LEFT JOIN patients p ON s.patient_id = p.id
                        WHERE DATE(s.date) BETWEEN ? AND ? AND s.current_grafts_count IS NOT NULL" . $agencyFilter . "
                    ");
                    $stmt->execute(array_merge([$startDate, $endDate], $agencyParams));
                    $stats['total_grafts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    // 6. Total appointments this month
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as count
                        FROM appointments a
                        LEFT JOIN patients p ON a.patient_id = p.id
                        WHERE DATE(a.appointment_date) BETWEEN ? AND ?" . $agencyFilter . "
                    ");
                    $stmt->execute(array_merge([$startDate, $endDate], $agencyParams));
                    $stats['total_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    // 7. Surgery status breakdown (reserved vs confirmed)
                    $stmt = $db->prepare("
                        SELECT
                            status,
                            COUNT(*) as count
                        FROM surgeries s
                        LEFT JOIN patients p ON s.patient_id = p.id
                        WHERE DATE(s.date) BETWEEN ? AND ?
                        AND status IN ('scheduled', 'confirmed')" . $agencyFilter . "
                        GROUP BY status
                    ");
                    $stmt->execute(array_merge([$startDate, $endDate], $agencyParams));
                    $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Initialize status counts
                    $stats['reserved_surgeries'] = 0;
                    $stats['confirmed_surgeries'] = 0;

                    // Populate status counts
                    foreach ($statusData as $row) {
                        if ($row['status'] === 'reserved') {
                            $stats['reserved_surgeries'] = (int) $row['count'];
                        } elseif ($row['status'] === 'confirmed') {
                            $stats['confirmed_surgeries'] = (int) $row['count'];
                        }
                    }

                    // Calculate total and percentages
                    $totalStatusSurgeries = $stats['reserved_surgeries'] + $stats['confirmed_surgeries'];
                    $stats['total_status_surgeries'] = $totalStatusSurgeries;
                    $stats['reserved_percentage'] = $totalStatusSurgeries > 0 ? round(($stats['reserved_surgeries'] / $totalStatusSurgeries) * 100, 1) : 0;
                    $stats['confirmed_percentage'] = $totalStatusSurgeries > 0 ? round(($stats['confirmed_surgeries'] / $totalStatusSurgeries) * 100, 1) : 0;

                    error_log("Dashboard API: Stats data returned: " . json_encode($stats));
                    return ['success' => true, 'stats' => $stats, 'month' => $month];
                } catch (PDOException $e) {
                    error_log("Dashboard API Error (stats): " . $e->getMessage());
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'today_overview':
            if ($method === 'POST') {
                if (is_staff()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

                try {
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
                        'success' => true,
                        'data' => [
                            'appointments_today' => (int) $appointmentsToday,
                            'surgeries_today' => (int) $surgeriesToday
                        ]
                    ];
                } catch (PDOException $e) {
                    error_log("Dashboard API Error (today_overview): " . $e->getMessage());
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'yearlyChart':
            if ($method === 'POST') {
                // Check if user has appropriate permissions
                if (is_staff()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

                $year = $input['year'] ?? date('Y');

                // Validate year format
                if (!preg_match('/^\d{4}$/', $year)) {
                    error_log("Dashboard API: Invalid year format: $year");
                    return ['success' => false, 'error' => 'Invalid year format. Use YYYY.'];
                }

                try {
                    // Check if user is an agent and filter by agency
                    $agencyFilter = '';
                    $agencyParams = [$year];
                    if (is_agent() && get_user_agency_id()) {
                        $agencyFilter = ' AND p.agency_id = ?';
                        $agencyParams[] = get_user_agency_id();
                    }

                    // Get monthly surgery counts for the year
                    $stmt = $db->prepare("
                        SELECT
                            strftime('%m', s.date) as month,
                            COUNT(*) as surgery_count,
                            COALESCE(SUM(s.current_grafts_count), 0) as graft_count
                        FROM surgeries s
                        LEFT JOIN patients p ON s.patient_id = p.id
                        WHERE strftime('%Y', s.date) = ?" . $agencyFilter . "
                        GROUP BY strftime('%m', s.date)
                        ORDER BY month
                    ");
                    $stmt->execute($agencyParams);
                    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Fill in missing months with zero counts
                    $chartData = [];
                    for ($i = 1; $i <= 12; $i++) {
                        $monthStr = sprintf('%02d', $i);
                        $found = false;
                        foreach ($monthlyData as $data) {
                            if ($data['month'] === $monthStr) {
                                $chartData[] = [
                                    'month' => $monthStr,
                                    'month_name' => date('F', mktime(0, 0, 0, $i, 1)),
                                    'surgery_count' => (int) $data['surgery_count'],
                                    'graft_count' => (int) $data['graft_count']
                                ];
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $chartData[] = [
                                'month' => $monthStr,
                                'month_name' => date('F', mktime(0, 0, 0, $i, 1)),
                                'surgery_count' => 0,
                                'graft_count' => 0
                            ];
                        }
                    }

                    error_log("Dashboard API: Yearly chart data returned: " . json_encode($chartData));
                    return ['success' => true, 'chartData' => $chartData, 'year' => $year];
                } catch (PDOException $e) {
                    error_log("Dashboard API Error (yearlyChart): " . $e->getMessage());
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'techAvailability':
            if ($method === 'POST') {
                // Check if user has appropriate permissions
                if (is_staff()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

                $month = $input['month'] ?? date('Y-m');

                // Validate month format YYYY-MM
                if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                    error_log("Dashboard API: Invalid month format: $month");
                    return ['success' => false, 'error' => 'Invalid month format. Use YYYY-MM.'];
                }

                try {
                    $startDate = $month . '-01';
                    $endDate = date('Y-m-t', strtotime($startDate));

                    // Check if user is an agent and filter by agency
                    $agencyFilter = '';
                    $agencyParams = [$startDate, $endDate];
                    if (is_agent() && get_user_agency_id()) {
                        $agencyFilter = ' AND p.agency_id = ?';
                        $agencyParams[] = get_user_agency_id();
                    }

                    // Get surgeries requiring technicians this month
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as surgery_count
                        FROM surgeries s
                        LEFT JOIN patients p ON s.patient_id = p.id
                        WHERE DATE(s.date) BETWEEN ? AND ?" . $agencyFilter . "
                    ");
                    $stmt->execute($agencyParams);
                    $surgeryCount = $stmt->fetch(PDO::FETCH_ASSOC)['surgery_count'];

                    // Each surgery needs minimum 2 technicians
                    $requiredTechDays = $surgeryCount * 2;

                    // Get total available technician days this month
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as available_days
                        FROM staff_availability ta
                        JOIN staff t ON ta.staff_id = t.id
                        WHERE ta.available_on BETWEEN ? AND ?
                        AND t.is_active = 1
                    ");
                    $stmt->execute([$startDate, $endDate]);
                    $availableDays = $stmt->fetch(PDO::FETCH_ASSOC)['available_days'];

                    // Calculate deficit or surplus
                    $difference = $availableDays - $requiredTechDays;

                    $responseData = [
                        'success' => true,
                        'data' => [
                            'surgery_count' => (int) $surgeryCount,
                            'required_tech_days' => (int) $requiredTechDays,
                            'available_tech_days' => (int) $availableDays,
                            'difference' => (int) $difference,
                            'status' => $difference >= 0 ? 'surplus' : 'deficit'
                        ],
                        'month' => $month
                    ];
                    error_log("Dashboard API: Tech availability data returned: " . json_encode($responseData['data']));
                    return $responseData;
                } catch (PDOException $e) {
                    error_log("Dashboard API Error (techAvailability): " . $e->getMessage());
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;



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
                        'week_schedule' => get_recent_appointments_data($db),
                        'recent_activity' => get_recent_activity_data($db),
                        'recent_surgeries' => get_recent_surgeries_data($db),
                        'recent_leads' => get_recent_leads_data($db),
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
 * Get today's schedule details (appointments and surgeries).
 */
function get_recent_appointments_data($db)
{
    $today = date('Y-m-d');
    $sevenDaysLater = date('Y-m-d', strtotime('+7 days'));
    $agencyFilter = '';
    $agencyParams = [];
    if (is_agent() && get_user_agency_id()) {
        $agencyFilter = ' AND p.agency_id = ?';
        $agencyParams = [get_user_agency_id()];
    }

    // Upcoming 10 appointments for the next 7 days
    $appointmentsStmt = $db->prepare("
        SELECT p.name as patient_name, a.appointment_date as date, a.start_time as time, 'Appointment' as type
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.appointment_date BETWEEN ? AND ? " . $agencyFilter . "
        ORDER BY a.appointment_date ASC, a.start_time
        LIMIT 10
    ");
    $appointmentsStmt->execute(array_merge([$today, $sevenDaysLater], $agencyParams));
    $appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'appointments' => $appointments,
        'surgeries' => []
    ];
}

/**
 * Retrieve the last 10 records from relevant database tables where updated_at is today.
 */
function get_recent_activity_data($db)
{
    $today = date('Y-m-d');
    $sevenDaysLater = date('Y-m-d', strtotime('+7 days'));
    $agencyFilter = '';
    if (is_agent() && get_user_agency_id()) {
        $agencyFilter = ' AND p.agency_id = ?';
    }

    $queries = [];

    // Upcoming Appointments
    $queries[] = "
        SELECT 'Appointment' as type, p.name || ' on ' || a.appointment_date as description,
        u.username as updated_by,
        a.appointment_date || ' ' || a.start_time as activity_timestamp,
        'Upcoming' as process_type
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.appointment_date BETWEEN ? AND ? " . $agencyFilter;

    // Upcoming Surgeries
    $queries[] = "
        SELECT 'Surgery' as type, p.name || ' on ' || s.date as description,
        u.username as updated_by,
        s.date as activity_timestamp,
        'Upcoming' as process_type
        FROM surgeries s
        JOIN patients p ON s.patient_id = p.id
        LEFT JOIN users u ON s.created_by = u.id
        WHERE s.date BETWEEN ? AND ? " . $agencyFilter;

    $unionQuery = implode(" UNION ALL ", $queries);
    $finalQuery = $unionQuery . " ORDER BY activity_timestamp ASC LIMIT 10";

    $params = [];
    // Params for Appointments query
    $params[] = $today;
    $params[] = $sevenDaysLater;
    if (is_agent() && get_user_agency_id()) {
        $params[] = get_user_agency_id();
    }
    // Params for Surgeries query
    $params[] = $today;
    $params[] = $sevenDaysLater;
    if (is_agent() && get_user_agency_id()) {
        $params[] = get_user_agency_id();
    }

    $stmt = $db->prepare($finalQuery);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve the last 10 recent surgeries.
 */
function get_recent_surgeries_data($db)
{
    $agencyFilter = '';
    $agencyParams = [];
    if (is_agent() && get_user_agency_id()) {
        $agencyFilter = ' WHERE p.agency_id = ?';
        $agencyParams = [get_user_agency_id()];
    }

    $stmt = $db->prepare("
        SELECT s.id, s.date, p.name as patient_name, s.status, s.forms
        FROM surgeries s
        JOIN patients p ON s.patient_id = p.id
        " . $agencyFilter . "
        ORDER BY s.date DESC
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
        LIMIT 5
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

<?php
require_once __DIR__ . '/../auth/auth.php';

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
    switch ($action) {
        case 'stats':
            if ($method === 'POST') {
                // Check if user has appropriate permissions
                if (is_technician()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

                $month = $input['month'] ?? date('Y-m');

                // Validate month format YYYY-MM
                if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
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
                        SELECT COALESCE(SUM(s.graft_count), 0) as count
                        FROM surgeries s
                        LEFT JOIN patients p ON s.patient_id = p.id
                        WHERE DATE(s.date) BETWEEN ? AND ? AND s.graft_count IS NOT NULL" . $agencyFilter . "
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
                        AND status IN ('reserved', 'confirmed')" . $agencyFilter . "
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

                    return ['success' => true, 'stats' => $stats, 'month' => $month];

                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'yearlyChart':
            if ($method === 'POST') {
                // Check if user has appropriate permissions
                if (is_technician()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

                $year = $input['year'] ?? date('Y');

                // Validate year format
                if (!preg_match('/^\d{4}$/', $year)) {
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
                            COALESCE(SUM(s.graft_count), 0) as graft_count
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

                    return ['success' => true, 'chartData' => $chartData, 'year' => $year];

                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        case 'techAvailability':
            if ($method === 'POST') {
                // Check if user has appropriate permissions
                if (is_technician()) {
                    return ['success' => false, 'error' => 'Insufficient permissions.'];
                }

                $month = $input['month'] ?? date('Y-m');

                // Validate month format YYYY-MM
                if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
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
                        FROM technician_availability ta
                        JOIN technicians t ON ta.tech_id = t.id
                        WHERE ta.available_on BETWEEN ? AND ?
                        AND t.is_active = 1
                    ");
                    $stmt->execute([$startDate, $endDate]);
                    $availableDays = $stmt->fetch(PDO::FETCH_ASSOC)['available_days'];

                    // Calculate deficit or surplus
                    $difference = $availableDays - $requiredTechDays;

                    return [
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

                } catch (PDOException $e) {
                    return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
                }
            }
            break;

        default:
            return ['success' => false, 'error' => 'Invalid action for dashboard entity.'];
    }

    return ['success' => false, 'error' => 'Invalid request method for this action.'];
}

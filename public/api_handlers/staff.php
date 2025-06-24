<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../services/LogService.php';

/**
 * Handle staff API requests
 * 
 * Endpoints:
 * - list: Get list of staff with search/filter
 * - get: Get single staff member details (including staff_details if applicable)
 * - add: Create new staff member (and staff_details if type is candidate)
 * - update: Update staff member information (and staff_details if type is candidate)
 * - delete: Delete staff member (admin only)
 * - add_note: Add note to staff member
 * - get_notes: Get staff notes
 * - delete_note: Delete note (admin only)
 */
function handle_staff($action, $method, $db, $input = [])
{
    // Check authentication and admin/editor access
    if (!is_logged_in()) {
        return ['success' => false, 'error' => 'Authentication required.'];
    }

    if (!is_admin() && !is_editor()) {
        return ['success' => false, 'error' => 'Admin or Editor access required.'];
    }

    switch ($action) {
        case 'list':
            if ($method === 'POST') {
                return get_staff_list($db, $input);
            }
            break;

        case 'get':
            if ($method === 'POST') {
                return get_staff_details($db, $input);
            }
            break;

        case 'add':
            if ($method === 'POST') {
                return add_staff($db, $input);
            }
            break;

        case 'update':
            if ($method === 'POST') {
                return update_staff($db, $input);
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                return delete_staff($db, $input);
            }
            break;

        case 'add_note':
            if ($method === 'POST') {
                return add_staff_note($db, $input);
            }
            break;

        case 'get_notes':
            if ($method === 'POST') {
                return get_staff_notes($db, $input);
            }
            break;

        case 'delete_note':
            if ($method === 'POST') {
                return delete_staff_note($db, $input);
            }
            break;
    }

    return ['success' => false, 'error' => 'Invalid action or method.'];
}

/**
 * Get staff list with search and filtering
 */
function get_staff_list($db, $input)
{
    try {
        $page = max(1, intval($input['page'] ?? 1));
        $limit = max(1, min(100, intval($input['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $search = trim($input['search'] ?? '');
        $staff_type_filter = trim($input['staff_type'] ?? '');
        $is_active_filter = isset($input['is_active']) ? intval($input['is_active']) : null;

        // Build WHERE clause
        $where_conditions = [];
        $params = [];

        if (!empty($search)) {
            $where_conditions[] = "(s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR s.location LIKE ? OR s.position_applied LIKE ? OR sd.speciality LIKE ? OR s.staff_type LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }

        if (!empty($staff_type_filter)) {
            $where_conditions[] = "s.staff_type = ?";
            $params[] = $staff_type_filter;
        }

        if ($is_active_filter !== null) {
            $where_conditions[] = "s.is_active = ?";
            $params[] = $is_active_filter;
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        // Get total count
        $count_sql = "
            SELECT COUNT(DISTINCT s.id)
            FROM staff s
            LEFT JOIN staff_details sd ON s.id = sd.staff_id
            {$where_clause}
        ";

        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();

        // Get staff members
        $sql = "
            SELECT 
                s.id, s.name, s.phone, s.email, s.location, s.position_applied, s.staff_type, s.is_active,
                sd.speciality, sd.experience_level, sd.current_company, sd.linkedin_profile, sd.source,
                sd.salary_expectation, sd.willing_to_relocate, sd.daily_fee
            FROM staff s
            LEFT JOIN staff_details sd ON s.id = sd.staff_id
            {$where_clause}
            ORDER BY s.name ASC
            LIMIT ? OFFSET ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for candidates (with search filter)
        $candidate_count_sql = "
            SELECT COUNT(DISTINCT s.id)
            FROM staff s
            LEFT JOIN staff_details sd ON s.id = sd.staff_id
            WHERE s.staff_type = 'candidate'
            " . (!empty($search) ? " AND (" . implode(' AND ', array_filter($where_conditions, function ($cond) {
            return strpos($cond, 's.name LIKE') !== false || strpos($cond, 's.email LIKE') !== false || strpos($cond, 's.phone LIKE') !== false || strpos($cond, 's.location LIKE') !== false || strpos($cond, 's.position_applied LIKE') !== false || strpos($cond, 'sd.speciality LIKE') !== false || strpos($cond, 's.staff_type LIKE') !== false;
        })) . ")" : "");
        $candidate_count_params = [];
        if (!empty($search)) {
            $candidate_count_params = array_merge($candidate_count_params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        $candidate_count_stmt = $db->prepare($candidate_count_sql);
        $candidate_count_stmt->execute($candidate_count_params);
        $candidate_total = $candidate_count_stmt->fetchColumn();

        // Get total count for staff (with search filter)
        $staff_count_sql = "
            SELECT COUNT(DISTINCT s.id)
            FROM staff s
            LEFT JOIN staff_details sd ON s.id = sd.staff_id
            WHERE s.staff_type = 'staff'
            " . (!empty($search) ? " AND (" . implode(' AND ', array_filter($where_conditions, function ($cond) {
            return strpos($cond, 's.name LIKE') !== false || strpos($cond, 's.email LIKE') !== false || strpos($cond, 's.phone LIKE') !== false || strpos($cond, 's.location LIKE') !== false || strpos($cond, 's.position_applied LIKE') !== false || strpos($cond, 'sd.speciality LIKE') !== false || strpos($cond, 's.staff_type LIKE') !== false;
        })) . ")" : "");
        $staff_count_params = [];
        if (!empty($search)) {
            $staff_count_params = array_merge($staff_count_params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        $staff_count_stmt = $db->prepare($staff_count_sql);
        $staff_count_stmt->execute($staff_count_params);
        $staff_total = $staff_count_stmt->fetchColumn();

        // Calculate pagination for the currently filtered list
        $total_pages = ceil($total / $limit);
        return [
            'success' => true,
            'staff' => $staff_members,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total' => $total, // Total for the currently filtered list
                'candidate_total' => $candidate_total, // Total candidates matching search
                'staff_total' => $staff_total, // Total staff matching search
                'limit' => $limit,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ];
    } catch (Exception $e) {
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Get staff list failed", ['error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to retrieve staff list.'];
    }
}

/**
 * Get single staff member details
 */
function get_staff_details($db, $input)
{
    $id = intval($input['id'] ?? 0);

    if (!$id) {
        return ['success' => false, 'error' => 'Staff ID is required.'];
    }

    try {
        $stmt = $db->prepare("
            SELECT
                s.id, s.name, s.phone, s.email, s.location, s.position_applied, s.staff_type, s.is_active,
                sd.speciality, sd.experience_level, sd.current_company, sd.linkedin_profile, sd.source,
                sd.salary_expectation, sd.willing_to_relocate, sd.daily_fee
            FROM staff s
            LEFT JOIN staff_details sd ON s.id = sd.staff_id
            WHERE s.id = ?
        ");

        $stmt->execute([$id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            return ['success' => false, 'error' => 'Staff member not found.'];
        }

        // The JOIN already fetches details if they exist, no need for separate query


        return ['success' => true, 'staff' => $staff];
    } catch (Exception $e) {
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Get staff details failed", ['id' => $input['id'] ?? 0, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to retrieve staff details.'];
    }
}

/**
 * Add new staff member
 */
function add_staff($db, $input)
{
    // Validate required fields for staff table
    $required_fields = ['name', 'email', 'location', 'staff_type'];
    foreach ($required_fields as $field) {
        if (empty(trim($input[$field] ?? ''))) {
            return ['success' => false, 'error' => "Field '{$field}' is required."];
        }
    }

    // Get current user ID for updated_by field
    $created_by = $input['authenticated_user_id'] ?? get_user_id();
    if (!$created_by) {
        return ['success' => false, 'error' => 'User not authenticated.'];
    }

    // Validate email format
    $email = trim($input['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format.'];
    }

    // Check for duplicate email
    try {
        $check_stmt = $db->prepare("SELECT id FROM staff WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            return ['success' => false, 'error' => 'A staff member with this email already exists.'];
        }
    } catch (Exception $e) {
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Duplicate email check failed", ['email' => $email, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to check for duplicate email.'];
    }

    try {
        $db->beginTransaction();

        // Insert into staff table
        $stmt = $db->prepare("
            INSERT INTO staff (name, phone, email, location, position_applied, staff_type, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            trim($input['name']),
            trim($input['phone'] ?? ''),
            $email,
            trim($input['location']),
            trim($input['position_applied'] ?? ''),
            trim($input['staff_type']),
            intval($input['is_active'] ?? 1),
            $created_by
        ]);

        if (!$result) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Failed to add staff member.'];
        }

        $staff_id = $db->lastInsertId();

        // If staff type is 'candidate', insert into staff_details table
        if (trim($input['staff_type']) === 'candidate') {
            $details_stmt = $db->prepare("
                INSERT INTO staff_details (
                    staff_id, speciality, experience_level, current_company,
                    linkedin_profile, source, salary_expectation, willing_to_relocate, daily_fee, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $details_result = $details_stmt->execute([
                $staff_id,
                trim($input['speciality'] ?? ''),
                trim($input['experience_level'] ?? ''),
                trim($input['current_company'] ?? ''),
                trim($input['linkedin_profile'] ?? ''),
                trim($input['source'] ?? ''),
                trim($input['salary_expectation'] ?? ''),
                intval($input['willing_to_relocate'] ?? 0),
                intval($input['daily_fee'] ?? 0),
                $created_by
            ]);

            if (!$details_result) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Failed to add staff details.'];
            }
        }

        $db->commit();
        return [
            'success' => true,
            'message' => 'Staff member added successfully.',
            'staff_id' => $staff_id
        ];
    } catch (Exception $e) {
        $db->rollBack();
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Add staff failed", ['input' => $input, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to add staff member: ' . $e->getMessage()];
    }
}

/**
 * Update staff member information
 */
function update_staff($db, $input)
{
    $id = intval($input['id'] ?? 0);

    if (!$id) {
        return ['success' => false, 'error' => 'Staff ID is required.'];
    }

    // Validate required fields for staff table
    $required_fields = ['name', 'email', 'location', 'staff_type'];
    foreach ($required_fields as $field) {
        if (empty(trim($input[$field] ?? ''))) {
            return ['success' => false, 'error' => "Field '{$field}' is required."];
        }
    }

    // Get current user ID for updated_by field
    $updated_by = $input['authenticated_user_id'] ?? get_user_id();
    if (!$updated_by) {
        return ['success' => false, 'error' => 'User not authenticated.'];
    }

    // Validate email format
    $email = trim($input['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format.'];
    }

    // Check for duplicate email (excluding current staff member)
    try {
        $check_stmt = $db->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $id]);
        if ($check_stmt->fetch()) {
            return ['success' => false, 'error' => 'A staff member with this email already exists.'];
        }
    } catch (Exception $e) {
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Duplicate email check failed on update", ['email' => $email, 'id' => $id, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to check for duplicate email.'];
    }

    try {
        $db->beginTransaction();

        // Update staff table
        $stmt = $db->prepare("
            UPDATE staff SET
                name = ?, phone = ?, email = ?, location = ?, position_applied = ?, staff_type = ?, is_active = ?, updated_by = ?
            WHERE id = ?
        ");

        $result = $stmt->execute([
            trim($input['name']),
            trim($input['phone'] ?? ''),
            $email,
            trim($input['location']),
            trim($input['position_applied'] ?? ''),
            trim($input['staff_type']),
            intval($input['is_active'] ?? 1),
            $updated_by,
            $id
        ]);

        // If staff type is 'candidate', update or insert into staff_details table
        $staff_type_lower = strtolower(trim($input['staff_type']));
        if ($staff_type_lower === 'candidate') {
            // Check if details already exist
            $check_details_stmt = $db->prepare("SELECT id FROM staff_details WHERE staff_id = ?");
            $check_details_stmt->execute([$id]);
            $details_exist = $check_details_stmt->fetch();

            if ($details_exist) {
                // Update existing details
                $details_stmt = $db->prepare("
                    UPDATE staff_details SET
                        speciality = ?, experience_level = ?, current_company = ?,
                        linkedin_profile = ?, source = ?, salary_expectation = ?, willing_to_relocate = ?, daily_fee = ?, updated_by = ?
                    WHERE staff_id = ?
                ");
                $update_params = [
                    trim($input['speciality'] ?? ''),
                    trim($input['experience_level'] ?? ''),
                    trim($input['current_company'] ?? ''),
                    trim($input['linkedin_profile'] ?? ''),
                    trim($input['source'] ?? ''),
                    trim($input['salary_expectation'] ?? ''),
                    intval($input['willing_to_relocate'] ?? 0),
                    intval($input['daily_fee'] ?? 0),
                    $updated_by,
                    $id
                ];
                $details_result = $details_stmt->execute($update_params);
            } else {
                // Insert new details
                $details_stmt = $db->prepare("
                    INSERT INTO staff_details (
                        staff_id, speciality, experience_level, current_company,
                        linkedin_profile, source, salary_expectation, willing_to_relocate, daily_fee, updated_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert_params = [
                    $id,
                    trim($input['speciality'] ?? ''),
                    trim($input['experience_level'] ?? ''),
                    trim($input['current_company'] ?? ''),
                    trim($input['linkedin_profile'] ?? ''),
                    trim($input['source'] ?? ''),
                    trim($input['salary_expectation'] ?? ''),
                    intval($input['willing_to_relocate'] ?? 0),
                    intval($input['daily_fee'] ?? 0),
                    $updated_by
                ];
                $details_result = $details_stmt->execute($insert_params);
            }

            if (!$details_result) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Failed to update staff details.'];
            }
        } else {
            // If staff type is not 'candidate', delete any existing staff_details
            $delete_details_stmt = $db->prepare("DELETE FROM staff_details WHERE staff_id = ?");
            $delete_details_stmt->execute([$id]);
        }

        $db->commit();
        return ['success' => true, 'message' => 'Staff member updated successfully.'];
    } catch (Exception $e) {
        $db->rollBack();
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Update staff failed", ['id' => $id, 'input' => $input, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to update staff member: ' . $e->getMessage()];
    }
}

/**
 * Delete staff member (admin only)
 */
function delete_staff($db, $input)
{
    if (!is_admin()) {
        return ['success' => false, 'error' => 'Admin access required to delete staff members.'];
    }

    $id = intval($input['id'] ?? 0);

    if (!$id) {
        return ['success' => false, 'error' => 'Staff ID is required.'];
    }

    try {
        // Get staff name for confirmation message
        $name_stmt = $db->prepare("SELECT name FROM staff WHERE id = ?");
        $name_stmt->execute([$id]);
        $staff = $name_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            return ['success' => false, 'error' => 'Staff member not found.'];
        }

        // Delete staff member (staff_details and staff_notes will be deleted automatically due to CASCADE)
        $stmt = $db->prepare("DELETE FROM staff WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => "Staff member '{$staff['name']}' deleted successfully."
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to delete staff member.'];
        }
    } catch (Exception $e) {
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Delete staff failed", ['id' => $id, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to delete staff member: ' . $e->getMessage()];
    }
}

/**
 * Add note to staff member
 */
function add_staff_note($db, $input)
{
    $staff_id = intval($input['staff_id'] ?? 0);
    $note_text = trim($input['note_text'] ?? '');

    if (!$staff_id) {
        return ['success' => false, 'error' => 'Staff ID is required.'];
    }

    if (empty($note_text)) {
        return ['success' => false, 'error' => 'Note text is required.'];
    }

    try {
        // Verify staff member exists
        $check_stmt = $db->prepare("SELECT id FROM staff WHERE id = ?");
        $check_stmt->execute([$staff_id]);
        if (!$check_stmt->fetch()) {
            return ['success' => false, 'error' => 'Staff member not found.'];
        }
        $created_by = $input['authenticated_user_id'] ?? get_user_id();

        $stmt = $db->prepare("
            INSERT INTO staff_notes (staff_id, note_text, note_type, is_important, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $staff_id,
            $note_text,
            trim($input['note_type'] ?? 'General'),
            intval($input['is_important'] ?? 0),
            $created_by
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Note added successfully.',
                'note_id' => $db->lastInsertId()
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to add note.'];
        }
    } catch (Exception $e) {
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Add staff note failed", ['staff_id' => $staff_id, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to add note: ' . $e->getMessage()];
    }
}

/**
 * Get staff notes
 */
function get_staff_notes($db, $input)
{
    $staff_id = intval($input['staff_id'] ?? 0);

    if (!$staff_id) {
        return ['success' => false, 'error' => 'Staff ID is required.'];
    }

    try {
        $stmt = $db->prepare("
            SELECT
                n.*
            FROM staff_notes n
            WHERE n.staff_id = ?
            ORDER BY n.created_at DESC
        ");

        $stmt->execute([$staff_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'notes' => $notes];
    } catch (Exception $e) {
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Get staff notes failed", ['staff_id' => $staff_id, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to retrieve notes.'];
    }
}

/**
 * Delete staff note (admin only)
 */
function delete_staff_note($db, $input)
{
    if (!is_admin()) {
        return ['success' => false, 'error' => 'Admin access required to delete notes.'];
    }

    $note_id = intval($input['note_id'] ?? 0);

    if (!$note_id) {
        return ['success' => false, 'error' => 'Note ID is required.'];
    }

    try {
        $stmt = $db->prepare("DELETE FROM staff_notes WHERE id = ?");
        $result = $stmt->execute([$note_id]);

        if ($result && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Note deleted successfully.'];
        } else {
            return ['success' => false, 'error' => 'Note not found or already deleted.'];
        }
    } catch (Exception $e) {
        $logService = new LogService();
        $logService->log('staff_api', 'error', "Delete staff note failed", ['note_id' => $note_id, 'error' => $e->getMessage()]);
        return ['success' => false, 'error' => 'Failed to delete note: ' . $e->getMessage()];
    }
}

<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../services/LogService.php';

/**
 * Handle job candidates API requests
 * 
 * Endpoints:
 * - list: Get list of candidates with search/filter
 * - get: Get single candidate details
 * - add: Create new candidate
 * - edit: Update candidate information
 * - delete: Delete candidate (admin only)
 * - add_note: Add note to candidate
 * - get_notes: Get candidate notes
 * - delete_note: Delete note (admin only)
 * - stats: Get candidate statistics
 */
function handle_candidates($action, $method, $db, $input = [])
{
    $logService = new LogService();
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
                return get_candidates_list($db, $input);
            }
            break;

        case 'get':
            if ($method === 'POST') {
                return get_candidate_details($db, $input);
            }
            break;

        case 'add':
            if ($method === 'POST') {
                return add_candidate($db, $input);
            }
            break;

        case 'update':
            if ($method === 'POST') {
                return edit_candidate($db, $input);
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                return delete_candidate($db, $input);
            }
            break;

        case 'add_note':
            if ($method === 'POST') {
                return add_candidate_note($db, $input);
            }
            break;

        case 'get_notes':
            if ($method === 'POST') {
                return get_candidate_notes($db, $input);
            }
            break;

        case 'delete_note':
            if ($method === 'POST') {
                return delete_candidate_note($db, $input);
            }
            break;

        case 'stats':
            if ($method === 'POST') {
                return get_candidate_stats($db);
            }
            break;
    }

    return ['success' => false, 'error' => 'Invalid action or method.'];
}

/**
 * Get candidates list with search and filtering
 */
function get_candidates_list($db, $input)
{
    try {
        $page = max(1, intval($input['page'] ?? 1));
        $limit = max(1, min(100, intval($input['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $search = trim($input['search'] ?? '');
        $status_filter = trim($input['status'] ?? '');
        $position_filter = trim($input['position'] ?? '');

        // Build WHERE clause
        $where_conditions = [];
        $params = [];

        if (!empty($search)) {
            $where_conditions[] = "(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.current_company LIKE ? OR c.position_applied LIKE ? OR c.status LIKE ? OR c.location LIKE ? OR c.source LIKE ? OR EXISTS (SELECT 1 FROM candidate_notes cn WHERE cn.candidate_id = c.id AND cn.note_text LIKE ?))";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }

        if (!empty($status_filter)) {
            $where_conditions[] = "c.status = ?";
            $params[] = $status_filter;
        }

        if (!empty($position_filter)) {
            $where_conditions[] = "c.position_applied LIKE ?";
            $params[] = "%{$position_filter}%";
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        // Get total count
        $count_sql = "
            SELECT COUNT(*) 
            FROM job_candidates c 
            {$where_clause}
        ";

        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();

        // Get candidates with creator info
        $sql = "
            SELECT 
                c.*,
                u.name as created_by_name,
                (SELECT COUNT(*) FROM candidate_notes WHERE candidate_id = c.id) as notes_count
            FROM job_candidates c
            LEFT JOIN users u ON c.created_by = u.id
            {$where_clause}
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate pagination
        $total_pages = ceil($total / $limit);

        return [
            'success' => true,
            'candidates' => $candidates,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total' => $total,
                'limit' => $limit,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ];
    } catch (Exception $e) {
        error_log("Get candidates list failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to retrieve candidates list.'];
    }
}

/**
 * Get single candidate details
 */
function get_candidate_details($db, $input)
{
    $id = intval($input['id'] ?? 0);

    if (!$id) {
        return ['success' => false, 'error' => 'Candidate ID is required.'];
    }

    try {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                u.name as created_by_name
            FROM job_candidates c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");

        $stmt->execute([$id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$candidate) {
            return ['success' => false, 'error' => 'Candidate not found.'];
        }

        return ['success' => true, 'candidate' => $candidate];
    } catch (Exception $e) {
        error_log("Get candidate details failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to retrieve candidate details.'];
    }
}

/**
 * Add new candidate
 */
function add_candidate($db, $input)
{
    // Validate required fields
    $required_fields = ['name', 'email', 'position_applied'];
    foreach ($required_fields as $field) {
        if (empty(trim($input[$field] ?? ''))) {
            return ['success' => false, 'error' => "Field '{$field}' is required."];
        }
    }

    // Validate email format
    $email = trim($input['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format.'];
    }

    // Check for duplicate email
    try {
        $check_stmt = $db->prepare("SELECT id FROM job_candidates WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            return ['success' => false, 'error' => 'A candidate with this email already exists.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to check for duplicate email.'];
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO job_candidates (
                name, email, phone, position_applied, experience_level, current_company,
                linkedin_profile, application_date, status, source, salary_expectation,
                availability_date, location, willing_to_relocate, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            trim($input['name']),
            $email,
            trim($input['phone'] ?? ''),
            trim($input['position_applied']),
            trim($input['experience_level'] ?? ''),
            trim($input['current_company'] ?? ''),
            trim($input['linkedin_profile'] ?? ''),
            $input['application_date'] ?? date('Y-m-d'),
            trim($input['status'] ?? 'Applied'),
            trim($input['source'] ?? ''),
            trim($input['salary_expectation'] ?? ''),
            $input['availability_date'] ?? null,
            trim($input['location'] ?? ''),
            intval($input['willing_to_relocate'] ?? 0),
            get_user_id()
        ]);

        if ($result) {
            $candidate_id = $db->lastInsertId();
            return [
                'success' => true,
                'message' => 'Candidate added successfully.',
                'candidate_id' => $candidate_id
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to add candidate.'];
        }
    } catch (Exception $e) {
        error_log("Add candidate failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to add candidate: ' . $e->getMessage()];
    }
}

/**
 * Edit candidate information
 */
function edit_candidate($db, $input)
{
    $id = intval($input['id'] ?? 0);

    if (!$id) {
        return ['success' => false, 'error' => 'Candidate ID is required.'];
    }

    // Validate required fields
    $required_fields = ['name', 'email', 'position_applied'];
    foreach ($required_fields as $field) {
        if (empty(trim($input[$field] ?? ''))) {
            return ['success' => false, 'error' => "Field '{$field}' is required."];
        }
    }

    // Validate email format
    $email = trim($input['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format.'];
    }

    // Check for duplicate email (excluding current candidate)
    try {
        $check_stmt = $db->prepare("SELECT id FROM job_candidates WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $id]);
        if ($check_stmt->fetch()) {
            return ['success' => false, 'error' => 'A candidate with this email already exists.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to check for duplicate email.'];
    }

    try {
        $stmt = $db->prepare("
            UPDATE job_candidates SET
                name = ?, email = ?, phone = ?, position_applied = ?, experience_level = ?,
                current_company = ?, linkedin_profile = ?, status = ?, source = ?,
                salary_expectation = ?, availability_date = ?, location = ?,
                willing_to_relocate = ?, updated_at = datetime('now')
            WHERE id = ?
        ");

        $result = $stmt->execute([
            trim($input['name']),
            $email,
            trim($input['phone'] ?? ''),
            trim($input['position_applied']),
            trim($input['experience_level'] ?? ''),
            trim($input['current_company'] ?? ''),
            trim($input['linkedin_profile'] ?? ''),
            trim($input['status'] ?? 'Applied'),
            trim($input['source'] ?? ''),
            trim($input['salary_expectation'] ?? ''),
            $input['availability_date'] ?? null,
            trim($input['location'] ?? ''),
            intval($input['willing_to_relocate'] ?? 0),
            $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Candidate updated successfully.'];
        } else {
            return ['success' => false, 'error' => 'No changes made or candidate not found.'];
        }
    } catch (Exception $e) {
        error_log("Edit candidate failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update candidate: ' . $e->getMessage()];
    }
}

/**
 * Delete candidate (admin only)
 */
function delete_candidate($db, $input)
{
    if (!is_admin()) {
        return ['success' => false, 'error' => 'Admin access required to delete candidates.'];
    }

    $id = intval($input['id'] ?? 0);

    if (!$id) {
        return ['success' => false, 'error' => 'Candidate ID is required.'];
    }

    try {
        // Get candidate name for confirmation message
        $name_stmt = $db->prepare("SELECT name FROM job_candidates WHERE id = ?");
        $name_stmt->execute([$id]);
        $candidate = $name_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$candidate) {
            return ['success' => false, 'error' => 'Candidate not found.'];
        }

        // Delete candidate (notes will be deleted automatically due to CASCADE)
        $stmt = $db->prepare("DELETE FROM job_candidates WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => "Candidate '{$candidate['name']}' deleted successfully."
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to delete candidate.'];
        }
    } catch (Exception $e) {
        error_log("Delete candidate failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete candidate: ' . $e->getMessage()];
    }
}

/**
 * Add note to candidate
 */
function add_candidate_note($db, $input)
{
    $candidate_id = intval($input['candidate_id'] ?? 0);
    $note_text = trim($input['note_text'] ?? '');

    if (!$candidate_id) {
        return ['success' => false, 'error' => 'Candidate ID is required.'];
    }

    if (empty($note_text)) {
        return ['success' => false, 'error' => 'Note text is required.'];
    }

    try {
        // Verify candidate exists
        $check_stmt = $db->prepare("SELECT id FROM job_candidates WHERE id = ?");
        $check_stmt->execute([$candidate_id]);
        if (!$check_stmt->fetch()) {
            return ['success' => false, 'error' => 'Candidate not found.'];
        }

        $stmt = $db->prepare("
            INSERT INTO candidate_notes (candidate_id, note_text, note_type, is_important, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $candidate_id,
            $note_text,
            trim($input['note_type'] ?? 'General'),
            intval($input['is_important'] ?? 0),
            get_user_id()
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
        error_log("Add candidate note failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to add note: ' . $e->getMessage()];
    }
}

/**
 * Get candidate notes
 */
function get_candidate_notes($db, $input)
{
    $candidate_id = intval($input['candidate_id'] ?? 0);

    if (!$candidate_id) {
        return ['success' => false, 'error' => 'Candidate ID is required.'];
    }

    try {
        $stmt = $db->prepare("
            SELECT
                n.*,
                u.name as created_by_name
            FROM candidate_notes n
            LEFT JOIN users u ON n.created_by = u.id
            WHERE n.candidate_id = ?
            ORDER BY n.created_at DESC
        ");

        $stmt->execute([$candidate_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'notes' => $notes];
    } catch (Exception $e) {
        error_log("Get candidate notes failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to retrieve notes.'];
    }
}

/**
 * Delete candidate note (admin only)
 */
function delete_candidate_note($db, $input)
{
    if (!is_admin()) {
        return ['success' => false, 'error' => 'Admin access required to delete notes.'];
    }

    $note_id = intval($input['note_id'] ?? 0);

    if (!$note_id) {
        return ['success' => false, 'error' => 'Note ID is required.'];
    }

    try {
        $stmt = $db->prepare("DELETE FROM candidate_notes WHERE id = ?");
        $result = $stmt->execute([$note_id]);

        if ($result && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Note deleted successfully.'];
        } else {
            return ['success' => false, 'error' => 'Note not found or already deleted.'];
        }
    } catch (Exception $e) {
        error_log("Delete candidate note failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete note: ' . $e->getMessage()];
    }
}

/**
 * Get candidate statistics
 */
function get_candidate_stats($db)
{
    try {
        // Total candidates
        $total_stmt = $db->query("SELECT COUNT(*) FROM job_candidates");
        $total = $total_stmt->fetchColumn();

        // Candidates by status
        $status_stmt = $db->query("
            SELECT status, COUNT(*) as count
            FROM job_candidates
            GROUP BY status
            ORDER BY count DESC
        ");
        $status_counts = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent candidates (last 30 days)
        $recent_stmt = $db->query("
            SELECT COUNT(*)
            FROM job_candidates
            WHERE application_date >= date('now', '-30 days')
        ");
        $recent = $recent_stmt->fetchColumn();

        // This week
        $week_stmt = $db->query("
            SELECT COUNT(*)
            FROM job_candidates
            WHERE application_date >= date('now', 'weekday 0', '-6 days')
        ");
        $week = $week_stmt->fetchColumn();

        // Today
        $today_stmt = $db->query("
            SELECT COUNT(*)
            FROM job_candidates
            WHERE application_date = date('now')
        ");
        $today = $today_stmt->fetchColumn();

        return [
            'success' => true,
            'stats' => [
                'total' => $total,
                'recent' => $recent,
                'week' => $week,
                'today' => $today,
                'by_status' => $status_counts
            ]
        ];
    } catch (Exception $e) {
        error_log("Get candidate stats failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to retrieve statistics.'];
    }
}

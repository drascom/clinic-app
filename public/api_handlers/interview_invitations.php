<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/email_functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Handle interview invitation API requests
 * 
 * Endpoints:
 * - send: Send interview invitation email
 * - list: Get list of sent invitations
 * - delete: Delete invitation record
 */
function handle_interview_invitations($action, $method, $db, $input = [])
{
    // Check authentication and admin/editor access
    if (!is_logged_in()) {
        return ['success' => false, 'error' => 'Authentication required.'];
    }

    if (!is_admin() && !is_editor()) {
        return ['success' => false, 'error' => 'Admin or Editor access required.'];
    }

    switch ($action) {
        case 'send':
            if ($method === 'POST') {
                return send_interview_invitation($db, $input);
            }
            break;

        case 'list':
            if ($method === 'POST') {
                return get_interview_invitations($db, $input);
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                return delete_interview_invitation($db, $input);
            }
            break;

        case 'stats':
            if ($method === 'POST') {
                return get_invitation_stats($db);
            }
            break;

        case 'get':
            if ($method === 'POST') {
                return get_invitation_by_id($db, $input);
            }
            break;

        case 'save_draft':
            if ($method === 'POST') {
                return save_invitation_draft($db, $input);
            }
            break;

        case 'send_draft':
            if ($method === 'POST') {
                return send_saved_invitation($db, $input);
            }
            break;
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

/**
 * Send interview invitation email
 */
function send_interview_invitation($db, $input)
{
    // Validate required fields
    $required_fields = ['staff_id', 'interview_date', 'interview_time', 'meeting_platform', 'meeting_link'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            return ['success' => false, 'error' => "Field '{$field}' is required."];
        }
    }

    // Validate staff_id
    $staff_id = intval($input['staff_id']);
    if (!$staff_id) {
        return ['success' => false, 'error' => 'Valid candidate ID is required.'];
    }

    // Get candidate information
    $candidate_stmt = $db->prepare("SELECT name, email FROM staff WHERE id = ?");
    $candidate_stmt->execute([$staff_id]);
    $candidate = $candidate_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidate) {
        return ['success' => false, 'error' => 'Candidate not found.'];
    }

    $candidate_name = $candidate['name'];
    $candidate_email = $candidate['email'];

    // Validate URL format for meeting link
    if (!filter_var($input['meeting_link'], FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'Invalid meeting link URL format.'];
    }

    // Validate video upload link if provided
    if (!empty($input['video_upload_link']) && !filter_var($input['video_upload_link'], FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'Invalid video upload link URL format.'];
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['interview_date'])) {
        return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
    }

    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}$/', $input['interview_time'])) {
        return ['success' => false, 'error' => 'Invalid time format. Use HH:MM.'];
    }

    $candidate_name = trim($input['candidate_name']);
    $candidate_email = trim($input['candidate_email']);
    $interview_date = $input['interview_date'];
    $interview_time = $input['interview_time'];
    $meeting_platform = trim($input['meeting_platform']);
    $meeting_link = trim($input['meeting_link']);
    $interview_duration = $input['interview_duration'] ?? '20 minutes';
    $video_upload_link = !empty($input['video_upload_link']) ? trim($input['video_upload_link']) : null;
    $notes = trim($input['notes'] ?? '');
    $sent_by = get_user_id();

    // Format date for email display
    $formatted_date = date('l, F j, Y', strtotime($interview_date));

    // Format time for email display
    $formatted_time = date('g:i A', strtotime($interview_time));

    // Create email content
    $subject = "Invitation to Online Interview – Hair Transplant Position at Liv & Harley Street";

    $email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
            .content { padding: 20px 0; }
            .details { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Interview Invitation</h2>
            </div>
            
            <div class='content'>
                <p>Dear {$candidate_name},</p>
                
                <p>Thank you for your interest in the Hair Transplant position at Liv & Harley Street.</p>
                
                <p>We are pleased to invite you to an online interview to further discuss your qualifications and experience. The interview will be held via {$meeting_platform}, and it will last approximately {$interview_duration}.</p>
                
                <div class='details'>
                    <strong>Interview Details:</strong><br>
                    <strong>Date:</strong> {$formatted_date}<br>
                    <strong>Time:</strong> {$formatted_time}<br>
                    <strong>Platform:</strong> {$meeting_platform}<br>
                    <strong>Link:</strong> <a href='{$meeting_link}'>{$meeting_link}</a>
                </div>";

    // Add video upload section if link is provided
    if ($video_upload_link) {
        $email_body .= "
                <div class='details' style='background-color: #fff3cd; border-left: 4px solid #ffc107; margin: 25px 0;'>
                    <strong style='color: #d63384; font-size: 16px;'>📹 REQUIRED VIDEO SUBMISSIONS</strong><br><br>

                    <p style='margin: 10px 0; font-weight: bold; color: #333;'>
                        You MUST upload TWO videos via the provided link for your application to be considered complete:
                    </p>

                    <div style='margin: 15px 0; padding: 10px; background-color: #e7f3ff; border-radius: 5px;'>
                        <strong style='color: #0066cc;'>📋 Video Requirements:</strong><br>
                        <strong>Video 1:</strong> Graft extraction procedure (minimum 1 minute duration)<br>
                        <strong>Video 2:</strong> Graft implantation procedure (minimum 1 minute duration)
                    </div>

                    <p style='margin: 15px 0;'>
                        <strong>Upload Link:</strong> <a href='{$video_upload_link}' style='color: #0066cc; text-decoration: underline;'>{$video_upload_link}</a>
                    </p>

                    <div style='background-color: #ff6b35; color: white; padding: 12px; border-radius: 8px; text-align: center; margin: 15px 0; border: 3px solid #ff4500;'>
                        <strong style='font-size: 18px; display: block; margin-bottom: 5px;'>🔑 IMPORTANT - LOGIN PASSWORD:</strong>
                        <span style='font-size: 24px; font-weight: bold; letter-spacing: 2px; background-color: #ffffff; color: #ff4500; padding: 8px 16px; border-radius: 5px; display: inline-block;'>hsh</span>
                        <div style='font-size: 12px; margin-top: 8px; opacity: 0.9;'>(enter exactly as shown, without quotes)</div>
                    </div>

                    <p style='margin: 10px 0; font-weight: bold; color: #d63384;'>
                        ⚠️ Both videos are mandatory - incomplete submissions will not be processed.
                    </p>
                </div>";
    }

    $email_body .= "
                <p>Please note that the interview may include a section conducted in English, in order to assess your language proficiency and communication skills with international patients.</p>
                
                <p>During the interview, we would like to learn more about your background, practical experience in hair transplantation procedures, and your approach to patient care.</p>
                
                <p>Kindly confirm your availability for the proposed time, or suggest alternative slots if needed. If you have any questions ahead of the meeting, feel free to get in touch.</p>
                
                <p>We look forward to speaking with you soon.</p>
            </div>
            
            <div class='footer'>
                <p>Kind regards,<br>
                Liv HSH Team<br>
                <a href='mailto:hr@livharleystreet.uk'>hr@livharleystreet.uk</a></p>
            </div>
        </div>
    </body>
    </html>";

    try {
        // Send email using existing email system
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($candidate_email, $candidate_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $email_body;

        $mail->send();

        // Store invitation record in database with 'sent' status
        $stmt = $db->prepare("
            INSERT INTO interview_invitations
            (staff_id, interview_date, interview_time, meeting_platform, meeting_link, interview_duration, video_upload_link, sent_by, email_status, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sent', 'sent', ?)
        ");

        $result = $stmt->execute([
            $staff_id,
            $interview_date,
            $interview_time,
            $meeting_platform,
            $meeting_link,
            $interview_duration,
            $video_upload_link,
            $sent_by,
            $notes
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => "Interview invitation sent successfully to {$candidate_name} ({$candidate_email})",
                'invitation_id' => $db->lastInsertId()
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to save invitation record to database.'];
        }

    } catch (Exception $e) {
        error_log("Interview invitation email failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to send email: ' . $e->getMessage()];
    }
}

/**
 * Get list of sent interview invitations
 */
function get_interview_invitations($db, $input)
{
    $page = max(1, intval($input['page'] ?? 1));
    $limit = max(1, min(100, intval($input['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // Build WHERE clause for comprehensive search
    $where_conditions = [];
    $params = [];

    if (!empty($input['search'])) {
        $search_term = '%' . $input['search'] . '%';
        $where_conditions[] = "(
            c.name LIKE ? OR
            c.email LIKE ? OR
            ii.meeting_platform LIKE ? OR
            ii.interview_date LIKE ? OR
            ii.interview_time LIKE ? OR
            ii.notes LIKE ? OR
            u.username LIKE ? OR
            u.name LIKE ? OR
            u.surname LIKE ?
        )";
        // Add the search term for each field
        for ($i = 0; $i < 9; $i++) {
            $params[] = $search_term;
        }
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get total count with search
    $count_query = "
        SELECT COUNT(*)
        FROM interview_invitations ii
        LEFT JOIN users u ON ii.sent_by = u.id
        LEFT JOIN staff c ON ii.staff_id = c.id
        {$where_clause}
    ";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();

    // Get invitations with sender and candidate information and search
    $query = "
        SELECT
            ii.*,
            c.name as candidate_name,
            c.email as candidate_email,
            c.phone as candidate_phone,
            c.position_applied as candidate_position,
            u.username as sent_by_username,
            u.name as sent_by_name,
            u.surname as sent_by_surname
        FROM interview_invitations ii
        LEFT JOIN users u ON ii.sent_by = u.id
        LEFT JOIN staff c ON ii.staff_id = c.id
        {$where_clause}
        ORDER BY ii.sent_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $db->prepare($query);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'success' => true,
        'invitations' => $invitations,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ];
}

/**
 * Delete interview invitation record (Admin only)
 */
function delete_interview_invitation($db, $input)
{
    // Check if user is admin
    if (!is_admin()) {
        return ['success' => false, 'error' => 'Admin access required to delete invitations.'];
    }

    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        return ['success' => false, 'error' => 'Invalid invitation ID.'];
    }

    $stmt = $db->prepare("DELETE FROM interview_invitations WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result && $stmt->rowCount() > 0) {
        return ['success' => true, 'message' => 'Invitation record deleted successfully.'];
    } else {
        return ['success' => false, 'error' => 'Invitation not found or already deleted.'];
    }
}

/**
 * Get invitation statistics
 */
function get_invitation_stats($db)
{
    try {
        // Total invitations
        $total_stmt = $db->query("SELECT COUNT(*) FROM interview_invitations");
        $total = $total_stmt->fetchColumn();

        // This month
        $month_stmt = $db->query("
            SELECT COUNT(*) FROM interview_invitations
            WHERE strftime('%Y-%m', sent_at) = strftime('%Y-%m', 'now')
        ");
        $month = $month_stmt->fetchColumn();

        $waiting_stmt = $db->query("SELECT COUNT(*) FROM interview_invitations WHERE email_status=='draft'");
        $waiting = $waiting_stmt->fetchColumn();


        return [
            'success' => true,
            'stats' => [
                'total' => intval($total),
                'month' => intval($month),
                'waiting' => intval($waiting),

            ]
        ];
    } catch (Exception $e) {
        error_log("Error getting invitation stats: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to load statistics.'];
    }
}

/**
 * Get single invitation by ID
 */
function get_invitation_by_id($db, $input)
{
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        return ['success' => false, 'error' => 'Invalid invitation ID.'];
    }

    try {
        // Get invitation with sender and candidate information
        $stmt = $db->prepare("
            SELECT
                ii.*,
                c.name as candidate_name,
                c.email as candidate_email,
                c.phone as candidate_phone,
                c.position_applied as candidate_position,
                u.username as sent_by_username,
                u.name as sent_by_name,
                u.surname as sent_by_surname,
                u.email as sent_by_email
            FROM interview_invitations ii
            LEFT JOIN users u ON ii.sent_by = u.id
            LEFT JOIN staff c ON ii.staff_id = c.id
            WHERE ii.id = ?
        ");

        $stmt->execute([$id]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitation) {
            return ['success' => false, 'error' => 'Invitation not found.'];
        }

        return [
            'success' => true,
            'invitation' => $invitation
        ];
    } catch (Exception $e) {
        error_log("Error getting invitation by ID: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to load invitation details.'];
    }
}

/**
 * Save invitation as draft without sending email
 */
function save_invitation_draft($db, $input)
{
    // Validate required fields
    $required_fields = ['candidate_id', 'interview_date', 'interview_time', 'meeting_platform', 'meeting_link'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            return ['success' => false, 'error' => "Field '{$field}' is required."];
        }
    }

    // Validate staff_id
    $staff_id = intval($input['candidate_id']);
    if (!$staff_id) {
        return ['success' => false, 'error' => 'Valid candidate ID is required.'];
    }

    // Get candidate information
    $candidate_stmt = $db->prepare("SELECT name, email FROM staff WHERE id = ?");
    $candidate_stmt->execute([$staff_id]);
    $candidate = $candidate_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidate) {
        return ['success' => false, 'error' => 'Candidate not found.'];
    }

    $candidate_name = $candidate['name'];
    $candidate_email = $candidate['email'];

    // Validate URL format for meeting link
    if (!filter_var($input['meeting_link'], FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'Invalid meeting link URL format.'];
    }

    // Validate video upload link if provided
    if (!empty($input['video_upload_link']) && !filter_var($input['video_upload_link'], FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'Invalid video upload link URL format.'];
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['interview_date'])) {
        return ['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'];
    }

    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}$/', $input['interview_time'])) {
        return ['success' => false, 'error' => 'Invalid time format. Use HH:MM.'];
    }

    $interview_date = $input['interview_date'];
    $interview_time = $input['interview_time'];
    $meeting_platform = trim($input['meeting_platform']);
    $meeting_link = trim($input['meeting_link']);
    $interview_duration = $input['interview_duration'] ?? '20 minutes';
    $video_upload_link = !empty($input['video_upload_link']) ? trim($input['video_upload_link']) : null;
    $notes = trim($input['notes'] ?? '');
    $sent_by = get_user_id();

    try {
        $invitation_id = isset($input['id']) ? intval($input['id']) : 0;

        if ($invitation_id > 0) {
            // UPDATE existing draft
            $stmt = $db->prepare("
                UPDATE interview_invitations SET
                    staff_id = ?,
                    interview_date = ?,
                    interview_time = ?,
                    meeting_platform = ?,
                    meeting_link = ?,
                    interview_duration = ?,
                    video_upload_link = ?,
                    notes = ?,
                    sent_by = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $staff_id,
                $interview_date,
                $interview_time,
                $meeting_platform,
                $meeting_link,
                $interview_duration,
                $video_upload_link,
                $notes,
                $sent_by,
                $invitation_id
            ]);
            $message = "Draft updated successfully for {$candidate_name}.";
        } else {
            // INSERT new draft
            $stmt = $db->prepare("
                INSERT INTO interview_invitations
                (staff_id, interview_date, interview_time, meeting_platform, meeting_link, interview_duration, video_upload_link, sent_by, email_status, status, notes, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', 'draft', ?, NULL)
            ");
            $result = $stmt->execute([
                $staff_id,
                $interview_date,
                $interview_time,
                $meeting_platform,
                $meeting_link,
                $interview_duration,
                $video_upload_link,
                $sent_by,
                $notes
            ]);
            if ($result) {
                $invitation_id = $db->lastInsertId();
            }
            $message = "Interview invitation saved as draft for {$candidate_name}.";
        }

        if ($result) {
            return [
                'success' => true,
                'message' => $message,
                'invitation_id' => $invitation_id
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to save invitation draft to database.'];
        }

    } catch (Exception $e) {
        error_log("Save invitation draft failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to save invitation draft: ' . $e->getMessage()];
    }
}

/**
 * Send a saved draft invitation
 */
function send_saved_invitation($db, $input)
{
    $id = intval($input['id'] ?? 0);
    error_log("send_saved_invitation: Received ID: " . $id);

    if ($id <= 0) {
        error_log("send_saved_invitation: Invalid invitation ID: " . $id);
        return ['success' => false, 'error' => 'Invalid invitation ID.'];
    }

    try {
        // Get the draft invitation with candidate information
        $stmt = $db->prepare("
            SELECT ii.*, c.name as candidate_name, c.email as candidate_email
            FROM interview_invitations ii
            LEFT JOIN staff c ON ii.staff_id = c.id
            WHERE ii.id = ? AND ii.status = 'draft'
        ");
        $stmt->execute([$id]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitation) {
            error_log("send_saved_invitation: Draft invitation with ID {$id} not found or not in 'draft' status.");
            return ['success' => false, 'error' => 'Draft invitation not found.'];
        }
        error_log("send_saved_invitation: Invitation found. Candidate Email: " . $invitation['candidate_email']);

        // Extract invitation data
        $candidate_name = $invitation['candidate_name'];
        $candidate_email = $invitation['candidate_email'];
        $interview_date = $invitation['interview_date'];
        $interview_time = $invitation['interview_time'];
        $meeting_platform = $invitation['meeting_platform'];
        $meeting_link = $invitation['meeting_link'];
        $interview_duration = $invitation['interview_duration'];
        $video_upload_link = $invitation['video_upload_link'];

        // Format date and time for email display
        $formatted_date = date('l, F j, Y', strtotime($interview_date));
        $formatted_time = date('g:i A', strtotime($interview_time));

        // Create email content (same as send_interview_invitation function)
        $subject = "Invitation to Online Interview – Hair Transplant Position at Liv & Harley Street";

        $email_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .content { padding: 20px 0; }
                .details { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Interview Invitation</h2>
                </div>

                <div class='content'>
                    <p>Dear {$candidate_name},</p>

                    <p>Thank you for your interest in the Hair Transplant position at Liv & Harley Street.</p>

                    <p>We are pleased to invite you to an online interview to further discuss your qualifications and experience. The interview will be held via {$meeting_platform}, and it will last approximately {$interview_duration}.</p>

                    <div class='details'>
                        <strong>Interview Details:</strong><br>
                        <strong>Date:</strong> {$formatted_date}<br>
                        <strong>Time:</strong> {$formatted_time}<br>
                        <strong>Platform:</strong> {$meeting_platform}<br>
                        <strong>Link:</strong> <a href='{$meeting_link}'>{$meeting_link}</a>
                    </div>";

        // Add video upload section if link is provided
        if ($video_upload_link) {
            $email_body .= "
                    <div class='details' style='background-color: #fff3cd; border-left: 4px solid #ffc107; margin: 25px 0;'>
                        <strong style='color: #d63384; font-size: 16px;'>📹 REQUIRED VIDEO SUBMISSIONS</strong><br><br>

                        <p style='margin: 10px 0; font-weight: bold; color: #333;'>
                            You MUST upload TWO videos via the provided link for your application to be considered complete:
                        </p>

                        <div style='margin: 15px 0; padding: 10px; background-color: #e7f3ff; border-radius: 5px;'>
                            <strong style='color: #0066cc;'>📋 Video Requirements:</strong><br>
                            <strong>Video 1:</strong> Graft extraction procedure (minimum 1 minute duration)<br>
                            <strong>Video 2:</strong> Graft implantation procedure (minimum 1 minute duration)
                        </div>

                        <p style='margin: 15px 0;'>
                            <strong>Upload Link:</strong> <a href='{$video_upload_link}' style='color: #0066cc; text-decoration: underline;'>{$video_upload_link}</a>
                        </p>

                        <div style='background-color: #ff6b35; color: white; padding: 12px; border-radius: 8px; text-align: center; margin: 15px 0; border: 3px solid #ff4500;'>
                            <strong style='font-size: 18px; display: block; margin-bottom: 5px;'>🔑 IMPORTANT - LOGIN PASSWORD:</strong>
                            <span style='font-size: 24px; font-weight: bold; letter-spacing: 2px; background-color: #ffffff; color: #ff4500; padding: 8px 16px; border-radius: 5px; display: inline-block;'>hsh</span>
                            <div style='font-size: 12px; margin-top: 8px; opacity: 0.9;'>(enter exactly as shown, without quotes)</div>
                        </div>

                        <p style='margin: 10px 0; font-weight: bold; color: #d63384;'>
                            ⚠️ Both videos are mandatory - incomplete submissions will not be processed.
                        </p>
                    </div>";
        }

        $email_body .= "
                    <p>Please note that the interview may include a section conducted in English, in order to assess your language proficiency and communication skills with international patients.</p>

                    <p>During the interview, we would like to learn more about your background, practical experience in hair transplantation procedures, and your approach to patient care.</p>

                    <p>Kindly confirm your availability for the proposed time, or suggest alternative slots if needed. If you have any questions ahead of the meeting, feel free to get in touch.</p>

                    <p>We look forward to speaking with you soon.</p>
                </div>

                <div class='footer'>
                    <p>Kind regards,<br>
                    Liv HSH Team<br>
                    <a href='mailto:hr@livharleystreet.uk'>hr@livharleystreet.uk</a></p>
                </div>
            </div>
        </body>
        </html>";

        // Send email using existing email system
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        error_log("PHPMailer Config: Host=" . (defined('SMTP_HOST') ? SMTP_HOST : 'UNDEFINED') .
            ", Username=" . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'UNDEFINED') .
            ", SMTPSecure=" . (defined('SMTP_SECURE') ? SMTP_SECURE : 'UNDEFINED') .
            ", Port=" . (defined('SMTP_PORT') ? SMTP_PORT : 'UNDEFINED') .
            ", FromEmail=" . (defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'UNDEFINED') .
            ", FromName=" . (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'UNDEFINED'));
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($candidate_email, $candidate_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $email_body;

        $mail->send();

        // Update invitation status to 'sent' and set sent_at timestamp
        $update_stmt = $db->prepare("
            UPDATE interview_invitations
            SET status = 'sent', email_status = 'sent', sent_at = datetime('now')
            WHERE id = ?
        ");

        $update_result = $update_stmt->execute([$id]);

        if ($update_result) {
            return [
                'success' => true,
                'message' => "Interview invitation sent successfully to {$candidate_name} ({$candidate_email})",
                'invitation_id' => $id
            ];
        } else {
            return ['success' => false, 'error' => 'Email sent but failed to update invitation status.'];
        }

    } catch (Exception $e) {
        error_log("Send saved invitation failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to send invitation: ' . $e->getMessage()];
    }
}
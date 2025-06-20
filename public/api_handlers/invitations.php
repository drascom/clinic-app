<?php
function handle_invitations($action, $method, $db, $request_data = [])
{
    $input = $request_data;
    // log_response($action, $method, $request_data); // Uncomment for debugging

    switch ($action) {
        case 'list':
            if ($method === 'POST') {
                // Fetch all invitations
                $stmt = $db->query("SELECT id, email, role, agency_id, status, created_at, used_at FROM invitations ORDER BY created_at DESC");
                return ['success' => true, 'invitations' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("SELECT id, email, role, agency_id, status, created_at, used_at FROM invitations WHERE id = ?");
                    $stmt->execute([$id]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $data ? ['success' => true, 'invitation' => $data] : ['success' => false, 'error' => "Invitation not found with ID: {$id}"];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    // Check if invitation exists
                    $check_stmt = $db->prepare("SELECT id FROM invitations WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'Invitation not found.'];
                    }

                    $stmt = $db->prepare("DELETE FROM invitations WHERE id = ?");
                    $stmt->execute([$id]);
                    return ['success' => true, 'message' => 'Invitation deleted successfully.'];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'resend':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    // Check if invitation exists and is pending
                    $check_stmt = $db->prepare("SELECT id, email FROM invitations WHERE id = ? AND status = 'pending'");
                    $check_stmt->execute([$id]);
                    $invitation = $check_stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$invitation) {
                        return ['success' => false, 'error' => 'Invitation not found or is not pending.'];
                    }

                    // Generate new token and expiry
                    $token = bin2hex(random_bytes(16)); // Generate a 32-character hex token
                    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token valid for 24 hours

                    // Update invitation with new token and expiry, reset status to pending
                    $update_stmt = $db->prepare("UPDATE invitations SET token = :token, reset_expiry = :expiry, status = 'pending', used_at = NULL, updated_at = datetime('now') WHERE id = :id");
                    $update_stmt->bindParam(':token', $token);
                    $update_stmt->bindParam(':expiry', $expiry);
                    $update_stmt->bindParam(':id', $id);

                    if ($update_stmt->execute()) {
                        // Re-send email using the send_mail handler
                        $invitation_link = "http://" . $_SERVER['HTTP_HOST'] . '/auth/enter_user_details.php?token=' . urlencode($token);
                        $email_subject = 'Complete Your Registration';
                        $email_body = "Hello,\n\nYou have been invited to join the system.\n\nClick the link below to set your password and activate your account:\n\n{$invitation_link}\n\nThis link will expire in 24 hours.\n\nThank you!";

                        $mail_request_data = [
                            'to' => $invitation['email'],
                            'subject' => $email_subject,
                            'body' => $email_body,
                        ];

                        // Call the handle_send_mail function directly
                        $mail_response = handle_send_mail('send', 'POST', $db, $mail_request_data);

                        if ($mail_response['success']) {
                            return ['success' => true, 'message' => 'Invitation resent successfully.'];
                        } else {
                            error_log("Failed to resend invitation email to {$invitation['email']}: " . ($mail_response['message'] ?? 'Unknown error'));
                            return ['success' => true, 'message' => 'Invitation token updated, but failed to resend email.', 'email_error' => $mail_response['message'] ?? 'Unknown email error'];
                        }
                    } else {
                        return ['success' => false, 'error' => 'Failed to update invitation token.'];
                    }
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        default:
            return ['success' => false, 'error' => "Invalid action '{$action}' for invitations entity."];
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}
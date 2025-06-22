<?php
require_once __DIR__ . '/../services/LogService.php';
require_once __DIR__ . '/../services/EmailService.php';

function handle_invitations($action, $method, $db, $request_data = [])
{
    $logService = new LogService();
    $input = $request_data;
    // log_response($action, $method, $request_data); // Uncomment for debugging

    switch ($action) {
        case 'list':
            if ($method === 'POST') {
                // Fetch all invitations
                $stmt = $db->query("SELECT id, email, role, agency_id, status, created_at, used_at FROM invitations ORDER BY created_at DESC");
                $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $logService->log('invitations', 'success', 'Invitations listed successfully.', ['count' => count($invitations)]);
                return ['success' => true, 'invitations' => $invitations];
            } else {
                $logService->log('invitations', 'error', 'Invalid method for list action.', ['method' => $method]);
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("SELECT id, email, role, agency_id, status, created_at, used_at FROM invitations WHERE id = ?");
                    $stmt->execute([$id]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($data) {
                        $logService->log('invitations', 'success', 'Invitation retrieved successfully.', ['id' => $id]);
                        return ['success' => true, 'invitation' => $data];
                    } else {
                        $logService->log('invitations', 'error', "Invitation not found with ID: {$id}", ['id' => $id]);
                        return ['success' => false, 'error' => "Invitation not found with ID: {$id}"];
                    }
                }
                $logService->log('invitations', 'error', 'ID is required for get action.', $input);
                return ['success' => false, 'error' => 'ID is required.'];
            } else {
                $logService->log('invitations', 'error', 'Invalid method for get action.', ['method' => $method]);
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
                        $logService->log('invitations', 'error', 'Invitation not found for delete.', ['id' => $id]);
                        return ['success' => false, 'error' => 'Invitation not found.'];
                    }

                    $stmt = $db->prepare("DELETE FROM invitations WHERE id = ?");
                    $stmt->execute([$id]);
                    $logService->log('invitations', 'success', 'Invitation deleted successfully.', ['id' => $id]);
                    return ['success' => true, 'message' => 'Invitation deleted successfully.'];
                }
                $logService->log('invitations', 'error', 'ID is required for delete action.', $input);
                return ['success' => false, 'error' => 'ID is required.'];
            } else {
                $logService->log('invitations', 'error', 'Invalid method for delete action.', ['method' => $method]);
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
                        $logService->log('invitations', 'error', 'Invitation not found or is not pending for resend.', ['id' => $id]);
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

                        // Call the EmailService
                        $emailService = new EmailService($db);
                        $mail_response = $emailService->sendInvitationEmail(
                            $_SESSION['user_id'],
                            $invitation['email'],
                            $invitation_link,
                            'user' // Assuming 'user' role for now, adjust if needed
                        );

                        if ($mail_response['success']) {
                            $logService->log('invitations', 'success', 'Invitation resent successfully.', ['id' => $id, 'email' => $invitation['email']]);
                            return ['success' => true, 'message' => 'Invitation resent successfully.'];
                        } else {
                            $logService->log('invitations', 'error', "Failed to resend invitation email to {$invitation['email']}: " . ($mail_response['message'] ?? 'Unknown error'), ['id' => $id, 'email' => $invitation['email']]);
                            return ['success' => true, 'message' => 'Invitation token updated, but failed to resend email.', 'email_error' => $mail_response['message'] ?? 'Unknown email error'];
                        }
                    } else {
                        $logService->log('invitations', 'error', 'Failed to update invitation token for resend.', ['id' => $id]);
                        return ['success' => false, 'error' => 'Failed to update invitation token.'];
                    }
                }
                $logService->log('invitations', 'error', 'ID is required for resend action.', $input);
                return ['success' => false, 'error' => 'ID is required.'];
            } else {
                $logService->log('invitations', 'error', 'Invalid method for resend action.', ['method' => $method]);
            }
            break;

        default:
            $logService->log('invitations', 'error', "Invalid action '{$action}' for invitations entity.", ['action' => $action]);
            return ['success' => false, 'error' => "Invalid action '{$action}' for invitations entity."];
    }

    $logService->log('invitations', 'error', "Invalid request for action '{$action}' with method '{$method}'.", ['action' => $action, 'method' => $method]);
    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

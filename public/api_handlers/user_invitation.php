<?php

require_once __DIR__ . '/email_functions.php';
require_once __DIR__ . '/../services/LogService.php';

function handle_invite_process($action, $method, $db, $request_data = [])
{
    $logService = new LogService();
    $input = $request_data;

    switch ($action) {
        case 'invite':
            if ($method === 'POST') {
                $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
                $agency_id = htmlspecialchars($input['agency_id'] ?? '', ENT_QUOTES, 'UTF-8');
                $role = htmlspecialchars($input['role'] ?? '', ENT_QUOTES, 'UTF-8');

                // Basic validation
                if (empty($email) || empty($role)) {
                    return ['success' => false, 'error' => 'Email and role are required.'];
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return ['success' => false, 'error' => 'Invalid email format.'];
                }

                // Check if user already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'Email is already registered.'];
                }

                // Check if there is already a pending invitation
                $stmt = $db->prepare("SELECT id FROM invitations WHERE email = :email AND status = 'pending'");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'An invitation has already been sent to this email.'];
                }

                // Generate token
                $token = bin2hex(random_bytes(16)); // 32-character secure token
                $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

                // Insert into invitations table
                $stmt = $db->prepare("INSERT INTO invitations (email, role, agency_id, token, status, created_at)
                                      VALUES (:email, :role, :agency_id, :token, 'pending', datetime('now'))");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':role', $role);
                $stmt->bindValue(':agency_id', $agency_id ?: null, PDO::PARAM_INT);
                $stmt->bindParam(':token', $token);

                if ($stmt->execute()) {
                    // Success: Generate link to send
                    $invitation_link = "http://" . $_SERVER['HTTP_HOST'] . '/enter_user_details.php?token=' . urlencode($token);

                    // Send email using the new EmailService
                    $emailService = new EmailService($db);
                    $userId = $_SESSION['user_id'] ?? null;

                    if ($userId === null) {
                        return ['success' => false, 'error' => 'User not authenticated for sending email.'];
                    }

                    $mail_response = $emailService->sendInvitationEmail($userId, $email, $invitation_link, $role);

                    if ($mail_response['success']) {
                        return [
                            'success' => true,
                            'message' => 'Invitation created and email sent successfully.',
                            'invitation_link' => $invitation_link
                        ];
                    } else {
                        // Log the email sending error but still report invitation creation success
                        error_log("Failed to send invitation email to {$email}: " . ($mail_response['error'] ?? 'Unknown error'));
                        return [
                            'success' => true, // Report success for invitation creation
                            'message' => 'Invitation created, but failed to send email.',
                            'email_error' => $mail_response['error'] ?? 'Unknown email error',
                            'invitation_link' => $invitation_link
                        ];
                    }
                } else {
                    return ['success' => false, 'error' => 'Failed to create invitation.'];
                }
            }

            return ['success' => false, 'error' => 'Invalid method for invite action.'];

        default:
            return ['success' => false, 'error' => "Invalid action '{$action}'."];
    }
}

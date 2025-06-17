<?php

require_once __DIR__ . '/send_mail.php';

function handle_invite_process($action, $method, $db, $request_data = [])
{
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

                    // Send email using the send_mail handler
                    $email_subject = 'Complete Your Registration';
                    $email_body = "Hello,\n\nYou have been invited to join the system as a '{$role}'.\n\nClick the link below to set your password and activate your account:\n\n{$invitation_link}\n\nThis link will expire in 24 hours.\n\nThank you!";

                    $mail_request_data = [
                        'to' => $email,
                        'subject' => $email_subject,
                        'body' => $email_body,
                        // Optional headers can be added here if needed
                        // 'headers' => "From: no-reply@yourdomain.com"
                    ];

                    // Call the handle_send_mail function directly
                    $mail_response = handle_send_mail('send', 'POST', $db, $mail_request_data);

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
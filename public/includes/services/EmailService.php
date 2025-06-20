<?php

// It's crucial to use a proper autoloader in a real application.
// If composer is used, this would be require_once __DIR__ . '/../../../vendor/autoload.php';
// For now, we'll assume PHPMailer is available.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Sends an email using user-specific SMTP settings.
     *
     * @param int    $userId The ID of the user whose SMTP settings should be used.
     * @param string $recipient The email address of the recipient.
     * @param string $subject The subject of the email.
     * @param string $htmlBody The HTML content of the email.
     * @return array An associative array with 'success' (boolean) and 'message' (string).
     */
    public function send(int $userId, string $recipient, string $subject, string $htmlBody): array
    {
        // Use the global function to get SMTP settings
        $settings = get_user_email_settings($this->db, $userId);

        if (!$settings) {
            return ['success' => false, 'message' => 'SMTP settings not found for the user.'];
        }

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_user'];
            $mail->Password = $settings['smtp_pass'];
            $mail->SMTPSecure = $settings['smtp_secure'] === 'none' ? '' : $settings['smtp_secure'];
            $mail->Port = $settings['smtp_port'];
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom($settings['smtp_user'], 'Your Application Name'); // Consider making the "From" name configurable
            $mail->addAddress($recipient);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully.'];
        } catch (Exception $e) {
            // In a real application, you would log this error.
            // error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
        }
    }
    /**
     * Sends a generic email using user-specific SMTP settings.
     *
     * @param int    $userId The ID of the user whose SMTP settings should be used.
     * @param string $recipient The email address of the recipient.
     * @param string $subject The subject of the email.
     * @param string $htmlBody The HTML content of the email.
     * @return array An associative array with 'success' (boolean) and 'message' (string).
     */
    public function sendGenericEmail(int $userId, string $recipient, string $subject, string $htmlBody): array
    {
        return $this->send($userId, $recipient, $subject, $htmlBody);
    }

    /**
     * Sends a password reset email.
     *
     * @param int    $userId The user's ID.
     * @param string $recipient_email The email address of the recipient.
     * @param string $reset_link The password reset link.
     * @return bool True on success, false on failure.
     */
    public function sendPasswordResetEmail(int $userId, string $recipient_email, string $reset_link): bool
    {
        $subject = 'Password Reset Request';
        $body = '&lt;p&gt;You requested a password reset. Click the link below to reset your password:&lt;/p&gt;';
        $body .= '&lt;p&gt;&lt;a href=&quot;' . $reset_link . '&quot;&gt;' . $reset_link . '&lt;/a&gt;&lt;/p&gt;';
        $body .= '&lt;p&gt;If you did not request a password reset, please ignore this email.&lt;/p&gt;';

        $result = $this->send($userId, $recipient_email, $subject, $body);

        if (!$result['success']) {
            error_log("Password reset email failed to send to {$recipient_email}. Error: {$result['message']}");
        }

        return $result['success'];
    }

    /**
     * Sends an invitation email.
     *
     * @param int    $userId The ID of the user sending the invitation.
     * @param string $recipient_email The email address of the recipient.
     * @param string $invitation_link The invitation link.
     * @param string $role The role the user is being invited to.
     * @return array An associative array with 'success' (boolean) and 'message' (string).
     */
    public function sendInvitationEmail(int $userId, string $recipient_email, string $invitation_link, string $role): array
    {
        $subject = 'Complete Your Registration';
        $body = "Hello,\n\nYou have been invited to join the system as a '{$role}'.\n\nClick the link below to set your password and activate your account:\n\n{$invitation_link}\n\nThis link will expire in 24 hours.\n\nThank you!";

        return $this->send($userId, $recipient_email, $subject, $body);
    }

}
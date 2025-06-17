<?php
require './../vendor/autoload.php'; // Include Composer autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Note: PHPMailer library is required. You need to install it
// (e.g., composer require phpmailer/phpmailer). The autoloader is now included.

// Load environment variables from .env file in the secrets directory at the project root
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../secrets');
$dotenv->load();

// Configure your SMTP settings securely using environment variables
define('SMTP_HOST', $_ENV['EMAIL_HOST']); // Outgoing Server
define('SMTP_USERNAME', $_ENV['EMAIL_USERNAME']); // SMTP username
define('SMTP_PASSWORD', $_ENV['EMAIL_PASSWORD']); // SMTP password
define('SMTP_PORT', $_ENV['EMAIL_PORT']); // SMTP Port
define('SMTP_SECURE', PHPMailer::ENCRYPTION_SMTPS); // Enable implicit TLS encryption

// Configure the 'From' email address and name using environment variables
define('MAIL_FROM_EMAIL', $_ENV['EMAIL_FROM']); // Sender email address
define('MAIL_FROM_NAME', $_ENV['EMAIL_FROM_NAME']); // Sender name

function send_password_reset_email($recipient_email, $reset_link)
{
    $mail = new PHPMailer(true); // Pass true to enable exceptions

    try {
        // Server settings
        $mail->SMTPDebug = 0; // Set to 2 for detailed debug output during testing
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($recipient_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = '<p>You requested a password reset. Click the link below to reset your password:</p>';
        $mail->Body .= '<p><a href="' . $reset_link . '">' . $reset_link . '</a></p>';
        $mail->Body .= '<p>If you did not request a password reset, please ignore this email.</p>';
        $mail->AltBody = 'You requested a password reset. Copy and paste the link below into your browser: ' . $reset_link;

        $mail->send();

        // Log the password reset request
        $log_entry = "[" . date('Y-m-d H:i:s') . "] Password reset email sent to: {$recipient_email}\n";
        file_put_contents(__DIR__ . '/../../logs/email.log', $log_entry, FILE_APPEND);

        return true; // Email sent successfully
    } catch (Exception $e) {
        // Log the error or handle it appropriately
        error_log("Password reset email failed to send to {$recipient_email}. Mailer Error: {$mail->ErrorInfo}");
        return false; // Email sending failed
    }
}

// You can add other email related functions here if needed
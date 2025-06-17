<?php

require './../vendor/autoload.php'; // Include Composer autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Note: PHPMailer library is required. You need to install it
// (e.g., composer require phpmailer/phpmailer). The autoloader is now included.

// Load environment variables from .env file in the secrets directory at the project root
// Adjust the path if your secrets directory is located elsewhere relative to this file
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


function handle_send_mail($action, $method, $db, $request_data = [])
{
    $input = $request_data;
    // log_response($action, $method, $request_data); // Uncomment for debugging

    switch ($action) {
        case 'send':
            if ($method === 'POST') {
                $to = $input['to'] ?? null;
                $subject = $input['subject'] ?? null;
                $body = $input['body'] ?? null;
                // Optional: $altBody = $input['altBody'] ?? ''; // Plain text body
                // Optional: $isHTML = $input['isHTML'] ?? false; // Set email format to HTML
                // Optional: $attachments = $input['attachments'] ?? []; // Array of file paths

                if (empty($to) || empty($subject) || empty($body)) {
                    return ['success' => false, 'error' => 'Recipient, subject, and body are required.'];
                }

                // Basic email format validation for recipient
                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    return ['success' => false, 'error' => 'Invalid recipient email format.'];
                }

                // PHPMailer sending logic
                $mail = new PHPMailer(true); // Passing true enables exceptions
                try {
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
                    $mail->addAddress($to); // Add a recipient

                    // Content
                    $mail->isHTML(true); // Set email format to HTML (assuming HTML body by default)
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    // Optional: $mail->AltBody = $altBody;

                    // Optional: Add attachments
                    // foreach ($attachments as $attachment_path) {
                    //     if (file_exists($attachment_path)) {
                    //         $mail->addAttachment($attachment_path);
                    //     } else {
                    //         // Log or handle missing attachment file
                    //     }
                    // }

                    $mail->send();

                    // Log successful email send (optional)
                    // error_log("Email sent successfully to {$to} with subject: {$subject}");
                    return ['success' => true, 'message' => 'Email sent successfully.'];
                } catch (Exception $e) {
                    // Log email send failure
                    error_log("Email send failed to {$to} with subject: {$subject}. Mailer Error: {$mail->ErrorInfo}");
                    return ['success' => false, 'error' => 'Failed to send email. Mailer Error: ' . $mail->ErrorInfo];
                }
            } else {
                return ['success' => false, 'error' => "Method '{$method}' not allowed for action '{$action}'."];
            }
            break;

        default:
            return ['success' => false, 'error' => "Invalid action '{$action}' for send_mail entity."];
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

# Email Sending Architecture

This document outlines the centralized email sending architecture for the application, which leverages user-specific SMTP settings for all outgoing emails.

## 1. Overview

The new architecture is designed to be robust, maintainable, and secure. It replaces a scattered and inconsistent approach to email sending with a single, centralized service. All emails, including system notifications, user invitations, and password resets, are now sent through the `EmailService` class.

The core principle is that every email is sent on behalf of a specific user, using that user's own configured SMTP server credentials. This improves deliverability and accountability.

## 2. Core Component: `EmailService`

The heart of the new system is the `EmailService` class.

- **Location:** `public/services/EmailService.php`
- **Dependency:** `PHPMailer` (or another robust mailer library)
- **Initialization:** `new EmailService(PDO $db)`

### API Documentation

#### `public function send(int $userId, string $recipient, string $subject, string $htmlBody): array`

This is the primary method for sending all emails.

- **Parameters:**

  - `int $userId`: The ID of the user whose SMTP settings should be used for sending the email. This is a **mandatory** parameter.
  - `string $recipient`: The email address of the recipient.
  - `string $subject`: The subject line of the email.
  - `string $htmlBody`: The HTML content of the email body.

- **Returns:** An associative array with two keys:

  - `'success'` (boolean): `true` if the email was sent successfully, `false` otherwise.
  - `'message'` (string): A descriptive message indicating the result of the operation (e.g., "Email sent successfully." or an error message from the mailer).

- **Workflow:**
  1. Fetches the SMTP settings for the given `$userId` from the `user_email_settings` table.
  2. If settings are not found, it returns a failure message.
  3. Configures a new `PHPMailer` instance with the user's SMTP host, port, username, and password.
  4. Handles SMTP authentication and encryption (TLS/SSL).
  5. Sends the email and returns the result.

## 3. Database Schema for SMTP Settings

User-specific SMTP settings are stored in the `user_email_settings` table.

- **Table Name:** `user_email_settings`
- **Columns:**
  - `id` (INT, Primary Key, Auto-Increment)
  - `user_id` (INT, Foreign Key to `users.id`): The user these settings belong to.
  - `smtp_host` (VARCHAR): The SMTP server host (e.g., `smtp.example.com`).
  - `smtp_port` (INT): The SMTP port (e.g., 587 for TLS, 465 for SSL).
  - `smtp_user` (VARCHAR): The username for the SMTP account.
  - `smtp_pass` (VARCHAR): The password for the SMTP account (should be encrypted in a real production environment).
  - `smtp_secure` (VARCHAR): The encryption method (`tls`, `ssl`, or `none`).

## 4. Refactored Files

The following files were modified to integrate with the new `EmailService`:

- **`public/api_handlers/send_mail.php`**: The main API handler for sending emails. It now instantiates `EmailService` and calls its `send()` method, using the logged-in user's ID from the session.
- **`public/api_handlers/invitations.php`**: The `resend` action was updated to use the `handle_send_mail` function, which in turn uses the `EmailService`.
- **`public/api_handlers/invite_process.php`**: This file required no changes, as it already used `handle_send_mail`, which was refactored.
- **`public/api_handlers/email_functions.php`**: The `send_password_reset_email` function was refactored to use the `EmailService`, and a redundant `handle_send_mail` function was removed.

## 5. Debugging Email Errors

- **Error Logging:** The `EmailService` returns detailed error messages from the underlying mailer library (PHPMailer). These messages are logged in the relevant API handlers (e.g., `send_mail.php`, `invitations.php`).
- **Check Logs:** Check the PHP error logs for messages prefixed with "Email send failed" or "Password reset email failed".
- **Common Issues:**
  - **Incorrect Credentials:** The most common issue will be incorrect SMTP host, port, username, or password in the `user_email_settings` table.
  - **Firewall Issues:** Ensure the web server can make outbound connections on the specified SMTP port.
  - **Two-Factor Authentication (2FA):** If the user's email account has 2FA enabled, they may need to generate an "App Password" to use for SMTP authentication.

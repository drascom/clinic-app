# Email System Workflow

This document outlines the current email system's architecture, user workflow, and key functions involved in sending and receiving emails.

## 1. User Workflow: Checking and Viewing Emails

1.  **Accessing the Email Dashboard:** The user navigates to the email dashboard, typically via `public/app-email/index.php`.
2.  **Initial Load & Display:**
    - Upon page load, the JavaScript in `public/app-email/index.php` calls `fetchUserEmailSettings()` to retrieve user-specific SMTP/IMAP settings.
    - It then calls `fetchAndDisplayConversations('inbox')` to list email senders from the local database.
    - **Function:** `public/api_handlers/emails.php::handle_emails('list_senders')`
    - **Function:** `public/api_handlers/email_functions.php::get_emails_from_db()`
    - **Function:** `public/api_handlers/email_functions.php::group_emails_by_sender()`
3.  **Checking for New Emails:**
    - The user clicks the "Check" button (`#check-emails-btn`).
    - This triggers a call to `checkNewEmails()` in `public/app-email/index.php`.
    - An `EventSource` is used to connect to `public/api.php?entity=emails&action=check_new_emails` for real-time progress updates.
    - **Function:** `public/api_handlers/emails.php::handle_emails('check_new_emails')`
    - **Function:** `public/api_handlers/email_functions.php::get_last_email_date()`
    - **Function:** `public/api_handlers/email_functions.php::fetch_new_emails()`
    - **Function:** `public/api_handlers/email_functions.php::get_email_body()`
    - **Function:** `public/api_handlers/email_functions.php::decode_email_part()`
    - **Function:** `public/api_handlers/email_functions.php::get_email_attachments()`
    - **Function:** `public/api_handlers/email_functions.php::store_emails()`
4.  **Viewing a Conversation:**
    - The user clicks on a sender in the email list.
    - This triggers `loadConversation(senderEmail, targetElement)` in `public/app-email/index.php`.
    - First, `mark_as_read` API call is made.
    - **Function:** `public/api_handlers/emails.php::handle_emails('mark_as_read')`
    - **Function:** `public/api_handlers/email_functions.php::mark_conversation_as_read()`
    - Then, `get_conversation` API call is made.
    - **Function:** `public/api_handlers/emails.php::handle_emails('get_conversation')`
    - **Function:** `public/api_handlers/email_functions.php::get_emails_from_db()`
    - **Function:** `public/api_handlers/email_functions.php::group_emails_by_sender()`
5.  **Viewing/Downloading Attachments:**
    - When an attachment link is clicked, a full-screen modal opens.
    - The modal provides options to "View" the attachment inline (for supported types like images and PDFs) or "Download" it directly.
    - This process is handled by `public/download.php`, which securely serves the file from a non-public `uploads` directory.

## 2. User Workflow: Sending Emails

1.  **Composing an Email:** (Assumed to be a separate UI, e.g., a modal or a dedicated page)
    - The user fills in recipient, subject, and body.
2.  **Saving/Sending Email:**
    - When the user saves a draft or sends an email, an API call is made.
    - **Function:** `public/api_handlers/emails.php::handle_emails('save_email')`
    - **Function:** `public/api_handlers/email_functions.php::save_email()`
    - If sending, the `EmailService` is invoked.
    - **Function:** `public/services/EmailService.php::send()`
    - **Function:** `public/api_handlers/email_functions.php::get_user_email_settings()`

## 3. Core Components and Functions

- **`public/app-email/index.php`**: Frontend UI for email dashboard.
  - `fetchUserEmailSettings()`: Fetches user's email configuration.
  - `fetchAndDisplayConversations(folder)`: Renders the list of email senders/conversations.
  - `checkNewEmails()`: Initiates fetching new emails from the IMAP server.
  - `loadConversation(senderEmail, targetElement)`: Displays the full content of a selected email conversation.
- **`public/download.php`**: Securely handles on-demand attachment viewing and downloading.
- **`public/api_handlers/emails.php`**: API handler for all email-related actions.
  - `handle_emails($action, $method, $db, $input)`: Main entry point, dispatches to specific actions.
  - Actions: `check_new_emails`, `list_senders`, `get_conversation`, `mark_as_read`, `delete_conversation`, `deactivate_conversation`, `list_deactivated_senders`, `get_draft`, `save_email`.
- **`public/api_handlers/email_functions.php`**: Contains core email logic (IMAP interaction, database operations).
  - `decode_email_part($part, $encoding)`: Decodes email body parts based on encoding.
  - `get_email_body($inbox, $email_number, $structure)`: Extracts the main body of an email.
  - `get_mime_type_from_number($type_number)`: Helper to convert IMAP content type numbers to standard MIME strings.
  - `get_email_attachments($inbox, $email_uid, $structure)`: Extracts attachment metadata (filename, size, MIME type, IMAP UID, part index) without downloading the file content.
  - `get_last_email_date($db, $user_id)`: Retrieves the timestamp of the latest email stored locally.
  - `store_emails($db, $emails)`: Inserts fetched emails and their attachments into the database.
  - `fetch_new_emails($db, $user_id, $last_email_date)`: Connects to IMAP, fetches new emails, and processes them.
  - `fetch_junk_emails($db, $user_id)`: Fetches emails from the Junk folder (similar to `fetch_new_emails`).
  - `deactivate_emails_by_sender($db, $sender_email, $user_id)`: Marks emails from a sender as inactive.
  - `get_emails_from_db($db, $user_id, $is_active = null)`: Retrieves emails from the local database.
  - `group_emails_by_sender($emails)`: Organizes emails into conversations.
  - `mark_conversation_as_read($db, $sender_email, $user_id)`: Marks emails in a conversation as read.
  - `delete_emails_by_sender($db, $sender_email, $user_id)`: Deletes emails from the database and IMAP server.
  - `get_user_email_settings($db, $user_id)`: Retrieves user-specific IMAP/SMTP settings from the database.
  - `get_draft_by_id($db, $draft_id, $user_id)`: Retrieves a specific email draft.
  - `save_email($db, $data)`: Saves a new email or updates an existing draft in the database.
- **`public/services/EmailService.php`**: Handles sending emails via SMTP.
  - `send(int $userId, string $recipient, string $subject, string $htmlBody)`: Core function to send an email using PHPMailer.
  - `sendGenericEmail(...)`, `sendPasswordResetEmail(...)`, `sendInvitationEmail(...)`: Specific email sending wrappers.
- **`public/includes/db.php`**: Database connection.
- **`public/assets/js/api-helper.js`**: Provides `apiRequest()` for standardized API calls.
- **`public/services/LogService.php`**: Handles application logging.

## 4. Database Tables

- `emails`: Stores fetched emails.
- `email_attachments`: Stores metadata for email attachments, including `email_uid` and `part_index` for on-demand fetching. The `file_path` is only populated after the file has been downloaded for the first time.
- `user_email_settings`: Stores user-specific IMAP and SMTP configuration.

# Email Functionality Refactor Checklist

This document outlines the tasks required to refactor the application's email functionality to use a centralized `EmailService` with user-configured SMTP settings.

- [ ] **1. Create a Centralized `EmailService` Class**

  - [ ] Create a new `EmailService` class in a suitable shared location (e.g., `public/services/EmailService.php`).
  - [ ] Implement a primary `send()` method to handle all email sending operations.
  - [ ] Fetch user-specific SMTP settings from the database.
  - [ ] Configure a mailer instance (e.g., PHPMailer) with user credentials.
  - [ ] Implement robust error handling and logging.

- [ ] **2. Refactor Core Email Sending Handlers**

  - [ ] Modify `public/emailapp/index.php`.
  - [ ] Modify `public/api_handlers/emails.php`.
  - [ ] Replace direct email logic with calls to the new `EmailService`.

- [ ] **3. Refactor the Invitation System**

  - [ ] Identify all files responsible for sending user invitations (e.g., `public/api_handlers/invite_process.php`, `public/api_handlers/invitations.php`).
  - [ ] Refactor these files to use the `EmailService`.

- [ ] **4. Consolidate and Clean Up**

  - [ ] Audit `public/api_handlers/` for other email-related scripts (e.g., `public/api_handlers/send_mail.php`).
  - [ ] Consolidate their functionality into the `EmailService`.

- [ ] **5. Create Technical Documentation**
  - [ ] Create a new Markdown file at `doc/email_architecture.md`.
  - [ ] Document the new architecture, `EmailService` API, database schema, and refactored files.
  - [ ] Provide a guide for debugging email sending errors.

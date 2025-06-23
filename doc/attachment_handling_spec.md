# Technical Specification: Email Attachment Handling System

## 1. Overview

This document outlines the technical design for a new email attachment handling system. The goal is to replace the current process of automatically downloading all attachments to a user's local machine. The new system will stream attachments directly from the mail server to a secure server-side storage location and serve them to the user on-demand via a dedicated API endpoint.

## 2. System Architecture Changes

### 2.1. Email Ingestion and Storage

The email fetching process will be modified to prevent direct download of attachments to the application server's local file system in a publicly accessible way.

1.  **Server-Side Storage:** A secure, non-public directory will be designated for storing all email attachments.

    - **Proposed Location:** `/storage/attachments/` (This path is outside the webroot `/public`). The directory structure within will be organized by `user_id` and `email_id` to prevent filename collisions, e.g., `/storage/attachments/{user_id}/{email_id}/{attachment_filename}`.

2.  **Attachment Processing (`public/api_handlers/email_functions.php`)**
    - The `fetch_new_emails()` function will orchestrate the process.
    - The `get_email_attachments()` function will be modified. Instead of saving the file to a local path that is directly served, it will:
      - Read the attachment content from the IMAP stream.
      - Save the attachment to the secure server-side storage path (`/storage/attachments/{user_id}/{email_id}/`).
      - Return the storage path, filename, MIME type, and size.
    - The `store_emails()` function will save this information to the `email_attachments` database table.

### 2.2. Database Schema

The `email_attachments` table will be updated to store the necessary information.

**Table: `email_attachments`**

| Column Name    | Data Type      | Description                                                                                             |
| -------------- | -------------- | ------------------------------------------------------------------------------------------------------- |
| `id`           | `INT` (PK)     | Primary key.                                                                                            |
| `email_id`     | `INT` (FK)     | Foreign key to the `emails` table.                                                                      |
| `filename`     | `VARCHAR(255)` | The original filename of the attachment.                                                                |
| `filesize`     | `INT`          | The size of the attachment in bytes.                                                                    |
| `mime_type`    | `VARCHAR(255)` | The MIME type of the attachment (e.g., `image/png`, `application/pdf`).                                 |
| `storage_path` | `VARCHAR(512)` | The path to the attachment in the secure server-side storage (e.g., `{user_id}/{email_id}/{filename}`). |
| `created_at`   | `TIMESTAMP`    | Timestamp of when the record was created.                                                               |

_Action: A migration script will be needed to add the `storage_path` column and potentially rename the existing `file_path` column._

### 2.3. User Interface (`public/app-email/index.php`)

When displaying an email conversation, the frontend will render attachments as links pointing to the new attachment serving API.

- The `loadConversation()` JavaScript function will receive the attachment details (id, filename, etc.) from the `get_conversation` API response.
- For each attachment, it will generate a link:
  ```html
  <a href="/serve-attachment.php?id={attachment_id}" target="_blank"
    >{attachment_filename}</a
  >
  ```
- The `target="_blank"` attribute will ensure the attachment opens in a new tab, which is ideal for inline viewing.

### 2.4. Attachment Serving API

A new, dedicated API endpoint will be created to serve attachments securely.

- **Endpoint:** `public/serve-attachment.php`
- **Request Method:** `GET`
- **Parameter:** `id` (the `id` of the attachment from the `email_attachments` table).

**Workflow:**

1.  The script receives the attachment `id`.
2.  It authenticates the user to ensure they have permission to view the email associated with the attachment.
3.  It queries the `email_attachments` table to retrieve the `storage_path`, `filename`, and `mime_type`.
4.  It constructs the full, absolute path to the file in the `/storage/attachments/` directory.
5.  It checks if the file exists.
6.  It sets the appropriate HTTP headers:
    - `Content-Type: {mime_type}`
    - `Content-Length: {filesize}`
    - **Conditional `Content-Disposition` Header:**
      - If `mime_type` is `image/*` or `application/pdf`, set:
        `Content-Disposition: inline; filename="{filename}"`
      - For all other `mime_type` values (e.g., `application/zip`), set:
        `Content-Disposition: attachment; filename="{filename}"`
7.  The script reads the file from the secure storage and streams it to the client.

## 3. Implementation Plan

1.  **Create Migration:** Write and apply a SQL migration script to update the `email_attachments` table schema.
2.  **Update Backend Logic:**
    - Modify `get_email_attachments()` in `public/api_handlers/email_functions.php` to save files to the new secure storage location.
    - Update `store_emails()` to save the `storage_path`.
3.  **Create Serving Endpoint:**
    - Create the `public/serve-attachment.php` file with the logic described in section 2.4.
4.  **Update Frontend:**
    - Modify the `loadConversation()` function in `public/app-email/index.php` to generate links pointing to `serve-attachment.php`.
5.  **Testing:**
    - Test email fetching with various attachment types (images, PDFs, ZIP files).
    - Verify that attachments are stored correctly in the `/storage/attachments` directory.
    - Verify that clicking attachment links results in the correct behavior (inline view vs. download).
    - Test security to ensure users cannot access attachments from other users' emails.

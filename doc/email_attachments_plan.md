# Plan for Email Attachment Handling

This document outlines the detailed plan to modify the email system to fetch, store, display, and allow downloading of email attachments.

## Goal
Modify the email system to fetch, store, display, and allow downloading of email attachments.

## Phase 1: Database Schema Update

1.  **Create a new table for attachments:** A new table, `email_attachments`, will be created to store metadata about attachments (e.g., `email_id`, `filename`, `file_path`, `mime_type`, `size`). The `file_path` will point to where the actual attachment file is stored on the server.
    *   **Action:** Create a new SQL migration file (e.g., `db/migrations/create_email_attachments_table.sql`) and define the schema.
    *   **Tool:** `write_to_file`

## Phase 2: Backend (PHP) Modifications

1.  **Modify `fetch_new_emails` in `public/includes/email.php` to extract attachments:**
    *   Iterate through `imap_fetchstructure` parts.
    *   Identify attachment parts (e.g., `type != TYPETEXT` and `disposition == 'attachment'`).
    *   Extract attachment details (filename, MIME type, content).
    *   Save attachment content to a designated server directory (e.g., `public/uploads/email_attachments/`).
    *   Store attachment metadata (filename, file path, MIME type, size) along with the `email_id` in the `email_attachments` table.
    *   **Action:** Add logic to `fetch_new_emails` to process and save attachments.
    *   **Tool:** `apply_diff`
2.  **Modify `store_emails` in `public/includes/email.php` to handle attachment storage:**
    *   After inserting the email into the `emails` table, retrieve the `email_id` (last inserted ID).
    *   Loop through any extracted attachments for that email and insert their metadata into the `email_attachments` table, linking them to the `email_id`.
    *   **Action:** Update `store_emails` to save attachment metadata.
    *   **Tool:** `apply_diff`
3.  **Modify `get_emails_from_db` in `public/includes/email.php` to retrieve attachments:**
    *   When fetching email details, also query the `email_attachments` table to get all attachments associated with each email.
    *   Include attachment data (e.g., filename, file path, size) in the returned email array.
    *   **Action:** Update `get_emails_from_db` to fetch and include attachment data.
    *   **Tool:** `apply_diff`
4.  **Create a new API action for downloading attachments:**
    *   Add a new `case` in `handle_emails` in `public/api_handlers/emails.php` (e.g., `download_attachment`).
    *   This action will take an `attachment_id` or `file_path` as input.
    *   It will securely serve the file, ensuring proper headers (Content-Type, Content-Disposition) for download.
    *   **Action:** Add `download_attachment` action to `handle_emails`.
    *   **Tool:** `apply_diff`

## Phase 3: Frontend (JavaScript/HTML) Modifications

1.  **Update `loadConversation` in `public/emailapp/index.php` to display attachments:**
    *   Parse the `attachments` array (if present) in the email data.
    *   For each attachment, create a link or button to download it.
    *   The link's `href` will point to the new `download_attachment` API endpoint.
    *   **Action:** Modify the `accordion-body` content to display attachment links.
    *   **Tool:** `apply_diff`
2.  **Create a directory for storing email attachments:**
    *   **Action:** Create the `public/uploads/email_attachments` directory.
    *   **Tool:** `execute_command` (mkdir)

## Mermaid Diagram for the Plan

```mermaid
graph TD
    A[Start: User wants to see/download attachments] --> B{Current System Analysis};
    B --> C{Attachments not fetched/stored};
    C --> D[Phase 1: Database Schema Update];
    D --> D1[Create email_attachments table];
    D1 --> E[Phase 2: Backend (PHP) Modifications];
    E --> E1[Modify fetch_new_emails to extract & save attachments];
    E1 --> E2[Modify store_emails to save attachment metadata];
    E2 --> E3[Modify get_emails_from_db to retrieve attachments];
    E3 --> E4[Add download_attachment API action];
    E4 --> F[Phase 3: Frontend (JS/HTML) Modifications];
    F --> F1[Update loadConversation to display attachment links];
    F1 --> F2[Create public/uploads/email_attachments directory];
    F2 --> G[End: Attachments visible and downloadable];
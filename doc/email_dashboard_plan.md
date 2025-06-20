# Detailed Plan for Email Dashboard Panel

**Goal 1: Setup Emailer Directory and Initial HTML Structure**
*   **Action**: Create the `emailer` directory and an `index.php` file within it.
*   **Action**: Design the basic two-column HTML layout in `emailer/index.php` using Bootstrap, similar to `public/patient/patients.php`. This will include a header, a search bar, a left panel for email senders/addresses, and a right panel for email content.
*   **Rationale**: Establish the foundational structure for the new dashboard.

**Goal 2: Implement IMAP Integration in `public/includes/email.php`**
*   **Action**: Add new functions to `public/includes/email.php` to handle IMAP connection and email fetching. This will involve:
    *   A function to connect to the IMAP server using settings from `secrets/.env`.
    *   A function to fetch emails from a specific mailbox (e.g., 'INBOX'), retrieving sender, recipient, subject, date, and body.
    *   A function to group emails into conversations (initially by sender email and subject, with potential for refinement using email headers like `In-Reply-To` or `References`).
*   **Considerations**: Error handling for IMAP connection and email parsing (HTML vs. plain text).
*   **Rationale**: Enable the application to retrieve email data from the IMAP server.

**Goal 3: Create New API Handler for Emails (`public/api_handlers/emails.php`)**
*   **Action**: Create a new PHP file `public/api_handlers/emails.php` to serve as the API endpoint for email-related requests.
*   **Action**: Implement `handle_emails()` function within this file, which will call the IMAP functions from `public/includes/email.php` to:
    *   List unique senders/conversations.
    *   Retrieve all emails for a selected conversation.
*   **Rationale**: Adhere to the existing API architecture and centralize email data access for the frontend.

**Goal 4: Populate Left Column (Sender List) and Implement Client-Side Search**
*   **Action**: In `emailer/index.php`, use JavaScript to call the new API endpoint (`/api.php?entity=emails&action=list_senders`) to fetch the list of unique senders/conversations.
*   **Action**: Dynamically populate the left column with a clickable list of sender names and email addresses.
*   **Action**: Implement client-side search functionality for the left column, similar to the `patients.php` search bar, to filter sender names/addresses.
*   **Rationale**: Provide the user with a navigable list of email conversations and a way to quickly find specific senders.

**Goal 5: Populate Right Column (Email Content with Accordions)**
*   **Action**: When a sender/conversation is selected from the left column, use JavaScript to call the API endpoint (`/api.php?entity=emails&action=get_conversation`) to fetch all emails related to that conversation.
*   **Action**: Dynamically populate the right column with the fetched emails, ordered from newest to oldest.
*   **Action**: Use Bootstrap Accordions for each email in the conversation. The `accordion-header` will display the email subject, and the `accordion-body` will show the mail content.
*   **Rationale**: Display the full email conversation in a structured and user-friendly manner.

**Mermaid Diagram:**

```mermaid
graph TD
    A[User Request: Email Dashboard] --> B(emailer/index.php)
    B --> C{HTML Structure: 2 Columns}
    C --> D[Left Column: Sender List + Search]
    C --> E[Right Column: Email Content (Accordions)]

    D --> F(JavaScript: Fetch Senders)
    F --> G[API Endpoint: /api.php?entity=emails&action=list_senders]
    G --> H(public/api_handlers/emails.php)
    H --> I(public/includes/email.php: IMAP Functions)
    I --> J[IMAP Server: Fetch Email Headers]

    E --> K(JavaScript: Fetch Conversation)
    K --> L[API Endpoint: /api.php?entity=emails&action=get_conversation]
    L --> H
    H --> I
    I --> J[IMAP Server: Fetch Full Email Content]

    J --> M[secrets/.env: Mail Settings]
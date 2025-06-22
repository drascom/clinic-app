# Private Messaging Feature Implementation Plan

This document outlines the plan to architect and implement a real-time private messaging feature.

## Phase 1: Backend API Enhancements

The first phase focuses on extending the `messages` API handler to support the required frontend functionality.

1.  **Implement `list_senders` Action**: In `public/api_handlers/messages.php`, a new `list_senders` action will be added. This action will be responsible for querying the database to retrieve a unique list of conversations for the currently logged-in user. The query will also fetch the name and email of the other participant, the timestamp of the most recent message, and a count of unread messages for each conversation.

2.  **Implement `get_conversation` Action**: A `get_conversation` action will be created to fetch the full message history between the current user and a selected correspondent. The messages will be ordered chronologically in descending order (newest first).

3.  **Implement `mark_conversation_as_read` Action**: To handle read receipts, a `mark_conversation_as_read` action will be implemented. This action will update the `is_read` flag to `true` for all messages within a specific conversation for the current user.

## Phase 2: Frontend Implementation

The second phase involves creating the user interface and connecting it to the new API endpoints.

1.  **Create New File**: A new file will be created at `public/app-msg/user-msg.php` to house the new messaging feature, leaving the existing `index.php` untouched.

2.  **Structure the UI**: The UI in `public/app-msg/user-msg.php` will be designed to mirror the two-column layout of the existing email module (`public/app-email/index.php`) to maintain design consistency across the application. The left column will display the list of conversations, and the right column will display the selected conversation's messages.

3.  **Populate the Sender List**: Upon page load, a JavaScript function will call the `list_senders` API endpoint using the global `apiRequest` helper. The returned data will be used to dynamically render the list of conversations in the left column.

4.  **Implement Client-Side Search**: A search input field will be added to the top of the sender list. A JavaScript event listener will filter the conversations in real-time based on user input, referencing the implementation in `public/patient/patients.php`.

5.  **Display Conversations**: When a user clicks on a conversation in the left column, an API call to the `get_conversation` endpoint will be triggered. The fetched messages will be rendered in the right column, styled to resemble a modern chat application. Immediately after displaying the messages, another API call will be made to the `mark_conversation_as_read` endpoint to update the read status of the messages.

## Architecture Diagram

```mermaid
graph TD
    subgraph Frontend (public/app-msg/user-msg.php)
        A[Sender List] -- Click --> B{Fetch Conversation};
        C[Search Bar] -- Input --> D{Filter Senders};
        B -- Calls API --> E[api.php];
    end

    subgraph Backend (api.php)
        E -- Routes to --> F[messages.php handler];
    end

    subgraph API Handler (messages.php)
        F -- action: list_senders --> G[Fetch Senders from DB];
        F -- action: get_conversation --> H[Fetch Messages from DB];
        F -- action: mark_conversation_as_read --> I[Update is_read in DB];
    end

    subgraph Database
        G -- SELECT --> J[(messages table)];
        H -- SELECT --> J;
        I -- UPDATE --> J;
    end

    A -- Populated by --> G;
    B -- Populates --> K[Message View];
    K -- Populated by --> H;
```

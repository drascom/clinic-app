# Message Reaction Feature Implementation Plan

This document outlines the plan to implement a robust, real-time private messaging feature with a message reaction system.

## Phase 1: Database Schema Update

1.  **Create `message_reactions` Table**: A new table named `message_reactions` will be created in the database. This table will store reaction data and will include the following fields:
    - `id` (PRIMARY KEY, INT, AUTO_INCREMENT)
    - `message_id` (INT, FOREIGN KEY to `messages` table)
    - `user_id` (INT, FOREIGN KEY to `users` table, representing the reactor)
    - `emoji_code` (VARCHAR, to store the emoji character or a short code)
    - `timestamp` (DATETIME, default CURRENT_TIMESTAMP)
    - A UNIQUE constraint on `(message_id, user_id)` will be added to ensure a user can only react once per message.

## Phase 2: Backend API Enhancements (`public/api_handlers/messages.php`)

The existing `messages` API handler will be extended to manage reactions.

1.  **Implement `add_reaction` Action**: This action will handle adding a new reaction to a message. It will take `message_id`, `user_id`, and `emoji_code` as input. It will insert a new record into the `message_reactions` table.

2.  **Implement `update_reaction` Action**: This action will allow users to change their existing reaction. It will take `message_id`, `user_id`, and `new_emoji_code` as input and update the `emoji_code` for the corresponding record in `message_reactions`.

3.  **Implement `delete_reaction` Action**: This action will remove a user's reaction from a message. It will take `message_id` and `user_id` as input and delete the corresponding record from `message_reactions`.

4.  **Modify `get_conversation` Action**: The `get_conversation` action will be updated to also fetch all reactions associated with each message in the conversation. This will involve a `LEFT JOIN` with the `message_reactions` table and grouping/aggregating reaction data for each message.

## Phase 3: Frontend Implementation (`public/app-msg/user-msg.php`)

This phase involves modifying the chat interface to support reactions.

1.  **Display Reaction Trigger**: For each message displayed in the right column, a small, clickable icon (e.g., a smiley face or a plus icon) will be added. This icon will serve as the reaction trigger.

2.  **Implement Reaction Popover**: When the reaction trigger is clicked, a small popover (using Bootstrap's Popover component or a custom solution) will appear. This popover will contain a predefined set of reaction emojis (e.g., 👍, ❤️, 😂, 😮, 😢, 😡).

3.  **Handle Emoji Selection**:

    - When an emoji is selected from the popover, the popover will close.
    - A JavaScript function will send an `apiRequest` to the `add_reaction` or `update_reaction` endpoint, passing the `message_id`, `current_user_id`, and the `emoji_code`.
    - The UI will be updated asynchronously to display the chosen reaction below the message.

4.  **Display Existing Reactions**:

    - When `get_conversation` returns messages with associated reactions, these reactions will be parsed and displayed visually below their respective messages.
    - Reactions will be grouped by emoji, showing a count for each emoji and potentially the avatars of users who reacted (if feasible with current user data).

5.  **Handle Changing/Removing Reactions**:

    - If a user clicks on their own existing reaction, it should trigger the `delete_reaction` API call and update the UI to remove their reaction.
    - If a user selects a different emoji, it should trigger the `update_reaction` API call.

6.  **Real-time Updates (Consideration)**: While the request mentions "real-time," a full WebSocket implementation is out of scope for this task given the current architecture. The "real-time" aspect will be simulated by re-fetching the conversation after a reaction is added/updated/deleted, or by directly manipulating the DOM for immediate visual feedback, followed by a background re-fetch to ensure data consistency.

## Architecture Diagram (with Reactions)

```mermaid
graph TD
    subgraph Frontend (public/app-msg/user-msg.php)
        A[Sender List] -- Click --> B{Fetch Conversation};
        C[Search Bar] -- Input --> D{Filter Senders};
        B -- Calls API --> E[api.php];
        K[Message View] -- Click Reaction Trigger --> L{Show Reaction Popover};
        L -- Select Emoji --> M{Send Reaction to API};
        M -- Calls API --> E;
    end

    subgraph Backend (api.php)
        E -- Routes to --> F[messages.php handler];
    end

    subgraph API Handler (messages.php)
        F -- action: list_senders --> G[Fetch Senders from DB];
        F -- action: get_conversation --> H[Fetch Messages & Reactions from DB];
        F -- action: mark_conversation_as_read --> I[Update is_read in DB];
        F -- action: add_reaction --> N[Add Reaction to DB];
        F -- action: update_reaction --> O[Update Reaction in DB];
        F -- action: delete_reaction --> P[Delete Reaction from DB];
    end

    subgraph Database
        G -- SELECT --> J[(messages table)];
        H -- SELECT --> J;
        H -- SELECT --> Q[(message_reactions table)];
        I -- UPDATE --> J;
        N -- INSERT --> Q;
        O -- UPDATE --> Q;
        P -- DELETE --> Q;
    end

    A -- Populated by --> G;
    B -- Populates --> K;
    K -- Displays --> H;
    K -- Updates based on --> N, O, P;
```

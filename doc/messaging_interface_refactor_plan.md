# Messaging Interface Refactor Plan

## Objective

Refactor and enhance the messaging interface located at `/public/app-msg/index.php` to implement a two-column layout for improved patient communication management. The left column must display a dynamic list of patients, each identified by their name, sorted in descending order based on the `message_date` of their most recent conversation. Upon selecting a patient from the left column, the right column will populate with all associated conversations for that patient. These conversations must be logically grouped by `related_table` and `related_id` to represent distinct communication threads or topics, with individual messages within each group sorted by `message_date` in descending order.

## Detailed Plan

### Phase 1: Backend (PHP - `/public/api_handlers/messages.php`)

1.  **Create `list_patients_with_messages` API Action:**

    - **Purpose:** To fetch a list of patients who have messages associated with them (where `related_table` is 'patients' and `related_id` is the patient's ID), along with the timestamp of their most recent message.
    - **Query Logic:**
      - Select `p.id AS patient_id`, `p.name AS patient_name`, `p.email AS patient_email`, `p.updated_by AS patient_responsible_user_id`.
      - Find the `MAX(m.timestamp)` as `latest_message_timestamp` for each patient where `m.related_table = 'patients'` and `m.related_id = p.id`.
      - Also, include messages where `m.sender_id` or `m.receiver_id` matches `p.updated_by` (if `p.updated_by` is not NULL). This will require a `LEFT JOIN` with the `users` table to get the `updated_by` user's ID.
      - Group by `p.id`.
      - Order the results by `latest_message_timestamp` in descending order.
    - **Return:** `['success' => true, 'patients' => [...]]`

2.  **Create `get_patient_conversation` API Action:**
    - **Purpose:** To retrieve all messages related to a specific `patient_id`, grouped by `related_table` and `related_id`, with messages within each group sorted by `timestamp` in descending order.
    - **Input:** `patient_id` and `responsible_user_id`.
    - **Query Logic:**
      - Select `m.*`, `s.username AS sender_name`, `s.email AS sender_email`, `r.username AS receiver_name`, `r.email AS receiver_email`, `GROUP_CONCAT(mr.emoji_code) as reactions`.
      - Filter messages where:
        - (`m.related_table = 'patients'` AND `m.related_id = :patient_id`)
        - OR (`m.sender_id = :responsible_user_id` AND `m.receiver_id = :current_user_id`)
        - OR (`m.sender_id = :current_user_id` AND `m.receiver_id = :responsible_user_id`)
      - Order the overall result by `m.timestamp DESC`.
      - Use the existing `get_entity_label` helper to enrich messages with `related_entity_label`.
    - **Return:** `['success' => true, 'conversation' => [...]]`

### Phase 2: Frontend (JavaScript - `/public/app-msg/index.php`)

1.  **Update `fetchAndDisplayContacts` Function:**

    - Change the `apiRequest` call to use the new `list_patients_with_messages` action.
    - `const response = await apiRequest('messages', 'list_patients_with_messages', { user_id: currentUserId });`
    - Adjust the `allContacts` variable to store the patient data.

2.  **Update `renderContacts` Function:**

    - Modify the loop to iterate over the `patients` array from the API response.
    - Update `listItem.dataset.participantId` to `contact.patient_id`.
    - Update `listItem.dataset.participantName` to `contact.patient_name`.
    - Add `listItem.dataset.responsibleUserId` to `contact.patient_responsible_user_id`.
    - Update the `innerHTML` to display `contact.patient_name` and potentially a snippet of the `latest_message` (if the new API provides it, otherwise remove this part for now).
    - The `unread_count` logic will need to be adapted if the new API provides it per patient.

3.  **Update `peopleList` Click Handler:**

    - The `li.dataset.participantId` will now correctly hold the `patient_id`.
    - Retrieve `responsibleUserId` from `li.dataset.responsibleUserId`.
    - The `loadConversation` call will pass `patient_id` and `responsibleUserId`.

4.  **Refine `loadConversation` Function:**

    - Change the `apiRequest` call to use the new `get_patient_conversation` action.
    - `const response = await apiRequest('messages', 'get_patient_conversation', { user_id: currentUserId, patient_id: patientId, responsible_user_id: responsibleUserId });`
    - **Crucially, adapt the message grouping logic (lines 241-283):**
      - Since the API will return messages sorted `DESC`, the grouping logic needs to build groups in reverse chronological order.
      - When `currentGroup` is defined, new messages should be _prepended_ to `currentGroup.messages` to maintain descending order within the group.
      - The `createConversationBubble` function will then render these groups, and `createMessageElement` will render individual messages within them.

5.  **Update Chatbox (Sending Messages):**
    - When a message is sent, the `related_table` should be `'patients'` and `related_id` should be the `activePatientId` (which is now the `patient_id`).
    - The `receiver_id` in the `send_message` API call will be the `responsibleUserId` obtained from the selected patient.

### Visual Representation (Mermaid Diagram)

```mermaid
graph TD
    A[User opens /public/app-msg/index.php] --> B{Frontend: fetchAndDisplayContacts()};
    B --> C[API Call: messages.list_patients_with_messages];
    C --> D{Backend: messages.php};
    D --> E[DB Query: Select patients with latest message timestamp, ordered DESC];
    E --> F[Return: List of patients with latest message info];
    F --> G{Frontend: renderContacts()};
    G --> H[Display patient list in left column];

    H --> I[User selects a patient from list];
    I --> J{Frontend: loadConversation(patientId, patientName, responsibleUserId)};
    J --> K[API Call: messages.get_patient_conversation];
    K --> L{Backend: messages.php};
    L --> M[DB Query: Select messages for patient, grouped by related_table/id, ordered DESC];
    M --> N[Return: Grouped conversation data];
    N --> O{Frontend: Render conversation in right column, grouping messages};

    O --> P[User types message and sends];
    P --> Q[API Call: messages.send_message];
    Q --> R{Backend: messages.php};
    R --> S[DB Insert: New message with sender_id=currentUserId, receiver_id=responsibleUserId, related_table='patients', related_id=patientId];
    S --> T[Return: Success];
    T --> U[Frontend: Optimistic UI update, then refresh conversation];
```

<?php
// File: public/api_handlers/conversations.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../services/LogService.php';

function handle_conversations($action, $method, $db, $input)
{
    $logService = new LogService();
    $authenticated_user_id = $input['authenticated_user_id'] ?? 0;

    // Helper function for validation
    function validate_input($input, $fields, $logService, $action_name)
    {
        foreach ($fields as $field) {
            if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
                $logService->log('conversations_api', 'error', "Missing or empty required field: {$field} for {$action_name} action", ['input' => $input]);
                return ['success' => false, 'message' => "Missing or empty required field: {$field}"];
            }
        }
        return ['success' => true];
    }

    // Helper function to get entity label
    function get_entity_label($db, $table_name, $record_id, $logService)
    {
        $allowed_tables = [
            'patients' => 'name',
            'staff' => 'name',
            'users' => 'username',
            'appointments' => 'title',
            'rooms' => 'name',
            'agencies' => 'name',
            'procedures' => 'name',
            'surgeries' => 'title',
            'candidates' => 'name'
        ];

        if ($table_name === 'general') {
            return 'General Broadcast';
        }

        if (!isset($allowed_tables[$table_name])) {
            $logService->log('conversations_api', 'warning', "Attempted to get entity label for disallowed table: {$table_name}", ['table_name' => $table_name, 'record_id' => $record_id]);
            return null;
        }

        $label_column = $allowed_tables[$table_name];

        try {
            $stmt = $db->prepare("SELECT {$label_column} AS label FROM {$table_name} WHERE id = :record_id");
            $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
            $stmt->execute();
            $entity_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($entity_data) {
                $logService->log('conversations_api', 'debug', "Retrieved entity label for {$table_name}:{$record_id}", ['label' => $entity_data['label']]);
                return $entity_data['label'];
            } else {
                $logService->log('conversations_api', 'debug', 'Entity not found for label retrieval', ['table_name' => $table_name, 'record_id' => $record_id]);
                return null;
            }
        } catch (PDOException $e) {
            $logService->log('conversations_api', 'error', 'Database error retrieving entity label: ' . $e->getMessage(), ['table_name' => $table_name, 'record_id' => $record_id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    switch ($action) {
        case 'list':
            if (!isset($input['user_id'])) {
                $logService->log('conversations_api', 'error', "Missing required field: user_id for list action", ['input' => $input]);
                return ['success' => false, 'message' => 'Missing required field: user_id'];
            }

            $user_id = (int)$input['user_id'];
            $limit = isset($input['limit']) ? (int)$input['limit'] : 20;
            $offset = isset($input['offset']) ? (int)$input['offset'] : 0;

            // Authorization check: user_id must match authenticated_user_id
            if ($user_id !== $authenticated_user_id) {
                $logService->log('conversations_api', 'warning', 'Unauthorized attempt to list messages for different user_id', ['attempted_user_id' => $user_id, 'authenticated_user_id' => $authenticated_user_id]);
                return ['success' => false, 'message' => 'Unauthorized to list messages for this user.'];
            }

            try {
                $stmt = $db->prepare("SELECT * FROM messages WHERE sender_id = :user_id OR receiver_id = :user_id ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $logService->log('conversations_api', 'success', 'Messages listed successfully', ['user_id' => $user_id, 'count' => count($messages)]);
                return ['success' => true, 'messages' => $messages];
            } catch (PDOException $e) {
                $logService->log('conversations_api', 'error', 'Database error listing messages: ' . $e->getMessage(), ['user_id' => $user_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'mark-read':
            $validation_result = validate_input($input, ['message_id'], $logService, 'mark-read');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $message_id = (int)$input['message_id'];

            try {
                $db->beginTransaction();

                // Fetch message to check authorization
                $stmt_fetch = $db->prepare("SELECT receiver_id FROM messages WHERE id = :message_id");
                $stmt_fetch->bindParam(':message_id', $message_id, PDO::PARAM_INT);
                $stmt_fetch->execute();
                $message = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

                if (!$message) {
                    $db->rollBack();
                    $logService->log('conversations_api', 'warning', 'Message not found for mark-read', ['message_id' => $message_id]);
                    return ['success' => false, 'message' => 'Message not found.'];
                }

                // Authorization check: Only the receiver can mark a message as read
                if ($message['receiver_id'] !== $authenticated_user_id) {
                    $db->rollBack();
                    $logService->log('conversations_api', 'warning', 'Unauthorized attempt to mark message as read', ['message_id' => $message_id, 'authenticated_user_id' => $authenticated_user_id, 'receiver_id' => $message['receiver_id']]);
                    return ['success' => false, 'message' => 'Unauthorized to mark this message as read.'];
                }

                $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = :message_id");
                $stmt->bindParam(':message_id', $message_id, PDO::PARAM_INT);
                $stmt->execute();

                $db->commit();
                if ($stmt->rowCount() > 0) {
                    $logService->log('conversations_api', 'success', 'Message marked as read successfully', ['message_id' => $message_id]);
                    return ['success' => true, 'message' => 'Message marked as read successfully'];
                } else {
                    $logService->log('conversations_api', 'warning', 'Message not found or already marked as read', ['message_id' => $message_id]);
                    return ['success' => false, 'message' => 'Message not found or already marked as read'];
                }
            } catch (PDOException $e) {
                $db->rollBack();
                $logService->log('conversations_api', 'error', 'Database error marking message as read: ' . $e->getMessage(), ['message_id' => $message_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'by-entity':
            $validation_result = validate_input($input, ['table_name', 'record_id'], $logService, 'by-entity');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $table_name = $input['table_name'];
            $record_id = (int)$input['record_id'];
            $limit = isset($input['limit']) ? (int)$input['limit'] : 20;
            $offset = isset($input['offset']) ? (int)$input['offset'] : 0;

            try {
                $stmt = $db->prepare("
                    SELECT * FROM messages
                    WHERE json_valid(related_table)
                    AND EXISTS (
                        SELECT 1 FROM json_each(related_table)
                        WHERE json_extract(value, '$.table_name') = :table_name
                          AND json_extract(value, '$.id') = :record_id
                    )
                    ORDER BY timestamp DESC
                    LIMIT :limit OFFSET :offset
                ");
                $stmt->bindParam(':table_name', $table_name, PDO::PARAM_STR);
                $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return ['success' => true, 'messages' => $messages];
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'list_senders':
            $validation_result = validate_input($input, ['user_id'], $logService, 'list_senders');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];

            // Authorization check: user_id must match authenticated_user_id
            if ($user_id !== $authenticated_user_id) {
                $logService->log('conversations_api', 'warning', 'Unauthorized attempt to list senders for different user_id', ['attempted_user_id' => $user_id, 'authenticated_user_id' => $authenticated_user_id]);
                return ['success' => false, 'message' => 'Unauthorized to list senders for this user.'];
            }

            try {
                // Get unique senders/receivers with the latest message
                $stmt = $db->prepare("
                    WITH conversations AS (
                        SELECT
                            CASE
                                WHEN m.sender_id = :user_id THEN m.receiver_id
                                ELSE m.sender_id
                            END AS participant_id,
                            MAX(m.id) AS max_message_id,
                            SUM(CASE WHEN m.receiver_id = :user_id AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
                        FROM
                            messages m
                        WHERE
                            m.sender_id = :user_id OR m.receiver_id = :user_id
                        GROUP BY
                            participant_id
                    )
                    SELECT
                        c.participant_id,
                        u.name AS participant_name,
                        u.username AS participant_username,
                        u.email AS participant_email,
                        m.timestamp AS latest_message_timestamp,
                        m.message AS latest_message,
                        c.unread_count
                    FROM
                        conversations c
                    JOIN
                        users u ON u.id = c.participant_id
                    JOIN
                        messages m ON m.id = c.max_message_id
                    ORDER BY
                        m.timestamp DESC
                ");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $senders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $logService->log('conversations_api', 'success', 'Senders listed successfully', ['user_id' => $user_id, 'count' => count($senders)]);
                return ['success' => true, 'senders' => $senders];
            } catch (PDOException $e) {
                $logService->log('conversations_api', 'error', 'Database error listing senders: ' . $e->getMessage(), ['user_id' => $user_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'get_conversation':
            $validation_result = validate_input($input, ['user_id', 'participant_id'], $logService, 'get_conversation');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];
            $participant_id = (int)$input['participant_id'];

            // Authorization check: user_id must match authenticated_user_id
            if ($user_id !== $authenticated_user_id) {
                $logService->log('conversations_api', 'warning', 'Unauthorized attempt to get conversation for different user_id', ['attempted_user_id' => $user_id, 'authenticated_user_id' => $authenticated_user_id]);
                return ['success' => false, 'message' => 'Unauthorized to access this conversation.'];
            }

            try {
                $stmt = $db->prepare("
                    SELECT
                        m.*,
                        s.username AS sender_name,
                        s.email AS sender_email,
                        r.username AS receiver_name,
                        r.email AS receiver_email,
                        GROUP_CONCAT(mr.emoji_code) as reactions
                    FROM
                        messages m
                    JOIN
                        users s ON s.id = m.sender_id
                    LEFT JOIN
                        users r ON r.id = m.receiver_id
                    LEFT JOIN
                        message_reactions mr ON mr.message_id = m.id
                    WHERE
                        (m.sender_id = :user_id AND m.receiver_id = :participant_id)
                        OR
                        (m.sender_id = :participant_id AND m.receiver_id = :user_id)
                    GROUP BY
                        m.id
                    ORDER BY
                        m.timestamp ASC
                ");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':participant_id', $participant_id, PDO::PARAM_INT);
                $stmt->execute();
                $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Add related entity details to each message
                foreach ($conversation as &$message) {
                    $message['related_table'] = json_decode($message['related_table'], true);
                    if (is_array($message['related_table'])) {
                        foreach ($message['related_table'] as &$related) {
                            $related['label'] = get_entity_label($db, $related['table_name'], $related['id'], $logService);
                        }
                    }
                }
                unset($message); // Break the reference with the last element

                $logService->log('conversations_api', 'success', 'Conversation retrieved successfully', ['user_id' => $user_id, 'participant_id' => $participant_id, 'count' => count($conversation)]);
                return ['success' => true, 'conversation' => $conversation];
            } catch (PDOException $e) {
                $logService->log('conversations_api', 'error', 'Database error retrieving conversation: ' . $e->getMessage(), ['user_id' => $user_id, 'participant_id' => $participant_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'mark_conversation_as_read':
            $validation_result = validate_input($input, ['user_id', 'participant_id'], $logService, 'mark_conversation_as_read');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];
            $participant_id = (int)$input['participant_id'];

            // Authorization check: user_id must match authenticated_user_id
            if ($user_id !== $authenticated_user_id) {
                $logService->log('conversations_api', 'warning', 'Unauthorized attempt to mark conversation as read for different user_id', ['attempted_user_id' => $user_id, 'authenticated_user_id' => $authenticated_user_id]);
                return ['success' => false, 'message' => 'Unauthorized to mark this conversation as read.'];
            }

            try {
                $db->beginTransaction();
                $updated_by = $input['updated_by'] ?? $authenticated_user_id;
                $stmt = $db->prepare("
                    UPDATE messages
                    SET is_read = 1, updated_by = :updated_by
                    WHERE
                        (sender_id = :participant_id AND receiver_id = :user_id)
                        AND is_read = 0
                ");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':participant_id', $participant_id, PDO::PARAM_INT);
                $stmt->bindParam(':updated_by', $updated_by, PDO::PARAM_INT);
                $stmt->execute();
                $db->commit();

                $updated_count = $stmt->rowCount();
                $logService->log('conversations_api', 'success', 'Messages marked as read successfully', ['user_id' => $user_id, 'participant_id' => $participant_id, 'updated_count' => $updated_count]);
                return ['success' => true, 'message' => 'Messages marked as read successfully', 'updated_count' => $updated_count];
            } catch (PDOException $e) {
                $db->rollBack();
                $logService->log('conversations_api', 'error', 'Database error marking messages as read: ' . $e->getMessage(), ['user_id' => $user_id, 'participant_id' => $participant_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'send_message':
            $validation_result = validate_input($input, ['sender_id', 'receiver_id', 'message'], $logService, 'send_message');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $sender_id = (int)$input['sender_id'];
            $receiver_id = (int)$input['receiver_id'];
            $message_content = trim($input['message']);
            $patient_id = isset($input['patient_id']) && $input['patient_id'] !== '' ? (int)$input['patient_id'] : null;
            $created_by = $input['created_by'] ?? $authenticated_user_id;

            // Default related_table to the user-to-user interaction if not provided
            $related_table = null;
            if (isset($input['related_table'])) {
                $related_table = json_decode($input['related_table'], true);
            } else {
                $related_table = [['table_name' => 'users', 'id' => $receiver_id]];
            }


            if ($sender_id !== $authenticated_user_id) {
                return ['success' => false, 'message' => 'Unauthorized: Sender ID mismatch.'];
            }

            try {
                $db->beginTransaction();

                $stmt_check_receiver = $db->prepare("SELECT COUNT(*) FROM users WHERE id = :receiver_id");
                $stmt_check_receiver->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
                $stmt_check_receiver->execute();
                if ($stmt_check_receiver->fetchColumn() === 0) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Receiver user not found.'];
                }

                $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, related_table, patient_id, message, created_by) VALUES (:sender_id, :receiver_id, :related_table, :patient_id, :message, :created_by)");
                $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
                $stmt->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
                $stmt->bindParam(':related_table', json_encode($related_table), PDO::PARAM_STR);
                $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
                $stmt->bindParam(':message', $message_content, PDO::PARAM_STR);
                $stmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);
                $stmt->execute();

                $db->commit();
                return ['success' => true, 'message' => 'Message sent successfully', 'message_id' => $db->lastInsertId()];
            } catch (PDOException $e) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'add_reaction':
            $validation_result = validate_input($input, ['user_id', 'message_id', 'emoji_code'], $logService, 'add_reaction');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];
            $message_id = (int)$input['message_id'];
            $emoji_code = $input['emoji_code'];
            $created_by = $input['created_by'] ?? $authenticated_user_id;

            // Authorization check: user_id must match authenticated_user_id
            if ($user_id !== $authenticated_user_id) {
                $logService->log('conversations_api', 'warning', 'Unauthorized attempt to add reaction for different user_id', ['attempted_user_id' => $user_id, 'authenticated_user_id' => $authenticated_user_id]);
                return ['success' => false, 'message' => 'Unauthorized to add reaction for this user.'];
            }

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("INSERT INTO message_reactions (user_id, message_id, emoji_code, created_by) VALUES (:user_id, :message_id, :emoji_code, :created_by)");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':message_id', $message_id, PDO::PARAM_INT);
                $stmt->bindParam(':emoji_code', $emoji_code, PDO::PARAM_STR);
                $stmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);
                $stmt->execute();
                $db->commit();

                $logService->log('conversations_api', 'success', 'Reaction added successfully', ['user_id' => $user_id, 'message_id' => $message_id, 'emoji_code' => $emoji_code]);
                return ['success' => true, 'message' => 'Reaction added successfully'];
            } catch (PDOException $e) {
                $db->rollBack();
                $logService->log('conversations_api', 'error', 'Database error adding reaction: ' . $e->getMessage(), ['user_id' => $user_id, 'message_id' => $message_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'update_reaction':
            $validation_result = validate_input($input, ['user_id', 'message_id', 'emoji_code'], $logService, 'update_reaction');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];
            $message_id = (int)$input['message_id'];
            $emoji_code = $input['emoji_code'];
            $updated_by = $input['updated_by'] ?? $authenticated_user_id;

            // Authorization check: user_id must match authenticated_user_id
            if ($user_id !== $authenticated_user_id) {
                $logService->log('conversations_api', 'warning', 'Unauthorized attempt to update reaction for different user_id', ['attempted_user_id' => $user_id, 'authenticated_user_id' => $authenticated_user_id]);
                return ['success' => false, 'message' => 'Unauthorized to update reaction for this user.'];
            }

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE message_reactions SET emoji_code = :emoji_code, updated_by = :updated_by WHERE user_id = :user_id AND message_id = :message_id");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':message_id', $message_id, PDO::PARAM_INT);
                $stmt->bindParam(':emoji_code', $emoji_code, PDO::PARAM_STR);
                $stmt->bindParam(':updated_by', $updated_by, PDO::PARAM_INT);
                $stmt->execute();
                $db->commit();

                $logService->log('conversations_api', 'success', 'Reaction updated successfully', ['user_id' => $user_id, 'message_id' => $message_id, 'emoji_code' => $emoji_code]);
                return ['success' => true, 'message' => 'Reaction updated successfully'];
            } catch (PDOException $e) {
                $db->rollBack();
                $logService->log('conversations_api', 'error', 'Database error updating reaction: ' . $e->getMessage(), ['user_id' => $user_id, 'message_id' => $message_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'delete_reaction':
            $validation_result = validate_input($input, ['user_id', 'message_id'], $logService, 'delete_reaction');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];
            $message_id = (int)$input['message_id'];

            // Authorization check: user_id must match authenticated_user_id
            if ($user_id !== $authenticated_user_id) {
                $logService->log('conversations_api', 'warning', 'Unauthorized attempt to delete reaction for different user_id', ['attempted_user_id' => $user_id, 'authenticated_user_id' => $authenticated_user_id]);
                return ['success' => false, 'message' => 'Unauthorized to delete reaction for this user.'];
            }

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("DELETE FROM message_reactions WHERE user_id = :user_id AND message_id = :message_id");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':message_id', $message_id, PDO::PARAM_INT);
                $stmt->execute();
                $db->commit();

                $logService->log('conversations_api', 'success', 'Reaction deleted successfully', ['user_id' => $user_id, 'message_id' => $message_id]);
                return ['success' => true, 'message' => 'Reaction deleted successfully'];
            } catch (PDOException $e) {
                $db->rollBack();
                $logService->log('conversations_api', 'error', 'Database error deleting reaction: ' . $e->getMessage(), ['user_id' => $user_id, 'message_id' => $message_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'delete_conversation':
            $validation_result = validate_input($input, ['user_id', 'participant_id'], $logService, 'delete_conversation');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];
            $participant_id = (int)$input['participant_id'];

            // Authorization check: user_id must match authenticated_user_id
            if ($user_id !== $authenticated_user_id) {
                $logService->log('conversations_api', 'warning', 'Unauthorized attempt to delete conversation for different user_id', ['attempted_user_id' => $user_id, 'authenticated_user_id' => $authenticated_user_id]);
                return ['success' => false, 'message' => 'Unauthorized to delete this conversation.'];
            }

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("
                    DELETE FROM messages
                    WHERE
                        (sender_id = :user_id AND receiver_id = :participant_id)
                        OR
                        (sender_id = :participant_id AND receiver_id = :user_id)
                ");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':participant_id', $participant_id, PDO::PARAM_INT);
                $stmt->execute();
                $db->commit();

                $deleted_count = $stmt->rowCount();
                $logService->log('conversations_api', 'success', 'Conversation deleted successfully', ['user_id' => $user_id, 'participant_id' => $participant_id, 'deleted_count' => $deleted_count]);
                return ['success' => true, 'message' => 'Conversation deleted successfully', 'deleted_count' => $deleted_count];
            } catch (PDOException $e) {
                $db->rollBack();
                $logService->log('conversations_api', 'error', 'Database error deleting conversation: ' . $e->getMessage(), ['user_id' => $user_id, 'participant_id' => $participant_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'get_entity_details':
            $validation_result = validate_input($input, ['table_name', 'record_id'], $logService, 'get_entity_details');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $table_name = $input['table_name'];
            $record_id = (int)$input['record_id'];

            // Whitelist allowed tables to prevent SQL injection
            $allowed_tables = [
                'patients' => 'name',
                'staff' => 'name',
                'users' => 'username',
                'appointments' => 'title', // Assuming appointments have a title
                'rooms' => 'name',
                'agencies' => 'name',
                'procedures' => 'name',
                'surgeries' => 'title', // Assuming surgeries have a title
                'candidates' => 'name'
            ];

            if (!isset($allowed_tables[$table_name])) {
                $logService->log('conversations_api', 'error', "Attempted to get entity details for disallowed table: {$table_name}", ['input' => $input]);
                return ['success' => false, 'message' => 'Invalid table name for entity details.'];
            }

            $label_column = $allowed_tables[$table_name];

            try {
                $stmt = $db->prepare("SELECT id, {$label_column} AS label FROM {$table_name} WHERE id = :record_id");
                $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
                $stmt->execute();
                $entity_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($entity_data) {
                    $logService->log('conversations_api', 'success', 'Entity details retrieved successfully', ['table_name' => $table_name, 'record_id' => $record_id]);
                    return ['success' => true, 'entity_data' => $entity_data];
                } else {
                    $logService->log('conversations_api', 'warning', 'Entity not found for details retrieval', ['table_name' => $table_name, 'record_id' => $record_id]);
                    return ['success' => false, 'message' => 'Entity not found.'];
                }
            } catch (PDOException $e) {
                $logService->log('conversations_api', 'error', 'Database error retrieving entity details: ' . $e->getMessage(), ['table_name' => $table_name, 'record_id' => $record_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'group_by_entity':
            if (!isset($input['user_id'])) {
                return ['success' => false, 'message' => 'Missing required field: user_id'];
            }

            $user_id = (int)$input['user_id'];

            if ($user_id !== $authenticated_user_id) {
                return ['success' => false, 'message' => 'Unauthorized to group messages for this user.'];
            }

            try {
                $stmt = $db->prepare("
                    WITH message_relations AS (
                        SELECT
                            m.id AS message_id,
                            m.timestamp,
                            m.receiver_id,
                            m.is_read,
                            json_extract(value, '$.table_name') AS related_table,
                            json_extract(value, '$.id') AS related_id
                        FROM
                            messages m,
                            json_each(m.related_table)
                        WHERE
                            (m.sender_id = :user_id OR m.receiver_id = :user_id)
                            AND json_valid(m.related_table)
                    )
                    SELECT
                        mr.related_table,
                        mr.related_id,
                        MAX(mr.timestamp) AS last_message_timestamp,
                        COUNT(mr.message_id) AS message_count,
                        SUM(CASE WHEN mr.receiver_id = :user_id AND mr.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
                    FROM
                        message_relations mr
                    GROUP BY
                        mr.related_table, mr.related_id
                    ORDER BY
                        last_message_timestamp DESC
                ");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $grouped_conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($grouped_conversations as &$group) {
                    $group['related_entity_label'] = get_entity_label($db, $group['related_table'], $group['related_id'], $logService);
                }
                unset($group);

                return ['success' => true, 'grouped_conversations' => $grouped_conversations];
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'list_patients_with_messages':
            $validation_result = validate_input($input, ['user_id'], $logService, 'list_patients_with_messages');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];

            if ($user_id !== $authenticated_user_id) {
                return ['success' => false, 'message' => 'Unauthorized to list patients for this user.'];
            }

            try {
                $stmt = $db->prepare("
                    WITH patient_latest_message AS (
                        SELECT
                            patient_id,
                            MAX(id) AS max_message_id
                        FROM
                            messages
                        WHERE
                            patient_id IS NOT NULL
                        GROUP BY
                            patient_id
                    )
                    SELECT
                        p.id AS patient_id,
                        p.name AS patient_name,
                        p.email AS patient_email,
                        p.updated_by AS patient_responsible_user_id,
                        m.timestamp AS latest_message_timestamp,
                        m.message AS latest_message
                    FROM
                        patients p
                    JOIN
                        patient_latest_message plm ON p.id = plm.patient_id
                    JOIN
                        messages m ON plm.max_message_id = m.id
                    ORDER BY
                        m.timestamp DESC
                ");
                $stmt->execute();
                $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return ['success' => true, 'patients' => $patients];
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'get_patient_conversation':
            $validation_result = validate_input($input, ['user_id', 'patient_id'], $logService, 'get_patient_conversation');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $user_id = (int)$input['user_id'];
            $patient_id = (int)$input['patient_id'];

            if ($user_id !== $authenticated_user_id) {
                return ['success' => false, 'message' => 'Unauthorized to access this conversation.'];
            }

            try {
                $stmt = $db->prepare("
                    SELECT
                        m.*,
                        s.username AS sender_name,
                        s.email AS sender_email,
                        r.username AS receiver_name,
                        r.email AS receiver_email,
                        GROUP_CONCAT(mr.emoji_code) as reactions
                    FROM
                        messages m
                    JOIN
                        users s ON s.id = m.sender_id
                    LEFT JOIN
                        users r ON r.id = m.receiver_id
                    LEFT JOIN
                        message_reactions mr ON mr.message_id = m.id
                    WHERE
                        m.patient_id = :patient_id
                    GROUP BY
                        m.id
                    ORDER BY
                        m.timestamp ASC
                ");
                $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
                $stmt->execute();
                $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($conversation as &$message) {
                    $message['related_table'] = json_decode($message['related_table'], true);
                    if (is_array($message['related_table'])) {
                        foreach ($message['related_table'] as &$related) {
                            $related['label'] = get_entity_label($db, $related['table_name'], $related['id'], $logService);
                        }
                    }
                }
                unset($message);

                return ['success' => true, 'conversation' => $conversation];
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        default:
            $logService->log('conversations_api', 'error', 'Unknown action requested', ['action' => $action, 'method' => $method, 'input' => $input]);
            return ['success' => false, 'message' => 'Unknown action.'];
    }
}

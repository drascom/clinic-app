<?php
// File: public/api_handlers/messages.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../services/LogService.php';

function handle_messages($action, $method, $db, $input)
{
    $logService = new LogService();
    $authenticated_user_id = $input['authenticated_user_id'] ?? 0;

    // Helper function for validation (local to messages.php)
    function validate_input($input, $fields, $logService, $action_name)
    {
        foreach ($fields as $field) {
            if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
                $logService->log('messages_api', 'error', "Missing or empty required field: {$field} for {$action_name} action", ['input' => $input]);
                return ['success' => false, 'message' => "Missing or empty required field: {$field}"];
            }
        }
        return ['success' => true];
    }

    // Helper function to get entity label (local to messages.php)
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
            $logService->log('messages_api', 'warning', "Attempted to get entity label for disallowed table: {$table_name}", ['table_name' => $table_name, 'record_id' => $record_id]);
            return null;
        }

        $label_column = $allowed_tables[$table_name];

        try {
            $stmt = $db->prepare("SELECT {$label_column} AS label FROM {$table_name} WHERE id = :record_id");
            $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
            $stmt->execute();
            $entity_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($entity_data) {
                $logService->log('messages_api', 'debug', "Retrieved entity label for {$table_name}:{$record_id}", ['label' => $entity_data['label']]);
                return $entity_data['label'];
            } else {
                $logService->log('messages_api', 'debug', 'Entity not found for label retrieval', ['table_name' => $table_name, 'record_id' => $record_id]);
                return null;
            }
        } catch (PDOException $e) {
            $logService->log('messages_api', 'error', 'Database error retrieving entity label: ' . $e->getMessage(), ['table_name' => $table_name, 'record_id' => $record_id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    switch ($action) {
        case 'create':
            $validation_result = validate_input($input, ['sender_id', 'message'], $logService, 'create');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $sender_id = (int)$input['sender_id'];
            $receiver_id = isset($input['receiver_id']) && $input['receiver_id'] !== '' ? (int)$input['receiver_id'] : null;
            $patient_id = isset($input['patient_id']) && $input['patient_id'] !== '' ? (int)$input['patient_id'] : null; // New patient_id
            $related_tables_json = $input['related_tables'] ?? null;
            $message_content = trim($input['message']);

            if ($sender_id !== $authenticated_user_id) {
                $logService->log('messages_api', 'warning', 'Unauthorized attempt to create message with different sender_id', ['attempted_sender_id' => $sender_id, 'authenticated_user_id' => $authenticated_user_id, 'input' => $input]);
                return ['success' => false, 'message' => 'Unauthorized: Sender ID mismatch.'];
            }

            try {
                $db->beginTransaction();

                if ($receiver_id !== null) {
                    $stmt_check_receiver = $db->prepare("SELECT COUNT(*) FROM users WHERE id = :receiver_id");
                    $stmt_check_receiver->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
                    $stmt_check_receiver->execute();
                    if ($stmt_check_receiver->fetchColumn() === 0) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Receiver user not found.'];
                    }
                }

                if ($patient_id !== null) {
                    $stmt_check_patient = $db->prepare("SELECT COUNT(*) FROM patients WHERE id = :patient_id");
                    $stmt_check_patient->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
                    $stmt_check_patient->execute();
                    if ($stmt_check_patient->fetchColumn() === 0) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Patient not found.'];
                    }
                }

                $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, related_table, patient_id, message) VALUES (:sender_id, :receiver_id, :related_table, :patient_id, :message)");
                $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
                $stmt->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
                $stmt->bindParam(':related_table', $related_tables_json, PDO::PARAM_STR);
                $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
                $stmt->bindParam(':message', $message_content, PDO::PARAM_STR);
                $stmt->execute();

                $db->commit();
                $logService->log('messages_api', 'success', 'Message created successfully', ['message_id' => $db->lastInsertId(), 'input' => $input]);
                return ['success' => true, 'message' => 'Message created successfully', 'message_id' => $db->lastInsertId()];
            } catch (PDOException $e) {
                $db->rollBack();
                $logService->log('messages_api', 'error', 'Database error creating message: ' . $e->getMessage(), ['input' => $input, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'get':
            $validation_result = validate_input($input, ['message_id'], $logService, 'get');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $message_id = (int)$input['message_id'];

            try {
                $stmt = $db->prepare("
                    SELECT
                        m.*,
                        s.username AS sender_username,
                        r.username AS receiver_username,
                        p.name AS patient_name
                    FROM
                        messages m
                    JOIN
                        users s ON s.id = m.sender_id
                    LEFT JOIN
                        users r ON r.id = m.receiver_id
                    LEFT JOIN
                        patients p ON p.id = m.patient_id
                    WHERE
                        m.id = :message_id
                ");
                $stmt->bindParam(':message_id', $message_id, PDO::PARAM_INT);
                $stmt->execute();
                $message = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($message) {
                    // Authorization check: Only sender or receiver can retrieve message details
                    if ($message['sender_id'] !== $authenticated_user_id && $message['receiver_id'] !== $authenticated_user_id) {
                        $logService->log('messages_api', 'warning', 'Unauthorized attempt to retrieve message', ['message_id' => $message_id, 'authenticated_user_id' => $authenticated_user_id]);
                        return ['success' => false, 'message' => 'Unauthorized to access this message.'];
                    }

                    $logService->log('messages_api', 'success', 'Message retrieved successfully', ['message_id' => $message_id]);
                    return ['success' => true, 'message_data' => $message];
                } else {
                    $logService->log('messages_api', 'warning', 'Message not found', ['message_id' => $message_id]);
                    return ['success' => false, 'message' => 'Message not found'];
                }
            } catch (PDOException $e) {
                $logService->log('messages_api', 'error', 'Database error retrieving message: ' . $e->getMessage(), ['message_id' => $message_id, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'update':
            $validation_result = validate_input($input, ['message_id', 'sender_id', 'message'], $logService, 'update');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $message_id = (int)$input['message_id'];
            $sender_id = (int)$input['sender_id'];
            $receiver_id = isset($input['receiver_id']) && $input['receiver_id'] !== '' ? (int)$input['receiver_id'] : null;
            $patient_id = isset($input['patient_id']) && $input['patient_id'] !== '' ? (int)$input['patient_id'] : null; // New patient_id
            $related_tables_json = $input['related_tables'] ?? null;
            $message_content = trim($input['message']);

            if ($sender_id !== $authenticated_user_id) {
                return ['success' => false, 'message' => 'Unauthorized: Sender ID mismatch.'];
            }

            try {
                $db->beginTransaction();

                $stmt_fetch = $db->prepare("SELECT sender_id FROM messages WHERE id = :message_id");
                $stmt_fetch->bindParam(':message_id', $message_id, PDO::PARAM_INT);
                $stmt_fetch->execute();
                $existing_message = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

                if (!$existing_message) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Message not found.'];
                }

                if ($existing_message['sender_id'] !== $authenticated_user_id) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Unauthorized to update this message.'];
                }

                $stmt = $db->prepare("UPDATE messages SET sender_id = :sender_id, receiver_id = :receiver_id, related_table = :related_table, patient_id = :patient_id, message = :message WHERE id = :message_id");
                $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
                $stmt->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
                $stmt->bindParam(':related_table', $related_tables_json, PDO::PARAM_STR);
                $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
                $stmt->bindParam(':message', $message_content, PDO::PARAM_STR);
                $stmt->bindParam(':message_id', $message_id, PDO::PARAM_INT);
                $stmt->execute();

                $db->commit();
                if ($stmt->rowCount() > 0) {
                    return ['success' => true, 'message' => 'Message updated successfully'];
                } else {
                    return ['success' => false, 'message' => 'Message not found or no changes made'];
                }
            } catch (PDOException $e) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        default:
            $logService->log('messages_api', 'error', 'Unknown action requested', ['action' => $action, 'method' => $method, 'input' => $input]);
            return ['success' => false, 'message' => 'Unknown action.'];
    }
}

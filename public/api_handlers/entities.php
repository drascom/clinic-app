<?php
// File: public/api_handlers/entities.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../services/LogService.php';

function handle_entities($action, $method, $db, $input)
{
    $logService = new LogService();
    $authenticated_user_id = $input['authenticated_user_id'] ?? 0;

    // Helper function for validation (re-using from messages.php for consistency)
    function validate_input($input, $fields, $logService, $action_name)
    {
        foreach ($fields as $field) {
            if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
                $logService->log('entities_api', 'error', "Missing or empty required field: {$field} for {$action_name} action", ['input' => $input]);
                return ['success' => false, 'message' => "Missing or empty required field: {$field}"];
            }
        }
        return ['success' => true];
    }

    switch ($action) {
        case 'tables':
            try {
                // Fetch all table names from the database
                $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'message_reactions'");
                $all_db_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $relevant_tables = [];
                // Define a mapping of table names to their likely label columns
                $table_label_map = [
                    'patients' => 'name',
                    'staff' => 'name',
                    'users' => 'username',
                    'appointments' => 'title',
                    'rooms' => 'name',
                    'agencies' => 'name',
                    'procedures' => 'name',
                    'surgeries' => 'title',
                    'candidates' => 'name',
                    'emails' => 'subject', // Assuming emails can be related and have a subject
                    'email_attachments' => 'filename', // Assuming attachments can be related
                ];

                foreach ($all_db_tables as $db_table) {
                    if (isset($table_label_map[$db_table])) {
                        // Verify if the table has an 'id' column and the specified label column
                        $stmt_check_cols = $db->prepare("PRAGMA table_info({$db_table})");
                        $stmt_check_cols->execute();
                        $columns = $stmt_check_cols->fetchAll(PDO::FETCH_COLUMN, 1); // Get column names

                        if (in_array('id', $columns) && in_array($table_label_map[$db_table], $columns)) {
                            $relevant_tables[] = [
                                'name' => $db_table,
                                'label' => ucfirst(str_replace('_', ' ', $db_table)) // Simple capitalization for label
                            ];
                        }
                    }
                }

                $logService->log('entities_api', 'success', 'Entity tables listed successfully', ['count' => count($relevant_tables)]);
                return ['success' => true, 'tables' => $relevant_tables];
            } catch (PDOException $e) {
                $logService->log('entities_api', 'error', 'Database error listing tables: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        case 'list':
            $validation_result = validate_input($input, ['table'], $logService, 'list');
            if (!$validation_result['success']) {
                return $validation_result;
            }

            $table_name = $input['table'];
            $search_term = $input['search'] ?? ''; // For Select2 search functionality

            // Whitelist allowed tables and their label columns to prevent SQL injection
            $allowed_tables = [
                'patients' => 'name',
                'staff' => 'name',
                'users' => 'username', // Users typically have a username
                'appointments' => 'title', // Assuming appointments have a title
                'rooms' => 'name',
                'agencies' => 'name',
                'procedures' => 'name',
                'surgeries' => 'title', // Assuming surgeries have a title
                'candidates' => 'name'
            ];

            if (!isset($allowed_tables[$table_name])) {
                $logService->log('entities_api', 'error', "Attempted to list records for disallowed table: {$table_name}", ['input' => $input]);
                return ['success' => false, 'message' => 'Invalid table name.'];
            }

            $label_column = $allowed_tables[$table_name];

            try {
                $sql = "SELECT id, {$label_column} AS label FROM {$table_name}";
                $params = [];

                if (!empty($search_term)) {
                    $sql .= " WHERE {$label_column} LIKE :search_term";
                    $params[':search_term'] = '%' . $search_term . '%';
                }
                $sql .= " ORDER BY {$label_column} ASC LIMIT 100"; // Limit results for performance

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $logService->log('entities_api', 'success', "Records listed successfully for table: {$table_name}", ['count' => count($records)]);
                return ['success' => true, 'records' => $records];
            } catch (PDOException $e) {
                $logService->log('entities_api', 'error', 'Database error listing records: ' . $e->getMessage(), ['table' => $table_name, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

        default:
            $logService->log('entities_api', 'error', 'Unknown action requested', ['action' => $action, 'method' => $method, 'input' => $input]);
            return ['success' => false, 'message' => 'Unknown action.'];
    }
}

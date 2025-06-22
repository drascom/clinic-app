<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/email_functions.php'; // For get_user_email_settings
require_once __DIR__ . '/../services/LogService.php';

function handle_email_settings($action, $method, $db, $input)
{
    $logService = new LogService();
    if ($method !== 'POST') {
        $logService->log('email_settings', 'error', 'Invalid request method. Only POST is allowed.', ['method' => $method]);
        return ['success' => false, 'message' => 'Invalid request method. Only POST is allowed.'];
    }
    $user_id = isset($input['user_id']) ? $input['user_id'] : $_SESSION['user_id'];

    switch ($action) {
        case 'save':
            if ($method !== 'POST') {
                $logService->log('email_settings', 'error', 'Invalid request method for save action.', ['method' => $method]);
                return ['success' => false, 'message' => 'Invalid request method.'];
            }

            $required_fields = ['email_address', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure'];
            foreach ($required_fields as $field) {
                if (empty($input[$field])) {
                    $logService->log('email_settings', 'error', "Missing required field: $field", $input);
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            try {
                $stmt = $db->prepare("SELECT id FROM user_email_settings WHERE user_id = :user_id");
                $stmt->bindValue(':user_id', $user_id);
                $stmt->execute();
                $existing_setting = $stmt->fetch();

                if ($existing_setting) {
                    $sql = "UPDATE user_email_settings SET email_address = :email_address, smtp_host = :smtp_host, smtp_port = :smtp_port, smtp_username = :smtp_username, smtp_password = :smtp_password, smtp_secure = :smtp_secure WHERE user_id = :user_id";
                } else {
                    $sql = "INSERT INTO user_email_settings (user_id, email_address, smtp_host, smtp_port, smtp_username, smtp_password, smtp_secure) VALUES (:user_id, :email_address, :smtp_host, :smtp_port, :smtp_username, :smtp_password, :smtp_secure)";
                }

                $stmt = $db->prepare($sql);
                $stmt->bindValue(':user_id', $user_id);
                $stmt->bindValue(':email_address', $input['email_address']);
                $stmt->bindValue(':smtp_host', $input['smtp_host']);
                $stmt->bindValue(':smtp_port', $input['smtp_port']);
                $stmt->bindValue(':smtp_username', $input['smtp_username']);
                $stmt->bindValue(':smtp_password', $input['smtp_password']);
                $stmt->bindValue(':smtp_secure', $input['smtp_secure']);

                if ($stmt->execute()) {
                    $logService->log('email_settings', 'success', 'Email settings saved successfully.', ['user_id' => $user_id]);
                    return ['success' => true];
                } else {
                    $logService->log('email_settings', 'error', 'Failed to save settings.', ['user_id' => $user_id]);
                    return ['success' => false, 'message' => 'Failed to save settings.'];
                }
            } catch (PDOException $e) {
                $logService->log('email_settings', 'error', 'Database error during save email settings: ' . $e->getMessage(), ['error' => $e->getMessage(), 'user_id' => $user_id]);
                return ['success' => false, 'message' => 'An error occurred while saving settings.'];
            }
            break;

        case 'get':
            if (!isset($input['user_id'])) {
                $logService->log('email_settings', 'error', 'User ID is required to fetch email settings.', $input);
                return ['success' => false, 'message' => 'User ID is required to fetch email settings.'];
            }
            $userId = (int) $input['user_id'];
            $settings = get_user_email_settings($db, $userId);
            if ($settings) {
                $logService->log('email_settings', 'success', 'Email settings retrieved successfully.', ['user_id' => $userId]);
                return ['success' => true, 'data' => $settings];
            } else {
                $logService->log('email_settings', 'info', 'Email settings not found for the specified user.', ['user_id' => $userId]);
                return ['success' => false, 'message' => 'Email settings not found for the specified user.'];
            }
        default:
            $logService->log('email_settings', 'error', 'Invalid action for email settings.', ['action' => $action]);
            return ['success' => false, 'message' => 'Invalid action for email settings.'];
    }
}

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../auth/auth.php';

function handle_session($action, $method, $db, $request_data = [])
{
    if (!is_logged_in()) {
        return ['success' => false, 'error' => 'Unauthorized'];
    }

    switch ($action) {
        case 'get_user_session':
            if ($method === 'GET' || $method === 'POST') {
                return [
                    'success' => true,
                    'user' => [
                        'id' => get_user_id(),
                        'username' => $_SESSION['username'] ?? null,
                        'role' => get_user_role(),
                        'agency_id' => get_user_agency_id()
                    ]
                ];
            }
            break;
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

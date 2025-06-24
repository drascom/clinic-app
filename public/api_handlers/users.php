<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_users($action, $method, $db, $request_data = [])
{
    $logService = new LogService();
    // Use the request data passed from api.php instead of reading from php://input
    $input = $request_data;
    switch ($action) {
        case 'add':
            if ($method === 'POST') {
                $email = trim($input['email'] ?? '');
                $name = trim($input['name'] ?? '');
                $surname = trim($input['surname'] ?? '');
                $username = trim($input['username'] ?? $name . ' ' . $surname);
                $username = ucwords($username);
                $password = trim($input['password'] ?? '');
                $role = trim($input['role'] ?? 'user'); // Default role to 'user'
                $agency_id = $input['agency_id'] ?? null;
                $is_active = $input['is_active'] ?? 0; // Default to 0 if not provided
                $created_by = $input['authenticated_user_id'] ?? null;
                if ($email && $username && $password) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $db->prepare("INSERT INTO users (email, username, password, role, agency_id, is_active, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?, datetime('now'), ?)");
                    $stmt->execute([$email, $username, $hashed, $role, $agency_id, $is_active, $created_by]);
                    return ['success' => true, 'id' => $db->lastInsertId()];
                }

                return ['success' => false, 'error' => 'Email, username, and password are required.'];
            }
            break;

        case 'edit': // Added update method as requested
            if ($method === 'POST') {
                $id = $input['id'] ?? null; // Corrected key to 'id'
                $email = trim($input['email'] ?? '');
                $name = trim($input['name'] ?? '');
                $surname = trim($input['surname'] ?? '');
                $username = trim($input['username'] ?? $name . '.' . $surname);
                $password = trim($input['password'] ?? '');
                $role = trim($input['role'] ?? 'user'); // Default role to 'user'
                $agency_id = $input['agency_id'] ?? null;
                $is_active = $input['is_active'] ?? 0; // Default to 0 if not provided
                $updated_by = $input['authenticated_user_id'] ?? null;

                error_log("User update handler - ID: " . ($id ?? 'NULL') . ", Email: " . ($email ?? 'NULL') . ", Username: " . ($username ?? 'NULL')); // Added logging
                if ($id && $email && $username) {
                    // Check if user exists
                    $check_stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'User not found.'];
                    }

                    if ($password) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET email = ?, username = ?, password = ?, role = ?, agency_id = ?, is_active= ?, updated_by = ?  WHERE id = ?");
                        $stmt->execute([$email, $username, $hashed, $role, $agency_id, $is_active, $updated_by, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE users SET email = ?, name = ?, surname = ?, username = ?, role = ?, agency_id = ?, is_active= ?, updated_by = ? WHERE id = ?");
                        $stmt->execute([$email, $name, $surname, $username, $role, $agency_id, $is_active, $updated_by, $id]);
                    }
                    return ['success' => true];
                }

                return ['success' => false, 'error' => 'ID, email and username are required.'];
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    // Check if user exists
                    $check_stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'User not found.'];
                    }

                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    return ['success' => true];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $data ? ['success' => true, 'user' => $data] : ['success' => false, 'error' => "User not found with ID: {$id}"];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'list':
            if ($method === 'POST') {
                $search = $input['search'] ?? '';
                $page = isset($input['page']) ? (int)$input['page'] : 1;
                $limit = 10;
                $offset = ($page - 1) * $limit;

                $sql = "SELECT id, username FROM users";
                $count_sql = "SELECT COUNT(id) FROM users";
                $params = [];

                if (!empty($search)) {
                    $sql .= " WHERE username LIKE ?";
                    $count_sql .= " WHERE username LIKE ?";
                    $params[] = '%' . $search . '%';
                }

                $sql .= " ORDER BY username LIMIT ? OFFSET ?";

                // Get total count for pagination
                $count_stmt = $db->prepare($count_sql);
                $count_stmt->execute($params);
                $total = $count_stmt->fetchColumn();

                // Get paginated results
                $stmt = $db->prepare($sql);
                $stmt->execute(array_merge($params, [$limit, $offset]));
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return ['success' => true, 'data' => $users, 'total' => $total];
            }
            break;
            break;

        case 'register':
            if ($method === 'POST') {
                $email = trim($input['email'] ?? '');
                $name = trim($input['name'] ?? '');
                $surname = trim($input['surname'] ?? '');
                $password = trim($input['password'] ?? '');
                $confirm_password = trim($input['confirm_password'] ?? '');
                $agency_id = $input['agency_id'] ?? null;

                // Validate required fields
                if (empty($email) || empty($name) || empty($surname) || empty($password) || empty($confirm_password)) {
                    return ['success' => false, 'error' => 'Please fill in all fields.'];
                }

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return ['success' => false, 'error' => 'Please enter a valid email address.'];
                }

                // Validate name and surname (basic validation)
                if (strlen($name) < 2 || strlen($surname) < 2) {
                    return ['success' => false, 'error' => 'Name and surname must be at least 2 characters long.'];
                }

                // Validate password strength
                if (strlen($password) < 8) {
                    return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
                }

                // Check if passwords match
                if ($password !== $confirm_password) {
                    return ['success' => false, 'error' => 'Passwords do not match.'];
                }

                // Validate agency_id if provided
                if ($agency_id !== null && !empty($agency_id)) {
                    $stmt = $db->prepare("SELECT id FROM agencies WHERE id = ?");
                    $stmt->execute([$agency_id]);
                    if (!$stmt->fetch()) {
                        return ['success' => false, 'error' => 'Invalid agency selected.'];
                    }
                }

                // Generate username from name and surname
                $base_username = strtolower(trim($name) . '.' . trim($surname));
                $username = $base_username;

                // Check if username already exists and generate a unique one
                $counter = 1;
                while (true) {
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if (!$stmt->fetch()) {
                        break; // Username is available
                    }
                    $username = $base_username . $counter;
                    $counter++;
                    if ($counter > 100) { // Prevent infinite loop
                        return ['success' => false, 'error' => 'Unable to generate a unique username. Please try different name/surname.'];
                    }
                }

                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'Email is already registered.'];
                }

                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new user into the database
                try {
                    $created_by = $input['authenticated_user_id'] ?? null;
                    $stmt = $db->prepare("INSERT INTO users (email, username, password, role, agency_id, name, surname, created_at, updated_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'), ?)");
                    $result = $stmt->execute([$email, $username, $hashed_password, 'user', $agency_id, $name, $surname, $created_by]);

                    if ($result) {
                        return ['success' => true, 'message' => 'Registration successful! You can login when your account is activated.'];
                    } else {
                        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
                    }
                } catch (Exception $e) {
                    return ['success' => false, 'error' => 'Registration failed. Please try again.${e->getMessage()}'];
                }
            }
            break;

        case 'change_password':
            if ($method === 'POST') {
                $userId = $input['user_id'] ?? null;
                $newPassword = $input['new_password'] ?? null;
                $confirmPassword = $input['confirm_password'] ?? null;
                $updated_by = $input['authenticated_user_id'] ?? null;

                if (!$userId || !$newPassword || !$confirmPassword) {
                    return ['success' => false, 'error' => $userId . $newPassword . $confirmPassword . ' All password fields are required.'];
                }

                if ($newPassword !== $confirmPassword) {
                    return ['success' => false, 'error' => 'New password and confirm password do not match.'];
                }


                // Hash and update the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ?, updated_by = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $updated_by, $userId]);

                return ['success' => true, 'message' => 'Password changed successfully.'];
            }
            break;

        case 'get_email_settings':
            if ($method === 'POST') {
                // Assuming user_id is passed in the input for this action
                $user_id = $input['user_id'] ?? null;

                if (!$user_id) {
                    return ['success' => false, 'error' => 'User ID is required to get email settings.'];
                }

                $stmt = $db->prepare("SELECT * FROM user_email_settings WHERE user_id = :user_id");
                $stmt->bindValue(':user_id', $user_id);
                $stmt->execute();
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$settings) {
                    $settings = [
                        'email_address' => '',
                        'smtp_host' => '',
                        'smtp_port' => '',
                        'smtp_username' => '',
                        'smtp_password' => '',
                        'smtp_secure' => 'tls'
                    ];
                }
                return ['success' => true, 'settings' => $settings];
            }
            break;
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}

<?php
// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine the correct path to db.php based on where this file is being included from
$db_path = '';
if (file_exists(__DIR__ . '/../includes/db.php')) {
    // Called from within auth directory
    $db_path = __DIR__ . '/../includes/db.php';
} elseif (file_exists(dirname(__DIR__) . '/includes/db.php')) {
    // Called from parent directory (like dashboard.php)
    $db_path = dirname(__DIR__) . '/includes/db.php';
} else {
    // Fallback - try relative path
    $db_path = 'includes/db.php';
}

require_once $db_path;
// Function to check if a user is logged in
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Function to attempt user login
function login_user($email, $password)
{
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    global $pdo; // Use the PDO connection from db.php

    $stmt = $pdo->prepare("SELECT id, username, password, role, agency_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        session_regenerate_id(true);
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['agency_id'] = $user['agency_id'];
        return true;
    }
    return false;
}

// Function to log out the current user
function logout_user()
{
    session_unset();
    session_destroy();
}


// Function to get current user's agency ID
function get_user_agency_id()
{
    return $_SESSION['agency_id'] ?? '';
}

// Function to get current user's user_role
function get_user_role()
{
    return $_SESSION['user_role'] ?? '';
}

// Function to register a new user
function register_user($email, $username, $password, $agency_id = null, $name = null, $surname = null)
{
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Validate password strength
    if (strlen($password) < 8) {
        return false;
    }

    global $pdo; // Use the PDO connection from db.php

    // Check if email already exists (username uniqueness is handled in signup.php)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return false; // Email already registered
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the database with name and surname
    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, role, agency_id, name, surname, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
        $result = $stmt->execute([$email, $username, $hashed_password, 'user', $agency_id, $name, $surname]);

        return $result; // Return true if successful, false otherwise
    } catch (Exception $e) {
        return false;
    }
}

// Function to get current user's ID
function get_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

// Function to check if current user is an admin
function is_admin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to check if current user is an agent
function is_agent()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'agent';
}

// Function to check if current user is an editor
function is_editor()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'editor';
}

// Function to check if current user is a technician
function is_staff()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'technician';
}

// Function to get current user's technician ID (for technician users only)
function get_technician_id()
{
    if (!is_staff()) {
        return null;
    }

    $user_id = get_user_id();
    if (!$user_id) {
        return null;
    }

    global $pdo;

    // Get user's details to match with staff record
    $stmt = $pdo->prepare("SELECT name, surname, username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    // Try multiple matching strategies
    $search_terms = [];

    // 1. Try full name (name + surname)
    if (!empty($user['name']) && !empty($user['surname'])) {
        $search_terms[] = trim($user['name'] . ' ' . $user['surname']);
    }

    // 2. Try just name
    if (!empty($user['name'])) {
        $search_terms[] = $user['name'];
    }

    // 3. Try username
    if (!empty($user['username'])) {
        $search_terms[] = $user['username'];
    }

    // 4. Try email
    if (!empty($user['email'])) {
        $search_terms[] = $user['email'];
    }

    // Search for technician record using each search term
    foreach ($search_terms as $search_term) {
        $stmt = $pdo->prepare("SELECT id FROM staff WHERE name = ? AND is_active = 1");
        $stmt->execute([$search_term]);
        $technician = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($technician) {
            return $technician['id'];
        }
    }

    return null;
}

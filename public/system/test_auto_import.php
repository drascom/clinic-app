<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Authentication Check
if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

echo "<h2>Testing Auto Import AJAX Call</h2>";

// Test the fetch_and_save_sheets action directly
echo "<h3>Testing fetch_and_save_sheets action...</h3>";

// Simulate the AJAX call
$_POST['action'] = 'fetch_and_save_sheets';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capture output
ob_start();

// Include the auto_import.php file to execute the AJAX handler
include 'auto_import.php';

$output = ob_get_clean();

echo "<h4>Response:</h4>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Check if it's valid JSON
$json = json_decode($output, true);
if ($json === null) {
    echo "<p style='color: red;'>ERROR: Response is not valid JSON</p>";
    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
} else {
    echo "<p style='color: green;'>SUCCESS: Valid JSON response</p>";
    echo "<h4>Parsed JSON:</h4>";
    echo "<pre>" . print_r($json, true) . "</pre>";
}
?>

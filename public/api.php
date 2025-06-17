<?php
// Report all PHP errors, warnings, and notices
error_reporting(E_ALL);
// Do not display errors in the output (important for APIs returning JSON)
ini_set('display_errors', 0);
// Enable logging of PHP errors
ini_set('log_errors', 1);
// Specify the file where PHP errors should be logged
ini_set('error_log', __DIR__ . '/../logs/php_error.log');
error_log("PHP error log path set to: " . ini_get('error_log'));

// Start output buffering to catch any unexpected output
ob_start();

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

// Main API routing
$entity = null;
$action = null;
$method = $_SERVER['REQUEST_METHOD'];
$request_body = file_get_contents('php://input');

if ($method === 'POST') {
    // Check Content-Type to determine if it's JSON or form data
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($content_type, 'application/json') !== false) {
        // For JSON POST requests, read from the request body
        $input = json_decode($request_body, true);
        if ($input) {
            $entity = $input['entity'] ?? null;
            $action = $input['action'] ?? null;
        }
    } else {
        // For standard form-encoded POST requests, merge all $_POST data
        $input = $_POST;
        $entity = $input['entity'] ?? null;
        $action = $input['action'] ?? null;
    }
} elseif ($method === 'PUT') {
    // For PUT requests, read from the request body (assuming JSON)
    $input = json_decode($request_body, true);
    if ($input) {
        $entity = $input['entity'] ?? null;
        $action = $input['action'] ?? null;
    }
} else { // For GET and other methods, still check $_GET
    $entity = $_GET['entity'] ?? null;
    $action = $_GET['action'] ?? null;
}

$response = ['success' => false, 'message' => "Invalid request: Missing entity or action.", 'details' => ['entity' => $entity, 'action' => $action, 'method' => $method]];

try {
    // Custom routing for technician availability endpoints
    $request_uri = $_SERVER['REQUEST_URI'];
    $parsed_url = parse_url($request_uri);
    $path = $parsed_url['path'] ?? '';

    if ($method === 'GET' && preg_match('/^\/api\/availability\/(\d{4}-\d{2})$/', $path, $matches)) {
        $entity = 'techAvail';
        $action = 'getAvailability';
        $input['month'] = $matches[1]; // Extract month from URL
    } elseif ($method === 'POST' && $path === '/api/toggle-day') {
        $entity = 'techAvail';
        $action = 'toggleDay';
        // The date is expected to be in the JSON body, already decoded into $input
    }

    // Main API routing (existing logic)
    if ($entity && $action) {
        $handler_file = __DIR__ . "/api_handlers/{$entity}.php";
        $handler_function = "handle_{$entity}";

        // Log file check
        if (file_exists($handler_file)) {
            include_once $handler_file;

            // Log function check
            if (function_exists($handler_function)) {
                $db = get_db();
                // Pass the input data to the handler function
                $response = $handler_function($action, $method, $db, $input ?? []);
                $log_message = "Result '{$entity}', action '{$action}', method '{$method}': " . $handler_function;
                error_log($log_message);
            } else {
                $log_message = "Handler function not found for entity '{$entity}', action '{$action}', method '{$method}': " . $handler_function;
                error_log($log_message);
                $response = ['success' => false, 'message' => "Function {$handler_function} not found."];
            }
        } else {
            $log_message = "Handler file not found for entity '{$entity}', action '{$action}', method '{$method}': " . $handler_file;
            error_log($log_message);
            $response = ['success' => false, 'message' => "Handler for {$entity} not found."];
        }
    }
} catch (Exception $e) {
    // Log the exception details
    error_log("Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    $response = ['success' => false, 'error' => 'Internal server error.'];
} catch (Error $e) {
    // Log the error details
    error_log("Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    $response = ['success' => false, 'error' => 'Fatal error occurred.'];
}

// Clean any unexpected output
ob_clean();

// Log and return the response
echo json_encode($response);
<?php
session_start();
// Report all PHP errors, warnings, and notices
error_reporting(E_ALL);
// Do not display errors in the output (important for APIs returning JSON)
ini_set('display_errors', 0);
// Enable logging of PHP errors
ini_set('log_errors', 1);
// Specify the file where PHP errors should be logged
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Start output buffering to catch any unexpected output
ob_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/services/LogService.php';

$logService = new LogService();

// Main API routing
$entity = null;
$action = null;
$method = $_SERVER['REQUEST_METHOD'];
$request_body = file_get_contents('php://input');
// Set Content-Type header based on the request. Default to application/json.
// For Server-Sent Events (SSE) from the emails handler, the content type will be overridden there.
if (!($entity === 'emails' && $action === 'check_new_emails')) {
    header('Content-Type: application/json');
}

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
                // Add authenticated user ID to input for handlers
                $input['authenticated_user_id'] = $_SESSION['user_id'] ?? 0;

                // Pass the input data to the handler function
                $response = $handler_function($action, $method, $db, $input ?? []);
                if (!$response['success']) {
                    $logService->log($entity, $response['success'] ? 'success' : 'error', "Action: {$action}", $response);
                }
                $logService->log($entity, $response['success'] ? 'success' : 'error', "File: {$entity} Action: {$action}", []);
            } else {
                $response = ['success' => false, 'message' => "Function {$handler_function} not found."];
                $logService->log($entity, 'error', "Handler function not found: {$handler_function}");
            }
        } else {
            $response = ['success' => false, 'message' => "Handler for {$entity} not found."];
            $logService->log('api', 'error', "Handler file not found for entity: {$entity}");
        }
    }
} catch (Exception $e) {
    $response = ['success' => false, 'error' => 'Internal server error.'];
    $logService->log('api', 'error', "Exception: " . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
} catch (Error $e) {
    $response = ['success' => false, 'error' => 'Fatal error occurred.'];
    $logService->log('api', 'error', "Fatal Error: " . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
}

// Clean any unexpected output
ob_clean();

// Log and return the response
echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);

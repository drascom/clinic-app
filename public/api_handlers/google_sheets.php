<?php
require_once '../includes/db.php';
require_once '../auth/auth.php';

// Handle different actions based on POST data
$action = $_POST['action'] ?? 'fetch_sheets';

// Debug logging function (can be simplified for API endpoint if needed)
function debugLog($message, $data = null, $level = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "\n## Debug Log - {$timestamp}\n\n";
    $logEntry .= "**Level:** {$level}\n";
    $logEntry .= "**Message:** {$message}\n";

    if ($data !== null) {
        $logEntry .= "**Data:**\n```json\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n```\n";
    }

    $logEntry .= "---\n";

    // Append to log file
    file_put_contents(__DIR__ . '/../../logs/google_debug.log', $logEntry, FILE_APPEND | LOCK_EX);
}

// Error handling function
function handleError($errorCode, $message, $data = null)
{
    debugLog("ERROR: {$errorCode} - {$message}", $data, 'ERROR');
    return [
        'success' => false,
        'error_code' => $errorCode,
        'message' => $message,
        'data' => $data
    ];
}

// Set content type to JSON
header('Content-Type: application/json');

// Authentication Check
if (!is_logged_in()) {
    debugLog("Authentication failed for API endpoint", null, 'ERROR');
    echo json_encode(handleError('AUTH_001', 'Authentication required'));
    exit();
}

// Handle different actions
if ($action === 'process_entry') {
    handleProcessEntry();
    exit();
} elseif ($action === 'test') {
    handleTest();
    exit();
}

// Default action is fetch_sheets (existing functionality)

require __DIR__ . '/../../vendor/autoload.php'; // Adjust path if Composer is installed elsewhere

// Function to get a setting value from the database with debugging
function get_setting($key)
{
    try {
        // debugLog("Retrieving setting from database", ['key' => $key]); // Avoid excessive logging in API
        $pdo = get_db(); // Get the PDO instance using the helper function
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = :key");
        $stmt->bindValue(':key', $key, PDO::PARAM_STR); // Use PDO::PARAM_STR for string binding
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Use PDO fetch method

        $value = $row ? $row['value'] : null;
        // debugLog("Setting retrieved", ['key' => $key, 'found' => $row ? 'YES' : 'NO', 'value_length' => $value ? strlen($value) : 0]); // Avoid excessive logging
        return $value;
    } catch (Exception $e) {
        debugLog("Error retrieving setting in API", ['key' => $key, 'error' => $e->getMessage()], 'ERROR');
        return null;
    }
}

// --- Configuration ---
$credentialsPath = __DIR__ . '/../../secrets/liv-hsh-patients-18682cec86db.json'; // Adjust path relative to this file
$spreadsheetId = get_setting('spreadsheet_id'); // Get Spreadsheet ID from settings
$cacheDuration = (int) get_setting('cache_duration') ?: 3600; // Default 1 hour
$cellRange = get_setting('cell_range') ?: 'A1:Z'; // Default range

// Validate required settings
if (!$spreadsheetId) {
    debugLog("Missing spreadsheet ID in API", null, 'ERROR');
    echo json_encode(handleError('CONFIG_001', 'Spreadsheet ID not configured.'));
    exit();
}

if (!file_exists($credentialsPath)) {
    debugLog("Google API credentials file not found in API", ['path' => $credentialsPath], 'ERROR');
    echo json_encode(handleError('CONFIG_002', 'Google API credentials file not found.'));
    exit();
}

// --- Caching Logic ---
$cacheDir = __DIR__ . '/../../cache/'; // Adjust path relative to this file
$cacheFile = $cacheDir . 'sheet_data_' . md5($spreadsheetId) . '.json'; // Use md5 for a safe filename

$cachedData = null;
$usingCache = false;

if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheDuration))) {
    // Cache is valid, read from cache
    $cachedContent = file_get_contents($cacheFile);
    $cachedData = json_decode($cachedContent, true);
    if ($cachedData !== null && isset($cachedData['spreadsheetTitle'], $cachedData['sheetTitles'], $cachedData['sheetValues'])) {
        // Use cached data
        $usingCache = true;
        debugLog("Using cached data in API");
        echo json_encode([
            'success' => true,
            'data' => $cachedData,
            'from_cache' => true
        ]);
        exit();
    } else {
        // Cache file is corrupted or empty, delete it
        debugLog("Corrupted cache file found, deleting in API", ['cache_file' => basename($cacheFile)], 'WARNING');
        @unlink($cacheFile); // Use @ to suppress errors if file doesn't exist
    }
}

if (!$usingCache) {
    // Data Fetching from Google Sheets API
    debugLog("Cache not available, fetching from Google Sheets API in API endpoint");
    $apiStartTime = microtime(true);

    try {
        $client = new \Google\Client();
        $client->setApplicationName('Google Sheets PHP Fetcher');
        $client->setScopes([\Google\Service\Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig($credentialsPath);

        $service = new \Google\Service\Sheets($client);

        // --- Fetch Spreadsheet Metadata to get Sheet Titles ---
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();

        $spreadsheetTitle = $spreadsheet->getProperties()->getTitle();

        // Data Processing
        $sheetValues = [];
        $sheetTitles = [];

        foreach ($sheets as $index => $sheet) {
            $sheetTitle = $sheet->getProperties()->getTitle();

            // Validate and sanitize sheet title
            if (empty($sheetTitle)) {
                debugLog("Empty sheet title found, skipping in API", ['sheet_index' => $index], 'WARNING');
                continue;
            }

            $sheetTitles[] = $sheetTitle;

            // Construct the range properly
            $escapedSheetTitle = str_replace("'", "''", $sheetTitle); // Escape single quotes in sheet name
            $range = "'" . $escapedSheetTitle . "'!" . $cellRange; // Properly format the range

            try {
                $response = $service->spreadsheets_values->get($spreadsheetId, $range);
                $values = $response->getValues() ?? [];
                $sheetValues[$sheetTitle] = $values;
            } catch (\Google\Service\Exception $e) {
                debugLog("Error fetching data for sheet in API", [
                    'sheet_title' => $sheetTitle,
                    'range' => $range,
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage()
                ], 'ERROR');

                // Skip this sheet and continue with others
                $sheetValues[$sheetTitle] = [];
                continue;
            }
        }

        $apiEndTime = microtime(true);
        debugLog("All sheet data retrieved in API endpoint", [
            'total_sheets' => count($sheets),
            'total_api_time_ms' => round(($apiEndTime - $apiStartTime) * 1000, 2)
        ]);

        // Cache Storage
        $dataToCache = [
            'timestamp' => time(),
            'spreadsheetTitle' => $spreadsheetTitle,
            'sheetTitles' => $sheetTitles,
            'sheetValues' => $sheetValues
        ];

        // Ensure cache directory exists before writing
        if (!is_dir($cacheDir)) {
            debugLog("Creating cache directory in API", ['path' => $cacheDir]);
            mkdir($cacheDir, 0777, true);
        }

        $cacheData = json_encode($dataToCache);
        $cacheWriteResult = file_put_contents($cacheFile, $cacheData);

        debugLog("Cache saved in API", [
            'cache_file' => basename($cacheFile),
            'cache_size_bytes' => $cacheWriteResult
        ]);

        echo json_encode([
            'success' => true,
            'data' => $dataToCache,
            'from_cache' => false
        ]);
    } catch (\Google\Service\Exception $e) {
        $error = handleError('API_002', 'Google Service Exception in API', [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'errors' => $e->getErrors()
        ]);
        echo json_encode($error);
        exit();
    } catch (\Exception $e) {
        $error = handleError('API_003', 'General API Exception in API', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        echo json_encode($error);
        exit();
    }
}

// Auto-import helper functions
function handleTest()
{
    debugLog("Starting test action");

    $tests = [];

    // Test database connection
    try {
        $db = get_db();
        $tests['database'] = 'OK';
    } catch (Exception $e) {
        $tests['database'] = 'ERROR: ' . $e->getMessage();
    }

    // Test autoloader
    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $tests['autoloader'] = 'OK';
    } else {
        $tests['autoloader'] = 'ERROR: Autoloader not found';
    }

    // Test Google Client
    try {
        if (class_exists('Google\Client')) {
            $tests['google_client'] = 'OK';
        } else {
            $tests['google_client'] = 'ERROR: Google Client class not found';
        }
    } catch (Exception $e) {
        $tests['google_client'] = 'ERROR: ' . $e->getMessage();
    }

    // Test credentials file
    $credentialsPath = __DIR__ . '/../../secrets/liv-hsh-patients-18682cec86db.json';
    if (file_exists($credentialsPath)) {
        $tests['credentials'] = 'OK';
    } else {
        $tests['credentials'] = 'ERROR: Credentials file not found';
    }

    echo json_encode([
        'success' => true,
        'tests' => $tests
    ]);
}

function handleProcessEntry()
{
    debugLog("Starting process_entry action", [
        'date_str' => $_POST['date_str'] ?? '',
        'patient_name' => $_POST['patient_name'] ?? '',
        'entry_type' => $_POST['entry_type'] ?? 'surgery'
    ]);

    $dateStr = $_POST['date_str'] ?? '';
    $patientName = $_POST['patient_name'] ?? '';
    $entryType = $_POST['entry_type'] ?? 'surgery';
    $originalEntry = $_POST['original_entry'] ?? '';

    try {
        $db = get_db();

        // Clean patient name
        $cleanedName = cleanPatientName($patientName);

        // Parse date
        $date = parseDate($dateStr);
        if (!$date) {
            throw new Exception('Invalid date format');
        }

        // Check if patient exists
        $patientId = findPatientByName($cleanedName, $db);
        $patientCreated = false;

        if (!$patientId) {
            // Create new patient
            $patientId = createPatient($cleanedName, $db);
            if (!$patientId) {
                throw new Exception('Failed to create patient');
            }
            $patientCreated = true;
        }

        $recordCreated = false;
        $recordType = '';

        if ($entryType === 'surgery') {
            // Check if surgery already exists
            if (!surgeryExists($patientId, $date, $db)) {
                // Create surgery
                $surgeryId = createSurgery($patientId, $date, $db);
                if (!$surgeryId) {
                    throw new Exception('Failed to create surgery');
                }
                $recordCreated = true;
                $recordType = 'surgery';
            }
        } else {
            // Handle consultations - create appointment record
            if (!appointmentExists($patientId, $date, $db)) {
                $appointmentId = createAppointment($patientId, $date, $entryType, $db, $originalEntry);
                if (!$appointmentId) {
                    throw new Exception('Failed to create appointment');
                }
                $recordCreated = true;
                $recordType = 'appointment';
            }
        }

        echo json_encode([
            'success' => true,
            'patient_created' => $patientCreated,
            'record_created' => $recordCreated,
            'record_type' => $recordType,
            'entry_type' => $entryType
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Helper functions for auto-import
function cleanPatientName($name)
{
    // Remove C-, V2V, F2F, VIDEO prefixes and any phone numbers
    $cleaned = preg_replace('/^(C|V2V|F2F|VIDEO)\s*-\s*/', '', trim($name));
    // Remove phone numbers (+ followed by digits, spaces, dashes)
    $cleaned = preg_replace('/\s*-?\s*\+?\d+[\d\s\-]*$/', '', $cleaned);
    return trim($cleaned);
}

function parseDate($dateStr)
{
    if (empty($dateStr))
        return null;

    $fullDateStr = $dateStr . ' 2025';
    $dateObj = new DateTime($fullDateStr);
    return $dateObj->format('Y-m-d');
}

function findPatientByName($name, $db)
{
    try {
        $stmt = $db->prepare("SELECT id FROM patients WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    } catch (PDOException $e) {
        debugLog("Error finding patient", ['name' => $name, 'error' => $e->getMessage()], 'ERROR');
        return null;
    }
}

function createPatient($name, $db)
{
    try {
        $stmt = $db->prepare("INSERT INTO patients (name, agency_id, created_at, updated_at) VALUES (?, 2, datetime('now'), datetime('now'))");
        $stmt->execute([$name]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        debugLog("Error creating patient", ['name' => $name, 'error' => $e->getMessage()], 'ERROR');
        return null;
    }
}

function surgeryExists($patientId, $date, $db)
{
    try {
        $stmt = $db->prepare("SELECT id FROM surgeries WHERE patient_id = ? AND date = ? LIMIT 1");
        $stmt->execute([$patientId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        debugLog("Error checking surgery existence", ['patient_id' => $patientId, 'date' => $date, 'error' => $e->getMessage()], 'ERROR');
        return false;
    }
}

function createSurgery($patientId, $date, $db)
{
    try {
        $stmt = $db->prepare("
            INSERT INTO surgeries (patient_id, date, room_id, status, graft_count, notes, is_recorded, created_at, updated_at)
            VALUES (?, ?, 1, 'sheduled', 0, 'Auto-imported from Google Sheets', 1, datetime('now'), datetime('now'))
        ");
        $stmt->execute([$patientId, $date]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        debugLog("Error creating surgery", ['patient_id' => $patientId, 'date' => $date, 'error' => $e->getMessage()], 'ERROR');
        return null;
    }
}

function appointmentExists($patientId, $date, $db)
{
    try {
        $stmt = $db->prepare("SELECT id FROM appointments WHERE patient_id = ? AND appointment_date = ? LIMIT 1");
        $stmt->execute([$patientId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        debugLog("Error checking appointment existence", ['patient_id' => $patientId, 'date' => $date, 'error' => $e->getMessage()], 'ERROR');
        return false;
    }
}

function createAppointment($patientId, $date, $type, $db, $originalEntry = '')
{
    try {
        $notes = 'Auto-imported from Google Sheets - ' . $type;

        // Extract time from original entry (e.g., "2pm", "14:00", "9:30am")
        $startTime = '09:00'; // Default fallback
        if (preg_match('/(\d{1,2}):?(\d{0,2})\s*(am|pm)/i', $originalEntry, $matches)) {
            $hour = intval($matches[1]);
            $minute = isset($matches[2]) && $matches[2] !== '' ? intval($matches[2]) : 0;
            $ampm = strtolower($matches[3]);

            // Convert to 24-hour format
            if ($ampm === 'pm' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }

            $startTime = sprintf('%02d:%02d', $hour, $minute);
        } elseif (preg_match('/(\d{1,2}):(\d{2})/', $originalEntry, $matches)) {
            // 24-hour format like "14:30"
            $startTime = sprintf('%02d:%02d', intval($matches[1]), intval($matches[2]));
        }

        // Calculate end time (30 minutes later)
        $startDateTime = new DateTime($date . ' ' . $startTime);
        $endDateTime = clone $startDateTime;
        $endDateTime->add(new DateInterval('PT30M'));
        $endTime = $endDateTime->format('H:i');

        $roomId = 4; // Default room

        $stmt = $db->prepare("
            INSERT INTO appointments (room_id, patient_id, appointment_date, start_time, end_time, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $stmt->execute([$roomId, $patientId, $date, $startTime, $endTime, $notes]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        debugLog("Error creating appointment", ['patient_id' => $patientId, 'date' => $date, 'type' => $type, 'error' => $e->getMessage()], 'ERROR');
        return null;
    }
}

<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../services/LogService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$logService = new LogService();

// Handle different actions based on POST data
$action = null;
$input_data = [];

if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $json_input = file_get_contents('php://input');
    $input_data = json_decode($json_input, true) ?: [];
    $action = $input_data['action'] ?? null;
} else {
    $action = $_POST['action'] ?? 'fetch_sheets';
    $input_data = $_POST;
}

// Error handling function
function handleError($errorCode, $message, $data = null)
{
    global $logService;
    $logService->log('google_sheets_api', 'error', "{$errorCode} - {$message}", $data);
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
    $logService->log('google_sheets_api', 'error', 'Authentication failed for API endpoint');
    echo json_encode(handleError('AUTH_001', 'Authentication required'));
    exit();
}

// Handle different actions
if ($action === 'process_entry') {
    handleProcessEntry($input_data);
    exit();
} elseif ($action === 'test') {
    handleTest();
    exit();
}

// Default action is fetch_sheets (existing functionality)

// Function to get a setting value from the database with debugging
function get_setting($key)
{
    global $logService;
    try {
        $pdo = get_db(); // Get the PDO instance using the helper function
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = :key");
        $stmt->bindValue(':key', $key, PDO::PARAM_STR); // Use PDO::PARAM_STR for string binding
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Use PDO fetch method

        $value = $row ? $row['value'] : null;
        return $value;
    } catch (Exception $e) {
        $logService->log('google_sheets_api', 'error', "Error retrieving setting in API", ['key' => $key, 'error' => $e->getMessage()]);
        return null;
    }
}

// --- Configuration ---
$credentialsPath = __DIR__ . '/../../secrets/google-sheets-api.json'; // Adjust path relative to this file
$spreadsheetId = get_setting('spreadsheet_id'); // Get Spreadsheet ID from settings
$cacheDuration = (int) get_setting('cache_duration') ?: 3600; // Default 1 hour
$cellRange = get_setting('cell_range') ?: 'A1:Z'; // Default range

// Validate required settings
if (!$spreadsheetId) {
    $logService->log('google_sheets_api', 'error', "Missing spreadsheet ID in API");
    echo json_encode(handleError('CONFIG_001', 'Spreadsheet ID not configured.'));
    exit();
}

if (!file_exists($credentialsPath)) {
    $logService->log('google_sheets_api', 'error', "Google API credentials file not found in API", ['path' => $credentialsPath]);
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
        echo json_encode([
            'success' => true,
            'data' => $cachedData,
            'from_cache' => true
        ]);
        exit();
    } else {
        // Cache file is corrupted or empty, delete it
        @unlink($cacheFile); // Use @ to suppress errors if file doesn't exist
    }
}

if (!$usingCache) {
    // Data Fetching from Google Sheets API
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
                $logService->log('google_sheets_api', 'error', "Error fetching data for sheet in API", [
                    'sheet_title' => $sheetTitle,
                    'range' => $range,
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage()
                ]);

                // Skip this sheet and continue with others
                $sheetValues[$sheetTitle] = [];
                continue;
            }
        }

        $apiEndTime = microtime(true);

        // Cache Storage
        $dataToCache = [
            'timestamp' => time(),
            'spreadsheetTitle' => $spreadsheetTitle,
            'sheetTitles' => $sheetTitles,
            'sheetValues' => $sheetValues
        ];

        // Ensure cache directory exists before writing
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $cacheData = json_encode($dataToCache);
        $cacheWriteResult = file_put_contents($cacheFile, $cacheData);

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
    $credentialsPath = __DIR__ . '/../../secrets/google-sheets-api.json';
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

function handleProcessEntry($data)
{
    $entries = $data['entries'] ?? [];

    if (empty($entries) || !is_array($entries)) {
        echo json_encode(['success' => false, 'error' => 'No entries provided or invalid format.']);
        return;
    }

    $db = get_db();
    $results = [];
    $errors = [];

    foreach ($entries as $item) {
        $dateStr = $item['date_str'] ?? '';
        $originalEntry = $item['original_entry'] ?? '';

        try {
            $date = parseDate($dateStr);
            if (!$date) {
                throw new Exception("Invalid date format for entry: {$originalEntry}");
            }

            $parsedEntries = parseComplexEntry($originalEntry);

            foreach ($parsedEntries as $entry) {
                $patientName = $entry['name'];
                $entryType = $entry['type'];

                // Check if patient exists
                $patientId = findPatientByName($patientName, $db);
                $patientCreated = false;

                if (!$patientId) {
                    // Create new patient
                    $patientId = createPatient($patientName, $db, $_SESSION['user_id']);
                    if (!$patientId) {
                        throw new Exception('Failed to create patient: ' . $patientName);
                    }
                    $patientCreated = true;
                }

                $recordCreated = false;
                $recordType = '';

                if ($entryType === 'surgery') {
                    if (!surgeryExists($patientId, $date, $db)) {
                        $surgeryId = createSurgery($patientId, $date, $db);
                        if (!$surgeryId) {
                            throw new Exception('Failed to create surgery for ' . $patientName);
                        }
                        $recordCreated = true;
                        $recordType = 'surgery';

                        // Create a pre-surgery consultation appointment
                        if (!preSurgeryAppointmentExists($patientId, $date, $db)) {
                            $appointmentNotes = 'Pre-surgery consultation';
                            $stmt = $db->prepare("
                                INSERT INTO appointments (room_id, patient_id, appointment_date, start_time, end_time, procedure_id, appointment_type, consultation_type, notes, created_by)
                                VALUES (3, ?, ?, '08:30', '09:00', 1, 'consultation', 'face-to-face', ?, 1)
                            ");
                            $stmt->execute([$patientId, $date, $appointmentNotes]);
                        }
                    }
                } else { // Consultation
                    if (!appointmentExists($patientId, $date, $db)) {
                        $appointmentId = createAppointment($patientId, $date, $entryType, $db, $originalEntry);
                        if (!$appointmentId) {
                            throw new Exception('Failed to create appointment for ' . $patientName);
                        }
                        $recordCreated = true;
                        $recordType = 'appointment';
                    }
                }

                $results[] = [
                    'original_entry' => $originalEntry,
                    'patient_name' => $patientName,
                    'patient_created' => $patientCreated,
                    'record_created' => $recordCreated,
                    'record_type' => $recordType,
                    'status' => 'success'
                ];
            }
        } catch (Exception $e) {
            $errors[] = [
                'original_entry' => $originalEntry,
                'error' => $e->getMessage()
            ];
        }
    }

    echo json_encode([
        'success' => empty($errors),
        'results' => $results,
        'errors' => $errors
    ]);
}

function parseComplexEntry($entry)
{
    $appointments = [];
    // Split by common delimiters for multiple appointments in one cell
    $delimiters = ['/', "\n"];
    // The regex looks for a space, a slash, and a space, OR a newline.
    $pattern = '/\s\/\s|\n/';
    $parts = preg_split($pattern, $entry, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part))
            continue;

        $name = cleanPatientName($part);
        $type = extractEntryType($part);

        $appointments[] = [
            'name' => $name,
            'type' => $type,
            'original' => $part
        ];
    }

    return $appointments;
}

function extractEntryType($entry)
{
    if (preg_match('/\b(F2F)\b/i', $entry))
        return 'F2F';
    if (preg_match('/\b(V2V)\b/i', $entry))
        return 'V2V';
    if (preg_match('/\b(VIDEO)\b/i', $entry))
        return 'V2V';
    if (preg_match('/\b(C-)\b/i', $entry))
        return 'F2F'; // Assuming C- is a form of face-to-face
    return 'surgery'; // Default
}


// Helper functions for auto-import
function cleanPatientName($name)
{
    // Remove prefixes like "C - ", "R - ", "V2V ", etc.
    $cleaned = preg_replace('/^([CRV]|V2V|F2F|VIDEO)\s*-\s*/i', '', trim($name));

    // Remove suffixes like " -"
    $cleaned = preg_replace('/-\s*$/', '', $cleaned);

    // Remove f2f and v2v from anywhere in the name
    $cleaned = preg_replace('/\s*(f2f|v2v)\s*/i', ' ', $cleaned);

    // Remove phone numbers and times
    $cleaned = preg_replace('/\s*\+\d+[\d\s\-]*\d{1,2}(am|pm)/i', '', $cleaned);
    $cleaned = preg_replace('/\s*\+\d+[\d\s\-]*/', '', $cleaned);
    $cleaned = preg_replace('/\s+\d{1,2}(am|pm)/i', '', $cleaned);

    // Replace multiple spaces with a single space
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);

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
    global $logService;
    try {
        $stmt = $db->prepare("SELECT id FROM patients WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    } catch (PDOException $e) {
        $logService->log('google_sheets_api', 'error', "Error finding patient", ['name' => $name, 'error' => $e->getMessage()]);
        return null;
    }
}

function createPatient($name, $db, $created_by)
{
    global $logService;
    try {
        // Set a default agency_id, you may want to make this dynamic
        $agency_id = 2; // Default agency
        $stmt = $db->prepare("
            INSERT INTO patients (name, agency_id, created_at, updated_at, created_by)
            VALUES (?, ?, datetime('now'), datetime('now'), ?)
        ");
        $stmt->execute([$name, $agency_id, $created_by]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        $logService->log('google_sheets_api', 'error', "Error creating patient", [
            'name' => $name,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

function surgeryExists($patientId, $date, $db)
{
    global $logService;
    try {
        $stmt = $db->prepare("SELECT id FROM surgeries WHERE patient_id = ? AND date = ? LIMIT 1");
        $stmt->execute([$patientId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        $logService->log('google_sheets_api', 'error', "Error checking surgery existence", ['patient_id' => $patientId, 'date' => $date, 'error' => $e->getMessage()]);
        return false;
    }
}

function createSurgery($patientId, $date, $db)
{
    global $logService;
    try {
        $stmt = $db->prepare("
            INSERT INTO surgeries (patient_id, date, room_id, status, predicted_grafts_count, notes, is_recorded, created_at, updated_at, created_by)
            VALUES (?, ?, 3, 'scheduled', 0, 'Auto-imported from Google Sheets', 1, datetime('now'), datetime('now'), 1)
        ");
        $stmt->execute([$patientId, $date]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        $logService->log('google_sheets_api', 'error', "Error creating surgery", ['patient_id' => $patientId, 'date' => $date, 'error' => $e->getMessage()]);
        return null;
    }
}

function appointmentExists($patientId, $date, $db)
{
    global $logService;
    try {
        $stmt = $db->prepare("SELECT id FROM appointments WHERE patient_id = ? AND appointment_date = ? LIMIT 1");
        $stmt->execute([$patientId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        $logService->log('google_sheets_api', 'error', "Error checking appointment existence", ['patient_id' => $patientId, 'date' => $date, 'error' => $e->getMessage()]);
        return false;
    }
}

function preSurgeryAppointmentExists($patientId, $date, $db)
{
    global $logService;
    try {
        $stmt = $db->prepare("SELECT id FROM appointments WHERE patient_id = ? AND appointment_date = ? AND start_time = '08:30' LIMIT 1");
        $stmt->execute([$patientId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        $logService->log('google_sheets_api', 'error', "Error checking pre-surgery appointment existence", ['patient_id' => $patientId, 'date' => $date, 'error' => $e->getMessage()]);
        return false; // Assume it doesn't exist on error to be safe
    }
}

function createAppointment($patientId, $date, $type, $db, $originalEntry = '')
{
    global $logService;
    try {
        $notes = 'Auto-imported from Google Sheets - ' . $type;

        // Determine consultation type
        $consultationType = null;
        if (strcasecmp($type, 'F2F') === 0) {
            $consultationType = 'face-to-face';
        } elseif (strcasecmp($type, 'V2V') === 0) {
            $consultationType = 'video-to-video';
        }

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

        $roomId = 1; // Default room

        $stmt = $db->prepare("
            INSERT INTO appointments (room_id, patient_id, procedure_id, appointment_date, start_time, end_time, notes, consultation_type, created_at, updated_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'), 1)
        ");
        $stmt->execute([$roomId, $patientId, 1, $date, $startTime, $endTime, $notes, $consultationType]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        $logService->log('google_sheets_api', 'error', "Error creating appointment", ['patient_id' => $patientId, 'date' => $date, 'type' => $type, 'error' => $e->getMessage()]);
        return null;
    }
}

<?php
require_once __DIR__ . '/../services/LogService.php';

// Database connection details for SQLite
$database_file = __DIR__ . '/../../db/database.sqlite'; // Correct path to database file in db folder
$lock_file = __DIR__ . '/../../db/database.lock'; // Lock file to prevent race conditions

$logService = new LogService();

/**
 * Initialize database with proper error handling and atomic operations
 */
function initialize_database($database_file, $sql_file)
{
    global $logService;
    $logService->log('db', 'info', "Starting database initialization process.");

    // Create a temporary database file first
    $temp_db_file = $database_file . '.tmp';

    try {
        // Remove temp file if it exists from previous failed attempts
        if (file_exists($temp_db_file)) {
            unlink($temp_db_file);
            $logService->log('db', 'info', "Removed existing temporary database file.");
        }

        // Create PDO connection to temporary database
        $temp_pdo = new PDO("sqlite:" . $temp_db_file);
        $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Begin transaction for atomic operation
        $temp_pdo->beginTransaction();

        // Read and execute SQL file
        if (!file_exists($sql_file)) {
            throw new Exception("database.sql file not found at " . $sql_file);
        }

        $sql = file_get_contents($sql_file);
        if ($sql === false) {
            throw new Exception("Failed to read database.sql file.");
        }

        $logService->log('db', 'info', "Executing database schema and data from database.sql");
        // Split SQL into individual statements and execute them one by one for better error reporting
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                try {
                    $logService->log('db', 'info', "Executing SQL statement: " . substr($stmt, 0, 100) . "...");
                    $temp_pdo->exec($stmt . ';');
                    $logService->log('db', 'info', "SQL statement executed successfully.");
                } catch (PDOException $e) {
                    $logService->log('db', 'error', "SQL statement failed: " . $e->getMessage() . " in statement: " . substr($stmt, 0, 200) . "...", ['file' => $e->getFile(), 'line' => $e->getLine()]);
                    throw $e; // Re-throw to be caught by the main catch block
                }
            }
        }

        // Verify that essential tables and data exist
        $tables_to_check = ['users', 'staff', 'procedures', 'agencies', 'photo_album_types'];
        foreach ($tables_to_check as $table) {
            $count = $temp_pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $logService->log('db', 'info', "Table '$table' has $count rows after initialization.", ['table' => $table, 'row_count' => $count]);

            // Ensure critical tables have data
            if (in_array($table, ['photo_album_types', 'agencies', 'procedures']) && $count == 0) {
                throw new Exception("Critical table '$table' is empty after initialization.");
            }
        }

        // Commit transaction
        $temp_pdo->commit();
        $temp_pdo = null; // Close connection

        // Atomically move temp database to final location
        if (!rename($temp_db_file, $database_file)) {
            throw new Exception("Failed to move temporary database to final location.");
        }

        return true;
    } catch (Exception $e) {
        $logService->log('db', 'error', "Database initialization failed: " . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

        // Cleanup: rollback transaction if still active
        if (isset($temp_pdo) && $temp_pdo->inTransaction()) {
            $temp_pdo->rollback();
        }

        // Remove temporary database file
        if (file_exists($temp_db_file)) {
            unlink($temp_db_file);
        }

        // Remove corrupted main database file if it exists
        if (file_exists($database_file)) {
            unlink($database_file);
            $logService->log('db', 'warning', "Removed corrupted database file.");
        }

        throw $e;
    }
}

$db_exists = file_exists($database_file);
// $logService->log('db', 'info', "Is database exists: " . ($db_exists ? 'Yes' : 'No'));

// Handle database creation with file locking to prevent race conditions
if (!$db_exists) {
    $logService->log('db', 'info', "Database does not exist. Acquiring lock for creation.");

    // Use file locking to prevent multiple processes from creating database simultaneously
    $lock_handle = fopen($lock_file, 'w');
    if (!$lock_handle) {
        $logService->log('db', 'error', "Failed to create lock file.");
        die("Database initialization failed. Please try again later.");
    }

    if (flock($lock_handle, LOCK_EX | LOCK_NB)) {
        $logService->log('db', 'info', "Lock acquired. Checking if database was created by another process.");

        // Double-check if database was created while waiting for lock
        if (!file_exists($database_file)) {
            try {
                $sql_file = __DIR__ . '/../../db/database.sql';
                initialize_database($database_file, $sql_file);
            } catch (Exception $e) {
                flock($lock_handle, LOCK_UN);
                fclose($lock_handle);
                unlink($lock_file);
                $logService->log('db', 'error', "Database initialization failed during lock: " . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
                die("Database initialization failed. Please try again later.");
            }
        } else {
            $logService->log('db', 'info', "Database was created by another process while waiting for lock.");
        }

        flock($lock_handle, LOCK_UN);
    } else {
        $logService->log('db', 'warning', "Could not acquire lock. Another process is creating the database.");
        // Wait a bit and check if database was created
        fclose($lock_handle);
        sleep(1);

        // Check again if database exists
        if (!file_exists($database_file)) {
            $logService->log('db', 'warning', "Database still does not exist after waiting.");
            die("Database initialization in progress. Please refresh the page in a moment.");
        }
    }

    fclose($lock_handle);
    unlink($lock_file);
}

// Create PDO connection with retry logic
$max_retries = 3;
$retry_count = 0;
$pdo = null;

while ($retry_count < $max_retries && $pdo === null) {
    try {
        $pdo = new PDO("sqlite:" . $database_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Test the connection by running a simple query
        $pdo->query("SELECT 1")->fetchColumn();
    } catch (\PDOException $e) {
        $retry_count++;
        $logService->log('db', 'error', "Database connection attempt $retry_count failed: SQLSTATE [" . $e->getCode() . "] " . $e->getMessage(), ['retry_count' => $retry_count]);

        if ($retry_count < $max_retries) {
            $logService->log('db', 'info', "Retrying database connection in 1 second...");
            sleep(1);
        } else {
            $logService->log('db', 'critical', "All database connection attempts failed.");
            die("Database connection failed after multiple attempts. Please try again later.");
        }
    }
}



function get_db()
{
    global $pdo;
    return $pdo;
}

/**
 * Recursively searches for database.sqlite starting from the project root.
 *
 * @param string $start_dir The directory to start the search from. Defaults to the project root.
 * @return array An array of absolute paths to database.sqlite files found.
 */
function find_database_sqlite($start_dir = __DIR__ . '/../../')
{
    $matches = [];
    // Use @ to suppress warnings for unreadable directories
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($start_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isFile() && $fileinfo->getFilename() === 'database.sqlite') {
            $matches[] = $fileinfo->getRealPath();
        }
    }

    return $matches;
}

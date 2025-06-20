<?php
// Database connection details for SQLite
$database_file = __DIR__ . '/../../db/database.sqlite'; // Correct path to database file in db folder
$lock_file = __DIR__ . '/../../db/database.lock'; // Lock file to prevent race conditions

// Ensure log_to_file function is defined before use
if (!function_exists('log_to_file')) {
    /**
     * Logs a message to the log.md file.
     *
     * @param string $message The message to log.
     */
    function log_to_file($message)
    {
        $log_file = __DIR__ . '/../../logs/db.log'; // Path to log.md in the public directory
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}" . PHP_EOL;

        // Use FILE_APPEND to add to the end of the file, and LOCK_EX to prevent race conditions
        @file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX); // Use @ to suppress potential warnings
    }
}

/**
 * Initialize database with proper error handling and atomic operations
 */
function initialize_database($database_file, $sql_file)
{
    log_to_file("Starting database initialization process.");

    // Create a temporary database file first
    $temp_db_file = $database_file . '.tmp';

    try {
        // Remove temp file if it exists from previous failed attempts
        if (file_exists($temp_db_file)) {
            unlink($temp_db_file);
            log_to_file("Removed existing temporary database file.");
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

        log_to_file("Executing database schema and data from database.sql");
        $temp_pdo->exec($sql);

        // Verify that essential tables and data exist
        $tables_to_check = ['users', 'staff', 'procedures', 'agencies', 'photo_album_types'];
        foreach ($tables_to_check as $table) {
            $count = $temp_pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            log_to_file("Table '$table' has $count rows after initialization.");

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

        log_to_file("Database initialization completed successfully.");
        return true;
    } catch (Exception $e) {
        log_to_file("Database initialization failed: " . $e->getMessage());

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
            log_to_file("Removed corrupted database file.");
        }

        throw $e;
    }
}

log_to_file("Is database exists: ");
$db_exists = file_exists($database_file);
log_to_file($db_exists ? 'Yes' : 'No');

// Handle database creation with file locking to prevent race conditions
if (!$db_exists) {
    log_to_file("Database does not exist. Acquiring lock for creation.");

    // Use file locking to prevent multiple processes from creating database simultaneously
    $lock_handle = fopen($lock_file, 'w');
    if (!$lock_handle) {
        log_to_file("Failed to create lock file.");
        die("Database initialization failed. Please try again later.");
    }

    if (flock($lock_handle, LOCK_EX | LOCK_NB)) {
        log_to_file("Lock acquired. Checking if database was created by another process.");

        // Double-check if database was created while waiting for lock
        if (!file_exists($database_file)) {
            try {
                $sql_file = __DIR__ . '/../../db/database.sql';
                initialize_database($database_file, $sql_file);
            } catch (Exception $e) {
                flock($lock_handle, LOCK_UN);
                fclose($lock_handle);
                unlink($lock_file);
                log_to_file("Database initialization failed: " . $e->getMessage());
                die("Database initialization failed. Please try again later.");
            }
        } else {
            log_to_file("Database was created by another process while waiting for lock.");
        }

        flock($lock_handle, LOCK_UN);
    } else {
        log_to_file("Could not acquire lock. Another process is creating the database.");
        // Wait a bit and check if database was created
        fclose($lock_handle);
        sleep(1);

        // Check again if database exists
        if (!file_exists($database_file)) {
            log_to_file("Database still does not exist after waiting.");
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
        log_to_file("PDO instance created and tested successfully.");
    } catch (\PDOException $e) {
        $retry_count++;
        log_to_file("Database connection attempt $retry_count failed: SQLSTATE [" . $e->getCode() . "] " . $e->getMessage());

        if ($retry_count < $max_retries) {
            log_to_file("Retrying database connection in 1 second...");
            sleep(1);
        } else {
            log_to_file("All database connection attempts failed.");
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

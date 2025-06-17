<?php
/**
 * Migration: Remove UNIQUE constraint from technicians.name field
 * This allows multiple technicians to have the same name
 */

require_once __DIR__ . '/../includes/db.php';

try {
    echo "Starting migration: Remove UNIQUE constraint from technicians.name field\n";

    // Begin transaction
    $pdo->beginTransaction();

    // Step 1: Create a backup of the current technicians table
    echo "Creating backup of technicians table...\n";
    $pdo->exec("CREATE TABLE technicians_backup AS SELECT * FROM technicians");

    // Step 2: Drop the current technicians table
    echo "Dropping current technicians table...\n";
    $pdo->exec("DROP TABLE technicians");

    // Step 3: Recreate the technicians table without the UNIQUE constraint on name
    echo "Recreating technicians table without UNIQUE constraint on name...\n";
    $pdo->exec("
        CREATE TABLE technicians (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            specialty TEXT NULL,
            phone TEXT NULL,
            status TEXT NULL CHECK (status IN ('Employed','Self Employed','Sponsorship' )),
            period TEXT NULL CHECK (period IN ('Full Time', 'Part Time')),
            notes TEXT NULL,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Step 4: Restore all data from the backup
    echo "Restoring data from backup...\n";
    $pdo->exec("
        INSERT INTO technicians (id, name, specialty, phone, status, period, notes, is_active, created_at)
        SELECT id, name, specialty, phone, status, period, notes, is_active, created_at
        FROM technicians_backup
    ");

    // Step 5: Recreate the index for better performance
    echo "Recreating performance index...\n";
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_technicians_active ON technicians(is_active)");

    // Step 6: Clean up the backup table
    echo "Cleaning up backup table...\n";
    $pdo->exec("DROP TABLE technicians_backup");

    // Verify the migration
    $stmt = $pdo->query("SELECT COUNT(*) as total_technicians FROM technicians");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Commit transaction
    $pdo->commit();

    echo "Migration completed successfully!\n";
    echo "Total technicians preserved: " . $result['total_technicians'] . "\n";
    echo "Technicians can now have duplicate names.\n";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }

    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "Database has been rolled back to previous state.\n";
    exit(1);
}

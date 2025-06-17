-- Migration: Remove UNIQUE constraint from technicians.name field
-- This allows multiple technicians to have the same name

-- Step 1: Create a backup of the current technicians table
CREATE TABLE technicians_backup AS SELECT * FROM technicians;

-- Step 2: Drop the current technicians table
DROP TABLE technicians;

-- Step 3: Recreate the technicians table without the UNIQUE constraint on name
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
);

-- Step 4: Restore all data from the backup
INSERT INTO technicians (id, name, specialty, phone, status, period, notes, is_active, created_at)
SELECT id, name, specialty, phone, status, period, notes, is_active, created_at
FROM technicians_backup;

-- Step 5: Recreate the index for better performance
CREATE INDEX IF NOT EXISTS idx_technicians_active ON technicians(is_active);

-- Step 6: Clean up the backup table
DROP TABLE technicians_backup;

-- Verify the migration
SELECT COUNT(*) as total_technicians FROM technicians;

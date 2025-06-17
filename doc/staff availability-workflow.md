# Staff Availability Workflow Analysis

This document details the functionality of the `public/api_handlers/staff_availability.php` handler and identifies a critical issue related to its database interactions.

## `handle_staff_availability` Function Overview

The `handle_staff_availability` function serves as a central API endpoint for managing staff availability. It processes various actions based on the `$action` parameter, `$method` (HTTP method), `$db` (PDO database connection), and an optional `$input` array.

### 1. `byRange`
*   **Purpose**: Retrieves availability data for a specific staff member within a given date range. Intended for staff to manage their own availability.
*   **Input Values**: `start` (YYYY-MM-DD), `end` (YYYY-MM-DD) from `$input`, and `staff_id` from `get_user_id()`.
*   **Database Interaction**: Queries the `staff` table (aliased as `sa`) for `id`, `available_on`, `period`, `staff_name`, and `specialty` within the specified date range and `staff_id`.

### 2. `byRangeAll`
*   **Purpose**: Retrieves availability data for all staff members within a given date range. Requires admin or editor permissions.
*   **Input Values**: `start` (YYYY-MM-DD), `end` (YYYY-MM-DD) from `$input`. Permissions checked via `is_admin()` and `is_editor()`.
*   **Database Interaction**: Queries the `staff` table (aliased as `sa`) for `staff_id`, `available_on`, `period`, and `staff_name` within the specified date range.

### 3. `toggleDayAdmin`
*   **Purpose**: Toggles the full-day availability for any staff member on a specific date. Requires admin or editor permissions. If availability exists, it's removed; otherwise, a 'full' day availability is added.
*   **Input Values**: `staff_id`, `date` (YYYY-MM-DD) from `$input`. Permissions checked via `is_admin()` and `is_editor()`.
*   **Database Interaction**: Checks for existing availability in the `staff` table, then performs `DELETE` or `INSERT` operations on the `staff` table to toggle 'full' day availability.

### 4. `byDate`
*   **Purpose**: Retrieves available staff members for a specific date, with an optional period filter ('am', 'pm', 'full').
*   **Input Values**: `date` (YYYY-MM-DD) from `$input`, optional `period` from `$input`.
*   **Database Interaction**: Joins `staff` (aliased as `s`) and `staff` (aliased as `sa`) tables, filtering by `available_on` and `is_active`. Optionally filters by `period`.

### 5. `set` / `add`
*   **Purpose**: Adds a new availability record for a staff member on a specific date and period. Handles conflicts with 'full' day availability.
*   **Input Values**: `staff_id`, `available_on` (or `date`), `period` from `$_POST` or `$input`.
*   **Database Interaction**: Checks staff existence and activity in the `staff` table. Performs `DELETE` operations to resolve period conflicts, then `INSERT` into the `staff` table. Fetches the newly created record.

### 6. `edit`
*   **Purpose**: Edits an existing availability record.
*   **Input Values**: `id`, `staff_id`, `available_on` (or `date`), `period` from `$_POST` or `$input`.
*   **Database Interaction**: Checks if the record exists in the `staff` table, then `UPDATE`s the `staff` table.

### 7. `unset` / `delete`
*   **Purpose**: Removes an availability record by its ID.
*   **Input Values**: `id` from `$_POST`, `$input`, or `$_GET`.
*   **Database Interaction**: Fetches details from the `staff` table before performing a `DELETE` operation on the `staff` table.

### 8. `list`
*   **Purpose**: Lists availability records with optional filters for staff ID and date.
*   **Input Values**: Optional `staff_id` and `date` from `$input`.
*   **Database Interaction**: Joins `staff` (aliased as `sa`) and `staff` (aliased as `s`) tables, filtering by `is_active` and optional `staff_id` and `date`.

### 9. `getAvailability` (Legacy)
*   **Purpose**: Retrieves availability for a specific month for the current staff member.
*   **Input Values**: `month` (YYYY-MM) from `$input`, `staff_id` from `get_staff_id()`.
*   **Database Interaction**: Queries the `staff` table for `available_on` within the calculated month range.

### 10. `toggleDay`
*   **Purpose**: Toggles the 'full' day availability for the current staff member on a specific date.
*   **Input Values**: `date` (YYYY-MM-DD) from `$input`, `staff_id` from `get_staff_id()`.
*   **Database Interaction**: Checks for 'full' day availability in the `staff` table. Performs `DELETE` or `INSERT` operations on the `staff` table, clearing any 'am'/'pm' entries if 'full' is added.

---

## Identified Problem: Database Schema Mismatch

**Diagnosis**:
The `public/api_handlers/staff_availability.php` handler consistently attempts to query and manipulate columns such as `available_on` and `period` directly within the `staff` table. However, a review of the `db/database.sql` schema reveals that these columns (`available_on`, `period`) are **not defined** in the `staff` table.

The `staff` table schema in `db/database.sql` is:
```sql
CREATE TABLE IF NOT EXISTS staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT NOT NULL,
    email TEXT NOT NULL,
    location TEXT NOT NULL,
    position_applied TEXT NULL,
    staff_type TEXT DEFAULT 'candidate' CHECK (staff_type IN ('candidate', 'staff')),
    is_active INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```
This table contains staff *details*, not their availability. There is a `technician_availability` table defined, which correctly stores `available_on` and `period` for technicians, but no equivalent for general staff.

**Impact**:
All SQL queries within `staff_availability.php` that reference `sa.available_on` or `sa.period` (where `sa` is an alias for the `staff` table) will fail due to non-existent columns. This is a critical bug preventing the staff availability features from functioning correctly.

**Proposed Solution**:
To rectify this, a new table named `staff_availability` must be created in `db/database.sql`. This table will be dedicated to storing staff availability records and will include the `staff_id` (as a foreign key to the `staff` table), `available_on`, and `period` columns, along with the standard `created_at` and `updated_at` timestamps and `user_id` as per the database schema rules.

This new table will provide the correct structure for the `staff_availability.php` handler to interact with, resolving the current database schema mismatch.
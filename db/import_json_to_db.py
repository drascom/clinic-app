#!/usr/bin/env python3
"""
Database Import Script
Imports JSON backup files into SQLite database with upsert functionality.

Author: Database Management System
Created: 2024
"""

import sqlite3
import json
import os
import sys
import logging
import zipfile
import tempfile
import shutil
from datetime import datetime
from pathlib import Path
import argparse

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('db_import.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

class DatabaseImporter:
    def __init__(self, db_path, backup_source):
        """Initialize the database importer."""
        self.db_path = db_path
        self.backup_source = backup_source
        self.temp_dir = None
        self.backup_dir = None

        logging.info(f"Initialized importer for database: {db_path}")
        logging.info(f"Backup source: {backup_source}")

    def prepare_backup_directory(self):
        """Prepare backup directory - extract ZIP if needed."""
        backup_path = Path(self.backup_source)

        if backup_path.is_file() and backup_path.suffix.lower() == '.zip':
            # Handle ZIP file
            logging.info(f"Extracting ZIP file: {backup_path}")

            # Create temporary directory
            self.temp_dir = tempfile.mkdtemp(prefix='backup_import_')

            try:
                with zipfile.ZipFile(backup_path, 'r') as zip_ref:
                    zip_ref.extractall(self.temp_dir)

                # Find the backup directory inside the extracted content
                extracted_dirs = [d for d in Path(self.temp_dir).iterdir() if d.is_dir()]
                if len(extracted_dirs) == 1:
                    self.backup_dir = extracted_dirs[0]
                else:
                    # Look for a directory that starts with 'backup_'
                    backup_dirs = [d for d in extracted_dirs if d.name.startswith('backup_')]
                    if backup_dirs:
                        self.backup_dir = backup_dirs[0]
                    else:
                        self.backup_dir = Path(self.temp_dir)

                logging.info(f"Extracted to temporary directory: {self.backup_dir}")
                return True

            except Exception as e:
                logging.error(f"Error extracting ZIP file: {e}")
                if self.temp_dir:
                    shutil.rmtree(self.temp_dir, ignore_errors=True)
                return False

        elif backup_path.is_dir():
            # Handle directory
            self.backup_dir = backup_path
            logging.info(f"Using backup directory: {self.backup_dir}")
            return True
        else:
            logging.error(f"Invalid backup source: {backup_path}")
            return False

    def cleanup(self):
        """Clean up temporary files."""
        if self.temp_dir and os.path.exists(self.temp_dir):
            shutil.rmtree(self.temp_dir, ignore_errors=True)
            logging.info("Cleaned up temporary files")

    def validate_backup_directory(self):
        """Validate that the backup directory exists and contains valid files."""
        if not self.backup_dir.exists():
            logging.error(f"Backup directory does not exist: {self.backup_dir}")
            return False
        
        manifest_file = self.backup_dir / 'backup_manifest.json'
        if not manifest_file.exists():
            logging.error(f"Backup manifest not found: {manifest_file}")
            return False
        
        try:
            with open(manifest_file, 'r', encoding='utf-8') as f:
                self.manifest = json.load(f)
            logging.info("Backup manifest loaded successfully")
            return True
        except Exception as e:
            logging.error(f"Error reading backup manifest: {e}")
            return False

    def get_table_primary_key(self, cursor, table_name):
        """Get the primary key column(s) for a table."""
        cursor.execute(f"PRAGMA table_info({table_name})")
        columns = cursor.fetchall()
        primary_keys = [col[1] for col in columns if col[5] > 0]  # col[5] is pk flag
        return primary_keys

    def table_exists(self, cursor, table_name):
        """Check if a table exists in the database."""
        cursor.execute("SELECT name FROM sqlite_master WHERE type='table' AND name=?", (table_name,))
        return cursor.fetchone() is not None

    def build_upsert_query(self, table_name, columns, primary_keys):
        """Build an UPSERT query for SQLite."""
        placeholders = ', '.join(['?' for _ in columns])
        columns_str = ', '.join(columns)
        
        # Build the INSERT part
        insert_query = f"INSERT INTO {table_name} ({columns_str}) VALUES ({placeholders})"
        
        # Build the ON CONFLICT part for upsert
        if primary_keys:
            pk_conflict = ', '.join(primary_keys)
            update_columns = [f"{col} = excluded.{col}" for col in columns if col not in primary_keys]
            
            if update_columns:
                update_str = ', '.join(update_columns)
                upsert_query = f"{insert_query} ON CONFLICT({pk_conflict}) DO UPDATE SET {update_str}"
            else:
                # If only primary keys, just ignore conflicts
                upsert_query = f"{insert_query} ON CONFLICT({pk_conflict}) DO NOTHING"
        else:
            # No primary key, use INSERT OR REPLACE
            upsert_query = f"INSERT OR REPLACE INTO {table_name} ({columns_str}) VALUES ({placeholders})"
        
        return upsert_query

    def import_table_data(self, cursor, table_name, json_file):
        """Import data from JSON file into database table."""
        try:
            with open(json_file, 'r', encoding='utf-8') as f:
                table_data = json.load(f)
            
            if 'data' not in table_data:
                logging.error(f"Invalid JSON structure in {json_file}")
                return False
            
            records = table_data['data']
            if not records:
                logging.info(f"No records to import for table '{table_name}'")
                return True
            
            # Get table schema
            if not self.table_exists(cursor, table_name):
                logging.error(f"Table '{table_name}' does not exist in database")
                return False
            
            # Get primary keys
            primary_keys = self.get_table_primary_key(cursor, table_name)
            
            # Get columns from first record
            columns = list(records[0].keys())
            
            # Build upsert query
            upsert_query = self.build_upsert_query(table_name, columns, primary_keys)
            
            # Import records
            imported_count = 0
            updated_count = 0
            error_count = 0
            
            for record in records:
                try:
                    # Check if record exists (for counting updates vs inserts)
                    if primary_keys:
                        pk_conditions = ' AND '.join([f"{pk} = ?" for pk in primary_keys])
                        pk_values = [record.get(pk) for pk in primary_keys]
                        check_query = f"SELECT COUNT(*) FROM {table_name} WHERE {pk_conditions}"
                        cursor.execute(check_query, pk_values)
                        exists = cursor.fetchone()[0] > 0
                    else:
                        exists = False
                    
                    # Prepare values in column order
                    values = [record.get(col) for col in columns]
                    
                    # Execute upsert
                    cursor.execute(upsert_query, values)
                    
                    if exists:
                        updated_count += 1
                    else:
                        imported_count += 1
                        
                except sqlite3.Error as e:
                    logging.error(f"Error importing record in table '{table_name}': {e}")
                    error_count += 1
                    continue
            
            logging.info(f"Table '{table_name}': {imported_count} new records, {updated_count} updated, {error_count} errors")
            return error_count == 0
            
        except Exception as e:
            logging.error(f"Error importing table '{table_name}': {e}")
            return False

    def import_database(self):
        """Main import function."""
        # Prepare backup directory (extract ZIP if needed)
        if not self.prepare_backup_directory():
            return False

        if not self.validate_backup_directory():
            self.cleanup()
            return False
        
        if not os.path.exists(self.db_path):
            logging.error(f"Database file not found: {self.db_path}")
            return False

        try:
            # Connect to database with foreign key support
            conn = sqlite3.connect(self.db_path)
            conn.execute("PRAGMA foreign_keys = ON")
            cursor = conn.cursor()
            
            # Begin transaction
            conn.execute("BEGIN TRANSACTION")
            
            successful_tables = 0
            failed_tables = 0
            total_imported = 0
            total_updated = 0
            
            # Import tables in dependency order (basic tables first)
            table_order = [
                'agencies', 'users', 'photo_album_types', 'rooms', 'procedures', 'technicians',
                'patients', 'job_candidates', 'surgeries', 'appointments', 'patient_photos',
                'invitations', 'interview_invitations', 'candidate_notes', 'room_reservations',
                'technician_availability', 'surgery_technicians', 'settings'
            ]
            
            # Get available JSON files
            json_files = list(self.backup_dir.glob('*.json'))
            available_tables = [f.stem for f in json_files if f.name != 'backup_manifest.json']
            
            # Import tables in order, then any remaining tables
            all_tables = table_order + [t for t in available_tables if t not in table_order]
            
            for table_name in all_tables:
                json_file = self.backup_dir / f"{table_name}.json"
                
                if not json_file.exists():
                    logging.info(f"Skipping table '{table_name}' - JSON file not found")
                    continue
                
                logging.info(f"Importing table: {table_name}")
                
                if self.import_table_data(cursor, table_name, json_file):
                    successful_tables += 1
                    logging.info(f"Successfully imported table '{table_name}'")
                else:
                    failed_tables += 1
                    logging.error(f"Failed to import table '{table_name}'")
            
            # Commit transaction
            conn.commit()
            conn.close()

            logging.info(f"Import completed: {successful_tables} tables imported successfully, {failed_tables} failed")

            # Cleanup temporary files
            self.cleanup()

            return failed_tables == 0
            
        except sqlite3.Error as e:
            logging.error(f"Database error during import: {e}")
            try:
                conn.rollback()
                conn.close()
            except:
                pass
            self.cleanup()
            return False
        except Exception as e:
            logging.error(f"Unexpected error during import: {e}")
            try:
                conn.rollback()
                conn.close()
            except:
                pass
            self.cleanup()
            return False

def main():
    """Main function to run the import."""
    parser = argparse.ArgumentParser(description='Import JSON backup files to SQLite database')
    parser.add_argument('backup_dir', help='Path to backup directory containing JSON files')
    
    args = parser.parse_args()
    
    # Database path relative to script location
    script_dir = Path(__file__).parent
    db_path = script_dir / 'database.sqlite'
    
    logging.info("Starting database import process")
    
    importer = DatabaseImporter(db_path, args.backup_dir)
    success = importer.import_database()
    
    if success:
        logging.info("Database import completed successfully")
        print("SUCCESS:Import completed successfully")
        sys.exit(0)
    else:
        logging.error("Database import failed")
        print("ERROR:Import failed")
        sys.exit(1)

if __name__ == "__main__":
    main()

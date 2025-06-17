#!/usr/bin/env python3
"""
Database Export Script
Exports all tables from SQLite database to JSON files with timestamp versioning.

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

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('db_export.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

class DatabaseExporter:
    def __init__(self, db_path):
        """Initialize the database exporter."""
        self.db_path = db_path
        self.backup_base_dir = Path(__file__).parent / 'backups'
        self.timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')

        # Create temporary directory for JSON files
        self.temp_dir = tempfile.mkdtemp(prefix=f'backup_{self.timestamp}_')
        self.backup_dir = Path(self.temp_dir)

        # Ensure backup base directory exists
        self.backup_base_dir.mkdir(parents=True, exist_ok=True)

        logging.info(f"Initialized exporter for database: {db_path}")
        logging.info(f"Temporary backup directory: {self.backup_dir}")

    def get_table_names(self, cursor):
        """Get all table names from the database."""
        cursor.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
        tables = [row[0] for row in cursor.fetchall()]
        logging.info(f"Found {len(tables)} tables: {', '.join(tables)}")
        return tables

    def get_table_schema(self, cursor, table_name):
        """Get the schema information for a table."""
        cursor.execute(f"PRAGMA table_info({table_name})")
        columns = cursor.fetchall()
        schema = {
            'columns': [
                {
                    'name': col[1],
                    'type': col[2],
                    'not_null': bool(col[3]),
                    'default_value': col[4],
                    'primary_key': bool(col[5])
                }
                for col in columns
            ]
        }
        return schema

    def export_table_data(self, cursor, table_name):
        """Export all data from a specific table."""
        try:
            cursor.execute(f"SELECT * FROM {table_name}")
            columns = [description[0] for description in cursor.description]
            rows = cursor.fetchall()
            
            # Convert rows to list of dictionaries
            data = []
            for row in rows:
                row_dict = {}
                for i, value in enumerate(row):
                    row_dict[columns[i]] = value
                data.append(row_dict)
            
            logging.info(f"Exported {len(data)} records from table '{table_name}'")
            return {
                'table_name': table_name,
                'record_count': len(data),
                'exported_at': datetime.now().isoformat(),
                'schema': self.get_table_schema(cursor, table_name),
                'data': data
            }
            
        except sqlite3.Error as e:
            logging.error(f"Error exporting table '{table_name}': {e}")
            return None

    def save_table_json(self, table_data, table_name):
        """Save table data to JSON file."""
        if table_data is None:
            return False
            
        json_file = self.backup_dir / f"{table_name}.json"
        try:
            with open(json_file, 'w', encoding='utf-8') as f:
                json.dump(table_data, f, indent=2, ensure_ascii=False, default=str)
            
            logging.info(f"Saved {table_data['record_count']} records to {json_file}")
            return True
            
        except Exception as e:
            logging.error(f"Error saving JSON file for table '{table_name}': {e}")
            return False

    def create_backup_manifest(self, exported_tables, total_records):
        """Create a manifest file with backup information."""
        manifest = {
            'backup_info': {
                'timestamp': self.timestamp,
                'created_at': datetime.now().isoformat(),
                'database_path': str(self.db_path),
                'backup_directory': str(self.backup_dir),
                'total_tables': len(exported_tables),
                'total_records': total_records
            },
            'tables': exported_tables,
            'export_summary': {
                'successful_tables': len([t for t in exported_tables if t['status'] == 'success']),
                'failed_tables': len([t for t in exported_tables if t['status'] == 'failed']),
                'total_exported_records': sum(t.get('record_count', 0) for t in exported_tables if t['status'] == 'success')
            }
        }
        
        manifest_file = self.backup_dir / 'backup_manifest.json'
        try:
            with open(manifest_file, 'w', encoding='utf-8') as f:
                json.dump(manifest, f, indent=2, ensure_ascii=False)
            
            logging.info(f"Created backup manifest: {manifest_file}")
            return True
            
        except Exception as e:
            logging.error(f"Error creating backup manifest: {e}")
            return False

    def cleanup_temp_files(self):
        """Clean up temporary files and directories."""
        try:
            if hasattr(self, 'temp_dir') and os.path.exists(self.temp_dir):
                shutil.rmtree(self.temp_dir)
                logging.info(f"Cleaned up temporary directory: {self.temp_dir}")
        except Exception as e:
            logging.error(f"Error cleaning up temporary files: {e}")

    def cleanup_old_backups(self):
        """Keep only the last 5 backup ZIP files, delete older ones."""
        try:
            # Get all backup ZIP files
            backup_files = list(self.backup_base_dir.glob('backup_*.zip'))

            # We want to keep 5 total, so if we have 5 or more, delete the oldest ones
            # to make room for the new backup we're about to create
            if len(backup_files) < 5:
                logging.info(f"Found {len(backup_files)} backup files, no cleanup needed")
                return

            # Sort by modification time (newest first)
            backup_files.sort(key=lambda x: x.stat().st_mtime, reverse=True)

            # Keep only the first 4 (newest), delete the rest
            # This makes room for the new backup we're about to create (total will be 5)
            files_to_delete = backup_files[4:]

            for file_path in files_to_delete:
                try:
                    file_path.unlink()
                    logging.info(f"Deleted old backup: {file_path.name}")
                except Exception as e:
                    logging.error(f"Error deleting {file_path}: {e}")

            logging.info(f"Cleanup completed: will keep {min(4, len(backup_files) - len(files_to_delete))} existing backups + 1 new backup = 5 total, deleted {len(files_to_delete)} old backups")

        except Exception as e:
            logging.error(f"Error during backup cleanup: {e}")

    def create_zip_backup(self):
        """Create a ZIP file containing all backup files."""
        zip_filename = f"backup_{self.timestamp}.zip"
        zip_path = self.backup_base_dir / zip_filename

        try:
            with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
                # Add all files from backup directory to ZIP
                for file_path in self.backup_dir.rglob('*'):
                    if file_path.is_file():
                        # Calculate relative path within the backup directory
                        relative_path = file_path.relative_to(self.backup_dir)
                        # Add file to ZIP with the backup folder name as root
                        arcname = f"backup_{self.timestamp}/{relative_path}"
                        zipf.write(file_path, arcname)
                        logging.info(f"Added {relative_path} to ZIP archive")

            # Get ZIP file size
            zip_size = zip_path.stat().st_size
            logging.info(f"Created ZIP backup: {zip_path} ({self.format_bytes(zip_size)})")

            # Clean up temporary files after ZIP creation
            self.cleanup_temp_files()

            return str(zip_path)

        except Exception as e:
            logging.error(f"Error creating ZIP backup: {e}")
            # Clean up temporary files even on error
            self.cleanup_temp_files()
            return None

    def format_bytes(self, size, precision=2):
        """Format bytes to human readable format."""
        units = ['B', 'KB', 'MB', 'GB']
        for i in range(len(units)):
            if size < 1024 or i == len(units) - 1:
                return f"{size:.{precision}f} {units[i]}"
            size /= 1024
        return f"{size:.{precision}f} {units[-1]}"

    def export_database(self):
        """Main export function."""
        if not os.path.exists(self.db_path):
            logging.error(f"Database file not found: {self.db_path}")
            return False

        # Clean up old backups first (keep only last 5)
        self.cleanup_old_backups()

        try:
            # Connect to database
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            # Get all tables
            table_names = self.get_table_names(cursor)
            
            if not table_names:
                logging.warning("No tables found in database")
                return False

            exported_tables = []
            total_records = 0
            
            # Export each table
            for table_name in table_names:
                logging.info(f"Exporting table: {table_name}")
                
                table_data = self.export_table_data(cursor, table_name)
                
                if table_data and self.save_table_json(table_data, table_name):
                    exported_tables.append({
                        'table_name': table_name,
                        'record_count': table_data['record_count'],
                        'status': 'success',
                        'file_name': f"{table_name}.json"
                    })
                    total_records += table_data['record_count']
                else:
                    exported_tables.append({
                        'table_name': table_name,
                        'record_count': 0,
                        'status': 'failed',
                        'file_name': None
                    })

            # Create backup manifest
            self.create_backup_manifest(exported_tables, total_records)

            # Create ZIP file
            zip_path = self.create_zip_backup()

            # Close database connection
            conn.close()

            successful_tables = len([t for t in exported_tables if t['status'] == 'success'])
            logging.info(f"Export completed: {successful_tables}/{len(table_names)} tables exported successfully")
            logging.info(f"Total records exported: {total_records}")
            logging.info(f"Backup saved to: {self.backup_dir}")

            if zip_path:
                logging.info(f"ZIP backup created: {zip_path}")
                # Store ZIP path for PHP to use
                self.zip_backup_path = zip_path
                return True
            else:
                logging.error("Failed to create ZIP backup")
                return False
            
        except sqlite3.Error as e:
            logging.error(f"Database error during export: {e}")
            self.cleanup_temp_files()
            return False
        except Exception as e:
            logging.error(f"Unexpected error during export: {e}")
            self.cleanup_temp_files()
            return False

def main():
    """Main function to run the export."""
    # Database path relative to script location
    script_dir = Path(__file__).parent
    db_path = script_dir / 'database.sqlite'
    
    logging.info("Starting database export process")
    
    exporter = DatabaseExporter(db_path)
    success = exporter.export_database()
    
    if success:
        logging.info("Database export completed successfully")
        # Output only ZIP path for PHP (no backup directory since it's temporary)
        zip_path = getattr(exporter, 'zip_backup_path', None)
        if zip_path:
            print(f"SUCCESS:{zip_path}")
        else:
            print("ERROR:ZIP file creation failed")
            sys.exit(1)
        sys.exit(0)
    else:
        logging.error("Database export failed")
        print("ERROR:Export failed")
        sys.exit(1)

if __name__ == "__main__":
    main()

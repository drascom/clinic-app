CREATE TABLE IF NOT EXISTS email_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    file_path TEXT,
    mime_type TEXT,
    size INTEGER,
    email_uid INTEGER,
    part_index TEXT,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
);
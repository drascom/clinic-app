CREATE TABLE IF NOT EXISTS email_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    file_path TEXT NOT NULL UNIQUE,
    mime_type TEXT,
    size INTEGER,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
);
CREATE TABLE closed_days (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL UNIQUE,
    reason TEXT,
    closed_by_user_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (closed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
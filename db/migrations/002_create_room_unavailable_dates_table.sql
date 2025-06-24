CREATE TABLE room_unavailable_dates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    unavailable_date DATE NOT NULL,
    reason VARCHAR(255),
    closed_by_user_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (closed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(room_id, unavailable_date)
);
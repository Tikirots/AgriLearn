-- =========================================================
-- AgriLearn — Migration: trainee approval + notifications
-- Safe to run on your EXISTING database — does not delete data.
-- Run this once in phpMyAdmin (SQL tab) on agrilearn_db.
-- =========================================================

-- 1. Allow a 'pending' account status (new trainees start here)
ALTER TABLE users
  MODIFY status ENUM('pending','active','inactive') NOT NULL DEFAULT 'pending';

-- 2. Notifications table (bell icon) — safe if it already exists
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Make sure your existing admin account is explicitly 'active'
--    (in case it was inserted before this migration)
UPDATE users SET status = 'active' WHERE role IN ('admin','trainer');

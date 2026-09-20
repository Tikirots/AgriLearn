-- =========================================================
-- AgriLearn Database Schema
-- Progressive Web Application-Based Training Portal and
-- Controlled Learning Management System
-- Masaganang Bukid Agricultural Learning Center
-- =========================================================

CREATE DATABASE IF NOT EXISTS agrilearn_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agrilearn_db;

-- -----------------------------------------------------
-- Users (login accounts: admin/trainer or trainee)
-- -----------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','trainer','trainee') NOT NULL DEFAULT 'trainee',
    status ENUM('pending','active','inactive') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Trainee profile details
-- -----------------------------------------------------
CREATE TABLE trainees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    address VARCHAR(255),
    contact_number VARCHAR(30),
    birthdate DATE,
    gender ENUM('Male','Female','Other'),
    photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Training Programs (NC II sets)
-- -----------------------------------------------------
CREATE TABLE training_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    nc_level VARCHAR(50) DEFAULT 'NC II',
    description TEXT,
    start_date DATE,
    end_date DATE,
    slots INT DEFAULT 30,
    status ENUM('open','ongoing','closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Enrollments (trainee applies -> admin approves)
-- -----------------------------------------------------
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainee_id INT NOT NULL,
    program_id INT NOT NULL,
    status ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    decided_at TIMESTAMP NULL,
    FOREIGN KEY (trainee_id) REFERENCES trainees(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_trainee_program (trainee_id, program_id)
);

-- -----------------------------------------------------
-- Modules (learning materials, controlled visibility)
-- -----------------------------------------------------
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    order_num INT DEFAULT 1,
    is_visible TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Trainee activity log (module views, attendance, notes)
-- -----------------------------------------------------
CREATE TABLE trainee_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainee_id INT NOT NULL,
    program_id INT NOT NULL,
    module_id INT NULL,
    activity_type VARCHAR(50) NOT NULL, -- e.g. 'Module Viewed','Attendance','Practical Exercise'
    description VARCHAR(255),
    activity_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainee_id) REFERENCES trainees(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- Certificates with QR verification
-- -----------------------------------------------------
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainee_id INT NOT NULL,
    program_id INT NOT NULL,
    certificate_code VARCHAR(40) NOT NULL UNIQUE,
    issued_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainee_id) REFERENCES trainees(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_cert_trainee_program (trainee_id, program_id)
);

-- -----------------------------------------------------
-- Notifications (bell icon in the top bar for admin & trainees)
-- -----------------------------------------------------
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

-- -----------------------------------------------------
-- Default admin account (username: admin / password: admin123)
-- Change this password immediately after first login.
-- -----------------------------------------------------
INSERT INTO users (username, email, password, role, status)
VALUES ('admin', 'admin@agrilearn.local', 'admin123', 'admin', 'active');
-- Login: admin / admin123
-- 'active' is set explicitly here since trainee self-registrations now
-- default to 'pending' and require admin approval before they can log in.
-- Password is stored in PLAIN TEXT — see includes/functions.php -> verify_password().

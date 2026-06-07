-- ============================================
-- CodeGuard Database
-- Import this file in phpMyAdmin
-- ============================================

CREATE DATABASE IF NOT EXISTS codeguard_db CHARACTER SET utf8 COLLATE utf8_general_ci;
USE codeguard_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(10) NOT NULL,
    token_count INT DEFAULT 0,
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comparisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_1 INT NOT NULL,
    submission_2 INT NOT NULL,
    token_score FLOAT DEFAULT 0,
    lcs_score FLOAT DEFAULT 0,
    hash_score FLOAT DEFAULT 0,
    similarity_percentage FLOAT DEFAULT 0,
    risk_level ENUM('low','medium','high') DEFAULT 'low',
    compared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_1) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (submission_2) REFERENCES submissions(id) ON DELETE CASCADE
);

-- Default Admin Account
-- Password: admin123
INSERT INTO users (name, email, password, role) VALUES (
    'Admin Teacher',
    'admin@codeguard.com',
    '$2y$10$TKh8H1.PfVqWMv0kMLJFoOgXFPBOPdwMDNl3MKB8k/Y8BQMST8dJy',
    'admin'
);

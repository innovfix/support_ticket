-- Setup local database for HIMA Support
USE hima;

-- Create issue_types table
CREATE TABLE IF NOT EXISTS issue_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Insert default issue types
INSERT IGNORE INTO issue_types (name, is_active) VALUES 
('Withdrawal paid status not amount came', 1),
('Coins not added', 1),
('Call amount not added', 1),
('App issue / crash', 1),
('Bank details issue', 1),
('KYC details related', 1),
('Blocking user', 1),
('mobile', 1);

-- Create tickets table
CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_code VARCHAR(20) NOT NULL UNIQUE,
    mobile_or_user_id VARCHAR(100) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    issue_description TEXT NOT NULL,
    status ENUM('new','in-progress','resolved','closed') NOT NULL DEFAULT 'new',
    assigned_to VARCHAR(100) DEFAULT NULL,
    screenshot_path VARCHAR(255) DEFAULT NULL,
    created_by VARCHAR(100) NOT NULL DEFAULT 'Staff',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    assigned_by VARCHAR(150) DEFAULT NULL,
    assigned_to_name VARCHAR(150) DEFAULT NULL,
    assigned_by_name VARCHAR(150) DEFAULT NULL
);

-- Create staff_users table
CREATE TABLE IF NOT EXISTS staff_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

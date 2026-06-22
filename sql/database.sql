-- Muni University QR Code Management System - Updated Database
CREATE DATABASE IF NOT EXISTS muni_vc_qr;
USE muni_vc_qr;

-- Admins table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
);

-- Profiles table (VC Profile)
CREATE TABLE profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    title VARCHAR(100) NOT NULL,
    office VARCHAR(100) NOT NULL,
    biography TEXT,
    email VARCHAR(100),
    phone VARCHAR(50),
    website VARCHAR(200),
    linkedin VARCHAR(200),
    facebook VARCHAR(200),
    twitter VARCHAR(200),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- QR Codes table (Enhanced)
CREATE TABLE qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    qr_image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    scan_count INT DEFAULT 0,
    last_scan TIMESTAMP NULL,
    -- Design settings (JSON)
    design_settings JSON,
    -- Content data (JSON)
    content_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Scans table
CREATE TABLE scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    qr_id INT NOT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (qr_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
    INDEX idx_qr_id (qr_id),
    INDEX idx_scanned_at (scanned_at)
);

-- Insert default admin (Password: admin123)
INSERT INTO admins (username, name, email, password, is_active) VALUES 
('admin', 'Administrator', 'admin@muni.ac.ug', '$2y$10$N9qo8uLOickgx2ZMRZoMy.Mr/.8Z1kPjM2xM9.wtM5cU8Z2nM0Yy', 1);

-- Insert sample profile
INSERT INTO profiles (full_name, title, office, biography, email, phone, website, linkedin, facebook, twitter) VALUES 
('Prof. Simon Anguma', 'Vice Chancellor', 'Office of the Vice Chancellor', 'Leading transformation through quality education, research, innovation, and community engagement.', 'vc@muni.ac.ug', '+256-123-456789', 'https://www.muni.ac.ug', 'https://linkedin.com/in/vc', 'https://facebook.com/muniuniversity', 'https://twitter.com/muniuniversity');

-- Insert sample QR code
INSERT INTO qr_codes (name, token, status, design_settings, content_data) VALUES 
('VC Official QR', CONCAT('vc_', REPLACE(UUID(), '-', '')), 'active', 
'{"frame":"rounded","pattern":"dots","corner":"square","logo_placement":"center","color":"#8B0000","background":"#FFFFFF"}',
'{"title":"Vice Chancellor Official QR","description":"Official verification QR code for the Vice Chancellor"}');
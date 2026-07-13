-- ============================================
-- MUNI UNIVERSITY QR CODE MANAGEMENT SYSTEM
-- COMPLETE DATABASE SCHEMA (WITH MULTI-USER)
-- ============================================

-- Drop existing tables if they exist
DROP TABLE IF EXISTS scans;
DROP TABLE IF EXISTS qr_codes;
DROP TABLE IF EXISTS admins;

-- ============================================
-- 1. ADMINS TABLE (User Authentication)
-- ============================================
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    created_by INT NULL,
    last_login TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. QR CODES TABLE (Stores all QR code data)
-- ============================================
CREATE TABLE qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    qr_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    scan_count INT DEFAULT 0,
    last_scan TIMESTAMP NULL,
    design_settings JSON DEFAULT NULL,
    content_data JSON DEFAULT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. SCANS TABLE (Tracks QR code scans)
-- ============================================
CREATE TABLE scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    qr_id INT NOT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (qr_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
    INDEX idx_qr_id (qr_id),
    INDEX idx_scanned_at (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. INSERT DEFAULT SUPER ADMIN
-- Password: Qrcode@muni2026
-- ============================================
INSERT INTO admins (username, name, email, password, role, is_active) VALUES 
('Muniqqrcode', 'Administrator', 'admin@muni.ac.ug', '$2y$10$Qrcode@muni2026HASHHERE', 'super_admin', 1);

-- ============================================
-- 5. VERIFY
-- ============================================
SELECT 'Database setup complete!' as Status;
SHOW TABLES;
SELECT id, username, name, email, role FROM admins;
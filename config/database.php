<?php
// Database configuration for LOCAL XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'muni_vc_qr');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'MUNI-QR-CODE');
define('APP_URL', 'http://localhost/muni-vc-qr');
define('APP_ENV', 'development');
define('APP_DEBUG', true);

// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('QR_PATH', UPLOAD_PATH . 'qrcodes/');
define('PROFILE_PATH', UPLOAD_PATH . 'profiles/');
define('LOGO_PATH', UPLOAD_PATH . 'logos/');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PDO Database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
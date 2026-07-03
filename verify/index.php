<?php
require_once '../config/database.php';

// Get QR token from URL query parameter
$token = $_GET['token'] ?? null;

// Initialize variables
$qrData = null;
$isValid = false;
$error = null;
$scanCount = 0;
$content = [];

try {
    if ($token) {
        // Find QR code by token
        $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE token = ?");
        $stmt->execute([$token]);
        $qrData = $stmt->fetch();
        
        if ($qrData) {
            if ($qrData['status'] === 'active') {
                $isValid = true;
                
                // Decode the content data from the QR code
                $content = json_decode($qrData['content_data'] ?? '{}', true);
                $scanCount = $qrData['scan_count'] ?? 0;
                
                // Update scan count with row locking
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("SELECT scan_count FROM qr_codes WHERE id = ? FOR UPDATE");
                $stmt->execute([$qrData['id']]);
                $current = $stmt->fetch();
                $newCount = ($current['scan_count'] ?? 0) + 1;
                
                $stmt = $pdo->prepare("UPDATE qr_codes SET scan_count = ?, last_scan = NOW() WHERE id = ?");
                $stmt->execute([$newCount, $qrData['id']]);
                
                $stmt = $pdo->prepare("INSERT INTO scans (qr_id) VALUES (?)");
                $stmt->execute([$qrData['id']]);
                
                $pdo->commit();
                
                // Get updated data
                $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE id = ?");
                $stmt->execute([$qrData['id']]);
                $qrData = $stmt->fetch();
                $scanCount = $qrData['scan_count'] ?? 0;
                
            } else {
                $error = 'This QR Code has been deactivated.';
            }
        } else {
            $error = 'Invalid QR Code. Please scan a valid verification code.';
        }
    } else {
        $error = 'No QR code token provided. Please scan a valid QR code.';
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Verification error: ' . $e->getMessage());
    $error = 'System error. Please try again later.';
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Verification error: ' . $e->getMessage());
    $error = 'System error. Please try again later.';
}

// Function to check if image file exists and is valid
function getValidImagePath($photoFile) {
    if (empty($photoFile)) {
        return null;
    }
    
    $basePath = '../assets/uploads/profiles/';
    
    $supportedFormats = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jfif' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'jif' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff'
    ];
    
    $ext = strtolower(pathinfo($photoFile, PATHINFO_EXTENSION));
    
    if (!isset($supportedFormats[$ext])) {
        error_log('Unsupported file format: ' . $ext);
        return null;
    }
    
    $fullPath = $basePath . $photoFile;
    
    if (!file_exists($fullPath)) {
        error_log('Photo file not found: ' . $fullPath);
        return null;
    }
    
    if (!is_readable($fullPath)) {
        error_log('Photo file not readable: ' . $fullPath);
        return null;
    }
    
    $fileSize = filesize($fullPath);
    if ($fileSize === 0) {
        error_log('Photo file is empty: ' . $fullPath);
        return null;
    }
    
    $imageInfo = @getimagesize($fullPath);
    if ($imageInfo === false) {
        error_log('Invalid image file: ' . $fullPath);
        return null;
    }
    
    return $fullPath;
}

// Get logo (optional)
$logoPath = '../assets/uploads/logos/';
$logoFile = null;
if (is_dir($logoPath)) {
    $files = scandir($logoPath);
    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
            $logoFile = $file;
            break;
        }
    }
}

// Get the photo path
$photoFile = $content['photo'] ?? '';
$photoPath = getValidImagePath($photoFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Verification - Muni University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .verification-card {
            background: white;
            border-radius: 24px;
            padding: 3rem 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            max-width: 720px;
            margin: 2rem auto;
        }
        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #059669;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #8B0000;
            box-shadow: 0 4px 20px rgba(139, 0, 0, 0.2);
            margin: 0 auto 1.5rem;
        }
        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #f1f3f5;
            color: #333;
            transition: all 0.3s;
            text-decoration: none;
        }
        .social-link:hover {
            background: #8B0000;
            color: white;
            transform: translateY(-2px);
        }
        .social-link i {
            font-size: 1.2rem;
        }
        /* Twitter bird SVG styling */
        .social-link .twitter-svg {
            width: 20px;
            height: 20px;
            fill: #333;
            transition: fill 0.3s;
        }
        .social-link:hover .twitter-svg {
            fill: white;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .profile-image-container {
            position: relative;
            display: inline-block;
        }
        .profile-image-container img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #8B0000;
            box-shadow: 0 4px 20px rgba(139, 0, 0, 0.2);
        }
        .profile-image-container .fallback {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border: 4px solid #8B0000;
        }
        .profile-image-container .fallback i {
            font-size: 4rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <?php if ($isValid && $qrData): ?>
            <!-- Verified Card -->
            <div class="verification-card fade-in">
                <!-- University Logo -->
                <div class="text-center mb-3">
                    <?php if ($logoFile && file_exists('../assets/uploads/logos/' . $logoFile)): ?>
                        <img src="../assets/uploads/logos/<?php echo htmlspecialchars($logoFile); ?>" 
                             alt="Muni University Logo" style="max-height: 80px; width: auto;">
                    <?php else: ?>
                        <h2 class="text-primary">Muni University</h2>
                    <?php endif; ?>
                </div>
                
                <!-- Verification Badge -->
                <div class="text-center">
                    <div class="verification-badge">
                        <i class="bi bi-check-circle-fill"></i>
                        VERIFIED OFFICIAL PROFILE
                    </div>
                </div>
                
                <!-- Profile Photo -->
                <div class="text-center">
                    <div class="profile-image-container">
                        <?php if ($photoPath && file_exists($photoPath)): ?>
                            <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                                 alt="Profile Photo" 
                                 class="profile-image"
                                 onerror="this.style.display='none'; this.parentElement.querySelector('.fallback').style.display='flex';">
                            <div class="fallback" style="display: none;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        <?php else: ?>
                            <div class="profile-image bg-secondary d-flex align-items-center justify-content-center text-white" 
                                 style="width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 1.5rem;">
                                <i class="bi bi-person-fill" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Profile Information -->
                <div class="text-center">
                    <h2 class="mb-1"><?php echo htmlspecialchars($content['full_name'] ?? 'Unknown User'); ?></h2>
                    <h5 class="text-primary mb-1"><?php echo htmlspecialchars($content['title_position'] ?? 'Unknown Title'); ?></h5>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($content['office'] ?? 'Unknown Office'); ?></p>
                    
                    <?php if (!empty($content['biography'])): ?>
                        <p class="mb-4" style="font-size: 1.05rem; line-height: 1.7;"><?php echo nl2br(htmlspecialchars($content['biography'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                    <?php if (!empty($content['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($content['email']); ?>" class="btn btn-primary">
                            <i class="bi bi-envelope"></i> Email
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($content['phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($content['phone']); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-telephone"></i> Call
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($content['website'])): ?>
                        <a href="<?php echo htmlspecialchars($content['website']); ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-globe"></i> Website
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Social Links - WITH TWITTER BIRD SVG -->
                <?php if (!empty($content['linkedin']) || !empty($content['facebook']) || !empty($content['twitter'])): ?>
                    <hr>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <?php if (!empty($content['linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($content['linkedin']); ?>" target="_blank" class="social-link" title="LinkedIn">
                                <i class="bi bi-linkedin"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($content['facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($content['facebook']); ?>" target="_blank" class="social-link" title="Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($content['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($content['twitter']); ?>" target="_blank" class="social-link" title="Twitter / X">
                                <!-- Twitter Bird SVG Logo -->
                                <svg class="twitter-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Footer Info -->
                <hr>
                <div class="text-center text-muted small">
                    <p class="mb-0">This is an official verification from</p>
                    <p class="mb-0"><strong>Muni University</strong></p>
                    <?php if ($qrData && isset($qrData['last_scan'])): ?>
                        <p class="mb-0 mt-2">
                            <i class="bi bi-clock"></i> Verified: <?php echo date('F j, Y \a\t g:i A', strtotime($qrData['last_scan'])); ?>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0 mt-1">
                        <i class="bi bi-shield-check text-success"></i> Secure Verification
                        <span class="mx-2">•</span>
                        <i class="bi bi-qr-code"></i> Scan ID: <?php echo htmlspecialchars(substr($qrData['token'] ?? '', 0, 12)); ?>
                    </p>
                    <p class="mb-0 mt-1">
                        <i class="bi bi-eye"></i> This QR code has been scanned <strong><?php echo number_format($scanCount); ?></strong> time<?php echo $scanCount != 1 ? 's' : ''; ?>
                    </p>
                </div>
            </div>
            
        <?php elseif ($error): ?>
            <!-- Error Card -->
            <div class="verification-card fade-in text-center">
                <div class="mb-4">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex p-4">
                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                    </div>
                </div>
                <h3 class="mb-3">Verification Failed</h3>
                <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
                <a href="https://www.muni.ac.ug" class="btn btn-primary">
                    <i class="bi bi-building"></i> Visit Muni University
                </a>
            </div>
            
        <?php else: ?>
            <!-- Default Card -->
            <div class="verification-card fade-in text-center">
                <div class="mb-4">
                    <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex p-4">
                        <i class="bi bi-qr-code" style="font-size: 4rem; color: #6c757d;"></i>
                    </div>
                </div>
                <h3 class="mb-3">Muni University</h3>
                <p class="text-muted mb-4">Official Verification Portal</p>
                <a href="https://www.muni.ac.ug" class="btn btn-primary">
                    <i class="bi bi-building"></i> Visit University Website
                </a>
            </div>
            
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="text-center py-4 mt-4 border-top">
            <p class="text-muted small mb-0">
                &copy; <?php echo date('Y'); ?> <a href="https://www.muni.ac.ug" class="text-decoration-none text-primary" target="_blank">Muni University</a>. 
                All Rights Reserved. | Official Verification System
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
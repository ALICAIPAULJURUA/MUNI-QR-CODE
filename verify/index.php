<?php
require_once '../config/database.php';

// Get QR token from URL query parameter
$token = $_GET['token'] ?? null;

// Initialize variables
$profile = null;
$qrData = null;
$isValid = false;
$error = null;

try {
    if ($token) {
        $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE token = ?");
        $stmt->execute([$token]);
        $qrData = $stmt->fetch();
        
        if ($qrData) {
            if ($qrData['status'] === 'active') {
                $isValid = true;
                
                $stmt = $pdo->prepare("UPDATE qr_codes SET scan_count = scan_count + 1, last_scan = NOW() WHERE id = ?");
                $stmt->execute([$qrData['id']]);
                
                $stmt = $pdo->prepare("INSERT INTO scans (qr_id) VALUES (?)");
                $stmt->execute([$qrData['id']]);
                
                $stmt = $pdo->query("SELECT * FROM profiles LIMIT 1");
                $profile = $stmt->fetch();
            } else {
                $error = 'This QR Code has been deactivated.';
            }
        } else {
            $error = 'Invalid QR Code. Please scan a valid verification code.';
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM profiles LIMIT 1");
        $profile = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE status = 'active' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $qrData = $stmt->fetch();
        
        if ($profile && $qrData) {
            $isValid = true;
        }
    }
} catch (PDOException $e) {
    $error = 'System error. Please try again later.';
}

$showVerification = ($isValid && $profile);

// Get logo
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
</head>
<body class="bg-light">
    <div class="container py-4">
        <?php if ($showVerification): ?>
            <div class="verification-card fade-in">
                <div class="text-center mb-3">
                    <?php if ($logoFile && file_exists('../assets/uploads/logos/' . $logoFile)): ?>
                        <img src="../assets/uploads/logos/<?php echo htmlspecialchars($logoFile); ?>" 
                             alt="Muni University Logo" style="max-height: 80px; width: auto;">
                    <?php else: ?>
                        <h2 class="text-primary">Muni University</h2>
                    <?php endif; ?>
                </div>
                
                <div class="text-center">
                    <div class="verification-badge">
                        <i class="bi bi-check-circle-fill"></i>
                        VERIFIED OFFICIAL PROFILE
                    </div>
                </div>
                
                <div class="text-center">
                    <?php if ($profile['photo'] && file_exists('../assets/uploads/profiles/' . $profile['photo'])): ?>
                        <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($profile['photo']); ?>" 
                             alt="Profile Photo" class="profile-image">
                    <?php else: ?>
                        <div class="profile-image bg-secondary d-flex align-items-center justify-content-center text-white" 
                             style="width: 180px; height: 180px; border-radius: 50%; margin: 0 auto 1.5rem;">
                            <i class="bi bi-person-fill" style="font-size: 5rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center">
                    <h2 class="mb-1"><?php echo htmlspecialchars($profile['full_name'] ?? 'Vice Chancellor'); ?></h2>
                    <h5 class="text-primary mb-1"><?php echo htmlspecialchars($profile['title'] ?? 'Office of the Vice Chancellor'); ?></h5>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($profile['office'] ?? 'Muni University'); ?></p>
                    
                    <?php if (!empty($profile['biography'])): ?>
                        <p class="mb-4" style="font-size: 1.05rem; line-height: 1.7;"><?php echo nl2br(htmlspecialchars($profile['biography'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                    <?php if (!empty($profile['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>" class="btn btn-primary">
                            <i class="bi bi-envelope"></i> Email
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($profile['phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($profile['phone']); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-telephone"></i> Call
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($profile['website'])): ?>
                        <a href="<?php echo htmlspecialchars($profile['website']); ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-globe"></i> Website
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($profile['linkedin']) || !empty($profile['facebook']) || !empty($profile['twitter'])): ?>
                    <hr>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <?php if (!empty($profile['linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($profile['linkedin']); ?>" target="_blank" class="social-link" title="LinkedIn">
                                <i class="bi bi-linkedin"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($profile['facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($profile['facebook']); ?>" target="_blank" class="social-link" title="Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($profile['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($profile['twitter']); ?>" target="_blank" class="social-link" title="Twitter / X">
                                <i class="bi bi-twitter-x"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
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
                </div>
            </div>
        <?php elseif ($error): ?>
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
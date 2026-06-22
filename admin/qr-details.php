<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    header('Location: manage-qr.php');
    exit;
}

// Get QR code details
$stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE id = ?");
$stmt->execute([$id]);
$qr = $stmt->fetch();

if (!$qr) {
    header('Location: manage-qr.php');
    exit;
}

// Get scan history
$stmt = $pdo->prepare("SELECT * FROM scans WHERE qr_id = ? ORDER BY scanned_at DESC LIMIT 20");
$stmt->execute([$id]);
$scanHistory = $stmt->fetchAll();

// Decode JSON data
$content = json_decode($qr['content_data'] ?? '{}', true);
$design = json_decode($qr['design_settings'] ?? '{}', true);

// Get profile photo
$photo = $content['photo'] ?? '';
$logo = $content['logo'] ?? '';

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-info-circle text-primary"></i> QR Code Details</h2>
        <div>
            <a href="qr-customize.php?id=<?php echo $qr['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="manage-qr.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- QR Preview -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-qr-code text-primary"></i> QR Code</h5>
                </div>
                <div class="card-body text-center">
                    <div id="qr-display" style="display: inline-block; background: white; padding: 20px; border-radius: 10px;"></div>
                    
                    <div class="mt-3">
                        <span class="badge <?php echo $qr['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo ucfirst($qr['status']); ?>
                        </span>
                    </div>
                    
                    <div class="mt-3">
                        <p class="mb-1"><strong>Token:</strong></p>
                        <code><?php echo $qr['token']; ?></code>
                    </div>
                    
                    <div class="mt-3">
                        <a href="qr-download.php?id=<?php echo $qr['id']; ?>" class="btn btn-success">
                            <i class="bi bi-download"></i> Download QR
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart text-primary"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h3><?php echo number_format($qr['scan_count']); ?></h3>
                            <small class="text-muted">Total Scans</small>
                        </div>
                        <div class="col-6">
                            <h3><?php echo $qr['last_scan'] ? date('M j, Y', strtotime($qr['last_scan'])) : 'N/A'; ?></h3>
                            <small class="text-muted">Last Scan</small>
                        </div>
                    </div>
                    <hr>
                    <p class="mb-1"><strong>Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($qr['created_at'])); ?></p>
                    <p class="mb-0"><strong>Updated:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($qr['updated_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Content & Design -->
        <div class="col-md-8">
            <!-- Content -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-text text-primary"></i> Content Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($content['title'] ?? 'N/A'); ?></p>
                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($content['full_name'] ?? 'N/A'); ?></p>
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($content['title_position'] ?? 'N/A'); ?></p>
                            <p><strong>Office:</strong> <?php echo htmlspecialchars($content['office'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($content['email'])): ?>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($content['email']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($content['phone'])): ?>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($content['phone']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($content['website'])): ?>
                                <p><strong>Website:</strong> <a href="<?php echo htmlspecialchars($content['website']); ?>" target="_blank"><?php echo htmlspecialchars($content['website']); ?></a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($content['biography'])): ?>
                        <div class="mt-2">
                            <strong>Biography:</strong>
                            <p class="mt-1"><?php echo nl2br(htmlspecialchars($content['biography'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Social Links -->
                    <?php if (!empty($content['linkedin']) || !empty($content['facebook']) || !empty($content['twitter'])): ?>
                        <div class="mt-3">
                            <strong>Social Links:</strong>
                            <div class="mt-2">
                                <?php if (!empty($content['linkedin'])): ?>
                                    <a href="<?php echo htmlspecialchars($content['linkedin']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">LinkedIn</a>
                                <?php endif; ?>
                                <?php if (!empty($content['facebook'])): ?>
                                    <a href="<?php echo htmlspecialchars($content['facebook']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Facebook</a>
                                <?php endif; ?>
                                <?php if (!empty($content['twitter'])): ?>
                                    <a href="<?php echo htmlspecialchars($content['twitter']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Twitter</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Images -->
                    <?php if (!empty($photo) && file_exists('../assets/uploads/profiles/' . $photo)): ?>
                        <div class="mt-3">
                            <strong>Profile Photo:</strong>
                            <br>
                            <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($photo); ?>" alt="Profile" style="max-width: 100px; border-radius: 50%; margin-top: 5px;">
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($logo) && file_exists('../assets/uploads/logos/' . $logo)): ?>
                        <div class="mt-3">
                            <strong>University Logo:</strong>
                            <br>
                            <img src="../assets/uploads/logos/<?php echo htmlspecialchars($logo); ?>" alt="Logo" style="max-height: 60px; margin-top: 5px;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Design Settings -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-palette text-primary"></i> Design Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Frame:</strong> <?php echo ucfirst($design['frame'] ?? 'rounded'); ?></p>
                            <p><strong>Pattern:</strong> <?php echo ucfirst($design['pattern'] ?? 'dots'); ?></p>
                            <p><strong>Corner:</strong> <?php echo ucfirst($design['corner'] ?? 'square'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Logo Placement:</strong> <?php echo ucfirst($design['logo_placement'] ?? 'center'); ?></p>
                            <p><strong>Size:</strong> <?php echo $design['size'] ?? 300; ?>px</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>QR Color:</strong> <span style="display:inline-block; width:20px; height:20px; background:<?php echo $design['color'] ?? '#8B0000'; ?>; border-radius:3px; vertical-align:middle;"></span></p>
                            <p><strong>Background:</strong> <span style="display:inline-block; width:20px; height:20px; background:<?php echo $design['background'] ?? '#FFFFFF'; ?>; border:1px solid #ddd; border-radius:3px; vertical-align:middle;"></span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Scan History -->
            <?php if (!empty($scanHistory)): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Recent Scans</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scanHistory as $index => $scan): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo date('F j, Y \a\t g:i A', strtotime($scan['scanned_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const url = '<?php echo APP_URL . '/verify?token=' . $qr['token']; ?>';
    const container = document.getElementById('qr-display');
    const color = '<?php echo $design['color'] ?? '#8B0000'; ?>';
    const bgColor = '<?php echo $design['background'] ?? '#FFFFFF'; ?>';
    
    new QRCode(container, {
        text: url,
        width: 200,
        height: 200,
        colorDark: color,
        colorLight: bgColor,
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
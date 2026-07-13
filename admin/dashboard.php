<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

// Get statistics
try {
    // Total QR codes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM qr_codes");
    $totalQRCodes = $stmt->fetch()['total'] ?? 0;
    
    // Active QR codes
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM qr_codes WHERE status = 'active'");
    $activeQRCodes = $stmt->fetch()['active'] ?? 0;
    
    // Total scans
    $stmt = $pdo->query("SELECT SUM(scan_count) as total FROM qr_codes");
    $totalScans = $stmt->fetch()['total'] ?? 0;
    
    // Today's scans
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM scans WHERE DATE(scanned_at) = CURDATE()");
    $stmt->execute();
    $todayScans = $stmt->fetch()['count'] ?? 0;
    
    // Last scan
    $stmt = $pdo->query("SELECT scanned_at FROM scans ORDER BY scanned_at DESC LIMIT 1");
    $lastScan = $stmt->fetch();
    $lastScanDate = $lastScan ? date('F j, Y \a\t g:i A', strtotime($lastScan['scanned_at'])) : 'No scans yet';
    
    // Recent QR codes
    $stmt = $pdo->query("SELECT id, name, token, status, scan_count, created_at FROM qr_codes ORDER BY created_at DESC LIMIT 5");
    $recentQRCodes = $stmt->fetchAll();
    
    // Get user info
    $admin = getCurrentAdmin();
    $isSuperAdmin = isSuperAdmin();
    
} catch (PDOException $e) {
    $totalQRCodes = 0;
    $activeQRCodes = 0;
    $totalScans = 0;
    $todayScans = 0;
    $lastScanDate = 'Error loading data';
    $recentQRCodes = [];
    $isSuperAdmin = false;
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2 text-primary"></i> Dashboard</h2>
        <div>
            <span class="badge bg-primary me-2">
                <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            </span>
            <?php if ($isSuperAdmin): ?>
                <span class="badge bg-danger">Super Admin</span>
            <?php else: ?>
                <span class="badge bg-secondary">Admin</span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total QR Codes</h6>
                            <h2 class="mb-0"><?php echo number_format($totalQRCodes); ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-qr-code fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active QR Codes</h6>
                            <h2 class="mb-0"><?php echo number_format($activeQRCodes); ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-check-circle fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Scans</h6>
                            <h2 class="mb-0"><?php echo number_format($totalScans); ?></h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-eye fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Today's Scans</h6>
                            <h2 class="mb-0"><?php echo number_format($todayScans); ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-calendar-check fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row g-4">
        <div class="col-md-<?php echo $isSuperAdmin ? '6' : '12'; ?>">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lightning-fill text-warning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="qr-generate.php" class="btn btn-primary w-100 py-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-qr-code me-2 fs-4"></i>
                                <div>
                                    <div><strong>Generate QR</strong></div>
                                    <small class="text-muted">Create new QR code</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="profile.php" class="btn btn-outline-primary w-100 py-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-person me-2 fs-4"></i>
                                <div>
                                    <div><strong>Update Profile</strong></div>
                                    <small class="text-muted">Edit your information</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="qr-customize.php" class="btn btn-outline-success w-100 py-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-palette me-2 fs-4"></i>
                                <div>
                                    <div><strong>Customize QR</strong></div>
                                    <small class="text-muted">Download & customize</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($isSuperAdmin): ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-people text-primary"></i> User Management</h5>
                </div>
                <div class="card-body text-center py-4">
                    <i class="bi bi-people-fill" style="font-size: 3rem; color: #8B0000;"></i>
                    <h5 class="mt-3">Manage Users</h5>
                    <p class="text-muted">Create and manage admin users</p>
                    <a href="manage-users.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right"></i> Go to User Management
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent QR Codes -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Recent QR Codes</h5>
            <a href="manage-qr.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentQRCodes)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p>No QR codes generated yet.</p>
                    <a href="qr-create.php" class="btn btn-primary">Generate First QR Code</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Token</th>
                                <th>Status</th>
                                <th>Scans</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentQRCodes as $qr): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($qr['name']); ?></td>
                                <td><code><?php echo substr($qr['token'], 0, 16); ?>...</code></td>
                                <td>
                                    <span class="badge <?php echo $qr['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($qr['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($qr['scan_count']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($qr['created_at'])); ?></td>
                                <td>
                                    <a href="qr-details.php?id=<?php echo $qr['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
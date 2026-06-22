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
    
    // Profile data
    $stmt = $pdo->query("SELECT * FROM profiles LIMIT 1");
    $profile = $stmt->fetch();
    
} catch (PDOException $e) {
    $totalQRCodes = 0;
    $activeQRCodes = 0;
    $totalScans = 0;
    $todayScans = 0;
    $lastScanDate = 'Error loading data';
    $recentQRCodes = [];
    $profile = null;
}

require_once '../includes/header.php';
?>

<style>
/* Step Progress Styles */
.step-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 0 5px 0;
    position: relative;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.step-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #e9ecef;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s ease;
    position: relative;
    border: 3px solid #dee2e6;
}

.step-circle.active {
    background: #8B0000;
    border-color: #8B0000;
    box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.15);
}

.step-circle.completed {
    background: #28a745;
    border-color: #28a745;
}

.step-circle .checkmark {
    display: none;
}

.step-circle.completed .checkmark {
    display: block;
}

.step-circle.completed .step-number {
    display: none;
}

.step-label {
    margin-top: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #6c757d;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.step-label.active {
    color: #8B0000;
}

.step-label.completed {
    color: #28a745;
}

/* Arrow between steps */
.step-arrow {
    flex: 1;
    height: 3px;
    background: #dee2e6;
    position: relative;
    margin: 0 3px;
    z-index: 1;
    min-width: 30px;
}

.step-arrow.active {
    background: #8B0000;
}

.step-arrow.completed {
    background: #28a745;
}

.step-arrow::after {
    content: '';
    position: absolute;
    right: -6px;
    top: -4px;
    width: 0;
    height: 0;
    border-left: 8px solid #dee2e6;
    border-top: 5px solid transparent;
    border-bottom: 5px solid transparent;
}

.step-arrow.active::after {
    border-left-color: #8B0000;
}

.step-arrow.completed::after {
    border-left-color: #28a745;
}

@media (max-width: 768px) {
    .step-circle {
        width: 36px;
        height: 36px;
        font-size: 13px;
    }
    .step-label {
        font-size: 9px;
    }
    .step-arrow {
        min-width: 15px;
    }
    .step-arrow::after {
        right: -4px;
        border-left-width: 5px;
        border-top-width: 3px;
        border-bottom-width: 3px;
    }
}
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2 text-primary"></i> Dashboard</h2>
        <span class="badge bg-primary">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
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
    
    <!-- Generate QR Button & Steps -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-4">
            <div class="mb-3">
                <i class="bi bi-qr-code" style="font-size: 3.5rem; color: #8B0000;"></i>
            </div>
            <h4 class="mb-2">Generate New QR Code</h4>
            <p class="text-muted mb-4">Create a new QR code for verification in 4 simple steps</p>
            <a href="qr-create.php" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-plus-circle"></i> Generate New QR Code
            </a>
            
            <!-- Step-by-step guide with circles and arrows -->
            <div class="step-progress mt-4">
                <!-- Step 1 -->
                <div class="step-item">
                    <div class="step-circle active">
                        <span class="step-number">1</span>
                        <span class="checkmark">✓</span>
                    </div>
                    <div class="step-label active">Content</div>
                </div>
                
                <!-- Arrow 1-2 -->
                <div class="step-arrow active"></div>
                
                <!-- Step 2 -->
                <div class="step-item">
                    <div class="step-circle">
                        <span class="step-number">2</span>
                        <span class="checkmark">✓</span>
                    </div>
                    <div class="step-label">Design</div>
                </div>
                
                <!-- Arrow 2-3 -->
                <div class="step-arrow"></div>
                
                <!-- Step 3 -->
                <div class="step-item">
                    <div class="step-circle">
                        <span class="step-number">3</span>
                        <span class="checkmark">✓</span>
                    </div>
                    <div class="step-label">Preview</div>
                </div>
                
                <!-- Arrow 3-4 -->
                <div class="step-arrow"></div>
                
                <!-- Step 4 -->
                <div class="step-item">
                    <div class="step-circle">
                        <span class="step-number">4</span>
                        <span class="checkmark">✓</span>
                    </div>
                    <div class="step-label">Create</div>
                </div>
            </div>
            
            <p class="text-muted small mt-3">
                <i class="bi bi-arrow-right"></i> Click "Generate New QR Code" to get started
            </p>
        </div>
    </div>
    
    <!-- Recent QR Codes -->
    <div class="card border-0 shadow-sm">
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
    
    <!-- Profile Preview -->
    <?php if ($profile): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-person-badge text-primary"></i> Current Vice Chancellor Profile</h5>
            <div class="row g-3 mt-2">
                <div class="col-md-8">
                    <h4><?php echo htmlspecialchars($profile['full_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($profile['title']); ?> | <?php echo htmlspecialchars($profile['office']); ?></p>
                    <?php if ($profile['biography']): ?>
                        <p class="mb-2"><?php echo htmlspecialchars(substr($profile['biography'], 0, 150)) . '...'; ?></p>
                    <?php endif; ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($profile['email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-envelope"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($profile['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($profile['phone']); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-telephone"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($profile['website']): ?>
                            <a href="<?php echo htmlspecialchars($profile['website']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-globe"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($profile['photo'] && file_exists('../assets/uploads/profiles/' . $profile['photo'])): ?>
                    <div class="col-md-4 text-center">
                        <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($profile['photo']); ?>" 
                             alt="Profile Photo" class="img-fluid rounded-circle" style="max-width: 150px;">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
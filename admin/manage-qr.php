<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        if ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE qr_codes SET status = 'active' WHERE id = ?");
            $stmt->execute([$id]);
            $message = "QR Code activated successfully!";
        } elseif ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE qr_codes SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            $message = "QR Code deactivated successfully!";
        } elseif ($action === 'delete') {
            // Get QR image path
            $stmt = $pdo->prepare("SELECT qr_image FROM qr_codes WHERE id = ?");
            $stmt->execute([$id]);
            $qr = $stmt->fetch();
            
            if ($qr && $qr['qr_image'] && file_exists('../assets/uploads/qrcodes/' . $qr['qr_image'])) {
                unlink('../assets/uploads/qrcodes/' . $qr['qr_image']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM qr_codes WHERE id = ?");
            $stmt->execute([$id]);
            $message = "QR Code deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Operation failed: " . $e->getMessage();
    }
}

// Get total count for pagination
$stmt = $pdo->query("SELECT COUNT(*) as total FROM qr_codes");
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get QR codes
$stmt = $pdo->prepare("SELECT * FROM qr_codes ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);
$qrCodes = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-list-ul text-primary"></i> Manage QR Codes</h2>
        <a href="qr-create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New QR
        </a>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php if (empty($qrCodes)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p>No QR codes found.</p>
                    <a href="qr-create.php" class="btn btn-primary">Create Your First QR Code</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>QR Preview</th>
                                <th>Name</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Scans</th>
                                <th>Last Scan</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($qrCodes as $qr): ?>
                            <tr>
                                <td>
                                    <div id="preview-<?php echo $qr['id']; ?>" style="width: 60px; height: 60px;"></div>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const url = '<?php echo APP_URL . '/verify?token=' . $qr['token']; ?>';
                                            const container = document.getElementById('preview-<?php echo $qr['id']; ?>');
                                            new QRCode(container, {
                                                text: url,
                                                width: 60,
                                                height: 60,
                                                colorDark: '#8B0000',
                                                colorLight: '#FFFFFF',
                                                correctLevel: QRCode.CorrectLevel.H
                                            });
                                        });
                                    </script>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($qr['name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><code><?php echo substr($qr['token'], 0, 12); ?>...</code></small>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($qr['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $qr['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($qr['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($qr['scan_count']); ?></td>
                                <td>
                                    <?php echo $qr['last_scan'] ? date('M j, Y g:i A', strtotime($qr['last_scan'])) : 'Never'; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="qr-details.php?id=<?php echo $qr['id']; ?>" class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="qr-customize.php?id=<?php echo $qr['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="qr-download.php?id=<?php echo $qr['id']; ?>" class="btn btn-outline-success" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <?php if ($qr['status'] === 'active'): ?>
                                            <a href="?action=deactivate&id=<?php echo $qr['id']; ?>" class="btn btn-outline-warning" title="Deactivate">
                                                <i class="bi bi-pause-circle"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=activate&id=<?php echo $qr['id']; ?>" class="btn btn-outline-success" title="Activate">
                                                <i class="bi bi-play-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $qr['id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this QR code?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<?php require_once '../includes/footer.php'; ?>
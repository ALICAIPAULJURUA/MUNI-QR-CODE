<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

$message = '';
$error = '';
$admin = getCurrentAdmin();
$isSuperAdmin = isSuperAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Change Username
    if ($action === 'change_username') {
        $new_username = trim($_POST['new_username'] ?? '');
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($new_username)) {
            $error = 'Please enter a new username.';
        } elseif (strlen($new_username) < 3) {
            $error = 'Username must be at least 3 characters long.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
            $error = 'Username can only contain letters, numbers, and underscores.';
        } elseif (empty($password_confirm)) {
            $error = 'Please enter your password to confirm.';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
                $stmt->execute([$admin['id']]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password_confirm, $user['password'])) {
                    // Check if username already exists (excluding current user)
                    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ? AND deleted_at IS NULL");
                    $stmt->execute([$new_username, $admin['id']]);
                    if ($stmt->fetch()) {
                        $error = 'Username already taken. Please choose another.';
                    } else {
                        // Update username
                        $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
                        $stmt->execute([$new_username, $admin['id']]);
                        
                        // Update session with new username
                        $_SESSION['admin_username'] = $new_username;
                        
                        $message = 'Username changed successfully!';
                        
                        // Refresh admin data
                        $admin = getCurrentAdmin();
                    }
                } else {
                    $error = 'Current password is incorrect.';
                }
            } catch (PDOException $e) {
                $error = 'Failed to change username. Please try again.';
                error_log('Username change error: ' . $e->getMessage());
            }
        }
    }
    
    // Change Password
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
                $stmt->execute([$admin['id']]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($current_password, $user['password'])) {
                    // Hash and update new password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $admin['id']]);
                    
                    $message = 'Password changed successfully! Please login again with your new password.';
                    
                    // Optionally log out the user to force re-login
                    // session_destroy();
                    // header('Location: login.php');
                    // exit;
                } else {
                    $error = 'Current password is incorrect.';
                }
            } catch (PDOException $e) {
                $error = 'Failed to change password. Please try again.';
                error_log('Password change error: ' . $e->getMessage());
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-gear text-primary"></i> Settings</h2>
        <div>
            <?php if ($isSuperAdmin): ?>
                <span class="badge bg-danger">Super Admin</span>
            <?php else: ?>
                <span class="badge bg-secondary">Admin</span>
            <?php endif; ?>
            <span class="badge bg-primary"><?php echo htmlspecialchars($admin['username']); ?></span>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row g-4">
        <!-- Change Username -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person text-primary"></i> Change Username</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_username">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" disabled>
                            <small class="text-muted">Your current username</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Username</label>
                            <input type="text" class="form-control" name="new_username" 
                                   placeholder="Enter new username" required minlength="3">
                            <small class="text-muted">Minimum 3 characters, letters, numbers, and underscores only</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm with Current Password</label>
                            <input type="password" class="form-control" name="password_confirm" 
                                   placeholder="Enter your current password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-pencil"></i> Change Username
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-key text-primary"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" 
                                   placeholder="Enter current password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" 
                                   placeholder="Enter new password" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" 
                                   placeholder="Confirm new password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-arrow-repeat"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Info & Role (Admin Only) -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle text-primary"></i> Account Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>User ID:</strong> <?php echo $admin['id']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Role:</strong> 
                            <?php if ($isSuperAdmin): ?>
                                <span class="badge bg-danger">Super Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Admin</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Email:</strong> <?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong> 
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>
                    <?php if ($isSuperAdmin): ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-lock-fill text-danger"></i> 
                                You have Super Admin privileges. You can manage all users and system settings.
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                You have Admin privileges. You can manage QR codes and your profile.
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
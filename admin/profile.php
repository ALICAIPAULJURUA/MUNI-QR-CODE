<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

$message = '';
$error = '';
$admin = getCurrentAdmin();

// Get current admin details
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin['id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Update profile
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            try {
                // Check if email already exists for other users
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$email, $user['id']]);
                if ($stmt->fetch()) {
                    $error = 'Email already exists.';
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $user['id']]);
                    
                    // Update session
                    $_SESSION['admin_name'] = $name;
                    $_SESSION['admin_email'] = $email;
                    
                    $message = 'Profile updated successfully!';
                    $user['name'] = $name;
                    $user['email'] = $email;
                }
            } catch (PDOException $e) {
                $error = 'Failed to update profile.';
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person-circle text-primary"></i> My Profile</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
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
    
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pencil-square text-primary"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small class="text-muted">Username cannot be changed here. Go to Settings to change username.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst(str_replace('_', ' ', $user['role'] ?? 'Admin')); ?>" disabled>
                            <small class="text-muted">Role cannot be changed here.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Status</label>
                            <input type="text" class="form-control" value="<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>" disabled>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                        
                        <a href="settings.php" class="btn btn-outline-secondary">
                            <i class="bi bi-gear"></i> Change Password / Username
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle text-primary"></i> Account Info</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <strong>User ID:</strong> <?php echo $user['id']; ?>
                        </li>
                        <li class="mb-2">
                            <strong>Role:</strong>
                            <?php if (isset($user['role']) && $user['role'] === 'super_admin'): ?>
                                <span class="badge bg-danger">Super Admin</span>
                            <?php else: ?>
                                <span class="badge bg-primary">Admin</span>
                            <?php endif; ?>
                        </li>
                        <li class="mb-2">
                            <strong>Status:</strong>
                            <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </li>
                        <?php if ($user['last_login']): ?>
                            <li class="mb-2">
                                <strong>Last Login:</strong>
                                <br>
                                <small><?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?></small>
                            </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <strong>Member Since:</strong>
                            <br>
                            <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                        </li>
                    </ul>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="settings.php" class="btn btn-outline-primary">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <?php if (isset($user['role']) && $user['role'] === 'super_admin'): ?>
                            <a href="manage-users.php" class="btn btn-outline-success">
                                <i class="bi bi-people"></i> Manage Users
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireSuperAdmin();

$message = '';
$error = '';

// Get all admins (except deleted ones)
$stmt = $pdo->query("SELECT * FROM admins WHERE deleted_at IS NULL ORDER BY id ASC");
$admins = $stmt->fetchAll();

// Get current admin
$currentAdmin = getCurrentAdmin();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Don't allow deleting self
    if ($id == $_SESSION['admin_id']) {
        $error = "You cannot delete your own account.";
    } else {
        try {
            if ($action === 'delete') {
                // Soft delete - mark as deleted
                $stmt = $pdo->prepare("UPDATE admins SET deleted_at = NOW() WHERE id = ? AND role != 'super_admin'");
                $stmt->execute([$id]);
                $message = "User deleted successfully!";
            } elseif ($action === 'activate') {
                $stmt = $pdo->prepare("UPDATE admins SET is_active = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $message = "User activated successfully!";
            } elseif ($action === 'deactivate') {
                $stmt = $pdo->prepare("UPDATE admins SET is_active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $message = "User deactivated successfully!";
            }
            // Refresh admin list
            $stmt = $pdo->query("SELECT * FROM admins WHERE deleted_at IS NULL ORDER BY id ASC");
            $admins = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Operation failed: " . $e->getMessage();
        }
    }
}

// Handle create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    // Validate
    if (empty($username) || empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            // Create user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, name, email, password, role, is_active, created_by) VALUES (?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([$username, $name, $email, $hash, $role, $_SESSION['admin_id']]);
            $message = "User created successfully!";
            
            // Refresh admin list
            $stmt = $pdo->query("SELECT * FROM admins WHERE deleted_at IS NULL ORDER BY id ASC");
            $admins = $stmt->fetchAll();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people text-primary"></i> Manage Users</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="bi bi-plus-circle"></i> Add New User
        </button>
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
    
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php if (empty($admins)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-people fs-1 d-block mb-3"></i>
                    <p>No users found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'super_admin'): ?>
                                        <span class="badge bg-danger">Super Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($user['is_active']): ?>
                                                <a href="?action=deactivate&id=<?php echo $user['id']; ?>" class="btn btn-outline-warning" title="Deactivate">
                                                    <i class="bi bi-pause-circle"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=activate&id=<?php echo $user['id']; ?>" class="btn btn-outline-success" title="Activate">
                                                    <i class="bi bi-play-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($user['role'] !== 'super_admin'): ?>
                                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Current user</span>
                                    <?php endif; ?>
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

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus text-primary"></i> Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required minlength="3">
                        <small class="text-muted">Minimum 3 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <small class="text-muted">Super Admin can create users; Admin can only manage QR codes</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_user" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
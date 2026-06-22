<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

$message = '';
$error = '';
$admin = getCurrentAdmin();

// Get profile data
$stmt = $pdo->query("SELECT * FROM profiles LIMIT 1");
$profile = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Update Profile
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $office = trim($_POST['office'] ?? '');
        $biography = trim($_POST['biography'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');
        $facebook = trim($_POST['facebook'] ?? '');
        $twitter = trim($_POST['twitter'] ?? '');
        
        if (empty($full_name) || empty($title) || empty($office)) {
            $error = 'Full Name, Title, and Office are required.';
        } else {
            try {
                if ($profile) {
                    $stmt = $pdo->prepare("
                        UPDATE profiles SET 
                            full_name = ?, title = ?, office = ?, biography = ?,
                            email = ?, phone = ?, website = ?, linkedin = ?, facebook = ?, twitter = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$full_name, $title, $office, $biography, $email, $phone, $website, $linkedin, $facebook, $twitter, $profile['id']]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO profiles (full_name, title, office, biography, email, phone, website, linkedin, facebook, twitter)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$full_name, $title, $office, $biography, $email, $phone, $website, $linkedin, $facebook, $twitter]);
                }
                $message = 'Profile updated successfully!';
                
                // Reload profile
                $stmt = $pdo->query("SELECT * FROM profiles LIMIT 1");
                $profile = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
    
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
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
                    $stmt->execute([$new_username, $admin['id']]);
                    if ($stmt->fetch()) {
                        $error = 'Username already taken. Please choose another.';
                    } else {
                        // Update username
                        $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
                        $stmt->execute([$new_username, $admin['id']]);
                        
                        // Update session
                        $_SESSION['admin_username'] = $new_username;
                        
                        $message = 'Username changed successfully!';
                        $admin = getCurrentAdmin();
                    }
                } else {
                    $error = 'Current password is incorrect.';
                }
            } catch (PDOException $e) {
                $error = 'Failed to change username. Please try again.';
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
                    // Update password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $admin['id']]);
                    $message = 'Password changed successfully!';
                } else {
                    $error = 'Current password is incorrect.';
                }
            } catch (PDOException $e) {
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
    
    // Upload Photo
    if ($action === 'upload_photo') {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $filename = 'profile_' . time() . '.' . $ext;
                $target = '../assets/uploads/profiles/' . $filename;
                
                if (!is_dir('../assets/uploads/profiles/')) {
                    mkdir('../assets/uploads/profiles/', 0777, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    // Delete old photo
                    if ($profile && $profile['photo'] && file_exists('../assets/uploads/profiles/' . $profile['photo'])) {
                        unlink('../assets/uploads/profiles/' . $profile['photo']);
                    }
                    
                    if ($profile) {
                        $stmt = $pdo->prepare("UPDATE profiles SET photo = ? WHERE id = ?");
                        $stmt->execute([$filename, $profile['id']]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO profiles (photo) VALUES (?)");
                        $stmt->execute([$filename]);
                    }
                    $message = 'Photo uploaded successfully!';
                    
                    // Reload profile
                    $stmt = $pdo->query("SELECT * FROM profiles LIMIT 1");
                    $profile = $stmt->fetch();
                } else {
                    $error = 'Failed to upload photo.';
                }
            } else {
                $error = 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP.';
            }
        } else {
            $error = 'Please select a photo to upload.';
        }
    }
    
    // Upload Logo
    if ($action === 'upload_logo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];
            
            if (in_array($ext, $allowed)) {
                $filename = 'logo_' . time() . '.' . $ext;
                $target = '../assets/uploads/logos/' . $filename;
                
                if (!is_dir('../assets/uploads/logos/')) {
                    mkdir('../assets/uploads/logos/', 0777, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    // Delete old logo
                    if (is_dir('../assets/uploads/logos/')) {
                        $files = scandir('../assets/uploads/logos/');
                        foreach ($files as $f) {
                            if ($f !== '.' && $f !== '..' && $f !== $filename) {
                                if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico'])) {
                                    unlink('../assets/uploads/logos/' . $f);
                                }
                            }
                        }
                    }
                    $message = 'Logo uploaded successfully!';
                } else {
                    $error = 'Failed to upload logo.';
                }
            } else {
                $error = 'Invalid file type. Allowed: JPG, PNG, GIF, SVG, WEBP, ICO.';
            }
        } else {
            $error = 'Please select a logo to upload.';
        }
    }
}

// Get current logo
$logoPath = '../assets/uploads/logos/';
$currentLogo = null;
if (is_dir($logoPath)) {
    $files = scandir($logoPath);
    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico'])) {
            $currentLogo = $file;
            break;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-gear text-primary"></i> Settings</h2>
        <span class="badge bg-primary">System Configuration</span>
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
        <!-- Profile Information -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge text-primary"></i> Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" 
                                       value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" 
                                       value="<?php echo htmlspecialchars($profile['title'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Office <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="office" 
                                       value="<?php echo htmlspecialchars($profile['office'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Biography</label>
                                <textarea class="form-control" name="biography" rows="3"><?php echo htmlspecialchars($profile['biography'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" 
                                       value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">LinkedIn</label>
                                <input type="url" class="form-control" name="linkedin" 
                                       value="<?php echo htmlspecialchars($profile['linkedin'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Facebook</label>
                                <input type="url" class="form-control" name="facebook" 
                                       value="<?php echo htmlspecialchars($profile['facebook'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Twitter / X</label>
                                <input type="url" class="form-control" name="twitter" 
                                       value="<?php echo htmlspecialchars($profile['twitter'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Change Username -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person text-primary"></i> Change Username</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_username">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" disabled>
                            <small class="text-muted">Current username</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Username</label>
                            <input type="text" class="form-control" name="new_username" 
                                   placeholder="Enter new username" required minlength="3">
                            <small class="text-muted">Minimum 3 characters, letters, numbers, and underscores only</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm with Password</label>
                            <input type="password" class="form-control" name="password_confirm" 
                                   placeholder="Enter your current password" required>
                        </div>
                        <button type="submit" class="btn btn-info w-100 text-white">
                            <i class="bi bi-pencil"></i> Change Username
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-key text-primary"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-arrow-repeat"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Profile Photo -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-image text-primary"></i> Profile Photo</h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($profile && $profile['photo'] && file_exists('../assets/uploads/profiles/' . $profile['photo'])): ?>
                        <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($profile['photo']); ?>" 
                             alt="Profile Photo" class="img-fluid rounded-circle" style="width: 120px; height: 120px; object-fit: cover; margin-bottom: 1rem;">
                    <?php else: ?>
                        <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                             style="width: 120px; height: 120px; margin-bottom: 1rem;">
                            <i class="bi bi-person-fill" style="font-size: 3rem; color: #6c757d;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_photo">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="photo" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-upload"></i> Upload Photo
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- University Logo -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-building text-primary"></i> University Logo</h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($currentLogo && file_exists('../assets/uploads/logos/' . $currentLogo)): ?>
                        <img src="../assets/uploads/logos/<?php echo htmlspecialchars($currentLogo); ?>" 
                             alt="University Logo" class="img-fluid mb-3" style="max-height: 80px;">
                        <p class="text-muted small">Current logo (will appear as site icon)</p>
                    <?php else: ?>
                        <div class="text-muted mb-3">
                            <i class="bi bi-building" style="font-size: 2rem;"></i>
                            <p class="small">No logo uploaded</p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_logo">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="logo" accept="image/*" required>
                            <small class="text-muted">Recommended: PNG or SVG for best quality</small>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-upload"></i> Upload Logo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
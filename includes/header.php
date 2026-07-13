<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in for admin pages
$isLoggedIn = isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
$currentPage = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$isSuperAdmin = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muni University QR Verification System</title>
    <link rel="icon" href="https://www.muni.ac.ug/favicon.ico">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Navigation Styles */
        .navbar {
            background: #8B0000 !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            padding: 0.8rem 0;
        }
        .navbar .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.7rem 1.2rem !important;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .navbar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
        }
        .navbar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.4rem;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-brand img {
            height: 35px;
            width: auto;
            border-radius: 4px;
        }
        .navbar-brand i {
            margin-right: 8px;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            padding: 8px;
        }
        .dropdown-item {
            border-radius: 8px;
            padding: 8px 16px;
            transition: all 0.2s;
        }
        .dropdown-item:hover {
            background: #f8f9fa;
        }
        .dropdown-item.text-danger:hover {
            background: #f8d7da;
            color: #721c24 !important;
        }
        .dropdown-item i {
            margin-right: 8px;
        }
        .dropdown-item-text {
            font-weight: 600;
            color: #333;
        }
        .main-content {
            min-height: calc(100vh - 70px);
            padding-bottom: 20px;
        }
        .role-badge {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }
        .role-badge.super-admin {
            background: #dc3545;
            color: white;
        }
        .role-badge.admin {
            background: #6c757d;
            color: white;
        }
        @media (max-width: 768px) {
            .navbar .nav-link {
                padding: 0.5rem 0.8rem !important;
            }
            .navbar-brand img {
                height: 28px;
            }
        }
    </style>
</head>
<body>
    <?php if ($isLoggedIn): ?>
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <?php 
                // Check if logo exists for navbar
                $logoNavPath = '../assets/uploads/logos/';
                $logoNav = null;
                if (is_dir($logoNavPath)) {
                    $files = scandir($logoNavPath);
                    foreach ($files as $file) {
                        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                            $logoNav = $file;
                            break;
                        }
                    }
                }
                if ($logoNav && file_exists('../assets/uploads/logos/' . $logoNav)): ?>
                    <img src="../assets/uploads/logos/<?php echo htmlspecialchars($logoNav); ?>" alt="Muni University">
                <?php else: ?>
                    <i class="bi bi-shield-check"></i>
                <?php endif; ?>
                Muni VC QR
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'qr-create.php' ? 'active' : ''; ?>" href="qr-create.php">
                            <i class="bi bi-plus-circle"></i> Create QR
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'manage-qr.php' ? 'active' : ''; ?>" href="manage-qr.php">
                            <i class="bi bi-list-ul"></i> Manage QR
                        </a>
                    </li>
                    <?php if ($isSuperAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'manage-users.php' ? 'active' : ''; ?>" href="manage-users.php">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($adminName); ?>
                            <?php if ($isSuperAdmin): ?>
                                <span class="role-badge super-admin">Super Admin</span>
                            <?php else: ?>
                                <span class="role-badge admin">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <span class="dropdown-item-text">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($adminName); ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $isSuperAdmin ? '🔑 Super Admin' : '👤 Admin'; ?>
                                    </small>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li>
                            <?php if ($isSuperAdmin): ?>
                            <li>
                                <a class="dropdown-item" href="manage-users.php">
                                    <i class="bi bi-people"></i> Manage Users
                                </a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="main-content">
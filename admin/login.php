<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'muni_vc_qr';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = '';
$username = '';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid password. Please try again.';
                }
            } else {
                $error = 'Username not found. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'System error. Please try again later.';
        }
    }
}

// Get logo if exists
$logoPath = '../assets/uploads/logos/';
$logoFile = null;
if (is_dir($logoPath)) {
    $files = scandir($logoPath);
    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico'])) {
            $logoFile = $file;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Muni QR Code System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #8B0000 0%, #C41E24 50%, #A00000 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-box {
            background: white;
            padding: 40px 35px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 420px;
            max-width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header .logo {
            max-height: 70px;
            width: auto;
            margin-bottom: 12px;
        }
        .login-header h2 {
            color: #8B0000;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .login-header p {
            color: #6c757d;
            font-size: 14px;
            font-weight: 400;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
            background: #f8f9fa;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #8B0000;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #8B0000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #A00000; }
        .btn:active { transform: scale(0.98); }
        .error {
            color: #721c24;
            margin-bottom: 15px;
            padding: 12px 15px;
            background: #f8d7da;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }
        .error:empty { display: none; }
        .login-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            font-size: 13px;
            color: #6c757d;
        }
        .login-footer a {
            color: #8B0000;
            text-decoration: none;
        }
        .login-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <?php if ($logoFile && file_exists('../assets/uploads/logos/' . $logoFile)): ?>
                <img src="../assets/uploads/logos/<?php echo htmlspecialchars($logoFile); ?>" 
                     alt="Muni University Logo" class="logo">
            <?php endif; ?>
            <h2>Muni QR Code System</h2>
            <p>Administrative Login</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> Muni University. All Rights Reserved.
        </div>
    </div>
</body>
</html>
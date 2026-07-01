<?php
session_start();

// Database connection
require_once '../config/database.php';

$error = '';

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
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Muni University QR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #8B0000 0%, #C41E24 50%, #A00000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container { width: 100%; max-width: 420px; }
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header .icon { font-size: 3rem; color: #8B0000; margin-bottom: 0.5rem; font-weight: 700; }
        .login-header h1 { color: #8B0000; font-size: 1.8rem; font-weight: 700; }
        .login-header p { color: #6c757d; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .input-group { position: relative; display: flex; align-items: center; }
        .input-group .input-icon {
            position: absolute;
            left: 12px;
            color: #999;
            font-size: 1rem;
            pointer-events: none;
        }
        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background: #f8f9fa;
        }
        .input-group input:focus {
            outline: none;
            border-color: #8B0000;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }
        .input-group .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
        }
        .input-group .toggle-password:hover { color: #333; }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #8B0000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover { background: #A00000; }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .login-footer {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }
        .login-footer .credentials {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .login-footer .credentials code {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        .login-footer .credentials .row {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 5px;
        }
        .text-muted { color: #6c757d; }
        .fw-bold { font-weight: 600; }
        .mt-2 { margin-top: 0.5rem; }
        .small { font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="icon">◆</div>
                <h1>Muni VC QR</h1>
                <p>Administrative Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <span>!</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-group">
                        <span class="input-icon">U</span>
                        <input type="text" id="username" name="username" 
                               placeholder="Enter your username or email" 
                               value="" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <span class="input-icon">P</span>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter your password" 
                               value="" required>
                        <button type="button" class="toggle-password" id="togglePassword">
                            Show
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">Login</button>
            </form>
            
            <div class="login-footer">
                <div class="credentials">
                    <div class="fw-bold mb-2">Default Login Credentials</div>
                    <div class="row">
                        <span>Username: <code>admin</code></span>
                        <span>Password: <code>admin123</code></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.textContent = type === 'password' ? 'Show' : 'Hide';
            });
        });
    </script>
</body>
</html>
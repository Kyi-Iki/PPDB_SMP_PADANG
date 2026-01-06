<?php
/**
 * File: admin/login.php
 * Deskripsi: Halaman login untuk admin
 */

session_start();
require_once '../config/database.php';
require_once '../includes/csrf.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['id_user']) && $_SESSION['role'] == 'admin') {
    header("Location: index.php");
    exit();
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi CSRF token
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan refresh halaman dan coba lagi.";
    } else {
        $username = clean_input($_POST['username']);
        $password_input = $_POST['password'];
    
    // Query ke database - ambil admin/operator berdasarkan username
    $sql = "SELECT * FROM users 
            WHERE username = ? AND role IN ('super_admin', 'operator') AND is_active = 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verifikasi password dengan password_verify()
        if (password_verify($password_input, $user['password'])) {
            // Set session
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['id_sekolah'] = $user['id_sekolah']; // Untuk operator
            
            // Update last login
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id_user = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $user['id_user']);
            mysqli_stmt_execute($update_stmt);
            
            log_aktivitas($user['id_user'], 'Login', $user['role'] . ' login berhasil');
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Username atau password salah!";
            log_aktivitas(0, 'Login Gagal', "Admin login gagal: $username");
        }
    } else {
        $error = "Username atau password salah!";
        log_aktivitas(0, 'Login Gagal', "Admin login gagal: $username");
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - PPDB SMP</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/design-system.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            /* Fallback + CSS variable */
            background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 50%, #2563eb 100%);
            background: var(--gradient-hero, linear-gradient(135deg, #1e3a5f 0%, #1e40af 50%, #2563eb 100%));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .login-header {
            /* Fallback + CSS variable */
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            background: var(--gradient-primary, linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%));
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            border-radius: 20px 20px 0 0;
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 0.2rem rgba(30, 60, 114, 0.15);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e2e8f0;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .input-group .btn {
            border: 2px solid #e2e8f0;
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .input-group .form-control:not(:last-child) {
            border-radius: 0;
        }
        
        .btn-login {
            /* Fallback + CSS variable */
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            background: var(--gradient-primary, linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%));
            border: none;
            padding: 0.875rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-login:hover {
            background: #1e3a5f;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 58, 95, 0.4);
            color: white;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .divider span {
            padding: 0 1rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="fw-bold mb-2" style="color: white !important;">Admin Panel</h3>
                <p class="mb-0" style="color: rgba(255,255,255,0.9) !important;">PPDB SMP <?php echo TAHUN_AJARAN; ?></p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label class="form-label fw-600">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" name="username" 
                                   placeholder="Masukkan username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-600">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" name="password" 
                                   id="password" placeholder="Masukkan password" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Login sebagai Admin
                    </button>
                </form>
                
                <div class="divider">
                    <span>atau</span>
                </div>
                
                <div class="text-center">
                    <a href="../index.php" class="text-decoration-none text-muted">
                        <i class="fas fa-arrow-left me-2"></i>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-white mb-1">
                <i class="fas fa-info-circle me-2"></i>
                Admin Default
            </p>
            <small class="text-white opacity-75">
                Username: <strong>admin</strong> | Password: <strong>admin123</strong>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
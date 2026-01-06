<?php
/**
 * File: siswa/login.php
 * Deskripsi: Halaman login untuk siswa/calon peserta didik
 */

session_start();
require_once '../config/database.php';
require_once '../includes/csrf.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['id_user']) && $_SESSION['role'] == 'siswa') {
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
    
    // Query ke database - ambil user berdasarkan username saja
    $sql = "SELECT u.*, s.id_siswa, s.nama_lengkap 
            FROM users u
            LEFT JOIN siswa s ON u.id_user = s.id_user
            WHERE u.username = ? AND u.role = 'siswa' AND u.is_active = 1";
    
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
            $_SESSION['id_siswa'] = $user['id_siswa'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            
            // Update last login
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id_user = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $user['id_user']);
            mysqli_stmt_execute($update_stmt);
            
            // Log aktivitas
            log_aktivitas($user['id_user'], 'Login', 'Login siswa berhasil');
            
            // Redirect ke dashboard
            header("Location: index.php");
            exit();
        } else {
            $error = "Username atau password salah!";
            log_aktivitas(0, 'Login Gagal', "Username: $username");
        }
    } else {
        $error = "Username atau password salah!";
        log_aktivitas(0, 'Login Gagal', "Username: $username");
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
    <title>Login Siswa - PPDB SMP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/design-system.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            background: var(--gradient-hero);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 450px;
            margin: 0 auto;
        }
        .login-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            padding: 40px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: var(--primary-700);
            font-weight: 700;
        }
        .btn-login {
            background: var(--gradient-primary) !important;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 58, 95, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h2>🎓 Login Siswa</h2>
                    <p class="text-muted">PPDB SMP <?php echo TAHUN_AJARAN; ?></p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Nama Pengguna</label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" 
                               placeholder="Ketik nama pengguna yang Anda buat saat daftar" required autofocus>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Contoh: andi2012 atau budi_siswa</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Kata Sandi</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" 
                               placeholder="Ketik kata sandi Anda" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100 btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Masuk
                    </button>
                </form>
                
                <!-- Help Section -->
                <div class="text-center mt-4 p-3 bg-light rounded">
                    <p class="mb-2 text-muted small">Lupa nama pengguna atau kata sandi?</p>
                    <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', KONTAK_TELEPON); ?>?text=Halo,%20saya%20lupa%20password%20akun%20PPDB" 
                       class="btn btn-success btn-sm" target="_blank">
                        <i class="fab fa-whatsapp me-1"></i>Hubungi Admin via WhatsApp
                    </a>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="mb-0">Belum punya akun?</p>
                    <a href="register.php" class="btn btn-outline-primary mt-2">
                        <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                    </a>
                </div>
                
                <div class="text-center mt-3">
                    <a href="../index.php" class="text-muted text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

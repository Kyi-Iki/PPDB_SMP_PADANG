<?php
/**
 * File: siswa/register.php
 * Deskripsi: Halaman registrasi akun calon peserta didik
 */

session_start();
require_once '../config/database.php';
require_once '../includes/csrf.php';

$error = '';
$success = '';

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi CSRF
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan refresh halaman.";
    } else {
        $nisn = clean_input($_POST['nisn']);
        $nik = clean_input($_POST['nik']);
        $nama_lengkap = clean_input($_POST['nama_lengkap']);
        $email = clean_input($_POST['email']);
        $username = clean_input($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validasi
        if (strlen($nisn) != 10) {
            $error = "NISN harus 10 digit!";
        } elseif (strlen($nik) != 16) {
            $error = "NIK harus 16 digit!";
        } elseif (strlen($password) < 6) {
            $error = "Password minimal 6 karakter!";
        } elseif ($password != $confirm_password) {
            $error = "Password tidak cocok!";
        } else {
            // Cek username sudah ada
            $check_sql = "SELECT id_user FROM users WHERE username = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $username);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = "Username sudah digunakan!";
            } else {
                // Cek NISN sudah ada
                $check_nisn = "SELECT id_siswa FROM siswa WHERE nisn = ?";
                $stmt_nisn = mysqli_prepare($conn, $check_nisn);
                mysqli_stmt_bind_param($stmt_nisn, "s", $nisn);
                mysqli_stmt_execute($stmt_nisn);
                $result_nisn = mysqli_stmt_get_result($stmt_nisn);
                
                if (mysqli_num_rows($result_nisn) > 0) {
                    $error = "NISN sudah terdaftar!";
                } else {
                    // Mulai transaksi
                    mysqli_begin_transaction($conn);
                    
                    try {
                        // Insert ke tabel users (password hash dengan bcrypt)
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $sql_user = "INSERT INTO users (username, password, email, role) 
                                    VALUES (?, ?, ?, 'siswa')";
                        $stmt_user = mysqli_prepare($conn, $sql_user);
                        mysqli_stmt_bind_param($stmt_user, "sss", $username, $password_hash, $email);
                        mysqli_stmt_execute($stmt_user);
                        $id_user = mysqli_insert_id($conn);
                        
                        // Insert ke tabel siswa (data dasar)
                        $sql_siswa = "INSERT INTO siswa (id_user, nisn, nik, nama_lengkap, tanggal_lahir, jenis_kelamin) 
                                      VALUES (?, ?, ?, ?, '2000-01-01', 'L')";
                        $stmt_siswa = mysqli_prepare($conn, $sql_siswa);
                        mysqli_stmt_bind_param($stmt_siswa, "isss", $id_user, $nisn, $nik, $nama_lengkap);
                        mysqli_stmt_execute($stmt_siswa);
                        
                        // Commit transaksi
                        mysqli_commit($conn);
                        
                        // Log aktivitas
                        log_aktivitas($id_user, 'Register', "Registrasi akun baru: $username");
                        
                        $success = "Registrasi berhasil! Silakan login.";
                        
                        // Redirect setelah 2 detik
                        header("refresh:2;url=login.php");
                        
                    } catch (Exception $e) {
                        // Rollback jika error
                        mysqli_rollback($conn);
                        $error = "Registrasi gagal: " . $e->getMessage();
                    }
                }
            }
        }
    } // End CSRF check
}?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - PPDB SMP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/design-system.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            background: var(--gradient-hero);
            min-height: 100vh;
            padding: 40px 0;
        }
        .register-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: var(--primary-700);
            font-weight: 700;
        }
        .btn-register {
            background: var(--gradient-primary) !important;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 58, 95, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-card">
            <div class="register-header">
                <h2>📝 Registrasi Akun</h2>
                <p class="text-muted">PPDB SMP Tahun Ajaran <?php echo TAHUN_AJARAN; ?></p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>Error!</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <strong>Berhasil!</strong> <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrf_input(); ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nisn" class="form-label">NISN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nisn" name="nisn" 
                               maxlength="10" pattern="[0-9]{10}" 
                               placeholder="10 digit NISN" required>
                        <small class="text-muted">Nomor Induk Siswa Nasional</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="nik" class="form-label">NIK <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nik" name="nik" 
                               maxlength="16" pattern="[0-9]{16}" 
                               placeholder="16 digit NIK" required>
                        <small class="text-muted">Nomor Induk Kependudukan</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                           placeholder="Nama sesuai akta kelahiran" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="email@contoh.com">
                    <small class="text-muted">Opsional, untuk notifikasi</small>
                </div>
                
                <hr class="my-4">
                
                <h5 class="mb-3">Buat Akun Login</h5>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" 
                           minlength="5" placeholder="Minimal 5 karakter" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="6" placeholder="Minimal 6 karakter" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               minlength="6" placeholder="Ketik ulang password" required>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="agree" required>
                    <label class="form-check-label" for="agree">
                        Saya menyetujui syarat dan ketentuan PPDB SMP
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register w-100">
                    Daftar Sekarang
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">Sudah punya akun?</p>
                <a href="login.php" class="text-decoration-none fw-bold">
                    Login di sini →
                </a>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

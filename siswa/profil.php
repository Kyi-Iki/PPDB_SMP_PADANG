<?php
/**
 * File: siswa/profil.php
 * Deskripsi: Halaman profil dan edit data diri siswa
 */

session_start();
require_once '../config/database.php';

check_siswa();

$id_siswa = $_SESSION['id_siswa'];
$error = '';
$success = '';

// Ambil data siswa
$sql = "SELECT s.*, u.username, u.email FROM siswa s 
        JOIN users u ON s.id_user = u.id_user 
        WHERE s.id_siswa = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_siswa);
mysqli_stmt_execute($stmt);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Ambil data orang tua
$sql_ortu = "SELECT * FROM orang_tua WHERE id_siswa = ?";
$stmt_ortu = mysqli_prepare($conn, $sql_ortu);
mysqli_stmt_bind_param($stmt_ortu, "i", $id_siswa);
mysqli_stmt_execute($stmt_ortu);
$ortu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_ortu));

// Proses update password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Cek password lama
    $sql_cek = "SELECT password FROM users WHERE id_user = ?";
    $stmt_cek = mysqli_prepare($conn, $sql_cek);
    mysqli_stmt_bind_param($stmt_cek, "i", $siswa['id_user']);
    mysqli_stmt_execute($stmt_cek);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));
    
    if (!password_verify($password_lama, $user['password'])) {
        $error = "Password lama tidak sesuai!";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter!";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $sql_update = "UPDATE users SET password = ? WHERE id_user = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "si", $password_hash, $siswa['id_user']);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $success = "Password berhasil diubah!";
            log_aktivitas($_SESSION['id_user'], 'Update Password', 'Password berhasil diubah');
        } else {
            $error = "Gagal mengubah password!";
        }
    }
}

// Proses update email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_email'])) {
    $email_baru = clean_input($_POST['email_baru']);
    
    if (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        $sql_update = "UPDATE users SET email = ? WHERE id_user = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "si", $email_baru, $siswa['id_user']);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $success = "Email berhasil diubah!";
            log_aktivitas($_SESSION['id_user'], 'Update Email', "Email diubah menjadi: $email_baru");
            // Refresh data
            $siswa['email'] = $email_baru;
        } else {
            $error = "Gagal mengubah email!";
        }
    }
}

// Proses update no telepon
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_telepon'])) {
    $no_telepon = clean_input($_POST['no_telepon']);
    
    $sql_update = "UPDATE siswa SET no_telepon = ? WHERE id_siswa = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "si", $no_telepon, $id_siswa);
    
    if (mysqli_stmt_execute($stmt_update)) {
        $success = "Nomor telepon berhasil diubah!";
        log_aktivitas($_SESSION['id_user'], 'Update Telepon', "Telepon diubah menjadi: $no_telepon");
        $siswa['no_telepon'] = $no_telepon;
    } else {
        $error = "Gagal mengubah nomor telepon!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - PPDB SMP</title>
    
    <?php include '../includes/head_siswa.php'; ?>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .profile-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        
        .profile-header h3, .profile-header p {
            color: white !important;
        }
        
        .profile-header .opacity-75 {
            opacity: 0.9 !important;
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-right: 1.5rem;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-label {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #2d3748;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/sidebar_siswa.php'; ?>
            </div>
            
            <div class="col-md-9">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="d-flex align-items-center">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?php echo $siswa['nama_lengkap']; ?></h3>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-id-card me-2"></i>NISN: <?php echo $siswa['nisn']; ?>
                            </p>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-user-circle me-2"></i>Username: <?php echo $siswa['username']; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <!-- Data Pribadi -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-user text-primary me-2"></i>Data Pribadi
                            </h5>
                            
                            <div class="mb-3">
                                <div class="info-label">Nama Lengkap</div>
                                <div class="info-value"><?php echo $siswa['nama_lengkap']; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-label">NISN</div>
                                <div class="info-value"><?php echo $siswa['nisn']; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-label">NIK</div>
                                <div class="info-value"><?php echo $siswa['nik'] ?? '-'; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-label">Tempat, Tanggal Lahir</div>
                                <div class="info-value">
                                    <?php echo ($siswa['tempat_lahir'] ?? '-') . ', ' . 
                                        (isset($siswa['tanggal_lahir']) ? tanggal_indonesia($siswa['tanggal_lahir']) : '-'); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-label">Jenis Kelamin</div>
                                <div class="info-value">
                                    <?php echo $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                </div>
                            </div>
                            
                            <div class="mb-0">
                                <div class="info-label">Asal Sekolah</div>
                                <div class="info-value"><?php echo $siswa['asal_sekolah'] ?? '-'; ?></div>
                            </div>
                            
                            <hr>
                            <a href="biodata.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Edit Biodata Lengkap
                            </a>
                        </div>
                        
                        <!-- Data Orang Tua -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-users text-success me-2"></i>Data Orang Tua
                            </h5>
                            
                            <div class="mb-3">
                                <div class="info-label">Nama Ayah</div>
                                <div class="info-value"><?php echo $ortu['nama_ayah'] ?? '-'; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-label">Nama Ibu</div>
                                <div class="info-value"><?php echo $ortu['nama_ibu'] ?? '-'; ?></div>
                            </div>
                            
                            <div class="mb-0">
                                <div class="info-label">No. Telepon Orang Tua</div>
                                <div class="info-value"><?php echo $ortu['no_telepon_ortu'] ?? '-'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Update Email -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-envelope text-info me-2"></i>Email
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email_baru" 
                                           value="<?php echo $siswa['email'] ?? ''; ?>" required>
                                </div>
                                <button type="submit" name="update_email" class="btn btn-info btn-sm">
                                    <i class="fas fa-save me-1"></i>Simpan Email
                                </button>
                            </form>
                        </div>
                        
                        <!-- Update No Telepon -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-phone text-warning me-2"></i>Nomor Telepon
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="tel" class="form-control" name="no_telepon" 
                                           value="<?php echo $siswa['no_telepon'] ?? ''; ?>" 
                                           placeholder="08xxxxxxxxxx" required>
                                </div>
                                <button type="submit" name="update_telepon" class="btn btn-warning btn-sm">
                                    <i class="fas fa-save me-1"></i>Simpan Telepon
                                </button>
                            </form>
                        </div>
                        
                        <!-- Update Password -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-lock text-danger me-2"></i>Ubah Password
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Password Lama</label>
                                    <input type="password" class="form-control" name="password_lama" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" name="password_baru" 
                                           minlength="6" required>
                                    <small class="text-muted">Minimal 6 karakter</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" name="konfirmasi_password" required>
                                </div>
                                <button type="submit" name="update_password" class="btn btn-danger btn-sm">
                                    <i class="fas fa-key me-1"></i>Ubah Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>


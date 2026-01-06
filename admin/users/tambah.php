<?php
/**
 * File: admin/users/tambah.php
 * Deskripsi: Form tambah user super_admin/operator
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/csrf.php';
check_admin();
check_super_admin(); // Only super_admin can add users

$error = '';
$success = '';

// Ambil daftar sekolah untuk dropdown
$sql_sekolah = "SELECT id_sekolah, nama_sekolah FROM sekolah WHERE status = 'aktif' ORDER BY nama_sekolah";
$sekolah_list = mysqli_query($conn, $sql_sekolah);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan coba lagi.";
    } else {
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = clean_input($_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $id_sekolah = isset($_POST['id_sekolah']) ? (int)$_POST['id_sekolah'] : null;
    
    // Validasi
    if (strlen($username) < 4) {
        $error = "Username minimal 4 karakter!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password != $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (!in_array($role, ['super_admin', 'operator'])) {
        $error = "Role tidak valid!";
    } elseif ($role == 'operator' && empty($id_sekolah)) {
        $error = "Operator harus memilih sekolah!";
    } else {
        // Cek username unik
        $sql_cek = "SELECT id_user FROM users WHERE username = ?";
        $stmt_cek = mysqli_prepare($conn, $sql_cek);
        mysqli_stmt_bind_param($stmt_cek, "s", $username);
        mysqli_stmt_execute($stmt_cek);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_cek)) > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Insert user baru
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Set id_sekolah null for super_admin
            if ($role == 'super_admin') {
                $id_sekolah = null;
            }
            
            $sql_insert = "INSERT INTO users (username, password, email, role, id_sekolah, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "ssssii", $username, $password_hash, $email, $role, $id_sekolah, $is_active);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                log_aktivitas($_SESSION['id_user'], 'Tambah User', "Menambahkan user: $username ($role)");
                $success = "User berhasil ditambahkan!";
                header("refresh:2;url=index.php");
            } else {
                $error = "Gagal menambahkan user!";
            }
            }
        }
    } // End CSRF check
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
    </style>
</head>
<body>
    <?php include '../../includes/navbar_admin.php'; ?>
    
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../../includes/sidebar_admin.php'; ?>
            </div>
            
            <div class="col-md-9">
                <a href="index.php" class="btn btn-outline-secondary mb-3">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
                
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>Tambah User Baru
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <?php echo csrf_input(); ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username *</label>
                                    <input type="text" class="form-control" name="username" 
                                           minlength="4" required autofocus>
                                    <small class="text-muted">Minimal 4 karakter</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-control" name="password" 
                                           minlength="6" required>
                                    <small class="text-muted">Minimal 6 karakter</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Konfirmasi Password *</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role *</label>
                                    <select class="form-select" name="role" id="role_select" required onchange="toggleSekolahField()">
                                        <option value="">Pilih Role</option>
                                        <option value="super_admin">Super Admin (Dinas)</option>
                                        <option value="operator">Operator (Sekolah)</option>
                                    </select>
                                    <small class="text-muted">
                                        Super Admin: Akses semua sekolah | Operator: Akses 1 sekolah
                                    </small>
                                </div>
                                <div class="col-md-6 mb-3" id="sekolah_field" style="display: none;">
                                    <label class="form-label">Sekolah *</label>
                                    <select class="form-select" name="id_sekolah" id="sekolah_select">
                                        <option value="">Pilih Sekolah</option>
                                        <?php while ($sch = mysqli_fetch_assoc($sekolah_list)): ?>
                                        <option value="<?php echo $sch['id_sekolah']; ?>">
                                            <?php echo $sch['nama_sekolah']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">Operator hanya dapat mengakses sekolah yang dipilih</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                               id="is_active" checked>
                                        <label class="form-check-label" for="is_active">Aktif</label>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSekolahField() {
            const role = document.getElementById('role_select').value;
            const sekolahField = document.getElementById('sekolah_field');
            const sekolahSelect = document.getElementById('sekolah_select');
            
            if (role === 'operator') {
                sekolahField.style.display = 'block';
                sekolahSelect.required = true;
            } else {
                sekolahField.style.display = 'none';
                sekolahSelect.required = false;
                sekolahSelect.value = '';
            }
        }
    </script>
</body>
</html>

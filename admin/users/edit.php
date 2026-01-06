<?php
/**
 * File: admin/users/edit.php
 * Deskripsi: Form edit user super_admin/operator dengan assignment sekolah
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/csrf.php';
check_admin();
check_super_admin(); // Only super_admin can edit users

$id_user = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if (!$id_user) {
    header("Location: index.php");
    exit();
}

// Ambil daftar sekolah
$sql_sekolah = "SELECT id_sekolah, nama_sekolah FROM sekolah WHERE status = 'aktif' ORDER BY nama_sekolah";
$sekolah_list = mysqli_query($conn, $sql_sekolah);

// Ambil data user
$sql = "SELECT u.*, s.nama_sekolah 
        FROM users u 
        LEFT JOIN sekolah s ON u.id_sekolah = s.id_sekolah 
        WHERE u.id_user = ? AND u.role IN ('super_admin', 'operator')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan coba lagi.";
    } else {
    $email = clean_input($_POST['email']);
    $role = clean_input($_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = $_POST['new_password'] ?? '';
    $id_sekolah = isset($_POST['id_sekolah']) ? (int)$_POST['id_sekolah'] : null;
    
    // Validasi role
    if (!in_array($role, ['super_admin', 'operator'])) {
        $error = "Role tidak valid!";
    } 
    // Cek jika mengubah status sendiri ke nonaktif
    elseif ($id_user == $_SESSION['id_user'] && !$is_active) {
        $error = "Tidak dapat menonaktifkan akun sendiri!";
    }
    // Cek jika operator tanpa sekolah
    elseif ($role == 'operator' && empty($id_sekolah)) {
        $error = "Operator harus memiliki sekolah!";
    } else {
        // Set id_sekolah null for super_admin
        if ($role == 'super_admin') {
            $id_sekolah = null;
        }
        
        // Update user
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $error = "Password minimal 6 karakter!";
            } else {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE users SET email = ?, role = ?, id_sekolah = ?, is_active = ?, password = ? WHERE id_user = ?";
                $stmt_update = mysqli_prepare($conn, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "ssiisi", $email, $role, $id_sekolah, $is_active, $password_hash, $id_user);
            }
        } else {
            $sql_update = "UPDATE users SET email = ?, role = ?, id_sekolah = ?, is_active = ? WHERE id_user = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "ssiii", $email, $role, $id_sekolah, $is_active, $id_user);
        }
        
        if (empty($error) && mysqli_stmt_execute($stmt_update)) {
            log_aktivitas($_SESSION['id_user'], 'Edit User', "Mengedit user: {$user['username']}");
            $success = "User berhasil diupdate!";
            
            // Refresh data
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id_user);
            mysqli_stmt_execute($stmt);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            
            // Reset sekolah list
            mysqli_data_seek($sekolah_list, 0);
        } elseif (empty($error)) {
            $error = "Gagal mengupdate user!";
        }
    } // End CSRF
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin PPDB</title>
    
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
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>Edit User: <?php echo $user['username']; ?>
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
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo $user['username']; ?>" 
                                           disabled readonly>
                                    <small class="text-muted">Username tidak dapat diubah</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo $user['email']; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" name="new_password" minlength="6">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role *</label>
                                    <select class="form-select" name="role" id="role_select" required onchange="toggleSekolahField()">
                                        <option value="super_admin" <?php echo $user['role'] == 'super_admin' ? 'selected' : ''; ?>>Super Admin (Dinas)</option>
                                        <option value="operator" <?php echo $user['role'] == 'operator' ? 'selected' : ''; ?>>Operator (Sekolah)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3" id="sekolah_field" style="<?php echo $user['role'] == 'operator' ? '' : 'display: none;'; ?>">
                                    <label class="form-label">Sekolah *</label>
                                    <select class="form-select" name="id_sekolah" id="sekolah_select">
                                        <option value="">Pilih Sekolah</option>
                                        <?php while ($sch = mysqli_fetch_assoc($sekolah_list)): ?>
                                        <option value="<?php echo $sch['id_sekolah']; ?>" 
                                                <?php echo $user['id_sekolah'] == $sch['id_sekolah'] ? 'selected' : ''; ?>>
                                            <?php echo $sch['nama_sekolah']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">Operator hanya dapat mengakses sekolah yang dipilih</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                               id="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Aktif</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Dibuat</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>" 
                                           disabled readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Login Terakhir</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Belum pernah'; ?>" 
                                           disabled readonly>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
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
            }
        }
        
        // Initialize on page load
        toggleSekolahField();
    </script>
</body>
</html>

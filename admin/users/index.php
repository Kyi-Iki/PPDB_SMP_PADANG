<?php
/**
 * File: admin/users/index.php
 * Deskripsi: Kelola pengguna admin dan operator
 */

session_start();
require_once '../../config/database.php';
check_admin();
check_super_admin(); // Only super_admin can manage users

$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';

// Proses hapus user
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id_user = (int)$_GET['id'];
    
    // Cek bukan diri sendiri
    if ($id_user == $_SESSION['id_user']) {
        $error = "Tidak dapat menghapus akun sendiri!";
    } else {
        $sql_delete = "DELETE FROM users WHERE id_user = ? AND role IN ('super_admin', 'operator')";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "i", $id_user);
        
        if (mysqli_stmt_execute($stmt_delete) && mysqli_affected_rows($conn) > 0) {
            $success = "User berhasil dihapus!";
            log_aktivitas($_SESSION['id_user'], 'Hapus User', "Menghapus user ID: $id_user");
        } else {
            $error = "Gagal menghapus user!";
        }
    }
}

// Ambil daftar user admin dan operator dengan info sekolah
$sql = "SELECT u.*, s.nama_sekolah 
        FROM users u 
        LEFT JOIN sekolah s ON u.id_sekolah = s.id_sekolah 
        WHERE u.role IN ('super_admin', 'operator') 
        ORDER BY u.role, u.username";
$result = mysqli_query($conn, $sql);

// Hitung statistik
$sql_stats = "SELECT 
              SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as admin_count,
              SUM(CASE WHEN role = 'operator' THEN 1 ELSE 0 END) as operator_count,
              SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
              FROM users WHERE role IN ('super_admin', 'operator')";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $sql_stats));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        .stat-icon.admin { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-icon.operator { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .stat-icon.active { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        
        .user-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .user-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: bold;
        }
        .user-avatar.super_admin { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .user-avatar.operator { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
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
                
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">
                            <i class="fas fa-users-cog text-primary me-2"></i>Kelola User
                        </h4>
                        <p class="text-muted mb-0">Manajemen admin dan operator sistem</p>
                    </div>
                    <a href="tambah.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tambah User
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon admin">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $stats['admin_count'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Admin</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon operator">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $stats['operator_count'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Operator</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon active">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $stats['active_count'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">User Aktif</p>
                        </div>
                    </div>
                </div>
                
                <!-- User List -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-bold">Daftar User</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) == 0): ?>
                        <p class="text-muted text-center py-4">Belum ada user</p>
                        <?php else: ?>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                        <div class="user-card">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar <?php echo $user['role']; ?> me-3">
                                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-0"><?php echo $user['username']; ?></h6>
                                            <small class="text-muted"><?php echo $user['email'] ?? '-'; ?></small>
                                            <?php if ($user['role'] == 'operator' && $user['nama_sekolah']): ?>
                                            <br><small class="text-info">
                                                <i class="fas fa-school me-1"></i><?php echo $user['nama_sekolah']; ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?php echo $user['role'] == 'super_admin' ? 'primary' : 'success'; ?>">
                                                <?php echo $user['role'] == 'super_admin' ? 'Super Admin' : 'Operator'; ?>
                                            </span>
                                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Login terakhir: <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Belum pernah'; ?>
                                        </small>
                                        <div>
                                            <a href="edit.php?id=<?php echo $user['id_user']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['id_user'] != $_SESSION['id_user']): ?>
                                            <a href="?delete=1&id=<?php echo $user['id_user']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

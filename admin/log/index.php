<?php
/**
 * File: admin/log/index.php
 * Deskripsi: Viewer log aktivitas sistem
 */

session_start();
require_once '../../config/database.php';
check_admin();
check_super_admin(); // Only super_admin can view logs

$tahun_ajaran = TAHUN_AJARAN;

// Filter parameters
$filter_user = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$filter_aktivitas = isset($_GET['aktivitas']) ? clean_input($_GET['aktivitas']) : '';
$filter_tanggal_dari = isset($_GET['dari']) ? clean_input($_GET['dari']) : '';
$filter_tanggal_sampai = isset($_GET['sampai']) ? clean_input($_GET['sampai']) : '';

// Pagination
$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Build query with filters
$where_conditions = ["1=1"];
$params = [];
$types = "";

if ($filter_user > 0) {
    $where_conditions[] = "la.id_user = ?";
    $params[] = $filter_user;
    $types .= "i";
}

if ($filter_aktivitas) {
    $where_conditions[] = "la.aktivitas LIKE ?";
    $params[] = "%$filter_aktivitas%";
    $types .= "s";
}

if ($filter_tanggal_dari) {
    $where_conditions[] = "DATE(la.waktu) >= ?";
    $params[] = $filter_tanggal_dari;
    $types .= "s";
}

if ($filter_tanggal_sampai) {
    $where_conditions[] = "DATE(la.waktu) <= ?";
    $params[] = $filter_tanggal_sampai;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total for pagination
$sql_count = "SELECT COUNT(*) as total FROM log_aktivitas la WHERE $where_clause";
if (!empty($params)) {
    $stmt_count = mysqli_prepare($conn, $sql_count);
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    mysqli_stmt_execute($stmt_count);
    $total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];
} else {
    $result_count = mysqli_query($conn, $sql_count);
    $total_rows = mysqli_fetch_assoc($result_count)['total'];
}
$total_pages = ceil($total_rows / $per_page);

// Main query
$sql = "SELECT la.*, u.username, u.role
        FROM log_aktivitas la
        LEFT JOIN users u ON la.id_user = u.id_user
        WHERE $where_clause
        ORDER BY la.waktu DESC
        LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $logs = mysqli_stmt_get_result($stmt);
} else {
    $logs = mysqli_query($conn, $sql);
}

// Get users for filter dropdown
$sql_users = "SELECT DISTINCT u.id_user, u.username, u.role 
              FROM log_aktivitas la 
              JOIN users u ON la.id_user = u.id_user 
              ORDER BY u.username";
$users = mysqli_query($conn, $sql_users);

// Get distinct aktivitas for filter
$sql_aktivitas = "SELECT DISTINCT aktivitas FROM log_aktivitas ORDER BY aktivitas";
$aktivitas_list = mysqli_query($conn, $sql_aktivitas);

// Statistics
$sql_stats = "SELECT 
              COUNT(*) as total_log,
              COUNT(DISTINCT id_user) as unique_users,
              COUNT(DISTINCT DATE(waktu)) as active_days
              FROM log_aktivitas";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $sql_stats));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Admin PPDB</title>
    
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
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin: 0 auto 1rem;
        }
        
        .log-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
            transition: all 0.2s;
        }
        .log-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .log-item.login { border-left-color: #28a745; }
        .log-item.logout { border-left-color: #6c757d; }
        .log-item.create { border-left-color: #17a2b8; }
        .log-item.update { border-left-color: #ffc107; }
        .log-item.delete { border-left-color: #dc3545; }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
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
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">
                            <i class="fas fa-history text-primary me-2"></i>Log Aktivitas
                        </h4>
                        <p class="text-muted mb-0">Riwayat aktivitas pengguna sistem</p>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <i class="fas fa-list"></i>
                            </div>
                            <h4 class="fw-bold mb-1"><?php echo number_format($stats['total_log']); ?></h4>
                            <p class="text-muted mb-0">Total Log</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4 class="fw-bold mb-1"><?php echo $stats['unique_users']; ?></h4>
                            <p class="text-muted mb-0">User Aktif</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h4 class="fw-bold mb-1"><?php echo $stats['active_days']; ?></h4>
                            <p class="text-muted mb-0">Hari Aktif</p>
                        </div>
                    </div>
                </div>
                
                <!-- Filter -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">User</label>
                            <select class="form-select" name="user">
                                <option value="">Semua User</option>
                                <?php while ($u = mysqli_fetch_assoc($users)): ?>
                                <option value="<?php echo $u['id_user']; ?>" 
                                        <?php echo $filter_user == $u['id_user'] ? 'selected' : ''; ?>>
                                    <?php echo $u['username']; ?> (<?php echo $u['role']; ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Aktivitas</label>
                            <select class="form-select" name="aktivitas">
                                <option value="">Semua Aktivitas</option>
                                <?php while ($a = mysqli_fetch_assoc($aktivitas_list)): ?>
                                <option value="<?php echo $a['aktivitas']; ?>"
                                        <?php echo $filter_aktivitas == $a['aktivitas'] ? 'selected' : ''; ?>>
                                    <?php echo $a['aktivitas']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Dari Tanggal</label>
                            <input type="date" class="form-control" name="dari" 
                                   value="<?php echo $filter_tanggal_dari; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Sampai Tanggal</label>
                            <input type="date" class="form-control" name="sampai" 
                                   value="<?php echo $filter_tanggal_sampai; ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($filter_user || $filter_aktivitas || $filter_tanggal_dari || $filter_tanggal_sampai): ?>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Reset Filter
                        </a>
                        <span class="ms-2 text-muted">Menampilkan <?php echo number_format($total_rows); ?> hasil</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Log List -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Riwayat Aktivitas</h5>
                        <span class="badge bg-primary"><?php echo number_format($total_rows); ?> log</span>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($logs) == 0): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Tidak ada log aktivitas</p>
                        </div>
                        <?php else: ?>
                        <?php while ($log = mysqli_fetch_assoc($logs)): 
                            $log_class = '';
                            if (stripos($log['aktivitas'], 'login') !== false) $log_class = 'login';
                            elseif (stripos($log['aktivitas'], 'logout') !== false) $log_class = 'logout';
                            elseif (stripos($log['aktivitas'], 'tambah') !== false || stripos($log['aktivitas'], 'create') !== false) $log_class = 'create';
                            elseif (stripos($log['aktivitas'], 'edit') !== false || stripos($log['aktivitas'], 'update') !== false || stripos($log['aktivitas'], 'koreksi') !== false) $log_class = 'update';
                            elseif (stripos($log['aktivitas'], 'hapus') !== false || stripos($log['aktivitas'], 'delete') !== false) $log_class = 'delete';
                        ?>
                        <div class="log-item <?php echo $log_class; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo $log['aktivitas']; ?></strong>
                                    <span class="badge bg-<?php echo $log['role'] == 'admin' ? 'primary' : ($log['role'] == 'operator' ? 'success' : 'secondary'); ?> ms-2">
                                        <?php echo $log['username'] ?? 'System'; ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['waktu'])); ?>
                                </small>
                            </div>
                            <?php if ($log['deskripsi']): ?>
                            <p class="text-muted mb-0 mt-1 small"><?php echo $log['deskripsi']; ?></p>
                            <?php endif; ?>
                            <small class="text-muted">
                                <i class="fas fa-globe me-1"></i><?php echo $log['ip_address'] ?? '-'; ?>
                            </small>
                        </div>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&user=<?php echo $filter_user; ?>&aktivitas=<?php echo urlencode($filter_aktivitas); ?>&dari=<?php echo $filter_tanggal_dari; ?>&sampai=<?php echo $filter_tanggal_sampai; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&user=<?php echo $filter_user; ?>&aktivitas=<?php echo urlencode($filter_aktivitas); ?>&dari=<?php echo $filter_tanggal_dari; ?>&sampai=<?php echo $filter_tanggal_sampai; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&user=<?php echo $filter_user; ?>&aktivitas=<?php echo urlencode($filter_aktivitas); ?>&dari=<?php echo $filter_tanggal_dari; ?>&sampai=<?php echo $filter_tanggal_sampai; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

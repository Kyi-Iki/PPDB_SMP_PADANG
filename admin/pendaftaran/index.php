<?php
/**
 * File: admin/pendaftaran/index.php
 * Deskripsi: Daftar pendaftar dengan filter & search advanced
 */

session_start();
require_once '../../config/database.php';
check_admin();

$tahun_ajaran = TAHUN_AJARAN;

// Ambil filter sekolah untuk operator
$operator_sekolah = get_sekolah_filter();

// Filter parameters
$filter_jalur = isset($_GET['jalur']) ? (int)$_GET['jalur'] : 0;
// Operator tidak boleh filter sekolah lain
$filter_sekolah = $operator_sekolah ? $operator_sekolah : (isset($_GET['sekolah']) ? (int)$_GET['sekolah'] : 0);
$filter_status = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Build query with filters
$where_conditions = ["p.tahun_ajaran = ?"];
$params = [$tahun_ajaran];
$types = "s";

// Operator harus filter sekolahnya
if ($operator_sekolah) {
    $where_conditions[] = "p.id_sekolah = ?";
    $params[] = $operator_sekolah;
    $types .= "i";
} elseif ($filter_sekolah > 0) {
    $where_conditions[] = "p.id_sekolah = ?";
    $params[] = $filter_sekolah;
    $types .= "i";
}

if ($filter_jalur > 0) {
    $where_conditions[] = "p.id_jalur = ?";
    $params[] = $filter_jalur;
    $types .= "i";
}

if ($filter_status) {
    $where_conditions[] = "p.status_verifikasi = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($search) {
    $where_conditions[] = "(s.nama_lengkap LIKE ? OR s.nisn LIKE ? OR p.nomor_pendaftaran LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total for pagination
$sql_count = "SELECT COUNT(*) as total FROM pendaftaran p
              JOIN siswa s ON p.id_siswa = s.id_siswa
              WHERE $where_clause";
$stmt_count = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt_count, $types, ...$params);
mysqli_stmt_execute($stmt_count);
$total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];
$total_pages = ceil($total_rows / $per_page);

// Main query
$sql = "SELECT p.*, s.nama_lengkap, s.nisn, s.no_telepon, sch.nama_sekolah, j.nama_jalur,
        hs.status_seleksi, hs.ranking
        FROM pendaftaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
        JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
        LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
        WHERE $where_clause
        ORDER BY p.tanggal_daftar DESC
        LIMIT $per_page OFFSET $offset";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$pendaftar = mysqli_stmt_get_result($stmt);

// Get filter options
$jalur_list = mysqli_query($conn, "SELECT * FROM jalur_ppdb ORDER BY id_jalur");
$sekolah_list = mysqli_query($conn, "SELECT * FROM sekolah WHERE status = 'aktif' ORDER BY nama_sekolah");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pendaftar - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-badge {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background: #667eea;
            color: white;
            font-weight: 500;
            border: none;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 2px;
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
                            <i class="fas fa-users text-primary me-2"></i>Daftar Pendaftar
                        </h4>
                        <p class="text-muted mb-0">Total: <?php echo number_format($total_rows); ?> pendaftar</p>
                    </div>
                    <a href="?export=csv" class="btn btn-success">
                        <i class="fas fa-file-csv me-2"></i>Export CSV
                    </a>
                </div>
                
                <!-- Filter Card -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Cari</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Nama, NISN, No. Pendaftaran...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Jalur</label>
                            <select class="form-select" name="jalur">
                                <option value="">Semua Jalur</option>
                                <?php while ($j = mysqli_fetch_assoc($jalur_list)): ?>
                                <option value="<?php echo $j['id_jalur']; ?>" 
                                        <?php echo $filter_jalur == $j['id_jalur'] ? 'selected' : ''; ?>>
                                    <?php echo $j['nama_jalur']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Sekolah</label>
                            <select class="form-select" name="sekolah">
                                <option value="">Semua Sekolah</option>
                                <?php while ($s = mysqli_fetch_assoc($sekolah_list)): ?>
                                <option value="<?php echo $s['id_sekolah']; ?>"
                                        <?php echo $filter_sekolah == $s['id_sekolah'] ? 'selected' : ''; ?>>
                                    <?php echo $s['nama_sekolah']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="terverifikasi" <?php echo $filter_status == 'terverifikasi' ? 'selected' : ''; ?>>Terverifikasi</option>
                                <option value="ditolak" <?php echo $filter_status == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($search || $filter_jalur || $filter_sekolah || $filter_status): ?>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Reset Filter
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Data Table -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>No. Pendaftaran</th>
                                        <th>Nama Siswa</th>
                                        <th>NISN</th>
                                        <th>Sekolah Tujuan</th>
                                        <th>Jalur</th>
                                        <th>Nilai</th>
                                        <th>Status</th>
                                        <th>Hasil</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($pendaftar) == 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>Tidak ada data pendaftar</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php while ($p = mysqli_fetch_assoc($pendaftar)): ?>
                                    <tr>
                                        <td>
                                            <code><?php echo $p['nomor_pendaftaran']; ?></code>
                                        </td>
                                        <td class="fw-bold"><?php echo $p['nama_lengkap']; ?></td>
                                        <td><?php echo $p['nisn']; ?></td>
                                        <td><?php echo $p['nama_sekolah']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $p['nama_jalur'] == 'Zonasi' ? 'primary' : 
                                                    ($p['nama_jalur'] == 'Prestasi' ? 'success' : 
                                                    ($p['nama_jalur'] == 'Afirmasi' ? 'warning' : 'info')); 
                                            ?>"><?php echo $p['nama_jalur']; ?></span>
                                        </td>
                                        <td><?php echo number_format($p['nilai_rapor_rata'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $p['status_verifikasi'] == 'terverifikasi' ? 'success' : 
                                                    ($p['status_verifikasi'] == 'ditolak' ? 'danger' : 'warning'); 
                                            ?>"><?php echo ucfirst($p['status_verifikasi']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($p['status_seleksi']): ?>
                                            <span class="badge bg-<?php echo $p['status_seleksi'] == 'lulus' ? 'success' : 'secondary'; ?>">
                                                <?php echo strtoupper($p['status_seleksi']); ?>
                                                <?php if ($p['ranking']): ?> #<?php echo $p['ranking']; ?><?php endif; ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="detail.php?id=<?php echo $p['id_pendaftaran']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&jalur=<?php echo $filter_jalur; ?>&sekolah=<?php echo $filter_sekolah; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&jalur=<?php echo $filter_jalur; ?>&sekolah=<?php echo $filter_sekolah; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&jalur=<?php echo $filter_jalur; ?>&sekolah=<?php echo $filter_sekolah; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
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
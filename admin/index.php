<?php
/**
 * File: admin/index.php
 * Deskripsi: Dashboard untuk admin
 */

session_start();
require_once '../config/database.php';

check_admin();

$tahun_ajaran = TAHUN_AJARAN;

// Ambil filter sekolah untuk operator (NULL untuk super_admin)
$sekolah_filter = get_sekolah_filter();
$sekolah_condition = $sekolah_filter ? " AND p.id_sekolah = $sekolah_filter" : "";

// Statistik Umum - Using prepared statements with operator filter
if ($sekolah_filter) {
    $sql_stats = "SELECT 
        (SELECT COUNT(*) FROM pendaftaran WHERE tahun_ajaran = ? AND id_sekolah = ?) as total_pendaftar,
        (SELECT COUNT(*) FROM pendaftaran WHERE tahun_ajaran = ? AND id_sekolah = ? AND status_verifikasi = 'terverifikasi') as terverifikasi,
        (SELECT COUNT(*) FROM pendaftaran WHERE tahun_ajaran = ? AND id_sekolah = ? AND status_verifikasi = 'pending') as pending,
        (SELECT COUNT(*) FROM hasil_seleksi hs 
         JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran 
         WHERE p.tahun_ajaran = ? AND p.id_sekolah = ? AND hs.status_seleksi = 'lulus') as lulus,
        (SELECT COUNT(*) FROM sekolah WHERE id_sekolah = ? AND status = 'aktif') as total_sekolah,
        (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE tahun_ajaran = ? AND id_sekolah = ?) as total_kuota";
    $stmt_stats = mysqli_prepare($conn, $sql_stats);
    mysqli_stmt_bind_param($stmt_stats, "sisisisisis", $tahun_ajaran, $sekolah_filter, $tahun_ajaran, $sekolah_filter, $tahun_ajaran, $sekolah_filter, $tahun_ajaran, $sekolah_filter, $sekolah_filter, $tahun_ajaran, $sekolah_filter);
} else {
    $sql_stats = "SELECT 
        (SELECT COUNT(*) FROM pendaftaran WHERE tahun_ajaran = ?) as total_pendaftar,
        (SELECT COUNT(*) FROM pendaftaran WHERE tahun_ajaran = ? AND status_verifikasi = 'terverifikasi') as terverifikasi,
        (SELECT COUNT(*) FROM pendaftaran WHERE tahun_ajaran = ? AND status_verifikasi = 'pending') as pending,
        (SELECT COUNT(*) FROM hasil_seleksi hs 
         JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran 
         WHERE p.tahun_ajaran = ? AND hs.status_seleksi = 'lulus') as lulus,
        (SELECT COUNT(*) FROM sekolah WHERE status = 'aktif') as total_sekolah,
        (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE tahun_ajaran = ?) as total_kuota";
    $stmt_stats = mysqli_prepare($conn, $sql_stats);
    mysqli_stmt_bind_param($stmt_stats, "sssss", $tahun_ajaran, $tahun_ajaran, $tahun_ajaran, $tahun_ajaran, $tahun_ajaran);
}
mysqli_stmt_execute($stmt_stats);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats));
mysqli_stmt_close($stmt_stats);

// Statistik per Jalur (dengan filter operator)
$sql_jalur = "SELECT j.nama_jalur, j.kode_jalur,
              COUNT(p.id_pendaftaran) as total,
              SUM(CASE WHEN p.status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as verified
              FROM jalur_ppdb j
              LEFT JOIN pendaftaran p ON j.id_jalur = p.id_jalur AND p.tahun_ajaran = ?" . 
              ($sekolah_filter ? " AND p.id_sekolah = ?" : "") . "
              GROUP BY j.id_jalur";
$stmt_jalur = mysqli_prepare($conn, $sql_jalur);
if ($sekolah_filter) {
    mysqli_stmt_bind_param($stmt_jalur, "si", $tahun_ajaran, $sekolah_filter);
} else {
    mysqli_stmt_bind_param($stmt_jalur, "s", $tahun_ajaran);
}
mysqli_stmt_execute($stmt_jalur);
$jalur_stats = mysqli_stmt_get_result($stmt_jalur);

// Pendaftar Terbaru (dengan filter operator)
$sql_recent = "SELECT p.*, s.nama_lengkap, s.nisn, sch.nama_sekolah, j.nama_jalur
               FROM pendaftaran p
               JOIN siswa s ON p.id_siswa = s.id_siswa
               JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
               JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
               WHERE p.tahun_ajaran = ?" .
               ($sekolah_filter ? " AND p.id_sekolah = ?" : "") . "
               ORDER BY p.tanggal_daftar DESC
               LIMIT 5";
$stmt_recent = mysqli_prepare($conn, $sql_recent);
if ($sekolah_filter) {
    mysqli_stmt_bind_param($stmt_recent, "si", $tahun_ajaran, $sekolah_filter);
} else {
    mysqli_stmt_bind_param($stmt_recent, "s", $tahun_ajaran);
}
mysqli_stmt_execute($stmt_recent);
$recent_pendaftar = mysqli_stmt_get_result($stmt_recent);

// Pendaftar Butuh Verifikasi (dengan filter operator)
$sql_need_verify = "SELECT COUNT(*) as total FROM pendaftaran 
                    WHERE tahun_ajaran = ? AND status_verifikasi = 'pending'" .
                    ($sekolah_filter ? " AND id_sekolah = ?" : "");
$stmt_verify = mysqli_prepare($conn, $sql_need_verify);
if ($sekolah_filter) {
    mysqli_stmt_bind_param($stmt_verify, "si", $tahun_ajaran, $sekolah_filter);
} else {
    mysqli_stmt_bind_param($stmt_verify, "s", $tahun_ajaran);
}
mysqli_stmt_execute($stmt_verify);
$need_verify = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_verify))['total'];
mysqli_stmt_close($stmt_verify);

// Statistik per Sekolah (operator hanya lihat sekolahnya)
$sql_sekolah_stats = "SELECT s.nama_sekolah, 
                      (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as kuota_dynamic,
                      COUNT(p.id_pendaftaran) as total_pendaftar,
                      SUM(CASE WHEN hs.status_seleksi = 'lulus' THEN 1 ELSE 0 END) as lulus
                      FROM sekolah s
                      LEFT JOIN pendaftaran p ON s.id_sekolah = p.id_sekolah AND p.tahun_ajaran = ?
                      LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                      WHERE s.status = 'aktif'" .
                      ($sekolah_filter ? " AND s.id_sekolah = ?" : "") . "
                      GROUP BY s.id_sekolah
                      LIMIT 5";
$stmt_sekolah = mysqli_prepare($conn, $sql_sekolah_stats);
if ($sekolah_filter) {
    mysqli_stmt_bind_param($stmt_sekolah, "si", $tahun_ajaran, $sekolah_filter);
} else {
    mysqli_stmt_bind_param($stmt_sekolah, "s", $tahun_ajaran);
}
mysqli_stmt_execute($stmt_sekolah);
$sekolah_stats = mysqli_stmt_get_result($stmt_sekolah);

// Data Pendaftaran 7 Hari Terakhir (untuk chart)
$sql_daily = "SELECT DATE(tanggal_daftar) as tanggal, COUNT(*) as total
              FROM pendaftaran 
              WHERE tahun_ajaran = ? AND tanggal_daftar >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              GROUP BY DATE(tanggal_daftar)
              ORDER BY tanggal";
$stmt_daily = mysqli_prepare($conn, $sql_daily);
mysqli_stmt_bind_param($stmt_daily, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt_daily);
$daily_result = mysqli_stmt_get_result($stmt_daily);

$daily_labels = [];
$daily_values = [];
while ($day = mysqli_fetch_assoc($daily_result)) {
    $daily_labels[] = date('d M', strtotime($day['tanggal']));
    $daily_values[] = (int)$day['total'];
}
mysqli_stmt_close($stmt_daily);

// Simpan jalur stats untuk pie chart
mysqli_data_seek($jalur_stats, 0);
$jalur_labels = [];
$jalur_values = [];
while ($j = mysqli_fetch_assoc($jalur_stats)) {
    $jalur_labels[] = $j['nama_jalur'];
    $jalur_values[] = (int)$j['total'];
}
mysqli_data_seek($jalur_stats, 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Admin - PPDB SMP</title>
    <?php include '../includes/head_admin.php'; ?>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">
                        <i class="fas fa-chart-line me-2"></i>
                        Dashboard Admin PPDB
                    </h2>
                    <p class="mb-0" style="color: rgba(255,255,255,0.9) !important;">
                        Selamat datang, <?php echo $_SESSION['username']; ?>! 
                        | Tahun Ajaran <?php echo $tahun_ajaran; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div style="color: rgba(255,255,255,0.85) !important;">
                        <i class="fas fa-clock me-2"></i>
                        <?php echo date('l, d F Y'); ?>
                    </div>
                    <div style="color: rgba(255,255,255,0.85) !important;">
                        <i class="fas fa-calendar me-2"></i>
                        Pukul <?php echo date('H:i'); ?> WIB
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/sidebar_admin.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Error Alert for Access Denied -->
                <?php if (isset($_GET['error']) && $_GET['error'] == 'access_denied'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-ban me-2"></i>
                    <strong>Akses Ditolak!</strong> Anda tidak memiliki izin untuk mengakses halaman tersebut. 
                    Fitur ini hanya tersedia untuk Super Admin.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Alert Verifikasi -->
                <?php if ($need_verify > 0): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Perhatian!</strong> Ada <?php echo $need_verify; ?> pendaftar yang menunggu verifikasi.
                    <a href="verifikasi/index.php" class="alert-link">Verifikasi Sekarang →</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card blue">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number text-primary">
                                <?php echo number_format($stats['total_pendaftar']); ?>
                            </div>
                            <div class="text-muted">Total Pendaftar</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card green">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number text-success">
                                <?php echo number_format($stats['terverifikasi']); ?>
                            </div>
                            <div class="text-muted">Terverifikasi</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card orange">
                            <div class="stat-icon orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number" style="color: #f093fb;">
                                <?php echo number_format($stats['pending']); ?>
                            </div>
                            <div class="text-muted">Menunggu</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card red">
                            <div class="stat-icon red">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-number text-danger">
                                <?php echo number_format($stats['lulus']); ?>
                            </div>
                            <div class="text-muted">Lulus</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="verifikasi/index.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Verifikasi</h6>
                                <p class="text-muted small mb-0">Verifikasi pendaftar</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="pendaftaran/index.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Data Pendaftar</h6>
                                <p class="text-muted small mb-0">Lihat pendaftar</p>
                            </div>
                        </a>
                    </div>
                    
                    <?php if (is_super_admin()): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="seleksi/proses.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Proses Seleksi</h6>
                                <p class="text-muted small mb-0">Jalankan seleksi</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="seleksi/publikasi.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Publikasi</h6>
                                <p class="text-muted small mb-0">Umumkan hasil</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="seleksi/hasil.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-list-ol"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Hasil Seleksi</h6>
                                <p class="text-muted small mb-0">Lihat hasil</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="sekolah/index.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-school"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Data Sekolah</h6>
                                <p class="text-muted small mb-0">Kelola sekolah</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="laporan/index.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-file-export"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Laporan</h6>
                                <p class="text-muted small mb-0">Cetak laporan</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="kuota/index.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Kuota Jalur</h6>
                                <p class="text-muted small mb-0">Atur kuota</p>
                            </div>
                        </a>
                    </div>
                    <?php else: ?>
                    <!-- Operator Quick Actions (4 items untuk grid seimbang) -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="sekolah/index.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-school"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Data Sekolah</h6>
                                <p class="text-muted small mb-0">Kelola sekolah</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="laporan/index.php" class="text-decoration-none">
                            <div class="quick-action-card h-100">
                                <div class="action-icon">
                                    <i class="fas fa-file-export"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Laporan</h6>
                                <p class="text-muted small mb-0">Cetak laporan</p>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Charts Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h6 class="fw-bold mb-0">
                                    <i class="fas fa-chart-line text-primary me-2"></i>
                                    Pendaftaran 7 Hari Terakhir
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="dailyChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h6 class="fw-bold mb-0">
                                    <i class="fas fa-chart-pie text-primary me-2"></i>
                                    Distribusi per Jalur
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="jalurChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3">
                    <!-- Statistik per Jalur -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);">
                                <h6 class="fw-bold mb-0" style="color: white !important;">
                                    <i class="fas fa-route me-2" style="color: white !important;"></i>
                                    Statistik per Jalur
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php while ($jalur = mysqli_fetch_assoc($jalur_stats)): ?>
                                <div class="jalur-stat">
                                    <div class="jalur-info">
                                        <div class="jalur-icon">
                                            <?php echo $jalur['kode_jalur']; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo $jalur['nama_jalur']; ?></div>
                                            <small class="text-muted">
                                                <?php echo $jalur['verified']; ?> terverifikasi dari <?php echo $jalur['total']; ?> pendaftar
                                            </small>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="fw-bold text-primary mb-0">
                                            <?php echo $jalur['total']; ?>
                                        </h4>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pendaftar Terbaru -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);">
                                <h6 class="fw-bold mb-0" style="color: white !important;">
                                    <i class="fas fa-user-clock me-2" style="color: white !important;"></i>
                                    Pendaftar Terbaru
                                </h6>
                                <a href="pendaftaran/index.php" class="btn btn-sm btn-light">
                                    Lihat Semua
                                </a>
                            </div>
                            <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama</th>
                                                <th>NISN</th>
                                                <th>Jalur</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Reset result pointer
                                            mysqli_data_seek($recent_pendaftar, 0);
                                            while ($p = mysqli_fetch_assoc($recent_pendaftar)): 
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo $p['nama_lengkap']; ?></div>
                                                    <small class="text-muted">
                                                        <?php echo $p['sekolah_pilihan_1'] ?? 'Belum pilih sekolah'; ?>
                                                    </small>
                                                </td>
                                                <td><?php echo $p['nisn']; ?></td>
                                                <td>
                                                    <span class="badge bg-primary-subtle text-primary">
                                                        <?php echo $p['nama_jalur']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $p['status_verifikasi'] == 'terverifikasi' ? 'success' : 
                                                            ($p['status_verifikasi'] == 'ditolak' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($p['status_verifikasi']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/scripts_admin.php'; ?>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Daily Registration Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($daily_labels); ?>,
            datasets: [{
                label: 'Jumlah Pendaftar',
                data: <?php echo json_encode($daily_values); ?>,
                borderColor: '#1e3a5f',
                backgroundColor: 'rgba(30, 58, 95, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    
    // Jalur Distribution Pie Chart
    const jalurCtx = document.getElementById('jalurChart').getContext('2d');
    new Chart(jalurCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($jalur_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($jalur_values); ?>,
                backgroundColor: ['#1e3a5f', '#059669', '#d97706', '#dc2626', '#0284c7']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            }
        }
    });
    </script>
</body>
</html>
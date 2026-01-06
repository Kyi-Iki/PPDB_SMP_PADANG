<?php
/**
 * File: admin/pengaturan.php
 * Deskripsi: Pengaturan GLOBAL sistem PPDB
 * 
 * Note: Jadwal per jalur diatur di jadwal_jalur.php
 */

session_start();
require_once '../config/database.php';

check_admin();

$error = '';
$success = '';
$tahun_ajaran = TAHUN_AJARAN;

// Ambil pengaturan yang ada
$sql_pengaturan = "SELECT * FROM pengaturan_ppdb WHERE tahun_ajaran = ?";
$stmt = mysqli_prepare($conn, $sql_pengaturan);
mysqli_stmt_bind_param($stmt, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt);
$pengaturan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Proses update pengaturan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_daftar_ulang = clean_input($_POST['tanggal_daftar_ulang']);
    $status_ppdb = clean_input($_POST['status_ppdb']);
    
    if ($pengaturan) {
        $sql = "UPDATE pengaturan_ppdb SET 
                tanggal_daftar_ulang = ?,
                status_ppdb = ?
                WHERE tahun_ajaran = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $tanggal_daftar_ulang, $status_ppdb, $tahun_ajaran);
    } else {
        $sql = "INSERT INTO pengaturan_ppdb 
                (tahun_ajaran, tanggal_mulai_daftar, tanggal_akhir_daftar, 
                 tanggal_seleksi, tanggal_pengumuman, tanggal_daftar_ulang, status_ppdb)
                VALUES (?, CURDATE(), CURDATE(), CURDATE(), CURDATE(), ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $tahun_ajaran, $tanggal_daftar_ulang, $status_ppdb);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        // SINKRONISASI: Update status di semua jadwal_jalur agar sama dengan status global
        // Status global dan jalur sekarang sama (1:1 mapping)
        $status_jalur = $status_ppdb;
        
        $sql_sync = "UPDATE jadwal_jalur SET status = ? WHERE tahun_ajaran = ?";
        $stmt_sync = mysqli_prepare($conn, $sql_sync);
        mysqli_stmt_bind_param($stmt_sync, "ss", $status_jalur, $tahun_ajaran);
        mysqli_stmt_execute($stmt_sync);
        $jalur_updated = mysqli_affected_rows($conn);
        
        log_aktivitas($_SESSION['id_user'], 'Update Pengaturan', "Pengaturan PPDB diperbarui. Status: $status_ppdb. Sinkron $jalur_updated jalur.");
        $success = "Pengaturan berhasil disimpan! Status <strong>$jalur_updated jalur</strong> telah disinkronisasi.";
        
        // Refresh data
        $stmt = mysqli_prepare($conn, $sql_pengaturan);
        mysqli_stmt_bind_param($stmt, "s", $tahun_ajaran);
        mysqli_stmt_execute($stmt);
        $pengaturan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    } else {
        $error = "Gagal menyimpan pengaturan!";
    }
}

// Statistik sistem
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM pendaftaran WHERE tahun_ajaran = ?) as total_pendaftar,
    (SELECT COUNT(*) FROM sekolah WHERE status = 'aktif' AND tahun_ajaran = ?) as total_sekolah,
    (SELECT COUNT(*) FROM users WHERE role = 'siswa') as total_siswa,
    (SELECT COUNT(*) FROM users WHERE role = 'admin') as total_admin";
$stmt_stats = mysqli_prepare($conn, $sql_stats);
mysqli_stmt_bind_param($stmt_stats, "ss", $tahun_ajaran, $tahun_ajaran);
mysqli_stmt_execute($stmt_stats);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pengaturan Sistem - Admin PPDB</title>
    <?php include '../includes/head_admin.php'; ?>
    <style>
        .setting-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .setting-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        .stat-mini {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .stat-mini h3 {
            color: #667eea;
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>
    
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/sidebar_admin.php'; ?>
            </div>
            
            <div class="col-md-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Pengaturan Sistem PPDB
                        </h4>
                        <span class="badge bg-light text-dark">Tahun Ajaran: <?php echo $tahun_ajaran; ?></span>
                    </div>
                    <div class="card-body">
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
                        
                        <!-- Link ke Jadwal Jalur -->
                        <div class="alert alert-info mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-alt fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Pengaturan Jadwal Per Jalur</h6>
                                    <p class="mb-0 small">Untuk mengatur jadwal pendaftaran, seleksi, dan pengumuman per jalur, gunakan menu <strong>Jadwal Jalur</strong>.</p>
                                </div>
                                <a href="jadwal_jalur.php" class="btn btn-info ms-auto">
                                    <i class="fas fa-arrow-right me-2"></i>Jadwal Jalur
                                </a>
                            </div>
                        </div>
                        
                        <form method="POST" action="">
                            <!-- Pengaturan Global -->
                            <div class="setting-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="setting-icon me-3">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Pengaturan Global</h5>
                                        <small class="text-muted">Pengaturan yang berlaku untuk semua jalur</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Daftar Ulang</label>
                                        <input type="date" class="form-control" name="tanggal_daftar_ulang"
                                               value="<?php echo $pengaturan['tanggal_daftar_ulang'] ?? ''; ?>" required>
                                        <small class="text-muted">Tanggal mulai daftar ulang bagi yang lulus</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status PPDB Global</label>
                                        <select class="form-select" name="status_ppdb" required>
                                            <option value="belum_dibuka" <?php echo ($pengaturan['status_ppdb'] ?? '') == 'belum_dibuka' ? 'selected' : ''; ?>>
                                                🔒 Belum Dibuka
                                            </option>
                                            <option value="pendaftaran" <?php echo ($pengaturan['status_ppdb'] ?? '') == 'pendaftaran' ? 'selected' : ''; ?>>
                                                📝 Pendaftaran Dibuka
                                            </option>
                                            <option value="seleksi" <?php echo ($pengaturan['status_ppdb'] ?? '') == 'seleksi' ? 'selected' : ''; ?>>
                                                ⚙️ Proses Seleksi
                                            </option>
                                            <option value="pengumuman" <?php echo ($pengaturan['status_ppdb'] ?? '') == 'pengumuman' ? 'selected' : ''; ?>>
                                                📢 Pengumuman
                                            </option>
                                            <option value="daftar_ulang" <?php echo ($pengaturan['status_ppdb'] ?? '') == 'daftar_ulang' ? 'selected' : ''; ?>>
                                                ✅ Daftar Ulang
                                            </option>
                                            <option value="selesai" <?php echo ($pengaturan['status_ppdb'] ?? '') == 'selesai' ? 'selected' : ''; ?>>
                                                🏁 Selesai
                                            </option>
                                        </select>
                                        <small class="text-muted">Status keseluruhan proses PPDB</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Info Sistem -->
                            <div class="setting-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="setting-icon me-3">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Informasi Sistem</h5>
                                        <small class="text-muted">Konfigurasi dari constants.php</small>
                                    </div>
                                </div>
                                
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td width="200">Tahun Ajaran</td>
                                        <td>: <strong><?php echo TAHUN_AJARAN; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Versi Aplikasi</td>
                                        <td>: <?php echo APP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Max Upload Size</td>
                                        <td>: <?php echo get_max_upload_size_readable(); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Server Time</td>
                                        <td>: <?php echo date('d F Y H:i:s'); ?></td>
                                    </tr>
                                </table>
                                
                                <div class="alert alert-secondary mt-3 mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <small>Untuk mengubah tahun ajaran dan konfigurasi lain, edit file <code>config/constants.php</code></small>
                                </div>
                            </div>
                            
                            <!-- Statistik Sistem -->
                            <div class="setting-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="setting-icon me-3">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Statistik Sistem</h5>
                                        <small class="text-muted">Ringkasan data saat ini</small>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="stat-mini">
                                            <h3><?php echo $stats['total_pendaftar']; ?></h3>
                                            <small class="text-muted">Total Pendaftar</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-mini">
                                            <h3><?php echo $stats['total_sekolah']; ?></h3>
                                            <small class="text-muted">Sekolah Aktif</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-mini">
                                            <h3><?php echo $stats['total_siswa']; ?></h3>
                                            <small class="text-muted">Akun Siswa</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-mini">
                                            <h3><?php echo $stats['total_admin']; ?></h3>
                                            <small class="text-muted">Akun Admin</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Simpan Pengaturan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/scripts_admin.php'; ?>
</body>
</html>

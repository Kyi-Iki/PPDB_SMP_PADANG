<?php
/**
 * File: siswa/index.php
 * Deskripsi: Dashboard untuk siswa setelah login
 */

session_start();
require_once '../config/database.php';

check_siswa();

$id_siswa = $_SESSION['id_siswa'];
$nama_siswa = $_SESSION['nama_lengkap'];
$tahun_ajaran = TAHUN_AJARAN;

// Ambil data siswa
$sql_siswa = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt = mysqli_prepare($conn, $sql_siswa);
mysqli_stmt_bind_param($stmt, "i", $id_siswa);
mysqli_stmt_execute($stmt);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Cek kelengkapan biodata
$biodata_lengkap = !empty($siswa['tempat_lahir']) && 
                   !empty($siswa['tanggal_lahir']) && 
                   !empty($siswa['alamat_lengkap']);

// Cek data orang tua
$sql_ortu = "SELECT * FROM orang_tua WHERE id_siswa = ?";
$stmt_ortu = mysqli_prepare($conn, $sql_ortu);
mysqli_stmt_bind_param($stmt_ortu, "i", $id_siswa);
mysqli_stmt_execute($stmt_ortu);
$ortu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_ortu));
$ortu_lengkap = $ortu && !empty($ortu['nama_ayah']) && !empty($ortu['nama_ibu']);

// Cek SEMUA pendaftaran siswa (multi-jalur)
$sql_pendaftaran = "SELECT p.*, s.nama_sekolah, s2.nama_sekolah as nama_sekolah_2, 
                    j.nama_jalur, hs.status_seleksi, hs.ranking, hs.dipublikasikan
                    FROM pendaftaran p
                    LEFT JOIN sekolah s ON p.id_sekolah = s.id_sekolah
                    LEFT JOIN sekolah s2 ON p.id_sekolah_pilihan_2 = s2.id_sekolah
                    LEFT JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
                    LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                    WHERE p.id_siswa = ? AND p.tahun_ajaran = ?
                    ORDER BY p.tanggal_daftar DESC";
$stmt_daftar = mysqli_prepare($conn, $sql_pendaftaran);
mysqli_stmt_bind_param($stmt_daftar, "is", $id_siswa, $tahun_ajaran);
mysqli_stmt_execute($stmt_daftar);
$result_daftar = mysqli_stmt_get_result($stmt_daftar);
$semua_pendaftaran = [];
$pendaftaran_aktif = null;
$sudah_lulus = false;

while ($p = mysqli_fetch_assoc($result_daftar)) {
    $semua_pendaftaran[] = $p;
    if ($p['status_seleksi'] == 'lulus') {
        $sudah_lulus = true;
        $pendaftaran_aktif = $p;
    }
}
// Jika belum lulus, ambil pendaftaran terakhir
if (!$pendaftaran_aktif && count($semua_pendaftaran) > 0) {
    $pendaftaran_aktif = $semua_pendaftaran[0];
}
$sudah_daftar = count($semua_pendaftaran) > 0;
$pendaftaran = $pendaftaran_aktif; // backward compatibility

// Cek dokumen umum dari tabel dokumen_siswa
$sql_dokumen = "SELECT COUNT(*) as total FROM dokumen_siswa WHERE id_siswa = ? AND kategori = 'umum'";
$stmt_dok = mysqli_prepare($conn, $sql_dokumen);
mysqli_stmt_bind_param($stmt_dok, "i", $id_siswa);
mysqli_stmt_execute($stmt_dok);
$dokumen_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_dok))['total'];

// Cek hasil seleksi (dari pendaftaran aktif)
$hasil_seleksi = null;
if ($sudah_daftar && $pendaftaran_aktif && $pendaftaran_aktif['status_seleksi']) {
    $hasil_seleksi = [
        'status_seleksi' => $pendaftaran_aktif['status_seleksi'],
        'ranking' => $pendaftaran_aktif['ranking'],
        'dipublikasikan' => $pendaftaran_aktif['dipublikasikan']
    ];
}

// Hitung progress
$progress = 0;
if ($biodata_lengkap) $progress += 25;
if ($ortu_lengkap) $progress += 25;
if ($dokumen_count >= 3) $progress += 25;
if ($sudah_daftar) $progress += 25;

// Pengaturan PPDB (global)
$sql_pengaturan = "SELECT * FROM pengaturan_ppdb WHERE tahun_ajaran = ?";
$stmt_pengaturan = mysqli_prepare($conn, $sql_pengaturan);
mysqli_stmt_bind_param($stmt_pengaturan, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt_pengaturan);
$pengaturan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pengaturan));

// Ambil jadwal per jalur
$jadwal_jalur_list = get_semua_jadwal_jalur();

// Cek peringatan dokumen jalur (untuk Afirmasi/Prestasi/Mutasi)
$peringatan_dokumen = [];
if ($sudah_daftar && $pendaftaran_aktif && !$sudah_lulus) {
    $nama_jalur = strtolower($pendaftaran_aktif['nama_jalur']);
    
    // Cek dokumen khusus jalur
    if (in_array($nama_jalur, ['afirmasi', 'prestasi', 'mutasi'])) {
        $sql_dok_jalur = "SELECT COUNT(*) as total FROM dokumen_siswa 
                          WHERE id_siswa = ? AND kategori = ? AND status_verifikasi = 'valid'";
        $stmt_jalur = mysqli_prepare($conn, $sql_dok_jalur);
        mysqli_stmt_bind_param($stmt_jalur, "is", $id_siswa, $nama_jalur);
        mysqli_stmt_execute($stmt_jalur);
        $dok_jalur_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jalur))['total'];
        
        if ($dok_jalur_count == 0) {
            // Cek apakah ada dokumen pending
            $sql_pending = "SELECT COUNT(*) as total FROM dokumen_siswa 
                            WHERE id_siswa = ? AND kategori = ? AND status_verifikasi = 'pending'";
            $stmt_pending = mysqli_prepare($conn, $sql_pending);
            mysqli_stmt_bind_param($stmt_pending, "is", $id_siswa, $nama_jalur);
            mysqli_stmt_execute($stmt_pending);
            $pending_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pending))['total'];
            
            if ($pending_count > 0) {
                $peringatan_dokumen[] = [
                    'type' => 'warning',
                    'jalur' => ucfirst($nama_jalur),
                    'message' => "Dokumen jalur {$pendaftaran_aktif['nama_jalur']} Anda sedang menunggu verifikasi. Pastikan dokumen valid sebelum seleksi dimulai."
                ];
            } else {
                $peringatan_dokumen[] = [
                    'type' => 'danger',
                    'jalur' => ucfirst($nama_jalur),
                    'message' => "⚠️ PENTING: Anda belum upload dokumen pendukung untuk jalur {$pendaftaran_aktif['nama_jalur']}. Tanpa dokumen valid, Anda TIDAK AKAN MASUK RANKING saat seleksi. <a href='dokumen.php' class='alert-link'>Upload sekarang →</a>"
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../includes/head_siswa.php'; ?>
    <title>Dashboard Siswa - PPDB SMP</title>
    
    <style>
        body {
            background: var(--bg-secondary);
        }
        
        .welcome-card {
            background: var(--gradient-hero);
            color: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-base);
            height: 100%;
            border: 1px solid var(--gray-100);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.blue {
            background: var(--gradient-primary);
            color: white;
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, var(--success-600) 0%, var(--success-500) 100%);
            color: white;
        }
        
        .stat-icon.orange {
            background: linear-gradient(135deg, var(--warning-600) 0%, var(--warning-500) 100%);
            color: white;
        }
        
        .stat-icon.red {
            background: linear-gradient(135deg, var(--error-600) 0%, var(--error-500) 100%);
            color: white;
        }
        
        .progress-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }
        
        .progress {
            height: 12px;
            border-radius: var(--radius-full);
            background: var(--gray-100);
        }
        
        .progress-bar {
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
        }
        
        .checklist-item {
            background: var(--gray-50);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            transition: all var(--transition-fast);
        }
        
        .checklist-item:hover {
            background: var(--gray-100);
        }
        
        .checklist-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1rem;
        }
        
        .checklist-icon.done {
            background: var(--success-100);
            color: var(--success-600);
        }
        
        .checklist-icon.pending {
            background: var(--warning-100);
            color: var(--warning-600);
        }
        
        .action-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-base);
            border: 1px solid var(--gray-100);
            height: 100%;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-500);
        }
        
        .action-icon {
            width: 64px;
            height: 64px;
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }
        
        .timeline-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }
        
        .timeline-item {
            display: flex;
            padding: 0.875rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .timeline-item:last-child {
            border-bottom: none;
        }
        
        .timeline-date {
            min-width: 80px;
            font-weight: 600;
            color: var(--primary-600);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4 mb-5">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="welcome-title">
                        Selamat Datang, <?php echo $nama_siswa; ?>! 👋
                    </div>
                    <p class="mb-0" style="color: rgba(255,255,255,0.95) !important;">
                        NISN: <?php echo $siswa['nisn']; ?> | Tahun Ajaran <?php echo $tahun_ajaran; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <?php if ($sudah_daftar): ?>
                    <div class="badge bg-light text-primary fs-6 px-3 py-2">
                        <i class="fas fa-check-circle me-2" style="color: #1e3a8a !important;"></i>
                        Sudah Terdaftar
                    </div>
                    <?php else: ?>
                    <div class="badge bg-warning text-dark fs-6 px-3 py-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Belum Daftar
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Peringatan Dokumen Jalur -->
        <?php if (!empty($peringatan_dokumen)): ?>
        <?php foreach ($peringatan_dokumen as $peringatan): ?>
        <div class="alert alert-<?php echo $peringatan['type']; ?> alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-<?php echo $peringatan['type'] == 'danger' ? 'exclamation-triangle' : 'clock'; ?> me-2"></i>
            <strong>Jalur <?php echo $peringatan['jalur']; ?>:</strong>
            <?php echo $peringatan['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $progress; ?>%</h3>
                            <p class="text-muted mb-0 small">Progress</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $dokumen_count; ?>/4</h3>
                            <p class="text-muted mb-0 small">Dokumen</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h3 class="fw-bold mb-1">
                                <?php 
                                if ($sudah_daftar) {
                                    echo ucfirst($pendaftaran['status_verifikasi']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </h3>
                            <p class="text-muted mb-0 small">Status</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h3 class="fw-bold mb-1">
                                <?php 
                                if ($hasil_seleksi) {
                                    echo '#' . $hasil_seleksi['ranking'];
                                } else {
                                    echo '-';
                                }
                                ?>
                            </h3>
                            <p class="text-muted mb-0 small">Ranking</p>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Card -->
                <div class="progress-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Progress Pendaftaran
                        </h5>
                        <span class="badge bg-primary"><?php echo $progress; ?>%</span>
                    </div>
                    
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?php echo $progress; ?>%" 
                             aria-valuenow="<?php echo $progress; ?>" 
                             aria-valuemin="0" aria-valuemax="100">
                            <?php echo $progress; ?>%
                        </div>
                    </div>
                    
                    <div class="checklist-item">
                        <div class="checklist-icon <?php echo $biodata_lengkap ? 'done' : 'pending'; ?>">
                            <i class="fas <?php echo $biodata_lengkap ? 'fa-check' : 'fa-clock'; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-600">Lengkapi Biodata</div>
                            <small class="text-muted">Data pribadi dan orang tua</small>
                        </div>
                        <?php if (!$biodata_lengkap): ?>
                        <a href="biodata.php" class="btn btn-sm btn-outline-primary">
                            Lengkapi
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="checklist-item">
                        <div class="checklist-icon <?php echo $dokumen_count >= 3 ? 'done' : 'pending'; ?>">
                            <i class="fas <?php echo $dokumen_count >= 3 ? 'fa-check' : 'fa-clock'; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-600">Upload Dokumen</div>
                            <small class="text-muted">KK, Ijazah, Rapor (<?php echo $dokumen_count; ?>/4)</small>
                        </div>
                        <?php if ($dokumen_count < 4): ?>
                        <a href="dokumen.php" class="btn btn-sm btn-outline-primary">
                            Upload
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="checklist-item">
                        <div class="checklist-icon <?php echo $sudah_daftar ? 'done' : 'pending'; ?>">
                            <i class="fas <?php echo $sudah_daftar ? 'fa-check' : 'fa-clock'; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-600">Pilih Sekolah</div>
                            <small class="text-muted">Pilih jalur dan sekolah tujuan</small>
                        </div>
                        <?php if (!$sudah_daftar): ?>
                        <a href="pilih_sekolah.php" class="btn btn-sm btn-outline-primary">
                            Pilih
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="checklist-item">
                        <div class="checklist-icon <?php echo $hasil_seleksi && $hasil_seleksi['dipublikasikan'] ? 'done' : 'pending'; ?>">
                            <i class="fas <?php echo $hasil_seleksi && $hasil_seleksi['dipublikasikan'] ? 'fa-check' : 'fa-clock'; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-600">Lihat Hasil</div>
                            <small class="text-muted">Pengumuman hasil seleksi</small>
                        </div>
                        <?php if ($hasil_seleksi && $hasil_seleksi['dipublikasikan']): ?>
                        <a href="hasil_seleksi.php" class="btn btn-sm btn-outline-primary">
                            Lihat
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <h6 class="fw-bold mb-2">Edit Biodata</h6>
                            <p class="text-muted small mb-3">Perbarui data pribadi Anda</p>
                            <a href="biodata.php" class="btn btn-primary btn-sm">
                                Buka
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-upload"></i>
                            </div>
                            <h6 class="fw-bold mb-2">Upload Dokumen</h6>
                            <p class="text-muted small mb-3">Upload berkas pendaftaran</p>
                            <a href="dokumen.php" class="btn btn-primary btn-sm">
                                Upload
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-print"></i>
                            </div>
                            <h6 class="fw-bold mb-2">Cetak Kartu</h6>
                            <p class="text-muted small mb-3">Cetak kartu pendaftaran</p>
                            <a href="cetak_kartu.php" class="btn btn-primary btn-sm <?php echo !$sudah_daftar ? 'disabled' : ''; ?>">
                                Cetak
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mt-4 mt-lg-0">
                <!-- Info Pendaftaran -->
                <?php if ($sudah_daftar): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-info-circle me-2"></i>
                            Info Pendaftaran
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted">No. Pendaftaran</td>
                                <td class="fw-bold"><?php echo $pendaftaran['nomor_pendaftaran']; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Sekolah Pilihan 1</td>
                                <td class="fw-bold"><?php echo $pendaftaran['nama_sekolah']; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Sekolah Pilihan 2</td>
                                <td><?php echo $pendaftaran['nama_sekolah_2'] ?? '-'; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jalur</td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $pendaftaran['nama_jalur']; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nilai Rata-rata</td>
                                <td class="fw-bold"><?php echo number_format($pendaftaran['nilai_rapor_rata'], 2); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jarak</td>
                                <td class="fw-bold"><?php echo $pendaftaran['jarak_rumah_sekolah']; ?> km</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $pendaftaran['status_verifikasi'] == 'terverifikasi' ? 'success' : 
                                            ($pendaftaran['status_verifikasi'] == 'ditolak' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($pendaftaran['status_verifikasi']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Jadwal PPDB Per Jalur -->
                <div class="timeline-card">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Jadwal PPDB Per Jalur
                    </h6>
                    
                    <?php if (!empty($jadwal_jalur_list)): ?>
                    <?php while ($jadwal = mysqli_fetch_assoc($jadwal_jalur_list)): 
                        $status_class = match($jadwal['status']) {
                            'dibuka' => 'success',
                            'ditutup' => 'secondary',
                            'seleksi' => 'warning',
                            'pengumuman' => 'info',
                            'selesai' => 'dark',
                            default => 'secondary'
                        };
                        $status_icon = match($jadwal['status']) {
                            'dibuka' => '✅',
                            'ditutup' => '🚫',
                            'seleksi' => '⚙️',
                            'pengumuman' => '📢',
                            'selesai' => '🏁',
                            default => '🔒'
                        };
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-date">
                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_icon; ?></span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-600"><?php echo $jadwal['nama_jalur']; ?></div>
                            <small class="text-muted">
                                <?php echo date('d M', strtotime($jadwal['tanggal_buka'])); ?> - 
                                <?php echo date('d M Y', strtotime($jadwal['tanggal_tutup'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <p class="text-muted text-center mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Jadwal belum tersedia
                    </p>
                    <?php endif; ?>
                </div>
                
                <!-- Bantuan -->
                <div class="card border-0 shadow-sm mt-4" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                    <div class="card-body text-center">
                        <i class="fas fa-headset mb-3" style="font-size: 3rem; color: var(--primary-700);"></i>
                        <h6 class="fw-bold mb-2">Butuh Bantuan?</h6>
                        <p class="text-muted small mb-3">
                            Tim kami siap membantu Anda
                        </p>
                        <div class="d-grid gap-2">
                            <a href="tel:0751123456" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-phone me-2"></i>Telepon
                            </a>
                            <a href="mailto:info@ppdb-smp.sch.id" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-envelope me-2"></i>Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

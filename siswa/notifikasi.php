<?php
/**
 * File: siswa/notifikasi.php
 * Deskripsi: Halaman notifikasi dan update status pendaftaran
 */

session_start();
require_once '../config/database.php';

check_siswa();

$id_siswa = $_SESSION['id_siswa'];
$tahun_ajaran = TAHUN_AJARAN;

// Ambil data siswa untuk sidebar
$sql_siswa = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt_siswa = mysqli_prepare($conn, $sql_siswa);
mysqli_stmt_bind_param($stmt_siswa, "i", $id_siswa);
mysqli_stmt_execute($stmt_siswa);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_siswa));

// Ambil semua pendaftaran dengan status terbaru
$sql_pendaftaran = "SELECT p.*, s.nama_sekolah, s2.nama_sekolah as nama_sekolah_2, j.nama_jalur,
                    hs.status_seleksi, hs.ranking, hs.dipublikasikan, hs.tanggal_publikasi,
                    d.total_dokumen, d.dokumen_valid, d.dokumen_invalid
                    FROM pendaftaran p
                    JOIN sekolah s ON p.id_sekolah = s.id_sekolah
                    LEFT JOIN sekolah s2 ON p.id_sekolah_pilihan_2 = s2.id_sekolah
                    JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
                    LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                    LEFT JOIN (
                        SELECT id_siswa, 
                               COUNT(*) as total_dokumen,
                               SUM(CASE WHEN status_verifikasi = 'valid' THEN 1 ELSE 0 END) as dokumen_valid,
                               SUM(CASE WHEN status_verifikasi = 'tidak_valid' THEN 1 ELSE 0 END) as dokumen_invalid
                        FROM dokumen_siswa GROUP BY id_siswa
                    ) d ON p.id_siswa = d.id_siswa
                    WHERE p.id_siswa = ? AND p.tahun_ajaran = ?
                    ORDER BY p.tanggal_daftar DESC";
$stmt = mysqli_prepare($conn, $sql_pendaftaran);
mysqli_stmt_bind_param($stmt, "is", $id_siswa, $tahun_ajaran);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$pendaftaran_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pendaftaran_list[] = $row;
}

// Ambil jadwal jalur untuk info timeline
$jadwal_jalur = get_semua_jadwal_jalur();

// Buat notifikasi dari data
$notifikasi = [];

foreach ($pendaftaran_list as $p) {
    // Format sekolah pilihan
    $sekolah_info = $p['nama_sekolah'];
    if (!empty($p['nama_sekolah_2'])) {
        $sekolah_info .= ' (Pilihan 2: ' . $p['nama_sekolah_2'] . ')';
    }
    
    // Notifikasi pendaftaran
    $notifikasi[] = [
        'tanggal' => $p['tanggal_daftar'],
        'icon' => 'fa-school',
        'warna' => 'primary',
        'judul' => 'Pendaftaran ' . $p['nama_jalur'],
        'pesan' => 'Anda berhasil mendaftar di ' . $sekolah_info . ' melalui jalur ' . $p['nama_jalur'],
        'status' => 'success'
    ];
    
    // Notifikasi verifikasi
    if ($p['status_verifikasi'] == 'terverifikasi') {
        $notifikasi[] = [
            'tanggal' => $p['updated_at'] ?? $p['tanggal_daftar'],
            'icon' => 'fa-check-circle',
            'warna' => 'success',
            'judul' => 'Verifikasi Berkas Selesai',
            'pesan' => 'Berkas pendaftaran jalur ' . $p['nama_jalur'] . ' telah diverifikasi',
            'status' => 'success'
        ];
    } elseif ($p['status_verifikasi'] == 'ditolak') {
        $notifikasi[] = [
            'tanggal' => $p['updated_at'] ?? $p['tanggal_daftar'],
            'icon' => 'fa-times-circle',
            'warna' => 'danger',
            'judul' => 'Verifikasi Ditolak',
            'pesan' => 'Berkas pendaftaran jalur ' . $p['nama_jalur'] . ' ditolak. Periksa catatan verifikasi.',
            'status' => 'danger'
        ];
    }
    
    // Notifikasi dokumen tidak valid
    if ($p['dokumen_invalid'] > 0) {
        $notifikasi[] = [
            'tanggal' => date('Y-m-d'),
            'icon' => 'fa-file-alt',
            'warna' => 'warning',
            'judul' => 'Dokumen Perlu Diperbaiki',
            'pesan' => $p['dokumen_invalid'] . ' dokumen dinyatakan tidak valid. Silakan upload ulang.',
            'status' => 'warning'
        ];
    }
    
    // Notifikasi hasil seleksi
    if ($p['dipublikasikan']) {
        if ($p['status_seleksi'] == 'lulus') {
            $notifikasi[] = [
                'tanggal' => $p['tanggal_publikasi'],
                'icon' => 'fa-trophy',
                'warna' => 'success',
                'judul' => '🎉 SELAMAT! Anda LULUS',
                'pesan' => 'Anda dinyatakan LULUS seleksi jalur ' . $p['nama_jalur'] . ' di ' . $p['nama_sekolah'] . '. Ranking: #' . $p['ranking'],
                'status' => 'success'
            ];
        } else {
            $notifikasi[] = [
                'tanggal' => $p['tanggal_publikasi'],
                'icon' => 'fa-info-circle',
                'warna' => 'secondary',
                'judul' => 'Hasil Seleksi ' . $p['nama_jalur'],
                'pesan' => 'Anda tidak lulus seleksi jalur ' . $p['nama_jalur'] . '. Anda masih dapat mendaftar di jalur lain.',
                'status' => 'info'
            ];
        }
    }
    
    // Notifikasi koreksi nilai rapor oleh admin
    if (!empty($p['catatan_koreksi_nilai'])) {
        $notifikasi[] = [
            'tanggal' => $p['tanggal_koreksi_nilai'] ?? date('Y-m-d'),
            'icon' => 'fa-edit',
            'warna' => 'warning',
            'judul' => '⚠️ Nilai Rapor Dikoreksi',
            'pesan' => 'Nilai rapor Anda pada jalur ' . $p['nama_jalur'] . ' telah dikoreksi oleh admin. Alasan: ' . $p['catatan_koreksi_nilai'],
            'status' => 'warning'
        ];
    }
}

// Sort notifikasi by tanggal DESC
usort($notifikasi, function($a, $b) {
    return strtotime($b['tanggal']) - strtotime($a['tanggal']);
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - PPDB SMP</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .notif-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }
        .notif-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .notif-card.success { border-left-color: #28a745; }
        .notif-card.warning { border-left-color: #ffc107; }
        .notif-card.danger { border-left-color: #dc3545; }
        .notif-card.info { border-left-color: #17a2b8; }
        
        .notif-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 1rem;
        }
        
        .timeline-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
            border-left: 2px solid #e9ecef;
        }
        .timeline-item:last-child { border-left: 2px solid transparent; padding-bottom: 0; }
        
        .timeline-dot {
            position: absolute;
            left: -8px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--primary-700);
        }
        .timeline-dot.active { background: #28a745; }
        .timeline-dot.pending { background: #6c757d; }
        
        .status-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-bell text-primary me-2"></i>Notifikasi & Status
                    </h4>
                    <span class="badge bg-primary"><?php echo count($notifikasi); ?> notifikasi</span>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Notifikasi List -->
                        <?php if (empty($notifikasi)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada notifikasi</h5>
                            <p class="text-muted">Mulai pendaftaran untuk melihat notifikasi</p>
                            <a href="pilih_sekolah.php" class="btn btn-primary">
                                <i class="fas fa-school me-2"></i>Daftar Sekarang
                            </a>
                        </div>
                        <?php else: ?>
                        <?php foreach ($notifikasi as $n): ?>
                        <div class="notif-card <?php echo $n['status']; ?>">
                            <div class="d-flex">
                                <div class="notif-icon bg-<?php echo $n['warna']; ?> bg-opacity-10 text-<?php echo $n['warna']; ?>">
                                    <i class="fas <?php echo $n['icon']; ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="fw-bold mb-1"><?php echo $n['judul']; ?></h6>
                                        <small class="text-muted">
                                            <?php echo tanggal_indonesia($n['tanggal']); ?>
                                        </small>
                                    </div>
                                    <p class="text-muted mb-0"><?php echo $n['pesan']; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Status Pendaftaran -->
                        <div class="status-card">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-clipboard-list text-primary me-2"></i>Status Pendaftaran
                            </h6>
                            
                            <?php if (empty($pendaftaran_list)): ?>
                            <p class="text-muted mb-0">Belum ada pendaftaran</p>
                            <?php else: ?>
                            <?php foreach ($pendaftaran_list as $p): ?>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-<?php 
                                    echo $p['nama_jalur'] == 'Zonasi' ? 'primary' : 
                                        ($p['nama_jalur'] == 'Prestasi' ? 'success' : 
                                        ($p['nama_jalur'] == 'Afirmasi' ? 'warning' : 'info')); 
                                ?> me-2"><?php echo $p['nama_jalur']; ?></span>
                                <div class="flex-grow-1">
                                    <small class="d-block"><strong>1:</strong> <?php echo $p['nama_sekolah']; ?></small>
                                    <?php if (!empty($p['nama_sekolah_2'])): ?>
                                    <small class="d-block text-muted"><strong>2:</strong> <?php echo $p['nama_sekolah_2']; ?></small>
                                    <?php endif; ?>
                                    <?php if ($p['dipublikasikan']): ?>
                                    <span class="badge bg-<?php echo $p['status_seleksi'] == 'lulus' ? 'success' : 'secondary'; ?>">
                                        <?php echo strtoupper($p['status_seleksi']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Menunggu</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Timeline Jalur -->
                        <div class="timeline-card">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>Jadwal Jalur
                            </h6>
                            
                            <?php foreach ($jadwal_jalur as $jj): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot <?php 
                                    echo $jj['status'] == 'dibuka' ? 'active' : 
                                        ($jj['status'] == 'ditutup' || $jj['status'] == 'selesai' ? 'pending' : ''); 
                                ?>"></div>
                                <div>
                                    <strong><?php echo $jj['nama_jalur']; ?></strong>
                                    <span class="badge bg-<?php 
                                        echo $jj['status'] == 'dibuka' ? 'success' : 
                                            ($jj['status'] == 'ditutup' ? 'danger' : 
                                            ($jj['status'] == 'seleksi' ? 'warning' : 
                                            ($jj['status'] == 'pengumuman' ? 'info' : 'secondary'))); 
                                    ?> ms-2"><?php echo ucfirst(str_replace('_', ' ', $jj['status'])); ?></span>
                                    <div class="small text-muted">
                                        <?php echo date('d/m', strtotime($jj['tanggal_buka'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($jj['tanggal_tutup'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Quick Links -->
                        <div class="status-card">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-link text-primary me-2"></i>Aksi Cepat
                            </h6>
                            <div class="d-grid gap-2">
                                <a href="dokumen.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-upload me-2"></i>Upload Dokumen
                                </a>
                                <a href="cetak_kartu.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-print me-2"></i>Cetak Kartu
                                </a>
                                <a href="hasil_seleksi.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-trophy me-2"></i>Lihat Hasil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>


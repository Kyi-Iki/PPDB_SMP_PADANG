<?php
/**
 * File: sekolah_detail.php
 * Deskripsi: Halaman detail sekolah untuk publik/siswa
 * Menampilkan informasi lengkap tentang sekolah
 */

require_once 'config/database.php';

$tahun_ajaran = TAHUN_AJARAN;

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id_sekolah = (int)$_GET['id'];

// Ambil data sekolah
$sql = "SELECT s.*, 
        (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = ?) as total_kuota,
        (SELECT COUNT(*) FROM pendaftaran WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = ?) as total_pendaftar,
        (SELECT COUNT(*) FROM hasil_seleksi hs 
         JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran 
         WHERE p.id_sekolah = s.id_sekolah AND hs.status_seleksi = 'lulus' AND p.tahun_ajaran = ?) as total_diterima
        FROM sekolah s
        WHERE s.id_sekolah = ? AND s.status = 'aktif'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssi", $tahun_ajaran, $tahun_ajaran, $tahun_ajaran, $id_sekolah);
mysqli_stmt_execute($stmt);
$sekolah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$sekolah) {
    header('Location: index.php');
    exit();
}

// Ambil kuota per jalur
$sql_kuota = "SELECT kj.*, jp.nama_jalur 
              FROM kuota_jalur kj 
              JOIN jalur_ppdb jp ON kj.id_jalur = jp.id_jalur
              WHERE kj.id_sekolah = ? AND kj.tahun_ajaran = ?
              ORDER BY kj.id_jalur";
$stmt_kuota = mysqli_prepare($conn, $sql_kuota);
mysqli_stmt_bind_param($stmt_kuota, "is", $id_sekolah, $tahun_ajaran);
mysqli_stmt_execute($stmt_kuota);
$kuota_list = mysqli_stmt_get_result($stmt_kuota);

// Ambil statistik pendaftar per jalur
$sql_stat = "SELECT jp.nama_jalur, jp.id_jalur,
             COUNT(p.id_pendaftaran) as jumlah_pendaftar,
             (SELECT jumlah_kuota FROM kuota_jalur WHERE id_sekolah = ? AND id_jalur = jp.id_jalur AND tahun_ajaran = ?) as kuota
             FROM jalur_ppdb jp
             LEFT JOIN pendaftaran p ON p.id_jalur = jp.id_jalur AND p.id_sekolah = ? AND p.tahun_ajaran = ?
             GROUP BY jp.id_jalur
             ORDER BY jp.id_jalur";
$stmt_stat = mysqli_prepare($conn, $sql_stat);
mysqli_stmt_bind_param($stmt_stat, "isis", $id_sekolah, $tahun_ajaran, $id_sekolah, $tahun_ajaran);
mysqli_stmt_execute($stmt_stat);
$stat_jalur = mysqli_stmt_get_result($stmt_stat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sekolah['nama_sekolah']); ?> - Detail Sekolah PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0 80px;
            position: relative;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: #f5f7fa;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .info-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-box:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-box:hover .stat-value { color: white !important; }
        .stat-box:hover .stat-label { color: rgba(255,255,255,0.8) !important; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .jalur-progress {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background: #e9ecef;
        }
        .jalur-progress .progress-bar {
            border-radius: 4px;
        }
        
        #schoolLocationMap {
            height: 300px;
            width: 100%;
            border-radius: 12px;
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(255,255,255,0.9);
            border-radius: 50px;
            padding: 10px 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
        }
        .back-btn:hover {
            background: white;
            color: #764ba2;
        }
        
        .school-badge {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .detail-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .detail-value {
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i>Kembali
    </a>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="school-badge mb-3">
                        <i class="fas fa-school me-1"></i>NPSN: <?php echo $sekolah['npsn']; ?>
                    </div>
                    <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($sekolah['nama_sekolah']); ?></h1>
                    <p class="lead mb-0 opacity-75">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo htmlspecialchars($sekolah['alamat_sekolah']); ?>
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <span class="badge bg-<?php echo $sekolah['status'] == 'aktif' ? 'success' : 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst($sekolah['status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>
    
    <div class="container" style="margin-top: -30px; position: relative; z-index: 10;">
        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="stat-value"><?php echo number_format($sekolah['total_kuota'] ?? 0); ?></div>
                    <div class="stat-label">Total Kuota</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="stat-value"><?php echo number_format($sekolah['total_pendaftar'] ?? 0); ?></div>
                    <div class="stat-label">Pendaftar</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="stat-value"><?php echo number_format($sekolah['total_diterima'] ?? 0); ?></div>
                    <div class="stat-label">Diterima</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="stat-value">
                        <?php 
                        $sisa = ($sekolah['total_kuota'] ?? 0) - ($sekolah['total_diterima'] ?? 0);
                        echo $sisa > 0 ? number_format($sisa) : '0'; 
                        ?>
                    </div>
                    <div class="stat-label">Sisa Kuota</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Informasi Sekolah -->
                <div class="info-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-info-circle text-primary me-2"></i>Informasi Sekolah
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Nama Sekolah</div>
                                <div class="detail-value"><?php echo htmlspecialchars($sekolah['nama_sekolah']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">NPSN</div>
                                <div class="detail-value"><?php echo $sekolah['npsn']; ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-item">
                                <div class="detail-label">Alamat Lengkap</div>
                                <div class="detail-value"><?php echo htmlspecialchars($sekolah['alamat_sekolah']); ?></div>
                            </div>
                        </div>
                        <?php if ($sekolah['latitude'] && $sekolah['latitude'] != 0): ?>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Koordinat GPS</div>
                                <div class="detail-value">
                                    <a href="https://www.google.com/maps?q=<?php echo $sekolah['latitude']; ?>,<?php echo $sekolah['longitude']; ?>" 
                                       target="_blank" class="text-decoration-none">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                        <?php echo number_format($sekolah['latitude'], 6); ?>, <?php echo number_format($sekolah['longitude'], 6); ?>
                                        <i class="fas fa-external-link-alt ms-1 small"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Tahun Ajaran</div>
                                <div class="detail-value"><?php echo $sekolah['tahun_ajaran']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Kuota Per Jalur -->
                <div class="info-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-chart-pie text-success me-2"></i>Kuota Per Jalur
                    </h5>
                    
                    <?php 
                    mysqli_data_seek($stat_jalur, 0);
                    while ($stat = mysqli_fetch_assoc($stat_jalur)): 
                        $kuota = $stat['kuota'] ?? 0;
                        $pendaftar = $stat['jumlah_pendaftar'] ?? 0;
                        $persen = $kuota > 0 ? min(100, ($pendaftar / $kuota) * 100) : 0;
                        
                        $jalur_color = match($stat['nama_jalur']) {
                            'Zonasi' => 'primary',
                            'Prestasi' => 'success',
                            'Afirmasi' => 'warning',
                            'Mutasi' => 'info',
                            default => 'secondary'
                        };
                    ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="badge bg-<?php echo $jalur_color; ?> me-2"><?php echo $stat['nama_jalur']; ?></span>
                            </div>
                            <div class="text-end">
                                <strong><?php echo $pendaftar; ?></strong> / <?php echo $kuota; ?> kursi
                                <small class="text-muted ms-2">(<?php echo number_format($persen, 0); ?>%)</small>
                            </div>
                        </div>
                        <div class="jalur-progress">
                            <div class="progress-bar bg-<?php echo $jalur_color; ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo $persen; ?>%"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Lokasi Peta -->
                <?php if ($sekolah['latitude'] && $sekolah['latitude'] != 0): ?>
                <div class="info-card">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-map-marked-alt text-danger me-2"></i>Lokasi Sekolah
                    </h5>
                    <div id="schoolLocationMap"></div>
                    <div class="text-center mt-3">
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $sekolah['latitude']; ?>,<?php echo $sekolah['longitude']; ?>" 
                           target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-directions me-1"></i>Petunjuk Arah
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Aksi -->
                <div class="info-card text-center">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-rocket text-warning me-2"></i>Tertarik Mendaftar?
                    </h5>
                    <p class="text-muted mb-4">Daftar sekarang dan jadilah bagian dari <?php echo $sekolah['nama_sekolah']; ?> tahun ajaran <?php echo TAHUN_AJARAN; ?>!</p>
                    <a href="siswa/register.php" class="btn btn-primary btn-lg w-100 mb-2">
                        <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                    </a>
                    <a href="siswa/login.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Sudah Punya Akun? Login
                    </a>
                </div>
                
                <!-- Info Kontak (jika ada) -->
                <div class="info-card">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-phone-alt text-info me-2"></i>Butuh Bantuan?
                    </h5>
                    <p class="text-muted small">
                        Hubungi panitia PPDB untuk informasi lebih lanjut tentang pendaftaran di sekolah ini.
                    </p>
                    <a href="index.php#contact" class="btn btn-outline-info btn-sm w-100">
                        <i class="fas fa-headset me-1"></i>Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <?php if ($sekolah['latitude'] && $sekolah['latitude'] != 0): ?>
    <script>
        // Initialize Map
        const lat = <?php echo $sekolah['latitude']; ?>;
        const lng = <?php echo $sekolah['longitude']; ?>;
        
        const map = L.map('schoolLocationMap').setView([lat, lng], 16);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);
        
        // Add marker
        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup('<b><?php echo addslashes($sekolah['nama_sekolah']); ?></b><br><?php echo addslashes($sekolah['alamat_sekolah']); ?>').openPopup();
    </script>
    <?php endif; ?>
</body>
</html>

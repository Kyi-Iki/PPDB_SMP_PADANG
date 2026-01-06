<?php
/**
 * File: siswa/cetak_kartu.php
 * Deskripsi: Cetak kartu pendaftaran PPDB (Multi-Jalur)
 */

session_start();
require_once '../config/database.php';

check_siswa();

$id_siswa = $_SESSION['id_siswa'];
$tahun_ajaran = TAHUN_AJARAN;

// Ambil data siswa
$sql_siswa = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt = mysqli_prepare($conn, $sql_siswa);
mysqli_stmt_bind_param($stmt, "i", $id_siswa);
mysqli_stmt_execute($stmt);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Ambil SEMUA pendaftaran siswa (multi-jalur)
$sql_pendaftaran = "SELECT p.*, s.nama_sekolah, s.alamat_sekolah, s.npsn, 
                    s2.nama_sekolah as nama_sekolah_2, s2.npsn as npsn_2,
                    j.nama_jalur
                    FROM pendaftaran p
                    JOIN sekolah s ON p.id_sekolah = s.id_sekolah
                    LEFT JOIN sekolah s2 ON p.id_sekolah_pilihan_2 = s2.id_sekolah
                    JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
                    WHERE p.id_siswa = ? AND p.tahun_ajaran = ?
                    ORDER BY p.tanggal_daftar DESC";
$stmt = mysqli_prepare($conn, $sql_pendaftaran);
mysqli_stmt_bind_param($stmt, "is", $id_siswa, $tahun_ajaran);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$semua_pendaftaran = [];
while ($row = mysqli_fetch_assoc($result)) {
    $semua_pendaftaran[] = $row;
}

$belum_daftar = count($semua_pendaftaran) == 0;

// Cek pendaftaran mana yang dipilih untuk dicetak
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pendaftaran = null;

if ($selected_id > 0) {
    foreach ($semua_pendaftaran as $p) {
        if ($p['id_pendaftaran'] == $selected_id) {
            $pendaftaran = $p;
            break;
        }
    }
}

// Jika tidak ada yang dipilih, ambil yang pertama
if (!$pendaftaran && count($semua_pendaftaran) > 0) {
    $pendaftaran = $semua_pendaftaran[0];
}

// Ambil data orang tua
$sql_ortu = "SELECT * FROM orang_tua WHERE id_siswa = ?";
$stmt_ortu = mysqli_prepare($conn, $sql_ortu);
mysqli_stmt_bind_param($stmt_ortu, "i", $id_siswa);
mysqli_stmt_execute($stmt_ortu);
$ortu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_ortu));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Pendaftaran PPDB - <?php echo $siswa['nama_lengkap']; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .kartu-container { max-width: 800px; margin: 2rem auto; }
        
        .kartu-pendaftaran {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .kartu-header {
            text-align: center;
            border-bottom: 3px solid var(--primary-700);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .kartu-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-700) 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: white;
        }
        
        .kartu-title { font-size: 1.75rem; font-weight: 700; color: #2d3748; margin-bottom: 0.5rem; }
        .kartu-subtitle { color: #6c757d; margin-bottom: 0; }
        
        .nomor-pendaftaran {
            background: linear-gradient(135deg, var(--primary-700) 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .nomor-text { font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.25rem; }
        .nomor-value { font-size: 1.75rem; font-weight: 700; letter-spacing: 2px; }
        
        .info-section { margin-bottom: 2rem; }
        .section-title {
            font-weight: 700;
            color: var(--primary-700);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .info-table { width: 100%; }
        .info-table tr { line-height: 2; }
        .info-table td:first-child { width: 35%; color: #6c757d; font-weight: 500; }
        .info-table td:nth-child(2) { width: 5%; color: #6c757d; }
        .info-table td:last-child { font-weight: 600; color: #2d3748; }
        
        .foto-box {
            width: 120px;
            height: 160px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            overflow: hidden;
        }
        .foto-box img { width: 100%; height: 100%; object-fit: cover; }
        
        .kartu-footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px dashed #dee2e6;
            text-align: right;
        }
        
        .ttd-box { margin-top: 4rem; }
        
        .barcode {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        /* Selector pendaftaran */
        .pendaftaran-selector {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .pendaftaran-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }
        .pendaftaran-item:hover {
            border-color: var(--primary-700);
            background: #f8f9ff;
        }
        .pendaftaran-item.active {
            border-color: var(--primary-700);
            background: linear-gradient(135deg, var(--primary-700)10 0%, #764ba210 100%);
        }
        
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .kartu-container { margin: 0; max-width: 100%; }
            .kartu-pendaftaran { border: 2px solid #000; box-shadow: none; }
        }
    </style>
</head>
<body>
    <!-- Navbar - No Print -->
    <div class="no-print">
        <?php include '../includes/navbar.php'; ?>
    </div>
    
    <div class="container kartu-container">
        <?php if ($belum_daftar): ?>
        <!-- Belum Daftar Warning -->
        <div class="card shadow-lg">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Belum Mendaftar</h4>
            </div>
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-id-card fa-4x text-warning"></i>
                </div>
                <h5 class="fw-bold">Kartu Pendaftaran Belum Tersedia</h5>
                <p class="text-muted mb-4">
                    Silakan pilih sekolah dan jalur PPDB terlebih dahulu untuk mendapatkan kartu pendaftaran.
                </p>
                <a href="pilih_sekolah.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-school me-2"></i>
                    Pilih Sekolah Sekarang
                </a>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Selector Pendaftaran (jika lebih dari 1) -->
        <?php if (count($semua_pendaftaran) > 1): ?>
        <div class="no-print pendaftaran-selector">
            <h6 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Pilih Kartu yang Akan Dicetak:</h6>
            <div class="row">
                <?php foreach ($semua_pendaftaran as $p): ?>
                <div class="col-md-6 mb-2">
                    <a href="?id=<?php echo $p['id_pendaftaran']; ?>" 
                       class="pendaftaran-item <?php echo ($pendaftaran['id_pendaftaran'] == $p['id_pendaftaran']) ? 'active' : ''; ?>">
                        <div class="me-3">
                            <span class="badge bg-<?php 
                                echo $p['nama_jalur'] == 'Zonasi' ? 'primary' : 
                                    ($p['nama_jalur'] == 'Prestasi' ? 'success' : 
                                    ($p['nama_jalur'] == 'Afirmasi' ? 'warning' : 'info')); 
                            ?>"><?php echo $p['nama_jalur']; ?></span>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo $p['nama_sekolah']; ?></div>
                            <small class="text-muted"><?php echo $p['nomor_pendaftaran']; ?></small>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Button Actions - No Print -->
        <div class="no-print mb-3 text-end">
            <button type="button" onclick="history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </button>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Cetak Kartu
            </button>
        </div>
        
        <!-- Kartu Pendaftaran -->
        <div class="kartu-pendaftaran">
            <!-- Header -->
            <div class="kartu-header">
                <div class="kartu-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="kartu-title">KARTU PENDAFTARAN</div>
                <div class="kartu-subtitle">
                    Penerimaan Peserta Didik Baru (PPDB) SMP<br>
                    Tahun Ajaran <?php echo $tahun_ajaran; ?>
                </div>
            </div>
            
            <!-- Nomor Pendaftaran -->
            <div class="nomor-pendaftaran">
                <div class="nomor-text">NOMOR PENDAFTARAN</div>
                <div class="nomor-value"><?php echo $pendaftaran['nomor_pendaftaran']; ?></div>
            </div>
            
            <div class="row">
                <div class="col-md-9">
                    <!-- Data Peserta Didik -->
                    <div class="info-section">
                        <div class="section-title">
                            <i class="fas fa-user me-2"></i>DATA PESERTA DIDIK
                        </div>
                        <table class="info-table">
                            <tr>
                                <td>Nama Lengkap</td>
                                <td>:</td>
                                <td><?php echo strtoupper($siswa['nama_lengkap']); ?></td>
                            </tr>
                            <tr>
                                <td>NISN</td>
                                <td>:</td>
                                <td><?php echo $siswa['nisn']; ?></td>
                            </tr>
                            <tr>
                                <td>NIK</td>
                                <td>:</td>
                                <td><?php echo $siswa['nik'] ?? '-'; ?></td>
                            </tr>
                            <tr>
                                <td>Tempat, Tanggal Lahir</td>
                                <td>:</td>
                                <td><?php echo ($siswa['tempat_lahir'] ?? '-') . ', ' . (isset($siswa['tanggal_lahir']) ? tanggal_indonesia($siswa['tanggal_lahir']) : '-'); ?></td>
                            </tr>
                            <tr>
                                <td>Jenis Kelamin</td>
                                <td>:</td>
                                <td><?php echo $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                            </tr>
                            <tr>
                                <td>Asal Sekolah</td>
                                <td>:</td>
                                <td><?php echo $siswa['asal_sekolah'] ?? '-'; ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Data Orang Tua -->
                    <div class="info-section">
                        <div class="section-title">
                            <i class="fas fa-users me-2"></i>DATA ORANG TUA/WALI
                        </div>
                        <table class="info-table">
                            <tr>
                                <td>Nama Ayah</td>
                                <td>:</td>
                                <td><?php echo $ortu['nama_ayah'] ?? '-'; ?></td>
                            </tr>
                            <tr>
                                <td>Nama Ibu</td>
                                <td>:</td>
                                <td><?php echo $ortu['nama_ibu'] ?? '-'; ?></td>
                            </tr>
                            <tr>
                                <td>No. Telepon</td>
                                <td>:</td>
                                <td><?php echo $ortu['no_telepon_ortu'] ?? $siswa['no_telepon'] ?? '-'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-3 text-center">
                    <div class="foto-box mx-auto">
                        <?php if (!empty($siswa['foto'])): ?>
                        <img src="../uploads/foto/<?php echo $siswa['foto']; ?>" alt="Foto">
                        <?php else: ?>
                        <i class="fas fa-user fa-3x text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 small text-muted">3 x 4 cm</div>
                </div>
            </div>
            
            <!-- Data Pendaftaran -->
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-school me-2"></i>DATA PENDAFTARAN
                </div>
                <table class="info-table">
                    <tr>
                        <td>Jalur PPDB</td>
                        <td>:</td>
                        <td>
                            <span class="badge bg-primary">
                                <?php echo $pendaftaran['nama_jalur']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Sekolah Pilihan 1</td>
                        <td>:</td>
                        <td><?php echo $pendaftaran['nama_sekolah']; ?> (NPSN: <?php echo $pendaftaran['npsn']; ?>)</td>
                    </tr>
                    <?php if (!empty($pendaftaran['nama_sekolah_2'])): ?>
                    <tr>
                        <td>Sekolah Pilihan 2</td>
                        <td>:</td>
                        <td><?php echo $pendaftaran['nama_sekolah_2']; ?> (NPSN: <?php echo $pendaftaran['npsn_2']; ?>)</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>Nilai Rata-rata Rapor</td>
                        <td>:</td>
                        <td><strong><?php echo number_format($pendaftaran['nilai_rapor_rata'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Jarak Rumah-Sekolah</td>
                        <td>:</td>
                        <td><?php echo $pendaftaran['jarak_rumah_sekolah']; ?> km</td>
                    </tr>
                    <tr>
                        <td>Tanggal Pendaftaran</td>
                        <td>:</td>
                        <td><?php echo tanggal_indonesia(date('Y-m-d', strtotime($pendaftaran['tanggal_daftar']))); ?></td>
                    </tr>
                    <tr>
                        <td>Status Verifikasi</td>
                        <td>:</td>
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
            
            <!-- Barcode -->
            <div class="barcode">
                <div style="font-family: 'Courier New', monospace; font-size: 0.75rem; letter-spacing: 1px;">
                    |||| || ||| |||| | ||| || |||| | || ||||<br>
                    <?php echo $pendaftaran['nomor_pendaftaran']; ?>
                </div>
            </div>
            
            <!-- Footer & Tanda Tangan -->
            <div class="kartu-footer">
                <div class="row">
                    <div class="col-6">
                        <p class="mb-0 small text-muted">Dicetak pada:</p>
                        <p class="fw-bold"><?php echo tanggal_indonesia(date('Y-m-d')) . ' ' . date('H:i'); ?> WIB</p>
                    </div>
                    <div class="col-6">
                        <p class="mb-1">Padang, <?php echo tanggal_indonesia(date('Y-m-d')); ?></p>
                        <p class="mb-0">Panitia PPDB</p>
                        <div class="ttd-box">
                            <p class="fw-bold mb-0">(_________________)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Info Bottom -->
            <div class="mt-4 p-3 bg-light rounded">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Catatan:</strong> Kartu pendaftaran ini merupakan bukti sah pendaftaran PPDB. 
                    Harap dibawa saat daftar ulang jika dinyatakan lulus.
                </small>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="no-print">
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * File: siswa/cetak_hasil.php
 * Deskripsi: Halaman cetak surat kelulusan/hasil seleksi PPDB
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

// Ambil hasil seleksi yang LULUS
$sql_hasil = "SELECT hs.*, p.nomor_pendaftaran, p.id_jalur, p.id_sekolah,
                     s.nama_sekolah, s.alamat_sekolah, s.npsn,
                     j.nama_jalur
              FROM hasil_seleksi hs
              JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
              JOIN sekolah s ON p.id_sekolah = s.id_sekolah
              JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
              WHERE p.id_siswa = ? 
              AND p.tahun_ajaran = ?
              AND hs.status_seleksi = 'lulus'
              AND hs.dipublikasikan = 1
              LIMIT 1";
$stmt_h = mysqli_prepare($conn, $sql_hasil);
mysqli_stmt_bind_param($stmt_h, "is", $id_siswa, $tahun_ajaran);
mysqli_stmt_execute($stmt_h);
$hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_h));

// Jika tidak ada hasil lulus
$belum_lulus = !$hasil;

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
    <title>Surat Kelulusan PPDB - <?php echo $siswa['nama_lengkap']; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .surat-container { max-width: 800px; margin: 2rem auto; }
        
        .surat-kelulusan {
            background: white;
            border: 3px solid #28a745;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .surat-header {
            text-align: center;
            border-bottom: 3px double #28a745;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .surat-logo {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            color: white;
        }
        
        .surat-title { font-size: 1.5rem; font-weight: 700; color: #2d3748; }
        .surat-subtitle { color: #6c757d; }
        
        .nomor-surat {
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 2rem;
        }
        
        .status-box {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .status-icon { font-size: 3rem; margin-bottom: 0.5rem; }
        .status-text { font-size: 1.75rem; font-weight: 700; }
        
        .info-table { width: 100%; margin-bottom: 2rem; }
        .info-table tr { line-height: 2.2; }
        .info-table td:first-child { width: 35%; color: #6c757d; }
        .info-table td:nth-child(2) { width: 5%; }
        .info-table td:last-child { font-weight: 600; }
        
        .sekolah-box {
            background: #e8f5e9;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .sekolah-name { font-size: 1.25rem; font-weight: 700; color: #2d3748; }
        .sekolah-npsn { color: #6c757d; }
        
        .footer-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px dashed #dee2e6;
        }
        
        .ttd-area {
            margin-top: 5rem;
            text-align: right;
        }
        
        .catatan-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .surat-container { margin: 0; max-width: 100%; }
            .surat-kelulusan { border: 2px solid #000; box-shadow: none; }
        }
    </style>
</head>
<body>
    <!-- Navbar - No Print -->
    <div class="no-print">
        <?php include '../includes/navbar.php'; ?>
    </div>
    
    <div class="container surat-container">
        <?php if ($belum_lulus): ?>
        <!-- Belum Lulus / Belum Ada Hasil -->
        <div class="card shadow-lg">
            <div class="card-header bg-secondary text-white">
                <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hasil Belum Tersedia</h4>
            </div>
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-hourglass-half fa-4x text-secondary"></i>
                </div>
                <h5 class="fw-bold">Surat Kelulusan Tidak Tersedia</h5>
                <p class="text-muted mb-4">
                    Surat kelulusan hanya dapat dicetak jika Anda dinyatakan <strong>LULUS</strong> 
                    dan hasil sudah dipublikasikan oleh panitia.
                </p>
                <a href="index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Button Actions - No Print -->
        <div class="no-print mb-3 text-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print me-2"></i>Cetak Surat
            </button>
        </div>
        
        <!-- Surat Kelulusan -->
        <div class="surat-kelulusan">
            <!-- Header -->
            <div class="surat-header">
                <div class="surat-logo">
                    <i class="fas fa-award"></i>
                </div>
                <div class="surat-title">SURAT KETERANGAN KELULUSAN</div>
                <div class="surat-subtitle">
                    Penerimaan Peserta Didik Baru (PPDB) SMP<br>
                    Tahun Ajaran <?php echo $tahun_ajaran; ?>
                </div>
            </div>
            
            <!-- Nomor Surat -->
            <div class="nomor-surat">
                Nomor: <?php echo $hasil['nomor_pendaftaran']; ?>/PPDB/<?php echo date('Y'); ?>
            </div>
            
            <!-- Status Box -->
            <div class="status-box">
                <div class="status-icon">🎉</div>
                <div class="status-text">SELAMAT, ANDA DINYATAKAN LULUS!</div>
            </div>
            
            <!-- Isi Surat -->
            <p>Dengan ini menerangkan bahwa:</p>
            
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
                    <td>Tempat, Tanggal Lahir</td>
                    <td>:</td>
                    <td><?php echo ($siswa['tempat_lahir'] ?? '-') . ', ' . (isset($siswa['tanggal_lahir']) ? tanggal_indonesia($siswa['tanggal_lahir']) : '-'); ?></td>
                </tr>
                <tr>
                    <td>Asal Sekolah</td>
                    <td>:</td>
                    <td><?php echo $siswa['asal_sekolah'] ?? '-'; ?></td>
                </tr>
                <tr>
                    <td>Nomor Pendaftaran</td>
                    <td>:</td>
                    <td><strong><?php echo $hasil['nomor_pendaftaran']; ?></strong></td>
                </tr>
                <tr>
                    <td>Jalur PPDB</td>
                    <td>:</td>
                    <td><span class="badge bg-primary"><?php echo $hasil['nama_jalur']; ?></span></td>
                </tr>
                <tr>
                    <td>Ranking</td>
                    <td>:</td>
                    <td><strong>#<?php echo $hasil['ranking']; ?></strong></td>
                </tr>
                <tr>
                    <td>Diterima di Pilihan</td>
                    <td>:</td>
                    <td>Pilihan <?php echo $hasil['pilihan_diterima'] ?? '1'; ?></td>
                </tr>
            </table>
            
            <p>Telah dinyatakan <strong>LULUS</strong> seleksi PPDB dan diterima di:</p>
            
            <!-- Sekolah Box -->
            <div class="sekolah-box">
                <div class="sekolah-name">
                    <i class="fas fa-school me-2"></i><?php echo $hasil['nama_sekolah']; ?>
                </div>
                <div class="sekolah-npsn">NPSN: <?php echo $hasil['npsn']; ?></div>
                <div class="sekolah-npsn"><?php echo $hasil['alamat_sekolah']; ?></div>
            </div>
            
            <!-- Footer -->
            <div class="footer-section">
                <div class="row">
                    <div class="col-6">
                        <p class="small text-muted mb-1">Dicetak pada:</p>
                        <p class="fw-bold"><?php echo tanggal_indonesia(date('Y-m-d')) . ' ' . date('H:i'); ?> WIB</p>
                    </div>
                    <div class="col-6 ttd-area">
                        <p class="mb-1">Padang, <?php echo tanggal_indonesia(date('Y-m-d')); ?></p>
                        <p class="mb-0">Panitia PPDB</p>
                        <div style="margin-top: 4rem;">
                            <p class="fw-bold mb-0">(_________________)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Catatan -->
            <div class="catatan-box">
                <strong><i class="fas fa-exclamation-triangle me-2"></i>PENTING:</strong>
                <ul class="mb-0 mt-2">
                    <li>Surat ini merupakan bukti kelulusan sementara.</li>
                    <li>Harap melakukan <strong>DAFTAR ULANG</strong> sesuai jadwal yang ditentukan.</li>
                    <li>Bawalah dokumen asli saat daftar ulang.</li>
                    <li>Kelulusan dapat dibatalkan jika tidak melakukan daftar ulang tepat waktu.</li>
                </ul>
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


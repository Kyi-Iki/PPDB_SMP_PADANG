<?php
/**
 * File: siswa/hasil_seleksi.php
 * Deskripsi: Halaman pengumuman hasil seleksi PPDB untuk siswa (Multi-Jalur)
 */

session_start();
require_once '../config/database.php';

check_siswa();

$id_siswa = $_SESSION['id_siswa'];
$tahun_ajaran = TAHUN_AJARAN;

// Ambil SEMUA pendaftaran siswa dengan hasil seleksi
$sql_pendaftaran = "SELECT p.*, s.nama_sekolah, s.npsn, s2.nama_sekolah as nama_sekolah_2, s2.npsn as npsn_2,
                    j.nama_jalur, j.kode_jalur,
                    hs.id_hasil, hs.skor_akhir, hs.ranking, hs.status_seleksi, hs.keterangan, 
                    hs.dipublikasikan, hs.tanggal_publikasi
                    FROM pendaftaran p
                    JOIN sekolah s ON p.id_sekolah = s.id_sekolah
                    LEFT JOIN sekolah s2 ON p.id_sekolah_pilihan_2 = s2.id_sekolah
                    JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
                    LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                    WHERE p.id_siswa = ? AND p.tahun_ajaran = ?
                    ORDER BY p.tanggal_daftar DESC";
$stmt = mysqli_prepare($conn, $sql_pendaftaran);
mysqli_stmt_bind_param($stmt, "is", $id_siswa, $tahun_ajaran);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$semua_pendaftaran = [];
$pendaftaran_lulus = null;
$belum_daftar = true;

while ($p = mysqli_fetch_assoc($result)) {
    $belum_daftar = false;
    $semua_pendaftaran[] = $p;
    if ($p['status_seleksi'] == 'lulus' && $p['dipublikasikan']) {
        $pendaftaran_lulus = $p;
    }
}

// Ambil data siswa lengkap
$sql_siswa = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt_siswa = mysqli_prepare($conn, $sql_siswa);
mysqli_stmt_bind_param($stmt_siswa, "i", $id_siswa);
mysqli_stmt_execute($stmt_siswa);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_siswa));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Seleksi - PPDB SMP</title>
    
    <?php include '../includes/head_siswa.php'; ?>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #f5f7fa;
        }
        
        .result-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .status-lulus {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        .status-tidak-lulus {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        .status-pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-md-3 no-print">
                <?php include '../includes/sidebar_siswa.php'; ?>
            </div>
            
            <div class="col-md-9">
                <?php if ($belum_daftar): ?>
                <div class="card result-card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Belum Mendaftar</h4>
                    </div>
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-school fa-4x text-warning"></i>
                        </div>
                        <h5 class="fw-bold">Anda Belum Memilih Sekolah</h5>
                        <p class="text-muted mb-4">
                            Silakan pilih sekolah dan jalur PPDB terlebih dahulu untuk melihat hasil seleksi.
                        </p>
                        <a href="pilih_sekolah.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-school me-2"></i>
                            Pilih Sekolah Sekarang
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="card result-card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">📢 Pengumuman Hasil Seleksi PPDB</h4>
                        <p class="mb-0 small">Tahun Ajaran <?php echo $tahun_ajaran; ?></p>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($pendaftaran_lulus): ?>
                        <!-- LULUS DI SALAH SATU JALUR -->
                        <div class="status-lulus mb-4">
                            <h1>🎉 SELAMAT!</h1>
                            <h3>ANDA DINYATAKAN <strong>LULUS</strong></h3>
                            <p class="mb-0">Jalur <?php echo $pendaftaran_lulus['nama_jalur']; ?> - <?php echo $pendaftaran_lulus['nama_sekolah']; ?></p>
                        </div>
                        
                        <!-- Detail Kelulusan -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <h6 class="text-primary">📋 Data Peserta</h6>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td width="40%">Nomor Pendaftaran</td>
                                            <td>: <strong><?php echo $pendaftaran_lulus['nomor_pendaftaran']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Nama Lengkap</td>
                                            <td>: <strong><?php echo $siswa['nama_lengkap']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>NISN</td>
                                            <td>: <?php echo $siswa['nisn']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <h6 class="text-primary">🏫 Sekolah Tujuan</h6>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td width="40%">Pilihan 1</td>
                                            <td>: <strong><?php echo $pendaftaran_lulus['nama_sekolah']; ?></strong></td>
                                        </tr>
                                        <?php if (!empty($pendaftaran_lulus['nama_sekolah_2'])): ?>
                                        <tr>
                                            <td>Pilihan 2</td>
                                            <td>: <?php echo $pendaftaran_lulus['nama_sekolah_2']; ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td>Jalur</td>
                                            <td>: <span class="badge bg-success"><?php echo $pendaftaran_lulus['nama_jalur']; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>Ranking</td>
                                            <td>: <strong class="text-danger">#<?php echo $pendaftaran_lulus['ranking']; ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-success">
                            <h5>🎓 Langkah Selanjutnya:</h5>
                            <ol class="mb-0">
                                <li>Cetak surat kelulusan ini</li>
                                <li>Lakukan daftar ulang sesuai jadwal yang ditentukan</li>
                                <li>Siapkan dokumen yang diperlukan</li>
                            </ol>
                        </div>
                        
                        <div class="text-center mt-4 no-print">
                            <button onclick="window.print()" class="btn btn-primary btn-lg">
                                🖨️ Cetak Pengumuman
                            </button>
                        </div>
                        
                        <?php else: ?>
                        <!-- BELUM LULUS ATAU HASIL BELUM DIPUBLIKASI -->
                        
                        <h5 class="mb-3">Riwayat Pendaftaran Anda</h5>
                        
                        <?php foreach ($semua_pendaftaran as $pend): ?>
                        <div class="info-box">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">
                                    <span class="badge bg-primary me-2"><?php echo $pend['nama_jalur']; ?></span>
                                    <?php echo $pend['nama_sekolah']; ?>
                                </h6>
                                <?php if ($pend['dipublikasikan']): ?>
                                <span class="badge bg-<?php 
                                    echo $pend['status_seleksi'] == 'lulus' ? 'success' : 
                                        ($pend['status_seleksi'] == 'tidak_lulus' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $pend['status_seleksi'])); ?>
                                </span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Menunggu Pengumuman</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                No. Pendaftaran: <?php echo $pend['nomor_pendaftaran']; ?> | 
                                Daftar: <?php echo tanggal_indonesia($pend['tanggal_daftar']); ?>
                                <?php if ($pend['ranking']): ?> | Ranking: #<?php echo $pend['ranking']; ?><?php endif; ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php 
                        // Cek apakah ada yang belum dipublikasi
                        $ada_menunggu = false;
                        foreach ($semua_pendaftaran as $pend) {
                            if (!$pend['dipublikasikan']) {
                                $ada_menunggu = true;
                                break;
                            }
                        }
                        ?>
                        
                        <?php if ($ada_menunggu): ?>
                        <div class="alert alert-info mt-3">
                            <strong>⏳ Proses seleksi masih berlangsung.</strong> 
                            Silakan cek kembali secara berkala untuk melihat hasil.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <strong>ℹ️ Anda belum lulus di jalur yang Anda daftarkan.</strong> 
                            Pada sistem serentak, setiap siswa hanya bisa mendaftar di 1 jalur. 
                            Silakan hubungi panitia PPDB untuk informasi lebih lanjut.
                        </div>
                        <?php endif; ?>
                        
                        <?php endif; // end if pendaftaran_lulus ?>
                        
                    </div>
                </div>
                <?php endif; // end if belum_daftar ?>
                
                <!-- Info Tambahan -->
                <div class="card mt-4 no-print">
                    <div class="card-body">
                        <h6 class="text-primary">📞 Informasi Kontak</h6>
                        <p class="mb-1">Jika ada pertanyaan lebih lanjut, silakan hubungi:</p>
                        <ul class="mb-0">
                            <li>Telp: (0751) 123456</li>
                            <li>Email: info@ppdb-smp.sch.id</li>
                            <li>Website: www.ppdb-smp.sch.id</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Auto refresh setiap 1 menit jika ada hasil yang belum dipublikasikan
        <?php 
        $auto_refresh = false;
        foreach ($semua_pendaftaran as $pend) {
            if (!$pend['dipublikasikan']) {
                $auto_refresh = true;
                break;
            }
        }
        if ($auto_refresh): 
        ?>
        setTimeout(function() {
            location.reload();
        }, 60000); // 60 detik
        <?php endif; ?>
    </script>
</body>
</html>

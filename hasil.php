<?php
/**
 * File: hasil.php
 * Deskripsi: Halaman publik untuk mencari/melihat hasil seleksi PPDB
 */

require_once 'config/database.php';

$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$hasil = null;
$siswa = null;

// Handle form search
if (isset($_GET['cari'])) {
    $keyword = clean_input($_GET['keyword'] ?? '');
    
    if (empty($keyword)) {
        $error = 'Masukkan nomor pendaftaran atau NIK untuk mencari hasil.';
    } else {
        // Cari berdasarkan nomor pendaftaran atau NIK
        $sql = "SELECT 
                    hs.*, 
                    p.nomor_pendaftaran,
                    p.id_jalur,
                    p.id_sekolah,
                    s.nama_lengkap,
                    s.nik,
                    sk.nama_sekolah,
                    j.nama_jalur
                FROM hasil_seleksi hs
                JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
                JOIN siswa s ON p.id_siswa = s.id_siswa
                JOIN sekolah sk ON p.id_sekolah = sk.id_sekolah
                JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
                WHERE (p.nomor_pendaftaran = ? OR s.nik = ?)
                AND p.tahun_ajaran = ?
                AND hs.dipublikasikan = 1
                LIMIT 1";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $keyword, $keyword, $tahun_ajaran);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hasil = mysqli_fetch_assoc($result);
        
        if (!$hasil) {
            // Cek apakah data ada tapi belum dipublikasikan
            $sql_check = "SELECT p.nomor_pendaftaran, s.nama_lengkap
                         FROM pendaftaran p
                         JOIN siswa s ON p.id_siswa = s.id_siswa
                         WHERE (p.nomor_pendaftaran = ? OR s.nik = ?)
                         AND p.tahun_ajaran = ?
                         LIMIT 1";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "sss", $keyword, $keyword, $tahun_ajaran);
            mysqli_stmt_execute($stmt_check);
            $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check));
            
            if ($siswa) {
                $error = 'Hasil seleksi untuk pendaftar ini belum dipublikasikan. Silakan periksa kembali nanti.';
            } else {
                $error = 'Data tidak ditemukan. Pastikan nomor pendaftaran atau NIK yang Anda masukkan sudah benar.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Hasil Seleksi - PPDB SMP <?php echo TAHUN_AJARAN; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/design-system.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <style>
        body { 
            background: var(--gradient-hero);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .search-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        
        .result-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
        }
        
        .status-badge {
            font-size: 1.5rem;
            padding: 15px 40px;
            border-radius: 50px;
        }
        
        .status-lulus {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .status-tidak-lulus {
            background: linear-gradient(135deg, #E53935 0%, #E35D5B 100%);
            color: white;
        }
        
        .info-row {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .info-value {
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
        }
        
        .back-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        .back-link:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-5 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
        <div class="text-center mb-4">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
        
        <?php if ($hasil): ?>
        <!-- Hasil Ditemukan -->
        <div class="result-card">
            <div class="text-center mb-4">
                <h2 class="fw-bold mb-2">Hasil Seleksi PPDB</h2>
                <p class="text-muted">Tahun Ajaran <?php echo TAHUN_AJARAN; ?></p>
            </div>
            
            <!-- Status Badge -->
            <div class="text-center mb-4">
                <?php if ($hasil['status_seleksi'] == 'lulus'): ?>
                <span class="status-badge status-lulus">
                    <i class="fas fa-check-circle me-2"></i>LULUS / DITERIMA
                </span>
                <?php else: ?>
                <span class="status-badge status-tidak-lulus">
                    <i class="fas fa-times-circle me-2"></i>TIDAK LULUS
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Info Peserta -->
            <div class="bg-light rounded-3 p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-user text-primary me-2"></i>Data Peserta</h5>
                <div class="info-row">
                    <div class="info-label">Nama Lengkap</div>
                    <div class="info-value"><?php echo htmlspecialchars($hasil['nama_lengkap']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nomor Pendaftaran</div>
                    <div class="info-value"><?php echo $hasil['nomor_pendaftaran']; ?></div>
                </div>
            </div>
            
            <!-- Info Sekolah -->
            <div class="bg-light rounded-3 p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-school text-success me-2"></i>Sekolah Tujuan</h5>
                <div class="info-row">
                    <div class="info-label">Nama Sekolah</div>
                    <div class="info-value"><?php echo htmlspecialchars($hasil['nama_sekolah']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Jalur Pendaftaran</div>
                    <div class="info-value">
                        <span class="badge bg-<?php 
                            echo match($hasil['nama_jalur']) {
                                'Zonasi' => 'primary',
                                'Prestasi' => 'success',
                                'Afirmasi' => 'warning',
                                'Mutasi' => 'info',
                                default => 'secondary'
                            };
                        ?>"><?php echo $hasil['nama_jalur']; ?></span>
                    </div>
                </div>
                <?php if ($hasil['status_seleksi'] == 'lulus'): ?>
                <div class="info-row">
                    <div class="info-label">Diterima di Pilihan</div>
                    <div class="info-value">Pilihan <?php echo $hasil['pilihan_diterima'] ?? '-'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Ranking</div>
                    <div class="info-value">#<?php echo $hasil['ranking']; ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($hasil['status_seleksi'] == 'lulus'): ?>
            <div class="alert alert-success">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Selamat!</strong> Anda diterima. Silakan lakukan daftar ulang sesuai jadwal yang ditentukan.
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Jangan berkecil hati! Masih ada kesempatan di jalur lain atau tahun ajaran berikutnya.
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="hasil.php" class="btn btn-outline-primary">
                    <i class="fas fa-search me-2"></i>Cari Hasil Lain
                </a>
                <a href="index.php" class="btn btn-primary ms-2">
                    <i class="fas fa-home me-2"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Form Pencarian -->
        <div class="search-container">
            <div class="text-center mb-4">
                <div class="display-1 mb-3">🎓</div>
                <h2 class="fw-bold mb-2">Cek Hasil Seleksi</h2>
                <p class="text-muted">PPDB SMP Tahun Ajaran <?php echo TAHUN_AJARAN; ?></p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-<?php echo $siswa ? 'warning' : 'danger'; ?> mb-4">
                <i class="fas fa-<?php echo $siswa ? 'clock' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="GET" action="">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Cari Berdasarkan:</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" name="keyword" class="form-control" 
                               placeholder="Ketik nomor pendaftaran atau NIK..."
                               value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>"
                               required>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <small class="text-muted d-block">
                                <i class="fas fa-file-alt text-primary me-1"></i>
                                <strong>No. Pendaftaran:</strong><br>
                                <code class="bg-light px-1">PPDB-2526-XXXXX</code><br>
                                <span class="text-muted">(lihat di bukti daftar)</span>
                            </small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">
                                <i class="fas fa-id-card text-success me-1"></i>
                                <strong>NIK:</strong><br>
                                <code class="bg-light px-1">16 digit angka</code><br>
                                <span class="text-muted">(lihat di KK/KTP)</span>
                            </small>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="cari" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-search me-2"></i>Cari Hasil
                </button>
            </form>
            
            <div class="text-center mt-4 pt-4 border-top">
                <p class="text-muted small mb-2">Belum mendaftar?</p>
                <a href="siswa/register.php" class="btn btn-outline-success">
                    <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <footer class="text-center py-3 text-white-50">
        <small>&copy; <?php echo date('Y'); ?> PPDB SMP - Dinas Pendidikan</small>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

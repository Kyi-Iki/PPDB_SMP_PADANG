<?php
/**
 * File: admin/seleksi/publikasi.php
 * Deskripsi: Halaman publikasi pengumuman hasil seleksi oleh admin
 */

session_start();
require_once '../../config/database.php';

check_admin();
check_super_admin(); // Only super_admin can publish results

$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';

// Proses publikasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publikasi'])) {
    $id_sekolah = (int)clean_input($_POST['id_sekolah']);
    $id_jalur = (int)clean_input($_POST['id_jalur']);
    
    // Cek apakah aksi diizinkan berdasarkan status GLOBAL
    $cek_aksi = cek_aksi_diizinkan('publikasi');
    
    if (!$cek_aksi['diizinkan']) {
        $error = $cek_aksi['pesan'] . " <a href='pengaturan.php' class='alert-link'>Ubah di Pengaturan</a>";
    } else {
        // Update status publikasi - filter by jalur juga
        $sql_update = "UPDATE hasil_seleksi hs
                       JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
                       SET hs.dipublikasikan = 1, hs.tanggal_publikasi = NOW()
                       WHERE p.id_sekolah = ? AND p.id_jalur = ? AND p.tahun_ajaran = ?";
        
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, "iis", $id_sekolah, $id_jalur, $tahun_ajaran);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            
            $jalur_info = get_jadwal_jalur($id_jalur);
            log_aktivitas($_SESSION['id_user'], 'Publikasi Hasil', 
                         "Publikasi hasil jalur: {$jalur_info['nama_jalur']}, sekolah ID: $id_sekolah, Total: $affected");
            
            $success = "Pengumuman berhasil dipublikasikan untuk <strong>$affected pendaftar</strong> di jalur <strong>{$jalur_info['nama_jalur']}</strong>!";
        } else {
            $error = "Gagal mempublikasikan pengumuman.";
        }
    }
}

// Ambil daftar sekolah
$sql_sekolah = "SELECT * FROM sekolah WHERE status = 'aktif' AND tahun_ajaran = ? ORDER BY nama_sekolah";
$stmt_sekolah = mysqli_prepare($conn, $sql_sekolah);
mysqli_stmt_bind_param($stmt_sekolah, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt_sekolah);
$sekolah_list = mysqli_stmt_get_result($stmt_sekolah);

// Ambil daftar jalur
$list_jalur = get_semua_jadwal_jalur();

// Statistik keseluruhan
$sql_stats = "SELECT 
                COUNT(*) as total_hasil,
                SUM(CASE WHEN status_seleksi = 'lulus' THEN 1 ELSE 0 END) as total_lulus,
                SUM(CASE WHEN status_seleksi = 'tidak_lulus' THEN 1 ELSE 0 END) as total_tidak_lulus,
                SUM(CASE WHEN dipublikasikan = 1 THEN 1 ELSE 0 END) as total_dipublikasikan
              FROM hasil_seleksi hs
              JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
              WHERE p.tahun_ajaran = ?";
$stmt_stats = mysqli_prepare($conn, $sql_stats);
mysqli_stmt_bind_param($stmt_stats, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt_stats);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publikasi Pengumuman - Admin PPDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
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
                <!-- Statistik -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h2 class="text-primary"><?php echo $stats['total_hasil']; ?></h2>
                                <p class="mb-0">Total Hasil</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h2 class="text-success"><?php echo $stats['total_lulus']; ?></h2>
                                <p class="mb-0">Lulus</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h2 class="text-danger"><?php echo $stats['total_tidak_lulus']; ?></h2>
                                <p class="mb-0">Tidak Lulus</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h2 class="text-info"><?php echo $stats['total_dipublikasikan']; ?></h2>
                                <p class="mb-0">Dipublikasikan</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">📢 Publikasi Pengumuman</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <strong>⚠️ Perhatian:</strong> Setelah pengumuman dipublikasikan, siswa dapat melihat hasil seleksi mereka. 
                            Pastikan proses seleksi sudah selesai dan hasil sudah diverifikasi dengan benar.
                        </div>
                        
                        <form method="POST" action="" onsubmit="return confirm('Yakin ingin mempublikasikan pengumuman? Tindakan ini akan membuat hasil terlihat oleh siswa.');">
                            <!-- Pilih Jalur -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">1. Pilih Jalur *</label>
                                <select class="form-select" name="id_jalur" required>
                                    <option value="">-- Pilih Jalur --</option>
                                    <?php while ($jalur = mysqli_fetch_assoc($list_jalur)): ?>
                                    <option value="<?php echo $jalur['id_jalur']; ?>">
                                        <?php echo $jalur['nama_jalur']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <!-- Pilih Sekolah -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">2. Pilih Sekolah *</label>
                                <select class="form-select" name="id_sekolah" required>
                                    <option value="">-- Pilih Sekolah --</option>
                                    <?php 
                                    mysqli_data_seek($sekolah_list, 0);
                                    while ($sekolah = mysqli_fetch_assoc($sekolah_list)): 
                                    ?>
                                    <option value="<?php echo $sekolah['id_sekolah']; ?>">
                                        <?php echo $sekolah['nama_sekolah']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="publikasi" class="btn btn-success btn-lg">
                                📢 Publikasikan Pengumuman
                            </button>
                            <a href="hasil.php" class="btn btn-outline-primary">
                                📊 Lihat Hasil Seleksi
                            </a>
                        </form>
                        
                        <hr class="my-4">
                        
                        <h5>📋 Daftar Sekolah dengan Hasil Seleksi</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Sekolah</th>
                                        <th>Total Hasil</th>
                                        <th>Lulus</th>
                                        <th>Tidak Lulus</th>
                                        <th>Status Publikasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    mysqli_data_seek($sekolah_list, 0);
                                    $no = 1;
                                    while ($sekolah = mysqli_fetch_assoc($sekolah_list)):
                                        $sql_detail = "SELECT 
                                                        COUNT(*) as total,
                                                        SUM(CASE WHEN hs.status_seleksi = 'lulus' THEN 1 ELSE 0 END) as lulus,
                                                        SUM(CASE WHEN hs.status_seleksi = 'tidak_lulus' THEN 1 ELSE 0 END) as tidak_lulus,
                                                        SUM(CASE WHEN hs.dipublikasikan = 1 THEN 1 ELSE 0 END) as published
                                                       FROM hasil_seleksi hs
                                                       JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
                                                       WHERE p.id_sekolah = ? AND p.tahun_ajaran = ?";
                                        $stmt_detail = mysqli_prepare($conn, $sql_detail);
                                        mysqli_stmt_bind_param($stmt_detail, "is", $sekolah['id_sekolah'], $tahun_ajaran);
                                        mysqli_stmt_execute($stmt_detail);
                                        $detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_detail));
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $sekolah['nama_sekolah']; ?></td>
                                        <td class="text-center"><?php echo $detail['total']; ?></td>
                                        <td class="text-center text-success"><strong><?php echo $detail['lulus']; ?></strong></td>
                                        <td class="text-center text-danger"><?php echo $detail['tidak_lulus']; ?></td>
                                        <td class="text-center">
                                            <?php if ($detail['published'] > 0 && $detail['published'] == $detail['total']): ?>
                                                <span class="badge bg-success">✅ Sudah Dipublikasi</span>
                                            <?php elseif ($detail['published'] > 0): ?>
                                                <span class="badge bg-warning">⏳ Sebagian</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">❌ Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="hasil.php?id_sekolah=<?php echo $sekolah['id_sekolah']; ?>" 
                                               class="btn btn-sm btn-info">
                                                👁️ Detail
                                            </a>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
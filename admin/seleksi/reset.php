<?php
/**
 * File: admin/seleksi/reset.php
 * Deskripsi: Reset hasil seleksi per sekolah atau per jalur
 */

session_start();
require_once '../../config/database.php';

check_admin();
check_super_admin(); // Only super_admin can reset selection

$error = '';
$success = '';
$tahun_ajaran = TAHUN_AJARAN;

// Ambil daftar jalur
$list_jalur = get_semua_jadwal_jalur();

// Proses Reset Seleksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    mysqli_begin_transaction($conn);
    
    try {
        // Reset per sekolah DAN jalur tertentu
        if ($action == 'reset_sekolah_jalur' && isset($_POST['id_sekolah']) && isset($_POST['id_jalur'])) {
            $id_sekolah = (int)clean_input($_POST['id_sekolah']);
            $id_jalur = (int)clean_input($_POST['id_jalur']);
            
            // Ambil info sekolah dan jalur
            $sql_info = "SELECT s.nama_sekolah, j.nama_jalur 
                        FROM sekolah s, jalur_ppdb j 
                        WHERE s.id_sekolah = ? AND j.id_jalur = ?";
            $stmt_info = mysqli_prepare($conn, $sql_info);
            mysqli_stmt_bind_param($stmt_info, "ii", $id_sekolah, $id_jalur);
            mysqli_stmt_execute($stmt_info);
            $info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));
            
            // Hapus hasil seleksi untuk sekolah dan jalur ini
            $sql_delete = "DELETE hs FROM hasil_seleksi hs
                          JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
                          WHERE p.id_sekolah = ? AND p.id_jalur = ? AND p.tahun_ajaran = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete);
            mysqli_stmt_bind_param($stmt_delete, "iis", $id_sekolah, $id_jalur, $tahun_ajaran);
            mysqli_stmt_execute($stmt_delete);
            $deleted = mysqli_affected_rows($conn);
            
            // Reset kuota terpakai untuk jalur ini saja
            $sql_reset_kuota = "UPDATE kuota_jalur SET kuota_terpakai = 0 
                               WHERE id_sekolah = ? AND id_jalur = ? AND tahun_ajaran = ?";
            $stmt_kuota = mysqli_prepare($conn, $sql_reset_kuota);
            mysqli_stmt_bind_param($stmt_kuota, "iis", $id_sekolah, $id_jalur, $tahun_ajaran);
            mysqli_stmt_execute($stmt_kuota);
            
            mysqli_commit($conn);
            
            log_aktivitas($_SESSION['id_user'], 'Reset Seleksi', 
                         "Reset seleksi {$info['nama_sekolah']} - Jalur {$info['nama_jalur']} ($deleted hasil dihapus)");
            
            $success = "Berhasil reset seleksi untuk <strong>{$info['nama_sekolah']}</strong> jalur <strong>{$info['nama_jalur']}</strong>. $deleted hasil dihapus.";
            
        // Reset semua jalur di sekolah tertentu
        } elseif ($action == 'reset_sekolah' && isset($_POST['id_sekolah'])) {
            $id_sekolah = (int)clean_input($_POST['id_sekolah']);
            
            // Ambil nama sekolah untuk log
            $sql_nama = "SELECT nama_sekolah FROM sekolah WHERE id_sekolah = ?";
            $stmt_nama = mysqli_prepare($conn, $sql_nama);
            mysqli_stmt_bind_param($stmt_nama, "i", $id_sekolah);
            mysqli_stmt_execute($stmt_nama);
            $nama_sekolah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_nama))['nama_sekolah'];
            
            // Hapus hasil seleksi untuk sekolah ini
            $sql_delete = "DELETE hs FROM hasil_seleksi hs
                          JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
                          WHERE p.id_sekolah = ? AND p.tahun_ajaran = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete);
            mysqli_stmt_bind_param($stmt_delete, "is", $id_sekolah, $tahun_ajaran);
            mysqli_stmt_execute($stmt_delete);
            $deleted = mysqli_affected_rows($conn);
            
            // Reset kuota terpakai
            $sql_reset_kuota = "UPDATE kuota_jalur SET kuota_terpakai = 0 
                               WHERE id_sekolah = ? AND tahun_ajaran = ?";
            $stmt_kuota = mysqli_prepare($conn, $sql_reset_kuota);
            mysqli_stmt_bind_param($stmt_kuota, "is", $id_sekolah, $tahun_ajaran);
            mysqli_stmt_execute($stmt_kuota);
            
            mysqli_commit($conn);
            
            log_aktivitas($_SESSION['id_user'], 'Reset Seleksi', 
                         "Reset seleksi sekolah: $nama_sekolah ($deleted hasil dihapus)");
            
            $success = "Berhasil reset seleksi untuk <strong>$nama_sekolah</strong> (semua jalur). $deleted hasil dihapus.";
            
        } elseif ($action == 'reset_semua') {
            // Hapus SEMUA hasil seleksi tahun ini
            $sql_delete_all = "DELETE hs FROM hasil_seleksi hs
                              JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
                              WHERE p.tahun_ajaran = ?";
            $stmt_delete_all = mysqli_prepare($conn, $sql_delete_all);
            mysqli_stmt_bind_param($stmt_delete_all, "s", $tahun_ajaran);
            mysqli_stmt_execute($stmt_delete_all);
            $deleted = mysqli_affected_rows($conn);
            
            // Reset semua kuota terpakai
            $sql_reset_all = "UPDATE kuota_jalur SET kuota_terpakai = 0 WHERE tahun_ajaran = ?";
            $stmt_reset_all = mysqli_prepare($conn, $sql_reset_all);
            mysqli_stmt_bind_param($stmt_reset_all, "s", $tahun_ajaran);
            mysqli_stmt_execute($stmt_reset_all);
            
            mysqli_commit($conn);
            
            log_aktivitas($_SESSION['id_user'], 'Reset Seleksi', 
                         "Reset SEMUA seleksi tahun $tahun_ajaran ($deleted hasil dihapus)");
            
            $success = "Berhasil reset <strong>SEMUA</strong> hasil seleksi tahun $tahun_ajaran. $deleted hasil dihapus.";
        }
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Gagal reset seleksi: " . $e->getMessage();
    }
}

// Ambil daftar sekolah dengan jumlah hasil seleksi
$sql_sekolah = "SELECT s.*, 
                (SELECT COUNT(*) FROM hasil_seleksi hs 
                 JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran 
                 WHERE p.id_sekolah = s.id_sekolah AND p.tahun_ajaran = ?) as total_hasil,
                (SELECT COUNT(*) FROM hasil_seleksi hs 
                 JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran 
                 WHERE p.id_sekolah = s.id_sekolah AND p.tahun_ajaran = ? 
                 AND hs.status_seleksi = 'lulus') as total_lulus
                FROM sekolah s 
                WHERE s.status = 'aktif' AND s.tahun_ajaran = ?
                ORDER BY s.nama_sekolah";
$stmt_sekolah = mysqli_prepare($conn, $sql_sekolah);
mysqli_stmt_bind_param($stmt_sekolah, "sss", $tahun_ajaran, $tahun_ajaran, $tahun_ajaran);
mysqli_stmt_execute($stmt_sekolah);
$sekolah_list = mysqli_stmt_get_result($stmt_sekolah);

// Hitung total hasil seleksi
$sql_total = "SELECT COUNT(*) as total FROM hasil_seleksi hs
              JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
              WHERE p.tahun_ajaran = ?";
$stmt_total = mysqli_prepare($conn, $sql_total);
mysqli_stmt_bind_param($stmt_total, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt_total);
$total_hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Seleksi - Admin PPDB</title>
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
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="fas fa-undo me-2"></i>Reset Hasil Seleksi</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Peringatan!</h5>
                            <p class="mb-0">Reset akan menghapus hasil seleksi. 
                            Data pendaftaran dan verifikasi tetap aman. Proses ini tidak dapat dibatalkan.</p>
                        </div>
                        
                        <!-- Reset per Sekolah & Jalur (BARU) -->
                        <div class="card mb-4 border-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">🎯 Reset per Sekolah & Jalur (Rekomendasi)</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Gunakan ini untuk reset seleksi jalur tertentu saja, tanpa mengganggu jalur lainnya.
                                </p>
                                <form method="POST" onsubmit="return confirm('Yakin ingin reset seleksi untuk sekolah dan jalur ini?');">
                                    <input type="hidden" name="action" value="reset_sekolah_jalur">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label">Pilih Sekolah</label>
                                            <select class="form-select" name="id_sekolah" required>
                                                <option value="">-- Pilih Sekolah --</option>
                                                <?php 
                                                mysqli_data_seek($sekolah_list, 0);
                                                while ($s = mysqli_fetch_assoc($sekolah_list)): 
                                                ?>
                                                <option value="<?php echo $s['id_sekolah']; ?>">
                                                    <?php echo $s['nama_sekolah']; ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Pilih Jalur</label>
                                            <select class="form-select" name="id_jalur" required>
                                                <option value="">-- Pilih Jalur --</option>
                                                <?php foreach ($list_jalur as $jalur): ?>
                                                <option value="<?php echo $jalur['id_jalur']; ?>">
                                                    <?php echo $jalur['nama_jalur']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-undo me-1"></i> Reset Jalur Ini
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Reset per Sekolah (Semua Jalur) -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">🏫 Reset per Sekolah (Semua Jalur)</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" onsubmit="return confirm('Yakin ingin reset SEMUA hasil seleksi di sekolah ini?');">
                                    <input type="hidden" name="action" value="reset_sekolah">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <select class="form-select" name="id_sekolah" required>
                                                <option value="">-- Pilih Sekolah --</option>
                                                <?php 
                                                mysqli_data_seek($sekolah_list, 0);
                                                while ($s = mysqli_fetch_assoc($sekolah_list)): 
                                                ?>
                                                <option value="<?php echo $s['id_sekolah']; ?>" 
                                                        <?php echo $s['total_hasil'] == 0 ? 'disabled' : ''; ?>>
                                                    <?php echo $s['nama_sekolah']; ?> 
                                                    (<?php echo $s['total_lulus']; ?> lulus / <?php echo $s['total_hasil']; ?> total)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-warning w-100">
                                                <i class="fas fa-undo me-1"></i> Reset Sekolah Ini
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Reset Semua -->
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">⚠️ Reset SEMUA Seleksi</h5>
                            </div>
                            <div class="card-body">
                                <p>Total hasil seleksi saat ini: <strong><?php echo $total_hasil; ?></strong> data</p>
                                
                                <?php if ($total_hasil > 0): ?>
                                <form method="POST" onsubmit="return confirm('PERINGATAN! Anda akan menghapus SEMUA hasil seleksi. Yakin?');">
                                    <input type="hidden" name="action" value="reset_semua">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash me-1"></i> Reset SEMUA Hasil Seleksi
                                    </button>
                                </form>
                                <?php else: ?>
                                <p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>Tidak ada hasil seleksi untuk direset.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <a href="/ppdb-smp/admin/seleksi/proses.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Proses Seleksi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

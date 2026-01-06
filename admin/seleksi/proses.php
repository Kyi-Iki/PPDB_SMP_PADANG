<?php
/**
 * File: admin/seleksi/proses.php
 * Deskripsi: Proses seleksi otomatis PPDB dengan algoritma per jalur (Dual Choice Support)
 * 
 * ALGORITMA SELEKSI (DUAL CHOICE):
 * 1. TAHAP 1: Proses semua Pilihan 1.
 * 2. TAHAP 2: Proses Pilihan 2 bagi yang gagal di Tahap 1, memperebutkan sisa kuota.
 */

session_start();
require_once '../../config/database.php';
require_once 'fungsi_seleksi.php'; // Load logic function

check_admin();
check_super_admin(); // Only super_admin can run selection

$error = '';
$success = '';
$log_proses = array();
$tahun_ajaran = TAHUN_AJARAN;

// Cek status PPDB - harus dalam periode "seleksi"
$status_ppdb = get_status_ppdb();
$seleksi_diizinkan = ($status_ppdb == 'seleksi');

// ==========================================
// HANDLE REQUEST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_seleksi_global'])) {
    
    // Validasi: hanya bisa proses saat status "seleksi"
    if (!$seleksi_diizinkan) {
        $error = "Proses seleksi hanya dapat dilakukan saat status PPDB adalah <strong>Seleksi</strong>. Status saat ini: <strong>" . ucfirst($status_ppdb) . "</strong>. Silakan ubah status di menu Pengaturan.";
    } else {
        $id_jalur = (int)clean_input($_POST['id_jalur']);
        
        // Ubah logic: Proses SEMUA SEKOLAH dalam jalur ini
        mysqli_begin_transaction($conn);
        try {
            // Ambil semua sekolah aktif untuk tahun ajaran ini
            $sekolah_res = mysqli_query($conn, "SELECT id_sekolah, nama_sekolah FROM sekolah WHERE status='aktif' AND tahun_ajaran='$tahun_ajaran'");
            
            while ($sek = mysqli_fetch_assoc($sekolah_res)) {
                // Ambil kuota sekolah utk jalur ini
                $q_kuota = mysqli_query($conn, "SELECT jumlah_kuota, id_kuota FROM kuota_jalur WHERE id_sekolah={$sek['id_sekolah']} AND id_jalur=$id_jalur AND tahun_ajaran='$tahun_ajaran'");
                $d_kuota = mysqli_fetch_assoc($q_kuota);
                
                if ($d_kuota) {
                    $log_proses[] = "=== Memproses Sekolah: {$sek['nama_sekolah']} ===";
                    $lulus = proses_seleksi_sekolah($conn, $sek['id_sekolah'], $id_jalur, $d_kuota['jumlah_kuota'], $tahun_ajaran);
                    
                    // Update penggunaan kuota
                    mysqli_query($conn, "UPDATE kuota_jalur SET kuota_terpakai=$lulus WHERE id_kuota={$d_kuota['id_kuota']}");
                }
            }
            
            mysqli_commit($conn);
            $success = "Seleksi Global Selesai!";
            
            // Log aktivitas
            log_aktivitas($_SESSION['id_user'], 'Proses Seleksi', "Proses seleksi jalur ID: $id_jalur");
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Data for UI
$list_jalur = get_semua_jadwal_jalur();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Proses Seleksi Global - Admin PPDB</title>
    <?php include '../../includes/head_admin.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar_admin.php'; ?>
    <div class="container mt-4">
        
        <!-- Back Button -->
        <div class="mb-3">
            <a href="/ppdb-smp/admin/index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
        </div>
        
        <div class="card shadow">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0"><i class="fas fa-bolt me-2"></i>Seleksi Otomatis (Global Jalur)</h4>
            </div>
            <div class="card-body">
                
                <?php if (!$seleksi_diizinkan): ?>
                <!-- Warning: Status bukan seleksi -->
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Proses Seleksi Tidak Tersedia</h5>
                    <p class="mb-2">Proses seleksi hanya dapat dilakukan saat status PPDB adalah <strong>Seleksi</strong>.</p>
                    <p class="mb-2">Status saat ini: <strong class="text-primary"><?php echo ucfirst($status_ppdb); ?></strong></p>
                    <hr>
                    <a href="/ppdb-smp/admin/pengaturan.php" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i>Ubah Status di Pengaturan
                    </a>
                </div>
                <?php else: ?>
                
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Status PPDB: <strong class="text-success">Seleksi</strong> - Proses seleksi dapat dilakukan.
                </div>
                
                <form method="POST" onsubmit="return confirm('Yakin ingin menjalankan proses seleksi? Proses ini akan menentukan kelulusan siswa.');">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Jalur yang akan diproses:</label>
                        <select name="id_jalur" class="form-select form-select-lg" required>
                            <?php while($j = mysqli_fetch_assoc($list_jalur)): ?>
                            <option value="<?php echo $j['id_jalur']; ?>"><?php echo $j['nama_jalur']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="proses_seleksi_global" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-play-circle me-2"></i>Mulai Seleksi Global
                    </button>
                    <div class="text-danger mt-2">
                        <small><i class="fas fa-exclamation-circle me-1"></i>Proses ini akan menjalankan seleksi untuk SELURUH SEKOLAH sekaligus demi keadilan Pilihan 1 & 2.</small>
                    </div>
                </form>
                
                <?php endif; ?>

                <?php if (!empty($log_proses)): ?>
                    <hr>
                    <h6><i class="fas fa-terminal me-2"></i>Log Proses:</h6>
                    <div class="bg-dark text-success p-3 rounded" style="max-height:400px; overflow-y:auto; font-family: monospace;">
                        <pre class="mb-0"><?php echo implode("\n", $log_proses); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include '../../includes/scripts_admin.php'; ?>
</body>
</html>

<?php
/**
 * File: admin/jadwal_jalur.php
 * Deskripsi: Halaman pengaturan jadwal per jalur PPDB
 */

session_start();
require_once '../config/database.php';

check_admin();

$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';

// Proses update jadwal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update tanggal SERENTAK untuk semua jalur
    if (isset($_POST['update_tanggal_serentak'])) {
        $tanggal_buka = clean_input($_POST['tanggal_buka_serentak']);
        $tanggal_tutup = clean_input($_POST['tanggal_tutup_serentak']);
        $tanggal_seleksi = clean_input($_POST['tanggal_seleksi_serentak']);
        $tanggal_pengumuman = clean_input($_POST['tanggal_pengumuman_serentak']);
        
        $sql = "UPDATE jadwal_jalur SET 
                tanggal_buka = ?, 
                tanggal_tutup = ?, 
                tanggal_seleksi = ?,
                tanggal_pengumuman = ?
                WHERE tahun_ajaran = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", 
            $tanggal_buka, $tanggal_tutup, $tanggal_seleksi, 
            $tanggal_pengumuman, $tahun_ajaran);
        
        if (mysqli_stmt_execute($stmt)) {
            log_aktivitas($_SESSION['id_user'], 'Update Jadwal Serentak', "Semua jalur: Buka=$tanggal_buka, Tutup=$tanggal_tutup");
            $success = "Tanggal semua jalur berhasil diperbarui secara serentak!";
        } else {
            $error = "Gagal memperbarui jadwal.";
        }
    }
    
    // Update status dan urutan per jalur (tanpa tanggal)
    if (isset($_POST['update_jalur'])) {
        $id_jadwal = clean_input($_POST['id_jadwal']);
        $status = clean_input($_POST['status']);
        $urutan = clean_input($_POST['urutan']);
        
        $sql = "UPDATE jadwal_jalur SET status = ?, urutan = ? WHERE id_jadwal = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $status, $urutan, $id_jadwal);
        
        if (mysqli_stmt_execute($stmt)) {
            log_aktivitas($_SESSION['id_user'], 'Update Jalur', "Jalur ID: $id_jadwal - Status: $status, Urutan: $urutan");
            $success = "Pengaturan jalur berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui.";
        }
    }
    
    // Update status massal
    if (isset($_POST['update_status_massal'])) {
        $new_status = clean_input($_POST['status_massal']);
        
        $sql = "UPDATE jadwal_jalur SET status = ? WHERE tahun_ajaran = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $new_status, $tahun_ajaran);
        
        if (mysqli_stmt_execute($stmt)) {
            log_aktivitas($_SESSION['id_user'], 'Update Status Massal', "Status semua jalur diubah ke: $new_status");
            $success = "Status semua jalur berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui status.";
        }
    }
}

// Ambil data jadwal jalur
$sql_jadwal = "SELECT jj.*, jp.nama_jalur, jp.kode_jalur,
               (SELECT COUNT(*) FROM pendaftaran p WHERE p.id_jalur = jj.id_jalur AND p.tahun_ajaran = jj.tahun_ajaran) as total_pendaftar
               FROM jadwal_jalur jj
               JOIN jalur_ppdb jp ON jj.id_jalur = jp.id_jalur
               WHERE jj.tahun_ajaran = ?
               ORDER BY jj.urutan ASC";
$stmt_jadwal = mysqli_prepare($conn, $sql_jadwal);
mysqli_stmt_bind_param($stmt_jadwal, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt_jadwal);
$jadwal_list = mysqli_stmt_get_result($stmt_jadwal);

$status_options = [
    'belum_dibuka' => ['label' => 'Belum Dibuka', 'class' => 'secondary', 'icon' => '🔒'],
    'pendaftaran' => ['label' => 'Pendaftaran', 'class' => 'success', 'icon' => '📝'],
    'seleksi' => ['label' => 'Seleksi', 'class' => 'warning', 'icon' => '⚙️'],
    'pengumuman' => ['label' => 'Pengumuman', 'class' => 'info', 'icon' => '📢'],
    'daftar_ulang' => ['label' => 'Daftar Ulang', 'class' => 'primary', 'icon' => '✅'],
    'selesai' => ['label' => 'Selesai', 'class' => 'dark', 'icon' => '🏁']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Jadwal Jalur PPDB - Admin</title>
    <?php include '../includes/head_admin.php'; ?>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>
    
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/sidebar_admin.php'; ?>
            </div>
            
            <div class="col-md-9">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Jadwal Pendaftaran Per Jalur
                        </h4>
                        <span class="badge bg-light text-dark">Tahun Ajaran: <?php echo $tahun_ajaran; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Info Penting SISTEM SERENTAK -->
                        <div class="alert alert-info mb-4">
                            <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-2"></i>Sistem Pendaftaran SERENTAK:</h6>
                            <ul class="mb-0">
                                <li><strong>Semua jalur dibuka bersamaan</strong> dalam satu periode pendaftaran</li>
                                <li>Siswa hanya boleh memilih <strong>1 jalur</strong> untuk seluruh periode</li>
                                <li>Dalam 1 jalur, siswa bisa memilih <strong>2 sekolah</strong> (Pilihan 1 & 2)</li>
                                <li>Jalur Afirmasi memerlukan dokumen pendukung (DTKS/SKTM/dll)</li>
                            </ul>
                        </div>

                        <!-- Form Tanggal SERENTAK (Semua Jalur) -->
                        <?php 
                        // Ambil tanggal dari jalur pertama (karena serentak, semua sama)
                        mysqli_data_seek($jadwal_list, 0);
                        $first_jadwal = mysqli_fetch_assoc($jadwal_list);
                        ?>
                        <div class="card border-primary mb-4">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Atur Tanggal Serentak (Semua Jalur)</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">📅 Tanggal Buka</label>
                                            <input type="date" name="tanggal_buka_serentak" class="form-control" 
                                                   value="<?php echo $first_jadwal['tanggal_buka'] ?? ''; ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">🚪 Tanggal Tutup</label>
                                            <input type="date" name="tanggal_tutup_serentak" class="form-control" 
                                                   value="<?php echo $first_jadwal['tanggal_tutup'] ?? ''; ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">⚙️ Seleksi</label>
                                            <input type="date" name="tanggal_seleksi_serentak" class="form-control" 
                                                   value="<?php echo $first_jadwal['tanggal_seleksi'] ?? ''; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">📢 Pengumuman</label>
                                            <input type="date" name="tanggal_pengumuman_serentak" class="form-control" 
                                                   value="<?php echo $first_jadwal['tanggal_pengumuman'] ?? ''; ?>">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" name="update_tanggal_serentak" class="btn btn-primary w-100">
                                                <i class="fas fa-save me-1"></i>Simpan Semua
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Tanggal ini akan diterapkan ke <strong>semua jalur</strong> secara serentak.
                                        </small>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Info Status dari Pengaturan Global -->
                        <div class="alert alert-secondary mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-sync-alt fa-2x me-3 text-primary"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold">Status Jalur Otomatis Sinkron</h6>
                                    <small class="mb-0">
                                        Status semua jalur akan otomatis mengikuti <strong>Status PPDB Global</strong> yang diatur di 
                                        <a href="pengaturan.php" class="alert-link">Pengaturan</a>.
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Daftar Jalur (Hanya Status & Urutan) -->
                        <h6 class="text-muted mb-3"><i class="fas fa-list me-2"></i>Pengaturan Status & Urutan Per Jalur</h6>
                        <?php 
                        mysqli_data_seek($jadwal_list, 0);
                        while ($jadwal = mysqli_fetch_assoc($jadwal_list)): 
                            $status_info = $status_options[$jadwal['status']] ?? $status_options['belum_dibuka'];
                        ?>
                        <div class="card mb-2 border-start border-3 border-<?php echo $status_info['class']; ?>">
                            <div class="card-body py-2">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <h6 class="mb-0 fw-bold">
                                            <?php echo $jadwal['nama_jalur']; ?>
                                            <small class="text-muted fw-normal ms-2">
                                                (<?php echo $jadwal['kode_jalur']; ?>)
                                            </small>
                                        </h6>
                                        <small class="text-muted">
                                            Pendaftar: <strong><?php echo $jadwal['total_pendaftar']; ?></strong> siswa
                                        </small>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <span class="badge bg-<?php echo $status_info['class']; ?> px-3 py-2">
                                            <?php echo $status_info['icon']; ?> <?php echo $status_info['label']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <form method="POST" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="id_jadwal" value="<?php echo $jadwal['id_jadwal']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $jadwal['status']; ?>">
                                            <div class="input-group input-group-sm" style="width: 120px;">
                                                <span class="input-group-text">Urutan</span>
                                                <input type="number" name="urutan" class="form-control" 
                                                       value="<?php echo $jadwal['urutan']; ?>" min="1" max="10">
                                            </div>
                                            <button type="submit" name="update_jalur" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        
                        <?php if (mysqli_num_rows($jadwal_list) == 0): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h5>Jadwal Jalur Belum Diatur</h5>
                            <p class="mb-3">Silakan jalankan script SQL untuk membuat data jadwal jalur.</p>
                            <code>database/create_jadwal_jalur.sql</code>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/scripts_admin.php'; ?>
</body>
</html>

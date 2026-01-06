<?php
/**
 * File: admin/kuota/index.php
 * Deskripsi: Pengaturan kuota per jalur untuk setiap sekolah
 * 
 * Access:
 * - Super Admin: Full CRUD access
 * - Operator: View-only access for their school
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/csrf.php';

check_admin(); // Allow both super_admin and operator

// Determine if view-only mode (operator = view only)
$view_only = is_operator();

$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';

// Ambil ID sekolah dari parameter
$id_sekolah = isset($_GET['id_sekolah']) ? (int)$_GET['id_sekolah'] : 0;

// For operator, force to their own school
if (is_operator()) {
    $operator_school = get_operator_sekolah();
    if ($id_sekolah == 0) {
        $id_sekolah = $operator_school;
    } else if ($id_sekolah != $operator_school) {
        // Operator trying to access other school's quota
        header("Location: index.php?id_sekolah=" . $operator_school);
        exit();
    }
}

// Ambil data sekolah
if ($id_sekolah) {
    $sql_sekolah = "SELECT * FROM sekolah WHERE id_sekolah = ?";
    $stmt = mysqli_prepare($conn, $sql_sekolah);
    mysqli_stmt_bind_param($stmt, "i", $id_sekolah);
    mysqli_stmt_execute($stmt);
    $sekolah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
} else {
    $sekolah = null;
}

// Proses update kuota total sekolah (super_admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_kuota_total']) && $id_sekolah && is_super_admin()) {
    // Verify CSRF
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan coba lagi.";
    } else {
    $kuota_total_baru = (int)$_POST['kuota_total_baru'];
    
    if ($kuota_total_baru < 1) {
        $error = "Kuota total harus minimal 1!";
    } else {
        $sql_update_total = "UPDATE sekolah SET kuota_total = ? WHERE id_sekolah = ?";
        $stmt = mysqli_prepare($conn, $sql_update_total);
        mysqli_stmt_bind_param($stmt, "ii", $kuota_total_baru, $id_sekolah);
        
        if (mysqli_stmt_execute($stmt)) {
            log_aktivitas($_SESSION['id_user'], 'Update Kuota Total', "Update kuota total sekolah ID: $id_sekolah menjadi $kuota_total_baru");
            $success = "Kuota total berhasil diperbarui menjadi $kuota_total_baru!";
            // Refresh data sekolah
            $sekolah['kuota_total'] = $kuota_total_baru;
        } else {
            $error = "Gagal mengupdate kuota total!";
        }
    }
    } // End CSRF else
}

// Proses update distribusi kuota per jalur (super_admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_distribusi']) && $id_sekolah && is_super_admin()) {
    // Verify CSRF
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan coba lagi.";
    } else {
    $kuota_zonasi = (int)$_POST['kuota_zonasi'];
    $kuota_prestasi = (int)$_POST['kuota_prestasi'];
    $kuota_afirmasi = (int)$_POST['kuota_afirmasi'];
    $kuota_mutasi = (int)$_POST['kuota_mutasi'];
    
    $total_kuota = $kuota_zonasi + $kuota_prestasi + $kuota_afirmasi + $kuota_mutasi;
    
    if ($total_kuota != $sekolah['kuota_total']) {
        $error = "Total kuota ($total_kuota) tidak sesuai dengan kuota sekolah ({$sekolah['kuota_total']})!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            // Update/Insert kuota untuk setiap jalur
            // Persentase Kota Padang: Zonasi 45%, Afirmasi 23%, Prestasi 27%, Mutasi 5%
            $jalur_data = [
                1 => $kuota_zonasi,   // Zonasi
                2 => $kuota_prestasi, // Prestasi
                3 => $kuota_afirmasi, // Afirmasi
                4 => $kuota_mutasi    // Mutasi
            ];
            
            foreach ($jalur_data as $id_jalur => $jumlah_kuota) {
                $sql_upsert = "INSERT INTO kuota_jalur (id_sekolah, id_jalur, jumlah_kuota, tahun_ajaran)
                              VALUES (?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE jumlah_kuota = VALUES(jumlah_kuota)";
                
                $stmt = mysqli_prepare($conn, $sql_upsert);
                mysqli_stmt_bind_param($stmt, "iiis", $id_sekolah, $id_jalur, $jumlah_kuota, $tahun_ajaran);
                mysqli_stmt_execute($stmt);
            }
            
            mysqli_commit($conn);
            
            log_aktivitas($_SESSION['id_user'], 'Update Kuota', "Update kuota sekolah: {$sekolah['nama_sekolah']}");
            
            $success = "Distribusi kuota berhasil diperbarui!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Gagal memperbarui kuota: " . $e->getMessage();
        }
    }
    } // End CSRF else
}

// Ambil kuota existing
$kuota_existing = [];
if ($id_sekolah) {
    $sql_kuota = "SELECT kj.*, j.nama_jalur FROM kuota_jalur kj
                  JOIN jalur_ppdb j ON kj.id_jalur = j.id_jalur
                  WHERE kj.id_sekolah = ? AND kj.tahun_ajaran = ?";
    $stmt = mysqli_prepare($conn, $sql_kuota);
    mysqli_stmt_bind_param($stmt, "is", $id_sekolah, $tahun_ajaran);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $kuota_existing[$row['id_jalur']] = $row;
    }
}

// Ambil daftar semua sekolah
$sql_list = "SELECT * FROM sekolah WHERE status = 'aktif' ORDER BY nama_sekolah";
$sekolah_list = mysqli_query($conn, $sql_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Kuota - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        body {
            background: #f5f7fa;
        }
        .kuota-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .jalur-input {
            background: #f8f9fa;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .jalur-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .progress-bar-custom {
            height: 25px;
            border-radius: 10px;
            font-weight: 600;
        }
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
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-percentage me-2"></i>
                            <?php echo $view_only ? 'Kuota Jalur Sekolah' : 'Pengaturan Kuota Jalur PPDB'; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($view_only): ?>
                        <!-- Operator: Show static school name -->
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Data Kuota Sekolah:</strong> <?php echo $sekolah['nama_sekolah'] ?? 'Tidak ditemukan'; ?>
                            <br><small class="text-muted">Mode hanya lihat - Anda tidak dapat mengubah data kuota.</small>
                        </div>
                        <?php else: ?>
                        <!-- Super Admin: Pilih Sekolah -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Pilih Sekolah:</label>
                            <select class="form-select" onchange="window.location.href='?id_sekolah=' + this.value">
                                <option value="">-- Pilih Sekolah --</option>
                                <?php while ($sch = mysqli_fetch_assoc($sekolah_list)): ?>
                                <option value="<?php echo $sch['id_sekolah']; ?>" 
                                        <?php echo ($id_sekolah == $sch['id_sekolah']) ? 'selected' : ''; ?>>
                                    <?php echo $sch['nama_sekolah']; ?> (Kuota: <?php echo $sch['kuota_total']; ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($sekolah): ?>
                        <!-- Info Sekolah + Form Edit Kuota Total -->
                        <div class="kuota-card">
                            <h5 class="fw-bold mb-3"><?php echo $sekolah['nama_sekolah']; ?></h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>NPSN:</strong> <?php echo $sekolah['npsn']; ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Alamat:</strong> <?php echo $sekolah['alamat_sekolah']; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <?php if (!$view_only): ?>
                                    <!-- Super Admin: Editable kuota total -->
                                    <form method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                        <?php echo csrf_input(); ?>
                                        <label class="form-label mb-0 fw-bold">Kuota Total:</label>
                                        <input type="number" name="kuota_total_baru" 
                                               class="form-control form-control-lg" style="width: 120px;"
                                               value="<?php echo $sekolah['kuota_total']; ?>" min="1" required>
                                        <button type="submit" name="update_kuota_total" class="btn btn-primary">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                    <p class="text-muted text-end mt-1 mb-0"><small>Total kursi tersedia</small></p>
                                    <?php else: ?>
                                    <!-- Operator: Display only kuota total -->
                                    <div class="text-end">
                                        <label class="form-label mb-0 fw-bold">Kuota Total:</label>
                                        <h2 class="text-primary fw-bold mb-0"><?php echo $sekolah['kuota_total']; ?></h2>
                                        <p class="text-muted mt-1 mb-0"><small>Total kursi tersedia</small></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Kuota -->
                        <form method="POST" action="" id="formKuota">
                            <?php echo csrf_input(); ?>
                            <div class="kuota-card">
                                <h6 class="fw-bold mb-3">Distribusi Kuota per Jalur:</h6>
                                
                                <!-- Zonasi -->
                                <div class="jalur-input">
                                    <div class="row align-items-center">
                                        <div class="col-md-1">
                                            <div class="jalur-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                ZON
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <h6 class="fw-bold mb-1">Jalur Zonasi</h6>
                                            <small class="text-muted">Berdasarkan jarak terdekat (45% - Aturan Kota Padang)</small>
                                            <div class="progress mt-2 progress-bar-custom">
                                                <div class="progress-bar bg-primary" id="progress_zonasi" style="width: 0%">0%</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" class="form-control form-control-lg<?php echo $view_only ? ' bg-light' : ''; ?>" 
                                                   name="kuota_zonasi" id="kuota_zonasi"
                                                   value="<?php echo $kuota_existing[1]['jumlah_kuota'] ?? floor($sekolah['kuota_total'] * 0.45); ?>"
                                                   min="0" max="<?php echo $sekolah['kuota_total']; ?>" 
                                                   onchange="updateTotal()" <?php echo $view_only ? 'readonly' : 'required'; ?>>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Prestasi -->
                                <div class="jalur-input">
                                    <div class="row align-items-center">
                                        <div class="col-md-1">
                                            <div class="jalur-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                                PRE
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <h6 class="fw-bold mb-1">Jalur Prestasi</h6>
                                            <small class="text-muted">Berdasarkan nilai rapor tertinggi (27% - Aturan Kota Padang)</small>
                                            <div class="progress mt-2 progress-bar-custom">
                                                <div class="progress-bar bg-success" id="progress_prestasi" style="width: 0%">0%</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" class="form-control form-control-lg<?php echo $view_only ? ' bg-light' : ''; ?>" 
                                                   name="kuota_prestasi" id="kuota_prestasi"
                                                   value="<?php echo $kuota_existing[2]['jumlah_kuota'] ?? floor($sekolah['kuota_total'] * 0.27); ?>"
                                                   min="0" max="<?php echo $sekolah['kuota_total']; ?>" 
                                                   onchange="updateTotal()" <?php echo $view_only ? 'readonly' : 'required'; ?>>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Afirmasi -->
                                <div class="jalur-input">
                                    <div class="row align-items-center">
                                        <div class="col-md-1">
                                            <div class="jalur-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                                AFI
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <h6 class="fw-bold mb-1">Jalur Afirmasi</h6>
                                            <small class="text-muted">Untuk siswa kurang mampu (23% - Aturan Kota Padang)</small>
                                            <div class="progress mt-2 progress-bar-custom">
                                                <div class="progress-bar bg-warning" id="progress_afirmasi" style="width: 0%">0%</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" class="form-control form-control-lg<?php echo $view_only ? ' bg-light' : ''; ?>" 
                                                   name="kuota_afirmasi" id="kuota_afirmasi"
                                                   value="<?php echo $kuota_existing[3]['jumlah_kuota'] ?? floor($sekolah['kuota_total'] * 0.23); ?>"
                                                   min="0" max="<?php echo $sekolah['kuota_total']; ?>" 
                                                   onchange="updateTotal()" <?php echo $view_only ? 'readonly' : 'required'; ?>>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mutasi -->
                                <div class="jalur-input">
                                    <div class="row align-items-center">
                                        <div class="col-md-1">
                                            <div class="jalur-icon" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                                                MUT
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <h6 class="fw-bold mb-1">Jalur Mutasi</h6>
                                            <small class="text-muted">Perpindahan orang tua/wali (5%)</small>
                                            <div class="progress mt-2 progress-bar-custom">
                                                <div class="progress-bar bg-danger" id="progress_mutasi" style="width: 0%">0%</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" class="form-control form-control-lg<?php echo $view_only ? ' bg-light' : ''; ?>" 
                                                   name="kuota_mutasi" id="kuota_mutasi"
                                                   value="<?php echo $kuota_existing[4]['jumlah_kuota'] ?? floor($sekolah['kuota_total'] * 0.05); ?>"
                                                   min="0" max="<?php echo $sekolah['kuota_total']; ?>" 
                                                   onchange="updateTotal()" <?php echo $view_only ? 'readonly' : 'required'; ?>>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Total -->
                                <div class="alert alert-info mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="fw-bold mb-0">
                                                <i class="fas fa-calculator me-2"></i>
                                                Total Kuota yang Dialokasikan:
                                            </h6>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <h3 class="mb-0">
                                                <span id="total_alokasi">0</span> / <?php echo $sekolah['kuota_total']; ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <a href="../index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                                <?php if (!$view_only): ?>
                                <input type="hidden" name="update_distribusi" value="1">
                                <button type="submit" class="btn btn-primary" id="btnSimpan">
                                    <i class="fas fa-save me-2"></i>Simpan Distribusi Kuota
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-school fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Silakan pilih sekolah terlebih dahulu</h5>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const kuotaTotal = <?php echo $sekolah ? $sekolah['kuota_total'] : 0; ?>;
        
        function updateTotal() {
            const zonasi = parseInt(document.getElementById('kuota_zonasi').value) || 0;
            const prestasi = parseInt(document.getElementById('kuota_prestasi').value) || 0;
            const afirmasi = parseInt(document.getElementById('kuota_afirmasi').value) || 0;
            const mutasi = parseInt(document.getElementById('kuota_mutasi').value) || 0;
            
            const total = zonasi + prestasi + afirmasi + mutasi;
            
            document.getElementById('total_alokasi').textContent = total;
            
            // Update progress bars
            updateProgress('progress_zonasi', zonasi);
            updateProgress('progress_prestasi', prestasi);
            updateProgress('progress_afirmasi', afirmasi);
            updateProgress('progress_mutasi', mutasi);
            
            // Validasi
            const btnSimpan = document.getElementById('btnSimpan');
            if (total !== kuotaTotal) {
                btnSimpan.disabled = true;
                btnSimpan.textContent = `❌ Total kuota harus ${kuotaTotal}`;
            } else {
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Kuota';
            }
        }
        
        function updateProgress(id, value) {
            const percentage = (value / kuotaTotal * 100).toFixed(1);
            const elem = document.getElementById(id);
            elem.style.width = percentage + '%';
            elem.textContent = percentage + '%';
        }
        
        // Initial update
        updateTotal();
    </script>
</body>
</html>
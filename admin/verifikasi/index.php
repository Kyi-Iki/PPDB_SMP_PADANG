<?php
/**
 * File: admin/verifikasi/index.php
 * Deskripsi: Verifikasi dokumen dan pendaftaran siswa
 */

session_start();
require_once '../../config/database.php';

check_admin();

$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';

// Proses verifikasi
if (isset($_POST['verify'])) {
    $id_pendaftaran = (int)$_POST['id_pendaftaran'];
    $status = clean_input($_POST['status']);
    $catatan = clean_input($_POST['catatan']);
    
    // Update status pendaftaran
    $sql_update = "UPDATE pendaftaran SET 
                   status_verifikasi = ?, 
                   catatan_verifikasi = ?,
                   verifikasi_at = NOW(),
                   verifikasi_by = ?
                   WHERE id_pendaftaran = ?";
    
    $stmt = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt, "ssii", $status, $catatan, $_SESSION['id_user'], $id_pendaftaran);
    
    if (mysqli_stmt_execute($stmt)) {
        // Update status dokumen di tabel dokumen_siswa
        if (isset($_POST['doc_count'])) {
            $doc_count = (int)$_POST['doc_count'];
            
            for ($i = 0; $i < $doc_count; $i++) {
                if (isset($_POST["doc_id_$i"]) && isset($_POST["doc_status_$i"])) {
                    $id_dok = (int)$_POST["doc_id_$i"];
                    $status_dok = mysqli_real_escape_string($conn, trim($_POST["doc_status_$i"]));
                    $catatan_dok = isset($_POST["doc_catatan_$i"]) ? mysqli_real_escape_string($conn, trim($_POST["doc_catatan_$i"])) : '';
                    
                    $sql_doc = "UPDATE dokumen_siswa SET status_verifikasi = ?, catatan = ?, verified_at = NOW(), verified_by = ? WHERE id_dokumen_siswa = ?";
                    $stmt_doc = mysqli_prepare($conn, $sql_doc);
                    mysqli_stmt_bind_param($stmt_doc, "ssii", $status_dok, $catatan_dok, $_SESSION['id_user'], $id_dok);
                    mysqli_stmt_execute($stmt_doc);
                }
            }
        }
        
        log_aktivitas($_SESSION['id_user'], 'Verifikasi', "Verifikasi pendaftaran ID: $id_pendaftaran - Status: $status");
        $success = "Verifikasi pendaftaran dan dokumen berhasil disimpan!";
    } else {
        $error = "Gagal melakukan verifikasi!";
    }
}

// Proses verifikasi dokumen individual
if (isset($_POST['verify_doc'])) {
    $id_dokumen = (int)$_POST['id_dokumen'];
    $status_doc = clean_input($_POST['status_doc']);
    $catatan_doc = clean_input($_POST['catatan_doc']);
    
    $sql_update_doc = "UPDATE dokumen_siswa SET status_verifikasi = ?, catatan = ?, verified_at = NOW(), verified_by = ? WHERE id_dokumen_siswa = ?";
    $stmt_doc = mysqli_prepare($conn, $sql_update_doc);
    mysqli_stmt_bind_param($stmt_doc, "ssii", $status_doc, $catatan_doc, $_SESSION['id_user'], $id_dokumen);
    mysqli_stmt_execute($stmt_doc);
    
    $success = "Status dokumen berhasil diupdate!";
}

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$filter_jalur = isset($_GET['jalur']) ? (int)$_GET['jalur'] : 0;

// Get operator's school filter
$operator_sekolah = get_sekolah_filter();

// Query pendaftar dengan jumlah dokumen dari dokumen_siswa
$sql = "SELECT p.*, s.nama_lengkap, s.nisn, s.no_telepon, s.id_siswa,
        sch.nama_sekolah, j.nama_jalur,
        (SELECT COUNT(*) FROM dokumen_siswa WHERE id_siswa = s.id_siswa) as jumlah_dokumen
        FROM pendaftaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
        JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
        WHERE p.tahun_ajaran = ? ";

// Add operator school filter
if ($operator_sekolah) {
    $sql .= "AND p.id_sekolah = $operator_sekolah ";
}

if ($filter_status) {
    $sql .= "AND p.status_verifikasi = '$filter_status' ";
}
if ($filter_jalur) {
    $sql .= "AND p.id_jalur = $filter_jalur ";
}

$sql .= "ORDER BY p.tanggal_daftar DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt);
$pendaftar_list = mysqli_stmt_get_result($stmt);

// Statistik - also filtered by school for operators
if ($operator_sekolah) {
    $sql_stats = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status_verifikasi = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
        SUM(CASE WHEN status_verifikasi = 'ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pendaftaran WHERE tahun_ajaran = ? AND id_sekolah = ?";
    $stmt_stats = mysqli_prepare($conn, $sql_stats);
    mysqli_stmt_bind_param($stmt_stats, "si", $tahun_ajaran, $operator_sekolah);
} else {
    $sql_stats = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status_verifikasi = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
        SUM(CASE WHEN status_verifikasi = 'ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pendaftaran WHERE tahun_ajaran = ?";
    $stmt_stats = mysqli_prepare($conn, $sql_stats);
    mysqli_stmt_bind_param($stmt_stats, "s", $tahun_ajaran);
}
mysqli_stmt_execute($stmt_stats);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pendaftar - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        .pendaftar-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .pendaftar-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
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
                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h3 class="text-primary mb-0"><?php echo $stats['total']; ?></h3>
                                <small class="text-muted">Total Pendaftar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h3 class="text-warning mb-0"><?php echo $stats['pending']; ?></h3>
                                <small class="text-muted">Menunggu</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h3 class="text-success mb-0"><?php echo $stats['terverifikasi']; ?></h3>
                                <small class="text-muted">Terverifikasi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h3 class="text-danger mb-0"><?php echo $stats['ditolak']; ?></h3>
                                <small class="text-muted">Ditolak</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-check-double me-2"></i>
                            Verifikasi Pendaftar
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <!-- Filter -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <select class="form-select" onchange="window.location.href='?status=' + this.value + '&jalur=<?php echo $filter_jalur; ?>'">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="terverifikasi" <?php echo $filter_status == 'terverifikasi' ? 'selected' : ''; ?>>Terverifikasi</option>
                                    <option value="ditolak" <?php echo $filter_status == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" onchange="window.location.href='?status=<?php echo $filter_status; ?>&jalur=' + this.value">
                                    <option value="0">Semua Jalur</option>
                                    <option value="1" <?php echo $filter_jalur == 1 ? 'selected' : ''; ?>>Zonasi</option>
                                    <option value="2" <?php echo $filter_jalur == 2 ? 'selected' : ''; ?>>Prestasi</option>
                                    <option value="3" <?php echo $filter_jalur == 3 ? 'selected' : ''; ?>>Afirmasi</option>
                                    <option value="4" <?php echo $filter_jalur == 4 ? 'selected' : ''; ?>>Mutasi</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- List Pendaftar -->
                        <?php while ($p = mysqli_fetch_assoc($pendaftar_list)): ?>
                        <div class="pendaftar-card">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($p['nama_lengkap'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <h6 class="fw-bold mb-1"><?php echo $p['nama_lengkap']; ?></h6>
                                    <p class="mb-1 small text-muted">
                                        <i class="fas fa-id-card me-1"></i> NISN: <?php echo $p['nisn']; ?>
                                    </p>
                                    <p class="mb-1 small">
                                        <i class="fas fa-school me-1"></i> <?php echo $p['nama_sekolah']; ?>
                                    </p>
                                    <span class="badge bg-info"><?php echo $p['nama_jalur']; ?></span>
                                </div>
                                <div class="col-md-3 text-center">
                                    <small class="text-muted d-block">Dokumen</small>
                                    <h5 class="mb-0"><?php echo $p['jumlah_dokumen']; ?>/4</h5>
                                    <span class="badge bg-<?php echo $p['status_berkas'] == 'lengkap' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($p['status_berkas']); ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <span class="badge bg-<?php 
                                        echo $p['status_verifikasi'] == 'terverifikasi' ? 'success' : 
                                            ($p['status_verifikasi'] == 'ditolak' ? 'danger' : 'warning'); 
                                    ?> mb-2">
                                        <?php echo ucfirst($p['status_verifikasi']); ?>
                                    </span>
                                    <div>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="showDetail(<?php echo $p['id_pendaftaran']; ?>)">
                                            <i class="fas fa-eye me-1"></i> Detail
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pendaftaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        
        function showDetail(id) {
            detailModal.show();
            fetch('detail_verifikasi.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalContent').innerHTML = html;
                });
        }
        
    </script>
</body>
</html>
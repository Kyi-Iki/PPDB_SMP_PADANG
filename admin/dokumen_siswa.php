<?php
/**
 * File: admin/dokumen_siswa.php
 * Deskripsi: Verifikasi dokumen siswa (per kategori: umum, afirmasi, prestasi, mutasi)
 * Menggunakan tabel dokumen_siswa (terpisah dari pendaftaran)
 */

session_start();
require_once '../config/database.php';
require_once '../config/dokumen_config.php';

check_admin();

$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';

// Proses verifikasi dokumen
if (isset($_POST['verify_doc'])) {
    $id_dokumen_siswa = (int)$_POST['id_dokumen_siswa'];
    $status_doc = clean_input($_POST['status_doc']);
    $catatan_doc = clean_input($_POST['catatan_doc']);
    
    $sql_update = "UPDATE dokumen_siswa SET 
                   status_verifikasi = ?, 
                   catatan = ?,
                   verified_at = NOW(),
                   verified_by = ?
                   WHERE id_dokumen_siswa = ?";
    $stmt = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt, "ssii", $status_doc, $catatan_doc, $_SESSION['id_user'], $id_dokumen_siswa);
    
    if (mysqli_stmt_execute($stmt)) {
        log_aktivitas($_SESSION['id_user'], 'Verifikasi Dokumen', "Dokumen ID: $id_dokumen_siswa - Status: $status_doc");
        $success = "Dokumen berhasil diverifikasi!";
    } else {
        $error = "Gagal verifikasi dokumen!";
    }
}

// Filter
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Query dokumen siswa
$sql = "SELECT ds.*, s.nama_lengkap, s.nisn, 
        p.nomor_pendaftaran, p.id_jalur, j.nama_jalur
        FROM dokumen_siswa ds
        JOIN siswa s ON ds.id_siswa = s.id_siswa
        LEFT JOIN pendaftaran p ON s.id_siswa = p.id_siswa AND p.tahun_ajaran = ?
        LEFT JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
        WHERE 1=1 ";

$params = [$tahun_ajaran];
$types = "s";

if ($filter_kategori) {
    $sql .= "AND ds.kategori = ? ";
    $params[] = $filter_kategori;
    $types .= "s";
}
if ($filter_status) {
    $sql .= "AND ds.status_verifikasi = ? ";
    $params[] = $filter_status;
    $types .= "s";
}

// Filter pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $sql .= "AND (s.nama_lengkap LIKE ? OR s.nisn LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

$sql .= "ORDER BY ds.uploaded_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$dokumen_list = mysqli_stmt_get_result($stmt);

// Statistik
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status_verifikasi = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status_verifikasi = 'valid' THEN 1 ELSE 0 END) as valid,
    SUM(CASE WHEN status_verifikasi = 'tidak_valid' THEN 1 ELSE 0 END) as tidak_valid
    FROM dokumen_siswa";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $sql_stats));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen Siswa - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        .doc-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .doc-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .doc-preview {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .kategori-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>
    
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/sidebar_admin.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h3 class="text-primary mb-0"><?php echo $stats['total']; ?></h3>
                                <small class="text-muted">Total Dokumen</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h3 class="text-warning mb-0"><?php echo $stats['pending']; ?></h3>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h3 class="text-success mb-0"><?php echo $stats['valid']; ?></h3>
                                <small class="text-muted">Valid</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h3 class="text-danger mb-0"><?php echo $stats['tidak_valid']; ?></h3>
                                <small class="text-muted">Tidak Valid</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-check me-2"></i>
                            Verifikasi Dokumen Siswa
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <!-- Filter + Pencarian -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" 
                                           placeholder="Cari nama atau NISN..." 
                                           value="<?php echo htmlspecialchars($search ?? ''); ?>"
                                           onkeypress="if(event.key==='Enter') applySearch()">
                                    <button class="btn btn-primary" type="button" onclick="applySearch()">Cari</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" onchange="applyFilter('kategori', this.value)">
                                    <option value="">Semua Kategori</option>
                                    <option value="umum" <?php echo ($filter_kategori ?? '') == 'umum' ? 'selected' : ''; ?>>📄 Umum</option>
                                    <option value="afirmasi" <?php echo ($filter_kategori ?? '') == 'afirmasi' ? 'selected' : ''; ?>>💚 Afirmasi</option>
                                    <option value="prestasi" <?php echo ($filter_kategori ?? '') == 'prestasi' ? 'selected' : ''; ?>>🏆 Prestasi</option>
                                    <option value="mutasi" <?php echo ($filter_kategori ?? '') == 'mutasi' ? 'selected' : ''; ?>>🚗 Mutasi</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" onchange="applyFilter('status', this.value)">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo ($filter_status ?? '') == 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                    <option value="valid" <?php echo ($filter_status ?? '') == 'valid' ? 'selected' : ''; ?>>✅ Valid</option>
                                    <option value="tidak_valid" <?php echo ($filter_status ?? '') == 'tidak_valid' ? 'selected' : ''; ?>>❌ Tidak Valid</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="dokumen_siswa.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                        
                        <!-- List Dokumen -->
                        <?php if (mysqli_num_rows($dokumen_list) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Siswa</th>
                                        <th>Kategori</th>
                                        <th>Jenis</th>
                                        <th>Preview</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($doc = mysqli_fetch_assoc($dokumen_list)): 
                                        $ext = strtolower(pathinfo($doc['nama_file'], PATHINFO_EXTENSION));
                                        $is_image = in_array($ext, ['jpg', 'jpeg', 'png']);
                                        $kategori_class = match($doc['kategori']) {
                                            'umum' => 'primary',
                                            'afirmasi' => 'success',
                                            'prestasi' => 'warning',
                                            'mutasi' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $doc['nama_lengkap']; ?></strong><br>
                                            <small class="text-muted">NISN: <?php echo $doc['nisn']; ?></small>
                                            <?php if ($doc['nama_jalur']): ?>
                                            <br><span class="badge bg-info"><?php echo $doc['nama_jalur']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $kategori_class; ?> kategori-badge">
                                                <?php echo ucfirst($doc['kategori']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo get_label_dokumen($doc['jenis_dokumen']); ?>
                                            <?php if ($doc['tanggal_terbit']): ?>
                                            <br><small class="text-muted">Terbit: <?php echo date('d/m/Y', strtotime($doc['tanggal_terbit'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($is_image): ?>
                                            <a href="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" target="_blank">
                                                <img src="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" 
                                                     style="max-width: 80px; max-height: 60px; object-fit: cover; border-radius: 4px;">
                                            </a>
                                            <?php else: ?>
                                            <a href="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" target="_blank" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-file-pdf me-1"></i>PDF
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $doc['status_verifikasi'] == 'valid' ? 'success' : 
                                                    ($doc['status_verifikasi'] == 'tidak_valid' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $doc['status_verifikasi'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="showVerifyModal(<?php echo htmlspecialchars(json_encode($doc)); ?>)">
                                                <i class="fas fa-check-circle"></i> Verifikasi
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada dokumen dengan filter ini</h5>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Verifikasi -->
    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verifikasi Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_dokumen_siswa" id="modal_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Info Siswa</h6>
                                <p class="mb-1"><strong id="modal_nama"></strong></p>
                                <p class="mb-1 text-muted">NISN: <span id="modal_nisn"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Info Dokumen</h6>
                                <p class="mb-1">Kategori: <span id="modal_kategori" class="badge"></span></p>
                                <p class="mb-1">Jenis: <span id="modal_jenis"></span></p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center mb-3" id="modal_preview"></div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status Verifikasi</label>
                                <select name="status_doc" class="form-select" required>
                                    <option value="pending">⏳ Pending</option>
                                    <option value="valid">✅ Valid</option>
                                    <option value="tidak_valid">❌ Tidak Valid</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Catatan</label>
                                <textarea name="catatan_doc" class="form-control" rows="2" 
                                          placeholder="Catatan untuk siswa..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="verify_doc" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan Verifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const verifyModal = new bootstrap.Modal(document.getElementById('verifyModal'));
        
        function showVerifyModal(doc) {
            document.getElementById('modal_id').value = doc.id_dokumen_siswa;
            document.getElementById('modal_nama').textContent = doc.nama_lengkap;
            document.getElementById('modal_nisn').textContent = doc.nisn;
            document.getElementById('modal_kategori').textContent = doc.kategori.charAt(0).toUpperCase() + doc.kategori.slice(1);
            document.getElementById('modal_kategori').className = 'badge bg-' + getKategoriClass(doc.kategori);
            document.getElementById('modal_jenis').textContent = doc.jenis_dokumen.replace(/_/g, ' ').toUpperCase();
            
            // Preview
            const ext = doc.nama_file.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png'].includes(ext);
            const previewHtml = isImage 
                ? `<a href="../uploads/dokumen/${doc.nama_file}" target="_blank"><img src="../uploads/dokumen/${doc.nama_file}" class="doc-preview"></a>`
                : `<a href="../uploads/dokumen/${doc.nama_file}" target="_blank" class="btn btn-outline-primary"><i class="fas fa-file-pdf me-1"></i>Lihat PDF</a>`;
            document.getElementById('modal_preview').innerHTML = previewHtml;
            
            // Set current status
            document.querySelector('select[name="status_doc"]').value = doc.status_verifikasi;
            document.querySelector('textarea[name="catatan_doc"]').value = doc.catatan || '';
            
            verifyModal.show();
        }
        
        function getKategoriClass(kategori) {
            const classes = {umum: 'primary', afirmasi: 'success', prestasi: 'warning', mutasi: 'danger'};
            return classes[kategori] || 'secondary';
        }
        
        function applyFilter(key, value) {
            const url = new URL(window.location.href);
            if (value) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
            window.location.href = url.toString();
        }
        
        function applySearch() {
            const searchValue = document.getElementById('searchInput').value.trim();
            const url = new URL(window.location.href);
            if (searchValue) {
                url.searchParams.set('search', searchValue);
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }
    </script>
</body>
</html>

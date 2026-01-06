<?php
/**
 * File: siswa/dokumen.php
 * Deskripsi: Upload dokumen siswa dengan batch upload per kategori
 * 
 * ALUR:
 * 1. Siswa upload dokumen UMUM (4 dokumen) dengan 1 tombol
 * 2. Siswa pilih jalur → upload dokumen KHUSUS sesuai jalur dengan 1 tombol
 */

session_start();
require_once '../config/database.php';
require_once '../config/dokumen_config.php';

check_siswa();

$id_siswa = $_SESSION['id_siswa'];
$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';
$upload_results = []; // Track hasil upload per file

// Ambil data siswa
$sql_siswa = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt_siswa = mysqli_prepare($conn, $sql_siswa);
mysqli_stmt_bind_param($stmt_siswa, "i", $id_siswa);
mysqli_stmt_execute($stmt_siswa);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_siswa));

// Cek pendaftaran
$sql_pendaftaran = "SELECT p.*, j.nama_jalur, j.kode_jalur 
                    FROM pendaftaran p 
                    JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
                    WHERE p.id_siswa = ? AND p.tahun_ajaran = ?";
$stmt_pend = mysqli_prepare($conn, $sql_pendaftaran);
mysqli_stmt_bind_param($stmt_pend, "is", $id_siswa, $tahun_ajaran);
mysqli_stmt_execute($stmt_pend);
$pendaftaran = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pend));
$jalur_dipilih = $pendaftaran ? strtolower($pendaftaran['nama_jalur']) : null;

// Fungsi untuk proses batch upload
function processBatchUpload($conn, $id_siswa, $kategori, $files_data, $post_data) {
    $results = [];
    $success_count = 0;
    $error_count = 0;
    
    foreach ($files_data['name'] as $key => $filename) {
        if (empty($filename)) continue; // Skip empty files
        
        $jenis_dokumen = $key;
        $file = [
            'name' => $files_data['name'][$key],
            'type' => $files_data['type'][$key],
            'tmp_name' => $files_data['tmp_name'][$key],
            'error' => $files_data['error'][$key],
            'size' => $files_data['size'][$key]
        ];
        
        // Skip jika tidak ada file
        if ($file['error'] == UPLOAD_ERR_NO_FILE) continue;
        
        // Validasi file
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file['error'] != 0) {
            $results[$jenis_dokumen] = ['success' => false, 'message' => 'Error upload file'];
            $error_count++;
            continue;
        }
        
        if (!in_array($file_ext, $allowed_ext)) {
            $results[$jenis_dokumen] = ['success' => false, 'message' => 'Format file tidak diizinkan'];
            $error_count++;
            continue;
        }
        
        if ($file['size'] > 2097152) { // 2MB
            $results[$jenis_dokumen] = ['success' => false, 'message' => 'Ukuran file maksimal 2MB'];
            $error_count++;
            continue;
        }
        
        // Upload file
        $upload_result = upload_file($file, 'dokumen');
        
        if (!$upload_result['success']) {
            $results[$jenis_dokumen] = ['success' => false, 'message' => 'Gagal upload file'];
            $error_count++;
            continue;
        }
        
        // Get tanggal terbit jika ada
        $tanggal_terbit = isset($post_data['tanggal_terbit'][$jenis_dokumen]) ? $post_data['tanggal_terbit'][$jenis_dokumen] : null;
        
        // Cek apakah sudah ada dokumen
        $sql_check = "SELECT id_dokumen_siswa FROM dokumen_siswa WHERE id_siswa = ? AND jenis_dokumen = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "is", $id_siswa, $jenis_dokumen);
        mysqli_stmt_execute($stmt_check);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check));
        
        if ($existing) {
            // Update existing
            $sql_update = "UPDATE dokumen_siswa SET 
                           nama_file = ?, path_file = ?, ukuran_file = ?, 
                           tanggal_terbit = ?, status_verifikasi = 'pending', 
                           uploaded_at = NOW(), catatan = NULL
                           WHERE id_dokumen_siswa = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "ssisi", 
                $upload_result['filename'], 
                $upload_result['path'], 
                $file['size'],
                $tanggal_terbit,
                $existing['id_dokumen_siswa']);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $results[$jenis_dokumen] = ['success' => true, 'message' => 'Diperbarui'];
                $success_count++;
            } else {
                $results[$jenis_dokumen] = ['success' => false, 'message' => 'Gagal update database'];
                $error_count++;
            }
        } else {
            // Insert new
            $sql_insert = "INSERT INTO dokumen_siswa 
                           (id_siswa, kategori, jenis_dokumen, nama_file, path_file, ukuran_file, tanggal_terbit)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "issssss", 
                $id_siswa, $kategori, $jenis_dokumen,
                $upload_result['filename'], $upload_result['path'], $file['size'], $tanggal_terbit);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $results[$jenis_dokumen] = ['success' => true, 'message' => 'Berhasil diupload'];
                $success_count++;
            } else {
                $results[$jenis_dokumen] = ['success' => false, 'message' => 'Gagal simpan database'];
                $error_count++;
            }
        }
    }
    
    return ['results' => $results, 'success_count' => $success_count, 'error_count' => $error_count];
}

// Proses batch upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['batch_upload'])) {
    $kategori = clean_input($_POST['kategori']);
    
    if (isset($_FILES['docs']) && !empty($_FILES['docs']['name'])) {
        $batch_result = processBatchUpload($conn, $id_siswa, $kategori, $_FILES['docs'], $_POST);
        $upload_results = $batch_result['results'];
        
        if ($batch_result['success_count'] > 0) {
            $success = "{$batch_result['success_count']} dokumen berhasil diupload!";
        }
        if ($batch_result['error_count'] > 0) {
            $error = "{$batch_result['error_count']} dokumen gagal diupload.";
        }
    } else {
        $error = "Pilih minimal 1 file untuk diupload!";
    }
}

// Cek success dari redirect
if (isset($_GET['success'])) {
    $success = "Dokumen berhasil diupload/diperbarui!";
}

// Ambil dokumen yang sudah diupload
$sql_dokumen = "SELECT * FROM dokumen_siswa WHERE id_siswa = ? ORDER BY kategori, uploaded_at DESC";
$stmt_dok = mysqli_prepare($conn, $sql_dokumen);
mysqli_stmt_bind_param($stmt_dok, "i", $id_siswa);
mysqli_stmt_execute($stmt_dok);
$result_dokumen = mysqli_stmt_get_result($stmt_dok);

$uploaded_docs = [];
while ($doc = mysqli_fetch_assoc($result_dokumen)) {
    $key = strtolower($doc['jenis_dokumen']);
    $uploaded_docs[$key] = $doc;
}

// Hitung progress dokumen umum
$total_umum = count(DOKUMEN_UMUM);
$uploaded_umum = 0;
foreach (DOKUMEN_UMUM as $jenis => $info) {
    if (isset($uploaded_docs[strtolower($jenis)])) {
        $uploaded_umum++;
    }
}
$progress_umum = ($total_umum > 0) ? round(($uploaded_umum / $total_umum) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dokumen - PPDB SMP</title>
    
    <?php include '../includes/head_siswa.php'; ?>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .doc-card {
            border-radius: 12px;
            transition: all 0.2s;
            border: 2px solid #e9ecef;
        }
        .doc-card:hover { 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .doc-card.uploaded { border-left: 4px solid #28a745; }
        .doc-card.pending { border-left: 4px solid #ffc107; }
        .doc-card.valid { border-left: 4px solid #28a745; background: #f0fff4; }
        .doc-card.tidak_valid { border-left: 4px solid #dc3545; background: #fff5f5; }
        
        .doc-preview {
            max-width: 100%;
            max-height: 120px;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .kategori-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .kategori-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .kategori-header.afirmasi { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .kategori-header.prestasi { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .kategori-header.mutasi { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        
        .save-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
        }
        .file-input-wrapper input[type=file] {
            font-size: 0.9rem;
        }
        
        .upload-status {
            font-size: 0.8rem;
            margin-top: 5px;
        }
        .upload-status.success { color: #28a745; }
        .upload-status.error { color: #dc3545; }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/sidebar_siswa.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Header -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-file-upload me-2"></i>Upload Dokumen</h4>
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
                        
                        <!-- Progress -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">📄 Progress Dokumen Umum</span>
                                <span class="badge bg-<?php echo $progress_umum == 100 ? 'success' : 'warning'; ?>">
                                    <?php echo $uploaded_umum; ?>/<?php echo $total_umum; ?> Lengkap
                                </span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-<?php echo $progress_umum == 100 ? 'success' : 'primary'; ?>" 
                                     style="width: <?php echo $progress_umum; ?>%"></div>
                            </div>
                        </div>
                        
                        <?php if ($jalur_dipilih): ?>
                        <div class="alert alert-info mb-0">
                            <strong><i class="fas fa-road me-2"></i>Jalur Dipilih:</strong> 
                            <span class="badge bg-primary"><?php echo $pendaftaran['nama_jalur']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- ========================================= -->
                <!-- DOKUMEN UMUM -->
                <!-- ========================================= -->
                <div class="kategori-section">
                    <div class="kategori-header">
                        <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Dokumen Umum <span class="badge bg-light text-dark">Wajib</span></h5>
                        <small>Upload semua dokumen di bawah, lalu klik tombol Simpan</small>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" id="formUmum">
                        <input type="hidden" name="kategori" value="umum">
                        
                        <div class="row">
                            <?php foreach (DOKUMEN_UMUM as $jenis => $info): 
                                $jenis_lower = strtolower($jenis);
                                $doc = $uploaded_docs[$jenis_lower] ?? null;
                                $status = $doc ? $doc['status_verifikasi'] : null;
                                $batch_status = $upload_results[$jenis_lower] ?? null;
                            ?>
                            <div class="col-md-6 mb-3">
                                <div class="card doc-card h-100 <?php echo $status ?? ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-0">
                                                <i class="fas <?php echo $info['icon']; ?> text-<?php echo $info['color']; ?> me-2"></i>
                                                <?php echo $info['label']; ?>
                                            </h6>
                                            <?php if ($doc): ?>
                                            <span class="badge bg-<?php 
                                                echo $status == 'valid' ? 'success' : ($status == 'tidak_valid' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Belum Upload</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <small class="text-muted d-block mb-2"><?php echo $info['keterangan']; ?></small>
                                        
                                        <?php if ($doc): ?>
                                        <div class="mb-2">
                                            <?php 
                                            $ext = strtolower(pathinfo($doc['nama_file'], PATHINFO_EXTENSION));
                                            $is_image = in_array($ext, ['jpg', 'jpeg', 'png']);
                                            ?>
                                            <?php if ($is_image): ?>
                                            <a href="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" target="_blank">
                                                <img src="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" class="doc-preview" alt="">
                                            </a>
                                            <?php else: ?>
                                            <a href="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-file-pdf me-1"></i>Lihat PDF
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- File Input -->
                                        <div class="file-input-wrapper">
                                            <input type="file" name="docs[<?php echo $jenis_lower; ?>]" 
                                                   class="form-control form-control-sm" 
                                                   accept=".jpg,.jpeg,.png,.pdf"
                                                   id="file_<?php echo $jenis_lower; ?>">
                                            <small class="text-muted">Max 2MB (JPG, PNG, PDF)</small>
                                            
                                            <?php if ($batch_status): ?>
                                            <div class="upload-status <?php echo $batch_status['success'] ? 'success' : 'error'; ?>">
                                                <i class="fas <?php echo $batch_status['success'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                                <?php echo $batch_status['message']; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="submit" name="batch_upload" class="btn btn-primary save-btn">
                                <i class="fas fa-save me-2"></i>Simpan Semua Dokumen Umum
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- ========================================= -->
                <!-- DOKUMEN KHUSUS PER JALUR -->
                <!-- ========================================= -->
                <?php
                $jalur_docs = [
                    'afirmasi' => ['title' => 'Dokumen Afirmasi', 'subtitle' => 'Pilih minimal 1 dokumen yang Anda miliki', 'docs' => DOKUMEN_AFIRMASI],
                    'prestasi' => ['title' => 'Dokumen Prestasi', 'subtitle' => 'Sertifikat prestasi maks 3 tahun terakhir', 'docs' => DOKUMEN_PRESTASI],
                    'mutasi' => ['title' => 'Dokumen Mutasi', 'subtitle' => 'Bukti pindah tugas/domisili orang tua', 'docs' => DOKUMEN_MUTASI]
                ];
                
                foreach ($jalur_docs as $kat_key => $kat_data):
                    $is_active_jalur = ($jalur_dipilih == $kat_key);
                ?>
                <div class="kategori-section">
                    <div class="kategori-header <?php echo $kat_key; ?>">
                        <h5 class="mb-0">
                            <i class="fas fa-folder me-2"></i><?php echo $kat_data['title']; ?>
                            <?php if ($is_active_jalur): ?>
                            <span class="badge bg-light text-dark">Jalur Anda</span>
                            <?php else: ?>
                            <span class="badge bg-light text-dark opacity-75">Opsional</span>
                            <?php endif; ?>
                        </h5>
                        <small><?php echo $kat_data['subtitle']; ?></small>
                    </div>
                    
                    <?php if ($is_active_jalur): ?>
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Wajib!</strong> Upload <strong>minimal 1 dokumen</strong> dari daftar di bawah.
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="kategori" value="<?php echo $kat_key; ?>">
                        
                        <div class="row">
                            <?php foreach ($kat_data['docs'] as $jenis => $info): 
                                $jenis_lower = strtolower($jenis);
                                $doc = $uploaded_docs[$jenis_lower] ?? null;
                                $status = $doc ? $doc['status_verifikasi'] : null;
                                $need_date = isset($info['validasi_tahun']) && $info['validasi_tahun'];
                                $batch_status = $upload_results[$jenis_lower] ?? null;
                            ?>
                            <div class="col-md-6 mb-3">
                                <div class="card doc-card h-100 <?php echo $status ?? ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-0">
                                                <i class="fas <?php echo $info['icon']; ?> text-<?php echo $info['color']; ?> me-2"></i>
                                                <?php echo $info['label']; ?>
                                            </h6>
                                            <?php if ($doc): ?>
                                            <span class="badge bg-<?php 
                                                echo $status == 'valid' ? 'success' : ($status == 'tidak_valid' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <small class="text-muted d-block mb-2"><?php echo $info['keterangan']; ?></small>
                                        
                                        <?php if ($doc): ?>
                                        <div class="mb-2">
                                            <?php 
                                            $ext = strtolower(pathinfo($doc['nama_file'], PATHINFO_EXTENSION));
                                            $is_image = in_array($ext, ['jpg', 'jpeg', 'png']);
                                            ?>
                                            <?php if ($is_image): ?>
                                            <a href="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" target="_blank">
                                                <img src="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" class="doc-preview" alt="">
                                            </a>
                                            <?php else: ?>
                                            <a href="../uploads/dokumen/<?php echo $doc['nama_file']; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-file-pdf me-1"></i>Lihat
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($doc['tanggal_terbit']): ?>
                                            <br><small class="text-muted">Terbit: <?php echo tanggal_indonesia($doc['tanggal_terbit']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($need_date): ?>
                                        <div class="mb-2">
                                            <label class="form-label small mb-1">Tanggal Terbit</label>
                                            <input type="date" name="tanggal_terbit[<?php echo $jenis_lower; ?>]" 
                                                   class="form-control form-control-sm"
                                                   value="<?php echo $doc['tanggal_terbit'] ?? ''; ?>">
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- File Input -->
                                        <div class="file-input-wrapper">
                                            <input type="file" name="docs[<?php echo $jenis_lower; ?>]" 
                                                   class="form-control form-control-sm" 
                                                   accept=".jpg,.jpeg,.png,.pdf">
                                            
                                            <?php if ($batch_status): ?>
                                            <div class="upload-status <?php echo $batch_status['success'] ? 'success' : 'error'; ?>">
                                                <i class="fas <?php echo $batch_status['success'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                                <?php echo $batch_status['message']; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="submit" name="batch_upload" class="btn btn-<?php echo $is_active_jalur ? 'success' : 'secondary'; ?> save-btn">
                                <i class="fas fa-save me-2"></i>Simpan Dokumen <?php echo ucfirst($kat_key); ?>
                            </button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
                
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

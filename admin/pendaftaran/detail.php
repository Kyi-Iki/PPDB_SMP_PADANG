<?php
/**
 * File: admin/pendaftaran/detail.php
 * Deskripsi: Detail pendaftaran dengan semua informasi dan dokumen
 */

session_start();
require_once '../../config/database.php';
check_admin();

$id_pendaftaran = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_pendaftaran) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Proses update nilai rapor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_nilai'])) {
    $nilai_1 = (float)$_POST['nilai_semester_1'];
    $nilai_2 = (float)$_POST['nilai_semester_2'];
    $nilai_3 = (float)$_POST['nilai_semester_3'];
    $nilai_4 = (float)$_POST['nilai_semester_4'];
    $nilai_5 = (float)$_POST['nilai_semester_5'];
    $alasan_koreksi = clean_input($_POST['alasan_koreksi']);
    
    // Validasi nilai 0-100
    $valid = true;
    foreach ([$nilai_1, $nilai_2, $nilai_3, $nilai_4, $nilai_5] as $n) {
        if ($n < 0 || $n > 100) {
            $valid = false;
            break;
        }
    }
    
    if (!$valid) {
        $error = "Nilai harus antara 0-100!";
    } elseif (empty($alasan_koreksi)) {
        $error = "Alasan koreksi wajib diisi!";
    } else {
        // Hitung rata-rata baru
        $nilai_rata = ($nilai_1 + $nilai_2 + $nilai_3 + $nilai_4 + $nilai_5) / 5;
        
        // Ambil nilai lama untuk log
        $sql_old = "SELECT nilai_rapor_rata FROM pendaftaran WHERE id_pendaftaran = ?";
        $stmt_old = mysqli_prepare($conn, $sql_old);
        mysqli_stmt_bind_param($stmt_old, "i", $id_pendaftaran);
        mysqli_stmt_execute($stmt_old);
        $old_nilai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old))['nilai_rapor_rata'];
        
        // Update nilai + simpan catatan koreksi untuk ditampilkan ke siswa
        $tanggal_koreksi = date('Y-m-d H:i:s');
        $sql_update = "UPDATE pendaftaran SET 
                       nilai_semester_1 = ?, nilai_semester_2 = ?, nilai_semester_3 = ?,
                       nilai_semester_4 = ?, nilai_semester_5 = ?, nilai_rapor_rata = ?,
                       catatan_koreksi_nilai = ?, tanggal_koreksi_nilai = ?
                       WHERE id_pendaftaran = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ddddddssi", $nilai_1, $nilai_2, $nilai_3, $nilai_4, $nilai_5, $nilai_rata, $alasan_koreksi, $tanggal_koreksi, $id_pendaftaran);
        
        if (mysqli_stmt_execute($stmt_update)) {
            // Log aktivitas
            $log_detail = "Koreksi nilai pendaftaran #$id_pendaftaran. Nilai lama: $old_nilai, Nilai baru: $nilai_rata. Alasan: $alasan_koreksi";
            log_aktivitas($_SESSION['id_user'], 'Koreksi Nilai Rapor', $log_detail);
            
            $success = "Nilai rapor berhasil diperbarui!";
            
            // Refresh halaman untuk update data
            header("Location: detail.php?id=$id_pendaftaran&success=1");
            exit();
        } else {
            $error = "Gagal memperbarui nilai!";
        }
    }
}

// Cek success dari redirect
if (isset($_GET['success'])) {
    $success = "Nilai rapor berhasil diperbarui!";
}

// Ambil data pendaftaran lengkap (termasuk Pilihan 2)
$sql = "SELECT p.*, s.*, sch.nama_sekolah, sch.npsn, sch.alamat_sekolah, j.nama_jalur,
        sch2.nama_sekolah as nama_sekolah_2, sch2.npsn as npsn_2,
        hs.status_seleksi, hs.skor_akhir, hs.ranking, hs.keterangan as keterangan_seleksi, 
        hs.dipublikasikan, hs.pilihan_diterima
        FROM pendaftaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
        LEFT JOIN sekolah sch2 ON p.id_sekolah_pilihan_2 = sch2.id_sekolah
        JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
        LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
        WHERE p.id_pendaftaran = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_pendaftaran);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    header("Location: index.php");
    exit();
}

// Check operator access - can only view their school's data
$operator_sekolah = get_sekolah_filter();
if ($operator_sekolah && $data['id_sekolah'] != $operator_sekolah) {
    header("Location: index.php?error=unauthorized");
    exit();
}

// Ambil data orang tua
$sql_ortu = "SELECT * FROM orang_tua WHERE id_siswa = ?";
$stmt_ortu = mysqli_prepare($conn, $sql_ortu);
mysqli_stmt_bind_param($stmt_ortu, "i", $data['id_siswa']);
mysqli_stmt_execute($stmt_ortu);
$ortu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_ortu));

// Ambil dokumen dari tabel dokumen_siswa (berdasarkan id_siswa)
$sql_dokumen = "SELECT * FROM dokumen_siswa
                WHERE id_siswa = ?
                ORDER BY kategori, jenis_dokumen";
$stmt_dok = mysqli_prepare($conn, $sql_dokumen);
mysqli_stmt_bind_param($stmt_dok, "i", $data['id_siswa']);
mysqli_stmt_execute($stmt_dok);
$dokumen_list = mysqli_stmt_get_result($stmt_dok);

// Ambil semua pendaftaran siswa ini (riwayat)
$sql_riwayat = "SELECT p.*, sch.nama_sekolah, j.nama_jalur, hs.status_seleksi
                FROM pendaftaran p
                JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
                JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
                LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                WHERE p.id_siswa = ?
                ORDER BY p.tanggal_daftar DESC";
$stmt_riwayat = mysqli_prepare($conn, $sql_riwayat);
mysqli_stmt_bind_param($stmt_riwayat, "i", $data['id_siswa']);
mysqli_stmt_execute($stmt_riwayat);
$riwayat = mysqli_stmt_get_result($stmt_riwayat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pendaftaran - <?php echo $data['nama_lengkap']; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-label {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #2d3748;
        }
        
        .doc-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        .doc-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .doc-card.valid { border-left: 4px solid #28a745; }
        .doc-card.pending { border-left: 4px solid #ffc107; }
        .doc-card.invalid { border-left: 4px solid #dc3545; }
        
        .doc-preview {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
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
                <!-- Back Button -->
                <a href="index.php" class="btn btn-outline-secondary mb-3">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
                
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
                
                <!-- Header -->
                <div class="detail-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-1"><?php echo $data['nama_lengkap']; ?></h3>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-id-card me-2"></i>NISN: <?php echo $data['nisn']; ?> | 
                                NIK: <?php echo $data['nik'] ?? '-'; ?>
                            </p>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-hashtag me-2"></i><?php echo $data['nomor_pendaftaran']; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-<?php 
                                echo $data['nama_jalur'] == 'Zonasi' ? 'primary' : 
                                    ($data['nama_jalur'] == 'Prestasi' ? 'success' : 
                                    ($data['nama_jalur'] == 'Afirmasi' ? 'warning text-dark' : 'info')); 
                            ?> fs-6"><?php echo $data['nama_jalur']; ?></span>
                            <br>
                            <span class="badge bg-<?php 
                                echo $data['status_verifikasi'] == 'terverifikasi' ? 'success' : 
                                    ($data['status_verifikasi'] == 'ditolak' ? 'danger' : 'warning text-dark'); 
                            ?> mt-2"><?php echo ucfirst($data['status_verifikasi']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Data Pribadi -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-user text-primary me-2"></i>Data Pribadi
                            </h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Nama Lengkap</div>
                                    <div class="info-value"><?php echo $data['nama_lengkap']; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Jenis Kelamin</div>
                                    <div class="info-value"><?php echo $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Tempat, Tanggal Lahir</div>
                                    <div class="info-value">
                                        <?php echo ($data['tempat_lahir'] ?? '-') . ', ' . 
                                            (isset($data['tanggal_lahir']) ? tanggal_indonesia($data['tanggal_lahir']) : '-'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Agama</div>
                                    <div class="info-value"><?php echo $data['agama'] ?? '-'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">NIK</div>
                                    <div class="info-value"><?php echo $data['nik'] ?? '-'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">No. Telepon</div>
                                    <div class="info-value">
                                        <?php if ($data['no_telepon']): ?>
                                        <a href="tel:<?php echo $data['no_telepon']; ?>" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i><?php echo $data['no_telepon']; ?>
                                        </a>
                                        <?php else: ?>-<?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Asal Sekolah SD/MI</div>
                                    <div class="info-value"><?php echo $data['asal_sekolah'] ?? '-'; ?></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <div class="info-label">Alamat Lengkap</div>
                                    <div class="info-value">
                                        <?php echo $data['alamat_lengkap'] ?? '-'; ?>
                                        <?php if ($data['kelurahan']): ?>
                                        <br><small class="text-muted">
                                            RT <?php echo $data['rt']; ?>/RW <?php echo $data['rw']; ?>, 
                                            Kel. <?php echo $data['kelurahan']; ?>, 
                                            Kec. <?php echo $data['kecamatan']; ?>, 
                                            <?php echo $data['kota']; ?>, <?php echo $data['provinsi'] ?? ''; ?>
                                            <?php if ($data['kode_pos']): ?> - <?php echo $data['kode_pos']; ?><?php endif; ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Koordinat GPS</div>
                                    <div class="info-value">
                                        <?php if ($data['latitude'] && $data['latitude'] != 0): ?>
                                        <a href="https://www.google.com/maps?q=<?php echo $data['latitude']; ?>,<?php echo $data['longitude']; ?>" 
                                           target="_blank" class="text-decoration-none">
                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            <?php echo number_format($data['latitude'], 6); ?>, 
                                            <?php echo number_format($data['longitude'], 6); ?>
                                            <i class="fas fa-external-link-alt ms-1 small"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Belum diatur</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Jarak ke Sekolah Pilihan 1</div>
                                    <div class="info-value">
                                        <span class="badge bg-<?php echo $data['jarak_rumah_sekolah'] < 5 ? 'success' : ($data['jarak_rumah_sekolah'] < 10 ? 'warning' : 'danger'); ?> fs-6">
                                            <?php echo number_format($data['jarak_rumah_sekolah'], 2); ?> km
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Data Orang Tua -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-users text-success me-2"></i>Data Orang Tua / Wali
                            </h5>
                            
                            <!-- Data Ayah -->
                            <h6 class="text-primary mb-2"><i class="fas fa-male me-1"></i>Data Ayah</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Nama Ayah</div>
                                    <div class="info-value"><?php echo $ortu['nama_ayah'] ?? '-'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">NIK Ayah</div>
                                    <div class="info-value"><?php echo $ortu['nik_ayah'] ?? '-'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Pekerjaan Ayah</div>
                                    <div class="info-value"><?php echo $ortu['pekerjaan_ayah'] ?? '-'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Penghasilan Ayah/Bulan</div>
                                    <div class="info-value">
                                        <?php if ($ortu['penghasilan_ayah']): ?>
                                        Rp <?php echo number_format($ortu['penghasilan_ayah'], 0, ',', '.'); ?>
                                        <?php else: ?>-<?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Data Ibu -->
                            <h6 class="text-primary mb-2 mt-3"><i class="fas fa-female me-1"></i>Data Ibu</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Nama Ibu</div>
                                    <div class="info-value"><?php echo $ortu['nama_ibu'] ?? '-'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">NIK Ibu</div>
                                    <div class="info-value"><?php echo $ortu['nik_ibu'] ?? '-'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Pekerjaan Ibu</div>
                                    <div class="info-value"><?php echo $ortu['pekerjaan_ibu'] ?? '-'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Penghasilan Ibu/Bulan</div>
                                    <div class="info-value">
                                        <?php if ($ortu['penghasilan_ibu']): ?>
                                        Rp <?php echo number_format($ortu['penghasilan_ibu'], 0, ',', '.'); ?>
                                        <?php else: ?>-<?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kontak -->
                            <h6 class="text-primary mb-2 mt-3"><i class="fas fa-phone me-1"></i>Kontak Orang Tua</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">No. Telepon Orang Tua</div>
                                    <div class="info-value">
                                        <?php if ($ortu['no_telepon_ortu']): ?>
                                        <a href="tel:<?php echo $ortu['no_telepon_ortu']; ?>" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i><?php echo $ortu['no_telepon_ortu']; ?>
                                        </a>
                                        <?php else: ?>-<?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Total Penghasilan Keluarga/Bulan</div>
                                    <div class="info-value">
                                        <?php 
                                        $total_penghasilan = ($ortu['penghasilan_ayah'] ?? 0) + ($ortu['penghasilan_ibu'] ?? 0);
                                        if ($total_penghasilan > 0): ?>
                                        <span class="badge bg-<?php echo $total_penghasilan < 2000000 ? 'danger' : ($total_penghasilan < 5000000 ? 'warning' : 'success'); ?>">
                                            Rp <?php echo number_format($total_penghasilan, 0, ',', '.'); ?>
                                        </span>
                                        <?php else: ?>-<?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dokumen -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-file-alt text-warning me-2"></i>Dokumen Pendaftaran
                            </h5>
                            
                            <?php if (mysqli_num_rows($dokumen_list) == 0): ?>
                            <p class="text-muted">Belum ada dokumen yang diupload</p>
                            <?php else: ?>
                            <div class="row">
                                <?php while ($dok = mysqli_fetch_assoc($dokumen_list)): ?>
                                <div class="col-md-6">
                                    <div class="doc-card <?php 
                                        echo $dok['status_verifikasi'] == 'valid' ? 'valid' : 
                                            ($dok['status_verifikasi'] == 'tidak_valid' ? 'invalid' : 'pending'); 
                                    ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-0"><?php echo ucwords(str_replace('_', ' ', $dok['jenis_dokumen'])); ?></h6>
                                            <span class="badge bg-<?php 
                                                echo $dok['status_verifikasi'] == 'valid' ? 'success' : 
                                                    ($dok['status_verifikasi'] == 'tidak_valid' ? 'danger' : 'warning'); 
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $dok['status_verifikasi'])); ?></span>
                                        </div>
                                        
                                        <?php 
                                        $ext = strtolower(pathinfo($dok['nama_file'], PATHINFO_EXTENSION));
                                        $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                        ?>
                                        
                                        <?php if ($is_image): ?>
                                        <a href="../../uploads/dokumen/<?php echo $dok['nama_file']; ?>" target="_blank">
                                            <img src="../../uploads/dokumen/<?php echo $dok['nama_file']; ?>" 
                                                 class="doc-preview" alt="<?php echo $dok['jenis_dokumen']; ?>">
                                        </a>
                                        <?php else: ?>
                                        <a href="../../uploads/dokumen/<?php echo $dok['nama_file']; ?>" 
                                           target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-download me-1"></i>Download File
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($dok['catatan']): ?>
                                        <div class="mt-2 small text-muted">
                                            <i class="fas fa-comment me-1"></i><?php echo $dok['catatan']; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Data Pendaftaran -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-school text-info me-2"></i>Data Pendaftaran
                            </h5>
                            
                            <div class="mb-3">
                                <div class="info-label">Sekolah Pilihan 1 (Utama)</div>
                                <div class="info-value"><?php echo $data['nama_sekolah']; ?></div>
                                <small class="text-muted">NPSN: <?php echo $data['npsn']; ?></small>
                            </div>
                            
                            <?php if ($data['nama_sekolah_2']): ?>
                            <div class="mb-3">
                                <div class="info-label">Sekolah Pilihan 2 (Cadangan)</div>
                                <div class="info-value"><?php echo $data['nama_sekolah_2']; ?></div>
                                <small class="text-muted">NPSN: <?php echo $data['npsn_2']; ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <div class="info-label">Jalur PPDB</div>
                                <div class="info-value">
                                    <span class="badge bg-primary"><?php echo $data['nama_jalur']; ?></span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-label">
                                    Nilai Rapor Rata-rata
                                    <button type="button" class="btn btn-sm btn-outline-warning ms-2" 
                                            data-bs-toggle="modal" data-bs-target="#editNilaiModal">
                                        <i class="fas fa-edit"></i> Koreksi
                                    </button>
                                </div>
                                <div class="info-value fs-4 text-primary">
                                    <?php echo number_format($data['nilai_rapor_rata'], 2); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-label">Jarak Rumah-Sekolah</div>
                                <div class="info-value"><?php echo $data['jarak_rumah_sekolah']; ?> km</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-label">Tanggal Daftar</div>
                                <div class="info-value">
                                    <?php echo tanggal_indonesia(date('Y-m-d', strtotime($data['tanggal_daftar']))); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hasil Seleksi -->
                        <?php if ($data['status_seleksi']): ?>
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-trophy text-warning me-2"></i>Hasil Seleksi
                            </h5>
                            
                            <div class="text-center mb-3">
                                <span class="badge bg-<?php echo $data['status_seleksi'] == 'lulus' ? 'success' : 'secondary'; ?> fs-5 px-4 py-2">
                                    <?php echo strtoupper($data['status_seleksi']); ?>
                                </span>
                                <?php if ($data['status_seleksi'] == 'lulus' && !empty($data['pilihan_diterima'])): ?>
                                <br>
                                <span class="badge bg-dark mt-2">
                                    <i class="fas fa-school me-1"></i>Diterima di Pilihan <?php echo $data['pilihan_diterima']; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($data['ranking']): ?>
                            <div class="mb-3 text-center">
                                <div class="info-label">Ranking</div>
                                <div class="info-value fs-3">#<?php echo $data['ranking']; ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($data['skor_akhir']): ?>
                            <div class="mb-3 text-center">
                                <div class="info-label">Skor Akhir</div>
                                <div class="info-value"><?php echo number_format($data['skor_akhir'], 2); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-center">
                                <span class="badge bg-<?php echo $data['dipublikasikan'] ? 'success' : 'warning'; ?>">
                                    <?php echo $data['dipublikasikan'] ? 'Sudah Dipublikasikan' : 'Belum Dipublikasikan'; ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Riwayat Pendaftaran -->
                        <?php if (mysqli_num_rows($riwayat) > 1): ?>
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-history text-secondary me-2"></i>Riwayat Pendaftaran
                            </h5>
                            
                            <?php while ($r = mysqli_fetch_assoc($riwayat)): ?>
                            <div class="d-flex align-items-center mb-2 <?php echo $r['id_pendaftaran'] == $id_pendaftaran ? 'opacity-50' : ''; ?>">
                                <span class="badge bg-info me-2"><?php echo $r['nama_jalur']; ?></span>
                                <div class="flex-grow-1">
                                    <small><?php echo $r['nama_sekolah']; ?></small>
                                    <?php if ($r['status_seleksi']): ?>
                                    <span class="badge bg-<?php echo $r['status_seleksi'] == 'lulus' ? 'success' : 'secondary'; ?> ms-1">
                                        <?php echo $r['status_seleksi']; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Quick Actions -->
                        <div class="info-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-bolt text-danger me-2"></i>Aksi Cepat
                            </h5>
                            <div class="d-grid gap-2">
                                <a href="../verifikasi/index.php?id=<?php echo $id_pendaftaran; ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-check-circle me-2"></i>Verifikasi Berkas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit Nilai -->
    <div class="modal fade" id="editNilaiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Koreksi Nilai Rapor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i>
                            Koreksi nilai hanya dilakukan jika nilai yang diinput siswa berbeda dengan dokumen rapor asli.
                        </div>
                        
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Semester 1</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                       name="nilai_semester_1" value="<?php echo $data['nilai_semester_1'] ?? 0; ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Semester 2</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                       name="nilai_semester_2" value="<?php echo $data['nilai_semester_2'] ?? 0; ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Semester 3</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                       name="nilai_semester_3" value="<?php echo $data['nilai_semester_3'] ?? 0; ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Semester 4</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                       name="nilai_semester_4" value="<?php echo $data['nilai_semester_4'] ?? 0; ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Semester 5</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                       name="nilai_semester_5" value="<?php echo $data['nilai_semester_5'] ?? 0; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alasan Koreksi <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="alasan_koreksi" rows="2" required
                                      placeholder="Contoh: Nilai tidak sesuai dengan rapor yang diupload"></textarea>
                            <small class="text-muted">Alasan akan dicatat di log aktivitas</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_nilai" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

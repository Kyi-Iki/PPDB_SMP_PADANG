<?php
/**
 * File: siswa/biodata.php
 * Deskripsi: Form pengisian biodata lengkap siswa dan orang tua
 */

session_start();
require_once '../config/database.php';
require_once '../includes/csrf.php';

// Cek login
check_siswa();

$id_siswa = $_SESSION['id_siswa'];
$error = '';
$success = '';

// Ambil data siswa existing
$sql = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_siswa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$siswa = mysqli_fetch_assoc($result);

// Ambil data orang tua
$sql_ortu = "SELECT * FROM orang_tua WHERE id_siswa = ?";
$stmt_ortu = mysqli_prepare($conn, $sql_ortu);
mysqli_stmt_bind_param($stmt_ortu, "i", $id_siswa);
mysqli_stmt_execute($stmt_ortu);
$result_ortu = mysqli_stmt_get_result($stmt_ortu);
$ortu = mysqli_fetch_assoc($result_ortu);

// Helper: Preserve form data on validation error
// Prioritas: POST data > Database data > default value
function form_value($field, $siswa_data, $ortu_data = null, $default = '') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$field])) {
        return htmlspecialchars($_POST[$field]);
    }
    if ($siswa_data && isset($siswa_data[$field])) {
        return htmlspecialchars($siswa_data[$field]);
    }
    if ($ortu_data && isset($ortu_data[$field])) {
        return htmlspecialchars($ortu_data[$field]);
    }
    return $default;
}

// Daftar Kecamatan Kota Padang
$daftar_kecamatan = [
    'Bungus Teluk Kabung',
    'Koto Tengah',
    'Kuranji',
    'Lubuk Begalung',
    'Lubuk Kilangan',
    'Nanggalo',
    'Padang Barat',
    'Padang Selatan',
    'Padang Timur',
    'Padang Utara',
    'Pauh'
];
// Proses submit form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi CSRF
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan refresh halaman.";
    } else {
        // Data Siswa
        $nik = clean_input($_POST['nik']);
    $tempat_lahir = clean_input($_POST['tempat_lahir']);
    $tanggal_lahir = clean_input($_POST['tanggal_lahir']);
    $jenis_kelamin = clean_input($_POST['jenis_kelamin']);
    $agama = clean_input($_POST['agama']);
    $alamat_lengkap = clean_input($_POST['alamat_lengkap']);
    $rt = clean_input($_POST['rt']);
    $rw = clean_input($_POST['rw']);
    $kelurahan = clean_input($_POST['kelurahan']);
    $kecamatan = clean_input($_POST['kecamatan']);
    $kota = clean_input($_POST['kota']);
    $provinsi = clean_input($_POST['provinsi']);
    $kode_pos = clean_input($_POST['kode_pos']);
    $latitude = clean_input($_POST['latitude']);
    $longitude = clean_input($_POST['longitude']);
    $no_telepon = clean_input($_POST['no_telepon']);
    $asal_sekolah = clean_input($_POST['asal_sekolah']);
    
    // Validasi usia (6-15 tahun untuk SMP)
    $usia = floor((time() - strtotime($tanggal_lahir)) / (365.25 * 24 * 60 * 60));
    if ($usia < 10 || $usia > 16) {
        $error = "Usia calon siswa harus antara 10-16 tahun untuk mendaftar SMP.";
    }
    
    // Validasi lokasi wajib diisi
    if (empty($latitude) || empty($longitude)) {
        $error = "Lokasi rumah (Latitude dan Longitude) wajib diisi. Gunakan tombol 'Ambil Lokasi GPS' atau pilih lokasi di peta.";
    }
    
    // Data Orang Tua
    $nama_ayah = clean_input($_POST['nama_ayah']);
    $nik_ayah = clean_input($_POST['nik_ayah']);
    $pekerjaan_ayah = clean_input($_POST['pekerjaan_ayah']);
    $penghasilan_ayah = clean_input($_POST['penghasilan_ayah']);
    $nama_ibu = clean_input($_POST['nama_ibu']);
    $nik_ibu = clean_input($_POST['nik_ibu']);
    $pekerjaan_ibu = clean_input($_POST['pekerjaan_ibu']);
    $penghasilan_ibu = clean_input($_POST['penghasilan_ibu']);
    $no_telepon_ortu = clean_input($_POST['no_telepon_ortu']);
    
    mysqli_begin_transaction($conn);
    
    if (empty($error)) {
    try {
        // Update data siswa
        $sql_update_siswa = "UPDATE siswa SET 
            nik = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ?, agama = ?,
            alamat_lengkap = ?, rt = ?, rw = ?, kelurahan = ?, kecamatan = ?,
            kota = ?, provinsi = ?, kode_pos = ?, latitude = ?, longitude = ?,
            no_telepon = ?, asal_sekolah = ?
            WHERE id_siswa = ?";
        
        $stmt_siswa = mysqli_prepare($conn, $sql_update_siswa);
        mysqli_stmt_bind_param($stmt_siswa, "sssssssssssssssssi", 
            $nik, $tempat_lahir, $tanggal_lahir, $jenis_kelamin, $agama,
            $alamat_lengkap, $rt, $rw, $kelurahan, $kecamatan,
            $kota, $provinsi, $kode_pos, $latitude, $longitude,
            $no_telepon, $asal_sekolah, $id_siswa);
        mysqli_stmt_execute($stmt_siswa);
        
        // Insert atau update data orang tua
        if ($ortu) {
            $sql_update_ortu = "UPDATE orang_tua SET 
                nama_ayah = ?, nik_ayah = ?, pekerjaan_ayah = ?, penghasilan_ayah = ?,
                nama_ibu = ?, nik_ibu = ?, pekerjaan_ibu = ?, penghasilan_ibu = ?,
                no_telepon_ortu = ?
                WHERE id_siswa = ?";
            
            $stmt_ortu = mysqli_prepare($conn, $sql_update_ortu);
            mysqli_stmt_bind_param($stmt_ortu, "sssdsssdsi",
                $nama_ayah, $nik_ayah, $pekerjaan_ayah, $penghasilan_ayah,
                $nama_ibu, $nik_ibu, $pekerjaan_ibu, $penghasilan_ibu,
                $no_telepon_ortu, $id_siswa);
            mysqli_stmt_execute($stmt_ortu);
        } else {
            $sql_insert_ortu = "INSERT INTO orang_tua 
                (id_siswa, nama_ayah, nik_ayah, pekerjaan_ayah, penghasilan_ayah,
                nama_ibu, nik_ibu, pekerjaan_ibu, penghasilan_ibu, no_telepon_ortu)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_ortu = mysqli_prepare($conn, $sql_insert_ortu);
            mysqli_stmt_bind_param($stmt_ortu, "isssdsssds",
                $id_siswa, $nama_ayah, $nik_ayah, $pekerjaan_ayah, $penghasilan_ayah,
                $nama_ibu, $nik_ibu, $pekerjaan_ibu, $penghasilan_ibu, $no_telepon_ortu);
            mysqli_stmt_execute($stmt_ortu);
        }
        
        mysqli_commit($conn);
        
        log_aktivitas($_SESSION['id_user'], 'Update Biodata', 'Biodata siswa berhasil diupdate');
        
        $success = "Data berhasil disimpan! Silakan lanjut ke upload dokumen.";
        
        // Refresh data
        header("refresh:2;url=dokumen.php");
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Gagal menyimpan data: " . $e->getMessage();
    }
    } // end if empty error
    } // end CSRF validation
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biodata - PPDB SMP</title>
    
    <?php include '../includes/head_siswa.php'; ?>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        body {
            background: #f5f7fa;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .section-title {
            color: #1e3a8a;
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }
        /* Progress Indicator */
        .progress-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .step-circle.active {
            background: linear-gradient(135deg, #1e3a8a 0%, #764ba2 100%);
            color: white;
        }
        .step-circle.active i {
            color: white !important;
        }
        .step-circle.completed {
            background: #28a745;
            color: white;
        }
        .step-circle.completed i {
            color: white !important;
        }
        .step-circle.pending {
            background: #e9ecef;
            color: #6c757d;
        }
        .step-circle.pending i {
            color: #6c757d !important;
        }
        .step-label {
            margin-top: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .step-line {
            width: 80px;
            height: 3px;
            background: #e9ecef;
            margin: 0 10px;
        }
        .step-line.completed {
            background: #28a745;
        }
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
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">📝 Biodata Lengkap</h4>
                    </div>
                    <div class="card-body">
                        <!-- Progress Indicator -->
                        <div class="progress-steps">
                            <div class="step">
                                <div class="step-circle active">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="step-label text-primary">Biodata</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <div class="step-circle pending">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <span class="step-label text-muted">Dokumen</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <div class="step-circle pending">
                                    <i class="fas fa-school"></i>
                                </div>
                                <span class="step-label text-muted">Pilih Sekolah</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <div class="step-circle pending">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="step-label text-muted">Selesai</span>
                            </div>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <?php echo csrf_input(); ?>
                            <!-- DATA PRIBADI -->
                            <div class="form-section">
                                <h5 class="section-title">👤 Data Pribadi</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo $siswa['nama_lengkap']; ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NISN</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo $siswa['nisn']; ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NIK (Nomor Induk Kependudukan) *</label>
                                        <input type="text" class="form-control" name="nik" 
                                               value="<?php echo form_value('nik', $siswa); ?>" 
                                               maxlength="16" pattern="[0-9]{16}" 
                                               placeholder="16 digit NIK sesuai KK" required>
                                        <small class="text-muted">NIK harus sesuai dengan Kartu Keluarga</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tempat Lahir *</label>
                                        <input type="text" class="form-control" name="tempat_lahir" 
                                               value="<?php echo form_value('tempat_lahir', $siswa); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Lahir *</label>
                                        <input type="date" class="form-control" name="tanggal_lahir" 
                                               value="<?php echo form_value('tanggal_lahir', $siswa); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jenis Kelamin *</label>
                                        <select class="form-select" name="jenis_kelamin" required>
                                            <option value="">Pilih</option>
                                            <?php $jk_val = form_value('jenis_kelamin', $siswa); ?>
                                            <option value="L" <?php echo ($jk_val == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                            <option value="P" <?php echo ($jk_val == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Agama *</label>
                                        <select class="form-select" name="agama" required>
                                            <option value="">Pilih</option>
                                            <option value="Islam" <?php echo ($siswa['agama'] == 'Islam') ? 'selected' : ''; ?>>Islam</option>
                                            <option value="Kristen" <?php echo ($siswa['agama'] == 'Kristen') ? 'selected' : ''; ?>>Kristen</option>
                                            <option value="Katolik" <?php echo ($siswa['agama'] == 'Katolik') ? 'selected' : ''; ?>>Katolik</option>
                                            <option value="Hindu" <?php echo ($siswa['agama'] == 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                                            <option value="Buddha" <?php echo ($siswa['agama'] == 'Buddha') ? 'selected' : ''; ?>>Buddha</option>
                                            <option value="Konghucu" <?php echo ($siswa['agama'] == 'Konghucu') ? 'selected' : ''; ?>>Konghucu</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Asal Sekolah SD/MI *</label>
                                    <input type="text" class="form-control" name="asal_sekolah" 
                                           value="<?php echo form_value('asal_sekolah', $siswa); ?>" 
                                           placeholder="Contoh: SD Negeri 01 Padang" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">No. Telepon/HP *</label>
                                    <input type="tel" class="form-control" name="no_telepon" 
                                           value="<?php echo form_value('no_telepon', $siswa); ?>" 
                                           placeholder="08xxxxxxxxxx" required>
                                </div>
                            </div>
                            
                            <!-- ALAMAT -->
                            <div class="form-section">
                                <h5 class="section-title">🏠 Alamat Tempat Tinggal</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Alamat Lengkap *</label>
                                    <textarea class="form-control" name="alamat_lengkap" rows="3" 
                                              required><?php echo form_value('alamat_lengkap', $siswa); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">RT *</label>
                                        <input type="text" class="form-control" name="rt" 
                                               value="<?php echo $siswa['rt'] ?? ''; ?>" maxlength="3" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">RW *</label>
                                        <input type="text" class="form-control" name="rw" 
                                               value="<?php echo $siswa['rw'] ?? ''; ?>" maxlength="3" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kelurahan *</label>
                                        <input type="text" class="form-control" name="kelurahan" 
                                               value="<?php echo $siswa['kelurahan'] ?? ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Kecamatan *</label>
                                        <select class="form-select" name="kecamatan" required>
                                            <option value="">-- Pilih Kecamatan --</option>
                                            <?php foreach ($daftar_kecamatan as $kec): ?>
                                            <option value="<?php echo $kec; ?>" 
                                                <?php echo (form_value('kecamatan', $siswa) == $kec) ? 'selected' : ''; ?>>
                                                <?php echo $kec; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Kota *</label>
                                        <input type="text" class="form-control" name="kota" 
                                               value="<?php echo $siswa['kota'] ?? 'Padang'; ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Provinsi *</label>
                                        <input type="text" class="form-control" name="provinsi" 
                                               value="<?php echo $siswa['provinsi'] ?? 'Sumatera Barat'; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Kode Pos</label>
                                        <input type="text" class="form-control" name="kode_pos" 
                                               value="<?php echo $siswa['kode_pos'] ?? ''; ?>" maxlength="5">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Latitude * <span class="text-danger"><i class="fas fa-exclamation-circle"></i></span></label>
                                        <input type="text" class="form-control" name="latitude" id="latitude"
                                               value="<?php echo form_value('latitude', $siswa); ?>" 
                                               placeholder="-0.xxxxxx" readonly required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Longitude * <span class="text-danger"><i class="fas fa-exclamation-circle"></i></span></label>
                                        <input type="text" class="form-control" name="longitude" id="longitude"
                                               value="<?php echo form_value('longitude', $siswa); ?>" 
                                               placeholder="100.xxxxxx" readonly required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="alert alert-warning mb-3">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            <strong>Lokasi Rumah Wajib Diisi!</strong> Gunakan tombol di bawah untuk mengambil lokasi otomatis atau pilih di peta.
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <button type="button" class="btn btn-outline-primary me-2" onclick="getLocation()">
                                            <i class="fas fa-crosshairs me-2"></i>Ambil Lokasi GPS Otomatis
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="toggleMap()">
                                            <i class="fas fa-map me-2"></i>Pilih dari Peta
                                        </button>
                                        <span id="gpsStatus" class="ms-2"></span>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Koordinat GPS digunakan untuk menghitung jarak ke sekolah (jalur zonasi). 
                                            Klik peta atau gunakan GPS untuk menentukan lokasi rumah Anda.
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Peta Interaktif -->
                                <div class="row" id="mapContainer" style="display: none;">
                                    <div class="col-12 mb-3">
                                        <div class="card border-primary">
                                            <div class="card-header bg-primary text-white py-2">
                                                <i class="fas fa-map-marked-alt me-2"></i>Klik pada peta untuk menentukan lokasi rumah
                                                <button type="button" class="btn btn-sm btn-light float-end" onclick="toggleMap()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="card-body p-0">
                                                <div id="locationMap" style="height: 350px; width: 100%;"></div>
                                            </div>
                                            <div class="card-footer bg-light py-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-mouse-pointer me-1"></i>Klik pada peta untuk memilih lokasi |
                                                    <i class="fas fa-search-plus me-1 ms-2"></i>Gunakan scroll mouse untuk zoom
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DATA ORANG TUA -->
                            <div class="form-section">
                                <h5 class="section-title">👨‍👩‍👦 Data Orang Tua / Wali</h5>
                                
                                <h6 class="text-primary mt-3">Data Ayah</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Ayah *</label>
                                        <input type="text" class="form-control" name="nama_ayah" 
                                               value="<?php echo $ortu['nama_ayah'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NIK Ayah</label>
                                        <input type="text" class="form-control" name="nik_ayah" 
                                               value="<?php echo $ortu['nik_ayah'] ?? ''; ?>" maxlength="16">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Pekerjaan Ayah *</label>
                                        <input type="text" class="form-control" name="pekerjaan_ayah" 
                                               value="<?php echo $ortu['pekerjaan_ayah'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Penghasilan Ayah/Bulan</label>
                                        <input type="number" class="form-control" name="penghasilan_ayah" 
                                               value="<?php echo $ortu['penghasilan_ayah'] ?? ''; ?>" 
                                               placeholder="Contoh: 3000000">
                                    </div>
                                </div>
                                
                                <h6 class="text-primary mt-4">Data Ibu</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Ibu *</label>
                                        <input type="text" class="form-control" name="nama_ibu" 
                                               value="<?php echo $ortu['nama_ibu'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NIK Ibu</label>
                                        <input type="text" class="form-control" name="nik_ibu" 
                                               value="<?php echo $ortu['nik_ibu'] ?? ''; ?>" maxlength="16">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Pekerjaan Ibu *</label>
                                        <input type="text" class="form-control" name="pekerjaan_ibu" 
                                               value="<?php echo $ortu['pekerjaan_ibu'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Penghasilan Ibu/Bulan</label>
                                        <input type="number" class="form-control" name="penghasilan_ibu" 
                                               value="<?php echo $ortu['penghasilan_ibu'] ?? ''; ?>" 
                                               placeholder="Contoh: 2000000">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">No. Telepon Orang Tua *</label>
                                    <input type="tel" class="form-control" name="no_telepon_ortu" 
                                           value="<?php echo $ortu['no_telepon_ortu'] ?? ''; ?>" 
                                           placeholder="08xxxxxxxxxx" required>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    💾 Simpan & Lanjutkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // GPS Location
        function getLocation() {
            const statusEl = document.getElementById('gpsStatus');
            
            if (!navigator.geolocation) {
                statusEl.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Browser tidak mendukung GPS</span>';
                return;
            }
            
            statusEl.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Mengambil lokasi...</span>';
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
                    document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
                    statusEl.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Lokasi berhasil diambil!</span>';
                },
                function(error) {
                    let msg = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            msg = 'Izin lokasi ditolak. Aktifkan GPS di browser.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            msg = 'Lokasi tidak tersedia.';
                            break;
                        case error.TIMEOUT:
                            msg = 'Waktu habis. Coba lagi.';
                            break;
                        default:
                            msg = 'Gagal mengambil lokasi.';
                    }
                    statusEl.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + msg + '</span>';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
        
        // NIK Validation
        document.querySelector('input[name="nik"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 16);
        });
    </script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Map variables
        let map = null;
        let marker = null;
        let mapInitialized = false;
        
        // Default center: Padang City
        const defaultLat = -0.9471;
        const defaultLng = 100.4172;
        
        // Get existing coordinates
        const existingLat = parseFloat(document.getElementById('latitude').value) || 0;
        const existingLng = parseFloat(document.getElementById('longitude').value) || 0;
        
        function toggleMap() {
            const container = document.getElementById('mapContainer');
            const isHidden = container.style.display === 'none';
            
            container.style.display = isHidden ? 'block' : 'none';
            
            if (isHidden && !mapInitialized) {
                initMap();
            } else if (isHidden && mapInitialized) {
                setTimeout(() => map.invalidateSize(), 100);
            }
        }
        
        function initMap() {
            // Determine initial position
            let initLat = defaultLat;
            let initLng = defaultLng;
            let initZoom = 13;
            
            if (existingLat !== 0 && existingLng !== 0) {
                initLat = existingLat;
                initLng = existingLng;
                initZoom = 16;
            }
            
            // Initialize map
            map = L.map('locationMap').setView([initLat, initLng], initZoom);
            
            // Add tile layer (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // Add marker if coordinates exist
            if (existingLat !== 0 && existingLng !== 0) {
                marker = L.marker([existingLat, existingLng], {draggable: true}).addTo(map);
                marker.bindPopup('<b>Lokasi Rumah Anda</b><br>Drag untuk memindahkan').openPopup();
                
                marker.on('dragend', function(e) {
                    updateCoordinates(e.target.getLatLng().lat, e.target.getLatLng().lng);
                });
            }
            
            // Click event to place/move marker
            map.on('click', function(e) {
                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng, {draggable: true}).addTo(map);
                    marker.bindPopup('<b>Lokasi Rumah Anda</b><br>Drag untuk memindahkan').openPopup();
                    
                    marker.on('dragend', function(ev) {
                        updateCoordinates(ev.target.getLatLng().lat, ev.target.getLatLng().lng);
                    });
                }
                updateCoordinates(e.latlng.lat, e.latlng.lng);
                marker.openPopup();
            });
            
            mapInitialized = true;
        }
        
        function updateCoordinates(lat, lng) {
            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);
            document.getElementById('gpsStatus').innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Lokasi dipilih dari peta!</span>';
        }
        
        // Update GPS function to also update map marker
        const originalGetLocation = getLocation;
        getLocation = function() {
            const statusEl = document.getElementById('gpsStatus');
            
            if (!navigator.geolocation) {
                statusEl.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Browser tidak mendukung GPS</span>';
                return;
            }
            
            statusEl.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Mengambil lokasi...</span>';
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    document.getElementById('latitude').value = lat.toFixed(8);
                    document.getElementById('longitude').value = lng.toFixed(8);
                    statusEl.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Lokasi GPS berhasil diambil!</span>';
                    
                    // Update map if initialized
                    if (mapInitialized && map) {
                        map.setView([lat, lng], 17);
                        if (marker) {
                            marker.setLatLng([lat, lng]);
                        } else {
                            marker = L.marker([lat, lng], {draggable: true}).addTo(map);
                            marker.bindPopup('<b>Lokasi Rumah Anda</b><br>Drag untuk memindahkan');
                            
                            marker.on('dragend', function(e) {
                                updateCoordinates(e.target.getLatLng().lat, e.target.getLatLng().lng);
                            });
                        }
                        marker.openPopup();
                    }
                },
                function(error) {
                    let msg = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            msg = 'Izin lokasi ditolak. Aktifkan GPS di browser.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            msg = 'Lokasi tidak tersedia.';
                            break;
                        case error.TIMEOUT:
                            msg = 'Waktu habis. Coba lagi.';
                            break;
                        default:
                            msg = 'Gagal mengambil lokasi.';
                    }
                    statusEl.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + msg + '</span>';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        };
    </script>
</body>
</html>

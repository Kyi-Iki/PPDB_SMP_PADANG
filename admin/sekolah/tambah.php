<?php
/**
 * File: admin/sekolah/tambah.php
 * Deskripsi: Form tambah sekolah baru
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/csrf.php';

check_admin();
check_super_admin(); // Only super_admin can add schools

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan coba lagi.";
    } else {
    $npsn = clean_input($_POST['npsn']);
    $nama_sekolah = clean_input($_POST['nama_sekolah']);
    $alamat_sekolah = clean_input($_POST['alamat_sekolah']);
    $kelurahan = clean_input($_POST['kelurahan']);
    $kecamatan = clean_input($_POST['kecamatan']);
    $kota = clean_input($_POST['kota']);
    $provinsi = clean_input($_POST['provinsi']);
    $latitude = clean_input($_POST['latitude']);
    $longitude = clean_input($_POST['longitude']);
    $kuota_total = clean_input($_POST['kuota_total']);
    $tahun_ajaran = clean_input($_POST['tahun_ajaran']);
    $status = clean_input($_POST['status']);
    
    // Validasi
    if (strlen($npsn) != 8) {
        $error = "NPSN harus 8 digit!";
    } else {
        // Cek NPSN duplikat
        $sql_check = "SELECT id_sekolah FROM sekolah WHERE npsn = ?";
        $stmt = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt, "s", $npsn);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
            $error = "NPSN sudah terdaftar!";
        } else {
            // Additional inputs
            $akreditasi = clean_input($_POST['akreditasi']);
            $telepon = clean_input($_POST['telepon']);
            $kepala_sekolah = clean_input($_POST['kepala_sekolah']);
            $email_sekolah = clean_input($_POST['email_sekolah']);
            
            // Handle upload foto
            $foto = '';
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $upload = upload_file($_FILES['foto'], 'sekolah');
                if ($upload['success']) {
                    $foto = $upload['filename'];
                } else {
                    $error = $upload['message'];
                    // Stop process if upload failed (optional policy)
                }
            }

            // Insert sekolah with foto
            $sql_insert = "INSERT INTO sekolah 
                          (npsn, nama_sekolah, alamat_sekolah, kelurahan, kecamatan, kota, 
                           provinsi, latitude, longitude, kuota_total, tahun_ajaran, status,
                           akreditasi, telepon, kepala_sekolah, email_sekolah, foto)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                          
            $stmt = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt, "sssssssssssssssss",
                $npsn, $nama_sekolah, $alamat_sekolah, $kelurahan, $kecamatan, $kota,
                $provinsi, $latitude, $longitude, $kuota_total, $tahun_ajaran, $status,
                $akreditasi, $telepon, $kepala_sekolah, $email_sekolah, $foto
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $id_sekolah = mysqli_insert_id($conn);
                
                log_aktivitas($_SESSION['id_user'], 'Tambah Sekolah', "Tambah sekolah: $nama_sekolah");
                
                $success = "Sekolah berhasil ditambahkan!";
                header("refresh:2;url=index.php");
            } else {
                $error = "Gagal menambahkan sekolah!";
            }
            }
        }
    } // End CSRF check
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Sekolah - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        body {
            background: #f5f7fa;
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
                            <i class="fas fa-plus-circle me-2"></i>
                            Tambah Sekolah Baru
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="needs-validation" enctype="multipart/form-data" novalidate>
                            <?php echo csrf_input(); ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NPSN *</label>
                                    <input type="text" class="form-control" name="npsn" 
                                           maxlength="8" pattern="[0-9]{8}" required>
                                    <small class="text-muted">8 digit angka</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Sekolah *</label>
                                    <input type="text" class="form-control" name="nama_sekolah" 
                                           placeholder="Contoh: SMP Negeri 1 Padang" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Foto Sekolah</label>
                                <input type="file" class="form-control" name="foto" accept="image/*">
                                <small class="text-muted">Format: JPG/PNG, Max: 2MB</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Alamat Lengkap *</label>
                                <textarea class="form-control" name="alamat_sekolah" rows="2" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kelurahan *</label>
                                    <input type="text" class="form-control" name="kelurahan" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kecamatan *</label>
                                    <select class="form-select" name="kecamatan" required>
                                        <option value="">-- Pilih Kecamatan --</option>
                                        <option value="Padang Barat">Padang Barat</option>
                                        <option value="Padang Timur">Padang Timur</option>
                                        <option value="Padang Utara">Padang Utara</option>
                                        <option value="Padang Selatan">Padang Selatan</option>
                                        <option value="Nanggalo">Nanggalo</option>
                                        <option value="Kuranji">Kuranji</option>
                                        <option value="Pauh">Pauh</option>
                                        <option value="Koto Tangah">Koto Tangah</option>
                                        <option value="Lubuk Begalung">Lubuk Begalung</option>
                                        <option value="Lubuk Kilangan">Lubuk Kilangan</option>
                                        <option value="Bungus Teluk Kabung">Bungus Teluk Kabung</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" name="telepon">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Email Sekolah</label>
                                    <input type="email" class="form-control" name="email_sekolah">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Kepala Sekolah</label>
                                    <input type="text" class="form-control" name="kepala_sekolah">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kota *</label>
                                    <input type="text" class="form-control" name="kota" value="Padang" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Provinsi *</label>
                                    <input type="text" class="form-control" name="provinsi" value="Sumatera Barat" required>
                                </div>
                            </div>
                            
                            <!-- Map Picker Section -->
                            <div class="mb-3">
                                <label class="form-label">Lokasi Sekolah (Geser Marker)</label>
                                <div id="mapPicker" style="height: 400px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6;"></div>
                                <small class="text-muted"><i class="fas fa-info-circle"></i> Klik atau geser marker merah untuk menentukan lokasi sekolah.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Latitude *</label>
                                    <input type="text" class="form-control" name="latitude" id="latitude"
                                           placeholder="-0.xxxxxx" required readonly>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Longitude *</label>
                                    <input type="text" class="form-control" name="longitude" id="longitude"
                                           placeholder="100.xxxxxx" required readonly>
                                </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Kuota Total *</label>
                                    <input type="number" class="form-control" name="kuota_total" 
                                           min="0" value="360" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tahun Ajaran *</label>
                                    <input type="text" class="form-control" name="tahun_ajaran" 
                                           value="<?php echo TAHUN_AJARAN; ?>" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" required>
                                        <option value="aktif">Aktif</option>
                                        <option value="nonaktif">Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Akreditasi</label>
                                    <select class="form-select" name="akreditasi">
                                        <option value="A">A</option>
                                        <option value="B" selected>B</option>
                                        <option value="C">C</option>
                                        <option value="Belum">Belum Terakreditasi</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS & Admin Map -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../../assets/js/admin-map.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initAdminMap();
        });
    </script>
</body>
</html>
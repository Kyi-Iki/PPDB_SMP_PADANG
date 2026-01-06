<?php
/**
 * File: admin/sekolah/edit.php
 * Deskripsi: Form edit sekolah (LENGKAP)
 * 
 * Access:
 * - Super Admin: Can edit any school
 * - Operator: Can edit only their own school
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/csrf.php';

check_admin(); // Allow both super_admin and operator

$id_sekolah = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Access check: Operator can only edit their own school
if (is_operator()) {
    $operator_sekolah = get_operator_sekolah();
    if ($id_sekolah != $operator_sekolah) {
        header("Location: index.php?error=access_denied");
        exit();
    }
}

// Ambil data sekolah
$sql = "SELECT * FROM sekolah WHERE id_sekolah = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_sekolah);
mysqli_stmt_execute($stmt);
$sekolah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$sekolah) {
    header("Location: index.php");
    exit();
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF
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
    $tahun_ajaran = clean_input($_POST['tahun_ajaran']);
    $status = clean_input($_POST['status']);
    
    // Additional inputs
    $akreditasi = clean_input($_POST['akreditasi']);
    $telepon = clean_input($_POST['telepon']);
    $kepala_sekolah = clean_input($_POST['kepala_sekolah']);
    $email_sekolah = clean_input($_POST['email_sekolah']);
    
    // Validasi
    if (strlen($npsn) != 8) {
        $error = "NPSN harus 8 digit!";
    } else {
        // Cek NPSN duplikat (kecuali diri sendiri)
        $sql_check = "SELECT id_sekolah FROM sekolah WHERE npsn = ? AND id_sekolah != ?";
        $stmt = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt, "si", $npsn, $id_sekolah);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
            $error = "NPSN sudah digunakan sekolah lain!";
        } else {
            // Handle upload foto
            $foto = $sekolah['foto']; // Default/Existing foto
            
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $upload = upload_file($_FILES['foto'], 'sekolah');
                if ($upload['success']) {
                    $foto = $upload['filename'];
                } else {
                    $error = $upload['message'];
                }
            }
            
            // Update sekolah with foto
            $sql_update = "UPDATE sekolah SET 
                          npsn = ?, nama_sekolah = ?, alamat_sekolah = ?, 
                          kelurahan = ?, kecamatan = ?, kota = ?, provinsi = ?,
                          latitude = ?, longitude = ?, kuota_total = ?, 
                          tahun_ajaran = ?, status = ?,
                          akreditasi = ?, telepon = ?, 
                          kepala_sekolah = ?, email_sekolah = ?, foto = ?
                          WHERE id_sekolah = ?";

            $stmt = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt, "sssssssssssssssssi",
                $npsn, $nama_sekolah, $alamat_sekolah, $kelurahan, $kecamatan, 
                $kota, $provinsi, $latitude, $longitude, $kuota_total, 
                $tahun_ajaran, $status, 
                $akreditasi, $telepon, $kepala_sekolah, $email_sekolah, $foto,
                $id_sekolah
            );
            
            if (mysqli_stmt_execute($stmt)) {
                log_aktivitas($_SESSION['id_user'], 'Edit Sekolah', "Edit sekolah: $nama_sekolah");
                
                $success = "Sekolah berhasil diupdate!";
                header("refresh:2;url=index.php");
            } else {
                $error = "Gagal mengupdate sekolah!";
            }
            }
        }
    } // End CSRF
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sekolah - Admin PPDB</title>
    
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
                            <i class="fas fa-edit me-2"></i>
                            Edit Sekolah
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
                                           value="<?php echo $sekolah['npsn']; ?>"
                                           maxlength="8" pattern="[0-9]{8}" required>
                                    <small class="text-muted">8 digit angka</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Sekolah *</label>
                                    <input type="text" class="form-control" name="nama_sekolah" 
                                           value="<?php echo $sekolah['nama_sekolah']; ?>"
                                           placeholder="Contoh: SMP Negeri 1 Padang" required>
                                </div>
                            </div>
                            
                            <div class="row align-items-center mb-3">
                                <div class="col-md-<?php echo !empty($sekolah['foto']) ? '8' : '12'; ?>">
                                    <label class="form-label">Foto Sekolah</label>
                                    <input type="file" class="form-control" name="foto" accept="image/*">
                                    <small class="text-muted">Format: JPG/PNG, Max: 2MB. Biarkan kosong jika tidak ingin mengubah foto.</small>
                                </div>
                                <?php if (!empty($sekolah['foto']) && file_exists('../../uploads/sekolah/' . $sekolah['foto'])): ?>
                                <div class="col-md-4 text-center">
                                    <label class="form-label d-block">Foto Saat Ini</label>
                                    <img src="../../uploads/sekolah/<?php echo $sekolah['foto']; ?>" 
                                         alt="Foto Sekolah" class="img-thumbnail" 
                                         style="max-height: 100px;">
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Alamat Lengkap *</label>
                                <textarea class="form-control" name="alamat_sekolah" rows="2" required><?php echo $sekolah['alamat_sekolah']; ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kelurahan *</label>
                                    <input type="text" class="form-control" name="kelurahan" 
                                           value="<?php echo $sekolah['kelurahan']; ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kecamatan *</label>
                                    <select class="form-select" name="kecamatan" required>
                                        <option value="">-- Pilih Kecamatan --</option>
                                        <?php
                                        $kecamatan_list = [
                                            'Padang Barat', 'Padang Timur', 'Padang Utara', 'Padang Selatan',
                                            'Nanggalo', 'Kuranji', 'Pauh', 'Koto Tangah', 'Lubuk Begalung',
                                            'Lubuk Kilangan', 'Bungus Teluk Kabung'
                                        ];
                                        foreach ($kecamatan_list as $kec) {
                                            $selected = ($sekolah['kecamatan'] == $kec) ? 'selected' : '';
                                            echo "<option value=\"$kec\" $selected>$kec</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" name="telepon" value="<?php echo $sekolah['telepon'] ?? ''; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Email Sekolah</label>
                                    <input type="email" class="form-control" name="email_sekolah" value="<?php echo $sekolah['email_sekolah'] ?? ''; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Kepala Sekolah</label>
                                    <input type="text" class="form-control" name="kepala_sekolah" value="<?php echo $sekolah['kepala_sekolah'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kota *</label>
                                    <input type="text" class="form-control" name="kota" 
                                           value="<?php echo $sekolah['kota']; ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Provinsi *</label>
                                    <input type="text" class="form-control" name="provinsi" 
                                           value="<?php echo $sekolah['provinsi']; ?>" required>
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
                                           value="<?php echo $sekolah['latitude']; ?>"
                                           placeholder="-0.xxxxxx" required readonly>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Longitude *</label>
                                    <input type="text" class="form-control" name="longitude" id="longitude"
                                           value="<?php echo $sekolah['longitude']; ?>"
                                           placeholder="100.xxxxxx" required readonly>
                                </div>
                            </div>
                            
                            <div class="row">
                                <?php if (is_super_admin()): ?>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Kuota Total *</label>
                                    <input type="number" class="form-control" name="kuota_total" 
                                           value="<?php echo $sekolah['kuota_total']; ?>"
                                           min="0" required>
                                </div>
                                <?php else: ?>
                                <!-- Hidden input for operator to preserve kuota value -->
                                <input type="hidden" name="kuota_total" value="<?php echo $sekolah['kuota_total']; ?>">
                                <?php endif; ?>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tahun Ajaran *</label>
                                    <input type="text" class="form-control" name="tahun_ajaran" 
                                           value="<?php echo $sekolah['tahun_ajaran']; ?>" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" required>
                                        <option value="aktif" <?php echo $sekolah['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="nonaktif" <?php echo $sekolah['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Akreditasi</label>
                                    <select class="form-select" name="akreditasi">
                                        <option value="A" <?php echo ($sekolah['akreditasi'] ?? '') == 'A' ? 'selected' : ''; ?>>A</option>
                                        <option value="B" <?php echo ($sekolah['akreditasi'] ?? '') == 'B' ? 'selected' : ''; ?>>B</option>
                                        <option value="C" <?php echo ($sekolah['akreditasi'] ?? '') == 'C' ? 'selected' : ''; ?>>C</option>
                                        <option value="Belum" <?php echo ($sekolah['akreditasi'] ?? '') == 'Belum' ? 'selected' : ''; ?>>Belum Terakreditasi</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Info:</strong> Jika Anda mengubah kuota total, pastikan untuk menyesuaikan kuota per jalur di menu Kuota.
                            </div>
                            
                            <div class="text-end">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update
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
            // Initialize map with existing school data
            const lat = <?php echo !empty($sekolah['latitude']) ? $sekolah['latitude'] : '-0.9471'; ?>;
            const lng = <?php echo !empty($sekolah['longitude']) ? $sekolah['longitude'] : '100.4172'; ?>;
            
            initAdminMap(lat, lng);
        });
    </script>
</body>
</html>
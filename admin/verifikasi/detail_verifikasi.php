<?php
/**
 * File: admin/verifikasi/detail_verifikasi.php
 * Deskripsi: Detail pendaftaran untuk verifikasi (AJAX)
 */

session_start();
require_once '../../config/database.php';

check_admin();

$id_pendaftaran = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data pendaftaran lengkap
$sql = "SELECT p.*, s.*, o.*, sch.nama_sekolah, j.nama_jalur
        FROM pendaftaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        LEFT JOIN orang_tua o ON s.id_siswa = o.id_siswa
        JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
        JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
        WHERE p.id_pendaftaran = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_pendaftaran);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    echo "<div class='alert alert-danger'>Data tidak ditemukan!</div>";
    exit();
}

// Ambil dokumen dari tabel dokumen_siswa (berdasarkan id_siswa, bukan id_pendaftaran)
$sql_dok = "SELECT * FROM dokumen_siswa WHERE id_siswa = ? ORDER BY kategori, jenis_dokumen";
$stmt_dok = mysqli_prepare($conn, $sql_dok);
mysqli_stmt_bind_param($stmt_dok, "i", $data['id_siswa']);
mysqli_stmt_execute($stmt_dok);
$dokumen_list = mysqli_stmt_get_result($stmt_dok);
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">
            <i class="fas fa-user me-2"></i>Data Siswa
        </h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td width="40%">Nama Lengkap</td>
                <td>: <strong><?php echo $data['nama_lengkap']; ?></strong></td>
            </tr>
            <tr>
                <td>NISN</td>
                <td>: <?php echo $data['nisn']; ?></td>
            </tr>
            <tr>
                <td>NIK</td>
                <td>: <?php echo $data['nik']; ?></td>
            </tr>
            <tr>
                <td>Tempat, Tgl Lahir</td>
                <td>: <?php echo $data['tempat_lahir'] . ', ' . tanggal_indonesia($data['tanggal_lahir']); ?></td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td>: <?php echo $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
            </tr>
            <tr>
                <td>Asal Sekolah</td>
                <td>: <?php echo $data['asal_sekolah']; ?></td>
            </tr>
            <tr>
                <td>No. Telepon</td>
                <td>: <?php echo $data['no_telepon']; ?></td>
            </tr>
        </table>
        
        <h6 class="fw-bold text-primary mb-3 mt-4">
            <i class="fas fa-users me-2"></i>Data Orang Tua
        </h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td width="40%">Nama Ayah</td>
                <td>: <?php echo $data['nama_ayah'] ?? '-'; ?></td>
            </tr>
            <tr>
                <td>Pekerjaan Ayah</td>
                <td>: <?php echo $data['pekerjaan_ayah'] ?? '-'; ?></td>
            </tr>
            <tr>
                <td>Nama Ibu</td>
                <td>: <?php echo $data['nama_ibu'] ?? '-'; ?></td>
            </tr>
            <tr>
                <td>Pekerjaan Ibu</td>
                <td>: <?php echo $data['pekerjaan_ibu'] ?? '-'; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">
            <i class="fas fa-school me-2"></i>Data Pendaftaran
        </h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td width="40%">No. Pendaftaran</td>
                <td>: <strong><?php echo $data['nomor_pendaftaran']; ?></strong></td>
            </tr>
            <tr>
                <td>Sekolah Tujuan</td>
                <td>: <?php echo $data['nama_sekolah']; ?></td>
            </tr>
            <tr>
                <td>Jalur</td>
                <td>: <span class="badge bg-info"><?php echo $data['nama_jalur']; ?></span></td>
            </tr>
            <tr>
                <td>Nilai Rata-rata</td>
                <td>: <strong><?php echo number_format($data['nilai_rapor_rata'], 2); ?></strong></td>
            </tr>
            <tr>
                <td>Jarak</td>
                <td>: <?php echo $data['jarak_rumah_sekolah']; ?> km</td>
            </tr>
            <tr>
                <td>Tanggal Daftar</td>
                <td>: <?php echo tanggal_indonesia(date('Y-m-d', strtotime($data['tanggal_daftar']))); ?></td>
            </tr>
            <tr>
                <td>Status Berkas</td>
                <td>: <span class="badge bg-<?php echo $data['status_berkas'] == 'lengkap' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($data['status_berkas']); ?>
                </span></td>
            </tr>
        </table>
    </div>
</div>

<hr class="my-4">

<!-- Form Verifikasi (Semua dalam satu form) -->
<form method="POST" action="index.php">
    <input type="hidden" name="id_pendaftaran" value="<?php echo $id_pendaftaran; ?>">
    
    <!-- Verifikasi Dokumen -->
    <h6 class="fw-bold text-primary mb-3">
        <i class="fas fa-file-alt me-2"></i>Verifikasi Dokumen (<?php echo mysqli_num_rows($dokumen_list); ?>/4)
    </h6>
    <div class="list-group mb-4">
        <?php 
        $doc_index = 0;
        while ($dok = mysqli_fetch_assoc($dokumen_list)): 
        $kategori_class = match($dok['kategori']) {
            'umum' => 'primary',
            'afirmasi' => 'success',
            'prestasi' => 'warning',
            'mutasi' => 'danger',
            default => 'secondary'
        };
        ?>
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <span class="badge bg-<?php echo $kategori_class; ?> me-2"><?php echo ucfirst($dok['kategori']); ?></span>
                    <strong><?php echo ucwords(str_replace('_', ' ', $dok['jenis_dokumen'])); ?></strong>
                    <br>
                    <small class="text-muted"><?php echo $dok['nama_file']; ?></small>
                    <a href="../../uploads/dokumen/<?php echo $dok['nama_file']; ?>" target="_blank" 
                       class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-eye"></i> Lihat
                    </a>
                </div>
                <div class="col-md-4">
                    <input type="hidden" name="doc_id_<?php echo $doc_index; ?>" value="<?php echo $dok['id_dokumen_siswa']; ?>">
                    <select class="form-select form-select-sm" name="doc_status_<?php echo $doc_index; ?>">
                        <option value="valid" <?php echo $dok['status_verifikasi'] == 'valid' ? 'selected' : ''; ?>>
                            ✅ Valid
                        </option>
                        <option value="pending" <?php echo $dok['status_verifikasi'] == 'pending' ? 'selected' : ''; ?>>
                            ⏳ Pending
                        </option>
                        <option value="tidak_valid" <?php echo $dok['status_verifikasi'] == 'tidak_valid' ? 'selected' : ''; ?>>
                            ❌ Tidak Valid
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" name="doc_catatan_<?php echo $doc_index; ?>" 
                           placeholder="Catatan..." value="<?php echo $dok['catatan'] ?? ''; ?>">
                </div>
            </div>
        </div>
        <?php 
        $doc_index++;
        endwhile; 
        ?>
    </div>
    <input type="hidden" name="doc_count" value="<?php echo $doc_index; ?>">
    
    <!-- Verifikasi Pendaftaran -->
    <h6 class="fw-bold text-primary mb-3">
        <i class="fas fa-check-circle me-2"></i>Verifikasi Pendaftaran
    </h6>
    
    <div class="mb-3">
        <label class="form-label">Status Verifikasi *</label>
        <select class="form-select" name="status" required>
            <option value="terverifikasi" <?php echo $data['status_verifikasi'] == 'terverifikasi' ? 'selected' : ''; ?>>
                ✅ Terverifikasi (Lolos verifikasi)
            </option>
            <option value="ditolak" <?php echo $data['status_verifikasi'] == 'ditolak' ? 'selected' : ''; ?>>
                ❌ Ditolak (Tidak lolos verifikasi)
            </option>
            <option value="pending" <?php echo $data['status_verifikasi'] == 'pending' ? 'selected' : ''; ?>>
                ⏳ Pending (Masih dalam review)
            </option>
        </select>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Catatan/Keterangan</label>
        <textarea class="form-control" name="catatan" rows="2" 
                  placeholder="Berikan catatan jika ditolak..."><?php echo $data['catatan_verifikasi']; ?></textarea>
    </div>
    
    <div class="text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Tutup
        </button>
        <button type="submit" name="verify" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Simpan Verifikasi
        </button>
    </div>
</form>
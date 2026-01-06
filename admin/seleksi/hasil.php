<?php
/**
 * File: admin/seleksi/hasil.php
 * Deskripsi: Lihat hasil seleksi lengkap
 */

session_start();
require_once '../../config/database.php';

check_admin();

$tahun_ajaran = TAHUN_AJARAN;

// Get operator's school filter
$operator_sekolah = get_sekolah_filter();

// For operators, force their school
$id_sekolah = $operator_sekolah ? $operator_sekolah : (isset($_GET['id_sekolah']) ? (int)$_GET['id_sekolah'] : 0);
$id_jalur = isset($_GET['id_jalur']) ? (int)$_GET['id_jalur'] : 0;

// Ambil daftar sekolah (only for super_admin)
$sql_sekolah = "SELECT * FROM sekolah WHERE status = 'aktif' ORDER BY nama_sekolah";
$sekolah_list = mysqli_query($conn, $sql_sekolah);

// Query hasil seleksi - dengan prepared statement yang benar
$params = [$tahun_ajaran];
$types = "s";

$sql = "SELECT hs.*, p.nomor_pendaftaran, p.nilai_rapor_rata, p.jarak_rumah_sekolah,
        s.nama_lengkap, s.nisn, sch.nama_sekolah, j.nama_jalur
        FROM hasil_seleksi hs
        JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
        JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
        WHERE p.tahun_ajaran = ?";

if ($id_sekolah > 0) {
    $sql .= " AND p.id_sekolah = ?";
    $params[] = $id_sekolah;
    $types .= "i";
}
if ($id_jalur > 0) {
    $sql .= " AND p.id_jalur = ?";
    $params[] = $id_jalur;
    $types .= "i";
}

$sql .= " ORDER BY hs.ranking ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$hasil_list = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Seleksi - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ppdb-smp/assets/css/admin.css">
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
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Hasil Seleksi PPDB
                        </h4>
                        <button onclick="window.print()" class="btn btn-light btn-sm">
                            <i class="fas fa-print me-2"></i>Cetak
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Filter -->
                        <div class="row mb-3">
                            <?php if (!$operator_sekolah): // Only show school filter for super_admin ?>
                            <div class="col-md-6">
                                <label class="form-label">Filter Sekolah:</label>
                                <select class="form-select" onchange="window.location.href='?id_sekolah=' + this.value + '&id_jalur=<?php echo $id_jalur; ?>'">
                                    <option value="0">Semua Sekolah</option>
                                    <?php 
                                    mysqli_data_seek($sekolah_list, 0);
                                    while ($sch = mysqli_fetch_assoc($sekolah_list)): 
                                    ?>
                                    <option value="<?php echo $sch['id_sekolah']; ?>" <?php echo $id_sekolah == $sch['id_sekolah'] ? 'selected' : ''; ?>>
                                        <?php echo $sch['nama_sekolah']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="<?php echo $operator_sekolah ? 'col-12' : 'col-md-6'; ?>">
                                <label class="form-label">Filter Jalur:</label>
                                <select class="form-select" onchange="window.location.href='?id_sekolah=<?php echo $id_sekolah; ?>&id_jalur=' + this.value">
                                    <option value="0">Semua Jalur</option>
                                    <option value="1" <?php echo $id_jalur == 1 ? 'selected' : ''; ?>>Zonasi</option>
                                    <option value="2" <?php echo $id_jalur == 2 ? 'selected' : ''; ?>>Prestasi</option>
                                    <option value="3" <?php echo $id_jalur == 3 ? 'selected' : ''; ?>>Afirmasi</option>
                                    <option value="4" <?php echo $id_jalur == 4 ? 'selected' : ''; ?>>Mutasi</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Tabel Hasil -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ranking</th>
                                        <th>No. Pendaftaran</th>
                                        <th>Nama</th>
                                        <th>NISN</th>
                                        <th>Sekolah</th>
                                        <th>Jalur</th>
                                        <th>Nilai/Jarak</th>
                                        <th>Skor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($hasil = mysqli_fetch_assoc($hasil_list)): 
                                    ?>
                                    <tr class="<?php echo $hasil['status_seleksi'] == 'lulus' ? 'table-success' : ''; ?>">
                                        <td class="fw-bold">#<?php echo $hasil['ranking']; ?></td>
                                        <td><?php echo $hasil['nomor_pendaftaran']; ?></td>
                                        <td><?php echo $hasil['nama_lengkap']; ?></td>
                                        <td><?php echo $hasil['nisn']; ?></td>
                                        <td><?php echo $hasil['nama_sekolah']; ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $hasil['nama_jalur']; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($hasil['nama_jalur'] == 'Prestasi') {
                                                echo number_format($hasil['nilai_rapor_rata'], 2);
                                            } else {
                                                echo $hasil['jarak_rumah_sekolah'] . ' km';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo number_format($hasil['skor_akhir'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $hasil['status_seleksi'] == 'lulus' ? 'success' : 
                                                    ($hasil['status_seleksi'] == 'cadangan' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo strtoupper($hasil['status_seleksi']); ?>
                                            </span>
                                            
                                            <?php if ($hasil['status_seleksi'] == 'lulus' && !empty($hasil['pilihan_diterima'])): ?>
                                            <span class="badge bg-dark ms-1">
                                                PILIHAN <?php echo $hasil['pilihan_diterima']; ?>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                    $no++;
                                    endwhile; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (mysqli_num_rows($hasil_list) == 0): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada hasil seleksi</h5>
                            <p class="text-muted">Silakan jalankan proses seleksi terlebih dahulu</p>
                            <a href="proses.php" class="btn btn-primary">
                                <i class="fas fa-cogs me-2"></i>Proses Seleksi
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
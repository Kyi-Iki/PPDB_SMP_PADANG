<?php
/**
 * File: admin/seleksi/ranking.php
 * Deskripsi: Lihat ranking pendaftar per jalur per sekolah
 * - Super Admin: Dapat melihat semua sekolah
 * - Operator: Hanya dapat melihat sekolah sendiri
 */

session_start();
require_once '../../config/database.php';

check_admin();

$tahun_ajaran = TAHUN_AJARAN;

// Get operator's school filter for RBAC
$operator_sekolah = get_sekolah_filter();

// Filters
$id_sekolah = isset($_GET['sekolah']) ? (int)$_GET['sekolah'] : 0;
$id_jalur = isset($_GET['jalur']) ? (int)$_GET['jalur'] : 1; // Default: Zonasi

// For operators, force their school
if ($operator_sekolah) {
    $id_sekolah = $operator_sekolah;
}

// Get school list for filter (only for super_admin)
$sekolah_list = mysqli_query($conn, "SELECT id_sekolah, nama_sekolah FROM sekolah ORDER BY nama_sekolah");

// Get jalur list
$jalur_list = mysqli_query($conn, "SELECT id_jalur, nama_jalur FROM jalur_ppdb ORDER BY id_jalur");

// Build ranking query
$sql = "SELECT 
            p.id_pendaftaran,
            p.nomor_pendaftaran,
            p.jarak_rumah_sekolah,
            p.nilai_rapor_rata,
            s.nisn,
            s.nama_lengkap,
            s.kecamatan as kecamatan_siswa,
            sch.nama_sekolah,
            j.nama_jalur,
            hs.ranking,
            hs.status_seleksi,
            hs.pilihan_diterima,
            hs.skor_akhir
        FROM pendaftaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
        JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
        LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
        WHERE p.tahun_ajaran = ?
        AND p.status_verifikasi = 'terverifikasi'";

$params = [$tahun_ajaran];
$types = "s";

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

// Order by ranking criteria per jalur
$sql .= " ORDER BY 
            CASE 
                WHEN hs.ranking IS NOT NULL THEN hs.ranking 
                ELSE 99999 
            END ASC,
            CASE 
                WHEN ? = 1 THEN p.jarak_rumah_sekolah  -- Zonasi: jarak terdekat
                WHEN ? = 2 THEN -p.nilai_rapor_rata  -- Prestasi: nilai tertinggi
                ELSE p.jarak_rumah_sekolah 
            END ASC";
$params[] = $id_jalur;
$params[] = $id_jalur;
$types .= "ii";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$ranking_list = mysqli_stmt_get_result($stmt);

// Get selected school & jalur names
$selected_school_name = 'Semua Sekolah';
$selected_jalur_name = 'Semua Jalur';

if ($id_sekolah > 0) {
    $sch = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_sekolah FROM sekolah WHERE id_sekolah = $id_sekolah"));
    $selected_school_name = $sch['nama_sekolah'] ?? 'Unknown';
}
if ($id_jalur > 0) {
    $jlr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_jalur FROM jalur_ppdb WHERE id_jalur = $id_jalur"));
    $selected_jalur_name = $jlr['nama_jalur'] ?? 'Unknown';
}

// Get kuota for selected school & jalur
$kuota_info = null;
if ($id_sekolah > 0 && $id_jalur > 0) {
    $kuota_info = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT jumlah_kuota, kuota_terpakai FROM kuota_jalur 
         WHERE id_sekolah = $id_sekolah AND id_jalur = $id_jalur AND tahun_ajaran = '$tahun_ajaran'"
    ));
}

// Count statistics
$total_pendaftar = mysqli_num_rows($ranking_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking Per Jalur - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ppdb-smp/assets/css/admin.css">
    
    <style>
        .rank-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: #000; }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); color: #fff; }
        .rank-other { background: #e9ecef; color: #495057; }
        
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
        }
        .filter-card .form-select {
            background: rgba(255,255,255,0.95);
        }
        
        .stats-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stats-box h3 {
            margin: 0;
            font-weight: 700;
        }
        
        .table-ranking td {
            vertical-align: middle;
        }
        
        @media print {
            .no-print { display: none !important; }
            .filter-card { background: #f8f9fa !important; color: #000 !important; }
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar_admin.php'; ?>
    
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <div class="col-md-3 no-print">
                <?php include '../../includes/sidebar_admin.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Filter Card -->
                <div class="filter-card no-print">
                    <div class="row align-items-end">
                        <?php if (!$operator_sekolah): ?>
                        <div class="col-md-5 mb-2 mb-md-0">
                            <label class="form-label fw-bold">
                                <i class="fas fa-school me-1"></i> Pilih Sekolah:
                            </label>
                            <select class="form-select" id="filterSekolah" 
                                    onchange="applyFilter()">
                                <option value="0">-- Semua Sekolah --</option>
                                <?php 
                                mysqli_data_seek($sekolah_list, 0);
                                while ($sch = mysqli_fetch_assoc($sekolah_list)): 
                                ?>
                                <option value="<?php echo $sch['id_sekolah']; ?>" 
                                        <?php echo $id_sekolah == $sch['id_sekolah'] ? 'selected' : ''; ?>>
                                    <?php echo $sch['nama_sekolah']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-<?php echo $operator_sekolah ? '8' : '5'; ?> mb-2 mb-md-0">
                            <label class="form-label fw-bold">
                                <i class="fas fa-route me-1"></i> Pilih Jalur:
                            </label>
                            <select class="form-select" id="filterJalur" 
                                    onchange="applyFilter()">
                                <?php 
                                mysqli_data_seek($jalur_list, 0);
                                while ($jlr = mysqli_fetch_assoc($jalur_list)): 
                                ?>
                                <option value="<?php echo $jlr['id_jalur']; ?>" 
                                        <?php echo $id_jalur == $jlr['id_jalur'] ? 'selected' : ''; ?>>
                                    <?php echo $jlr['nama_jalur']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button onclick="window.print()" class="btn btn-light w-100">
                                <i class="fas fa-print me-1"></i> Cetak
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stats-box">
                            <h3 class="text-primary"><?php echo $total_pendaftar; ?></h3>
                            <small class="text-muted">Total Pendaftar</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <h3 class="text-success"><?php echo $kuota_info['jumlah_kuota'] ?? '-'; ?></h3>
                            <small class="text-muted">Kuota Tersedia</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <h3 class="text-info"><?php echo $kuota_info['kuota_terpakai'] ?? '-'; ?></h3>
                            <small class="text-muted">Kuota Terpakai</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <h3 class="text-warning">
                                <?php 
                                if ($kuota_info) {
                                    $sisa = $kuota_info['jumlah_kuota'] - $kuota_info['kuota_terpakai'];
                                    echo $sisa >= 0 ? $sisa : 0;
                                } else {
                                    echo '-';
                                }
                                ?>
                            </h3>
                            <small class="text-muted">Sisa Kuota</small>
                        </div>
                    </div>
                </div>
                
                <!-- Ranking Table -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Ranking Jalur <?php echo $selected_jalur_name; ?>
                            <?php if ($id_sekolah > 0): ?>
                            - <?php echo $selected_school_name; ?>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($total_pendaftar == 0): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada data pendaftar untuk filter ini.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-ranking mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 70px;">Ranking</th>
                                        <th>NISN</th>
                                        <th>Nama Siswa</th>
                                        <th>Kecamatan</th>
                                        <?php if ($id_jalur == 1): // Zonasi ?>
                                        <th class="text-end">Jarak (km)</th>
                                        <?php elseif ($id_jalur == 2): // Prestasi ?>
                                        <th class="text-end">Nilai Rapor</th>
                                        <?php else: ?>
                                        <th class="text-end">Skor</th>
                                        <?php endif; ?>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    mysqli_data_seek($ranking_list, 0);
                                    while ($row = mysqli_fetch_assoc($ranking_list)): 
                                        $display_rank = $row['ranking'] ?? $rank;
                                        $rank_class = 'rank-other';
                                        if ($display_rank == 1) $rank_class = 'rank-1';
                                        elseif ($display_rank == 2) $rank_class = 'rank-2';
                                        elseif ($display_rank == 3) $rank_class = 'rank-3';
                                    ?>
                                    <tr class="<?php echo $row['status_seleksi'] == 'lulus' ? 'table-success' : ''; ?>">
                                        <td class="text-center">
                                            <span class="rank-badge <?php echo $rank_class; ?>">
                                                <?php echo $display_rank; ?>
                                            </span>
                                        </td>
                                        <td><code><?php echo $row['nisn']; ?></code></td>
                                        <td>
                                            <strong><?php echo $row['nama_lengkap']; ?></strong>
                                            <?php if ($row['pilihan_diterima']): ?>
                                            <br><small class="text-muted">Pilihan <?php echo $row['pilihan_diterima']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $row['kecamatan_siswa']; ?></td>
                                        <td class="text-end">
                                            <?php 
                                            if ($id_jalur == 1) {
                                                // Zonasi - show distance
                                                echo number_format($row['jarak_rumah_sekolah'], 2) . ' km';
                                            } elseif ($id_jalur == 2) {
                                                // Prestasi - show grades
                                                echo number_format($row['nilai_rapor_rata'], 2);
                                            } else {
                                                // Others - show score
                                                echo number_format($row['skor_akhir'] ?? 0, 2);
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['status_seleksi'] == 'lulus'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>LULUS
                                            </span>
                                            <?php elseif ($row['status_seleksi'] == 'tidak_lulus'): ?>
                                            <span class="badge bg-danger">TIDAK LULUS</span>
                                            <?php elseif ($row['status_seleksi'] == 'cadangan'): ?>
                                            <span class="badge bg-warning">CADANGAN</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">PENDING</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                    $rank++;
                                    endwhile; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="mt-3 text-muted small no-print">
                    <i class="fas fa-info-circle me-1"></i>
                    <?php if ($id_jalur == 1): ?>
                    Jalur Zonasi: Diurutkan berdasarkan jarak terdekat ke sekolah.
                    <?php elseif ($id_jalur == 2): ?>
                    Jalur Prestasi: Diurutkan berdasarkan nilai rapor tertinggi.
                    <?php elseif ($id_jalur == 3): ?>
                    Jalur Afirmasi: Diurutkan berdasarkan dokumen pendukung terverifikasi.
                    <?php elseif ($id_jalur == 4): ?>
                    Jalur Mutasi: Diurutkan berdasarkan dokumen mutasi terverifikasi.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyFilter() {
            const sekolah = document.getElementById('filterSekolah')?.value || 0;
            const jalur = document.getElementById('filterJalur').value;
            window.location.href = `?sekolah=${sekolah}&jalur=${jalur}`;
        }
    </script>
</body>
</html>

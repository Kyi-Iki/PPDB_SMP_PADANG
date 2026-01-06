<?php
/**
 * File: admin/laporan/index.php
 * Deskripsi: Halaman laporan PPDB untuk admin
 * Fitur: Export data pendaftar, statistik per jalur, rekap hasil seleksi
 */

session_start();
require_once '../../config/database.php';

check_admin();

$tahun_ajaran = TAHUN_AJARAN;

// Get operator's school filter
$operator_sekolah = get_sekolah_filter();
$sekolah_condition = $operator_sekolah ? " AND id_sekolah = $operator_sekolah" : "";

// Statistik Keseluruhan (filtered for operators)
$sql_stats = "SELECT 
    COUNT(*) as total_pendaftar,
    SUM(CASE WHEN status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
    SUM(CASE WHEN status_verifikasi = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status_verifikasi = 'ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM pendaftaran WHERE tahun_ajaran = ?" . $sekolah_condition;
$stmt = mysqli_prepare($conn, $sql_stats);
mysqli_stmt_bind_param($stmt, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Statistik per Jalur (filtered for operators)
$jalur_condition = $operator_sekolah ? " AND p.id_sekolah = $operator_sekolah" : "";
$sql_jalur = "SELECT j.nama_jalur, j.kode_jalur,
              COUNT(p.id_pendaftaran) as total,
              SUM(CASE WHEN p.status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as verified,
              SUM(CASE WHEN hs.status_seleksi = 'lulus' THEN 1 ELSE 0 END) as lulus
              FROM jalur_ppdb j
              LEFT JOIN pendaftaran p ON j.id_jalur = p.id_jalur AND p.tahun_ajaran = ?" . $jalur_condition . "
              LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
              GROUP BY j.id_jalur";
$stmt_jalur = mysqli_prepare($conn, $sql_jalur);
mysqli_stmt_bind_param($stmt_jalur, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt_jalur);
$jalur_stats = mysqli_stmt_get_result($stmt_jalur);

// Statistik per Sekolah (filtered for operators - only their school)
if ($operator_sekolah) {
    $sql_sekolah = "SELECT s.nama_sekolah, s.npsn, 
                    (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as kuota_dynamic,
                    COUNT(p.id_pendaftaran) as total_pendaftar,
                    SUM(CASE WHEN p.status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
                    SUM(CASE WHEN hs.status_seleksi = 'lulus' THEN 1 ELSE 0 END) as lulus
                    FROM sekolah s
                    LEFT JOIN pendaftaran p ON s.id_sekolah = p.id_sekolah AND p.tahun_ajaran = ?
                    LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                    WHERE s.id_sekolah = ?
                    GROUP BY s.id_sekolah";
    $stmt_sekolah = mysqli_prepare($conn, $sql_sekolah);
    mysqli_stmt_bind_param($stmt_sekolah, "si", $tahun_ajaran, $operator_sekolah);
} else {
    $sql_sekolah = "SELECT s.nama_sekolah, s.npsn, 
                    (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as kuota_dynamic,
                    COUNT(p.id_pendaftaran) as total_pendaftar,
                    SUM(CASE WHEN p.status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
                    SUM(CASE WHEN hs.status_seleksi = 'lulus' THEN 1 ELSE 0 END) as lulus
                    FROM sekolah s
                    LEFT JOIN pendaftaran p ON s.id_sekolah = p.id_sekolah AND p.tahun_ajaran = ?
                    LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                    WHERE s.status = 'aktif'
                    GROUP BY s.id_sekolah
                    ORDER BY s.nama_sekolah";
    $stmt_sekolah = mysqli_prepare($conn, $sql_sekolah);
    mysqli_stmt_bind_param($stmt_sekolah, "s", $tahun_ajaran);
}
mysqli_stmt_execute($stmt_sekolah);
$sekolah_stats = mysqli_stmt_get_result($stmt_sekolah);

// Export CSV jika diminta
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_ppdb_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // BOM untuk Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['No', 'No Pendaftaran', 'NISN', 'NIK', 'Nama Lengkap', 'Jenis Kelamin', 
                      'Asal Sekolah', 'Sekolah Tujuan', 'Jalur', 'Nilai Rata-rata', 
                      'Jarak (km)', 'Status Verifikasi', 'Status Seleksi', 'Ranking']);
    
    // Data (filtered for operators)
    $export_sekolah_filter = $operator_sekolah ? " AND p.id_sekolah = $operator_sekolah" : "";
    $sql_export = "SELECT p.nomor_pendaftaran, s.nisn, s.nik, s.nama_lengkap, s.jenis_kelamin,
                   s.asal_sekolah, sch.nama_sekolah, j.nama_jalur, p.nilai_rapor_rata,
                   p.jarak_rumah_sekolah, p.status_verifikasi, 
                   COALESCE(hs.status_seleksi, '-') as status_seleksi,
                   COALESCE(hs.ranking, '-') as ranking
                   FROM pendaftaran p
                   JOIN siswa s ON p.id_siswa = s.id_siswa
                   JOIN sekolah sch ON p.id_sekolah = sch.id_sekolah
                   JOIN jalur_ppdb j ON p.id_jalur = j.id_jalur
                   LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                   WHERE p.tahun_ajaran = ?" . $export_sekolah_filter . "
                   ORDER BY sch.nama_sekolah, j.nama_jalur, hs.ranking";
    
    $stmt_export = mysqli_prepare($conn, $sql_export);
    mysqli_stmt_bind_param($stmt_export, "s", $tahun_ajaran);
    mysqli_stmt_execute($stmt_export);
    $result = mysqli_stmt_get_result($stmt_export);
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $no++,
            $row['nomor_pendaftaran'],
            $row['nisn'],
            $row['nik'],
            $row['nama_lengkap'],
            $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan',
            $row['asal_sekolah'],
            $row['nama_sekolah'],
            $row['nama_jalur'],
            number_format($row['nilai_rapor_rata'], 2),
            $row['jarak_rumah_sekolah'],
            ucfirst($row['status_verifikasi']),
            $row['status_seleksi'] != '-' ? strtoupper($row['status_seleksi']) : '-',
            $row['ranking']
        ]);
    }
    
    fclose($output);
    
    log_aktivitas($_SESSION['id_user'], 'Export Laporan', 'Export data pendaftar ke CSV');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan PPDB - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .stat-icon.blue { background: #e3f2fd; color: #1976d2; }
        .stat-icon.green { background: #e8f5e9; color: #388e3c; }
        .stat-icon.orange { background: #fff3e0; color: #f57c00; }
        .stat-icon.red { background: #ffebee; color: #d32f2f; }
        
        .report-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .card { box-shadow: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <?php include '../../includes/navbar_admin.php'; ?>
    </div>
    
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <div class="col-md-3 no-print">
                <?php include '../../includes/sidebar_admin.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <div>
                        <h4 class="fw-bold mb-1">
                            <i class="fas fa-file-alt text-primary me-2"></i>
                            Laporan PPDB
                        </h4>
                        <p class="text-muted mb-0">Tahun Ajaran <?php echo $tahun_ajaran; ?></p>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="fas fa-print me-2"></i>Cetak
                        </button>
                        <a href="export_pdf.php" target="_blank" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </a>
                        <a href="?export=csv" class="btn btn-success">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </a>
                    </div>
                </div>
                
                <!-- Statistik Keseluruhan -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="fw-bold text-primary mb-1"><?php echo number_format($stats['total_pendaftar']); ?></h3>
                            <p class="text-muted mb-0">Total Pendaftar</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="fw-bold text-success mb-1"><?php echo number_format($stats['terverifikasi']); ?></h3>
                            <p class="text-muted mb-0">Terverifikasi</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="fw-bold text-warning mb-1"><?php echo number_format($stats['pending']); ?></h3>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h3 class="fw-bold text-danger mb-1"><?php echo number_format($stats['ditolak']); ?></h3>
                            <p class="text-muted mb-0">Ditolak</p>
                        </div>
                    </div>
                </div>
                
                <!-- Laporan per Jalur -->
                <div class="report-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-route me-2"></i>
                            Rekap per Jalur PPDB
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Jalur</th>
                                        <th class="text-center">Total Pendaftar</th>
                                        <th class="text-center">Terverifikasi</th>
                                        <th class="text-center">Lulus</th>
                                        <th class="text-center">% Kelulusan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_all = 0;
                                    $verified_all = 0;
                                    $lulus_all = 0;
                                    while ($j = mysqli_fetch_assoc($jalur_stats)): 
                                        $total_all += $j['total'];
                                        $verified_all += $j['verified'];
                                        $lulus_all += $j['lulus'];
                                        $persen = $j['verified'] > 0 ? round(($j['lulus'] / $j['verified']) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-info"><?php echo $j['kode_jalur']; ?></span></td>
                                        <td class="fw-bold"><?php echo $j['nama_jalur']; ?></td>
                                        <td class="text-center"><?php echo $j['total']; ?></td>
                                        <td class="text-center"><?php echo $j['verified']; ?></td>
                                        <td class="text-center text-success fw-bold"><?php echo $j['lulus']; ?></td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $persen; ?>%">
                                                    <?php echo $persen; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot class="table-secondary">
                                    <tr class="fw-bold">
                                        <td colspan="2">TOTAL</td>
                                        <td class="text-center"><?php echo $total_all; ?></td>
                                        <td class="text-center"><?php echo $verified_all; ?></td>
                                        <td class="text-center text-success"><?php echo $lulus_all; ?></td>
                                        <td class="text-center">
                                            <?php echo $verified_all > 0 ? round(($lulus_all / $verified_all) * 100, 1) : 0; ?>%
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Laporan per Sekolah -->
                <div class="report-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-school me-2"></i>
                            Rekap per Sekolah
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Sekolah</th>
                                        <th>NPSN</th>
                                        <th class="text-center">Kuota</th>
                                        <th class="text-center">Pendaftar</th>
                                        <th class="text-center">Terverifikasi</th>
                                        <th class="text-center">Lulus</th>
                                        <th class="text-center">Sisa Kuota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($s = mysqli_fetch_assoc($sekolah_stats)): 
                                        $kuota_fix = $s['kuota_dynamic'] ?? 0;
                                        $sisa = $kuota_fix - ($s['lulus'] ?? 0);
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td class="fw-bold"><?php echo $s['nama_sekolah']; ?></td>
                                        <td><?php echo $s['npsn']; ?></td>
                                        <td class="text-center"><?php echo $kuota_fix; ?></td>
                                        <td class="text-center"><?php echo $s['total_pendaftar']; ?></td>
                                        <td class="text-center"><?php echo $s['terverifikasi']; ?></td>
                                        <td class="text-center text-success fw-bold"><?php echo $s['lulus'] ?? 0; ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $sisa > 0 ? 'warning' : 'success'; ?>">
                                                <?php echo $sisa > 0 ? $sisa : 'Penuh'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Laporan -->
                <div class="text-center mt-4 text-muted">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Laporan digenerate pada: <?php echo date('d F Y H:i:s'); ?> WIB
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

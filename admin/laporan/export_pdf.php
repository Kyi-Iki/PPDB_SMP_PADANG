<?php
/**
 * File: admin/laporan/export_pdf.php
 * Deskripsi: Generate PDF laporan PPDB
 * Menggunakan HTML to PDF tanpa library eksternal
 */

session_start();
require_once '../../config/database.php';
check_admin();

$tahun_ajaran = TAHUN_AJARAN;
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$id_sekolah = isset($_GET['sekolah']) ? (int)$_GET['sekolah'] : 0;
$id_jalur = isset($_GET['jalur']) ? (int)$_GET['jalur'] : 0;

// Set header untuk print-friendly HTML
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan PPDB - <?php echo $tahun_ajaran; ?></title>
    <style>
        * {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
        }
        
        body {
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 16pt;
            margin: 0;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 14pt;
            margin: 5px 0;
        }
        
        .header p {
            margin: 3px 0;
            font-size: 10pt;
            color: #555;
        }
        
        .section-title {
            background: #f0f0f0;
            padding: 8px 15px;
            font-weight: bold;
            margin: 20px 0 10px;
            border-left: 4px solid #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 6px 10px;
            text-align: left;
        }
        
        th {
            background: #e0e0e0;
            font-weight: bold;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        
        .stats-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #333;
            border-radius: 8px;
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 24pt;
            color: #333;
        }
        
        .stat-box p {
            margin: 5px 0 0;
            font-size: 10pt;
            color: #666;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 10pt;
            color: #666;
        }
        
        .signature {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }
        
        .signature-box {
            text-align: center;
            width: 250px;
        }
        
        .signature-line {
            margin-top: 80px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        
        @media print {
            body { margin: 0; padding: 10mm; }
            .no-print { display: none !important; }
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 25px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12pt;
        }
        
        .print-btn:hover {
            background: #0b5ed7;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        🖨️ Cetak / Print PDF
    </button>
    
    <!-- Header -->
    <div class="header">
        <h1>Laporan Penerimaan Peserta Didik Baru (PPDB)</h1>
        <h2>Tahun Ajaran <?php echo $tahun_ajaran; ?></h2>
        <p>Dicetak pada: <?php echo date('d F Y H:i:s'); ?> WIB</p>
    </div>
    
    <?php
    // Statistik Keseluruhan
    $sql_stats = "SELECT 
        COUNT(*) as total_pendaftar,
        SUM(CASE WHEN status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
        SUM(CASE WHEN status_verifikasi = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_verifikasi = 'ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pendaftaran WHERE tahun_ajaran = ?";
    $stmt = mysqli_prepare($conn, $sql_stats);
    mysqli_stmt_bind_param($stmt, "s", $tahun_ajaran);
    mysqli_stmt_execute($stmt);
    $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    // Hitung yang lulus
    $sql_lulus = "SELECT COUNT(*) as lulus FROM hasil_seleksi hs 
                  JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran
                  WHERE p.tahun_ajaran = ? AND hs.status_seleksi = 'lulus' AND hs.dipublikasikan = 1";
    $stmt_lulus = mysqli_prepare($conn, $sql_lulus);
    mysqli_stmt_bind_param($stmt_lulus, "s", $tahun_ajaran);
    mysqli_stmt_execute($stmt_lulus);
    $lulus_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_lulus))['lulus'];
    ?>
    
    <!-- Statistik -->
    <div class="section-title">I. RINGKASAN STATISTIK</div>
    <div class="stats-grid">
        <div class="stat-box">
            <h3><?php echo number_format($stats['total_pendaftar']); ?></h3>
            <p>Total Pendaftar</p>
        </div>
        <div class="stat-box">
            <h3><?php echo number_format($stats['terverifikasi']); ?></h3>
            <p>Terverifikasi</p>
        </div>
        <div class="stat-box">
            <h3><?php echo number_format($stats['pending']); ?></h3>
            <p>Pending</p>
        </div>
        <div class="stat-box">
            <h3><?php echo number_format($lulus_count); ?></h3>
            <p>Diterima</p>
        </div>
    </div>
    
    <!-- Rekap per Jalur -->
    <div class="section-title">II. REKAP PER JALUR PPDB</div>
    <?php
    $sql_jalur = "SELECT j.nama_jalur, j.kode_jalur,
                  COUNT(p.id_pendaftaran) as total,
                  SUM(CASE WHEN p.status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as verified,
                  SUM(CASE WHEN hs.status_seleksi = 'lulus' AND hs.dipublikasikan = 1 THEN 1 ELSE 0 END) as lulus
                  FROM jalur_ppdb j
                  LEFT JOIN pendaftaran p ON j.id_jalur = p.id_jalur AND p.tahun_ajaran = ?
                  LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                  GROUP BY j.id_jalur
                  ORDER BY j.id_jalur";
    $stmt_jalur = mysqli_prepare($conn, $sql_jalur);
    mysqli_stmt_bind_param($stmt_jalur, "s", $tahun_ajaran);
    mysqli_stmt_execute($stmt_jalur);
    $jalur_stats = mysqli_stmt_get_result($stmt_jalur);
    ?>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Jalur</th>
                <th class="text-center">Total Pendaftar</th>
                <th class="text-center">Terverifikasi</th>
                <th class="text-center">Diterima</th>
                <th class="text-center">% Kelulusan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_all = 0; $verified_all = 0; $lulus_all = 0;
            while ($j = mysqli_fetch_assoc($jalur_stats)): 
                $total_all += $j['total'];
                $verified_all += $j['verified'];
                $lulus_all += $j['lulus'];
                $persen = $j['verified'] > 0 ? round(($j['lulus'] / $j['verified']) * 100, 1) : 0;
            ?>
            <tr>
                <td class="text-center"><?php echo $j['kode_jalur']; ?></td>
                <td><?php echo $j['nama_jalur']; ?></td>
                <td class="text-center"><?php echo $j['total']; ?></td>
                <td class="text-center"><?php echo $j['verified']; ?></td>
                <td class="text-center fw-bold text-success"><?php echo $j['lulus']; ?></td>
                <td class="text-center"><?php echo $persen; ?>%</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="background: #d0d0d0;">
                <td colspan="2" class="fw-bold">TOTAL</td>
                <td class="text-center fw-bold"><?php echo $total_all; ?></td>
                <td class="text-center fw-bold"><?php echo $verified_all; ?></td>
                <td class="text-center fw-bold text-success"><?php echo $lulus_all; ?></td>
                <td class="text-center fw-bold">
                    <?php echo $verified_all > 0 ? round(($lulus_all / $verified_all) * 100, 1) : 0; ?>%
                </td>
            </tr>
        </tfoot>
    </table>
    
    <!-- Rekap per Sekolah -->
    <div class="section-title">III. REKAP PER SEKOLAH</div>
    <?php
    $sql_sekolah = "SELECT s.nama_sekolah, s.npsn, s.kuota_total,
                    COUNT(p.id_pendaftaran) as total_pendaftar,
                    SUM(CASE WHEN p.status_verifikasi = 'terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
                    SUM(CASE WHEN hs.status_seleksi = 'lulus' AND hs.dipublikasikan = 1 THEN 1 ELSE 0 END) as lulus
                    FROM sekolah s
                    LEFT JOIN pendaftaran p ON s.id_sekolah = p.id_sekolah AND p.tahun_ajaran = ?
                    LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                    WHERE s.status = 'aktif'
                    GROUP BY s.id_sekolah
                    ORDER BY s.nama_sekolah";
    $stmt_sekolah = mysqli_prepare($conn, $sql_sekolah);
    mysqli_stmt_bind_param($stmt_sekolah, "s", $tahun_ajaran);
    mysqli_stmt_execute($stmt_sekolah);
    $sekolah_stats = mysqli_stmt_get_result($stmt_sekolah);
    ?>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Sekolah</th>
                <th>NPSN</th>
                <th class="text-center">Kuota</th>
                <th class="text-center">Pendaftar</th>
                <th class="text-center">Diterima</th>
                <th class="text-center">Sisa</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_kuota = 0; $total_daftar = 0; $total_terima = 0;
            while ($s = mysqli_fetch_assoc($sekolah_stats)): 
                $sisa = $s['kuota_total'] - ($s['lulus'] ?? 0);
                $total_kuota += $s['kuota_total'];
                $total_daftar += $s['total_pendaftar'];
                $total_terima += $s['lulus'];
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $s['nama_sekolah']; ?></td>
                <td><?php echo $s['npsn']; ?></td>
                <td class="text-center"><?php echo $s['kuota_total']; ?></td>
                <td class="text-center"><?php echo $s['total_pendaftar']; ?></td>
                <td class="text-center fw-bold text-success"><?php echo $s['lulus'] ?? 0; ?></td>
                <td class="text-center"><?php echo $sisa > 0 ? $sisa : '-'; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="background: #d0d0d0;">
                <td colspan="3" class="fw-bold">TOTAL</td>
                <td class="text-center fw-bold"><?php echo $total_kuota; ?></td>
                <td class="text-center fw-bold"><?php echo $total_daftar; ?></td>
                <td class="text-center fw-bold text-success"><?php echo $total_terima; ?></td>
                <td class="text-center fw-bold"><?php echo $total_kuota - $total_terima; ?></td>
            </tr>
        </tfoot>
    </table>
    
    <!-- Signature -->
    <div class="signature">
        <div class="signature-box">
            <p><?php echo date('d F Y'); ?></p>
            <p>Panitia PPDB</p>
            <div class="signature-line">
                <p>(_______________________)</p>
                <p>NIP. </p>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis dari Sistem PPDB Online.</p>
        <p>Untuk cetak ke PDF: Klik tombol "Cetak / Print PDF" → Pilih "Save as PDF" pada dialog print.</p>
    </div>
</body>
</html>
<?php
log_aktivitas($_SESSION['id_user'], 'Export PDF Laporan', 'Generate laporan PDF');
?>

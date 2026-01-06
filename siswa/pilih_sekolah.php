<?php
/**
 * File: siswa/pilih_sekolah.php
 * Deskripsi: Form pemilihan jalur PPDB dan sekolah tujuan
 * 
 * ATURAN PENDAFTARAN:
 * - Setiap jalur memiliki jadwal berbeda
 * - Siswa hanya boleh daftar ke 1 sekolah per jalur
 * - Sistem SERENTAK: Siswa hanya bisa mendaftar 1 jalur per tahun ajaran
 * - Pilihan sekolah tidak dapat diubah setelah dikirim
 */

session_start();
require_once '../config/database.php';
require_once '../includes/csrf.php';

check_siswa();

$id_siswa = $_SESSION['id_siswa'];
$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';

// Cek kelengkapan biodata
$sql_cek = "SELECT * FROM siswa s 
            LEFT JOIN orang_tua o ON s.id_siswa = o.id_siswa 
            WHERE s.id_siswa = ?";
$stmt_cek = mysqli_prepare($conn, $sql_cek);
mysqli_stmt_bind_param($stmt_cek, "i", $id_siswa);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);
$data_siswa = mysqli_fetch_assoc($result_cek);
$siswa = $data_siswa; // Alias untuk sidebar

$biodata_incomplete = (!$data_siswa['tempat_lahir'] || !$data_siswa['nama_ayah']);

// Ambil data jalur yang tersedia untuk siswa ini
$jalur_tersedia = get_jalur_tersedia_siswa($id_siswa);

// Ambil semua pendaftaran siswa di tahun ini (termasuk sekolah pilihan 2)
$sql_semua_daftar = "SELECT p.*, jp.nama_jalur, s1.nama_sekolah, s2.nama_sekolah as nama_sekolah_2, hs.status_seleksi
                     FROM pendaftaran p
                     JOIN jalur_ppdb jp ON p.id_jalur = jp.id_jalur
                     JOIN sekolah s1 ON p.id_sekolah = s1.id_sekolah
                     LEFT JOIN sekolah s2 ON p.id_sekolah_pilihan_2 = s2.id_sekolah
                     LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
                     WHERE p.id_siswa = ? AND p.tahun_ajaran = ?
                     ORDER BY p.tanggal_daftar DESC";
$stmt_daftar = mysqli_prepare($conn, $sql_semua_daftar);
mysqli_stmt_bind_param($stmt_daftar, "is", $id_siswa, $tahun_ajaran);
mysqli_stmt_execute($stmt_daftar);
$semua_pendaftaran = mysqli_stmt_get_result($stmt_daftar);
$sudah_lulus = false;

// Cek apakah sudah lulus di salah satu jalur
$pendaftaran_list = [];
while ($pend = mysqli_fetch_assoc($semua_pendaftaran)) {
    $pendaftaran_list[] = $pend;
    if ($pend['status_seleksi'] == 'lulus') {
        $sudah_lulus = true;
        $data_lulus = $pend;
    }
}
$sudah_daftar = count($pendaftaran_list) > 0;
$pendaftaran_aktif = $sudah_daftar ? $pendaftaran_list[0] : null;

// Ambil data sekolah aktif
$sql_sekolah = "SELECT s.*, 
                (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = ?) as total_kuota
                FROM sekolah s 
                WHERE s.status = 'aktif' AND s.tahun_ajaran = ?
                ORDER BY s.nama_sekolah";
$stmt_sekolah = mysqli_prepare($conn, $sql_sekolah);
mysqli_stmt_bind_param($stmt_sekolah, "ss", $tahun_ajaran, $tahun_ajaran);
mysqli_stmt_execute($stmt_sekolah);
$sekolah_list = mysqli_stmt_get_result($stmt_sekolah);

// Proses submit pendaftaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['daftar'])) {
    // Validasi CSRF
    if (!verify_csrf()) {
        $error = "Sesi tidak valid. Silakan refresh halaman.";
    } else {
        $id_jalur = (int)clean_input($_POST['id_jalur']);
        $id_sekolah = (int)clean_input($_POST['id_sekolah']);
    $id_sekolah_2 = isset($_POST['id_sekolah_2']) && !empty($_POST['id_sekolah_2']) ? (int)clean_input($_POST['id_sekolah_2']) : NULL;

    // Validasi: Sekolah 1 & 2 tidak boleh sama
    if ($id_sekolah_2 && $id_sekolah == $id_sekolah_2) {
        $error = "Pilihan Sekolah 1 dan Sekolah 2 tidak boleh sama.";
    } else {
        $nilai_semester_1 = (float)clean_input($_POST['nilai_semester_1']);
        $nilai_semester_2 = (float)clean_input($_POST['nilai_semester_2']);
        $nilai_semester_3 = (float)clean_input($_POST['nilai_semester_3']);
        $nilai_semester_4 = (float)clean_input($_POST['nilai_semester_4']);
        $nilai_semester_5 = (float)clean_input($_POST['nilai_semester_5']);
        
        // VALIDASI JALUR
        $validasi = validasi_pendaftaran_jalur($id_siswa, $id_jalur);
        
        if (!$validasi['valid']) {
            $error = $validasi['pesan'];
        } else {
            // Hitung nilai rata-rata
            $nilai_rapor_rata = ($nilai_semester_1 + $nilai_semester_2 + $nilai_semester_3 + 
                                 $nilai_semester_4 + $nilai_semester_5) / 5;
            
            // Ambil koordinat sekolah 1
            $sql_koordinat = "SELECT latitude, longitude FROM sekolah WHERE id_sekolah = ?";
            $stmt_koordinat = mysqli_prepare($conn, $sql_koordinat);
            mysqli_stmt_bind_param($stmt_koordinat, "i", $id_sekolah);
            mysqli_stmt_execute($stmt_koordinat);
            $sekolah_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_koordinat));
            
            // Hitung jarak ke Sekolah Pilihan 1
            $jarak = 0;
            if ($sekolah_data['latitude'] && $sekolah_data['longitude'] && 
                $data_siswa['latitude'] && $data_siswa['longitude']) {
                $jarak = hitung_jarak(
                    $data_siswa['latitude'], $data_siswa['longitude'],
                    $sekolah_data['latitude'], $sekolah_data['longitude']
                );
            }
            
            // Hitung jarak ke Sekolah Pilihan 2 (jika ada)
            $jarak_2 = null;
            if ($id_sekolah_2) {
                $stmt_koordinat_2 = mysqli_prepare($conn, $sql_koordinat);
                mysqli_stmt_bind_param($stmt_koordinat_2, "i", $id_sekolah_2);
                mysqli_stmt_execute($stmt_koordinat_2);
                $sekolah_data_2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_koordinat_2));
                
                if ($sekolah_data_2['latitude'] && $sekolah_data_2['longitude'] && 
                    $data_siswa['latitude'] && $data_siswa['longitude']) {
                    $jarak_2 = hitung_jarak(
                        $data_siswa['latitude'], $data_siswa['longitude'],
                        $sekolah_data_2['latitude'], $sekolah_data_2['longitude']
                    );
                }
            }
            
            // Generate nomor pendaftaran (unique: siswa ID + timestamp + random)
            $no_pendaftaran = "REG-" . date('ymd') . "-" . $id_siswa . "-" . substr(uniqid(), -4);
            
            $status_verifikasi = 'pending';
            $tanggal_daftar = date('Y-m-d H:i:s');
            
            // Insert data (Updated for Dual Choice with distance to both schools)
            $sql_insert = "INSERT INTO pendaftaran (
                            id_siswa, id_sekolah, id_sekolah_pilihan_2, id_jalur, tahun_ajaran, nomor_pendaftaran,
                            nilai_semester_1, nilai_semester_2, nilai_semester_3, nilai_semester_4, nilai_semester_5,
                            nilai_rapor_rata, jarak_rumah_sekolah, jarak_sekolah_2, status_verifikasi, tanggal_daftar
                           ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "iiissddddddddsss", 
                $id_siswa, $id_sekolah, $id_sekolah_2, $id_jalur, $tahun_ajaran, $no_pendaftaran,
                $nilai_semester_1, $nilai_semester_2, $nilai_semester_3, $nilai_semester_4, $nilai_semester_5,
                $nilai_rapor_rata, $jarak, $jarak_2, $status_verifikasi, $tanggal_daftar
            );
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $success = "Pendaftaran berhasil! Nomor pendaftaran Anda: <strong>$no_pendaftaran</strong>";
                log_aktivitas($_SESSION['id_user'], 'Pendaftaran', 
                             "Pendaftaran baru: $no_pendaftaran");
                header("refresh:3;url=dokumen.php");
            } else {
                $error = "Gagal mendaftar: " . mysqli_error($conn);
            }
        }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Jalur & Sekolah - PPDB SMP</title>
    <meta name="base-path" content="/ppdb-smp">
    <?php include '../includes/head_siswa.php'; ?>
    
    <style>
        .jalur-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            height: 100%;
        }
        .jalur-card.tersedia {
            cursor: pointer;
        }
        .jalur-card.tersedia:hover {
            border-color: var(--primary-700);
            box-shadow: 0 5px 15px rgba(30, 58, 95, 0.3);
        }
        .jalur-card.active {
            border-color: var(--primary-700);
            background: #f0f3ff;
        }
        .jalur-card.disabled {
            opacity: 0.6;
            background: #f5f5f5;
        }
        .jalur-card.sudah-daftar {
            border-color: #28a745;
            background: #f0fff4;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .sekolah-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .sekolah-card:hover {
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .timeline-item {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary-700);
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 14px;
            top: 15px;
            width: 2px;
            height: calc(100% + 10px);
            background: #dee2e6;
        }
        .timeline-item:last-child::after {
            display: none;
        }
        
        /* Map Styles */
        #schoolMap {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            border: 2px solid #dee2e6;
        }
        .school-card-map {
            transition: all 0.3s;
            cursor: pointer;
        }
        .school-card-map:hover {
            transform: translateX(5px);
            border-left: 3px solid var(--primary-700);
        }
        .school-card-map.selected {
            background: #f0f3ff;
            border-left: 3px solid var(--primary-700);
        }
        .eligibility-badge {
            font-size: 0.7rem;
        }
        #eligibilityPanel {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-radius: 10px;
            padding: 15px;
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
                <?php if ($biodata_incomplete): ?>
                <!-- Biodata Belum Lengkap -->
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Biodata Belum Lengkap</h4>
                    </div>
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-user-edit fa-4x text-warning"></i>
                        </div>
                        <h5 class="fw-bold">Lengkapi Biodata Terlebih Dahulu</h5>
                        <p class="text-muted mb-4">
                            Anda harus melengkapi data pribadi dan data orang tua sebelum memilih sekolah.
                        </p>
                        <a href="biodata.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-edit me-2"></i>
                            Lengkapi Biodata
                        </a>
                    </div>
                </div>
                
                <?php elseif ($sudah_daftar): ?>
                <!-- Sudah Mendaftar - Tampilkan Riwayat -->
                <div class="card shadow border-primary mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Anda Sudah Terdaftar</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Pendaftaran PPDB sudah selesai.</strong> Anda sudah terdaftar dan tidak dapat mengubah pilihan sekolah.
                            Silakan tunggu pengumuman hasil seleksi.
                        </div>
                        
                        <h5 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>Riwayat Pendaftaran Anda</h5>
                        
                        <?php foreach ($pendaftaran_list as $pend): ?>
                        <div class="card mb-3 <?php echo $sudah_lulus && $pend['status_seleksi'] == 'lulus' ? 'border-success' : 'border-secondary'; ?>">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold mb-2">
                                            <i class="fas fa-school me-2 text-primary"></i>
                                            <?php echo $pend['nama_sekolah']; ?>
                                        </h5>
                                        <p class="mb-1">
                                            <span class="badge bg-info me-2"><?php echo $pend['nama_jalur']; ?></span>
                                            <small class="text-muted">No. Pendaftaran: <strong><?php echo $pend['nomor_pendaftaran']; ?></strong></small>
                                        </p>
                                        <?php if (!empty($pend['nama_sekolah_2'])): ?>
                                        <p class="mb-0 text-muted"><small>Pilihan 2: <?php echo $pend['nama_sekolah_2'] ?? '-'; ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <?php 
                                        $status_class = match($pend['status_seleksi']) {
                                            'lulus' => 'success',
                                            'tidak_lulus' => 'danger',
                                            default => 'warning'
                                        };
                                        $status_text = match($pend['status_seleksi']) {
                                            'lulus' => '✅ LULUS',
                                            'tidak_lulus' => '❌ Tidak Lulus',
                                            default => '⏳ Menunggu Seleksi'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?> fs-6 px-3 py-2">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-4">
                            <?php if ($sudah_lulus): ?>
                            <a href="cetak_kartu.php" class="btn btn-success btn-lg">
                                <i class="fas fa-print me-2"></i>Cetak Bukti Kelulusan
                            </a>
                            <?php else: ?>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Kembali ke Dashboard
                            </a>
                            <a href="hasil_seleksi.php" class="btn btn-outline-primary">
                                <i class="fas fa-trophy me-2"></i>Cek Hasil Seleksi
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                
                <!-- Belum Mendaftar - Tampilkan Form Pendaftaran -->
                <?php if (count($pendaftaran_list) > 0): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Pendaftaran Anda</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Jalur</th>
                                        <th>Sekolah</th>
                                        <th>Nomor Pendaftaran</th>
                                        <th>Status Verifikasi</th>
                                        <th>Status Seleksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($pend = mysqli_fetch_assoc($semua_pendaftaran)): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?php echo $pend['nama_jalur']; ?></span></td>
                                        <td><?php echo $pend['nama_sekolah']; ?></td>
                                        <td><code><?php echo $pend['nomor_pendaftaran']; ?></code></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $pend['status_verifikasi'] == 'terverifikasi' ? 'success' : 
                                                    ($pend['status_verifikasi'] == 'ditolak' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($pend['status_verifikasi']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($pend['status_seleksi']): ?>
                                            <span class="badge bg-<?php 
                                                echo $pend['status_seleksi'] == 'lulus' ? 'success' : 
                                                    ($pend['status_seleksi'] == 'tidak_lulus' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $pend['status_seleksi'])); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">Menunggu seleksi</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Jadwal dan Form Pendaftaran -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🎯 Pendaftaran PPDB Per Jalur</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
                        <?php else: ?>
                        
                        <!-- Info Aturan SISTEM SERENTAK -->
                        <div class="alert alert-info mb-4">
                            <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Aturan Pendaftaran (Sistem Serentak):</h6>
                            <ul class="mb-0">
                                <li>Anda hanya boleh memilih <strong>1 JALUR</strong> untuk seluruh pendaftaran tahun ini</li>
                                <li>Dalam 1 jalur, Anda bisa memilih <strong>2 sekolah</strong> (Pilihan Utama & Cadangan)</li>
                                <li>Pilihan <strong>tidak dapat diubah</strong> setelah pendaftaran dikirim</li>
                                <li>Pilih jalur yang paling sesuai dengan kondisi Anda</li>
                            </ul>
                        </div>
                        
                        <!-- Kriteria Jalur -->
                        <div class="alert alert-secondary mb-4">
                            <h6 class="fw-bold"><i class="fas fa-list-check me-2"></i>Kriteria Setiap Jalur:</h6>
                            <div class="row small">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>🏠 Domisili/Zonasi:</strong> Berdasarkan jarak rumah ke sekolah</p>
                                    <p class="mb-1"><strong>🏆 Prestasi:</strong> Berdasarkan nilai rapor & prestasi akademik/non-akademik</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>💚 Afirmasi:</strong> Khusus siswa DTKS, disabilitas, atau anak panti (perlu upload dokumen)</p>
                                    <p class="mb-1"><strong>🔄 Mutasi:</strong> Perpindahan dari luar kota/daerah</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pilih Jalur -->
                        <h5 class="text-primary mb-3">1️⃣ Pilih Jalur Pendaftaran</h5>
                        <div class="row mb-4">
                            <?php 
                            $jalur_dibuka = null;
                            foreach ($jalur_tersedia as $jt): 
                                $jadwal = $jt['jalur'];
                                $status = $jt['status'];
                                $is_dibuka = ($status == 'tersedia');
                                if ($is_dibuka && !$jalur_dibuka) {
                                    $jalur_dibuka = $jadwal;
                                }
                            ?>
                            <div class="col-md-6 mb-3">
                                <div class="jalur-card position-relative <?php echo $is_dibuka ? 'tersedia' : ($status == 'sudah_daftar' ? 'sudah-daftar' : 'disabled'); ?>"
                                     <?php if ($is_dibuka): ?>onclick="selectJalur(<?php echo $jadwal['id_jalur']; ?>, '<?php echo $jadwal['nama_jalur']; ?>')"<?php endif; ?>>
                                    
                                    <!-- Status Badge -->
                                    <span class="status-badge badge bg-<?php 
                                        echo $status == 'tersedia' ? 'success' : 
                                            ($status == 'sudah_daftar' ? 'info' : 'secondary'); 
                                    ?>">
                                        <?php 
                                        echo $status == 'tersedia' ? '✅ Dibuka' : 
                                            ($status == 'sudah_daftar' ? '📝 Sudah Daftar' : '🔒 Belum/Ditutup'); 
                                        ?>
                                    </span>
                                    
                                    <?php 
                                    // Deskripsi dan ikon per jalur
                                    $jalur_info = [
                                        'zonasi' => ['icon' => '🏠', 'desc' => 'Berdasarkan jarak rumah'],
                                        'domisili' => ['icon' => '🏠', 'desc' => 'Berdasarkan jarak rumah'],
                                        'prestasi' => ['icon' => '🏆', 'desc' => 'Nilai rapor & prestasi'],
                                        'afirmasi' => ['icon' => '💚', 'desc' => 'DTKS / Disabilitas / Panti'],
                                        'mutasi' => ['icon' => '🔄', 'desc' => 'Perpindahan dari luar daerah']
                                    ];
                                    $key = strtolower($jadwal['nama_jalur']);
                                    $info = $jalur_info[$key] ?? ['icon' => '📋', 'desc' => ''];
                                    ?>
                                    <h5 class="text-primary mb-1"><?php echo $info['icon']; ?> <?php echo $jadwal['nama_jalur']; ?></h5>
                                    <p class="text-muted small mb-2"><?php echo $info['desc']; ?></p>
                                    
                                    <div class="small">
                                        <p class="mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo tanggal_indonesia($jadwal['tanggal_buka']); ?> - <?php echo tanggal_indonesia($jadwal['tanggal_tutup']); ?>
                                        </p>
                                    </div>
                                    
                                    <?php if ($status == 'sudah_daftar'): ?>
                                    <div class="mt-2 p-2 bg-light rounded small">
                                        <strong>Terdaftar di:</strong> <?php echo $jt['pendaftaran']['nama_sekolah'] ?? '-'; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_dibuka): ?>
                                    <input type="radio" name="selected_jalur" value="<?php echo $jadwal['id_jalur']; ?>" 
                                           id="jalur_<?php echo $jadwal['id_jalur']; ?>" hidden>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (!$jalur_dibuka): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-clock fa-2x mb-3"></i>
                            <h5>Tidak Ada Jalur yang Sedang Dibuka</h5>
                            <p class="mb-0">Silakan cek kembali jadwal pendaftaran atau hubungi panitia PPDB.</p>
                        </div>
                        <?php else: ?>
                        
                        <!-- Form Pendaftaran (hanya muncul jika ada jalur dibuka) -->
                        <form method="POST" action="" id="formPendaftaran" onsubmit="return confirmSubmit()">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="id_jalur" id="input_id_jalur" value="">
                            <input type="hidden" name="daftar" value="1">
                            
                            <!-- Input Nilai Rapor -->
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">2️⃣ Input Nilai Rapor Semester 1-5</h5>
                                <div class="row">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Semester <?php echo $i; ?> *</label>
                                        <input type="number" class="form-control" 
                                               name="nilai_semester_<?php echo $i; ?>" 
                                               step="0.01" min="0" max="100" required>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted">Masukkan nilai rata-rata rapor per semester (0-100)</small>
                            </div>
                            
                            <!-- Pilih Sekolah -->
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">3️⃣ Pilih Sekolah Tujuan (Maksimal 2)</h5>
                                
                                <!-- Selection Dashboard (Sticky) -->
                                <div class="card bg-light mb-3 sticky-top shadow-sm" style="top: 0; z-index: 10;">
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <!-- Choice 1 -->
                                            <div class="col-md-6">
                                                <div class="p-2 border rounded bg-white border-primary">
                                                    <small class="text-primary fw-bold d-block mb-1">
                                                        <i class="fas fa-check-circle"></i> Pilihan Utama (Wajib)
                                                    </small>
                                                    <h6 class="fw-bold text-dark mb-2" id="display_sekolah_1">- Belum dipilih -</h6>
                                                    <input type="hidden" name="id_sekolah" id="input_sekolah_1" required>
                                                    <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                                                            id="btn_clear_1" onclick="clearChoice(1)" style="display:none;">
                                                        <i class="fas fa-times"></i> Hapus
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Choice 2 -->
                                            <div class="col-md-6">
                                                <div class="p-2 border rounded bg-white">
                                                    <small class="text-secondary fw-bold d-block mb-1">
                                                        <i class="fas fa-plus-circle"></i> Pilihan Kedua (Opsional)
                                                    </small>
                                                    <h6 class="fw-bold text-dark mb-2" id="display_sekolah_2">- Belum dipilih -</h6>
                                                    <input type="hidden" name="id_sekolah_2" id="input_sekolah_2">
                                                    <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                                                            id="btn_clear_2" onclick="clearChoice(2)" style="display:none;">
                                                        <i class="fas fa-times"></i> Hapus
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Toggle Map & Filter Controls -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleMapBtn" onclick="toggleSchoolMap()">
                                        <i class="fas fa-map"></i> Peta
                                    </button>
                                    <div class="input-group input-group-sm w-50">
                                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                        <input type="text" id="schoolSearch" class="form-control" placeholder="Cari sekolah..." onkeyup="filterSchools()">
                                    </div>
                                    <select id="schoolSort" class="form-select form-select-sm w-auto" onchange="sortSchools()">
                                        <option value="name_asc">Nama (A-Z)</option>
                                        <option value="distance_asc">Jarak</option>
                                    </select>
                                </div>
                                
                                <!-- Map Container -->
                                <div id="mapContainer" class="mb-3" style="display:none;">
                                    <div id="schoolMap" style="height: 300px; border-radius: 10px;"></div>
                                </div>

                                <!-- Scrollable School List -->
                                <div class="school-list-container border rounded p-2" style="max-height: 400px; overflow-y: auto;">
                                    <div class="list-group" id="schoolListGroup">
                                        <?php 
                                        mysqli_data_seek($sekolah_list, 0);
                                        while ($sekolah = mysqli_fetch_assoc($sekolah_list)): 
                                            // Escape strings for JS
                                            $js_nama = htmlspecialchars($sekolah['nama_sekolah'], ENT_QUOTES);
                                            $js_id = $sekolah['id_sekolah'];
                                        ?>
                                        <div class="list-group-item school-card-map mb-2 border rounded" 
                                               data-school-id="<?php echo $js_id; ?>"
                                               data-name="<?php echo strtolower($sekolah['nama_sekolah']); ?>"
                                               data-distance="999999">
                                            
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1 school-title fw-bold text-dark"><?php echo $sekolah['nama_sekolah']; ?></h6>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-map-marker-alt text-danger me-1"></i> <?php echo $sekolah['kecamatan']; ?> | 
                                                        Kuota: <strong><?php echo $sekolah['total_kuota'] ?? 0; ?></strong>
                                                    </p>
                                                    <span class="school-distance text-success fw-bold small" style="display:none;"></span>
                                                </div>
                                                <?php if ($sekolah['akreditasi']): ?>
                                                <span class="badge bg-light text-dark border"><?php echo $sekolah['akreditasi']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Action Buttons -->
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill btn-select-1" 
                                                        onclick="selectSchool(<?php echo $js_id; ?>, '<?php echo $js_nama; ?>', 1)">
                                                    Pilih #1
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill btn-select-2" 
                                                        onclick="selectSchool(<?php echo $js_id; ?>, '<?php echo $js_nama; ?>', 2)">
                                                    Pilih #2
                                                </button>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                        
                                        <!-- No Results -->
                                        <div id="noSchoolsFound" class="text-center py-4" style="display:none;">
                                            <i class="fas fa-search fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">Sekolah tidak ditemukan</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-danger mt-4">
                                <strong>⚠️ PERINGATAN PENTING:</strong><br>
                                Pastikan <strong>Pilihan Utama</strong> dan <strong>Pilihan Kedua</strong> (jika ada) sudah benar.
                                Perubahan tidak dapat dilakukan setelah pendaftaran dikirim.
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-lg" id="btnDaftar" disabled>
                                    ✅ Daftar Sekarang
                                </button>
                            </div>
                        </form>
                        <?php endif; // end if jalur_dibuka ?>
                        <?php endif; // end if success ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Global state for choices
        let choice1 = null;
        let choice2 = null;

        let selectedJalurName = '';

        function selectJalur(id, namaJalur) {
            selectedJalurName = namaJalur;
            
            // Remove active class from all
            document.querySelectorAll('.jalur-card.tersedia').forEach(card => {
                card.classList.remove('active');
            });
            
            // Add active class to selected
            const radio = document.getElementById('jalur_' + id);
            if (radio) {
                radio.checked = true;
                radio.parentElement.classList.add('active');
                
                // Set hidden input
                document.getElementById('input_id_jalur').value = id;
                
                // Enable submit button logic check
                checkSubmitState();
                
                // Update Map Logic if initialized
                if (typeof calculateAllEligibility === 'function') {
                    calculateAllEligibility();
                }
            }
        }
        
        function selectSchool(id, name, choiceNum) {
            // Validate: Prevent same school in both choices
            if (choiceNum === 1 && choice2 === id) {
                alert('Sekolah ini sudah dipilih sebagai Pilihan 2. Hapus pilihan 2 terlebih dahulu jika ingin menjadikannya Pilihan 1.');
                return;
            }
            if (choiceNum === 2 && choice1 === id) {
                alert('Sekolah ini sudah dipilih sebagai Pilihan 1. Hapus pilihan 1 terlebih dahulu jika ingin menjadikannya Pilihan 2.');
                return;
            }
            
            // Update State
            if (choiceNum === 1) {
                choice1 = id;
                document.getElementById('input_sekolah_1').value = id;
                document.getElementById('display_sekolah_1').innerText = name;
                document.getElementById('display_sekolah_1').classList.remove('text-muted');
                document.getElementById('display_sekolah_1').classList.add('text-primary');
                document.getElementById('btn_clear_1').style.display = 'block';
            } else {
                choice2 = id;
                document.getElementById('input_sekolah_2').value = id;
                document.getElementById('display_sekolah_2').innerText = name;
                document.getElementById('display_sekolah_2').classList.remove('text-muted');
                document.getElementById('display_sekolah_2').classList.add('text-success');
                document.getElementById('btn_clear_2').style.display = 'block';
            }
            
            // Visual Feedback on Buttons
            updateButtonStates();
            checkSubmitState();
            
            // Highlight on Map if exists
            if (typeof selectSchoolOnMap === 'function' && mapInitialized) {
                selectSchoolOnMap(id);
            }
        }
        
        function clearChoice(choiceNum) {
            if (choiceNum === 1) {
                choice1 = null;
                document.getElementById('input_sekolah_1').value = '';
                document.getElementById('display_sekolah_1').innerText = '- Belum dipilih -';
                document.getElementById('display_sekolah_1').classList.add('text-muted');
                document.getElementById('display_sekolah_1').classList.remove('text-primary');
                document.getElementById('btn_clear_1').style.display = 'none';
            } else {
                choice2 = null;
                document.getElementById('input_sekolah_2').value = '';
                document.getElementById('display_sekolah_2').innerText = '- Belum dipilih -';
                document.getElementById('display_sekolah_2').classList.add('text-muted');
                document.getElementById('display_sekolah_2').classList.remove('text-success');
                document.getElementById('btn_clear_2').style.display = 'none';
            }
            updateButtonStates();
            checkSubmitState();
        }
        
        function updateButtonStates() {
            // Reset all buttons
            document.querySelectorAll('.btn-select-1, .btn-select-2').forEach(btn => {
                btn.classList.remove('active', 'btn-primary', 'btn-success');
                btn.classList.add('btn-outline-primary', 'btn-outline-secondary');
                btn.disabled = false;
                btn.innerHTML = btn.classList.contains('btn-select-1') ? 'Pilih #1' : 'Pilih #2';
            });
            
            // Update Choice 1 Buttons
            if (choice1) {
                const btn1 = document.querySelector(`.school-card-map[data-school-id="${choice1}"] .btn-select-1`);
                if (btn1) {
                    btn1.classList.add('active', 'btn-primary');
                    btn1.classList.remove('btn-outline-primary');
                    btn1.innerText = '✅ Dipilih #1';
                }
                // Disable "Pilih #2" for this school
                const btn2 = document.querySelector(`.school-card-map[data-school-id="${choice1}"] .btn-select-2`);
                if (btn2) btn2.disabled = true;
            }
            
            // Update Choice 2 Buttons
            if (choice2) {
                const btn2 = document.querySelector(`.school-card-map[data-school-id="${choice2}"] .btn-select-2`);
                if (btn2) {
                    btn2.classList.add('active', 'btn-success');
                    btn2.classList.remove('btn-outline-secondary');
                    btn2.innerText = '✅ Dipilih #2';
                }
                // Disable "Pilih #1" for this school
                const btn1 = document.querySelector(`.school-card-map[data-school-id="${choice2}"] .btn-select-1`);
                if (btn1) btn1.disabled = true;
            }
        }
        
        function checkSubmitState() {
            const jalur = document.getElementById('input_id_jalur').value;
            const sekolah1 = document.getElementById('input_sekolah_1').value;
            const btn = document.getElementById('btnDaftar');
            
            if (jalur && sekolah1) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
        }
        
        function confirmSubmit() {
            const jalur = document.getElementById('input_id_jalur').value;
            const sekolah1 = document.getElementById('input_sekolah_1').value;
            
            if (!jalur) {
                alert('Silakan pilih jalur pendaftaran terlebih dahulu!');
                return false;
            }
            
            if (!sekolah1) {
                alert('Silakan pilih sekolah untuk Pilihan Utama!');
                return false;
            }
            
            let msg = 'PERHATIAN!\n\nAnda akan mendaftar dengan pilihan:\n';
            msg += '1. ' + document.getElementById('display_sekolah_1').innerText + '\n';
            
            if (choice2) {
                msg += '2. ' + document.getElementById('display_sekolah_2').innerText + '\n';
            } else {
                msg += '2. - Tidak Ada -\n';
            }
            
            msg += '\nSetelah dikirim, data TIDAK DAPAT DIUBAH.\nLanjutkan?';
            
            return confirm(msg);
        }
        
        // Sync card selection with map (Updated to not interfere with buttons)
        document.querySelectorAll('.school-card-map').forEach(card => {
            card.addEventListener('click', function(e) {
                // Ignore clicks on buttons
                if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;

                // Remove selected from all
                document.querySelectorAll('.school-card-map').forEach(c => c.classList.remove('selected'));
                // Add to clicked
                this.classList.add('selected');
                
                // If map is visible, zoom to school
                const schoolId = this.dataset.schoolId;
                if (typeof selectSchoolOnMap === 'function' && schoolId) {
                    selectSchoolOnMap(parseInt(schoolId));
                }
            });
        });
    </script>
    
    <!-- School Map JS -->
    <script src="/ppdb-smp/assets/js/school-map.js"></script>
    
    <?php 
    // Prepare sekolah data for map
    mysqli_data_seek($sekolah_list, 0);
    $sekolah_array = [];
    while ($s = mysqli_fetch_assoc($sekolah_list)) {
        $sekolah_array[] = $s;
    }
    ?>
    
    <script>
        // Sekolah data for map
        window.sekolahDataGlobal = <?php echo json_encode($sekolah_array); ?>;
        
        // Initialize map when toggle is clicked
        let mapInitialized = false;
        
        function toggleSchoolMap() {
            const mapContainer = document.getElementById('mapContainer');
            const toggleBtn = document.getElementById('toggleMapBtn');
            
            if (mapContainer.style.display === 'none') {
                mapContainer.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-map"></i> Sembunyikan Peta';
                
                // Initialize map only once
                if (!mapInitialized && window.sekolahDataGlobal) {
                    setTimeout(() => {
                        initSchoolMap(window.sekolahDataGlobal);
                        mapInitialized = true;
                    }, 100);
                } else if (map) {
                    setTimeout(() => map.invalidateSize(), 100);
                }
            } else {
                mapContainer.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-map"></i> Tampilkan Peta';
            }
        }
        
        // Event listener untuk card sudah di-setup sebelumnya (line 787-804)
        // Tidak perlu duplicate di sini

        // ============================
        // SEARCH & SORT LOGIC
        // ============================
        
        function filterSchools() {
            const input = document.getElementById('schoolSearch');
            const filter = input.value.toLowerCase();
            const container = document.getElementById('schoolListGroup');
            const cards = container.getElementsByClassName('school-card-map');
            let visibleCount = 0;
            
            for (let i = 0; i < cards.length; i++) {
                const name = cards[i].getAttribute('data-name');
                if (name.includes(filter)) {
                    cards[i].style.display = "";
                    visibleCount++;
                } else {
                    cards[i].style.display = "none";
                }
            }
            
            // Show no results message
            document.getElementById('noSchoolsFound').style.display = visibleCount === 0 ? 'block' : 'none';
        }
        
        function sortSchools() {
            const criteria = document.getElementById('schoolSort').value;
            const container = document.getElementById('schoolListGroup');
            const cards = Array.from(container.getElementsByClassName('school-card-map'));
            
            cards.sort((a, b) => {
                if (criteria === 'name_asc') {
                    const nameA = a.getAttribute('data-name');
                    const nameB = b.getAttribute('data-name');
                    return nameA.localeCompare(nameB);
                } else if (criteria === 'distance_asc') {
                    const distA = parseFloat(a.getAttribute('data-distance')) || 999999;
                    const distB = parseFloat(b.getAttribute('data-distance')) || 999999;
                    return distA - distB;
                }
            });
            
            // Re-append sorted cards
            cards.forEach(card => container.appendChild(card));
        }

        // Auto-sort by distance after location is found (Called from school-map.js or observer)
        // We can hook into calculateAllEligibility in school-map.js
        // For now, let's use a mutation observer on one of the cards to detect distance update
        // OR simply rely on user to pick sort, OR update school-map.js to call sortSchools()
    </script>
</body>
</html>

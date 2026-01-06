<?php
/**
 * File: config/database.php
 * Deskripsi: Koneksi database menggunakan MySQLi
 * Author: Sistem PPDB SMP
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ppdb_smp');

// Koneksi ke Database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Load konstanta global
require_once __DIR__ . '/constants.php';

// Fungsi untuk mencegah SQL Injection
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Fungsi untuk mengecek login
function check_login() {
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }
}

// Fungsi untuk mengecek apakah user adalah super_admin
function is_super_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'super_admin';
}

// Fungsi untuk mengecek apakah user adalah operator
function is_operator() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'operator';
}

// Fungsi untuk mendapatkan id_sekolah operator (NULL jika super_admin)
function get_operator_sekolah() {
    return $_SESSION['id_sekolah'] ?? null;
}

// Fungsi untuk mengecek role admin (super_admin atau operator)
function check_admin() {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'operator'])) {
        header("Location: ../index.php");
        exit();
    }
}

// Fungsi untuk mengecek role super_admin saja
function check_super_admin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 'super_admin') {
        // Use absolute path to prevent redirect loops
        header("Location: /ppdb-smp/admin/index.php?error=access_denied");
        exit();
    }
}

// Fungsi untuk mengecek role siswa
function check_siswa() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa') {
        header("Location: ../index.php");
        exit();
    }
}

// Fungsi untuk mendapatkan filter sekolah (untuk query)
// Super admin: return NULL (tidak ada filter)
// Operator: return id_sekolah mereka
function get_sekolah_filter() {
    if (is_super_admin()) {
        return null; // Tidak ada filter, akses semua
    }
    return get_operator_sekolah();
}

// Fungsi untuk logging aktivitas
function log_aktivitas($id_user, $aktivitas, $deskripsi) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Jika id_user = 0 atau tidak valid, set ke NULL untuk menghindari foreign key error
    if ($id_user <= 0) {
        $sql = "INSERT INTO log_aktivitas (id_user, aktivitas, deskripsi, ip_address) 
                VALUES (NULL, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $aktivitas, $deskripsi, $ip);
    } else {
        $sql = "INSERT INTO log_aktivitas (id_user, aktivitas, deskripsi, ip_address) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isss", $id_user, $aktivitas, $deskripsi, $ip);
    }
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Fungsi untuk generate nomor pendaftaran
function generate_nomor_pendaftaran($tahun_ajaran) {
    global $conn;
    
    $tahun_prefix = substr($tahun_ajaran, 0, 4);
    
    // Ambil nomor urut terakhir
    $sql = "SELECT MAX(CAST(SUBSTRING(nomor_pendaftaran, -5) AS UNSIGNED)) AS last_number 
            FROM pendaftaran WHERE tahun_ajaran = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tahun_ajaran);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $urutan = ($row['last_number'] ?? 0) + 1;
    $nomor = 'PPDB' . $tahun_prefix . str_pad($urutan, 5, '0', STR_PAD_LEFT);
    
    mysqli_stmt_close($stmt);
    return $nomor;
}

// Fungsi untuk menghitung jarak koordinat (Haversine Formula)
function hitung_jarak($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // Radius bumi dalam kilometer
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earth_radius * $c;
    
    return round($distance, 2);
}

// Fungsi untuk upload file
function upload_file($file, $folder = 'dokumen') {
    // Gunakan absolute path agar aman dipanggil dari folder manapun
    $target_dir = dirname(__DIR__) . "/uploads/" . $folder . "/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Validasi tipe file
    $allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
    if (!in_array($file_extension, $allowed_types)) {
        return array('success' => false, 'message' => 'Format file tidak diizinkan');
    }
    
    // Validasi ukuran file (max 2MB)
    if ($file["size"] > 2097152) {
        return array('success' => false, 'message' => 'Ukuran file maksimal 2MB');
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return array(
            'success' => true, 
            'filename' => $new_filename,
            'path' => $target_file,
            'size' => $file["size"]
        );
    } else {
        return array('success' => false, 'message' => 'Gagal upload file');
    }
}

// Fungsi untuk format tanggal Indonesia
function tanggal_indonesia($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $pecah = explode('-', $tanggal);
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

// Fungsi untuk format rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// ============================================
// FUNGSI VALIDASI JADWAL PPDB
// ============================================

/**
 * Ambil pengaturan PPDB dari database
 */
function get_pengaturan_ppdb() {
    global $conn;
    $tahun = TAHUN_AJARAN;
    
    $sql = "SELECT * FROM pengaturan_ppdb WHERE tahun_ajaran = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tahun);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Ambil status PPDB saat ini
 */
function get_status_ppdb() {
    $pengaturan = get_pengaturan_ppdb();
    return $pengaturan['status_ppdb'] ?? 'belum_dibuka';
}

/**
 * Cek apakah aksi diizinkan berdasarkan status saat ini
 * 
 * @param string $aksi Aksi yang ingin dilakukan (pendaftaran, verifikasi, seleksi, pengumuman, daftar_ulang)
 * @return array ['diizinkan' => bool, 'pesan' => string]
 */
function cek_aksi_diizinkan($aksi) {
    $status = get_status_ppdb();
    
    // Map aksi ke status yang diperlukan
    $aksi_status = [
        'pendaftaran' => ['pendaftaran'],
        'verifikasi' => ['pendaftaran', 'seleksi'],
        'seleksi' => ['seleksi'],
        'publikasi' => ['pengumuman'],
        'pengumuman' => ['pengumuman', 'daftar_ulang'],
        'daftar_ulang' => ['daftar_ulang']
    ];
    
    // Status nama yang user-friendly
    $status_labels = [
        'belum_dibuka' => 'Belum Dibuka',
        'pendaftaran' => 'Pendaftaran',
        'seleksi' => 'Seleksi',
        'pengumuman' => 'Pengumuman',
        'daftar_ulang' => 'Daftar Ulang',
        'selesai' => 'Selesai'
    ];
    
    // Cek apakah aksi diizinkan
    $status_diizinkan = $aksi_status[$aksi] ?? [];
    $diizinkan = in_array($status, $status_diizinkan);
    
    if ($diizinkan) {
        return [
            'diizinkan' => true,
            'pesan' => 'Aksi diizinkan'
        ];
    } else {
        $status_butuh = implode(' / ', array_map(function($s) use ($status_labels) {
            return $status_labels[$s] ?? $s;
        }, $status_diizinkan));
        
        return [
            'diizinkan' => false,
            'pesan' => "Aksi ini hanya dapat dilakukan pada periode: <strong>$status_butuh</strong>. Status saat ini: <strong>{$status_labels[$status]}</strong>. Silakan ubah status di menu Pengaturan.",
            'status_saat_ini' => $status,
            'status_dibutuhkan' => $status_diizinkan
        ];
    }
}

/**
 * Dapatkan pesan status PPDB saat ini
 */
function get_pesan_status() {
    $pengaturan = get_pengaturan_ppdb();
    $status = $pengaturan['status_ppdb'] ?? 'belum_dibuka';
    
    $pesan = [
        'belum_dibuka' => ['icon' => '🔒', 'text' => 'PPDB belum dibuka', 'class' => 'secondary'],
        'pendaftaran' => ['icon' => '📝', 'text' => 'Pendaftaran dibuka', 'class' => 'primary'],
        'seleksi' => ['icon' => '⚙️', 'text' => 'Proses seleksi', 'class' => 'warning'],
        'pengumuman' => ['icon' => '📢', 'text' => 'Pengumuman hasil', 'class' => 'info'],
        'daftar_ulang' => ['icon' => '✅', 'text' => 'Daftar ulang', 'class' => 'success'],
        'selesai' => ['icon' => '🏁', 'text' => 'PPDB selesai', 'class' => 'dark']
    ];
    
    $info = $pesan[$status] ?? $pesan['belum_dibuka'];
    $info['status'] = $status;
    $info['tanggal'] = $pengaturan;
    
    return $info;
}

// ============================================
// FUNGSI VALIDASI JALUR PENDAFTARAN
// ============================================

/**
 * Ambil jadwal semua jalur untuk tahun ajaran ini
 */
function get_semua_jadwal_jalur() {
    global $conn;
    $tahun = TAHUN_AJARAN;
    
    $sql = "SELECT jj.*, jp.nama_jalur, jp.kode_jalur 
            FROM jadwal_jalur jj
            JOIN jalur_ppdb jp ON jj.id_jalur = jp.id_jalur
            WHERE jj.tahun_ajaran = ?
            ORDER BY jj.urutan ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tahun);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/**
 * Ambil jadwal untuk satu jalur
 */
function get_jadwal_jalur($id_jalur) {
    global $conn;
    $tahun = TAHUN_AJARAN;
    
    $sql = "SELECT jj.*, jp.nama_jalur, jp.kode_jalur 
            FROM jadwal_jalur jj
            JOIN jalur_ppdb jp ON jj.id_jalur = jp.id_jalur
            WHERE jj.id_jalur = ? AND jj.tahun_ajaran = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $id_jalur, $tahun);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

/**
 * Cek apakah jalur sedang dibuka untuk pendaftaran
 * @return array ['dibuka' => bool, 'pesan' => string]
 */
function cek_jalur_dibuka($id_jalur) {
    $jadwal = get_jadwal_jalur($id_jalur);
    $today = date('Y-m-d');
    
    if (!$jadwal) {
        return [
            'dibuka' => false,
            'pesan' => 'Jadwal jalur belum diatur'
        ];
    }
    
    // Cek status jalur - harus dalam status pendaftaran
    if ($jadwal['status'] != 'pendaftaran') {
        return [
            'dibuka' => false,
            'pesan' => "Jalur {$jadwal['nama_jalur']} tidak dalam periode pendaftaran. Status: {$jadwal['status']}",
            'jadwal' => $jadwal
        ];
    }
    
    // Cek tanggal
    if ($today < $jadwal['tanggal_buka']) {
        return [
            'dibuka' => false,
            'pesan' => "Pendaftaran jalur {$jadwal['nama_jalur']} belum dibuka. Dibuka mulai " . tanggal_indonesia($jadwal['tanggal_buka']),
            'jadwal' => $jadwal
        ];
    }
    
    if ($today > $jadwal['tanggal_tutup']) {
        return [
            'dibuka' => false,
            'pesan' => "Pendaftaran jalur {$jadwal['nama_jalur']} sudah ditutup sejak " . tanggal_indonesia($jadwal['tanggal_tutup']),
            'jadwal' => $jadwal
        ];
    }
    
    return [
        'dibuka' => true,
        'pesan' => "Pendaftaran jalur {$jadwal['nama_jalur']} dibuka sampai " . tanggal_indonesia($jadwal['tanggal_tutup']),
        'jadwal' => $jadwal
    ];
}

/**
 * Cek apakah siswa sudah mendaftar di jalur tertentu
 * @return array ['sudah_daftar' => bool, 'data' => pendaftaran data jika ada]
 */
function cek_sudah_daftar_jalur($id_siswa, $id_jalur) {
    global $conn;
    $tahun = TAHUN_AJARAN;
    
    $sql = "SELECT p.*, s.nama_sekolah, jp.nama_jalur
            FROM pendaftaran p
            JOIN sekolah s ON p.id_sekolah = s.id_sekolah
            JOIN jalur_ppdb jp ON p.id_jalur = jp.id_jalur
            WHERE p.id_siswa = ? AND p.id_jalur = ? AND p.tahun_ajaran = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iis", $id_siswa, $id_jalur, $tahun);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($result) {
        return [
            'sudah_daftar' => true,
            'pesan' => "Anda sudah mendaftar di jalur {$result['nama_jalur']} ke {$result['nama_sekolah']}. Satu siswa hanya boleh mendaftar ke 1 sekolah per jalur.",
            'data' => $result
        ];
    }
    
    return [
        'sudah_daftar' => false,
        'pesan' => '',
        'data' => null
    ];
}

/**
 * SISTEM SERENTAK: Cek apakah siswa boleh mendaftar
 * - Sudah lulus di jalur manapun? Tidak boleh daftar lagi
 * - Sudah daftar di jalur manapun tahun ini? Hanya boleh 1 jalur
 * @return array ['boleh' => bool, 'pesan' => string]
 */
function cek_boleh_daftar_serentak($id_siswa) {
    global $conn;
    $tahun = TAHUN_AJARAN;
    
    // Cek apakah sudah LULUS di jalur manapun tahun ini
    $sql_lulus = "SELECT p.id_pendaftaran, jp.nama_jalur, s.nama_sekolah
            FROM pendaftaran p
            JOIN jalur_ppdb jp ON p.id_jalur = jp.id_jalur
            JOIN sekolah s ON p.id_sekolah = s.id_sekolah
            LEFT JOIN hasil_seleksi hs ON p.id_pendaftaran = hs.id_pendaftaran
            WHERE p.id_siswa = ? AND p.tahun_ajaran = ? AND hs.status_seleksi = 'lulus'
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql_lulus);
    mysqli_stmt_bind_param($stmt, "is", $id_siswa, $tahun);
    mysqli_stmt_execute($stmt);
    $lulus = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($lulus) {
        return [
            'boleh' => false,
            'pesan' => "Selamat! Anda sudah dinyatakan LULUS di jalur {$lulus['nama_jalur']} ke {$lulus['nama_sekolah']}. Tidak dapat mendaftar lagi."
        ];
    }
    
    // Cek apakah sudah daftar di jalur manapun tahun ini (SISTEM SERENTAK: Hanya boleh 1 jalur)
    $sql_daftar = "SELECT p.id_pendaftaran, jp.nama_jalur, s.nama_sekolah
            FROM pendaftaran p
            JOIN jalur_ppdb jp ON p.id_jalur = jp.id_jalur
            JOIN sekolah s ON p.id_sekolah = s.id_sekolah
            WHERE p.id_siswa = ? AND p.tahun_ajaran = ?
            LIMIT 1";
    $stmt2 = mysqli_prepare($conn, $sql_daftar);
    mysqli_stmt_bind_param($stmt2, "is", $id_siswa, $tahun);
    mysqli_stmt_execute($stmt2);
    $sudah_daftar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    
    if ($sudah_daftar) {
        return [
            'boleh' => false,
            'pesan' => "Anda sudah mendaftar di jalur {$sudah_daftar['nama_jalur']} ke {$sudah_daftar['nama_sekolah']}. Pada sistem serentak, Anda hanya boleh memilih 1 jalur."
        ];
    }
    
    return ['boleh' => true, 'pesan' => ''];
}

/**
 * Cek eligibility jalur Afirmasi (versi lama - untuk backward compatibility)
 * Jalur Afirmasi hanya untuk: DTKS, Disabilitas, Panti Asuhan
 * @return array ['eligible' => bool, 'pesan' => string]
 */
function cek_eligibility_afirmasi($id_siswa) {
    global $conn;
    
    // Cek di tabel dokumen (via pendaftaran)
    $sql = "SELECT COUNT(*) as dok_afirmasi FROM dokumen d
            JOIN pendaftaran p ON d.id_pendaftaran = p.id_pendaftaran
            WHERE p.id_siswa = ? 
            AND d.jenis_dokumen IN ('DTKS', 'SKTM', 'KIS', 'PKH', 'Disabilitas', 'Panti',
                                    'dtks', 'sktm', 'kartu_kis', 'kartu_pkh', 'surat_disabilitas', 'surat_panti')
            AND d.status_verifikasi = 'valid'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_siswa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    // Cek apakah ada dokumen afirmasi yang valid
    if ($result && $result['dok_afirmasi'] > 0) {
        return ['eligible' => true, 'pesan' => ''];
    }
    
    return [
        'eligible' => false,
        'pesan' => 'Jalur Afirmasi hanya untuk calon siswa dari keluarga tidak mampu (DTKS/SKTM), penyandang disabilitas, atau anak panti asuhan. Silakan upload dokumen pendukung terlebih dahulu atau pilih jalur lain.'
    ];
}

/**
 * Validasi lengkap pendaftaran jalur
 * @return array ['valid' => bool, 'pesan' => string]
 */
function validasi_pendaftaran_jalur($id_siswa, $id_jalur) {
    // 1. Cek apakah jalur sedang dibuka
    $cek_buka = cek_jalur_dibuka($id_jalur);
    if (!$cek_buka['dibuka']) {
        return ['valid' => false, 'pesan' => $cek_buka['pesan']];
    }
    
    // 2. SISTEM SERENTAK: Cek apakah sudah daftar di jalur manapun 
    $cek_boleh = cek_boleh_daftar_serentak($id_siswa);
    if (!$cek_boleh['boleh']) {
        return ['valid' => false, 'pesan' => $cek_boleh['pesan']];
    }
    
    // 3. Cek kelengkapan dokumen UMUM (wajib untuk semua jalur)
    //    Dokumen khusus per jalur dicek saat SELEKSI, bukan saat daftar
    $cek_dok_umum = cek_dokumen_umum_lengkap($id_siswa);
    if (!$cek_dok_umum['lengkap']) {
        return ['valid' => false, 'pesan' => $cek_dok_umum['pesan']];
    }
    
    // 4. Info khusus per jalur (hanya warning, tidak memblokir pendaftaran)
    $jadwal = get_jadwal_jalur($id_jalur);
    $nama_jalur = strtolower($jadwal['nama_jalur']);
    $warning = '';
    
    if ($nama_jalur == 'afirmasi') {
        $warning = 'Pastikan Anda mengupload dokumen Afirmasi (DTKS/SKTM/KIS/dll) sebelum deadline pendaftaran untuk diproses dalam seleksi.';
    } elseif ($nama_jalur == 'prestasi') {
        $warning = 'Pastikan Anda mengupload Sertifikat Prestasi (maks 3 tahun terakhir) sebelum deadline pendaftaran.';
    } elseif ($nama_jalur == 'mutasi') {
        $warning = 'Pastikan Anda mengupload dokumen Mutasi (Surat Tugas/Pindah Domisili) sebelum deadline pendaftaran.';
    }
    
    return ['valid' => true, 'pesan' => 'Pendaftaran valid', 'warning' => $warning];
}

/**
 * Cek kelengkapan dokumen UMUM (wajib semua jalur)
 * Dokumen umum: KK, SKL, Rapor, Foto
 */
function cek_dokumen_umum_lengkap($id_siswa) {
    global $conn;
    
    // Dokumen umum yang wajib
    $dokumen_umum = ['kk', 'skl', 'rapor', 'foto'];
    $dokumen_ada = [];
    
    // Cek di tabel dokumen_siswa
    $sql = "SELECT LOWER(jenis_dokumen) as jenis FROM dokumen_siswa 
            WHERE id_siswa = ? AND kategori = 'umum'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_siswa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $dokumen_ada[] = $row['jenis'];
    }
    
    // Cek dokumen yang kurang
    $kurang = array_diff($dokumen_umum, $dokumen_ada);
    
    if (empty($kurang)) {
        return ['lengkap' => true, 'pesan' => ''];
    }
    
    // Buat label dokumen yang kurang
    $labels = [
        'kk' => 'Kartu Keluarga',
        'skl' => 'SKL/Ijazah', 
        'rapor' => 'Rapor',
        'foto' => 'Pas Foto'
    ];
    
    $label_kurang = [];
    foreach ($kurang as $jenis) {
        $label_kurang[] = $labels[$jenis] ?? ucfirst($jenis);
    }
    
    return [
        'lengkap' => false,
        'pesan' => 'Dokumen umum belum lengkap. Silakan upload: ' . implode(', ', $label_kurang)
    ];
}

// [REMOVED] Fungsi cek_dokumen_afirmasi_uploaded sudah tidak dipakai
// Sistem sekarang menggunakan tabel dokumen_siswa dan validasi di fungsi seleksi

/**
 * Ambil daftar jalur yang tersedia untuk siswa
 */
function get_jalur_tersedia_siswa($id_siswa) {
    global $conn;
    $tahun = TAHUN_AJARAN;
    $today = date('Y-m-d');
    
    $jalur_list = [];
    $semua_jalur = get_semua_jadwal_jalur();
    
    while ($jalur = mysqli_fetch_assoc($semua_jalur)) {
        $info = [
            'jalur' => $jalur,
            'status' => 'tersedia'
        ];
        
        // Cek apakah jalur sedang dibuka
        $cek_buka = cek_jalur_dibuka($jalur['id_jalur']);
        if (!$cek_buka['dibuka']) {
            $info['status'] = 'belum_dibuka';
            $info['pesan'] = $cek_buka['pesan'];
        }
        
        // Cek apakah sudah daftar di jalur ini
        $cek_daftar = cek_sudah_daftar_jalur($id_siswa, $jalur['id_jalur']);
        if ($cek_daftar['sudah_daftar']) {
            $info['status'] = 'sudah_daftar';
            $info['pesan'] = $cek_daftar['pesan'];
            $info['pendaftaran'] = $cek_daftar['data'];
        }
        
        $jalur_list[] = $info;
    }
    
    return $jalur_list;
}

// ============================================
// FUNGSI VALIDASI AKSI ADMIN PER JALUR
// ============================================

/**
 * Cek apakah aksi admin diizinkan berdasarkan status jalur
 * 
 * @param string $aksi Aksi yang ingin dilakukan (seleksi, publikasi)
 * @param int $id_jalur ID jalur yang akan diproses
 * @return array ['diizinkan' => bool, 'pesan' => string]
 */
function cek_aksi_jalur_diizinkan($aksi, $id_jalur) {
    $jadwal = get_jadwal_jalur($id_jalur);
    
    if (!$jadwal) {
        return [
            'diizinkan' => false,
            'pesan' => 'Jadwal jalur tidak ditemukan. Silakan atur jadwal jalur terlebih dahulu.'
        ];
    }
    
    $status = $jadwal['status'];
    $nama_jalur = $jadwal['nama_jalur'];
    
    // Map aksi ke status yang diperlukan
    $aksi_status = [
        'seleksi' => ['seleksi'],
        'publikasi' => ['pengumuman']
    ];
    
    // Status nama yang user-friendly
    $status_labels = [
        'belum_dibuka' => 'Belum Dibuka',
        'dibuka' => 'Dibuka',
        'ditutup' => 'Ditutup',
        'seleksi' => 'Seleksi',
        'pengumuman' => 'Pengumuman',
        'selesai' => 'Selesai'
    ];
    
    // Cek apakah aksi diizinkan
    $status_diizinkan = $aksi_status[$aksi] ?? [];
    $diizinkan = in_array($status, $status_diizinkan);
    
    if ($diizinkan) {
        return [
            'diizinkan' => true,
            'pesan' => 'Aksi diizinkan'
        ];
    } else {
        $status_butuh = implode(' / ', array_map(function($s) use ($status_labels) {
            return $status_labels[$s] ?? $s;
        }, $status_diizinkan));
        
        return [
            'diizinkan' => false,
            'pesan' => "Aksi ini hanya dapat dilakukan saat status jalur <strong>$nama_jalur</strong> adalah: <strong>$status_butuh</strong>. Status saat ini: <strong>{$status_labels[$status]}</strong>. Silakan ubah status jalur di menu Jadwal Jalur.",
            'status_saat_ini' => $status,
            'status_dibutuhkan' => $status_diizinkan
        ];
    }
}

/**
 * Update status jalur
 */
function update_status_jalur($id_jalur, $status_baru) {
    global $conn;
    $tahun = TAHUN_AJARAN;
    
    $sql = "UPDATE jadwal_jalur SET status = ? WHERE id_jalur = ? AND tahun_ajaran = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sis", $status_baru, $id_jalur, $tahun);
    return mysqli_stmt_execute($stmt);
}

/**
 * Sinkronisasi status jalur secara otomatis berdasarkan tanggal
 * 
 * HYBRID APPROACH:
 * - OTOMATIS: belum_dibuka ↔ dibuka ↔ ditutup (berdasarkan tanggal)
 * - MANUAL: seleksi, pengumuman, selesai (harus diubah admin)
 * 
 * Dipanggil otomatis saat halaman dimuat
 */
function sync_status_jalur_otomatis() {
    global $conn;
    $tahun = TAHUN_AJARAN;
    $today = date('Y-m-d');
    
    // Ambil semua jalur yang statusnya bisa di-auto-update
    $sql = "SELECT * FROM jadwal_jalur WHERE tahun_ajaran = ? AND status IN ('belum_dibuka', 'dibuka', 'ditutup')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tahun);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($jalur = mysqli_fetch_assoc($result)) {
        $new_status = null;
        $current_status = $jalur['status'];
        $tanggal_buka = $jalur['tanggal_buka'];
        $tanggal_tutup = $jalur['tanggal_tutup'];
        
        // Logika auto-update:
        if ($today < $tanggal_buka) {
            // Belum sampai tanggal buka
            if ($current_status != 'belum_dibuka') {
                $new_status = 'belum_dibuka';
            }
        } elseif ($today >= $tanggal_buka && $today <= $tanggal_tutup) {
            // Dalam rentang tanggal pendaftaran
            if ($current_status != 'dibuka') {
                $new_status = 'dibuka';
            }
        } elseif ($today > $tanggal_tutup) {
            // Sudah lewat tanggal tutup
            if ($current_status != 'ditutup') {
                $new_status = 'ditutup';
            }
        }
        
        // Update jika ada perubahan
        if ($new_status !== null) {
            $sql_update = "UPDATE jadwal_jalur SET status = ? WHERE id_jadwal = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "si", $new_status, $jalur['id_jadwal']);
            mysqli_stmt_execute($stmt_update);
        }
    }
}

// DISABLED: Status sekarang disinkronisasi dari Pengaturan Global
// Lihat admin/pengaturan.php untuk sinkronisasi status
// sync_status_jalur_otomatis();
?>
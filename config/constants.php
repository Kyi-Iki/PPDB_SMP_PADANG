<?php
/**
 * File: config/constants.php
 * Deskripsi: Konstanta global untuk aplikasi PPDB
 * 
 * Gunakan file ini untuk mengatur konfigurasi yang sering berubah
 * seperti tahun ajaran, batas upload, dll.
 */

// =====================================================
// KONFIGURASI TAHUN AJARAN
// =====================================================
// Ubah nilai ini setiap tahun ajaran baru
define('TAHUN_AJARAN', '2025/2026');
define('TAHUN_AJARAN_SINGKAT', '2026');

// =====================================================
// KONFIGURASI APLIKASI
// =====================================================
define('APP_NAME', 'PPDB SMP');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistem Informasi Penerimaan Peserta Didik Baru SMP');

// =====================================================
// KONFIGURASI UPLOAD
// =====================================================
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB dalam bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/dokumen/');

// =====================================================
// KONFIGURASI KUOTA DEFAULT (Sesuai Aturan Kota Padang)
// =====================================================
define('KUOTA_ZONASI_PERSEN', 45);    // Domisili/Zonasi
define('KUOTA_PRESTASI_PERSEN', 27);  // Prestasi Akademik
define('KUOTA_AFIRMASI_PERSEN', 23);  // Ekonomi Lemah
define('KUOTA_MUTASI_PERSEN', 5);     // Perpindahan Orang Tua

// =====================================================
// KONFIGURASI KONTAK
// =====================================================
define('KONTAK_TELEPON', '(0751) 123456');
define('KONTAK_EMAIL', 'info@ppdb-smp.sch.id');
define('KONTAK_ALAMAT', 'Padang, Sumatera Barat');

// =====================================================
// TIMEZONE
// =====================================================
date_default_timezone_set('Asia/Jakarta');

// =====================================================
// HELPER FUNCTION
// =====================================================
/**
 * Get human-readable file size limit
 */
function get_max_upload_size_readable() {
    $size = MAX_UPLOAD_SIZE;
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * Check if file extension is allowed
 */
function is_extension_allowed($extension) {
    return in_array(strtolower($extension), ALLOWED_EXTENSIONS);
}
?>

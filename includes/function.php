<?php
/**
 * File: includes/function.php
 * Deskripsi: Helper functions tambahan untuk sistem
 * 
 * CATATAN: File ini berisi fungsi-fungsi helper yang TIDAK duplikat
 * dengan database.php. Fungsi yang sudah ada di database.php
 * sudah dihapus untuk menghindari redundansi.
 * 
 * Fungsi dari database.php yang sudah tersedia:
 * - clean_input(), check_login(), check_admin(), check_siswa()
 * - log_aktivitas(), generate_nomor_pendaftaran(), hitung_jarak()
 * - upload_file(), tanggal_indonesia(), format_rupiah()
 * - Semua fungsi validasi jadwal dan jalur PPDB
 */

// =====================================================
// FUNGSI FORMAT
// =====================================================

/**
 * Format file size ke readable format
 */
if (!function_exists('format_file_size')) {
    function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return '1 byte';
        }
        return '0 bytes';
    }
}

/**
 * Generate random string
 */
if (!function_exists('generate_random_string')) {
    function generate_random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}

// =====================================================
// FUNGSI VALIDASI
// =====================================================

/**
 * Validasi NISN (10 digit)
 */
if (!function_exists('validate_nisn')) {
    function validate_nisn($nisn) {
        return preg_match('/^[0-9]{10}$/', $nisn);
    }
}

/**
 * Validasi NIK (16 digit)
 */
if (!function_exists('validate_nik')) {
    function validate_nik($nik) {
        return preg_match('/^[0-9]{16}$/', $nik);
    }
}

/**
 * Validasi email
 */
if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

/**
 * Validasi nomor telepon Indonesia (08xxxxxxxxxx)
 */
if (!function_exists('validate_phone')) {
    function validate_phone($phone) {
        return preg_match('/^08[0-9]{8,11}$/', $phone);
    }
}

// =====================================================
// FUNGSI FILE
// =====================================================

/**
 * Sanitize filename
 */
if (!function_exists('sanitize_filename')) {
    function sanitize_filename($filename) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
}

/**
 * Check file extension
 */
if (!function_exists('check_file_extension')) {
    function check_file_extension($filename, $allowed_extensions) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, $allowed_extensions);
    }
}

// =====================================================
// FUNGSI TANGGAL
// =====================================================

/**
 * Calculate age dari tanggal lahir
 */
if (!function_exists('calculate_age')) {
    function calculate_age($birthdate) {
        $birthDate = new DateTime($birthdate);
        $today = new DateTime();
        return $today->diff($birthDate)->y;
    }
}

/**
 * Get hari dalam bahasa Indonesia
 */
if (!function_exists('get_hari_indonesia')) {
    function get_hari_indonesia($date) {
        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        return $days[date('l', strtotime($date))];
    }
}

/**
 * Get bulan dalam bahasa Indonesia
 */
if (!function_exists('get_bulan_indonesia')) {
    function get_bulan_indonesia($month_number) {
        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        return $months[(int)$month_number];
    }
}

// =====================================================
// FUNGSI SESSION & MESSAGE
// =====================================================

/**
 * Redirect dengan flash message
 */
if (!function_exists('redirect_with_message')) {
    function redirect_with_message($url, $type, $message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
        header("Location: $url");
        exit();
    }
}

/**
 * Display flash message (auto clear setelah ditampilkan)
 */
if (!function_exists('display_message')) {
    function display_message() {
        if (isset($_SESSION['message'])) {
            $type = $_SESSION['message_type'] ?? 'info';
            $icon = match($type) {
                'success' => 'check-circle',
                'error', 'danger' => 'exclamation-circle',
                'warning' => 'exclamation-triangle',
                default => 'info-circle'
            };
            
            echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
            echo '<i class="fas fa-' . $icon . ' me-2"></i>';
            echo $_SESSION['message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            
            unset($_SESSION['message'], $_SESSION['message_type']);
        }
    }
}

/**
 * Check if user logged in
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['id_user']) && isset($_SESSION['role']);
    }
}

/**
 * Get current user role
 */
if (!function_exists('get_user_role')) {
    function get_user_role() {
        return $_SESSION['role'] ?? null;
    }
}
?>
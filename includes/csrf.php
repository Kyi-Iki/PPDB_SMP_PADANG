<?php
/**
 * File: includes/csrf.php
 * Deskripsi: CSRF Protection Helper Functions
 * 
 * Fungsi-fungsi untuk melindungi form dari serangan Cross-Site Request Forgery
 */

/**
 * Generate atau ambil CSRF token dari session
 * @return string Token CSRF
 */
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Generate hidden input field untuk CSRF token
 * @return string HTML hidden input
 */
function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verifikasi CSRF token dari request
 * @param bool $regenerate Regenerate token setelah verifikasi (default: true)
 * @return bool True jika valid, false jika tidak
 */
function verify_csrf($regenerate = true) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    
    if (empty($token) || empty($session_token)) {
        return false;
    }
    
    $valid = hash_equals($session_token, $token);
    
    // Regenerate token setelah verifikasi berhasil untuk mencegah reuse
    if ($valid && $regenerate) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $valid;
}

/**
 * Verifikasi CSRF dan redirect jika gagal
 * @param string $redirect_url URL redirect jika verifikasi gagal
 * @param string $error_message Pesan error
 */
function require_csrf($redirect_url = null, $error_message = 'Sesi tidak valid. Silakan coba lagi.') {
    if (!verify_csrf()) {
        if ($redirect_url) {
            $_SESSION['error'] = $error_message;
            header("Location: $redirect_url");
            exit();
        } else {
            http_response_code(403);
            die($error_message);
        }
    }
}

/**
 * Generate meta tag untuk CSRF (berguna untuk AJAX)
 * @return string HTML meta tag
 */
function csrf_meta() {
    return '<meta name="csrf-token" content="' . csrf_token() . '">';
}
?>

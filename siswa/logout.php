<?php
/**
 * File: siswa/logout.php
 * Deskripsi: Logout untuk siswa/calon peserta didik
 */

session_start();
require_once '../config/database.php';

// Log aktivitas sebelum logout
if (isset($_SESSION['id_user'])) {
    log_aktivitas($_SESSION['id_user'], 'Logout', 'Siswa logout dari sistem');
}

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke landing page
header("Location: ../index.php");
exit();
?>

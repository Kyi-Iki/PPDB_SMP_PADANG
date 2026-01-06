<?php
/**
 * File: admin/logout.php
 * Deskripsi: Logout untuk admin
 */

session_start();
require_once '../config/database.php';

// Log aktivitas sebelum logout
if (isset($_SESSION['id_user'])) {
    log_aktivitas($_SESSION['id_user'], 'Logout', 'Admin logout dari sistem');
}

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke login admin
header("Location: login.php");
exit();
?>
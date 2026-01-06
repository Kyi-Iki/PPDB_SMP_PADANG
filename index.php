<?php
/**
 * File: index.php
 * Deskripsi: Landing page utama PPDB SMP
 */

session_start();
require_once 'config/database.php';

// Ambil pengaturan PPDB dengan prepared statement
$tahun_ajaran = TAHUN_AJARAN;
$sql_pengaturan = "SELECT * FROM pengaturan_ppdb WHERE tahun_ajaran = ? LIMIT 1";
$stmt_p = mysqli_prepare($conn, $sql_pengaturan);
mysqli_stmt_bind_param($stmt_p, "s", $tahun_ajaran);
mysqli_stmt_execute($stmt_p);
$pengaturan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_p));

// Statistik dengan prepared statement
// Statistik dengan prepared statement
$sql_stats = "SELECT 
                (SELECT COUNT(*) FROM pendaftaran WHERE tahun_ajaran = ?) as total_pendaftar,
                (SELECT COUNT(*) FROM sekolah WHERE status = 'aktif') as total_sekolah,
                (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE tahun_ajaran = ?) as total_kuota,
                (SELECT COUNT(*) FROM hasil_seleksi hs 
                 JOIN pendaftaran p ON hs.id_pendaftaran = p.id_pendaftaran 
                 WHERE p.tahun_ajaran = ? AND hs.status_seleksi = 'lulus' AND hs.dipublikasikan = 1) as total_diterima";
$stmt_s = mysqli_prepare($conn, $sql_stats);
mysqli_stmt_bind_param($stmt_s, "sss", $tahun_ajaran, $tahun_ajaran, $tahun_ajaran);
mysqli_stmt_execute($stmt_s);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_s));

// Daftar sekolah untuk list (Semua untuk fitur search)
// Gunakan subquery untuk menghitung total kuota per sekolah secara dinamis
$sql_sekolah = "SELECT s.*, 
                (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as kuota_dynamic
                FROM sekolah s 
                WHERE s.status = 'aktif' 
                ORDER BY s.nama_sekolah";
$sekolah_list = mysqli_query($conn, $sql_sekolah);

// Ambil daftar kecamatan untuk filter
$sql_kecamatan = "SELECT DISTINCT kecamatan FROM sekolah ORDER BY kecamatan";
$kecamatan_list = mysqli_query($conn, $sql_kecamatan);

// Daftar sekolah untuk peta (Semua)
// Daftar sekolah untuk peta (Semua)
$sql_sekolah_map = "SELECT s.*, 
                    (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as kuota_dynamic
                    FROM sekolah s WHERE s.status = 'aktif'";
$sekolah_map_result = mysqli_query($conn, $sql_sekolah_map);
$sekolah_map_data = [];
while ($s = mysqli_fetch_assoc($sekolah_map_result)) {
    // Overwrite static quota with dynamic calculation
    $s['kuota_total'] = $s['kuota_dynamic'] ?? 0;
    $sekolah_map_data[] = $s;
}

// Ambil jadwal jalur untuk ditampilkan
$jadwal_jalur = get_semua_jadwal_jalur();

// Cek apakah ada jalur yang dibuka (status pendaftaran)
$ada_jalur_dibuka = false;
foreach ($jadwal_jalur as $jj) {
    if ($jj['status'] == 'pendaftaran') {
        $ada_jalur_dibuka = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPDB SMP <?php echo TAHUN_AJARAN; ?> - Penerimaan Peserta Didik Baru</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Sistem Informasi Penerimaan Peserta Didik Baru (PPDB) SMP Tahun Ajaran <?php echo TAHUN_AJARAN; ?>. Daftar online dengan mudah, cepat, dan transparan.">
    <meta name="keywords" content="PPDB, SMP, Pendaftaran, Siswa Baru, <?php echo TAHUN_AJARAN_SINGKAT; ?>, Penerimaan Peserta Didik Baru">
    <meta name="author" content="PPDB SMP">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
    
    <!-- Design System CSS -->
    <link href="assets/css/design-system.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <style>
        /* ================================
           INDEX.PHP - Minimal Overrides
           Uses design-system.css variables
           ================================ */
        
        body {
            overflow-x: hidden;
            background: #e8eef3;
        }
        
        /* Global Section Styling */
        .content-section {
            background: #e8eef3;
            padding: 80px 0;
        }
        
        /* White container cards that stand out */
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        /* Fix Black Outline on Map Click */
        path.leaflet-interactive:focus {
            outline: none;
        }
        
        /* Hero Section - Override for landing page */
        .hero-section {
            background: var(--gradient-hero);
            color: white;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }
        
        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            color: white;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            color: rgba(255,255,255,0.95);
            font-weight: 400;
        }
        
        /* Navbar Logo */
        .navbar-logo {
            height: 32px;
            width: auto;
            object-fit: contain;
        }
        
        /* Tut Wuri Logo - Hero Section */
        .hero-logo-container {
            width: 100%;
        }
        
        .tut-wuri-wrapper {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            padding: 3rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: inline-block;
            transition: all 0.4s ease;
        }
        
        .tut-wuri-wrapper:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .tut-wuri-logo {
            height: 280px;
            width: auto;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));
        }
        
        .hero-buttons .btn {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--radius-full);
            margin: 0.5rem;
            transition: all var(--transition-base);
        }
        
        .hero-buttons .btn-light {
            background: white !important;
            color: var(--primary-700) !important;
        }
        
        .hero-buttons .btn-light:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255,255,255,0.3);
        }
        
        .hero-buttons .btn-outline-light:hover {
            background: white !important;
            color: var(--primary-700) !important;
            transform: translateY(-3px);
        }
        
        /* Stats Section */
        .stats-section {
            margin-top: -60px;
            position: relative;
            z-index: 2;
        }
        
        /* Landing page stat cards override */
        .stats-section .stat-card {
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
        }
        
        .stats-section .stat-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient-primary);
            border-radius: 50%;
            font-size: 1.8rem;
        }
        
        .stats-section .stat-icon.green { 
            background: linear-gradient(135deg, var(--success-600) 0%, var(--success-500) 100%); 
        }
        
        .stats-section .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-700);
        }
        
        .stats-section .stat-label {
            color: var(--gray-500);
        }
        
        /* School List Cards */
        .school-list-card {
            transition: all var(--transition-base);
            border-radius: var(--radius-lg);
        }
        
        .school-list-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(30, 58, 95, 0.2) !important;
            border-left: 4px solid var(--primary-700) !important;
        }
        
        .school-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-100);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Schedule Section */
        .schedule-section {
            padding: 80px 0;
            background: #e8eef3;
        }
        
        .schedule-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }
        
        .schedule-item i { 
            width: 30px; 
            color: var(--primary-700); 
        }
        
        /* Timeline Section */
        .timeline-section {
            padding: 80px 0;
            background: #e8eef3;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 2rem;
        }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .timeline-content {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }
        
        /* Jalur PPDB Cards - Landing page override */
        .jalur-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .jalur-card:hover {
            border-color: var(--primary-700);
            box-shadow: 0 10px 30px rgba(30, 58, 95, 0.2);
        }
        
        .jalur-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            font-size: 1.5rem;
        }
        
        /* School Cards - Enhanced */
        .school-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
        }
        
        .school-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(30, 58, 95, 0.2);
            border-color: transparent;
        }
        
        .school-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: white;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 16px 16px 0 0;
        }
        
        .school-header i {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .school-body {
            padding: 1.5rem;
            background: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .hero-buttons .btn {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }
            
            .stats-section .stat-number {
                font-size: 2rem;
            }
        }
        
        /* Sekolah Section Enhanced */
        .sekolah-section {
            background: #e8eef3;
            position: relative;
        }
        
        .search-filter-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .search-input-wrapper {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            z-index: 2;
        }
        
        .search-input {
            padding-left: 45px !important;
            border-radius: 12px !important;
            border: 2px solid #e5e7eb !important;
            height: 48px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1) !important;
        }
        
        .filter-select {
            border-radius: 12px !important;
            border: 2px solid #e5e7eb !important;
            height: 48px;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1) !important;
        }
        
        .school-scroll-container {
            background: transparent;
        }
        
        /* Button Modern Styling */
        #resetFilterBtn {
            height: 48px;
            border-radius: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top main-navbar" style="z-index: 999;">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <div class="brand-icon me-2">
                    <img src="assets/images/logo-kota-padang.png" 
                         alt="Logo Kota Padang" 
                         class="navbar-logo"
                         onerror="this.outerHTML='<i class=\'fas fa-graduation-cap\'></i>'">
                </div>
                <span class="brand-text">PPDB SMP <?php echo TAHUN_AJARAN_SINGKAT; ?></span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="#tentang">
                            <i class="fas fa-info-circle me-1 d-lg-none"></i>Tentang
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="#jadwal">
                            <i class="fas fa-calendar me-1 d-lg-none"></i>Jadwal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="#jalur">
                            <i class="fas fa-route me-1 d-lg-none"></i>Jalur PPDB
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="#sekolah">
                            <i class="fas fa-school me-1 d-lg-none"></i>Sekolah
                        </a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-login-modern" href="siswa/login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <style>
    /* Modern Navbar Styles */
    .main-navbar {
        padding: 1rem 0;
        transition: all 0.4s ease;
    }
    
    .main-navbar.scrolled {
        background: rgba(30, 58, 95, 0.95) !important;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
        padding: 0.5rem 0;
    }
    
    .brand-icon {
        width: 42px;
        height: 42px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .navbar-brand:hover .brand-icon {
        background: rgba(255, 255, 255, 0.25);
        transform: rotate(-5deg) scale(1.05);
    }
    
    .brand-text {
        font-size: 1.25rem;
        letter-spacing: 0.5px;
    }
    
    .nav-link-modern {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 500;
        padding: 0.6rem 1rem !important;
        border-radius: 10px;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .nav-link-modern::after {
        content: '';
        position: absolute;
        bottom: 5px;
        left: 50%;
        width: 0;
        height: 2px;
        background: white;
        transition: all 0.3s ease;
        transform: translateX(-50%);
        border-radius: 2px;
    }
    
    .nav-link-modern:hover {
        color: white !important;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .nav-link-modern:hover::after {
        width: 30px;
    }
    
    .btn-login-modern {
        background: white !important;
        color: #1e3a5f !important;
        font-weight: 600;
        padding: 0.6rem 1.5rem !important;
        border-radius: 50px !important;
        border: 2px solid white !important;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .btn-login-modern:hover {
        background: transparent !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 255, 255, 0.2);
    }
    
    .navbar-toggler:focus {
        box-shadow: none;
    }
    
    @media (max-width: 991px) {
        .main-navbar {
            background: rgba(30, 58, 95, 0.98) !important;
            backdrop-filter: blur(20px);
        }
        
        .navbar-collapse {
            padding: 1rem 0;
        }
        
        .nav-link-modern {
            padding: 0.75rem 1rem !important;
        }
        
        .nav-link-modern::after {
            display: none;
        }
        
        .btn-login-modern {
            margin-top: 0.5rem;
            text-align: center;
            display: block;
        }
    }
    </style>
    
    <script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.main-navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    </script>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 hero-content">
                    <!-- Status Badge -->
                    <div class="mb-4">
                        <?php if ($ada_jalur_dibuka): ?>
                        <span class="status-badge open">
                            <i class="fas fa-check-circle me-2"></i>PENDAFTARAN DIBUKA
                        </span>
                        <?php else: ?>
                        <span class="status-badge closed">
                            <i class="fas fa-times-circle me-2"></i>PENDAFTARAN DITUTUP
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="hero-title">
                        Pendaftaran<br>Peserta Didik Baru<br>
                        <span style="color: #ffd700;">SMP <?php echo TAHUN_AJARAN; ?></span>
                    </h1>
                    <p class="hero-subtitle">
                        Sistem informasi penerimaan peserta didik baru tingkat SMP secara online.
                        Mudah, cepat, dan transparan!
                    </p>
                    <div class="hero-buttons">
                        <?php if ($ada_jalur_dibuka): ?>
                        <a href="siswa/register.php" class="btn btn-light">
                            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                        </a>
                        <?php else: ?>
                        <a href="#jadwal" class="btn btn-light">
                            <i class="fas fa-calendar me-2"></i>Lihat Jadwal
                        </a>
                        <?php endif; ?>
                        <a href="panduan.php" class="btn btn-outline-light">
                            <i class="fas fa-book me-2"></i>Panduan Pendaftaran
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-flex align-items-center justify-content-center">
                    <div class="hero-logo-container text-center">
                        <!-- Logo Tut Wuri Handayani - Centered & Larger -->
                        <div class="tut-wuri-wrapper">
                            <img src="assets/images/logo-tut-wuri.png" 
                                 alt="Logo Tut Wuri Handayani" 
                                 class="tut-wuri-logo"
                                 onerror="this.parentElement.innerHTML='<i class=\'fas fa-graduation-cap\' style=\'font-size: 12rem; opacity: 0.2;\'></i>'">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['total_pendaftar']); ?></div>
                        <div class="stat-label">Total Pendaftar</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['total_diterima'] ?? 0); ?></div>
                        <div class="stat-label">Siswa Diterima</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_sekolah']; ?></div>
                        <div class="stat-label">Sekolah Tersedia</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chair"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['total_kuota'] ?? 0); ?></div>
                        <div class="stat-label">Total Kuota</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section py-5 position-relative" style="z-index: 1; background: linear-gradient(135deg, #e8eef3 0%, #f5f7fa 100%);">
        <div class="container">
            <!-- Section Header -->
            <div class="text-center mb-4">
                <span class="badge bg-primary-subtle text-primary mb-3 px-4 py-2 rounded-pill fw-bold">
                    <i class="fas fa-map-marked-alt me-2"></i>PETA ZONASI
                </span>
                <h2 class="section-title mb-2">Peta Sebaran Sekolah</h2>
                <p class="section-subtitle text-muted">Lihat lokasi SMP Negeri di Kota Padang untuk merencanakan pendaftaran Anda</p>
            </div>
            
            <!-- Map Container with Floating Panel -->
            <div class="position-relative">
                <!-- Floating Stats Panel -->
                <div class="position-absolute" style="top: 16px; right: 16px; z-index: 1000; width: 220px;">
                    <div class="card border-0 shadow" style="border-radius: 12px; backdrop-filter: blur(10px); background: rgba(255,255,255,0.95);">
                        <div class="card-body p-3">
                            <!-- Stats Row -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 45px; height: 45px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-school text-white"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold text-primary mb-0"><?php echo $stats['total_sekolah']; ?></h4>
                                    <small class="text-muted">Sekolah Tersedia</small>
                                </div>
                            </div>
                            
                            <!-- Quick Stats -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="bg-light rounded p-2 text-center">
                                        <div class="fw-bold text-info small">11</div>
                                        <div class="text-muted" style="font-size: 10px;">Kecamatan</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2 text-center">
                                        <div class="fw-bold text-success small"><?php echo number_format($stats['total_kuota'] ?? 0); ?></div>
                                        <div class="text-muted" style="font-size: 10px;">Total Kuota</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Toggle Switch -->
                            <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background: #f8f9fa;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-layer-group text-primary me-2"></i>
                                    <span class="small fw-medium">Polygon</span>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="polygonToggle" checked 
                                           onchange="togglePolygons()" style="cursor: pointer; width: 40px; height: 20px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Map -->
                <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 16px;">
                    <div id="landingMap" style="height: 520px; width: 100%;"></div>
                </div>
                
                <!-- Bottom Legend Bar -->
                <div class="d-flex justify-content-center gap-4 mt-3">
                    <div class="d-flex align-items-center">
                        <img src="https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png" alt="marker" style="height: 18px;" class="me-2">
                        <span class="small text-muted">Lokasi Sekolah</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-2" style="width: 18px; height: 12px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 2px;"></div>
                        <span class="small text-muted">Batas Kecamatan</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-mouse-pointer text-muted me-2" style="font-size: 12px;"></i>
                        <span class="small text-muted">Klik marker untuk detail</span>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Sekolah Section (MOVED HERE) -->
    <section class="sekolah-section py-5" id="sekolah">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary px-4 py-2 rounded-pill fw-bold mb-3">
                    <i class="fas fa-school me-2"></i>DAFTAR SEKOLAH
                </span>
                <h2 class="section-title mb-2">Sekolah Tersedia</h2>
                <p class="section-subtitle">Daftar SMP yang membuka PPDB <?php echo TAHUN_AJARAN; ?></p>
            </div>
            
            <!-- Search & Filter Controls -->
            <div class="search-filter-card mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="landingSearchInput" class="form-control search-input" 
                                   placeholder="Cari nama sekolah atau NPSN...">
                        </div>
                    </div>
                <div class="col-md-4">
                    <select id="kecamatanFilter" class="form-select filter-select">
                        <option value="">Semua Kecamatan</option>
                        <?php 
                        $list_kecamatan = [
                            "Bungus Teluk Kabung", "Koto Tangah", "Kuranji", 
                            "Lubuk Begalung", "Lubuk Kilangan", "Nanggalo", 
                            "Padang Barat", "Padang Selatan", "Padang Timur", 
                            "Padang Utara", "Pauh"
                        ];
                        foreach($list_kecamatan as $kec): 
                        ?>
                        <option value="<?php echo $kec; ?>"><?php echo $kec; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button id="resetFilterBtn" class="btn btn-outline-primary w-100">
                        <i class="fas fa-undo me-2"></i>Reset Filter
                    </button>
                </div>
                </div>
                <div class="text-end mt-2">
                    <small class="text-muted" id="schoolCountLabel">
                        <i class="fas fa-info-circle me-1"></i>Menampilkan semua sekolah
                    </small>
                </div>
            </div>

            <!-- Scrollable Grid Container -->
            <div class="school-scroll-container" style="max-height: 800px; overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
                <div class="row g-4" id="schoolGrid">
                    <?php while ($sekolah = mysqli_fetch_assoc($sekolah_list)): ?>
                    <div class="col-md-6 col-lg-4 school-item" 
                         data-name="<?php echo strtolower($sekolah['nama_sekolah']); ?>" 
                         data-npsn="<?php echo $sekolah['npsn']; ?>"
                         data-kecamatan="<?php echo $sekolah['kecamatan']; ?>">
                        <div class="school-card h-100">
                            <!-- School Photo/Header -->
                            <div class="position-relative">
                                <?php if (!empty($sekolah['foto']) && file_exists('uploads/sekolah/' . $sekolah['foto'])): ?>
                                    <div style="height: 160px; overflow: hidden; border-radius: 12px 12px 0 0;">
                                        <img src="uploads/sekolah/<?php echo $sekolah['foto']; ?>" 
                                             alt="<?php echo $sekolah['nama_sekolah']; ?>"
                                             class="w-100 h-100" style="object-fit: cover;">
                                    </div>
                                    <div class="px-3 pt-3 pb-0">
                                        <h6 class="fw-bold mb-0 text-primary">
                                            <i class="fas fa-school me-2"></i><?php echo $sekolah['nama_sekolah']; ?>
                                        </h6>
                                    </div>
                                <?php else: ?>
                                    <div class="school-header">
                                        <i class="fas fa-school me-2"></i>
                                        <?php echo $sekolah['nama_sekolah']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="school-body mt-auto">
                                <p class="mb-2">
                                    <i class="fas fa-id-card text-muted me-2"></i>
                                    <small>NPSN: <?php echo $sekolah['npsn']; ?></small>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                    <small><?php echo $sekolah['kecamatan']; ?></small>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-chair text-success me-2"></i>
                                    <small>Kuota: <strong><?php echo $sekolah['kuota_dynamic'] ?? 0; ?> siswa</strong></small>
                                </p>
                                <p class="mb-3">
                                    <i class="fas fa-check-circle text-info me-2"></i>
                                    <small>Status: <span class="badge bg-success">Aktif</span></small>
                                </p>
                                <a href="sekolah_detail.php?id=<?php echo $sekolah['id_sekolah']; ?>" 
                                   class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-info-circle me-1"></i>Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- No Results Message -->
                <div id="noResultsMsg" class="text-center py-5 d-none">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/search-not-found-illustration-download-in-svg-png-gif-file-formats--zoom-magnifier-data-user-interface-pack-design-development-illustrations-6632131.png" 
                         alt="Not Found" style="width: 150px; opacity: 0.7;">
                    <h5 class="mt-3 text-muted">Sekolah tidak ditemukan</h5>
                    <p class="text-muted small">Coba kata kunci lain atau reset filter.</p>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="siswa/register.php" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-rocket me-2"></i>Mulai Pendaftaran
                </a>
            </div>
        </div>
    </section>

    <!-- Schedule Section (NEW) -->
    <section class="schedule-section" id="jadwal">
        <div class="container">
            <h2 class="section-title">Jadwal Pendaftaran</h2>
            <p class="section-subtitle">Jadwal pendaftaran per jalur PPDB <?php echo TAHUN_AJARAN; ?></p>
            
            <div class="row g-4">
                <?php foreach ($jadwal_jalur as $jj): 
                    $bg_color = match($jj['nama_jalur']) {
                        'Zonasi' => '#667eea',
                        'Prestasi' => '#11998e',
                        'Afirmasi' => '#f5576c',
                        'Mutasi' => '#f093fb',
                        default => '#667eea'
                    };
                    
                    $status_badge = match($jj['status']) {
                        'belum_dibuka' => '<span class="badge bg-secondary">Belum Dibuka</span>',
                        'pendaftaran' => '<span class="badge bg-success">Pendaftaran</span>',
                        'seleksi' => '<span class="badge bg-warning text-dark">Seleksi</span>',
                        'pengumuman' => '<span class="badge bg-info">Pengumuman</span>',
                        'daftar_ulang' => '<span class="badge bg-primary">Daftar Ulang</span>',
                        'selesai' => '<span class="badge bg-dark">Selesai</span>',
                        default => '<span class="badge bg-secondary">' . ucfirst($jj['status']) . '</span>'
                    };
                ?>
                <div class="col-md-6 col-lg-3">
                    <div class="schedule-card">
                        <div class="schedule-header" style="background: <?php echo $bg_color; ?>;">
                            <i class="fas fa-route me-2"></i><?php echo $jj['nama_jalur']; ?>
                            <?php echo $status_badge; ?>
                        </div>
                        <div class="schedule-body">
                            <div class="schedule-item">
                                <i class="fas fa-calendar-plus"></i>
                                <div>
                                    <small class="text-muted d-block">Pendaftaran Dibuka</small>
                                    <strong><?php echo date('d M Y', strtotime($jj['tanggal_buka'])); ?></strong>
                                </div>
                            </div>
                            <div class="schedule-item">
                                <i class="fas fa-calendar-times"></i>
                                <div>
                                    <small class="text-muted d-block">Pendaftaran Ditutup</small>
                                    <strong><?php echo date('d M Y', strtotime($jj['tanggal_tutup'])); ?></strong>
                                </div>
                            </div>
                            <div class="schedule-item">
                                <i class="fas fa-bullhorn"></i>
                                <div>
                                    <small class="text-muted d-block">Pengumuman</small>
                                    <strong><?php echo date('d M Y', strtotime($jj['tanggal_pengumuman'])); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section" id="tentang">
        <div class="container">
            <h2 class="section-title">Alur Pendaftaran PPDB</h2>
            <p class="section-subtitle">Ikuti langkah-langkah berikut untuk mendaftar</p>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="timeline-item">
                        <div class="timeline-icon">1</div>
                        <div class="timeline-content">
                            <h5 class="fw-bold">Registrasi Akun</h5>
                            <p class="text-muted mb-0">Buat akun dengan NISN dan NIK. Pastikan data yang dimasukkan valid.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">2</div>
                        <div class="timeline-content">
                            <h5 class="fw-bold">Lengkapi Biodata</h5>
                            <p class="text-muted mb-0">Isi biodata pribadi, alamat, dan data orang tua dengan lengkap dan benar.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">3</div>
                        <div class="timeline-content">
                            <h5 class="fw-bold">Upload Dokumen</h5>
                            <p class="text-muted mb-0">Upload Kartu Keluarga, Ijazah/SKL, dan Rapor dalam format PDF/JPG.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">4</div>
                        <div class="timeline-content">
                            <h5 class="fw-bold">Pilih Jalur & Sekolah</h5>
                            <p class="text-muted mb-0">Pilih jalur PPDB (Zonasi/Prestasi/Afirmasi/Mutasi) dan sekolah tujuan.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">5</div>
                        <div class="timeline-content">
                            <h5 class="fw-bold">Tunggu Pengumuman</h5>
                            <p class="text-muted mb-0">Pantau hasil seleksi dan lakukan daftar ulang jika diterima.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Jalur PPDB Section -->
    <section class="jalur-section py-5" id="jalur" style="background: #e8eef3;">
        <div class="container">
            <h2 class="section-title">Jalur PPDB SMP</h2>
            <p class="section-subtitle">Pilih jalur yang sesuai dengan kondisi Anda</p>
            
            <!-- Important Rule Alert -->
            <div class="alert alert-warning border-warning mb-4 shadow-sm" style="border-left: 5px solid #ffc107 !important;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1">⚠️ Aturan Penting</h5>
                        <p class="mb-0">Setiap peserta <strong>hanya boleh mendaftar maksimal 2 (dua) SMP dalam 1 (satu) jalur</strong>. Pastikan pilihan Anda sudah tepat karena <strong>tidak bisa diubah</strong> setelah pendaftaran dikunci.</p>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="jalur-card">
                        <div class="jalur-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5 class="fw-bold mb-2">🏠 Jalur Zonasi</h5>
                        <p class="text-primary fw-semibold mb-2">Kuota: <?php echo KUOTA_ZONASI_PERSEN; ?>%</p>
                        <p class="text-muted small mb-3">Berdasarkan jarak tempat tinggal ke sekolah.</p>
                        <div class="bg-light p-2 rounded small">
                            <strong class="text-dark">Cocok untuk:</strong><br>
                            ✅ Warga dengan jarak dekat ke sekolah<br>
                            ✅ Punya KK domisili minimal 1 tahun
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="jalur-card">
                        <div class="jalur-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h5 class="fw-bold mb-2">🏆 Jalur Prestasi</h5>
                        <p class="text-success fw-semibold mb-2">Kuota: <?php echo KUOTA_PRESTASI_PERSEN; ?>%</p>
                        <p class="text-muted small mb-3">Berdasarkan nilai rapor semester 1-5 SD.</p>
                        <div class="bg-light p-2 rounded small">
                            <strong class="text-dark">Cocok untuk:</strong><br>
                            ✅ Siswa dengan nilai rapor tinggi<br>
                            ✅ Punya sertifikat prestasi lomba
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="jalur-card">
                        <div class="jalur-icon" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h5 class="fw-bold mb-2">💝 Jalur Afirmasi</h5>
                        <p class="text-danger fw-semibold mb-2">Kuota: <?php echo KUOTA_AFIRMASI_PERSEN; ?>%</p>
                        <p class="text-muted small mb-3">Untuk siswa dari keluarga kurang mampu.</p>
                        <div class="bg-light p-2 rounded small">
                            <strong class="text-dark">Cocok untuk:</strong><br>
                            ✅ Pemegang KIP/PKH/DTKS<br>
                            ✅ Anak yatim piatu/panti asuhan
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="jalur-card">
                        <div class="jalur-icon" style="background: linear-gradient(135deg, #f093fb 0%, #ec38bc 100%);">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h5 class="fw-bold mb-2">🔄 Jalur Mutasi</h5>
                        <p class="text-info fw-semibold mb-2">Kuota: <?php echo KUOTA_MUTASI_PERSEN; ?>%</p>
                        <p class="text-muted small mb-3">Untuk siswa yang ikut pindah orang tua.</p>
                        <div class="bg-light p-2 rounded small">
                            <strong class="text-dark">Cocok untuk:</strong><br>
                            ✅ Orang tua pindah tugas/kerja<br>
                            ✅ Punya surat pindah resmi
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5 class="fw-bold mb-3">PPDB SMP <?php echo TAHUN_AJARAN; ?></h5>
                    <p class="text-white-50">
                        Sistem Informasi Penerimaan Peserta Didik Baru<br>
                        Tingkat SMP <?php echo KONTAK_ALAMAT; ?>
                    </p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="siswa/login.php" class="text-white-50 text-decoration-none"><i class="fas fa-sign-in-alt me-2"></i>Login Siswa</a></li>
                        <li class="mb-2"><a href="admin/login.php" class="text-white-50 text-decoration-none"><i class="fas fa-user-shield me-2"></i>Login Admin</a></li>
                        <li class="mb-2"><a href="panduan.php" class="text-white-50 text-decoration-none"><i class="fas fa-book me-2"></i>Panduan</a></li>
                        <li class="mb-2"><a href="#jalur" class="text-white-50 text-decoration-none"><i class="fas fa-route me-2"></i>Jalur PPDB</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="fw-bold mb-3">Kontak</h6>
                    <p class="text-white-50 mb-2">
                        <i class="fas fa-phone me-2"></i><?php echo KONTAK_TELEPON; ?>
                    </p>
                    <p class="text-white-50 mb-2">
                        <i class="fas fa-envelope me-2"></i><?php echo KONTAK_EMAIL; ?>
                    </p>
                    <p class="text-white-50 mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i><?php echo KONTAK_ALAMAT; ?>
                    </p>
                </div>
            </div>
            <hr class="my-4 bg-white opacity-25">
            <div class="text-center text-white-50">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> PPDB SMP. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Map Logic -->
    <script src="assets/js/landing-map.js?v=<?php echo time(); ?>"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map with data
            const sekolahData = <?php echo json_encode($sekolah_map_data); ?>;
            initLandingMap(sekolahData);
        });
    </script>
    <!-- Search & Filter Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if elements exist (to prevent errors if section is modified)
            const searchInput = document.getElementById('landingSearchInput');
            const districtFilter = document.getElementById('kecamatanFilter');
            const resetBtn = document.getElementById('resetFilterBtn');
            const schoolItems = document.querySelectorAll('.school-item');
            const schoolCountLabel = document.getElementById('schoolCountLabel');
            const noResultsMsg = document.getElementById('noResultsMsg');
            const schoolGrid = document.getElementById('schoolGrid');

            if (searchInput && schoolItems.length > 0) {
                // Initialize visible count
                updateCount(schoolItems.length);

                function updateCount(count) {
                    if(schoolCountLabel) schoolCountLabel.textContent = count + ' Sekolah ditemukan';
                }

                function filterSchools() {
                    const searchValue = searchInput.value.toLowerCase();
                    const districtValue = districtFilter ? districtFilter.value : '';
                    let visibleCount = 0;

                    schoolItems.forEach(item => {
                        const name = item.dataset.name || '';
                        const npsn = item.dataset.npsn || '';
                        const district = item.dataset.kecamatan || '';

                        const matchesSearch = name.includes(searchValue) || npsn.includes(searchValue);
                        const matchesDistrict = districtValue === '' || district === districtValue;

                        if (matchesSearch && matchesDistrict) {
                            item.classList.remove('d-none'); // Unhide col
                            visibleCount++;
                        } else {
                            item.classList.add('d-none'); // Hide col
                        }
                    });

                    updateCount(visibleCount);
                    
                    if (visibleCount === 0) {
                        if(noResultsMsg) noResultsMsg.classList.remove('d-none');
                        if(schoolGrid) schoolGrid.classList.add('d-none');
                    } else {
                        if(noResultsMsg) noResultsMsg.classList.add('d-none');
                        if(schoolGrid) schoolGrid.classList.remove('d-none');
                    }
                }

                // Event Listeners
                searchInput.addEventListener('input', filterSchools);
                if(districtFilter) districtFilter.addEventListener('change', filterSchools);
                
                if(resetBtn) {
                    resetBtn.addEventListener('click', function() {
                        searchInput.value = '';
                        if(districtFilter) districtFilter.value = '';
                        filterSchools();
                    });
                }
            }
        });
    </script>
</body>
</html>
<?php
/**
 * File: panduan.php
 * Deskripsi: Halaman panduan PPDB untuk calon siswa
 */

session_start();
require_once 'config/database.php';

// Ambil jadwal jalur
$jadwal_jalur = get_semua_jadwal_jalur();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan PPDB - PPDB SMP <?php echo TAHUN_AJARAN; ?></title>
    
    <!-- SEO -->
    <meta name="description" content="Panduan lengkap pendaftaran PPDB SMP <?php echo TAHUN_AJARAN; ?>. Langkah-langkah, persyaratan, dan FAQ.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Design System CSS (sama dengan index.php) -->
    <link href="assets/css/design-system.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #e8eef3; }
        
        /* Navbar Styles (sama dengan index.php) */
        .main-navbar {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #1e3a5f 100%);
            padding: 1rem 0;
        }
        .brand-icon {
            width: 42px;
            height: 42px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }
        .navbar-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .brand-text {
            font-size: 1.3rem;
            font-weight: 700;
        }
        .nav-link-modern {
            color: rgba(255,255,255,0.85) !important;
            padding: 0.6rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .nav-link-modern:hover, .nav-link-modern.active {
            color: #fff !important;
            background: rgba(255,255,255,0.15);
        }
        .btn-login-modern {
            background: white;
            color: #1e3a5f;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
        }
        .btn-login-modern:hover {
            background: #f0f0f0;
            color: #1e3a5f;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #1e3a5f 100%);
            color: white;
            padding: 4rem 0;
        }
        .step-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            height: 100%;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .step-card:hover {
            transform: translateY(-5px);
        }
        .step-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .schedule-mini {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
        }
        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tip-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            border-radius: 0 10px 10px 0;
            margin: 1rem 0;
        }
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 1rem;
            border-radius: 0 10px 10px 0;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <!-- Navbar (sama dengan index.php) -->
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
                        <a class="nav-link nav-link-modern" href="index.php">
                            <i class="fas fa-home me-1 d-lg-none"></i>Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern active" href="panduan.php">
                            <i class="fas fa-book-open me-1 d-lg-none"></i>Panduan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="index.php#jadwal">
                            <i class="fas fa-calendar me-1 d-lg-none"></i>Jadwal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="index.php#sekolah">
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
    
    <!-- Hero Section -->
    <section class="hero-section" style="padding-top: 120px;">
        <div class="container text-center">
            <span class="badge px-4 py-2 mb-3" style="background: white; color: #1e3a5f; font-size: 0.9rem;">
                <i class="fas fa-book-reader me-2"></i>Panduan Lengkap
            </span>
            <h1 class="display-4 fw-bold text-white mb-3">
                Panduan Pendaftaran PPDB
            </h1>
            <p class="lead text-white opacity-75 mb-4">
                Ikuti langkah-langkah berikut untuk mendaftar PPDB SMP <?php echo TAHUN_AJARAN; ?>
            </p>
            <a href="#langkah" class="btn btn-lg px-4" style="background: white; color: #1e3a5f; font-weight: 600;">
                <i class="fas fa-arrow-down me-2"></i>Mulai
            </a>
        </div>
    </section>
    
    <!-- Jadwal Singkat -->
    <section class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Jadwal Pendaftaran</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($jadwal_jalur as $jj): 
                                    $status_class = match($jj['status']) {
                                        'pendaftaran' => 'success',
                                        'seleksi' => 'warning',
                                        'pengumuman' => 'info',
                                        'daftar_ulang' => 'primary',
                                        'selesai' => 'dark',
                                        default => 'secondary'
                                    };
                                ?>
                                <div class="col-md-3 mb-2">
                                    <div class="schedule-mini">
                                        <strong><?php echo $jj['nama_jalur']; ?></strong>
                                        <span class="badge bg-<?php echo $status_class; ?> float-end"><?php echo ucfirst(str_replace('_', ' ', $jj['status'])); ?></span>
                                        <div class="small text-muted mt-1">
                                            <?php echo date('d/m', strtotime($jj['tanggal_buka'])); ?> - <?php echo date('d/m/Y', strtotime($jj['tanggal_tutup'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Steps -->
    <section class="py-5">
        <div class="container">
            <h3 class="text-center fw-bold mb-5">
                <i class="fas fa-list-ol me-2 text-primary"></i>Langkah-langkah Pendaftaran
            </h3>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5 class="fw-bold">Registrasi Akun</h5>
                        <p class="text-muted">
                            Klik tombol "Daftar" dan isi data awal:
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>NISN (10 digit)</li>
                            <li><i class="fas fa-check text-success me-2"></i>NIK (16 digit) <span class="badge bg-warning text-dark">Wajib</span></li>
                            <li><i class="fas fa-check text-success me-2"></i>Nama Lengkap</li>
                            <li><i class="fas fa-check text-success me-2"></i>Email Aktif</li>
                            <li><i class="fas fa-check text-success me-2"></i>Username & Password</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5 class="fw-bold">Lengkapi Biodata</h5>
                        <p class="text-muted">
                            Login ke akun dan lengkapi data:
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Data Pribadi</li>
                            <li><i class="fas fa-check text-success me-2"></i>Alamat Lengkap</li>
                            <li><i class="fas fa-check text-success me-2"></i>Koordinat Rumah <span class="badge bg-info">Auto GPS</span></li>
                            <li><i class="fas fa-check text-success me-2"></i>Data Orang Tua</li>
                            <li><i class="fas fa-check text-success me-2"></i>Asal Sekolah</li>
                        </ul>
                        <div class="tip-box small">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Tips:</strong> Klik tombol "Gunakan Lokasi Saya" untuk mengisi koordinat otomatis!
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5 class="fw-bold">Upload Dokumen</h5>
                        <p class="text-muted">
                            Siapkan dan upload:
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-file-alt text-primary me-2"></i>Kartu Keluarga (KK)</li>
                            <li><i class="fas fa-file-alt text-primary me-2"></i>SKL/Ijazah SD</li>
                            <li><i class="fas fa-file-alt text-primary me-2"></i>Rapor (semester 1-5)</li>
                            <li><i class="fas fa-file-alt text-primary me-2"></i>Pas Foto 3x4</li>
                        </ul>
                        <small class="text-muted">Format: JPG, PNG, PDF (max <?php echo get_max_upload_size_readable(); ?>)</small>
                        <div class="warning-box small mt-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Jika dokumen ditolak, Anda akan menerima notifikasi dan harus upload ulang.
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h5 class="fw-bold">Pilih Sekolah & Jalur</h5>
                        <p class="text-muted">
                            Pilih jalur pendaftaran:
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-map-marker-alt text-info me-2"></i><strong>Zonasi</strong> (<?php echo KUOTA_ZONASI_PERSEN; ?>%) - Berdasarkan jarak</li>
                            <li><i class="fas fa-trophy text-warning me-2"></i><strong>Prestasi</strong> (<?php echo KUOTA_PRESTASI_PERSEN; ?>%) - Nilai rapor</li>
                            <li><i class="fas fa-heart text-danger me-2"></i><strong>Afirmasi</strong> (<?php echo KUOTA_AFIRMASI_PERSEN; ?>%) - Keluarga tidak mampu</li>
                            <li><i class="fas fa-exchange-alt text-success me-2"></i><strong>Mutasi</strong> (<?php echo KUOTA_MUTASI_PERSEN; ?>%) - Pindah domisili</li>
                        </ul>
                        <div class="tip-box small">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Tips:</strong> Pilih jalur yang paling sesuai dengan kriteria Anda!
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">5</div>
                        <h5 class="fw-bold">Cetak Kartu</h5>
                        <p class="text-muted">
                            Setelah pendaftaran selesai:
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-print text-primary me-2"></i>Cetak kartu pendaftaran</li>
                            <li><i class="fas fa-clock text-warning me-2"></i>Tunggu verifikasi admin</li>
                            <li><i class="fas fa-bell text-info me-2"></i>Pantau status di halaman Notifikasi</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">6</div>
                        <h5 class="fw-bold">Lihat Hasil</h5>
                        <p class="text-muted">
                            Saat pengumuman:
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-trophy text-success me-2"></i>Cek status kelulusan</li>
                            <li><i class="fas fa-download text-primary me-2"></i>Download bukti kelulusan</li>
                            <li><i class="fas fa-calendar text-warning me-2"></i>Ikuti jadwal daftar ulang</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FAQ -->
    <section class="py-5 bg-light">
        <div class="container">
            <h3 class="fw-bold text-center mb-4">
                <i class="fas fa-question-circle me-2"></i>
                Pertanyaan Umum (FAQ)
            </h3>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class="fas fa-map-marker-alt me-2"></i>Bagaimana cara mendapatkan koordinat rumah?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Ada 2 cara:</p>
                                    <ol>
                                        <li><strong>Otomatis (Recommended):</strong> Saat mengisi biodata, klik tombol <span class="badge bg-primary">📍 Gunakan Lokasi Saya</span>. Browser akan meminta izin lokasi, dan koordinat akan terisi otomatis.</li>
                                        <li><strong>Manual:</strong> Buka Google Maps, cari lokasi rumah Anda, klik kanan lalu pilih "What's here?". Koordinat akan muncul dalam format latitude, longitude. Contoh: -0.947092, 100.417279</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="fas fa-route me-2"></i>Apa saja jalur pendaftaran yang tersedia?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ada 4 jalur:
                                    <ul>
                                        <li><strong>Zonasi (<?php echo KUOTA_ZONASI_PERSEN; ?>%)</strong> - Berdasarkan jarak rumah ke sekolah</li>
                                        <li><strong>Prestasi (<?php echo KUOTA_PRESTASI_PERSEN; ?>%)</strong> - Berdasarkan nilai rapor semester 1-5</li>
                                        <li><strong>Afirmasi (<?php echo KUOTA_AFIRMASI_PERSEN; ?>%)</strong> - Untuk keluarga tidak mampu / berkebutuhan khusus</li>
                                        <li><strong>Mutasi (<?php echo KUOTA_MUTASI_PERSEN; ?>%)</strong> - Siswa yang mengikuti pindah tugas orang tua</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="fas fa-file-upload me-2"></i>Berapa ukuran maksimal file yang bisa diupload?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Maksimal <strong><?php echo get_max_upload_size_readable(); ?></strong> per file. Format yang diterima: <?php echo implode(', ', array_map('strtoupper', ALLOWED_EXTENSIONS)); ?>.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    <i class="fas fa-layer-group me-2"></i>Apakah saya bisa mendaftar di beberapa jalur?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <strong>Tidak.</strong> Pada sistem pendaftaran serentak, Anda hanya bisa memilih <strong>1 jalur</strong> untuk seluruh periode pendaftaran. Namun, dalam 1 jalur tersebut Anda bisa memilih:
                                    <ul>
                                        <li><strong>2 sekolah</strong>: Pilihan Utama dan Pilihan Cadangan</li>
                                        <li>Jika tidak diterima di Pilihan 1, sistem otomatis memproses Pilihan 2</li>
                                    </ul>
                                    <div class="alert alert-info mt-2 mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Pilih jalur yang paling sesuai dengan kriteria Anda: Zonasi, Prestasi, Afirmasi, atau Mutasi.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    <i class="fas fa-times-circle me-2"></i>Apa yang harus dilakukan jika dokumen ditolak?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Jika dokumen ditolak:
                                    <ol>
                                        <li>Login ke akun Anda</li>
                                        <li>Buka halaman <strong>Notifikasi</strong> untuk melihat alasan penolakan</li>
                                        <li>Pergi ke halaman <strong>Upload Dokumen</strong></li>
                                        <li>Upload ulang dokumen yang benar sesuai catatan admin</li>
                                        <li>Tunggu verifikasi ulang dari admin</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    <i class="fas fa-id-card me-2"></i>Mengapa NIK wajib diisi?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    NIK (Nomor Induk Kependudukan) wajib untuk:
                                    <ul>
                                        <li>Verifikasi identitas calon siswa</li>
                                        <li>Memastikan data yang diinput sesuai dengan Kartu Keluarga</li>
                                        <li>Integrasi dengan data kependudukan</li>
                                    </ul>
                                    <strong>Pastikan NIK yang dimasukkan valid (16 digit) dan sesuai dengan KK.</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                    <i class="fas fa-phone-alt me-2"></i>Bagaimana jika ada kendala?
                                </button>
                            </h2>
                            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Hubungi panitia PPDB:
                                    <ul class="list-unstyled mt-2">
                                        <li><i class="fas fa-phone me-2 text-primary"></i><?php echo KONTAK_TELEPON; ?></li>
                                        <li><i class="fas fa-envelope me-2 text-primary"></i><?php echo KONTAK_EMAIL; ?></li>
                                        <li><i class="fas fa-map-marker-alt me-2 text-primary"></i><?php echo KONTAK_ALAMAT; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                    <i class="fas fa-map-marked-alt me-2"></i>Bagaimana cara menggunakan Peta Zonasi?
                                </button>
                            </h2>
                            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Sistem PPDB dilengkapi peta interaktif untuk memudahkan melihat lokasi sekolah:</p>
                                    <ul>
                                        <li><strong>Marker Sekolah:</strong> Menunjukkan lokasi setiap sekolah pada peta.</li>
                                        <li><strong>Polygon Kecamatan:</strong> Menampilkan batas-batas kecamatan di Kota Padang.</li>
                                        <li><strong>Cek Jarak:</strong> Saat memilih sekolah, sistem otomatis menghitung jarak lurus dari rumah ke sekolah menggunakan koordinat GPS.</li>
                                        <li><strong>Seleksi Zonasi:</strong> Pada jalur zonasi, jarak rumah ke sekolah menjadi faktor utama penilaian (semakin dekat semakin diprioritaskan).</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA -->
    <section class="py-5 text-center">
        <div class="container">
            <h3 class="fw-bold mb-4">Siap untuk mendaftar?</h3>
            <a href="siswa/register.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-user-plus me-2"></i>
                Daftar Sekarang
            </a>
            <a href="index.php" class="btn btn-outline-secondary btn-lg px-4 ms-2">
                <i class="fas fa-arrow-left me-2"></i>
                Kembali
            </a>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

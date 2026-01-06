<?php
/**
 * File: includes/navbar.php
 * Deskripsi: Modern Navbar untuk siswa dengan mobile bottom navigation
 */

// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['id_user']) && $_SESSION['role'] == 'siswa';
$nama_siswa = $_SESSION['nama_lengkap'] ?? 'Siswa';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark siswa-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $is_logged_in ? 'index.php' : '../index.php'; ?>">
            <img src="/ppdb-smp/assets/images/logo-kota-padang.png" alt="Logo Kota Padang" class="brand-logo me-2">
            <span class="brand-text">PPDB SMP</span>
        </a>
        
        <!-- Desktop Menu -->
        <div class="d-none d-lg-flex align-items-center ms-auto">
            <?php if ($is_logged_in): ?>
            <ul class="navbar-nav desktop-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'biodata.php' ? 'active' : ''; ?>" href="biodata.php">
                        <i class="fas fa-user-edit"></i>
                        <span>Biodata</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'pilih_sekolah.php' ? 'active' : ''; ?>" href="pilih_sekolah.php">
                        <i class="fas fa-school"></i>
                        <span>Pendaftaran</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'hasil_seleksi.php' ? 'active' : ''; ?>" href="hasil_seleksi.php">
                        <i class="fas fa-trophy"></i>
                        <span>Hasil</span>
                    </a>
                </li>
            </ul>
            
            <!-- User Dropdown -->
            <div class="dropdown ms-3">
                <a class="user-dropdown d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($nama_siswa, 0, 1)); ?>
                    </div>
                    <div class="user-info d-none d-xl-block">
                        <span class="user-name"><?php echo $nama_siswa; ?></span>
                        <span class="user-role">Siswa</span>
                    </div>
                    <i class="fas fa-chevron-down ms-2"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                    <li class="dropdown-header">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar-lg me-3"><?php echo strtoupper(substr($nama_siswa, 0, 1)); ?></div>
                            <div>
                                <div class="fw-bold" style="color: #1f2937;"><?php echo $nama_siswa; ?></div>
                                <small style="color: #6b7280;">Siswa PPDB</small>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="profil.php">
                            <i class="fas fa-user-circle" style="color: #3b82f6;"></i>
                            <span style="color: #1f2937;">Profil Saya</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="notifikasi.php">
                            <i class="fas fa-bell" style="color: #f59e0b;"></i>
                            <span style="color: #1f2937;">Notifikasi</span>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item logout-item" href="logout.php" onclick="return confirm('Yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt" style="color: #ef4444;"></i>
                            <span style="color: #ef4444;">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
            <?php else: ?>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                </li>
                <li class="nav-item ms-2">
                    <a class="btn btn-light btn-register" href="register.php">
                        <i class="fas fa-user-plus me-1"></i> Daftar Sekarang
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
        
        <!-- Mobile: User Avatar Only -->
        <?php if ($is_logged_in): ?>
        <div class="d-lg-none d-flex align-items-center">
            <div class="dropdown">
                <a class="user-dropdown-mobile" href="#" data-bs-toggle="dropdown">
                    <div class="user-avatar-sm">
                        <?php echo strtoupper(substr($nama_siswa, 0, 1)); ?>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                    <li class="dropdown-header">
                        <div class="text-center py-2">
                            <div class="user-avatar-lg mx-auto mb-2"><?php echo strtoupper(substr($nama_siswa, 0, 1)); ?></div>
                            <div class="fw-bold" style="color: #1f2937;"><?php echo $nama_siswa; ?></div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="profil.php">
                            <i class="fas fa-user-circle" style="color: #3b82f6;"></i>
                            <span style="color: #1f2937;">Profil</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item logout-item" href="logout.php" onclick="return confirm('Logout?')">
                            <i class="fas fa-sign-out-alt" style="color: #ef4444;"></i>
                            <span style="color: #ef4444;">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>

<!-- Mobile Bottom Navigation -->
<?php if ($is_logged_in): ?>
<nav class="mobile-bottom-nav d-lg-none">
    <a href="index.php" class="bottom-nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>Beranda</span>
    </a>
    <a href="biodata.php" class="bottom-nav-item <?php echo $current_page == 'biodata.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-edit"></i>
        <span>Biodata</span>
    </a>
    <a href="pilih_sekolah.php" class="bottom-nav-item center-item <?php echo $current_page == 'pilih_sekolah.php' ? 'active' : ''; ?>">
        <div class="center-icon">
            <i class="fas fa-school"></i>
        </div>
        <span>Daftar</span>
    </a>
    <a href="dokumen.php" class="bottom-nav-item <?php echo $current_page == 'dokumen.php' ? 'active' : ''; ?>">
        <i class="fas fa-file-upload"></i>
        <span>Dokumen</span>
    </a>
    <button type="button" class="bottom-nav-item" onclick="toggleMoreMenu()">
        <i class="fas fa-ellipsis-h"></i>
        <span>Lainnya</span>
    </button>
</nav>

<!-- More Menu Overlay -->
<div class="more-menu-overlay d-lg-none" id="moreMenuOverlay" onclick="closeMoreMenu()"></div>

<!-- More Menu Panel -->
<div class="more-menu-panel d-lg-none" id="moreMenuPanel">
    <div class="more-menu-header">
        <span>Menu Lainnya</span>
        <button type="button" class="more-menu-close" onclick="closeMoreMenu()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="more-menu-grid">
        <a href="hasil_seleksi.php" class="more-menu-item">
            <div class="more-menu-icon" style="background: #fff1f2; color: #f43f5e;">
                <i class="fas fa-trophy"></i>
            </div>
            <span>Hasil Seleksi</span>
        </a>
        <a href="cetak_kartu.php" class="more-menu-item">
            <div class="more-menu-icon" style="background: #ecfeff; color: #06b6d4;">
                <i class="fas fa-id-card"></i>
            </div>
            <span>Cetak Kartu</span>
        </a>
        <a href="notifikasi.php" class="more-menu-item">
            <div class="more-menu-icon" style="background: #fefce8; color: #eab308;">
                <i class="fas fa-bell"></i>
            </div>
            <span>Notifikasi</span>
        </a>
        <a href="profil.php" class="more-menu-item">
            <div class="more-menu-icon" style="background: #eef2ff; color: #6366f1;">
                <i class="fas fa-user-circle"></i>
            </div>
            <span>Profil</span>
        </a>
        <a href="logout.php" class="more-menu-item" onclick="return confirm('Yakin ingin logout?')">
            <div class="more-menu-icon" style="background: #fef2f2; color: #ef4444;">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <span>Logout</span>
        </a>
    </div>
</div>

<script>
function toggleMoreMenu() {
    document.getElementById('moreMenuOverlay').classList.toggle('show');
    document.getElementById('moreMenuPanel').classList.toggle('show');
    document.body.style.overflow = document.getElementById('moreMenuPanel').classList.contains('show') ? 'hidden' : '';
}

function closeMoreMenu() {
    document.getElementById('moreMenuOverlay').classList.remove('show');
    document.getElementById('moreMenuPanel').classList.remove('show');
    document.body.style.overflow = '';
}
</script>
<?php endif; ?>

<style>
/* ==================== NAVBAR STYLES ==================== */
.siswa-navbar {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
    padding: 0.75rem 0;
    box-shadow: 0 4px 20px rgba(30, 58, 138, 0.3);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

/* Brand */
.brand-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
    backdrop-filter: blur(10px);
}

.brand-logo {
    height: 38px;
    width: auto;
    border-radius: 6px;
    background: rgba(255,255,255,0.95);
    padding: 3px;
}

.brand-text {
    font-weight: 700;
    font-size: 1.25rem;
    color: #fff;
    letter-spacing: 0.5px;
}

/* Desktop Navigation */
.desktop-nav {
    display: flex;
    gap: 0.5rem;
}

.desktop-nav .nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1rem;
    color: rgba(255,255,255,0.85);
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 0.9rem;
}

.desktop-nav .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.15);
    transform: translateY(-2px);
}

.desktop-nav .nav-link.active {
    color: #1e3a8a;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.desktop-nav .nav-link i {
    font-size: 1rem;
}

/* User Dropdown */
.user-dropdown {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: rgba(255,255,255,0.15);
    border-radius: 50px;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s;
}

.user-dropdown:hover {
    background: rgba(255,255,255,0.25);
    color: #fff;
}

.user-avatar {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    color: #fff;
    border: 2px solid rgba(255,255,255,0.5);
}

.user-avatar-sm {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    color: #fff;
}

.user-avatar-lg {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
    color: #fff;
}

.user-info {
    margin-left: 0.75rem;
    line-height: 1.2;
}

.user-name {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
}

.user-role {
    display: block;
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Modern Dropdown */
.modern-dropdown {
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    padding: 0.5rem;
    min-width: 220px;
    margin-top: 10px;
    background: #fff;
}

.modern-dropdown .dropdown-header {
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 0.5rem;
}

.modern-dropdown .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    transition: all 0.2s;
    color: #1f2937 !important;
}

.modern-dropdown .dropdown-item i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.modern-dropdown .dropdown-item:hover {
    background: #f1f5f9;
    transform: translateX(5px);
}

.modern-dropdown .logout-item:hover {
    background: #fef2f2;
}

.btn-register {
    font-weight: 600;
    padding: 0.6rem 1.25rem;
    border-radius: 50px;
    color: #1e3a8a !important;
}

.btn-register:hover {
    transform: scale(1.05);
}

/* ==================== MOBILE BOTTOM NAV ==================== */
.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    padding: 0.5rem 0.25rem;
    padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));
    box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
    z-index: 1050;
    border-top: 1px solid #e5e7eb;
}

.bottom-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.7rem;
    font-weight: 500;
    transition: all 0.3s;
    min-width: 60px;
}

.bottom-nav-item i {
    font-size: 1.25rem;
    transition: all 0.3s;
}

.bottom-nav-item.active {
    color: #3b82f6;
}

.bottom-nav-item.active i {
    transform: scale(1.1);
}

/* Center Item (Daftar) */
.bottom-nav-item.center-item {
    position: relative;
    margin-top: -20px;
}

.bottom-nav-item.center-item .center-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    border: 4px solid #fff;
}

.bottom-nav-item.center-item.active .center-icon {
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    transform: scale(1.05);
}

.bottom-nav-item.center-item span {
    color: #3b82f6;
    font-weight: 600;
}

/* Mobile User Dropdown */
.user-dropdown-mobile {
    padding: 0.25rem;
}

/* Add padding to body for bottom nav */
@media (max-width: 991px) {
    body {
        padding-bottom: 80px;
    }
}

/* Responsive */
@media (max-width: 575px) {
    .brand-text {
        font-size: 1.1rem;
    }
    
    .brand-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
}

/* ==================== MORE MENU STYLES ==================== */
.more-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1060;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.more-menu-overlay.show {
    opacity: 1;
    visibility: visible;
}

.more-menu-panel {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-radius: 24px 24px 0 0;
    z-index: 1070;
    transform: translateY(100%);
    transition: transform 0.3s ease;
    padding-bottom: env(safe-area-inset-bottom);
    box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
}

.more-menu-panel.show {
    transform: translateY(0);
}

.more-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.more-menu-header span {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1f2937;
}

.more-menu-close {
    width: 36px;
    height: 36px;
    border: none;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.more-menu-close:hover {
    background: #e5e7eb;
    color: #374151;
}

.more-menu-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    padding: 1.5rem;
}

.more-menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border-radius: 16px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s;
}

.more-menu-item:hover {
    background: #f3f4f6;
    transform: scale(1.02);
    color: #374151;
}

.more-menu-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.more-menu-item span {
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
}

/* Bottom nav button style */
.bottom-nav-item button,
button.bottom-nav-item {
    background: none;
    border: none;
    cursor: pointer;
}
</style>
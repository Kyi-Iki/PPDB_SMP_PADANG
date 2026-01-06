<?php
/**
 * File: includes/navbar_admin.php
 * Deskripsi: Navbar untuk admin
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nama_admin = $_SESSION['username'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-gradient-admin sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/ppdb-smp/admin/index.php">
            <i class="fas fa-shield-alt me-2"></i>
            ADMIN PPDB
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarAdminNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="/ppdb-smp/index.php" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i> Lihat Website
                    </a>
                </li>
                
                <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" 
                       role="button" data-bs-toggle="dropdown">
                        <div class="avatar-circle-admin me-2">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <span class="d-none d-lg-inline"><?php echo $nama_admin; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="dropdown-header" style="color: #6c757d !important;">
                            <small style="color: #6c757d !important;">Logged in as</small><br>
                            <strong style="color: #212529 !important;"><?php echo $nama_admin; ?></strong>
                            <br><small class="badge bg-<?php echo is_super_admin() ? 'primary' : 'info'; ?>"><?php echo is_super_admin() ? 'Super Admin' : 'Operator'; ?></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (is_super_admin()): ?>
                        <li>
                            <a class="dropdown-item" href="/ppdb-smp/admin/pengaturan.php">
                                <i class="fas fa-cog me-2"></i> Pengaturan
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item text-danger" href="/ppdb-smp/admin/logout.php" 
                               onclick="return confirm('Yakin ingin logout?')">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.bg-gradient-admin {
    /* Fallback + CSS variable */
    background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 50%, #2563eb 100%);
    background: var(--gradient-hero, linear-gradient(135deg, #1e3a5f 0%, #1e40af 50%, #2563eb 100%));
}

.avatar-circle-admin {
    width: 35px;
    height: 35px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    border: 2px solid rgba(255, 255, 255, 0.4);
}

.navbar-dark .navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.9);
    transition: all 0.3s;
}

.navbar-dark .navbar-nav .nav-link:hover {
    color: #fff;
}

/* Dropdown menu styling */
.navbar .dropdown-menu {
    z-index: 1050 !important;
    min-width: 200px;
    border: none;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    background: white !important;
}

/* Fix text color in dropdown */
.navbar .dropdown-menu .dropdown-header {
    color: #6c757d !important;
}

.navbar .dropdown-menu .dropdown-header strong {
    color: #212529 !important;
}

.navbar .dropdown-menu .dropdown-item {
    color: #212529 !important;
    padding: 0.6rem 1rem;
    transition: all 0.2s;
}

.navbar .dropdown-menu .dropdown-item:hover {
    background: #f8f9fa;
    color: #212529 !important;
}

.navbar .dropdown-menu .dropdown-item.text-danger {
    color: #dc3545 !important;
}

.navbar .dropdown-menu .dropdown-item.text-danger:hover {
    background: #fff5f5;
    color: #dc3545 !important;
}

.navbar .dropdown-menu .dropdown-item i {
    color: inherit !important;
}
</style>
<?php
/**
 * File: includes/sidebar_admin.php
 * Deskripsi: Sidebar menu untuk admin dengan role-based access
 */

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Base URL untuk admin - menggunakan path absolut
$admin_base = '/ppdb-smp/admin';

// Cek role untuk menu visibility
$is_super = is_super_admin();
$operator_sekolah = get_operator_sekolah();

// Menu items dengan kategori dan role restriction
// 'super_only' => true berarti hanya super_admin yang bisa akses
$menu_categories = [
    'Dashboard' => [
        [
            'icon' => 'fas fa-tachometer-alt',
            'label' => 'Dashboard',
            'url' => $admin_base . '/index.php',
            'page' => 'index.php',
            'dir' => 'admin'
        ]
    ],
    'Data Master' => [
        [
            'icon' => 'fas fa-school',
            'label' => 'Data Sekolah',
            'url' => $admin_base . '/sekolah/index.php',
            'page' => 'index.php',
            'dir' => 'sekolah'
        ],
        [
            'icon' => 'fas fa-percentage',
            'label' => 'Kuota Jalur',
            'url' => $admin_base . '/kuota/index.php',
            'page' => 'index.php',
            'dir' => 'kuota'
        ],
        [
            'icon' => 'fas fa-calendar-alt',
            'label' => 'Jadwal Jalur',
            'url' => $admin_base . '/jadwal_jalur.php',
            'page' => 'jadwal_jalur.php',
            'dir' => 'admin',
            'super_only' => true
        ]
    ],
    'Pendaftaran' => [
        [
            'icon' => 'fas fa-users',
            'label' => 'Daftar Pendaftar',
            'url' => $admin_base . '/pendaftaran/index.php',
            'page' => 'index.php',
            'dir' => 'pendaftaran'
        ],
        [
            'icon' => 'fas fa-check-circle',
            'label' => 'Verifikasi Pendaftaran',
            'url' => $admin_base . '/verifikasi/index.php',
            'page' => 'index.php',
            'dir' => 'verifikasi'
        ],
        [
            'icon' => 'fas fa-file-circle-check',
            'label' => 'Verifikasi Dokumen',
            'url' => $admin_base . '/dokumen_siswa.php',
            'page' => 'dokumen_siswa.php',
            'dir' => 'admin'
        ]
    ],
    'Seleksi' => [
        [
            'icon' => 'fas fa-cogs',
            'label' => 'Proses Seleksi',
            'url' => $admin_base . '/seleksi/proses.php',
            'page' => 'proses.php',
            'dir' => 'seleksi',
            'super_only' => true
        ],
        [
            'icon' => 'fas fa-list-ol',
            'label' => 'Hasil Seleksi',
            'url' => $admin_base . '/seleksi/hasil.php',
            'page' => 'hasil.php',
            'dir' => 'seleksi'
        ],
        [
            'icon' => 'fas fa-trophy',
            'label' => 'Ranking Per Jalur',
            'url' => $admin_base . '/seleksi/ranking.php',
            'page' => 'ranking.php',
            'dir' => 'seleksi'
        ],
        [
            'icon' => 'fas fa-bullhorn',
            'label' => 'Publikasi',
            'url' => $admin_base . '/seleksi/publikasi.php',
            'page' => 'publikasi.php',
            'dir' => 'seleksi',
            'super_only' => true
        ],
        [
            'icon' => 'fas fa-undo',
            'label' => 'Reset Seleksi',
            'url' => $admin_base . '/seleksi/reset.php',
            'page' => 'reset.php',
            'dir' => 'seleksi',
            'super_only' => true
        ]
    ],
    'Laporan' => [
        [
            'icon' => 'fas fa-chart-bar',
            'label' => 'Laporan',
            'url' => $admin_base . '/laporan/index.php',
            'page' => 'index.php',
            'dir' => 'laporan'
        ]
    ],
    'Pengaturan' => [
        [
            'icon' => 'fas fa-users-cog',
            'label' => 'Kelola Operator',
            'url' => $admin_base . '/users/index.php',
            'page' => 'index.php',
            'dir' => 'users',
            'super_only' => true
        ],
        [
            'icon' => 'fas fa-history',
            'label' => 'Log Aktivitas',
            'url' => $admin_base . '/log/index.php',
            'page' => 'index.php',
            'dir' => 'log',
            'super_only' => true
        ],
        [
            'icon' => 'fas fa-database',
            'label' => 'Backup & Restore',
            'url' => $admin_base . '/backup/index.php',
            'page' => 'index.php',
            'dir' => 'backup',
            'super_only' => true
        ],
        [
            'icon' => 'fas fa-cog',
            'label' => 'Pengaturan',
            'url' => $admin_base . '/pengaturan.php',
            'page' => 'pengaturan.php',
            'dir' => 'admin',
            'super_only' => true
        ]
    ]
];
?>

<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="text-center py-3">
            <div class="admin-avatar mb-2">
                <i class="fas <?php echo $is_super ? 'fa-user-shield' : 'fa-user-tie'; ?>"></i>
            </div>
            <h6 class="mb-0 fw-bold">
                <?php echo $is_super ? 'Super Admin' : 'Operator'; ?>
            </h6>
            <?php if (!$is_super && $operator_sekolah): 
                // Ambil nama sekolah operator
                $sql_sch = "SELECT nama_sekolah FROM sekolah WHERE id_sekolah = ?";
                $stmt_sch = mysqli_prepare($conn, $sql_sch);
                mysqli_stmt_bind_param($stmt_sch, "i", $operator_sekolah);
                mysqli_stmt_execute($stmt_sch);
                $sch_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sch));
            ?>
            <small class="text-white-50 d-block"><?php echo $sch_data['nama_sekolah'] ?? 'Sekolah'; ?></small>
            <?php else: ?>
            <small style="color: rgba(255,255,255,0.85) !important;">PPDB SMP <?php echo TAHUN_AJARAN; ?></small>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <?php foreach ($menu_categories as $category => $items): 
            // Filter items berdasarkan role
            $visible_items = array_filter($items, function($item) use ($is_super) {
                // Jika super_only = true dan user bukan super_admin, sembunyikan
                if (isset($item['super_only']) && $item['super_only'] && !$is_super) {
                    return false;
                }
                return true;
            });
            
            // Skip kategori jika tidak ada item yang visible
            if (empty($visible_items)) continue;
        ?>
        <div class="menu-category">
            <div class="category-label"><?php echo $category; ?></div>
            <?php foreach ($visible_items as $item): 
                $is_active = ($current_page == $item['page'] && $current_dir == $item['dir']);
            ?>
            <a href="<?php echo $item['url']; ?>" 
               class="menu-item <?php echo $is_active ? 'active' : ''; ?>">
                <i class="<?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['label']; ?></span>
                <?php if ($is_active): ?>
                <i class="fas fa-chevron-right ms-auto"></i>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="sidebar-footer">
        <div class="p-3">
            <div class="quick-stats card bg-light border-0">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2">
                        <i class="fas fa-chart-line me-2"></i>Quick Stats
                    </h6>
                    <?php
                    // $conn sudah tersedia dari parent file
                    $tahun = TAHUN_AJARAN;
                    
                    // Total pendaftar
                    $sql_total = "SELECT COUNT(*) as total FROM pendaftaran WHERE tahun_ajaran = ?";
                    $stmt = mysqli_prepare($conn, $sql_total);
                    mysqli_stmt_bind_param($stmt, "s", $tahun);
                    mysqli_stmt_execute($stmt);
                    $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
                    
                    // Terverifikasi
                    $sql_verified = "SELECT COUNT(*) as total FROM pendaftaran 
                                    WHERE tahun_ajaran = ? AND status_verifikasi = 'terverifikasi'";
                    $stmt = mysqli_prepare($conn, $sql_verified);
                    mysqli_stmt_bind_param($stmt, "s", $tahun);
                    mysqli_stmt_execute($stmt);
                    $verified = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
                    ?>
                    <div class="d-flex justify-content-between mb-1">
                        <small>Total Pendaftar</small>
                        <strong class="text-primary"><?php echo $total; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small>Terverifikasi</small>
                        <strong class="text-success"><?php echo $verified; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-sidebar {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.08);
    overflow: hidden;
    position: sticky;
    top: 80px;
}

.sidebar-header {
    /* Fallback + CSS variable */
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    background: var(--gradient-primary, linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%));
    color: white;
}

.admin-avatar {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 1.5rem;
}

.sidebar-menu {
    max-height: calc(100vh - 400px);
    overflow-y: auto;
}

.menu-category {
    margin-bottom: 1rem;
}

.category-label {
    padding: 0.75rem 1.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #6c757d;
    letter-spacing: 0.5px;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.25rem;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.menu-item:hover {
    background: #f8f9fa;
    color: #1e3c72;
    padding-left: 1.5rem;
}

.menu-item.active {
    background: linear-gradient(90deg, rgba(30,60,114,0.1) 0%, rgba(42,82,152,0.05) 100%);
    color: #1e3c72;
    font-weight: 600;
    border-left-color: #1e3c72;
}

.menu-item i:first-child {
    width: 25px;
    margin-right: 10px;
    text-align: center;
}

.sidebar-footer {
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.quick-stats {
    border-radius: 10px;
}

/* Scrollbar */
.sidebar-menu::-webkit-scrollbar {
    width: 5px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 5px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: #555;
}

@media (max-width: 767px) {
    .admin-sidebar {
        position: static;
        margin-bottom: 1.5rem;
    }
}
</style>

<script>
// Simpan dan restore scroll position sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebarMenu = document.querySelector('.sidebar-menu');
    
    if (sidebarMenu) {
        // Restore scroll position dari localStorage
        const savedScrollPos = localStorage.getItem('sidebarScrollPos');
        if (savedScrollPos) {
            sidebarMenu.scrollTop = parseInt(savedScrollPos);
        }
        
        // Scroll ke menu aktif jika ada
        const activeItem = sidebarMenu.querySelector('.menu-item.active');
        if (activeItem && !savedScrollPos) {
            activeItem.scrollIntoView({ block: 'center', behavior: 'instant' });
        }
        
        // Simpan scroll position sebelum pindah halaman
        sidebarMenu.addEventListener('scroll', function() {
            localStorage.setItem('sidebarScrollPos', sidebarMenu.scrollTop);
        });
        
        // Simpan juga saat klik menu
        const menuItems = sidebarMenu.querySelectorAll('.menu-item');
        menuItems.forEach(function(item) {
            item.addEventListener('click', function() {
                localStorage.setItem('sidebarScrollPos', sidebarMenu.scrollTop);
            });
        });
    }
});
</script>
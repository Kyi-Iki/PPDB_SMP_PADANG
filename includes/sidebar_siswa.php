<?php
/**
 * File: includes/sidebar_siswa.php
 * Deskripsi: Modern Sidebar menu untuk siswa dengan colorful icons
 */

$current_page = basename($_SERVER['PHP_SELF']);

// Check pendaftaran progress
$has_biodata = !empty($siswa['nik'] ?? null);
$has_dokumen = false;
$has_pendaftaran = false;

if (isset($siswa) && isset($conn)) {
    // Check dokumen
    $sql_dok = "SELECT COUNT(*) as total FROM dokumen_siswa WHERE id_siswa = ?";
    $stmt_dok = mysqli_prepare($conn, $sql_dok);
    mysqli_stmt_bind_param($stmt_dok, "i", $siswa['id_siswa']);
    mysqli_stmt_execute($stmt_dok);
    $res_dok = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_dok));
    $has_dokumen = ($res_dok['total'] ?? 0) > 0;
    
    // Check pendaftaran
    $sql_pend = "SELECT COUNT(*) as total FROM pendaftaran WHERE id_siswa = ?";
    $stmt_pend = mysqli_prepare($conn, $sql_pend);
    mysqli_stmt_bind_param($stmt_pend, "i", $siswa['id_siswa']);
    mysqli_stmt_execute($stmt_pend);
    $res_pend = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pend));
    $has_pendaftaran = ($res_pend['total'] ?? 0) > 0;
}

// Menu items with colors
$menu_items = [
    [
        'icon' => 'fas fa-home',
        'label' => 'Dashboard',
        'url' => 'index.php',
        'page' => 'index.php',
        'color' => '#3b82f6',
        'bg' => '#eff6ff'
    ],
    [
        'icon' => 'fas fa-user-edit',
        'label' => 'Biodata',
        'url' => 'biodata.php',
        'page' => 'biodata.php',
        'color' => '#8b5cf6',
        'bg' => '#f5f3ff',
        'badge' => $has_biodata ? '✓' : '!'
    ],
    [
        'icon' => 'fas fa-file-upload',
        'label' => 'Upload Dokumen',
        'url' => 'dokumen.php',
        'page' => 'dokumen.php',
        'color' => '#f59e0b',
        'bg' => '#fffbeb',
        'badge' => $has_dokumen ? '✓' : '!'
    ],
    [
        'icon' => 'fas fa-school',
        'label' => 'Pilih Sekolah',
        'url' => 'pilih_sekolah.php',
        'page' => 'pilih_sekolah.php',
        'color' => '#10b981',
        'bg' => '#ecfdf5',
        'badge' => $has_pendaftaran ? '✓' : null
    ],
    [
        'icon' => 'fas fa-id-card',
        'label' => 'Cetak Kartu',
        'url' => 'cetak_kartu.php',
        'page' => 'cetak_kartu.php',
        'color' => '#06b6d4',
        'bg' => '#ecfeff'
    ],
    [
        'icon' => 'fas fa-trophy',
        'label' => 'Hasil Seleksi',
        'url' => 'hasil_seleksi.php',
        'page' => 'hasil_seleksi.php',
        'color' => '#f43f5e',
        'bg' => '#fff1f2'
    ],
    [
        'icon' => 'fas fa-bell',
        'label' => 'Notifikasi',
        'url' => 'notifikasi.php',
        'page' => 'notifikasi.php',
        'color' => '#eab308',
        'bg' => '#fefce8'
    ],
    [
        'icon' => 'fas fa-user-circle',
        'label' => 'Profil',
        'url' => 'profil.php',
        'page' => 'profil.php',
        'color' => '#6366f1',
        'bg' => '#eef2ff'
    ]
];

// Progress calculation
$progress_steps = [
    ['label' => 'Biodata', 'done' => $has_biodata],
    ['label' => 'Dokumen', 'done' => $has_dokumen],
    ['label' => 'Daftar', 'done' => $has_pendaftaran]
];
$completed_steps = count(array_filter($progress_steps, fn($s) => $s['done']));
$progress_percent = ($completed_steps / count($progress_steps)) * 100;
?>

<!-- Desktop Sidebar (hidden on mobile) -->
<div class="d-none d-md-block">
    <div class="modern-sidebar">
        <!-- Progress Card -->
        <div class="progress-card mb-3">
            <div class="progress-header">
                <span class="progress-title">Progress Pendaftaran</span>
                <span class="progress-percent"><?php echo round($progress_percent); ?>%</span>
            </div>
            <div class="progress-bar-wrapper">
                <div class="progress-bar-fill" style="width: <?php echo $progress_percent; ?>%"></div>
            </div>
            <div class="progress-steps">
                <?php foreach ($progress_steps as $step): ?>
                <div class="progress-step <?php echo $step['done'] ? 'done' : ''; ?>">
                    <div class="step-dot">
                        <?php if ($step['done']): ?>
                        <i class="fas fa-check"></i>
                        <?php endif; ?>
                    </div>
                    <span><?php echo $step['label']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Menu Card -->
        <div class="menu-card">
            <div class="menu-header">
                <i class="fas fa-compass me-2"></i>
                Menu Navigasi
            </div>
            <div class="menu-list">
                <?php foreach ($menu_items as $item): ?>
                <a href="<?php echo $item['url']; ?>" 
                   class="menu-item <?php echo $current_page == $item['page'] ? 'active' : ''; ?>">
                    <div class="menu-icon" style="background: <?php echo $item['bg']; ?>; color: <?php echo $item['color']; ?>;">
                        <i class="<?php echo $item['icon']; ?>"></i>
                    </div>
                    <span class="menu-label"><?php echo $item['label']; ?></span>
                    <?php if (isset($item['badge'])): ?>
                    <span class="menu-badge <?php echo $item['badge'] == '✓' ? 'success' : 'warning'; ?>">
                        <?php echo $item['badge']; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($current_page == $item['page']): ?>
                    <i class="fas fa-chevron-right menu-arrow"></i>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Help Card -->
        <div class="help-card mt-3">
            <div class="help-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h6>Butuh Bantuan?</h6>
            <p>Hubungi panitia PPDB</p>
            <a href="tel:0751123456" class="help-btn">
                <i class="fas fa-phone-alt me-1"></i> Hubungi
            </a>
        </div>
    </div>
</div>

<style>
/* ==================== MODERN SIDEBAR STYLES ==================== */
.modern-sidebar {
    position: sticky;
    top: 80px;
    z-index: 100;
}

/* Progress Card */
.progress-card {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    border-radius: 16px;
    padding: 1.25rem;
    color: #fff;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-title {
    font-weight: 600;
    font-size: 0.9rem;
}

.progress-percent {
    font-weight: 700;
    font-size: 1.1rem;
}

.progress-bar-wrapper {
    height: 8px;
    background: rgba(255,255,255,0.3);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.progress-bar-fill {
    height: 100%;
    background: #fff;
    border-radius: 10px;
    transition: width 0.5s ease;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 0.75rem;
    opacity: 0.7;
}

.progress-step.done {
    opacity: 1;
}

.step-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.25rem;
    font-size: 0.65rem;
}

.progress-step.done .step-dot {
    background: #10b981;
}

/* Menu Card */
.menu-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.menu-header {
    background: #f8fafc;
    padding: 1rem 1.25rem;
    font-weight: 600;
    color: #64748b;
    font-size: 0.85rem;
    border-bottom: 1px solid #e2e8f0;
}

.menu-list {
    padding: 0.5rem;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    color: #334155;
    text-decoration: none;
    transition: all 0.3s ease;
    margin-bottom: 0.25rem;
}

.menu-item:hover {
    background: #f1f5f9;
    transform: translateX(5px);
    color: #1e3a8a;
}

.menu-item.active {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.menu-item.active .menu-icon {
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}

.menu-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1rem;
    transition: all 0.3s;
}

.menu-label {
    flex: 1;
    font-weight: 500;
    font-size: 0.9rem;
}

.menu-badge {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
}

.menu-badge.success {
    background: #dcfce7;
    color: #16a34a;
}

.menu-badge.warning {
    background: #fef3c7;
    color: #d97706;
}

.menu-arrow {
    font-size: 0.75rem;
    opacity: 0.7;
}

/* Help Card */
.help-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    border: 1px solid #bae6fd;
}

.help-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: #fff;
    font-size: 1.25rem;
}

.help-card h6 {
    font-weight: 700;
    color: #0c4a6e;
    margin-bottom: 0.25rem;
}

.help-card p {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 1rem;
}

.help-btn {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: #fff;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

.help-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(14, 165, 233, 0.4);
    color: #fff;
}

/* Mobile - hide sidebar content since we have bottom nav */
@media (max-width: 767px) {
    .modern-sidebar {
        display: none;
    }
}
</style>
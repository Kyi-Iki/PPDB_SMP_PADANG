<?php
/**
 * File: admin/sekolah/index.php
 * Deskripsi: Manajemen data sekolah (List & CRUD)
 * 
 * Access:
 * - Super Admin: Full access (view all, add, edit, delete, kuota)
 * - Operator: View & edit only their own school (no add, delete, kuota)
 */

session_start();
require_once '../../config/database.php';

check_admin(); // Allow both super_admin and operator

$tahun_ajaran = TAHUN_AJARAN;
$error = '';
$success = '';

// Get operator's school filter
$id_sekolah_filter = get_sekolah_filter();

// Proses hapus (Only super_admin can delete)
if (isset($_GET['delete']) && is_super_admin()) {
    $id_sekolah = (int)$_GET['delete'];
    
    $sql_delete = "DELETE FROM sekolah WHERE id_sekolah = ?";
    $stmt = mysqli_prepare($conn, $sql_delete);
    mysqli_stmt_bind_param($stmt, "i", $id_sekolah);
    
    if (mysqli_stmt_execute($stmt)) {
        log_aktivitas($_SESSION['id_user'], 'Hapus Sekolah', "Hapus sekolah ID: $id_sekolah");
        $success = "Sekolah berhasil dihapus!";
    } else {
        $error = "Gagal menghapus sekolah!";
    }
}

// Ambil daftar sekolah (filtered for operator)
if ($id_sekolah_filter) {
    // Operator: only their school
    $sql_list = "SELECT s.*, 
                 (SELECT COUNT(*) FROM pendaftaran WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as total_pendaftar,
                 (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as kuota_dynamic
                 FROM sekolah s
                 WHERE s.id_sekolah = $id_sekolah_filter";
} else {
    // Super Admin: all schools
    $sql_list = "SELECT s.*, 
                 (SELECT COUNT(*) FROM pendaftaran WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as total_pendaftar,
                 (SELECT SUM(jumlah_kuota) FROM kuota_jalur WHERE id_sekolah = s.id_sekolah AND tahun_ajaran = '$tahun_ajaran') as kuota_dynamic
                 FROM sekolah s
                 ORDER BY s.nama_sekolah";
}
$sekolah_list = mysqli_query($conn, $sql_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Sekolah - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #f5f7fa;
        }
        
        .school-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }
        
        .school-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        
        .school-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar_admin.php'; ?>
    
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../../includes/sidebar_admin.php'; ?>
            </div>
            
            <div class="col-md-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-school me-2"></i>
                            <?php echo is_operator() ? 'Data Sekolah Saya' : 'Data Sekolah SMP'; ?>
                        </h4>
                        <?php if (is_super_admin()): ?>
                        <a href="tambah.php" class="btn btn-light">
                            <i class="fas fa-plus me-2"></i>Tambah Sekolah
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <input type="text" id="searchInput" class="form-control" 
                                   placeholder="🔍 Cari sekolah...">
                        </div>
                        
                        <div id="schoolList">
                            <?php while ($sekolah = mysqli_fetch_assoc($sekolah_list)): ?>
                            <div class="school-card">
                                <div class="row align-items-center">
                                    <div class="col-md-1">
                                        <?php if (!empty($sekolah['foto']) && file_exists('../../uploads/sekolah/' . $sekolah['foto'])): ?>
                                            <img src="../../uploads/sekolah/<?php echo $sekolah['foto']; ?>" 
                                                 alt="<?php echo $sekolah['nama_sekolah']; ?>" 
                                                 class="rounded-3 shadow-sm"
                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="school-icon">
                                                <i class="fas fa-school"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-7">
                                        <h5 class="fw-bold mb-1"><?php echo $sekolah['nama_sekolah']; ?></h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-id-card me-2"></i>
                                            NPSN: <?php echo $sekolah['npsn']; ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            <?php echo $sekolah['alamat_sekolah']; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="stat-badge bg-primary text-white mb-2">
                                            <i class="fas fa-chair me-1"></i>
                                            Kuota: <?php echo $sekolah['kuota_dynamic'] ?? 0; ?>
                                        </div>
                                        <div class="stat-badge bg-success text-white">
                                            <i class="fas fa-users me-1"></i>
                                            Pendaftar: <?php echo $sekolah['total_pendaftar']; ?>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge bg-<?php echo $sekolah['status'] == 'aktif' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($sekolah['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <a href="edit.php?id=<?php echo $sekolah['id_sekolah']; ?>" 
                                           class="btn btn-sm btn-outline-primary mb-1">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if (is_super_admin()): ?>
                                        <a href="../kuota/index.php?id_sekolah=<?php echo $sekolah['id_sekolah']; ?>" 
                                           class="btn btn-sm btn-outline-info mb-1">
                                            <i class="fas fa-percentage"></i> Kuota
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $sekolah['id_sekolah']; ?>, '<?php echo $sekolah['nama_sekolah']; ?>')" 
                                                class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const cards = document.querySelectorAll('.school-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
        
        // Confirm delete
        function confirmDelete(id, nama) {
            if (confirm(`Yakin ingin menghapus "${nama}"?\n\nData pendaftar ke sekolah ini akan ikut terhapus!`)) {
                window.location.href = `?delete=${id}`;
            }
        }
    </script>
</body>
</html>
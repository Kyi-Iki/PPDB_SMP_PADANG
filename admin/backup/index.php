<?php
/**
 * File: admin/backup/index.php
 * Deskripsi: Backup dan Restore Database
 */

session_start();
require_once '../../config/database.php';
check_admin();
check_super_admin(); // Only super_admin can access backup

// check_super_admin() already redirects non-super_admin users

$error = '';
$success = '';

// Folder backup
$backup_folder = __DIR__ . '/files';
if (!is_dir($backup_folder)) {
    mkdir($backup_folder, 0755, true);
}

// Proses backup
if (isset($_POST['backup'])) {
    $database = 'ppdb_smp';
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_folder . '/' . $filename;
    
    // Gunakan mysqldump
    $mysqldump_path = 'C:/xampp/mysql/bin/mysqldump.exe';
    
    // Cek apakah mysqldump ada
    if (file_exists($mysqldump_path)) {
        $command = "\"$mysqldump_path\" --user=root --password= --host=localhost $database > \"$filepath\" 2>&1";
        exec($command, $output, $return_var);
        
        if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            $success = "Backup berhasil dibuat: $filename";
            log_aktivitas($_SESSION['id_user'], 'Backup Database', "Membuat backup: $filename");
        } else {
            // Fallback: manual backup dengan PHP
            $tables = [];
            $result = mysqli_query($conn, "SHOW TABLES FROM $database");
            while ($row = mysqli_fetch_row($result)) {
                $tables[] = $row[0];
            }
            
            $sql_dump = "-- PPDB SMP Database Backup\n";
            $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                // Create table
                $create = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
                $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql_dump .= $create[1] . ";\n\n";
                
                // Insert data
                $rows = mysqli_query($conn, "SELECT * FROM `$table`");
                while ($row = mysqli_fetch_assoc($rows)) {
                    $values = array_map(function($v) use ($conn) {
                        return $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'";
                    }, array_values($row));
                    $sql_dump .= "INSERT INTO `$table` VALUES(" . implode(',', $values) . ");\n";
                }
                $sql_dump .= "\n";
            }
            
            $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            if (file_put_contents($filepath, $sql_dump)) {
                $success = "Backup berhasil dibuat: $filename (PHP fallback)";
                log_aktivitas($_SESSION['id_user'], 'Backup Database', "Membuat backup: $filename");
            } else {
                $error = "Gagal membuat backup!";
            }
        }
    } else {
        $error = "mysqldump tidak ditemukan. Pastikan XAMPP terinstall dengan benar.";
    }
}

// Proses download
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filepath = $backup_folder . '/' . $file;
    
    if (file_exists($filepath) && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }
}

// Proses hapus
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $filepath = $backup_folder . '/' . $file;
    
    if (file_exists($filepath) && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
        if (unlink($filepath)) {
            $success = "File $file berhasil dihapus!";
            log_aktivitas($_SESSION['id_user'], 'Hapus Backup', "Menghapus backup: $file");
        } else {
            $error = "Gagal menghapus file!";
        }
    }
}

// Proses restore
if (isset($_POST['restore']) && isset($_FILES['restore_file'])) {
    $uploaded = $_FILES['restore_file'];
    
    if ($uploaded['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($uploaded['name'], PATHINFO_EXTENSION));
        
        if ($ext != 'sql') {
            $error = "File harus berformat .sql!";
        } else {
            $sql_content = file_get_contents($uploaded['tmp_name']);
            
            // Disable foreign key checks
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
            
            // Split by semicolon and execute
            $queries = array_filter(array_map('trim', explode(';', $sql_content)));
            $success_count = 0;
            $error_count = 0;
            
            foreach ($queries as $query) {
                if (!empty($query) && $query != 'SET FOREIGN_KEY_CHECKS=0' && $query != 'SET FOREIGN_KEY_CHECKS=1') {
                    if (mysqli_query($conn, $query)) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
            
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
            
            if ($error_count == 0) {
                $success = "Restore berhasil! $success_count query dieksekusi.";
                log_aktivitas($_SESSION['id_user'], 'Restore Database', "Restore dari file: " . $uploaded['name']);
            } else {
                $error = "Restore selesai dengan $error_count error dari " . ($success_count + $error_count) . " query.";
            }
        }
    } else {
        $error = "Error upload file!";
    }
}

// List backup files
$backup_files = [];
if (is_dir($backup_folder)) {
    $files = scandir($backup_folder);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $filepath = $backup_folder . '/' . $file;
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => filemtime($filepath)
            ];
        }
    }
    // Sort by date desc
    usort($backup_files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Database info
$db_size = 0;
$result_tables = mysqli_query($conn, "SHOW TABLE STATUS FROM ppdb_smp");
$table_count = mysqli_num_rows($result_tables);
while ($table = mysqli_fetch_assoc($result_tables)) {
    $db_size += $table['Data_length'] + $table['Index_length'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - Admin PPDB</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f5f7fa; }
        
        .action-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            height: 100%;
            transition: all 0.3s;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .action-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .backup-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
            transition: all 0.2s;
        }
        .backup-item:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">
                            <i class="fas fa-database text-primary me-2"></i>Backup & Restore
                        </h4>
                        <p class="text-muted mb-0">Kelola backup database sistem</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Database: <strong>ppdb_smp</strong></small>
                        <small class="text-muted"><?php echo $table_count; ?> tabel | <?php echo number_format($db_size / 1024 / 1024, 2); ?> MB</small>
                    </div>
                </div>
                
                <!-- Action Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="action-card">
                            <div class="action-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <i class="fas fa-download"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Backup Database</h5>
                            <p class="text-muted mb-3">Buat salinan database saat ini ke file SQL</p>
                            <form method="POST">
                                <button type="submit" name="backup" class="btn btn-primary btn-lg">
                                    <i class="fas fa-cloud-download-alt me-2"></i>Buat Backup
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="action-card">
                            <div class="action-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                                <i class="fas fa-upload"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Restore Database</h5>
                            <p class="text-muted mb-3">Kembalikan database dari file backup SQL</p>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <input type="file" class="form-control" name="restore_file" accept=".sql" required>
                                </div>
                                <button type="submit" name="restore" class="btn btn-danger btn-lg"
                                        onclick="return confirm('PERINGATAN: Restore akan menimpa data saat ini. Pastikan Anda sudah backup terlebih dahulu. Lanjutkan?')">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>Restore
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Backup Files List -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-folder-open me-2 text-primary"></i>File Backup Tersimpan
                        </h5>
                        <span class="badge bg-primary"><?php echo count($backup_files); ?> file</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($backup_files)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>Belum ada file backup</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($backup_files as $file): ?>
                        <div class="backup-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-code text-primary me-2"></i>
                                    <strong><?php echo $file['name']; ?></strong>
                                    <span class="badge bg-secondary ms-2">
                                        <?php echo number_format($file['size'] / 1024, 1); ?> KB
                                    </span>
                                </div>
                                <div>
                                    <small class="text-muted me-3">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', $file['date']); ?>
                                    </small>
                                    <a href="?download=<?php echo urlencode($file['name']); ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="?delete=<?php echo urlencode($file['name']); ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Hapus file ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Warning -->
                <div class="alert alert-warning mt-4">
                    <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Peringatan!</h6>
                    <ul class="mb-0">
                        <li>Backup secara rutin untuk mencegah kehilangan data</li>
                        <li>Restore akan <strong>menimpa</strong> semua data saat ini</li>
                        <li>Simpan file backup di lokasi aman (cloud storage, external drive)</li>
                        <li>Jangan bagikan file backup ke pihak yang tidak berwenang</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

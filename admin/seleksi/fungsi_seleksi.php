<?php
/**
 * File: admin/seleksi/fungsi_seleksi.php
 * Deskripsi: Kumpulan fungsi logika seleksi (Dual Choice)
 */

/**
 * Fungsi Umum Seleksi per-Sekolah
 * Proses Tahap 1 (Pilihan 1) dan Tahap 2 (Pilihan 2) sekaligus untuk satu sekolah.
 */
function proses_seleksi_sekolah($conn, $id_sekolah, $id_jalur, $kuota, $tahun_ajaran_val) {
    global $log_proses; // Menggunakan global array log jika ada
    
    // ==========================================
    // TAHAP 1: SELEKSI PILIHAN PERTAMA
    // ==========================================
    if(isset($log_proses)) $log_proses[] = ">> TAHAP 1: Memproses Pilihan Pertama...";
    
    // Ambil pendaftar yang memilih sekolah ini sebagai Pilihan 1
    // Filter: Status 'terverifikasi'
    $sql_1 = "SELECT p.*, s.tanggal_lahir, s.nama_lengkap
            FROM pendaftaran p
            JOIN siswa s ON p.id_siswa = s.id_siswa
            WHERE p.id_sekolah = ? 
            AND p.id_jalur = ? 
            AND p.tahun_ajaran = ?
            AND p.status_verifikasi = 'terverifikasi'";
            
    // Tambahan filter per jalur
    $nama_jalur = get_nama_jalur_func($conn, $id_jalur);
    // Filter berdasarkan jalur dengan dokumen dari tabel dokumen_siswa
    if ($nama_jalur == 'Afirmasi') {
        // Afirmasi: Harus punya dokumen pendukung yang SUDAH DIVERIFIKASI VALID
        $sql_1 .= " AND EXISTS (
            SELECT 1 FROM dokumen_siswa ds 
            WHERE ds.id_siswa = p.id_siswa 
            AND ds.kategori = 'afirmasi'
            AND ds.status_verifikasi = 'valid'
        )";
    } elseif ($nama_jalur == 'Prestasi') {
        // Prestasi: Harus punya sertifikat yang valid dan maks 3 tahun
        $sql_1 .= " AND EXISTS (
            SELECT 1 FROM dokumen_siswa ds 
            WHERE ds.id_siswa = p.id_siswa 
            AND ds.kategori = 'prestasi'
            AND ds.status_verifikasi = 'valid'
            AND (ds.tanggal_terbit IS NULL OR YEAR(CURDATE()) - YEAR(ds.tanggal_terbit) <= 3)
        )";
    } elseif ($nama_jalur == 'Mutasi') {
        // Mutasi: Harus punya dokumen mutasi yang valid
        $sql_1 .= " AND EXISTS (
            SELECT 1 FROM dokumen_siswa ds 
            WHERE ds.id_siswa = p.id_siswa 
            AND ds.kategori = 'mutasi'
            AND ds.status_verifikasi = 'valid'
        )";
    }

    // Sorting Logic (Sesuai Jalur)
    $order_clause = get_order_clause_func($nama_jalur);
    $sql_1 .= " ORDER BY " . $order_clause;

    $stmt_1 = mysqli_prepare($conn, $sql_1);
    mysqli_stmt_bind_param($stmt_1, "iis", $id_sekolah, $id_jalur, $tahun_ajaran_val);
    mysqli_stmt_execute($stmt_1);
    $result_1 = mysqli_stmt_get_result($stmt_1);
    
    $ranking_global = 1;
    $lulus_count = 0;
    
    while ($row = mysqli_fetch_assoc($result_1)) {
        // Cek apakah masih ada kuota
        if ($lulus_count < $kuota) {
            $status = 'lulus';
            $pilihan_diterima = 1; // Diterima di Pilihan 1
            $lulus_count++;
        } else {
            $status = 'tidak_lulus';
            $pilihan_diterima = NULL;
        }
        
        simpan_hasil_func($conn, $row, $ranking_global, $status, $pilihan_diterima, $nama_jalur);
        
        if(isset($log_proses)) $log_proses[] = "#$ranking_global [P1] {$row['nama_lengkap']} - Status: " . strtoupper($status);
        $ranking_global++;
    }
    
    // ==========================================
    // TAHAP 2: SELEKSI PILIHAN KEDUA
    // ==========================================
    $sisa_kuota = $kuota - $lulus_count;
    
    if ($sisa_kuota > 0) {
        if(isset($log_proses)) $log_proses[] = ">> TAHAP 2: Memproses Pilihan Kedua (Sisa Kuota: $sisa_kuota)...";
        
        $sql_2 = "SELECT p.*, s.tanggal_lahir, s.nama_lengkap, 
                  (SELECT status_seleksi FROM hasil_seleksi WHERE id_pendaftaran = p.id_pendaftaran LIMIT 1) as status_p1
                  FROM pendaftaran p
                  JOIN siswa s ON p.id_siswa = s.id_siswa
                  WHERE p.id_sekolah_pilihan_2 = ? 
                  AND p.id_jalur = ? 
                  AND p.tahun_ajaran = ?
                  AND p.status_verifikasi = 'terverifikasi'";
                  
        // Filter berdasarkan jalur dengan dokumen dari tabel dokumen_siswa
        if ($nama_jalur == 'Afirmasi') {
            $sql_2 .= " AND EXISTS (
                SELECT 1 FROM dokumen_siswa ds 
                WHERE ds.id_siswa = p.id_siswa 
                AND ds.kategori = 'afirmasi'
                AND ds.status_verifikasi = 'valid'
            )";
        } elseif ($nama_jalur == 'Prestasi') {
            $sql_2 .= " AND EXISTS (
                SELECT 1 FROM dokumen_siswa ds 
                WHERE ds.id_siswa = p.id_siswa 
                AND ds.kategori = 'prestasi'
                AND ds.status_verifikasi = 'valid'
                AND (ds.tanggal_terbit IS NULL OR YEAR(CURDATE()) - YEAR(ds.tanggal_terbit) <= 3)
            )";
        } elseif ($nama_jalur == 'Mutasi') {
            $sql_2 .= " AND EXISTS (
                SELECT 1 FROM dokumen_siswa ds 
                WHERE ds.id_siswa = p.id_siswa 
                AND ds.kategori = 'mutasi'
                AND ds.status_verifikasi = 'valid'
            )";
        }
        
        // Sorting Logic for Pass 2: Use jarak_sekolah_2 for Zonasi/Afirmasi
        $order_clause_p2 = get_order_clause_p2_func($nama_jalur);
        $sql_2 .= " ORDER BY " . $order_clause_p2;
        
        $stmt_2 = mysqli_prepare($conn, $sql_2);
        mysqli_stmt_bind_param($stmt_2, "iis", $id_sekolah, $id_jalur, $tahun_ajaran_val);
        mysqli_stmt_execute($stmt_2);
        $result_2 = mysqli_stmt_get_result($stmt_2);
        
        while ($row = mysqli_fetch_assoc($result_2)) {
            // Cek apakah siswa ini SUDAH LULUS di Pilihan 1?
            if ($row['status_p1'] == 'lulus') {
                if(isset($log_proses)) $log_proses[] = "[SKIP] {$row['nama_lengkap']} sudah lulus di Pilihan 1.";
                continue; 
            }
            
            if ($lulus_count < $kuota) {
                $status = 'lulus';
                $pilihan_diterima = 2; // Diterima di Pilihan 2
                $lulus_count++;
                
                simpan_hasil_func($conn, $row, $ranking_global, $status, $pilihan_diterima, $nama_jalur);
                if(isset($log_proses)) $log_proses[] = "#$ranking_global [P2] {$row['nama_lengkap']} - Status: LULUS (Pilihan 2)";
            } else {
                if(isset($log_proses)) $log_proses[] = "#$ranking_global [P2] {$row['nama_lengkap']} - Status: GAGAL (Kuota Penuh)";
            }
            $ranking_global++;
        }
        
    } else {
        if(isset($log_proses)) $log_proses[] = ">> TAHAP 2 SKIP: Kuota sudah penuh oleh Pilihan 1.";
    }
    
    return $lulus_count;
}

function get_nama_jalur_func($conn, $id_jalur) {
    $q = mysqli_query($conn, "SELECT nama_jalur FROM jalur_ppdb WHERE id_jalur = $id_jalur");
    $r = mysqli_fetch_assoc($q);
    return $r ? $r['nama_jalur'] : '';
}

// Jalur ID Constants (sesuaikan dengan database)
define('JALUR_ZONASI', 1);
define('JALUR_PRESTASI', 2);
define('JALUR_AFIRMASI', 3);
define('JALUR_MUTASI', 4);

function get_order_clause_func($nama_jalur) {
    // Sorting sesuai persyaratan Kota Padang:
    // - Zonasi/Afirmasi/Mutasi: Jarak → Usia → Waktu Pendaftaran
    // - Prestasi: Nilai → Jarak → Usia → Waktu Pendaftaran
    switch ($nama_jalur) {
        case 'Zonasi':   return "p.jarak_rumah_sekolah ASC, s.tanggal_lahir ASC, p.tanggal_daftar ASC";
        case 'Prestasi': return "p.nilai_rapor_rata DESC, p.jarak_rumah_sekolah ASC, s.tanggal_lahir ASC, p.tanggal_daftar ASC";
        case 'Afirmasi': return "p.jarak_rumah_sekolah ASC, s.tanggal_lahir ASC, p.tanggal_daftar ASC";
        case 'Mutasi':   return "p.jarak_rumah_sekolah ASC, s.tanggal_lahir ASC, p.tanggal_daftar ASC";
        default: return "p.id_pendaftaran ASC";
    }
}

// Pass 2 uses jarak_sekolah_2 for distance-based ranking
function get_order_clause_p2_func($nama_jalur) {
    // Sorting sesuai persyaratan Kota Padang (untuk Pilihan 2):
    // - Zonasi/Afirmasi/Mutasi: Jarak ke Sekolah 2 → Usia → Waktu Pendaftaran
    // - Prestasi: Nilai → Jarak ke Sekolah 2 → Usia → Waktu Pendaftaran
    switch ($nama_jalur) {
        case 'Zonasi':   return "p.jarak_sekolah_2 ASC, s.tanggal_lahir ASC, p.tanggal_daftar ASC";
        case 'Prestasi': return "p.nilai_rapor_rata DESC, p.jarak_sekolah_2 ASC, s.tanggal_lahir ASC, p.tanggal_daftar ASC";
        case 'Afirmasi': return "p.jarak_sekolah_2 ASC, s.tanggal_lahir ASC, p.tanggal_daftar ASC";
        case 'Mutasi':   return "p.jarak_sekolah_2 ASC, s.tanggal_lahir ASC, p.tanggal_daftar ASC";
        default: return "p.id_pendaftaran ASC";
    }
}

function simpan_hasil_func($conn, $row, $ranking, $status, $pilihan_diterima, $nama_jalur) {
    $skor = ($nama_jalur == 'Prestasi') ? $row['nilai_rapor_rata'] : (100 - $row['jarak_rumah_sekolah']);
    $ket = "$nama_jalur (P$pilihan_diterima)";
    
    $sql = "INSERT INTO hasil_seleksi 
            (id_pendaftaran, ranking, skor_akhir, status_seleksi, pilihan_diterima, keterangan, tanggal_seleksi)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            ranking = VALUES(ranking), 
            skor_akhir = VALUES(skor_akhir),
            status_seleksi = VALUES(status_seleksi),
            pilihan_diterima = VALUES(pilihan_diterima),
            keterangan = VALUES(keterangan)";
            
    $stmt = mysqli_prepare($conn, $sql);
    // Type binding: id_pendaftaran(i), ranking(i), skor_akhir(d), status_seleksi(s), pilihan_diterima(i/null), keterangan(s)
    // Note: When pilihan_diterima is NULL (for tidak_lulus), we need to handle it specially
    $pilihan_int = $pilihan_diterima ? (int)$pilihan_diterima : null;
    mysqli_stmt_bind_param($stmt, "iidsis", 
        $row['id_pendaftaran'], $ranking, $skor, $status, $pilihan_int, $ket);
    mysqli_stmt_execute($stmt);
}
?>

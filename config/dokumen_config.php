<?php
/**
 * File: config/dokumen_config.php
 * Deskripsi: Konfigurasi dokumen per jalur PPDB
 * 
 * ALUR DOKUMEN:
 * 1. Siswa upload dokumen UMUM → wajib untuk semua jalur
 * 2. Siswa pilih jalur → muncul info dokumen KHUSUS yang dibutuhkan
 * 3. Siswa upload dokumen KHUSUS (opsional, tapi wajib jika pilih jalur itu)
 * 4. Saat SELEKSI, sistem cek dokumen khusus sudah VALID
 */

// Definisi Dokumen UMUM (wajib semua jalur)
define('DOKUMEN_UMUM', [
    'kk' => [
        'label' => 'Kartu Keluarga',
        'icon' => 'fa-users',
        'color' => 'primary',
        'wajib' => true,
        'keterangan' => 'Scan KK yang masih berlaku'
    ],
    'skl' => [
        'label' => 'Surat Keterangan Lulus / Ijazah',
        'icon' => 'fa-certificate',
        'color' => 'success',
        'wajib' => true,
        'keterangan' => 'SKL dari sekolah asal atau ijazah'
    ],
    'rapor' => [
        'label' => 'Rapor Semester 1-5',
        'icon' => 'fa-book',
        'color' => 'info',
        'wajib' => true,
        'keterangan' => 'Scan rapor semester 1 sampai 5'
    ],
    'foto' => [
        'label' => 'Pas Foto 3x4',
        'icon' => 'fa-image',
        'color' => 'warning',
        'wajib' => true,
        'keterangan' => 'Pas foto terbaru latar merah/biru'
    ]
]);

// Definisi Dokumen AFIRMASI (pilih salah satu)
define('DOKUMEN_AFIRMASI', [
    'dtks' => [
        'label' => 'DTKS (Data Terpadu Kesejahteraan Sosial)',
        'icon' => 'fa-file-alt',
        'color' => 'success',
        'keterangan' => 'Bukti terdaftar di DTKS'
    ],
    'sktm' => [
        'label' => 'SKTM (Surat Keterangan Tidak Mampu)',
        'icon' => 'fa-file-alt',
        'color' => 'success',
        'keterangan' => 'SKTM dari kelurahan/desa'
    ],
    'kis' => [
        'label' => 'Kartu Indonesia Sehat (KIS)',
        'icon' => 'fa-id-card',
        'color' => 'success',
        'keterangan' => 'Kartu KIS yang masih berlaku'
    ],
    'pkh' => [
        'label' => 'Kartu PKH (Program Keluarga Harapan)',
        'icon' => 'fa-id-card',
        'color' => 'success',
        'keterangan' => 'Kartu PKH yang masih berlaku'
    ],
    'surat_disabilitas' => [
        'label' => 'Surat Keterangan Disabilitas',
        'icon' => 'fa-wheelchair',
        'color' => 'info',
        'keterangan' => 'Surat keterangan dari dokter/RS'
    ],
    'surat_panti' => [
        'label' => 'Surat Keterangan Panti Asuhan',
        'icon' => 'fa-home',
        'color' => 'info',
        'keterangan' => 'Surat keterangan dari panti asuhan'
    ]
]);

// Definisi Dokumen PRESTASI
define('DOKUMEN_PRESTASI', [
    'sertifikat_prestasi' => [
        'label' => 'Sertifikat Prestasi',
        'icon' => 'fa-trophy',
        'color' => 'warning',
        'keterangan' => 'Sertifikat/piagam prestasi (maks 3 tahun terakhir, divalidasi Disdik Padang)',
        'validasi_tahun' => true,
        'max_tahun' => 3
    ]
]);

// Definisi Dokumen MUTASI
define('DOKUMEN_MUTASI', [
    'surat_tugas_ortu' => [
        'label' => 'Surat Tugas Orang Tua',
        'icon' => 'fa-briefcase',
        'color' => 'danger',
        'keterangan' => 'Surat tugas dari instansi/perusahaan tempat orang tua bekerja'
    ],
    'surat_pindah_domisili' => [
        'label' => 'Surat Pindah Domisili',
        'icon' => 'fa-map-marker-alt',
        'color' => 'danger',
        'keterangan' => 'Surat keterangan pindah dari kelurahan asal'
    ],
    'surat_tugas_guru' => [
        'label' => 'Surat Tugas Guru (Anak Guru)',
        'icon' => 'fa-chalkboard-teacher',
        'color' => 'purple',
        'keterangan' => 'Khusus anak guru: Surat tugas guru dari Dinas Pendidikan'
    ],
    'kk_mutasi' => [
        'label' => 'KK Baru (Anak Guru)',
        'icon' => 'fa-users',
        'color' => 'purple',
        'keterangan' => 'Khusus anak guru: KK yang menunjukkan hubungan dengan guru'
    ]
]);

/**
 * Mendapatkan semua dokumen berdasarkan kategori
 */
function get_dokumen_by_kategori($kategori) {
    switch (strtolower($kategori)) {
        case 'umum':
            return DOKUMEN_UMUM;
        case 'afirmasi':
            return DOKUMEN_AFIRMASI;
        case 'prestasi':
            return DOKUMEN_PRESTASI;
        case 'mutasi':
            return DOKUMEN_MUTASI;
        default:
            return [];
    }
}

/**
 * Mendapatkan label dokumen berdasarkan jenis
 */
function get_label_dokumen($jenis_dokumen) {
    $semua_dokumen = array_merge(
        DOKUMEN_UMUM, DOKUMEN_AFIRMASI, DOKUMEN_PRESTASI, DOKUMEN_MUTASI
    );
    return $semua_dokumen[$jenis_dokumen]['label'] ?? ucwords(str_replace('_', ' ', $jenis_dokumen));
}

/**
 * Cek apakah dokumen umum siswa sudah lengkap
 */
function cek_dokumen_umum_siswa($conn, $id_siswa) {
    $dokumen_wajib = array_keys(DOKUMEN_UMUM);
    $dokumen_ada = [];
    
    // Query dokumen siswa
    $sql = "SELECT jenis_dokumen FROM dokumen_siswa 
            WHERE id_siswa = ? AND kategori = 'umum'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_siswa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $dokumen_ada[] = strtolower($row['jenis_dokumen']);
    }
    
    $kurang = array_diff($dokumen_wajib, $dokumen_ada);
    
    if (empty($kurang)) {
        return ['lengkap' => true, 'pesan' => ''];
    }
    
    $label_kurang = [];
    foreach ($kurang as $jenis) {
        $label_kurang[] = DOKUMEN_UMUM[$jenis]['label'];
    }
    
    return [
        'lengkap' => false,
        'pesan' => 'Dokumen umum belum lengkap: ' . implode(', ', $label_kurang)
    ];
}

/**
 * Cek apakah dokumen khusus jalur sudah ada dan valid
 */
function cek_dokumen_jalur_valid($conn, $id_siswa, $jalur) {
    $jalur_lower = strtolower($jalur);
    
    // Zonasi tidak butuh dokumen khusus
    if ($jalur_lower == 'zonasi') {
        return ['valid' => true, 'pesan' => ''];
    }
    
    // Query dokumen khusus yang sudah valid
    $sql = "SELECT jenis_dokumen, tanggal_terbit FROM dokumen_siswa 
            WHERE id_siswa = ? AND kategori = ? AND status_verifikasi = 'valid'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $id_siswa, $jalur_lower);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $ada_valid = false;
    while ($row = mysqli_fetch_assoc($result)) {
        // Khusus Prestasi: cek validitas tahun (maks 3 tahun)
        if ($jalur_lower == 'prestasi' && $row['jenis_dokumen'] == 'sertifikat_prestasi') {
            if ($row['tanggal_terbit']) {
                $tahun_terbit = (int)date('Y', strtotime($row['tanggal_terbit']));
                $tahun_sekarang = (int)date('Y');
                if (($tahun_sekarang - $tahun_terbit) <= 3) {
                    $ada_valid = true;
                    break;
                }
            }
        } else {
            $ada_valid = true;
            break;
        }
    }
    
    if ($ada_valid) {
        return ['valid' => true, 'pesan' => ''];
    }
    
    // Buat pesan error sesuai jalur
    $pesan = match($jalur_lower) {
        'afirmasi' => 'Tidak ada dokumen Afirmasi yang valid (DTKS/SKTM/KIS/PKH/Surat Disabilitas/Surat Panti)',
        'prestasi' => 'Tidak ada Sertifikat Prestasi yang valid (maks 3 tahun terakhir, sudah diverifikasi)',
        'mutasi' => 'Tidak ada dokumen Mutasi yang valid (Surat Tugas/Pindah Domisili)',
        default => 'Dokumen khusus belum diverifikasi'
    };
    
    return ['valid' => false, 'pesan' => $pesan];
}

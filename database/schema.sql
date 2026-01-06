-- ================================================================
-- PPDB SMP - Database Schema
-- ================================================================
-- File: database/schema.sql
-- Description: Complete database schema for PPDB SMP application
-- Version: 1.0 (Consolidated)
-- Last Updated: 2026-01-04
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ================================================================
-- 1. CORE TABLES
-- ================================================================

-- Tabel Users (Super Admin, Operator, Siswa)
CREATE TABLE IF NOT EXISTS `users` (
    `id_user` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `role` ENUM('super_admin', 'operator', 'siswa') DEFAULT 'siswa',
    `id_sekolah` INT NULL COMMENT 'Untuk operator sekolah',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Sekolah
CREATE TABLE IF NOT EXISTS `sekolah` (
    `id_sekolah` INT AUTO_INCREMENT PRIMARY KEY,
    `npsn` VARCHAR(20) NOT NULL UNIQUE,
    `nama_sekolah` VARCHAR(100) NOT NULL,
    `alamat_sekolah` TEXT,
    `kelurahan` VARCHAR(50),
    `kecamatan` VARCHAR(50),
    `kota` VARCHAR(50) DEFAULT 'Padang',
    `provinsi` VARCHAR(50) DEFAULT 'Sumatera Barat',
    `latitude` DECIMAL(10,8),
    `longitude` DECIMAL(11,8),
    `kuota_total` INT DEFAULT 0,
    `tahun_ajaran` VARCHAR(20),
    `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    `akreditasi` ENUM('A','B','C') DEFAULT 'B',
    `telepon` VARCHAR(20),
    `kepala_sekolah` VARCHAR(100),
    `email_sekolah` VARCHAR(100),
    `foto` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kecamatan (kecamatan),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Siswa
CREATE TABLE IF NOT EXISTS `siswa` (
    `id_siswa` INT AUTO_INCREMENT PRIMARY KEY,
    `id_user` INT NOT NULL UNIQUE,
    `nisn` VARCHAR(20) NOT NULL UNIQUE,
    `nik` VARCHAR(20) NOT NULL UNIQUE,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `tempat_lahir` VARCHAR(50),
    `tanggal_lahir` DATE,
    `jenis_kelamin` ENUM('L', 'P'),
    `agama` VARCHAR(20),
    `alamat` TEXT,
    `kelurahan` VARCHAR(50),
    `kecamatan` VARCHAR(50),
    `kota` VARCHAR(50) DEFAULT 'Padang',
    `provinsi` VARCHAR(50) DEFAULT 'Sumatera Barat',
    `kode_pos` VARCHAR(10),
    `no_telepon` VARCHAR(20),
    `latitude` DECIMAL(10,8) COMMENT 'Koordinat rumah',
    `longitude` DECIMAL(11,8) COMMENT 'Koordinat rumah',
    `nama_ayah` VARCHAR(100),
    `pekerjaan_ayah` VARCHAR(50),
    `nama_ibu` VARCHAR(100),
    `pekerjaan_ibu` VARCHAR(50),
    `no_telepon_ortu` VARCHAR(20),
    `asal_sekolah` VARCHAR(100),
    `npsn_asal` VARCHAR(20),
    `foto` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    INDEX idx_kecamatan (kecamatan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- 2. JALUR PPDB & KUOTA
-- ================================================================

-- Tabel Jalur PPDB
CREATE TABLE IF NOT EXISTS `jalur_ppdb` (
    `id_jalur` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_jalur` VARCHAR(50) NOT NULL,
    `deskripsi` TEXT,
    `persentase_kuota` INT DEFAULT 0,
    `urutan` INT DEFAULT 1,
    `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Kuota Per Jalur Per Sekolah
CREATE TABLE IF NOT EXISTS `kuota_jalur` (
    `id_kuota` INT AUTO_INCREMENT PRIMARY KEY,
    `id_sekolah` INT NOT NULL,
    `id_jalur` INT NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `jumlah_kuota` INT DEFAULT 0,
    `kuota_terpakai` INT DEFAULT 0,
    FOREIGN KEY (id_sekolah) REFERENCES sekolah(id_sekolah) ON DELETE CASCADE,
    FOREIGN KEY (id_jalur) REFERENCES jalur_ppdb(id_jalur) ON DELETE CASCADE,
    UNIQUE KEY unique_kuota (id_sekolah, id_jalur, tahun_ajaran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Jadwal Per Jalur
CREATE TABLE IF NOT EXISTS `jadwal_jalur` (
    `id_jadwal` INT AUTO_INCREMENT PRIMARY KEY,
    `id_jalur` INT NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `tanggal_buka` DATE NOT NULL,
    `tanggal_tutup` DATE NOT NULL,
    `tanggal_seleksi` DATE,
    `tanggal_pengumuman` DATE,
    `status` ENUM('belum_dibuka', 'dibuka', 'ditutup', 'seleksi', 'pengumuman', 'selesai') DEFAULT 'belum_dibuka',
    `urutan` INT DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jalur) REFERENCES jalur_ppdb(id_jalur) ON DELETE CASCADE,
    UNIQUE KEY unique_jalur_tahun (id_jalur, tahun_ajaran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Pengaturan PPDB
CREATE TABLE IF NOT EXISTS `pengaturan_ppdb` (
    `id_pengaturan` INT AUTO_INCREMENT PRIMARY KEY,
    `tahun_ajaran` VARCHAR(20) NOT NULL UNIQUE,
    `tanggal_mulai_daftar` DATE NOT NULL,
    `tanggal_akhir_daftar` DATE NOT NULL,
    `tanggal_seleksi` DATE NOT NULL,
    `tanggal_pengumuman` DATE NOT NULL,
    `tanggal_daftar_ulang` DATE NOT NULL,
    `status_ppdb` ENUM('belum_dibuka', 'pendaftaran', 'seleksi', 'pengumuman', 'daftar_ulang', 'selesai') DEFAULT 'belum_dibuka',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- 3. PENDAFTARAN & DOKUMEN
-- ================================================================

-- Tabel Pendaftaran
CREATE TABLE IF NOT EXISTS `pendaftaran` (
    `id_pendaftaran` INT AUTO_INCREMENT PRIMARY KEY,
    `id_siswa` INT NOT NULL,
    `id_sekolah` INT NOT NULL COMMENT 'Pilihan 1',
    `id_sekolah_pilihan_2` INT NULL COMMENT 'Pilihan 2',
    `id_jalur` INT NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `nomor_pendaftaran` VARCHAR(50) UNIQUE,
    `tanggal_daftar` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status_berkas` ENUM('belum_lengkap', 'lengkap', 'terverifikasi') DEFAULT 'belum_lengkap',
    `status_verifikasi` ENUM('pending', 'terverifikasi', 'ditolak') DEFAULT 'pending',
    `catatan_verifikasi` TEXT,
    `verifikasi_at` TIMESTAMP NULL,
    `verifikasi_by` INT NULL,
    `jarak_rumah` DECIMAL(10,2) COMMENT 'Jarak dalam meter',
    `nilai_rapor_rata` DECIMAL(5,2),
    `catatan_koreksi_nilai` TEXT,
    `tanggal_koreksi_nilai` TIMESTAMP NULL,
    `skor_akhir` DECIMAL(10,4),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE,
    FOREIGN KEY (id_sekolah) REFERENCES sekolah(id_sekolah) ON DELETE CASCADE,
    FOREIGN KEY (id_sekolah_pilihan_2) REFERENCES sekolah(id_sekolah) ON DELETE SET NULL,
    FOREIGN KEY (id_jalur) REFERENCES jalur_ppdb(id_jalur) ON DELETE CASCADE,
    INDEX idx_tahun_ajaran (tahun_ajaran),
    INDEX idx_status (status_verifikasi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Dokumen Siswa
CREATE TABLE IF NOT EXISTS `dokumen_siswa` (
    `id_dokumen_siswa` INT AUTO_INCREMENT PRIMARY KEY,
    `id_siswa` INT NOT NULL,
    `kategori` ENUM('umum', 'afirmasi', 'prestasi', 'mutasi') NOT NULL DEFAULT 'umum',
    `jenis_dokumen` VARCHAR(50) NOT NULL,
    `nama_file` VARCHAR(255) NOT NULL,
    `path_file` VARCHAR(500) NOT NULL,
    `ukuran_file` INT DEFAULT 0,
    `status_verifikasi` ENUM('pending', 'valid', 'tidak_valid') DEFAULT 'pending',
    `catatan` TEXT,
    `tanggal_terbit` DATE DEFAULT NULL COMMENT 'Untuk sertifikat prestasi',
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `verified_at` TIMESTAMP NULL,
    `verified_by` INT NULL,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE,
    INDEX idx_siswa_kategori (id_siswa, kategori),
    INDEX idx_status (status_verifikasi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- 4. HASIL SELEKSI
-- ================================================================

-- Tabel Hasil Seleksi
CREATE TABLE IF NOT EXISTS `hasil_seleksi` (
    `id_hasil` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pendaftaran` INT NOT NULL,
    `id_sekolah` INT NOT NULL,
    `id_jalur` INT NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `status_seleksi` ENUM('lulus', 'tidak_lulus', 'cadangan') DEFAULT 'tidak_lulus',
    `ranking` INT,
    `pilihan_diterima` TINYINT(1) NULL COMMENT '1: Pilihan 1, 2: Pilihan 2',
    `skor_akhir` DECIMAL(10,4),
    `tanggal_seleksi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `catatan` TEXT,
    FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran) ON DELETE CASCADE,
    FOREIGN KEY (id_sekolah) REFERENCES sekolah(id_sekolah) ON DELETE CASCADE,
    FOREIGN KEY (id_jalur) REFERENCES jalur_ppdb(id_jalur) ON DELETE CASCADE,
    INDEX idx_tahun (tahun_ajaran),
    INDEX idx_status (status_seleksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- 5. LOG & AKTIVITAS
-- ================================================================

-- Tabel Log Aktivitas
CREATE TABLE IF NOT EXISTS `log_aktivitas` (
    `id_log` INT AUTO_INCREMENT PRIMARY KEY,
    `id_user` INT,
    `aktivitas` VARCHAR(100),
    `deskripsi` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (id_user),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- 6. FOREIGN KEY FOR USERS -> SEKOLAH
-- ================================================================

ALTER TABLE users ADD CONSTRAINT fk_users_sekolah 
    FOREIGN KEY (id_sekolah) REFERENCES sekolah(id_sekolah) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- 7. DEFAULT DATA
-- ================================================================

-- Insert Jalur PPDB
INSERT INTO `jalur_ppdb` (`nama_jalur`, `deskripsi`, `persentase_kuota`, `urutan`) VALUES
('Zonasi', 'Jalur berdasarkan jarak tempat tinggal ke sekolah', 50, 4),
('Prestasi', 'Jalur berdasarkan nilai rapor', 25, 2),
('Afirmasi', 'Jalur untuk keluarga tidak mampu/berkebutuhan khusus', 15, 1),
('Mutasi', 'Jalur untuk siswa yang mengikuti pindah tugas orang tua', 10, 3)
ON DUPLICATE KEY UPDATE nama_jalur = nama_jalur;

-- Insert Super Admin Default
INSERT INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ppdb.sch.id', 'super_admin')
ON DUPLICATE KEY UPDATE username = username;
-- Default password: password

-- ================================================================
-- END OF SCHEMA
-- ================================================================

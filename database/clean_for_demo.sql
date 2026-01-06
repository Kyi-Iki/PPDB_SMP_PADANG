-- ================================================================
-- SCRIPT: Clean Database for Demo
-- Date: 2026-01-04
-- Description: Removes all data except super_admin and operator users
--              for a clean demo presentation
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ================================================================
-- 1. Clear Student Related Data (with IF EXISTS checks)
-- ================================================================

-- Clear student documents
DROP TABLE IF EXISTS dokumen_siswa_temp;
DELETE FROM dokumen_siswa WHERE 1=1;

-- Clear student registrations/applications  
DELETE FROM pendaftaran WHERE 1=1;

-- Clear student selection results
DELETE FROM hasil_seleksi WHERE 1=1;

-- Clear student data
DELETE FROM siswa WHERE 1=1;

-- ================================================================
-- 2. Clear User Data (Keep Admin/Operator Only)
-- ================================================================

-- Delete all users with role 'siswa'
DELETE FROM users WHERE role = 'siswa';

-- ================================================================
-- 3. Clear Activity Logs
-- ================================================================

DELETE FROM log_aktivitas WHERE 1=1;

-- ================================================================
-- 4. Reset Auto Increment
-- ================================================================

ALTER TABLE siswa AUTO_INCREMENT = 1;
ALTER TABLE pendaftaran AUTO_INCREMENT = 1;
ALTER TABLE dokumen_siswa AUTO_INCREMENT = 1;
ALTER TABLE hasil_seleksi AUTO_INCREMENT = 1;
ALTER TABLE log_aktivitas AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- DONE! Database cleaned for demo.
-- ================================================================
SELECT 'Database cleaned successfully!' AS Result;

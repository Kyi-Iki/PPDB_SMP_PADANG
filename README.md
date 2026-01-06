# PPDB SMP - Sistem Penerimaan Peserta Didik Baru

Sistem informasi PPDB (Penerimaan Peserta Didik Baru) untuk SMP Kota Padang dengan fitur pendaftaran online, seleksi berbasis jarak, dan manajemen data terpusat.

## 🚀 Fitur Utama

### Untuk Siswa
- ✅ Pendaftaran akun & login dengan validasi NISN/NIK
- ✅ Pengisian biodata lengkap dengan peta interaktif
- ✅ Upload dokumen (KK, Akta, Ijazah, Rapor)
- ✅ Pemilihan sekolah (2 pilihan) dan jalur PPDB
- ✅ Cek hasil seleksi online
- ✅ Cetak kartu peserta & surat kelulusan

### Untuk Admin
- ✅ Dashboard dengan analytics (Chart.js)
- ✅ Verifikasi dokumen siswa
- ✅ Manajemen sekolah & kuota (dengan dropdown kecamatan)
- ✅ Peta lokasi sekolah interaktif
- ✅ Proses seleksi otomatis per jalur
- ✅ Publikasi hasil seleksi
- ✅ Export laporan
- ✅ **Role-Based Access Control (RBAC)**

### Jalur PPDB
| Jalur | Kuota | Kriteria Seleksi |
|-------|-------|------------------|
| **Zonasi** | 50% | Jarak rumah ke sekolah (semakin dekat semakin prioritas) |
| **Prestasi** | 25% | Nilai rapor semester 1-5 |
| **Afirmasi** | 15% | DTKS/disabilitas/anak panti |
| **Mutasi** | 10% | Perpindahan dari luar daerah |

---

## 🔐 Role-Based Access Control (RBAC)

| Role | Akses | Keterangan |
|------|-------|------------|
| `super_admin` | Semua fitur, semua sekolah | Full access |
| `operator` | Data sekolah sendiri saja | Terbatas per sekolah |

---

## ⚙️ Persyaratan Sistem

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.4+
- Apache/Nginx
- XAMPP (untuk development lokal)

---

## 📦 Instalasi

### 1. Clone Repository
```bash
cd C:\xampp\htdocs
git clone [repository-url] ppdb-smp
```

### 2. Buat Database
```sql
CREATE DATABASE ppdb_smp;
```

### 3. Import Schema
```bash
# Via command line
mysql -u root ppdb_smp < database/schema.sql

# Atau via phpMyAdmin
# Import file: database/schema.sql
```

### 4. Konfigurasi Database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ppdb_smp');
```

### 5. Akses Aplikasi
```
Frontend: http://localhost/ppdb-smp/
Admin:    http://localhost/ppdb-smp/admin/
```

### 6. Login Default
```
Super Admin:
- Username: admin
- Password: password

Siswa:
- Daftar via: /siswa/register.php
```

---

## 📁 Struktur Folder

```
ppdb-smp/
├── admin/              # Halaman admin
│   ├── kuota/          # Manajemen kuota
│   ├── laporan/        # Export laporan
│   ├── pendaftaran/    # Data pendaftar
│   ├── sekolah/        # CRUD sekolah
│   ├── seleksi/        # Proses seleksi
│   ├── users/          # Manajemen user
│   └── verifikasi/     # Verifikasi dokumen
├── assets/
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript (maps, etc)
│   └── data/           # GeoJSON kecamatan
├── config/             # Database & constants
├── database/
│   └── schema.sql      # Database schema
├── includes/           # Header, footer, sidebar
├── siswa/              # Halaman siswa
├── uploads/            # File uploads
├── index.php           # Landing page
├── panduan.php         # Panduan pendaftaran
└── sekolah_detail.php  # Detail sekolah
```

---

## �️ Fitur Peta

- **Peta Interaktif** - Leaflet.js dengan OpenStreetMap
- **Polygon Kecamatan** - Batas wilayah 11 kecamatan Kota Padang
- **Marker Sekolah** - Lokasi 20 SMP Negeri
- **Perhitungan Jarak** - Jarak langsung rumah-sekolah (GPS)
- **Floating Stats Panel** - Statistik sekolah dengan toggle polygon

---

## 🔒 Keamanan

- ✅ CSRF Protection
- ✅ Password hashing (bcrypt)
- ✅ Prepared statements (SQL injection prevention)
- ✅ Session management
- ✅ Input validation & sanitization
- ✅ Role-based access control

---

## 📝 Changelog

### v1.2.0 (2026-01-04)
- 🗑️ Hapus fitur radius zonasi (menggunakan jarak langsung)
- ✨ Peta full-width dengan floating stats panel
- ✨ Toggle switch polygon kecamatan
- ✨ Dropdown kecamatan di CRUD sekolah
- 🧹 Cleanup debug code & unused files
- 📦 Konsolidasi SQL ke single schema.sql

### v1.1.0 (2026-01-02)
- ✨ Role-Based Access Control (Super Admin & Operator)
- ✨ Data isolation per sekolah untuk operator

### v1.0.0 (2026-01-01)
- Initial release
- Dashboard analytics dengan Chart.js
- Fitur cetak kartu & surat kelulusan

---

## 📄 Lisensi

Proyek ini dikembangkan untuk Dinas Pendidikan Kota Padang.

---

## 🤝 Kontributor

Dikembangkan dengan ❤️ untuk PPDB SMP Kota Padang

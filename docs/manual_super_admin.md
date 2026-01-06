# 📘 Manual Book Super Admin
## Sistem PPDB SMP Kota Padang

**Versi**: 1.2.0  
**Tanggal**: Januari 2026  
**Target Pengguna**: Dinas Pendidikan Kota Padang

---

## Daftar Isi

1. [Login Sistem](#1-login-sistem)
2. [Dashboard & Analytics](#2-dashboard--analytics)
3. [Pengaturan PPDB](#3-pengaturan-ppdb)
4. [Jadwal Per Jalur](#4-jadwal-per-jalur)
5. [Kelola Sekolah](#5-kelola-sekolah)
6. [Kelola Kuota](#6-kelola-kuota)
7. [Kelola User & Operator](#7-kelola-user--operator)
8. [Verifikasi Dokumen](#8-verifikasi-dokumen)
9. [Proses Seleksi](#9-proses-seleksi)
10. [Publikasi Hasil](#10-publikasi-hasil)
11. [Laporan & Export](#11-laporan--export)
12. [Log Aktivitas](#12-log-aktivitas)

---

## 1. Login Sistem

### Langkah-langkah:
1. Buka browser dan akses: `http://localhost/ppdb-smp/admin/`
2. Masukkan kredensial:
   - **Username**: `admin`
   - **Password**: `password`
3. Klik tombol **Login**

### Akses Super Admin:
Super Admin memiliki akses ke **semua fitur** dan **semua sekolah**.

> 📸 **Screenshot yang harus diambil:**
> - Halaman login admin (form kosong)
> - Halaman login admin (form terisi)
> - Tampilan setelah login berhasil (redirect ke dashboard)

---

## 2. Dashboard & Analytics

### Lokasi Menu:
Sidebar → **Dashboard** (menu paling atas)

### Informasi yang Ditampilkan:

| Widget | Deskripsi |
|--------|-----------|
| **Total Pendaftar** | Jumlah seluruh pendaftar tahun ajaran ini |
| **Terverifikasi** | Jumlah pendaftar yang sudah diverifikasi |
| **Menunggu Verifikasi** | Jumlah yang masih pending |
| **Total Sekolah** | Jumlah SMP yang terdaftar |

### Grafik:
- **Pie Chart**: Distribusi pendaftar per jalur (Zonasi, Prestasi, Afirmasi, Mutasi)
- **Bar Chart**: Pendaftar per sekolah

> 📸 **Screenshot yang harus diambil:**
> - Dashboard full view (atas)
> - Widget statistik
> - Grafik pie chart jalur
> - Grafik bar chart sekolah

---

## 3. Pengaturan PPDB

### Lokasi Menu:
Sidebar → **Pengaturan**

### Field yang Tersedia:

| Field | Deskripsi |
|-------|-----------|
| **Tahun Ajaran** | Contoh: 2025/2026 |
| **Tanggal Mulai Daftar** | Kapan pendaftaran dibuka |
| **Tanggal Akhir Daftar** | Kapan pendaftaran ditutup |
| **Tanggal Seleksi** | Kapan proses seleksi dilakukan |
| **Tanggal Pengumuman** | Kapan hasil diumumkan |
| **Status PPDB** | belum_dibuka / pendaftaran / seleksi / pengumuman / selesai |

### Langkah Mengubah:
1. Klik menu **Pengaturan**
2. Isi/ubah field yang diperlukan
3. Pilih **Status PPDB** sesuai periode
4. Klik **Simpan Pengaturan**

> 📸 **Screenshot:** Form pengaturan PPDB, dropdown status

---

## 4. Jadwal Per Jalur

### Lokasi Menu:
Sidebar → **Jadwal Jalur**

### Langkah Mengubah:
1. Klik menu **Jadwal Jalur**
2. Klik **Edit** pada jalur yang ingin diubah
3. Isi tanggal buka, tutup, seleksi, pengumuman
4. Pilih status jalur
5. Klik **Simpan**

> 📸 **Screenshot:** Tabel jadwal, form edit jadwal

---

## 5. Kelola Sekolah

### Lokasi Menu:
Sidebar → **Sekolah**

### Langkah Tambah Sekolah:
1. Klik **Sekolah** → **Tambah Sekolah**
2. Isi: NPSN, Nama, Alamat, Kecamatan, Kuota
3. Klik peta untuk set lokasi GPS
4. Klik **Simpan**

### Langkah Edit/Hapus:
1. Klik **Daftar Sekolah**
2. Klik ikon **Edit** atau **Hapus**

> 📸 **Screenshot:** Tabel sekolah, form tambah dengan peta, dropdown kecamatan

---

## 6. Kelola Kuota

### Lokasi Menu:
Sidebar → **Kuota**

### Distribusi Default:
| Jalur | Persentase |
|-------|------------|
| Zonasi | 50% |
| Prestasi | 25% |
| Afirmasi | 15% |
| Mutasi | 10% |

### Langkah:
1. Klik **Kuota**
2. Pilih sekolah
3. Isi kuota per jalur
4. Klik **Simpan**

> 📸 **Screenshot:** Form kuota per sekolah

---

## 7. Kelola User & Operator

### Lokasi Menu:
Sidebar → **Users**

### Langkah Tambah Operator:
1. Klik **Users** → **Tambah User**
2. Isi username, password, email
3. Role: **Operator**
4. Pilih sekolah yang ditangani
5. Klik **Simpan**

> 📸 **Screenshot:** Tabel user, form tambah operator

---

## 8. Verifikasi Dokumen

### Lokasi Menu:
Sidebar → **Verifikasi**

### Langkah:
1. Klik **Verifikasi**
2. Filter: sekolah, status, jalur
3. Klik nama siswa untuk detail
4. Preview dokumen
5. Pilih: **Valid** / **Tidak Valid**
6. Isi catatan jika ditolak
7. Klik **Simpan**

> 📸 **Screenshot:** Tabel pendaftar, preview dokumen, form status

---

## 9. Proses Seleksi

### Lokasi Menu:
Sidebar → **Seleksi** → **Proses Seleksi**

### Prasyarat:
- Status jalur: `seleksi`
- Dokumen sudah diverifikasi

### Algoritma:
| Jalur | Kriteria |
|-------|----------|
| Zonasi | Jarak terdekat |
| Prestasi | Nilai tertinggi |
| Afirmasi | Jarak + dokumen afirmasi valid |
| Mutasi | Urutan daftar + dokumen mutasi valid |

### Langkah:
1. Ubah status jalur → **Seleksi** (di Jadwal Jalur)
2. Klik **Proses Seleksi**
3. Pilih jalur & sekolah
4. Klik **Proses**
5. Lihat hasil ranking

> 📸 **Screenshot:** Form seleksi, hasil ranking

---

## 10. Publikasi Hasil

### Lokasi Menu:
Sidebar → **Seleksi** → **Publikasi**

### Langkah:
1. Ubah status jalur → **Pengumuman**
2. Klik **Publikasi**
3. Pilih jalur & sekolah
4. Preview hasil
5. Klik **Publikasikan**

> 📸 **Screenshot:** Halaman publikasi, preview hasil

---

## 11. Laporan & Export

### Lokasi Menu:
Sidebar → **Laporan**

### Jenis Laporan:
- Rekapitulasi per sekolah
- Daftar pendaftar
- Hasil seleksi
- Statistik

### Format: Excel, PDF, CSV

> 📸 **Screenshot:** Filter laporan, hasil export

---

## 12. Log Aktivitas

### Lokasi Menu:
Sidebar → **Log Aktivitas**

### Informasi: Waktu, User, Aktivitas, Deskripsi, IP Address

> 📸 **Screenshot:** Tabel log aktivitas

---

## Ringkasan Screenshot (18 Total)

| No | Halaman | Screenshot |
|----|---------|------------|
| 1-3 | Login | Form login, dashboard |
| 4-5 | Dashboard | Widget, grafik |
| 6 | Pengaturan | Form PPDB |
| 7-8 | Jadwal | Tabel, form edit |
| 9-11 | Sekolah | Tabel, form, peta |
| 12 | Kuota | Form kuota |
| 13-14 | Users | Tabel, form tambah |
| 15-16 | Verifikasi | Preview, form status |
| 17 | Seleksi | Hasil ranking |
| 18 | Laporan | Export data |

---

*Manual Super Admin v1.2.0 - PPDB SMP Kota Padang*

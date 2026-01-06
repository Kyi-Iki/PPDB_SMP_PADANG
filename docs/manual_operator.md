# 📗 Manual Book Operator Sekolah
## Sistem PPDB SMP Kota Padang

**Versi**: 1.2.0  
**Tanggal**: Januari 2026  
**Target Pengguna**: Operator Sekolah (per SMP)

---

## Daftar Isi

1. [Tentang Akun Operator](#1-tentang-akun-operator)
2. [Login Sistem](#2-login-sistem)
3. [Dashboard Sekolah](#3-dashboard-sekolah)
4. [Verifikasi Dokumen](#4-verifikasi-dokumen)
5. [Lihat Ranking Per Jalur](#5-lihat-ranking-per-jalur)
6. [Export Laporan](#6-export-laporan)

---

## 1. Tentang Akun Operator

### Apa itu Operator?
Operator adalah user yang ditugaskan untuk mengelola data pendaftaran di **satu sekolah tertentu**.

### Perbedaan dengan Super Admin:

| Fitur | Super Admin | Operator |
|-------|-------------|----------|
| Lihat semua sekolah | ✅ | ❌ |
| Kelola pengaturan PPDB | ✅ | ❌ |
| Kelola sekolah | ✅ | ❌ |
| Kelola user | ✅ | ❌ |
| Proses seleksi | ✅ | ❌ |
| Publikasi hasil | ✅ | ❌ |
| Verifikasi dokumen | ✅ Semua | ✅ Sekolah sendiri |
| Lihat ranking | ✅ Semua | ✅ Sekolah sendiri |
| Export laporan | ✅ Semua | ✅ Sekolah sendiri |

### Akun Operator:
- Dibuat oleh Super Admin
- Username: biasanya `operator_smpn[nomor]`
- Terikat ke satu sekolah

---

## 2. Login Sistem

### Langkah-langkah:
1. Buka browser dan akses: `http://localhost/ppdb-smp/admin/`
2. Masukkan kredensial yang diberikan oleh Dinas:
   - **Username**: `operator_smpn1` (contoh)
   - **Password**: `********`
3. Klik tombol **Login**

### Setelah Login:
- Anda akan diarahkan ke Dashboard
- Data yang tampil **hanya untuk sekolah Anda**
- Menu yang tersedia lebih terbatas dari Super Admin

> 📸 **Screenshot yang harus diambil:**
> - Halaman login
> - Dashboard operator (data sekolah sendiri)

---

## 3. Dashboard Sekolah

### Lokasi:
Otomatis tampil setelah login, atau klik **Dashboard** di sidebar

### Informasi yang Ditampilkan:

| Widget | Deskripsi |
|--------|-----------|
| **Total Pendaftar** | Jumlah pendaftar ke sekolah Anda |
| **Terverifikasi** | Yang sudah Anda verifikasi |
| **Menunggu** | Yang perlu diverifikasi |
| **Kuota Tersisa** | Sisa kuota per jalur |

### Grafik:
- Distribusi pendaftar per jalur ke sekolah Anda
- Timeline pendaftaran

> 📸 **Screenshot yang harus diambil:**
> - Dashboard dengan statistik sekolah
> - Grafik pendaftar

---

## 4. Verifikasi Dokumen

### Lokasi Menu:
Sidebar → **Verifikasi**

### Tugas Utama Operator:
Memeriksa dan memvalidasi dokumen siswa yang mendaftar ke sekolah Anda.

### Status Dokumen:

| Status | Warna | Deskripsi |
|--------|-------|-----------|
| `pending` | 🟡 Kuning | Belum diperiksa |
| `valid` | 🟢 Hijau | Dokumen diterima |
| `tidak_valid` | 🔴 Merah | Dokumen ditolak |

### Langkah Verifikasi:

#### Step 1: Lihat Daftar Pending
1. Klik **Verifikasi** di sidebar
2. Secara default menampilkan siswa dengan status **Pending**
3. Data yang tampil hanya siswa yang mendaftar ke sekolah Anda

#### Step 2: Buka Detail Siswa
1. Klik pada nama siswa
2. Lihat data biodata siswa
3. Lihat daftar dokumen yang diupload

#### Step 3: Preview Dokumen
1. Klik pada thumbnail dokumen
2. Dokumen akan terbuka di tab baru
3. Periksa keaslian dan kelengkapan

#### Step 4: Tentukan Status
1. Pilih status: **Valid** atau **Tidak Valid**
2. Jika **Tidak Valid**, wajib isi alasan di kolom catatan
3. Klik **Simpan**

### Checklist Verifikasi per Dokumen:

| Dokumen | Yang Harus Diperiksa |
|---------|---------------------|
| **Kartu Keluarga (KK)** | - Nama siswa tercantum<br>- NIK sesuai dengan yang diinput<br>- Alamat sesuai<br>- Dokumen tidak blur |
| **Ijazah/SKL** | - Nama sesuai<br>- Tahun kelulusan benar<br>- Ada cap/stempel sekolah |
| **Rapor** | - Nilai lengkap semester 1-5<br>- Nama sesuai<br>- Ada tanda tangan wali kelas |
| **Pas Foto** | - Formal (seragam/kemeja)<br>- Latar merah/biru<br>- Wajah terlihat jelas |
| **DTKS/SKTM** (Afirmasi) | - Nama siswa/ortu tercantum<br>- Masih berlaku<br>- Ada cap Kelurahan/Dinas |
| **Sertifikat** (Prestasi) | - Maks 3 tahun terakhir<br>- Ada legalisir/cap<br>- Tingkat minimal Kota |
| **Surat Tugas** (Mutasi) | - Orang tua yang pindah<br>- Tanggal berlaku<br>- Ada cap instansi |

### Tips Verifikasi:
- ✅ Jika ragu, konsultasikan dengan Super Admin
- ✅ Berikan catatan yang jelas jika menolak
- ✅ Siswa bisa upload ulang jika ditolak
- ✅ Verifikasi sebelum deadline seleksi

> 📸 **Screenshot yang harus diambil:**
> - Tabel daftar siswa pending
> - Detail dokumen siswa
> - Preview dokumen (KK, Ijazah)
> - Form verifikasi dengan dropdown status
> - Contoh catatan penolakan
> - Notifikasi sukses

---

## 5. Lihat Ranking Per Jalur

### Lokasi Menu:
Sidebar → **Seleksi** → **Ranking Per Jalur**

### Fungsi:
Melihat urutan ranking siswa yang mendaftar ke sekolah Anda setelah proses seleksi.

### Informasi yang Ditampilkan:

| Kolom | Deskripsi |
|-------|-----------|
| **Ranking** | Urutan berdasarkan kriteria jalur |
| **Nama Siswa** | Nama lengkap pendaftar |
| **NISN** | Nomor Induk Siswa Nasional |
| **Jalur** | Zonasi/Prestasi/Afirmasi/Mutasi |
| **Jarak** | Jarak rumah ke sekolah (km) |
| **Nilai** | Rata-rata nilai rapor |
| **Status** | lulus/tidak lulus/cadangan |

### Langkah Melihat Ranking:
1. Klik **Seleksi** → **Ranking Per Jalur**
2. Pilih **Jalur** dari dropdown (Zonasi/Prestasi/dll)
3. Data yang tampil adalah ranking ke sekolah Anda
4. Scroll untuk melihat semua peserta

### Filter Tambahan:
- **Status**: Lulus / Tidak Lulus / Cadangan
- **Urutan**: Ranking / Nama / NISN

> ⚠️ **Catatan**: Ranking baru tersedia setelah Super Admin menjalankan proses seleksi.

> 📸 **Screenshot yang harus diambil:**
> - Halaman ranking dengan dropdown jalur
> - Tabel ranking lengkap
> - Filter status

---

## 6. Export Laporan

### Lokasi Menu:
Sidebar → **Laporan**

### Jenis Laporan yang Tersedia:

| Laporan | Deskripsi |
|---------|-----------|
| **Daftar Pendaftar** | Semua siswa yang mendaftar ke sekolah Anda |
| **Status Verifikasi** | Rekapitulasi pending/valid/tidak valid |
| **Hasil Seleksi** | Daftar lulus dan tidak lulus |

### Format Export:
- **Excel (.xlsx)** - Untuk analisis lebih lanjut
- **PDF** - Untuk cetak dan arsip
- **CSV** - Data mentah

### Langkah Export:
1. Klik **Laporan**
2. Pilih jenis laporan
3. Pilih format (Excel/PDF/CSV)
4. Klik **Export**
5. File akan terdownload otomatis

### Contoh Penggunaan:
- Export daftar pendaftar untuk diarsipkan
- Print daftar siswa lulus untuk papan pengumuman
- Rekap verifikasi untuk laporan ke Dinas

> 📸 **Screenshot yang harus diambil:**
> - Halaman laporan
> - Pilihan format export
> - Preview data sebelum export

---

## Ringkasan Tugas Operator

### Checklist Harian:
- [ ] Login ke sistem
- [ ] Cek dashboard untuk pendaftar baru
- [ ] Verifikasi dokumen yang pending
- [ ] Logout setelah selesai

### Checklist Mingguan:
- [ ] Rekap status verifikasi
- [ ] Koordinasi dengan Dinas jika ada kendala
- [ ] Export laporan progress

### Checklist Sebelum Seleksi:
- [ ] Pastikan semua dokumen sudah diverifikasi
- [ ] Tidak ada yang masih pending
- [ ] Lapor ke Dinas jika sudah siap

---

## Troubleshooting

### Masalah Umum:

| Masalah | Solusi |
|---------|--------|
| Tidak bisa login | Hubungi Super Admin untuk reset password |
| Dokumen tidak bisa dibuka | Minta siswa upload ulang |
| Data siswa tidak muncul | Siswa mungkin pilih sekolah lain, atau filter salah |
| Tombol seleksi tidak ada | Hanya Super Admin yang bisa proses seleksi |

### Kontak Bantuan:
- **Super Admin**: Dinas Pendidikan Kota Padang
- **Telepon**: (0751) 123456
- **Email**: admin@ppdb-smp.sch.id

---

## Ringkasan Screenshot Operator (10 Total)

| No | Halaman | Screenshot |
|----|---------|------------|
| 1 | Login | Form login operator |
| 2 | Dashboard | Widget statistik sekolah |
| 3 | Dashboard | Grafik pendaftar |
| 4 | Verifikasi | Tabel siswa pending |
| 5 | Verifikasi | Detail dokumen siswa |
| 6 | Verifikasi | Preview dokumen |
| 7 | Verifikasi | Form status valid/tidak |
| 8 | Ranking | Tabel ranking per jalur |
| 9 | Laporan | Filter laporan |
| 10 | Laporan | Preview export |

---

*Manual Operator Sekolah v1.2.0 - PPDB SMP Kota Padang*

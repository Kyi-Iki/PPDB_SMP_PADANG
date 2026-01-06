# 📙 Manual Book Siswa & Orang Tua
## Sistem PPDB SMP Kota Padang

**Versi**: 1.2.0  
**Tanggal**: Januari 2026  
**Target Pengguna**: Calon Siswa SMP & Orang Tua

---

## Daftar Isi

1. [Persiapan Sebelum Mendaftar](#1-persiapan-sebelum-mendaftar)
2. [Registrasi Akun](#2-registrasi-akun)
3. [Login Portal Siswa](#3-login-portal-siswa)
4. [Dashboard Siswa](#4-dashboard-siswa)
5. [Mengisi Biodata](#5-mengisi-biodata)
6. [Menentukan Lokasi Rumah](#6-menentukan-lokasi-rumah)
7. [Upload Dokumen](#7-upload-dokumen)
8. [Memilih Jalur & Sekolah](#8-memilih-jalur--sekolah)
9. [Cek Status Pendaftaran](#9-cek-status-pendaftaran)
10. [Melihat Hasil Seleksi](#10-melihat-hasil-seleksi)
11. [Cetak Kartu Peserta](#11-cetak-kartu-peserta)
12. [Cetak Surat Kelulusan](#12-cetak-surat-kelulusan)
13. [FAQ - Pertanyaan Umum](#13-faq---pertanyaan-umum)

---

## 1. Persiapan Sebelum Mendaftar

### Dokumen yang Harus Disiapkan:

#### Dokumen Wajib (Semua Jalur):
| No | Dokumen | Format | Ukuran Max |
|----|---------|--------|------------|
| 1 | Kartu Keluarga (KK) | JPG/PNG/PDF | 2 MB |
| 2 | Ijazah SD / Surat Keterangan Lulus | JPG/PNG/PDF | 2 MB |
| 3 | Rapor Semester 1-5 | JPG/PNG/PDF | 2 MB |
| 4 | Pas Foto 3x4 (latar merah/biru) | JPG/PNG | 2 MB |

#### Dokumen Tambahan (Sesuai Jalur):

| Jalur | Dokumen Tambahan |
|-------|-----------------|
| **Afirmasi** | DTKS / SKTM / Kartu KIS / Kartu PKH / Surat Disabilitas / Surat Panti |
| **Prestasi** | Sertifikat Prestasi (maks 3 tahun terakhir) |
| **Mutasi** | Surat Tugas Pindah Orang Tua / Surat Pindah Domisili |
| **Zonasi** | Tidak ada (cukup dokumen wajib) |

### Data yang Harus Diketahui:
- NISN (Nomor Induk Siswa Nasional) - dari sekolah asal
- NIK (Nomor Induk Kependudukan) - dari KK
- Alamat lengkap sesuai KK
- Koordinat GPS rumah (bisa diambil otomatis)
- Nilai rapor semester 1-5

### Tips Persiapan:
- ✅ Scan/foto dokumen dengan cahaya cukup
- ✅ Pastikan tulisan terbaca jelas
- ✅ Simpan dalam format JPG, PNG, atau PDF
- ✅ Ukuran file tidak lebih dari 2 MB

---

## 2. Registrasi Akun

### Langkah-langkah:

#### Step 1: Buka Halaman Registrasi
1. Buka browser (Chrome/Firefox/Edge)
2. Akses: `http://localhost/ppdb-smp/siswa/register.php`
3. Atau dari halaman utama, klik **Daftar Sekarang**

#### Step 2: Isi Form Registrasi

| Field | Keterangan | Contoh |
|-------|------------|--------|
| **NISN** | 10 digit, dari sekolah asal | 1234567890 |
| **NIK** | 16 digit, dari KK | 1371071234567890 |
| **Nama Lengkap** | Sesuai Ijazah/KK | AHMAD FADLI |
| **Email** | Email aktif untuk notifikasi | ahmad@gmail.com |
| **No. HP** | Nomor yang bisa dihubungi | 08123456789 |
| **Password** | Minimal 6 karakter | ******** |
| **Konfirmasi Password** | Ulangi password | ******** |

#### Step 3: Submit
1. Periksa kembali semua data
2. Centang persetujuan
3. Klik **Daftar**

#### Step 4: Konfirmasi
- Jika berhasil, akan muncul notifikasi sukses
- Anda akan diarahkan ke halaman login

> ⚠️ **Penting**: 
> - NISN dan NIK tidak bisa diubah setelah registrasi
> - Ingat password Anda!

> 📸 **Screenshot yang harus diambil:**
> - Form registrasi kosong
> - Form registrasi terisi
> - Notifikasi sukses registrasi

---

## 3. Login Portal Siswa

### Langkah-langkah:
1. Akses: `http://localhost/ppdb-smp/siswa/login.php`
2. Masukkan:
   - **NISN**: 10 digit NISN Anda
   - **Password**: Password saat registrasi
3. Klik **Login**

### Lupa Password?
1. Klik link **Lupa Password**
2. Masukkan email yang didaftarkan
3. Cek email untuk link reset

> 📸 **Screenshot yang harus diambil:**
> - Halaman login siswa
> - Form login terisi

---

## 4. Dashboard Siswa

### Tampilan Setelah Login:

Setelah login, Anda akan melihat dashboard dengan:

| Bagian | Keterangan |
|--------|------------|
| **Progress Pendaftaran** | Persentase kelengkapan (0-100%) |
| **Checklist** | Biodata ✓ / Dokumen ✓ / Sekolah ✓ |
| **Quick Actions** | Tombol cepat ke setiap halaman |
| **Jadwal PPDB** | Timeline pendaftaran per jalur |
| **Status Pendaftaran** | Jika sudah mendaftar sekolah |

### Menu Sidebar:
- 🏠 Dashboard
- 👤 Biodata
- 📄 Upload Dokumen
- 🏫 Pilih Sekolah
- 🎫 Cetak Kartu
- 🏆 Hasil Seleksi
- 🔔 Notifikasi
- ⚙️ Profil

> 📸 **Screenshot yang harus diambil:**
> - Dashboard lengkap
> - Progress bar
> - Menu sidebar

---

## 5. Mengisi Biodata

### Lokasi:
Sidebar → **Biodata**

### Data yang Harus Diisi:

#### Data Pribadi:
| Field | Keterangan | Wajib |
|-------|------------|-------|
| NIK | 16 digit (sudah terisi) | ✅ |
| Nama Lengkap | Sudah terisi dari registrasi | ✅ |
| Tempat Lahir | Kota kelahiran | ✅ |
| Tanggal Lahir | Format: DD/MM/YYYY | ✅ |
| Jenis Kelamin | Laki-laki / Perempuan | ✅ |
| Agama | Islam/Kristen/Katolik/Hindu/Buddha/Konghucu | ✅ |
| No. Telepon | Nomor HP aktif | ✅ |
| Asal Sekolah | Nama SD asal | ✅ |

#### Data Alamat:
| Field | Keterangan | Wajib |
|-------|------------|-------|
| Alamat Lengkap | Nama jalan, nomor, gang | ✅ |
| RT/RW | Contoh: 001/002 | ✅ |
| Kelurahan | Nama kelurahan | ✅ |
| Kecamatan | Pilih dari dropdown | ✅ |
| Kota | Default: Padang | ✅ |
| Provinsi | Default: Sumatera Barat | ✅ |
| Kode Pos | 5 digit | |
| **Latitude** | Koordinat GPS (wajib!) | ✅ |
| **Longitude** | Koordinat GPS (wajib!) | ✅ |

#### Data Orang Tua:
| Field | Keterangan | Wajib |
|-------|------------|-------|
| Nama Ayah | Nama lengkap | ✅ |
| NIK Ayah | 16 digit | |
| Pekerjaan Ayah | Pilih dari dropdown | ✅ |
| Penghasilan Ayah | Range penghasilan | |
| Nama Ibu | Nama lengkap | ✅ |
| NIK Ibu | 16 digit | |
| Pekerjaan Ibu | Pilih dari dropdown | ✅ |
| Penghasilan Ibu | Range penghasilan | |
| No. Telepon Ortu | Yang bisa dihubungi | ✅ |

### Langkah Mengisi:
1. Klik **Biodata** di sidebar
2. Isi semua field yang bertanda *
3. Untuk koordinat GPS, lihat bagian berikutnya
4. Klik **Simpan Biodata**

> 📸 **Screenshot yang harus diambil:**
> - Form data pribadi
> - Dropdown kecamatan
> - Form data orang tua
> - Notifikasi berhasil simpan

---

## 6. Menentukan Lokasi Rumah

### Mengapa Penting?
Koordinat GPS rumah digunakan untuk menghitung **jarak ke sekolah** pada jalur **Zonasi**. Semakin dekat, semakin tinggi peluang diterima.

### Cara Mengambil Lokasi:

#### Opsi 1: Otomatis dari GPS (Disarankan)
1. Di halaman Biodata, scroll ke bagian alamat
2. Klik tombol **📍 Ambil Lokasi GPS Otomatis**
3. Browser akan meminta izin lokasi → Klik **Izinkan**
4. Tunggu beberapa detik
5. Latitude dan Longitude akan terisi otomatis

#### Opsi 2: Pilih dari Peta
1. Klik tombol **🗺️ Pilih dari Peta**
2. Peta akan muncul
3. Geser peta ke lokasi rumah Anda
4. Klik pada lokasi rumah
5. Marker akan muncul dan koordinat terisi

#### Tips Akurasi GPS:
- ✅ Gunakan di lokasi rumah actual
- ✅ Pastikan GPS HP/laptop aktif
- ✅ Tunggu hingga akurasi < 50 meter
- ❌ Jangan ambil lokasi dari tempat lain!

> ⚠️ **Peringatan**: Lokasi yang tidak sesuai dapat menyebabkan **diskualifikasi** saat verifikasi.

> 📸 **Screenshot yang harus diambil:**
> - Tombol ambil lokasi GPS
> - Peta dengan marker lokasi
> - Field latitude/longitude terisi

---

## 7. Upload Dokumen

### Lokasi:
Sidebar → **Upload Dokumen**

### Langkah Upload:

#### Step 1: Pilih Kategori
- **Dokumen Umum** (wajib semua jalur)
- **Dokumen Afirmasi** (jika pilih jalur Afirmasi)
- **Dokumen Prestasi** (jika pilih jalur Prestasi)
- **Dokumen Mutasi** (jika pilih jalur Mutasi)

#### Step 2: Upload File
1. Klik **Pilih File** atau **Browse**
2. Pilih file dari komputer (JPG/PNG/PDF, max 2MB)
3. File akan ter-select

#### Step 3: Simpan
1. Setelah semua dokumen dalam kategori dipilih
2. Klik **Simpan Semua Dokumen [Kategori]**
3. Tunggu proses upload

### Status Dokumen:

| Status | Warna | Arti |
|--------|-------|------|
| **Belum Upload** | ⚪ Abu-abu | File belum diupload |
| **Pending** | 🟡 Kuning | Menunggu verifikasi operator |
| **Valid** | 🟢 Hijau | Dokumen diterima |
| **Tidak Valid** | 🔴 Merah | Dokumen ditolak, upload ulang |

### Jika Dokumen Ditolak:
1. Lihat alasan penolakan di catatan
2. Siapkan dokumen yang benar
3. Upload ulang file baru
4. Status akan kembali ke **Pending**

> 📸 **Screenshot yang harus diambil:**
> - Halaman upload dokumen
> - Kategori dokumen umum
> - Kategori dokumen jalur khusus
> - File yang sudah diupload
> - Status dokumen (pending/valid)

---

## 8. Memilih Jalur & Sekolah

### Lokasi:
Sidebar → **Pilih Sekolah**

### Tahapan Pemilihan:

#### Step 1: Pilih Jalur PPDB
Pilih **SATU** jalur yang sesuai:

| Jalur | Kriteria | Kuota |
|-------|----------|-------|
| **Zonasi** | Berdasarkan jarak rumah ke sekolah | 50% |
| **Prestasi** | Berdasarkan nilai rapor | 25% |
| **Afirmasi** | Keluarga tidak mampu / disabilitas | 15% |
| **Mutasi** | Pindahan dari luar daerah | 10% |

#### Step 2: Pilih Sekolah
1. **Pilihan 1** (Wajib): Sekolah utama yang diinginkan
2. **Pilihan 2** (Opsional): Sekolah cadangan

> 💡 **Tips**: 
> - Lihat jarak rumah ke sekolah di peta
> - Lihat sisa kuota sekolah
> - Pilih sekolah yang realistis sesuai jalur

#### Step 3: Isi Nilai Rapor (untuk Prestasi)
Jika memilih jalur **Prestasi**, isi nilai:
- Semester 1: ___
- Semester 2: ___
- Semester 3: ___
- Semester 4: ___
- Semester 5: ___

#### Step 4: Konfirmasi
1. Periksa kembali semua pilihan
2. Klik **Daftar Sekarang**
3. Konfirmasi pilihan

### Setelah Mendaftar:
- Anda akan mendapat **Nomor Pendaftaran**
- Status: **Menunggu Verifikasi**
- **Tidak bisa mengubah pilihan** setelah submit

> ⚠️ **Penting**: Pastikan semua dokumen sudah diupload sebelum memilih sekolah!

> 📸 **Screenshot yang harus diambil:**
> - Halaman pilih jalur
> - Peta sekolah
> - Form pilih sekolah 1 dan 2
> - Form nilai rapor (untuk Prestasi)
> - Konfirmasi pendaftaran
> - Nomor pendaftaran

---

## 9. Cek Status Pendaftaran

### Lokasi:
Sidebar → **Dashboard** atau **Pilih Sekolah**

### Status yang Mungkin:

| Status | Arti | Aksi |
|--------|------|------|
| **Menunggu Verifikasi** | Dokumen sedang diperiksa | Tunggu |
| **Terverifikasi** | Dokumen sudah valid | Tunggu seleksi |
| **Ditolak** | Ada dokumen tidak valid | Upload ulang |
| **Menunggu Seleksi** | Proses seleksi belum dimulai | Tunggu |
| **Hasil Tersedia** | Seleksi selesai | Lihat hasil |

### Notifikasi:
- Cek menu **🔔 Notifikasi** untuk update terbaru
- Notifikasi otomatis saat status berubah

> 📸 **Screenshot yang harus diambil:**
> - Status pendaftaran di dashboard
> - Halaman notifikasi

---

## 10. Melihat Hasil Seleksi

### Lokasi:
Sidebar → **Hasil Seleksi**

### Hasil yang Mungkin:

| Status | Warna | Arti |
|--------|-------|------|
| **🎉 LULUS** | 🟢 Hijau | Selamat! Anda diterima |
| **Tidak Lulus** | 🔴 Merah | Tidak diterima di sekolah pilihan |
| **Cadangan** | 🟡 Kuning | Masuk daftar tunggu |

### Informasi yang Ditampilkan:
- Status kelulusan
- Sekolah yang diterima (Pilihan 1 atau 2)
- Peringkat/Ranking
- Skor akhir

### Jika Lulus:
1. Cetak Surat Kelulusan
2. Siapkan dokumen asli untuk daftar ulang
3. Datang ke sekolah sesuai jadwal daftar ulang

### Jika Tidak Lulus:
- Informasi lanjutan akan diberikan oleh Dinas
- Tetap simpan bukti pendaftaran

> 📸 **Screenshot yang harus diambil:**
> - Halaman hasil seleksi
> - Detail kelulusan (jika lulus)
> - Ranking dan skor

---

## 11. Cetak Kartu Peserta

### Lokasi:
Sidebar → **Cetak Kartu**

### Prasyarat:
- Sudah mendaftar ke sekolah
- Status minimal: **Terverifikasi**

### Isi Kartu Peserta:
- Nomor Pendaftaran
- Nama Siswa
- NISN
- Sekolah Pilihan
- Jalur PPDB
- Barcode/QR Code

### Langkah Cetak:
1. Klik **Cetak Kartu** di sidebar
2. Klik tombol **🖨️ Cetak Kartu**
3. Pilih printer atau simpan sebagai PDF
4. Print di kertas A4

### Kegunaan Kartu:
- Bukti resmi pendaftaran
- Dibawa saat daftar ulang
- Untuk verifikasi identitas

> 📸 **Screenshot yang harus diambil:**
> - Preview kartu peserta
> - Tombol cetak

---

## 12. Cetak Surat Kelulusan

### Lokasi:
Sidebar → **Hasil Seleksi** → Tombol **Cetak**

### Prasyarat:
- Status: **LULUS**
- Hasil sudah dipublikasikan

### Isi Surat Kelulusan:
- Kop Surat Dinas Pendidikan
- Nama Siswa
- NISN
- Sekolah Diterima
- Jalur PPDB
- Ranking
- Tanda tangan elektronik

### Langkah Cetak:
1. Buka halaman **Hasil Seleksi**
2. Klik tombol **🖨️ Cetak Surat Kelulusan**
3. Simpan sebagai PDF atau print langsung
4. Bawa saat daftar ulang

> 📸 **Screenshot yang harus diambil:**
> - Preview surat kelulusan
> - Tombol cetak

---

## 13. FAQ - Pertanyaan Umum

### Registrasi & Login

**Q: Saya lupa password, bagaimana?**
> A: Klik "Lupa Password" di halaman login, masukkan email, dan cek email untuk reset.

**Q: NISN saya salah saat registrasi, bisa diubah?**
> A: Tidak bisa diubah sendiri. Hubungi panitia PPDB untuk koreksi.

### Dokumen

**Q: Dokumen saya ditolak, apa yang harus dilakukan?**
> A: Lihat alasan penolakan, siapkan dokumen yang benar, dan upload ulang.

**Q: Format file apa yang diterima?**
> A: JPG, JPEG, PNG, atau PDF. Maksimal 2 MB per file.

### Pendaftaran

**Q: Bisakah saya mengubah pilihan sekolah?**
> A: Tidak bisa setelah submit. Pastikan benar sebelum klik Daftar.

**Q: Bisakah saya mendaftar di lebih dari 1 jalur?**
> A: Tidak bisa. Sistem serentak = 1 siswa hanya boleh 1 jalur.

**Q: Saya tidak punya dokumen afirmasi, apakah bisa pilih jalur Afirmasi?**
> A: Bisa mendaftar, tapi tanpa dokumen valid Anda tidak akan masuk ranking saat seleksi.

### Hasil Seleksi

**Q: Kapan hasil diumumkan?**
> A: Lihat jadwal di halaman utama atau dashboard. Tanggal bisa berbeda per jalur.

**Q: Saya tidak lulus, apa yang bisa saya lakukan?**
> A: Tunggu informasi lanjutan dari Dinas Pendidikan.

### Teknis

**Q: Website tidak bisa diakses?**
> A: Coba browser lain, clear cache, atau hubungi panitia.

**Q: GPS tidak bisa diambil?**
> A: Pastikan izin lokasi browser diaktifkan. Atau gunakan opsi pilih dari peta.

---

## Ringkasan Screenshot Siswa (20 Total)

| No | Halaman | Screenshot |
|----|---------|------------|
| 1 | Registrasi | Form registrasi |
| 2 | Login | Form login siswa |
| 3 | Dashboard | Tampilan lengkap |
| 4-6 | Biodata | Data pribadi, alamat, ortu |
| 7-8 | Lokasi | Tombol GPS, peta |
| 9-11 | Dokumen | Upload, kategori, status |
| 12-14 | Pilih Sekolah | Jalur, peta, form |
| 15 | Status | Progress pendaftaran |
| 16 | Notifikasi | Daftar notifikasi |
| 17-18 | Hasil | Status lulus/tidak |
| 19 | Cetak Kartu | Preview kartu |
| 20 | Cetak Surat | Preview surat lulus |

---

## Kontak Bantuan

| Kanal | Kontak |
|-------|--------|
| **Telepon** | (0751) 123456 |
| **WhatsApp** | 08xx-xxxx-xxxx |
| **Email** | info@ppdb-smp.sch.id |
| **Website** | ppdb-smp.sch.id |

**Jam Layanan**: Senin - Jumat, 08.00 - 16.00 WIB

---

*Manual Siswa & Orang Tua v1.2.0 - PPDB SMP Kota Padang*

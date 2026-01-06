# 🎬 Script Video Tutorial PPDB SMP
## Panduan Lengkap dalam Satu Video

**Durasi Total**: ~15-20 menit  
**Format**: MP4, 1080p  
**Target**: Semua pengguna (Siswa, Operator, Admin)

---

# SATU VIDEO LENGKAP: Tutorial Sistem PPDB SMP Kota Padang

## BAGIAN 1: PENGENALAN SISTEM (0:00 - 3:00)

### 1.1 Opening & Tampilan Utama (0:00 - 0:30)

**Tampilan**: Landing page PPDB

**Narasi**:
> "Selamat datang di Video Tutorial Sistem PPDB SMP Kota Padang.
> 
> Inilah tampilan halaman utama sistem PPDB.
> 
> Di bagian atas terdapat menu navigasi: Tentang, Jadwal, Jalur PPDB, Sekolah, dan tombol Login.
> 
> Di bagian hero terlihat status pendaftaran apakah sedang dibuka atau ditutup, serta tombol untuk mendaftar."

**Aksi**:
- Buka http://localhost/ppdb-smp/
- Tunjukkan menu navigasi
- Tunjukkan badge status pendaftaran
- Tunjukkan tombol Daftar dan Login

📸 **Screenshot**: Landing page dengan hero section

---

### 1.2 Statistik Real-Time (0:30 - 1:00)

**Tampilan**: Section statistik

**Narasi**:
> "Di bawah hero section, terdapat 4 kartu statistik real-time:
> 
> SATU, Total Pendaftar - menunjukkan jumlah siswa yang sudah mendaftar.
> 
> DUA, Siswa Diterima - jumlah siswa yang sudah lulus seleksi.
> 
> TIGA, Sekolah Tersedia - total 20 SMP Negeri yang bisa dipilih.
> 
> EMPAT, Total Kuota - jumlah kursi yang tersedia untuk tahun ini.
> 
> Data ini diupdate otomatis sesuai kondisi terkini."

**Aksi**:
- Scroll ke section statistik
- Tunjukkan masing-masing kartu stat
- Pause di setiap angka

📸 **Screenshot**: Kartu statistik

---

### 1.3 Peta Zonasi Interaktif (1:00 - 1:45)

**Tampilan**: Peta dengan polygon kecamatan dan marker sekolah

**Narasi**:
> "Fitur unggulan sistem ini adalah Peta Zonasi Interaktif.
> 
> Peta menampilkan 11 kecamatan di Kota Padang dengan warna berbeda sebagai area polygon.
> 
> Setiap marker biru menunjukkan lokasi SMP Negeri.
> 
> Klik pada marker untuk melihat detail sekolah: nama, alamat, dan kuota.
> 
> Di sisi kanan peta ada panel yang menampilkan jumlah sekolah dan kecamatan.
> 
> Anda bisa menggunakan kontrol di kiri bawah untuk:
> Filter berdasarkan kecamatan.
> Ambil lokasi GPS rumah Anda secara otomatis.
> 
> Fitur ini membantu siswa dan orang tua menentukan sekolah terdekat untuk jalur Zonasi."

**Aksi**:
- Tunjukkan peta dengan polygon berwarna
- Klik marker sekolah, tunjukkan popup info
- Pilih filter kecamatan
- Klik tombol lokasi GPS

📸 **Screenshot**: Peta zonasi dengan popup sekolah

---

### 1.4 Jadwal Pendaftaran Per Jalur (1:45 - 2:15)

**Tampilan**: Section jadwal

**Narasi**:
> "Scroll ke bawah untuk melihat Jadwal Pendaftaran.
> 
> Setiap jalur PPDB memiliki periode pendaftaran berbeda:
> Zonasi, Prestasi, Afirmasi, dan Mutasi.
> 
> Informasi yang ditampilkan meliputi:
> Tanggal buka dan tutup pendaftaran.
> Tanggal seleksi.
> Tanggal pengumuman hasil.
> 
> Badge status menunjukkan apakah jalur sedang Dibuka, Ditutup, atau Selesai."

**Aksi**:
- Scroll ke section jadwal
- Tunjukkan setiap card jalur dengan tanggal
- Highlight badge status

📸 **Screenshot**: Jadwal per jalur

---

### 1.5 Informasi 4 Jalur PPDB (2:15 - 2:45)

**Tampilan**: Section jalur PPDB

**Narasi**:
> "Sistem menyediakan 4 jalur pendaftaran:
> 
> ZONASI - kuota 50 persen, seleksi berdasarkan jarak rumah ke sekolah.
> Semakin dekat semakin prioritas.
> 
> PRESTASI - kuota 25 persen, seleksi berdasarkan nilai rapor.
> Semakin tinggi nilai semakin prioritas.
> 
> AFIRMASI - kuota 15 persen, untuk keluarga tidak mampu atau disabilitas.
> Membutuhkan dokumen DTKS atau sejenis.
> 
> MUTASI - kuota 10 persen, untuk pindahan dari luar daerah.
> Membutuhkan surat tugas pindah orang tua."

**Aksi**:
- Tunjukkan 4 card jalur dengan ikon
- Jelaskan kuota masing-masing

📸 **Screenshot**: 4 jalur PPDB

---

### 1.6 Daftar & Filter Sekolah (2:45 - 3:00)

**Tampilan**: Section daftar sekolah

**Narasi**:
> "Di bagian bawah, terdapat daftar lengkap 20 sekolah dengan fitur pencarian dan filter.
> 
> Ketik nama sekolah di kotak pencarian untuk mencari.
> Atau pilih kecamatan dari dropdown untuk memfilter.
> 
> Setiap card sekolah menampilkan: foto, nama, alamat, dan akreditasi."

**Aksi**:
- Scroll ke section sekolah
- Ketik pencarian
- Pilih filter kecamatan
- Tunjukkan card sekolah

📸 **Screenshot**: Daftar sekolah dengan filter

---

## BAGIAN 2: TUTORIAL SISWA (3:00 - 10:00)

### 2.1 Registrasi Akun (3:00 - 4:00)

**Tampilan**: Halaman registrasi siswa

**Narasi**:
> "Sekarang kita masuk ke proses pendaftaran.
> 
> Langkah pertama adalah registrasi akun.
> 
> Buka halaman pendaftaran siswa dengan klik tombol Daftar Sekarang di halaman utama.
> 
> Siapkan NISN 10 digit dari sekolah asal dan NIK 16 digit dari Kartu Keluarga.
> 
> Isi form registrasi: NISN, NIK, nama lengkap, email aktif, nomor HP, dan password minimal 6 karakter.
> 
> Setelah semua terisi, klik Daftar."

**Aksi**:
- Buka http://localhost/ppdb-smp/siswa/register.php
- Isi form registrasi
- Klik Daftar, tunggu konfirmasi

📸 **Screenshot**: Form registrasi

---

### 2.2 Login & Dashboard (4:00 - 4:30)

**Tampilan**: Login dan Dashboard siswa

**Narasi**:
> "Login dengan NISN dan password yang sudah dibuat.
> 
> Setelah login, Anda masuk ke Dashboard.
> Di sini terlihat progress pendaftaran dan checklist langkah yang harus diselesaikan."

**Aksi**:
- Login ke http://localhost/ppdb-smp/siswa/login.php
- Tunjukkan dashboard dan progress bar

📸 **Screenshot**: Dashboard siswa

---

### 2.3 Mengisi Biodata (2:30 - 4:00)

**Tampilan**: Halaman Biodata

**Narasi**:
> "Klik menu Biodata di sidebar.
> 
> Isi data pribadi: tempat tanggal lahir, jenis kelamin, agama, dan asal sekolah.
> 
> Isi data alamat: alamat lengkap, RT/RW, kelurahan, dan pilih kecamatan dari dropdown.
> 
> PENTING: Lokasi GPS wajib diisi. Klik tombol Ambil Lokasi GPS atau Pilih dari Peta.
> Pastikan lokasi yang diambil adalah lokasi rumah yang sebenarnya karena ini menentukan jarak untuk jalur Zonasi.
> 
> Terakhir, isi data orang tua.
> 
> Klik Simpan Biodata."

**Aksi**:
- Isi semua field biodata
- Klik tombol GPS, tunjukkan marker di peta
- Isi data orang tua
- Klik Simpan

📸 **Screenshot**: Form biodata dengan GPS

---

### 2.4 Upload Dokumen (4:00 - 5:30)

**Tampilan**: Halaman Upload Dokumen

**Narasi**:
> "Klik menu Upload Dokumen.
> 
> Ada 2 kategori: Dokumen Umum yang wajib untuk semua jalur, dan Dokumen Khusus sesuai jalur yang dipilih.
> 
> Untuk Dokumen Umum: upload Kartu Keluarga, Ijazah atau SKL, Rapor, dan Pas Foto.
> 
> Format file: JPG, PNG, atau PDF. Maksimal 2 MB per file.
> 
> Setelah semua file dipilih, klik Simpan Dokumen.
> 
> Status dokumen akan berubah menjadi Pending, menunggu verifikasi operator."

**Aksi**:
- Tunjukkan kategori dokumen
- Upload beberapa file
- Klik Simpan

📸 **Screenshot**: Halaman upload dokumen

---

### 2.5 Pilih Jalur & Sekolah (5:30 - 7:00)

**Tampilan**: Halaman Pilih Sekolah

**Narasi**:
> "Langkah terpenting: memilih jalur dan sekolah.
> 
> Pilih SATU jalur pendaftaran:
> - Zonasi: berdasarkan jarak terdekat, kuota 50%
> - Prestasi: berdasarkan nilai rapor, kuota 25%
> - Afirmasi: untuk keluarga tidak mampu, kuota 15%
> - Mutasi: untuk pindahan luar daerah, kuota 10%
> 
> Setelah pilih jalur, pilih sekolah dari peta atau dropdown.
> Anda bisa memilih 2 sekolah: Pilihan 1 sebagai utama dan Pilihan 2 sebagai cadangan.
> 
> PERHATIAN: Setelah submit, pilihan tidak bisa diubah.
> 
> Jika sudah yakin, klik Daftar Sekarang."

**Aksi**:
- Pilih jalur
- Pilih sekolah di peta
- Klik Daftar, konfirmasi
- Tunjukkan nomor pendaftaran

📸 **Screenshot**: Halaman pilih sekolah dengan peta

---

### 2.6 Cek Hasil & Cetak (7:00 - 8:00)

**Tampilan**: Halaman Hasil dan Cetak

**Narasi**:
> "Setelah proses seleksi selesai, cek menu Hasil Seleksi.
> 
> Jika Anda lulus, akan muncul status hijau dengan sekolah tujuan.
> 
> Cetak Kartu Peserta dari menu Cetak Kartu.
> Jika lulus, cetak juga Surat Kelulusan.
> 
> Bawa dokumen ini saat daftar ulang ke sekolah."

**Aksi**:
- Buka menu Hasil Seleksi
- Tunjukkan kartu peserta
- Preview cetak

📸 **Screenshot**: Hasil seleksi dan kartu peserta

---

## BAGIAN 3: TUTORIAL OPERATOR (8:00 - 11:00)

### 3.1 Login Operator (8:00 - 8:30)

**Tampilan**: Login admin

**Narasi**:
> "Sekarang kita masuk ke sisi Operator Sekolah.
> 
> Login dengan akun operator yang diberikan Dinas.
> 
> Operator hanya bisa melihat data untuk sekolah sendiri."

**Aksi**:
- Login sebagai operator
- Tunjukkan dashboard

📸 **Screenshot**: Dashboard operator

---

### 3.2 Verifikasi Dokumen (8:30 - 10:00)

**Tampilan**: Menu Verifikasi

**Narasi**:
> "Tugas utama Operator adalah verifikasi dokumen.
> 
> Klik menu Verifikasi.
> Anda akan melihat daftar siswa yang mendaftar ke sekolah Anda.
> 
> Klik nama siswa untuk melihat detail.
> Preview setiap dokumen yang diupload.
> 
> Periksa dengan teliti:
> - Kartu Keluarga: nama siswa tercantum, NIK sesuai
> - Ijazah: nama sesuai, tahun kelulusan benar
> - Rapor: nilai lengkap
> - Foto: formal, latar merah/biru
> 
> Pilih status Valid jika dokumen benar.
> Pilih Tidak Valid jika ada masalah, dan isi catatan penolakan.
> 
> Klik Simpan."

**Aksi**:
- Buka verifikasi, pilih siswa
- Preview dokumen
- Set status valid
- Simpan

📸 **Screenshot**: Proses verifikasi dokumen

---

### 3.3 Lihat Ranking (10:00 - 11:00)

**Tampilan**: Menu Ranking Per Jalur

**Narasi**:
> "Setelah seleksi dijalankan oleh Dinas, Anda bisa melihat ranking.
> 
> Klik Seleksi, lalu Ranking Per Jalur.
> Pilih jalur untuk melihat peringkat siswa.
> 
> Gunakan data ini untuk persiapan daftar ulang."

**Aksi**:
- Buka Ranking Per Jalur
- Pilih jalur Zonasi
- Tunjukkan tabel ranking

📸 **Screenshot**: Ranking per jalur

---

## BAGIAN 4: TUTORIAL SUPER ADMIN (11:00 - 15:00)

### 4.1 Dashboard & Statistik (11:00 - 11:30)

**Tampilan**: Dashboard Admin

**Narasi**:
> "Super Admin memiliki akses penuh ke semua fitur.
> 
> Dashboard menampilkan statistik: total pendaftar, terverifikasi, dan distribusi per jalur."

**Aksi**:
- Login sebagai admin
- Tunjukkan widget statistik dan grafik

📸 **Screenshot**: Dashboard admin

---

### 4.2 Pengaturan PPDB (11:30 - 12:00)

**Tampilan**: Menu Pengaturan

**Narasi**:
> "Di menu Pengaturan, atur periode PPDB: tanggal mulai, akhir, seleksi, dan pengumuman.
> 
> Status PPDB menentukan fitur yang aktif: pendaftaran, seleksi, atau pengumuman."

**Aksi**:
- Buka pengaturan, tunjukkan form

📸 **Screenshot**: Pengaturan PPDB

---

### 4.3 Kelola Sekolah (12:00 - 12:30)

**Tampilan**: Menu Sekolah

**Narasi**:
> "Di menu Sekolah, kelola data semua SMP.
> 
> Tambah sekolah baru, edit data, atau set lokasi di peta."

**Aksi**:
- Buka daftar sekolah
- Tunjukkan form edit dengan peta

📸 **Screenshot**: Form sekolah dengan peta

---

### 4.4 Proses Seleksi (12:30 - 14:00)

**Tampilan**: Menu Proses Seleksi

**Narasi**:
> "Proses seleksi hanya bisa dilakukan saat status PPDB adalah Seleksi.
> 
> Ubah status di Pengaturan terlebih dahulu.
> 
> Lalu buka Seleksi, Proses Seleksi.
> Pilih jalur yang akan diproses.
> 
> Klik Mulai Seleksi Global.
> Sistem akan menghitung ranking berdasarkan kriteria masing-masing jalur:
> - Zonasi: jarak terdekat
> - Prestasi: nilai tertinggi
> 
> Setelah selesai, lihat hasilnya di Ranking Per Jalur."

**Aksi**:
- Ubah status ke Seleksi
- Jalankan proses seleksi
- Lihat hasil ranking

📸 **Screenshot**: Proses dan hasil seleksi

---

### 4.5 Publikasi Hasil (14:00 - 15:00)

**Tampilan**: Menu Publikasi

**Narasi**:
> "Langkah terakhir adalah publikasi hasil.
> 
> Ubah status PPDB menjadi Pengumuman.
> 
> Buka Seleksi, Publikasi.
> Pilih jalur dan sekolah, lalu klik Publikasikan.
> 
> Setelah dipublikasi, siswa bisa melihat hasil di portal mereka."

**Aksi**:
- Ubah status ke Pengumuman
- Publikasikan hasil

📸 **Screenshot**: Publikasi hasil

---

## BAGIAN 5: PENUTUP (15:00 - 15:30)

**Tampilan**: Landing page

**Narasi**:
> "Itulah tutorial lengkap Sistem PPDB SMP Kota Padang.
> 
> Untuk siswa: daftar, isi biodata, upload dokumen, pilih sekolah.
> Untuk operator: verifikasi dokumen siswa.
> Untuk admin: proses seleksi dan publikasi hasil.
> 
> Jika ada pertanyaan, hubungi Dinas Pendidikan Kota Padang.
> 
> Terima kasih telah menonton."

**Aksi**:
- Tampilkan info kontak
- Fade out

---

## RINGKASAN SCREENSHOT (13 Total)

| No | Bagian | Screenshot |
|----|--------|------------|
| 1 | Intro | Landing page dengan peta |
| 2 | Siswa | Form registrasi |
| 3 | Siswa | Dashboard siswa |
| 4 | Siswa | Form biodata dengan GPS |
| 5 | Siswa | Upload dokumen |
| 6 | Siswa | Pilih sekolah dengan peta |
| 7 | Siswa | Hasil seleksi & kartu |
| 8 | Operator | Dashboard operator |
| 9 | Operator | Proses verifikasi |
| 10 | Operator | Ranking per jalur |
| 11 | Admin | Dashboard admin |
| 12 | Admin | Proses seleksi |
| 13 | Admin | Publikasi hasil |

---

## TIPS PEREKAMAN

1. **Resolusi**: 1920x1080 untuk HD
2. **Zoom browser**: 100%
3. **Gerakkan mouse perlahan** agar viewer bisa mengikuti
4. **Pause 2-3 detik** setelah klik sebelum narasi
5. **Siapkan data demo** sebelum rekam
6. **Rekam sekali jalan** per bagian, edit jika perlu

---

*Script Video Tutorial PPDB SMP - Versi Satu Video Lengkap*

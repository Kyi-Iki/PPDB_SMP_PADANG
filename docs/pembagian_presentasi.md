# 📊 NASKAH PRESENTASI DEMO LANGSUNG
## Sistem PPDB SMP | 7 Orang | ~45 menit

---

# PEMBAGIAN DEMO

| Urutan | Presenter | Bagian Demo | Durasi |
|--------|-----------|-------------|--------|
| 1 | Moderator | Pembukaan | 2 menit |
| 2 | Presenter 1 | Landing Page & Penjelasan Umum | 5 menit |
| 3 | Presenter 2 | Registrasi & Login Siswa | 5 menit |
| 4 | Presenter 3 | Biodata, Dokumen & Pilih Sekolah | 6 menit |
| 5 | Presenter 4 | Dashboard Admin & Verifikasi | 6 menit |
| 6 | Presenter 5 | Proses Seleksi & Publikasi | 5 menit |
| 7 | Presenter 6 | Peta Interaktif & Kesimpulan | 5 menit |
| - | Moderator | Penutup & Tanya Jawab | 10 menit |

---

# MODERATOR - PEMBUKAAN

**[Buka browser ke localhost/ppdb-smp]**

> "Assalamualaikum warahmatullahi wabarakatuh.
>
> Bapak/Ibu Dosen, perkenalkan kami dari Kelompok [X]. Hari ini kami akan mempresentasikan **Sistem Informasi PPDB SMP Berbasis Web**.
>
> Berbeda dengan presentasi biasa, kami akan langsung **mendemokan sistem** sambil menjelaskan fitur-fiturnya.
>
> Perkenalkan anggota kami: [Sebutkan 6 nama].
>
> Langsung saja, [Nama 1] akan memulai dari halaman utama."

---

# PRESENTER 1 - LANDING PAGE & PENJELASAN UMUM
## Halaman: localhost/ppdb-smp/index.php

**[Sudah terbuka halaman landing page]**

> "Terima kasih. Ini adalah halaman utama sistem PPDB kami.
>
> **[Tunjuk hero section]**
> Di bagian atas, pengunjung bisa melihat status PPDB saat ini - apakah pendaftaran sudah dibuka atau belum. Status ini dikontrol oleh admin.
>
> **[Scroll ke statistik]**
> Di sini ada statistik real-time: jumlah pendaftar, siswa lulus, sekolah, dan total kuota.
>
> **[Scroll ke section jadwal]**
> Ini jadwal pendaftaran per jalur. Sistem kami mendukung 4 jalur:
> - **Zonasi** - berdasarkan jarak rumah ke sekolah
> - **Prestasi** - berdasarkan nilai akademik
> - **Afirmasi** - untuk keluarga tidak mampu
> - **Mutasi** - untuk pindahan dari luar daerah
>
> Pembagian kuota mengikuti aturan yaitu Zonasi 45%, Prestasi 27%, Afirmasi 23%, dan Mutasi 5%.
>
> **[Scroll ke daftar sekolah]**
> Di bawah sini ada daftar 20 SMP Negeri di Kota Padang lengkap dengan kuota masing-masing.
>
> Sekarang [Nama 2] akan menunjukkan proses registrasi siswa."

---

# PRESENTER 2 - REGISTRASI & LOGIN SISWA
## Halaman: /auth/register.php → /auth/login.php → /siswa/index.php

**[Klik tombol Login di navbar, lalu klik "Daftar di sini"]**

> "Untuk mendaftar, siswa mengakses halaman registrasi ini.
>
> **[Tunjukkan form registrasi]**
> Siswa hanya perlu memasukkan:
> - **NISN** - 10 digit, divalidasi otomatis
> - **NIK** - 16 digit, divalidasi otomatis
> - **Password** - minimal 6 karakter
>
> Sistem akan menolak jika format tidak sesuai.
>
> **[Klik Login, masukkan akun siswa demo]**
> Setelah registrasi, siswa login dengan NISN dan password.
>
> **[Setelah login, tunjukkan dashboard siswa]**
> Ini dashboard siswa. Ada progress checklist di kiri yang menunjukkan langkah mana yang sudah dan belum selesai:
> - Isi Biodata
> - Upload Dokumen
> - Pilih Jalur & Sekolah
>
> Siswa harus menyelesaikan semuanya untuk bisa mengikuti seleksi.
>
> Selanjutnya [Nama 3] akan menunjukkan pengisian data siswa."

---

# PRESENTER 3 - BIODATA, DOKUMEN & PILIH SEKOLAH
## Halaman: /siswa/biodata.php → /siswa/dokumen.php → /siswa/pilih_sekolah.php

**[Klik menu Biodata di sidebar]**

> "Ini halaman pengisian biodata.
>
> **[Scroll form biodata]**
> Siswa mengisi data lengkap: nama, tempat lahir, tanggal lahir, jenis kelamin, alamat, dan sebagainya.
>
> **[Tunjukkan bagian GPS]**
> Yang paling penting adalah **lokasi GPS rumah**. Siswa harus mengizinkan akses lokasi atau memasukkan koordinat manual. Data GPS ini digunakan untuk menghitung jarak ke sekolah di jalur Zonasi.
>
> **[Klik menu Dokumen]**
> Setelah biodata, siswa upload dokumen:
> - Kartu Keluarga
> - Foto
> - Ijazah/SKL
> - Rapor
> - Dokumen tambahan sesuai jalur
>
> Format yang diterima JPG, PNG, atau PDF maksimal 2MB.
>
> **[Klik menu Pilih Sekolah]**
> Terakhir, siswa memilih jalur dan sekolah.
>
> Siswa hanya boleh memilih **1 jalur** tapi bisa memilih **2 sekolah** sebagai pilihan 1 dan pilihan 2. Pemilihan ini final dan tidak bisa diubah.
>
> Setelah semua selesai, data siswa masuk ke antrian verifikasi.
>
> [Nama 4] akan menunjukkan sisi admin."

---

# PRESENTER 4 - DASHBOARD ADMIN & VERIFIKASI
## Halaman: /admin/index.php → /admin/verifikasi/ → /admin/pengaturan.php

**[Logout dari siswa, login sebagai admin]**

> "Sekarang kita masuk sebagai Super Admin.
>
> **[Tunjukkan dashboard admin]**
> Ini dashboard administrator. Ada statistik lengkap:
> - Total pendaftar
> - Sudah diverifikasi
> - Belum diverifikasi
> - Distribusi per jalur dalam bentuk chart
>
> Sistem kami membedakan 2 role pengelola:
> - **Super Admin** - akses semua data
> - **Operator Sekolah** - hanya data sekolahnya
>
> **[Klik menu Verifikasi]**
> Di sini operator memverifikasi dokumen siswa.
>
> **[Klik salah satu siswa]**
> Operator bisa melihat setiap dokumen yang diupload, lalu menandai Valid atau Tidak Valid dengan catatan.
>
> **[Klik menu Pengaturan]**
> Di Pengaturan, admin mengontrol status PPDB:
> - Belum Dibuka
> - Pendaftaran
> - Seleksi
> - Pengumuman
> - Daftar Ulang
> - Selesai
>
> Saat status diubah di sini, semua jalur otomatis ikut berubah.
>
> Selanjutnya [Nama 5] akan menunjukkan proses seleksi."

---

# PRESENTER 5 - PROSES SELEKSI & PUBLIKASI
## Halaman: /admin/seleksi/proses.php → /admin/seleksi/ranking.php → /admin/seleksi/publikasi.php

**[Klik menu Seleksi > Proses Seleksi]**

> "Setelah pendaftaran ditutup, admin menjalankan proses seleksi.
>
> **[Tunjukkan halaman proses seleksi]**
> Admin memilih jalur dan sekolah, lalu sistem akan menghitung ranking otomatis.
>
> Logika seleksi berbeda per jalur:
> - **Zonasi** - yang jaraknya paling dekat dapat peringkat tertinggi
> - **Prestasi** - nilai rapor tertinggi menang
> - **Afirmasi & Mutasi** - dokumen pendukung harus valid
>
> **[Klik menu Ranking]**
> Setelah proses, hasilnya bisa dilihat di menu Ranking. Ada tabel dengan peringkat, nama siswa, nilai/jarak, dan status lulus atau tidak.
>
> **[Klik menu Publikasi]**
> Terakhir, admin mempublikasikan hasil agar bisa dilihat siswa.
>
> Setelah dipublikasi, siswa login dan melihat apakah mereka lulus atau tidak, beserta sekolah tujuan.
>
> Selanjutnya [Nama 6] akan menunjukkan fitur peta dan menyampaikan kesimpulan."

---

# PRESENTER 6 - PETA INTERAKTIF & KESIMPULAN
## Halaman: /index.php (scroll ke peta)

**[Kembali ke halaman utama, scroll ke section peta]**

> "Fitur unggulan kami adalah peta interaktif ini.
>
> **[Tunjukkan peta]**
> Peta ini menampilkan:
> - 20 marker untuk 20 SMP Negeri kerena saat ini kami baru mengumpulkan 20 data sekolah
    dan akan bisa ditambah lagi.
> - Polygon berwarna untuk 11 kecamatan Kota Padang
>
> **[Klik salah satu marker]**
> Jika diklik, muncul popup dengan nama sekolah, alamat, dan kuota, serta detail sekolah.
>
> Peta ini kami buat menggunakan **Leaflet.js** dengan data GeoJSON untuk polygon kecamatan.
>
> Untuk perhitungan jarak di jalur Zonasi, kami menggunakan rumus **Haversine** yang menghitung jarak antara 2 titik koordinat di permukaan bumi.
>
> ---
>
> **KESIMPULAN**
>
> Sistem PPDB SMP yang kami buat berhasil:
> 1. Mengotomasi pendaftaran siswa secara online
> 2. Mendukung 4 jalur sesuai regulasi
> 3. Menyediakan transparansi lewat peta dan ranking
> 4. Memudahkan kerja operator dan admin
>
> **Saran pengembangan:**
> - Notifikasi via email/WhatsApp
> - Integrasi dengan Dapodik
> - Versi mobile app
>
> Demikian demo sistem kami. Terima kasih."

---

# MODERATOR - PENUTUP

> "Terima kasih [Nama 6].
>
> Bapak/Ibu Dosen, demikian demo sistem PPDB SMP dari kelompok kami.
>
> Apakah ada pertanyaan? Silakan.
>
> *(Setelah tanya jawab selesai)*
>
> Terima kasih atas pertanyaan dan masukannya.
> Wassalamualaikum warahmatullahi wabarakatuh."

---

## PERSIAPAN SEBELUM DEMO

1. ✅ Pastikan XAMPP sudah running (Apache + MySQL)
2. ✅ Siapkan **akun demo**:
   - Siswa: NISN 1234567890 / password: demo123
   - Admin: admin / password: admin123
3. ✅ Isi data demo (minimal 3-5 siswa yang sudah lengkap)
4. ✅ Test semua fitur sebelum presentasi
5. ✅ Siapkan browser dengan tab yang sudah ready
6. ✅ **Backup screenshot** kalau terjadi error

---

*Semoga sukses presentasinya!* 🎉

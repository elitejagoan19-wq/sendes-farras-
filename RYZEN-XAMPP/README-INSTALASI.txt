RYZEN — PORTAL INFORMASI PENDIDIKAN
Paket PHP + MySQL untuk XAMPP

FITUR
- Login dengan 4 peran: administrator, guru, siswa, orang tua.
- Dashboard ringkasan.
- Pengumuman: admin dapat menambah, mengubah, dan menghapus.
- Jadwal pelajaran: admin dapat mengelola jadwal.
- Absensi: admin/guru dapat mencatat kehadiran per tanggal.
- Siswa dapat melihat riwayat kehadirannya.
- Orang tua dapat melihat kehadiran anak yang dihubungkan admin.
- Manajemen siswa dan akun pengguna oleh admin.
- Tampilan responsif untuk laptop dan ponsel.
- Password disimpan menggunakan password_hash() PHP, query menggunakan PDO prepared statements, dan formulir memakai token CSRF.

PERSYARATAN
- XAMPP dengan Apache, MySQL/MariaDB, dan PHP 8.0+.
- Browser modern.

CARA INSTALASI DI WINDOWS
1. Ekstrak folder RYZEN-XAMPP dari ZIP.
2. Salin folder RYZEN-XAMPP ke C:\xampp\htdocs\
3. Ubah nama folder menjadi ryzen (disarankan), sehingga lokasi menjadi:
   C:\xampp\htdocs\ryzen\
4. Buka XAMPP Control Panel.
5. Klik Start pada Apache dan MySQL. Keduanya harus berwarna hijau.
6. Buka browser: http://localhost/phpmyadmin
7. Klik tab Import.
8. Pilih file: C:\xampp\htdocs\ryzen\database\ryzen_school.sql
9. Klik Import / Go dan tunggu sampai selesai.
   File SQL membuat database bernama ryzen_school dan tabel-tabelnya.
10. Buka: http://localhost/ryzen/install.php
11. Jika muncul instalasi berhasil, gunakan akun demo di bawah.
12. Setelah berhasil, HAPUS file install.php dari folder project.
13. Buka: http://localhost/ryzen/

AKUN DEMO
Administrator
  Email    : admin@ryzen.sch.id
  Password : Admin123!

Guru
  Email    : guru@ryzen.sch.id
  Password : Guru123!

Siswa
  Email    : siswa@ryzen.sch.id
  Password : Siswa123!

Orang tua
  Email    : ortu@ryzen.sch.id
  Password : Ortu123!

Data contoh siswa Andi Pratama kelas XI RPL 2 sudah dihubungkan dengan akun orang tua demo.

PENGGUNAAN
ADMIN:
- Pengumuman: buat, edit, hapus informasi.
- Jadwal: tambah, edit, hapus jadwal.
- Absensi: catat Hadir/Izin/Sakit/Alpa.
- Data Siswa: tambah/edit/hapus data siswa dan hubungkan akun orang tua.
- Pengguna: buat/edit/hapus akun guru, siswa, orang tua, dan admin.
- Saat menambah siswa dari menu Data Siswa, sistem membuat akun siswa otomatis dan menampilkan password awal acak setelah tersimpan.

GURU:
- Melihat pengumuman dan jadwal.
- Mengisi absensi siswa.

SISWA:
- Melihat pengumuman dan jadwal.
- Melihat riwayat kehadiran miliknya.

ORANG TUA:
- Melihat informasi anak dan riwayat kehadiran.
- Agar data anak tampil, admin harus menghubungkan akun orang tua di menu Data Siswa.

PENGATURAN DATABASE
Konfigurasi default ada di config/config.php:
DB_HOST = 127.0.0.1
DB_NAME = ryzen_school
DB_USER = root
DB_PASS = (kosong)

Jika MySQL XAMPP kamu memakai password root, isi DB_PASS sesuai password tersebut.
Jika nama folder bukan /ryzen, ubah BASE_URL di config/config.php.

CATATAN PENTING
- Akun demo hanya untuk uji coba lokal. Ganti password akun setelah masuk dan jangan gunakan password demo untuk layanan publik.
- Ini merupakan prototipe sekolah untuk demonstrasi. Sebelum dipakai dengan data siswa sungguhan atau dipublikasikan ke internet, minta pemeriksaan administrator/ahli IT sekolah, aktifkan HTTPS, buat backup, batasi akses, dan tambahkan kebijakan privasi serta persetujuan yang sesuai.
- Jangan unggah data pribadi siswa asli ke hosting publik untuk demo.
- Absensi pada versi ini dicatat manual oleh admin/guru; belum terhubung ke mesin fingerprint, kartu, atau sistem presensi resmi.
- Akun orang tua hanya dapat melihat siswa yang sudah ditautkan melalui parent_user_id.
- Jika muncul "could not find driver", aktifkan extension=pdo_mysql pada php.ini XAMPP lalu restart Apache.
- Jika Apache tidak bisa start, kemungkinan port 80/443 sedang dipakai aplikasi lain. Periksa log XAMPP.

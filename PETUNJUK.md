# Presensi Satker Prasarana Strategis NTT

Aplikasi PHP native dengan MySQL/MariaDB untuk Apache di XAMPP.

## Menjalankan

1. Letakkan folder di `C:\xampp\htdocs\absen-satker`.
2. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
3. Jalankan `basis_data.sql` melalui phpMyAdmin untuk membuat database.
4. Jalankan `composer install` dari folder proyek untuk memasang pustaka ekspor XLSX dan PDF.
5. Buka `http://localhost/absen-satker/`.
6. Sebelum membuat admin pertama kali, atur `APP_ADMIN_PASSWORD` di environment Apache ke kata sandi acak minimal 16 karakter. Username awal `admin` dapat diganti dengan `APP_ADMIN_USERNAME`; admin wajib mengganti kata sandi pada login pertama.
7. Tambahkan akun pegawai dari menu **Data pegawai**. Pegawai juga wajib mengganti kata sandi awal.

## Akun masuk

- Admin awal: username `admin` (atau nilai `APP_ADMIN_USERNAME`) dan kata sandi rahasia dari `APP_ADMIN_PASSWORD`. Tidak ada kata sandi bawaan; admin wajib mengganti kata sandi pada login pertama.
- Pegawai: tidak ada username/kata sandi bawaan. Admin membuat akun di menu **Data pegawai** dan menetapkan username serta kata sandi awal. Sampaikan kredensial itu kepada pegawai melalui jalur internal; pegawai wajib mengganti kata sandi saat login pertama.

## Struktur halaman

- `masuk.php`: formulir masuk.
- `ringkasan_admin.php`: ringkasan administrator.
- `ringkasan_pegawai.php`: ringkasan dan formulir absensi pegawai.
- `riwayat_absensi.php`: riwayat absensi pegawai.
- `daftar_pegawai.php`: pencarian, filter, aktivasi/nonaktif akun, dan penghapusan permanen pegawai.
- `pegawai_tambah.php`: pembuatan akun pegawai.
- `pegawai_ubah.php`: perubahan identitas, alamat, telepon, dan foto profil pegawai.
- `pegawai_kata_sandi.php`: pengaturan kata sandi awal pegawai.
- `pengaturan_admin.php`: administrator mengganti kata sandi dan logo instansi.
- `profil.php`: pegawai memperbarui nama, nomor pegawai, nama pengguna, alamat, telepon, foto profil, dan kata sandi.
- `arsip_absensi.php`: arsip absensi admin.
- `laporan_absensi.php`: rekap absensi bulanan per pegawai dengan ekspor Excel dan PDF.
- `ubah_kata_sandi.php`: penggantian kata sandi awal.
- `includes/inisialisasi.php`: sesi, autentikasi, aksi form, unggahan, dan akses bukti.
- `includes/kerangka_awal.php` dan `includes/kerangka_akhir.php`: kerangka bersama halaman setelah autentikasi.
- `index.php`: pengarah berdasarkan status login dan peran.

Konfigurasi bawaan untuk pengembangan lokal: host `127.0.0.1`, database `absen_satker`, username MySQL `root`, kata sandi kosong. Untuk produksi, set `APP_ENV=production`, gunakan `APP_DB_USER` non-root dan `APP_DB_PASSWORD` yang kuat; aplikasi menolak kredensial database root/kosong. Atur seluruh rahasia di environment server, bukan di berkas web. Jika HTTPS berhenti di reverse proxy tepercaya, set `APP_HTTPS_TERMINATED=1` hanya pada environment Apache di belakang proxy tersebut. Sebelum admin pertama kali dibuat, atur `APP_ADMIN_NAME`, `APP_ADMIN_USERNAME`, dan `APP_ADMIN_PASSWORD` (minimal 16 karakter). Tabel aplikasi adalah `pengguna`, `absensi`, `pengaturan_aplikasi`, dan `pembatasan_masuk`. Pada database lama, tabel `users`, `attendance`, dan `app_settings` diganti namanya otomatis saat koneksi pertama tanpa menghapus datanya. Jika nama lama dan baru sudah sama-sama ada, aplikasi berhenti agar data tidak tertimpa; gabungkan data secara manual terlebih dahulu.

Folder `storage/attendance` harus dapat ditulisi Apache. Akses langsung ke folder bukti dan foto profil diblokir; aplikasi memeriksa hak akses sebelum mengirim berkas. Kolom alamat, telepon, dan foto profil ditambahkan otomatis pada database lama. Zona waktu aplikasi dan koneksi database menggunakan WITA (Asia/Makassar).

Kata sandi baru minimal 12 karakter. Setelah 5 kegagalan login dalam 15 menit, pasangan username/alamat IP dibatasi selama 15 menit. Sesi berakhir setelah 30 menit tidak aktif atau 12 jam total. Aplikasi mengaktifkan cookie sesi aman saat HTTPS, kebijakan keamanan konten, dan pembatasan akses berkas melalui Apache. Untuk produksi pemerintahan, wajib aktifkan HTTPS, gunakan akun MySQL khusus berhak minimum (bukan `root`), simpan rahasia di environment server di luar webroot, batasi akses jaringan/admin, serta tetapkan pemantauan dan pencadangan terenkripsi. Kamera dan lokasi memerlukan HTTPS, kecuali pada `localhost`. Foto dan video adalah bukti liveness untuk tinjauan admin; tidak ada pengenalan/pencocokan wajah biometrik otomatis. Batas tiap unggahan 20 MB; sesuaikan `upload_max_filesize` serta `post_max_size` bila diperlukan.

Di menu **Data pegawai**, menonaktifkan akun hanya memblokir login dan mempertahankan seluruh data. Menghapus akun adalah tindakan permanen yang menghapus akun, seluruh catatan absensi, foto profil, serta berkas bukti setelah konfirmasi.
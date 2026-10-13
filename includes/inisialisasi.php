<?php
declare(strict_types=1);

require_once __DIR__ . '/keamanan.php';
mulai_sesi_aman();
require_once __DIR__ . '/../konfigurasi.php';

function teks_aman(?string $nilai): string
{
    return htmlspecialchars($nilai ?? '', ENT_QUOTES, 'UTF-8');
}

function alihkan_halaman(string $alamatTujuan): never
{
    $jalurSkrip = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $direktoriAplikasi = str_ends_with($jalurSkrip, '/includes/inisialisasi.php')
        ? dirname(dirname($jalurSkrip))
        : dirname($jalurSkrip);
    header('Location: ' . rtrim($direktoriAplikasi, '/') . '/' . ltrim($alamatTujuan, '/'));
    exit;
}

function token_csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function periksa_token_csrf(): void
{
    $dataDikirim = $_POST['token_csrf'] ?? null;
    if (!is_string($dataDikirim) || !hash_equals(token_csrf(), $dataDikirim)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman.');
    }
}

function format_tanggal_indonesia(string $nilai, string $formatTanggal): string
{
    $terjemahan = [
        'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis',
        'Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu','January'=>'Januari',
        'February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni',
        'July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober',
        'November'=>'November','December'=>'Desember','Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar',
        'Apr'=>'Apr','Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Agu','Sep'=>'Sep','Oct'=>'Okt','Nov'=>'Nov','Dec'=>'Des',
    ];
    return strtr(date($formatTanggal, strtotime($nilai)), $terjemahan);
}

function simpan_unggahan(string $namaBidang, array $tipeMimeDiizinkan): string
{
    $berkas = $_FILES[$namaBidang] ?? null;
    if (!$berkas || $berkas['error'] !== UPLOAD_ERR_OK || $berkas['size'] < 1 || $berkas['size'] > 20 * 1024 * 1024) {
        throw new RuntimeException('Bukti tidak tersedia atau melebihi batas 20 MB.');
    }
    $tipeMime = (new finfo(FILEINFO_MIME_TYPE))->file($berkas['tmp_name']);
    if (!isset($tipeMimeDiizinkan[$tipeMime])) {
        throw new RuntimeException('Format bukti tidak didukung.');
    }
    $namaBerkas = bin2hex(random_bytes(20)) . '.' . $tipeMimeDiizinkan[$tipeMime];
    if (!move_uploaded_file($berkas['tmp_name'], DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $namaBerkas)) {
        throw new RuntimeException('Bukti absensi gagal disimpan.');
    }
    return $namaBerkas;
}

function simpan_foto_profil(string $namaBidang): string
{
    $berkas = $_FILES[$namaBidang] ?? null;
    $tipeMimeDiizinkan = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $tipeMime = $berkas && $berkas['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($berkas['tmp_name']) : '';
    if (!$berkas || $berkas['error'] !== UPLOAD_ERR_OK || $berkas['size'] < 1 || $berkas['size'] > 5 * 1024 * 1024 || !isset($tipeMimeDiizinkan[$tipeMime])) {
        throw new RuntimeException('Foto profil harus JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.');
    }
    $namaBerkas = 'profile_' . bin2hex(random_bytes(20)) . '.' . $tipeMimeDiizinkan[$tipeMime];
    if (!move_uploaded_file($berkas['tmp_name'], DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $namaBerkas)) {
        throw new RuntimeException('Foto profil gagal disimpan.');
    }
    return $namaBerkas;
}

function simpan_logo_instansi(string $namaBidang): string
{
    $berkas = $_FILES[$namaBidang] ?? null;
    $tipeMimeDiizinkan = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $tipeMime = $berkas && $berkas['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($berkas['tmp_name']) : '';
    if (!$berkas || $berkas['error'] !== UPLOAD_ERR_OK || $berkas['size'] < 1 || $berkas['size'] > 5 * 1024 * 1024 || !isset($tipeMimeDiizinkan[$tipeMime])) {
        throw new RuntimeException('Logo harus JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.');
    }
    $namaBerkas = 'logo_' . bin2hex(random_bytes(20)) . '.' . $tipeMimeDiizinkan[$tipeMime];
    if (!move_uploaded_file($berkas['tmp_name'], DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $namaBerkas)) {
        throw new RuntimeException('Logo instansi gagal disimpan.');
    }
    return $namaBerkas;
}

try {
    $koneksi = koneksi_basis_data();
    $namaBerkasLogo = $koneksi->query("SELECT setting_value FROM pengaturan_aplikasi WHERE setting_key='site_logo'")->fetchColumn() ?: null;
} catch (Throwable $kesalahan) {
    http_response_code(503);
    exit('<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Database belum siap</title><body style="font:16px/1.6 sans-serif;max-width:620px;margin:12vh auto;padding:24px;color:#243d35"><h1>Database belum siap</h1><p>Buat database <strong>absen_satker</strong> dengan menjalankan <code>basis_data.sql</code> melalui phpMyAdmin, lalu muat ulang halaman.</p><p>Periksa konfigurasi database di <code>konfigurasi.php</code> dan kredensial MySQL XAMPP.</p></body></html>');
}

if (($_GET['action'] ?? '') === 'site_logo') {
    $jalurBerkas = $namaBerkasLogo ? DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . basename($namaBerkasLogo) : '';
    if (!$namaBerkasLogo || !is_file($jalurBerkas)) {
        http_response_code(404);
        exit('Logo instansi tidak ditemukan.');
    }
    $tipeMime = (new finfo(FILEINFO_MIME_TYPE))->file($jalurBerkas);
    if (!in_array($tipeMime, ['image/jpeg','image/png','image/webp'], true)) {
        http_response_code(404);
        exit('Logo instansi tidak ditemukan.');
    }
    header('Content-Type: ' . $tipeMime);
    header('Content-Length: ' . filesize($jalurBerkas));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=3600');
    readfile($jalurBerkas);
    exit;
}

$penggunaAktif = $_SESSION['user'] ?? null;
if ($penggunaAktif) {
    $pernyataan = $koneksi->prepare('SELECT id,employee_no,name,username,address,phone,profile_photo,role,is_active,must_change_password FROM pengguna WHERE id=?');
    $pernyataan->execute([$penggunaAktif['id']]);
    $penggunaTerkini = $pernyataan->fetch();
    if (!$penggunaTerkini || !$penggunaTerkini['is_active']) {
        hapus_sesi_aman();
        $penggunaAktif = null;
    } else {
        $_SESSION['user'] = array_intersect_key($penggunaTerkini, array_flip(['id','employee_no','name','username','address','phone','profile_photo','role','must_change_password']));
        $penggunaAktif = $_SESSION['user'];
    }
}

if (($_GET['action'] ?? '') === 'evidence') {
    if (!$penggunaAktif) {
        alihkan_halaman('masuk.php');
    }
    $pernyataan = $koneksi->prepare('SELECT * FROM absensi WHERE id=?');
    $pernyataan->execute([(int) ($_GET['id'] ?? 0)]);
    $catatan = $pernyataan->fetch();
    $jenis = $_GET['kind'] ?? '';
    if (!$catatan || ($penggunaAktif['role'] !== 'admin' && (int) $catatan['user_id'] !== (int) $penggunaAktif['id']) || !in_array($jenis, ['selfie','video'], true)) {
        http_response_code(404);
        exit('Bukti tidak ditemukan.');
    }
    $namaBerkas = $jenis === 'selfie' ? $catatan['selfie_path'] : $catatan['video_path'];
    $jalurBerkas = DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $namaBerkas;
    if (!is_file($jalurBerkas)) {
        http_response_code(404);
        exit('Berkas bukti tidak ditemukan.');
    }
    header('Content-Type: ' . ((new finfo(FILEINFO_MIME_TYPE))->file($jalurBerkas) ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($jalurBerkas));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . basename($jalurBerkas) . '"');
    readfile($jalurBerkas);
    exit;
}

if (($_GET['action'] ?? '') === 'profile_photo') {
    if (!$penggunaAktif) {
        alihkan_halaman('masuk.php');
    }
    $idPengguna = (int) ($_GET['id'] ?? $penggunaAktif['id']);
    if ($penggunaAktif['role'] !== 'admin' && $idPengguna !== (int) $penggunaAktif['id']) {
        http_response_code(404);
        exit('Foto profil tidak ditemukan.');
    }
    $pernyataan = $koneksi->prepare('SELECT profile_photo FROM pengguna WHERE id=?');
    $pernyataan->execute([$idPengguna]);
    $namaBerkas = $pernyataan->fetchColumn();
    $jalurBerkas = $namaBerkas ? DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . basename($namaBerkas) : '';
    if (!$namaBerkas || !is_file($jalurBerkas)) {
        http_response_code(404);
        exit('Foto profil tidak ditemukan.');
    }
    $tipeMime = (new finfo(FILEINFO_MIME_TYPE))->file($jalurBerkas);
    if (!in_array($tipeMime, ['image/jpeg','image/png','image/webp'], true)) {
        http_response_code(404);
        exit('Foto profil tidak ditemukan.');
    }
    header('Content-Type: ' . $tipeMime);
    header('Content-Length: ' . filesize($jalurBerkas));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($jalurBerkas);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    periksa_token_csrf();
    $aksi = $_POST['aksi_formulir'] ?? '';
    try {
        if ($aksi === 'masuk') {
            $namaPenggunaMasuk = trim((string) ($_POST['nama_pengguna'] ?? ''));
            if (periksa_pembatasan_masuk($koneksi, $namaPenggunaMasuk)) {
                throw new RuntimeException('Username atau kata sandi tidak sesuai.');
            }
            $pernyataan = $koneksi->prepare('SELECT * FROM pengguna WHERE username=? AND is_active=1');
            $pernyataan->execute([$namaPenggunaMasuk]);
            $penggunaDitemukan = $pernyataan->fetch();
            if (!$penggunaDitemukan || !password_verify((string) ($_POST['kata_sandi'] ?? ''), $penggunaDitemukan['password_hash'])) {
                catat_percobaan_masuk($koneksi, $namaPenggunaMasuk, false);
                throw new RuntimeException('Username atau kata sandi tidak sesuai.');
            }
            catat_percobaan_masuk($koneksi, $namaPenggunaMasuk, true);
            session_regenerate_id(true);
            $_SESSION['waktu_login'] = time();
            $_SESSION['aktivitas_terakhir'] = time();
            $_SESSION['user'] = array_intersect_key($penggunaDitemukan, array_flip(['id','employee_no','name','username','role','must_change_password']));
            alihkan_halaman($penggunaDitemukan['role'] === 'admin' ? 'ringkasan_admin.php' : 'ringkasan_pegawai.php');
        }
        if (!$penggunaAktif) {
            alihkan_halaman('masuk.php');
        }
        if ($aksi === 'keluar') {
            hapus_sesi_aman();
            alihkan_halaman('masuk.php');
        }
        if ($aksi === 'ubah_kata_sandi') {
            $kataSandi = (string) ($_POST['kata_sandi_baru'] ?? '');
            if (mb_strlen($kataSandi, 'UTF-8') < PANJANG_MINIMUM_KATA_SANDI) {
                throw new RuntimeException('Kata sandi baru minimal 12 karakter.');
            }
            $koneksi->prepare('UPDATE pengguna SET password_hash=?,must_change_password=0 WHERE id=?')->execute([password_hash($kataSandi, PASSWORD_DEFAULT), $penggunaAktif['id']]);
            $_SESSION['user']['must_change_password'] = 0;
            $_SESSION['flash'] = ['success', 'Kata sandi berhasil diperbarui.'];
            alihkan_halaman($penggunaAktif['role'] === 'admin' ? 'ringkasan_admin.php' : 'ringkasan_pegawai.php');
        }
        if ($aksi === 'ubah_kata_sandi_admin' && $penggunaAktif['role'] === 'admin') {
            $kataSandiSaatIni = (string) ($_POST['kata_sandi_saat_ini'] ?? '');
            $kataSandi = (string) ($_POST['kata_sandi_baru'] ?? '');
            $konfirmasiKataSandi = (string) ($_POST['konfirmasi_kata_sandi_baru'] ?? '');
            $pernyataan = $koneksi->prepare('SELECT password_hash FROM pengguna WHERE id=? AND role=\'admin\'');
            $pernyataan->execute([$penggunaAktif['id']]);
            $hashKataSandi = $pernyataan->fetchColumn();
            if (!$hashKataSandi || !password_verify($kataSandiSaatIni, $hashKataSandi)) {
                throw new RuntimeException('Kata sandi saat ini tidak sesuai.');
            }
            if (mb_strlen($kataSandi, 'UTF-8') < PANJANG_MINIMUM_KATA_SANDI) {
                throw new RuntimeException('Kata sandi baru minimal 12 karakter.');
            }
            if ($kataSandi !== $konfirmasiKataSandi) {
                throw new RuntimeException('Konfirmasi kata sandi baru tidak sesuai.');
            }
            if (password_verify($kataSandi, $hashKataSandi)) {
                throw new RuntimeException('Kata sandi baru harus berbeda dari kata sandi saat ini.');
            }
            $koneksi->prepare('UPDATE pengguna SET password_hash=? WHERE id=? AND role=\'admin\'')->execute([password_hash($kataSandi, PASSWORD_DEFAULT), $penggunaAktif['id']]);
            $_SESSION['flash'] = ['success', 'Kata sandi administrator berhasil diperbarui.'];
            alihkan_halaman('pengaturan_admin.php');
        }
        if ($aksi === 'perbarui_logo_instansi' && $penggunaAktif['role'] === 'admin') {
            $logo = simpan_logo_instansi('logo_instansi');
            try {
                $pernyataan = $koneksi->prepare("INSERT INTO pengaturan_aplikasi(setting_key,setting_value) VALUES('site_logo',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
                $pernyataan->execute([$logo]);
            } catch (Throwable $kesalahan) {
                @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $logo);
                throw $kesalahan;
            }
            if ($namaBerkasLogo) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . basename($namaBerkasLogo));
            $_SESSION['flash'] = ['success', 'Logo instansi berhasil diperbarui.'];
            alihkan_halaman('pengaturan_admin.php');
        }
        if ($penggunaAktif['role'] === 'pegawai' && $aksi === 'perbarui_profil') {
            $nama = trim((string) ($_POST['nama'] ?? ''));
            $nomorPegawai = trim((string) ($_POST['nomor_pegawai'] ?? ''));
            $namaPengguna = trim((string) ($_POST['nama_pengguna'] ?? ''));
            $alamat = trim((string) ($_POST['alamat'] ?? ''));
            $telepon = trim((string) ($_POST['telepon'] ?? ''));
            $kataSandi = (string) ($_POST['kata_sandi_baru'] ?? '');
            if ($nama === '' || $namaPengguna === '' || strlen($nama) > 160 || strlen($nomorPegawai) > 80 || strlen($namaPengguna) > 80 || strlen($telepon) > 40) {
                throw new RuntimeException('Periksa kembali nama, nomor pegawai, username, dan nomor telepon.');
            }
            if ($kataSandi !== '' && mb_strlen($kataSandi, 'UTF-8') < PANJANG_MINIMUM_KATA_SANDI) {
                throw new RuntimeException('Kata sandi baru minimal 12 karakter.');
            }
            $foto = null;
            if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
                $foto = simpan_foto_profil('foto_profil');
            }
            $fotoProfilLama = $penggunaAktif['profile_photo'] ?? null;
            try {
                $kueri = 'UPDATE pengguna SET name=?,employee_no=?,username=?,address=?,phone=?';
                $daftarNilai = [$nama, $nomorPegawai !== '' ? $nomorPegawai : null, $namaPengguna, $alamat !== '' ? $alamat : null, $telepon !== '' ? $telepon : null];
                if ($foto !== null) {
                    $kueri .= ',profile_photo=?';
                    $daftarNilai[] = $foto;
                }
                if ($kataSandi !== '') {
                    $kueri .= ',password_hash=?,must_change_password=0';
                    $daftarNilai[] = password_hash($kataSandi, PASSWORD_DEFAULT);
                }
                $kueri .= ' WHERE id=? AND role=\'pegawai\'';
                $daftarNilai[] = $penggunaAktif['id'];
                $pernyataan = $koneksi->prepare($kueri);
                $pernyataan->execute($daftarNilai);
            } catch (Throwable $kesalahan) {
                if ($foto !== null) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $foto);
                throw $kesalahan;
            }
            if ($foto !== null && $fotoProfilLama) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . basename($fotoProfilLama));
            $_SESSION['flash'] = ['success', 'Profil berhasil diperbarui.'];
            alihkan_halaman('profil.php');
        }
        if ($penggunaAktif['role'] === 'pegawai' && $aksi === 'absen_ulang') {
            $idAbsensi = filter_var($_POST['id_absensi'] ?? null, FILTER_VALIDATE_INT);
            $jenis = (string) ($_POST['jenis_absensi'] ?? '');
            $jenisDiizinkan = ['check_in','check_out','izin','cuti','sakit','perjadin'];
            if (!$idAbsensi || !in_array($jenis, $jenisDiizinkan, true)) {
                throw new RuntimeException('Jenis absensi tidak valid.');
            }
            $lintang = filter_var($_POST['lintang'] ?? null, FILTER_VALIDATE_FLOAT);
            $bujur = filter_var($_POST['bujur'] ?? null, FILTER_VALIDATE_FLOAT);
            $akurasi = filter_var($_POST['akurasi'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($lintang === false || $bujur === false || $akurasi === false || abs($lintang) > 90 || abs($bujur) > 180 || $akurasi < 0 || $akurasi > 1000) {
                throw new RuntimeException('Lokasi tidak valid atau akurasinya melebihi 1.000 meter.');
            }

            $koneksi->beginTransaction();
            $koneksi->prepare('SELECT id FROM pengguna WHERE id=? FOR UPDATE')->execute([$penggunaAktif['id']]);
            $pernyataan = $koneksi->prepare('SELECT id,kind,selfie_path,video_path FROM absensi WHERE user_id=? AND DATE(created_at)=CURDATE() ORDER BY id FOR UPDATE');
            $pernyataan->execute([$penggunaAktif['id']]);
            $daftarCatatanHariIni = $pernyataan->fetchAll();
            $catatanDitemukan = false;
            $swafotoLama = null;
            $videoLama = null;
            foreach ($daftarCatatanHariIni as &$catatan) {
                if ((int) $catatan['id'] === $idAbsensi) {
                    $catatan['kind'] = $jenis;
                    $catatanDitemukan = true;
                    $swafotoLama = $catatan['selfie_path'];
                    $videoLama = $catatan['video_path'];
                }
            }
            unset($catatan);
            if (!$catatanDitemukan) {
                throw new RuntimeException('Catatan yang akan diubah tidak ditemukan untuk hari ini.');
            }

            $jenisHariIni = array_column($daftarCatatanHariIni, 'kind');
            $jumlahPerJenis = array_count_values($jenisHariIni);
            $jumlahAbsenMasuk = $jumlahPerJenis['check_in'] ?? 0;
            $jumlahAbsenPulang = $jumlahPerJenis['check_out'] ?? 0;
            $jumlahStatusKhusus = ($jumlahPerJenis['izin'] ?? 0) + ($jumlahPerJenis['cuti'] ?? 0) + ($jumlahPerJenis['sakit'] ?? 0);
            if ($jumlahAbsenMasuk > 1 || $jumlahAbsenPulang > 1 || ($jumlahStatusKhusus > 0 && count($jenisHariIni) > 1)) {
                throw new RuntimeException('Perubahan ini bertentangan dengan catatan absensi lain hari ini.');
            }
            if ($jumlahAbsenPulang > 0) {
                $indeksAbsenPulang = array_search('check_out', $jenisHariIni, true);
                $jenisSebelumnya = array_slice($jenisHariIni, 0, $indeksAbsenPulang);
                if (!in_array('check_in', $jenisSebelumnya, true) && !in_array('perjadin', $jenisSebelumnya, true)) {
                    throw new RuntimeException('Absensi pulang memerlukan absensi masuk atau perjalanan dinas sebelumnya.');
                }
                if (date('H:i') < '16:00') {
                    throw new RuntimeException('Absensi pulang hanya berlaku setelah pukul 16.00 WITA.');
                }
            }

            $buktiBaru = [];
            try {
                $swafoto = simpan_unggahan('swafoto', ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']);
                $buktiBaru[] = $swafoto;
                $video = simpan_unggahan('video', ['video/webm'=>'webm','video/mp4'=>'mp4','video/ogg'=>'ogv']);
                $buktiBaru[] = $video;
                $pernyataan = $koneksi->prepare('UPDATE absensi SET kind=?,latitude=?,longitude=?,accuracy=?,selfie_path=?,video_path=? WHERE id=? AND user_id=? AND DATE(created_at)=CURDATE()');
                $pernyataan->execute([$jenis,$lintang,$bujur,$akurasi,$swafoto,$video,$idAbsensi,$penggunaAktif['id']]);
                $koneksi->commit();
            } catch (Throwable $kesalahan) {
                foreach ($buktiBaru as $namaBerkas) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $namaBerkas);
                throw $kesalahan;
            }
            if ($swafotoLama) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . basename($swafotoLama));
            if ($videoLama) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . basename($videoLama));
            $_SESSION['flash'] = ['success', 'Absen ulang berhasil. Status, foto, video, dan lokasi telah diperbarui.'];
            alihkan_halaman('ringkasan_pegawai.php');
        }
        if ($penggunaAktif['role'] === 'admin' && $aksi === 'tambah_pegawai') {
            $nama = trim((string) ($_POST['nama'] ?? ''));
            $nomorPegawai = trim((string) ($_POST['nomor_pegawai'] ?? ''));
            $namaPengguna = trim((string) ($_POST['nama_pengguna'] ?? ''));
            $jabatan = trim((string) ($_POST['jabatan'] ?? ''));
            $kataSandi = (string) ($_POST['kata_sandi'] ?? '');
            if (!$nama || !$nomorPegawai || !$namaPengguna || strlen($jabatan) > 100 || mb_strlen($kataSandi, 'UTF-8') < PANJANG_MINIMUM_KATA_SANDI) {
                throw new RuntimeException('Lengkapi data; kata sandi minimal 12 karakter.');
            }
            $pernyataan = $koneksi->prepare("INSERT INTO pengguna(employee_no,name,username,position,password_hash,role,is_active,must_change_password) VALUES(?,?,?,?,?,'pegawai',1,1)");
            $pernyataan->execute([$nomorPegawai,$nama,$namaPengguna,$jabatan !== '' ? $jabatan : null,password_hash($kataSandi, PASSWORD_DEFAULT)]);
            $_SESSION['flash'] = ['success', 'Akun pegawai berhasil ditambahkan.'];
            alihkan_halaman('daftar_pegawai.php');
        }
        if ($penggunaAktif['role'] === 'admin' && $aksi === 'ubah_status_pegawai') {
            $koneksi->prepare("UPDATE pengguna SET is_active=1-is_active WHERE id=? AND role='pegawai'")->execute([(int) ($_POST['id_pegawai'] ?? 0)]);
            $_SESSION['flash'] = ['success', 'Status akun pegawai diperbarui.'];
            alihkan_halaman('daftar_pegawai.php');
        }
        if ($penggunaAktif['role'] === 'admin' && $aksi === 'hapus_pegawai') {
            $idPegawai = filter_var($_POST['id_pegawai'] ?? null, FILTER_VALIDATE_INT);
            if ($idPegawai === false || $idPegawai === null || $idPegawai < 1) {
                throw new RuntimeException('Akun pegawai yang akan dihapus tidak valid.');
            }
            require_once __DIR__ . '/manajemen_pegawai.php';
            $jumlahBerkasGagal = hapus_akun_pegawai($koneksi, $idPegawai);
            if ($jumlahBerkasGagal > 0) {
                $_SESSION['flash'] = ['error', 'Akun dan catatan absensi sudah dihapus, tetapi ' . $jumlahBerkasGagal . ' berkas bukti perlu dibersihkan oleh administrator server.'];
            } else {
                $_SESSION['flash'] = ['success', 'Akun pegawai beserta seluruh data absensi dan bukti berhasil dihapus.'];
            }
            alihkan_halaman('daftar_pegawai.php');
        }
        if ($penggunaAktif['role'] === 'admin' && $aksi === 'ubah_data_pegawai') {
            $idPegawai = (int) ($_POST['id_pegawai'] ?? 0);
            $nama = trim((string) ($_POST['nama'] ?? ''));
            $nomorPegawai = trim((string) ($_POST['nomor_pegawai'] ?? ''));
            $namaPengguna = trim((string) ($_POST['nama_pengguna'] ?? ''));
            $jabatan = trim((string) ($_POST['jabatan'] ?? ''));
            $alamat = trim((string) ($_POST['alamat'] ?? ''));
            $telepon = trim((string) ($_POST['telepon'] ?? ''));
            if ($idPegawai < 1 || $nama === '' || $nomorPegawai === '' || $namaPengguna === '' || strlen($nama) > 160 || strlen($nomorPegawai) > 80 || strlen($namaPengguna) > 80 || strlen($jabatan) > 100 || strlen($telepon) > 40) {
                throw new RuntimeException('Periksa kembali nama, nomor pegawai, username, dan nomor telepon.');
            }
            $foto = null;
            if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
                $foto = simpan_foto_profil('foto_profil');
            }
            $pernyataanFotoLama = $koneksi->prepare("SELECT profile_photo FROM pengguna WHERE id=? AND role='pegawai'");
            $pernyataanFotoLama->execute([$idPegawai]);
            $fotoProfilLama = $pernyataanFotoLama->fetchColumn();
            if ($fotoProfilLama === false) {
                if ($foto !== null) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $foto);
                throw new RuntimeException('Akun pegawai tidak ditemukan.');
            }
            try {
                $kueri = 'UPDATE pengguna SET name=?,employee_no=?,username=?,position=?,address=?,phone=?';
                $daftarNilai = [$nama, $nomorPegawai, $namaPengguna, $jabatan !== '' ? $jabatan : null, $alamat !== '' ? $alamat : null, $telepon !== '' ? $telepon : null];
                if ($foto !== null) {
                    $kueri .= ',profile_photo=?';
                    $daftarNilai[] = $foto;
                }
                $kueri .= " WHERE id=? AND role='pegawai'";
                $daftarNilai[] = $idPegawai;
                $koneksi->prepare($kueri)->execute($daftarNilai);
            } catch (Throwable $kesalahan) {
                if ($foto !== null) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $foto);
                throw $kesalahan;
            }
            if ($foto !== null && $fotoProfilLama) @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . basename($fotoProfilLama));
            $_SESSION['flash'] = ['success', 'Data pegawai berhasil diperbarui.'];
            alihkan_halaman('daftar_pegawai.php');
        }
        if ($penggunaAktif['role'] === 'admin' && $aksi === 'atur_kata_sandi_pegawai') {
            $idPegawai = (int) ($_POST['id_pegawai'] ?? 0);
            $kataSandi = (string) ($_POST['kata_sandi_baru'] ?? '');
            if ($idPegawai < 1 || mb_strlen($kataSandi, 'UTF-8') < PANJANG_MINIMUM_KATA_SANDI) {
                throw new RuntimeException('Kata sandi awal harus minimal 12 karakter.');
            }
            $pernyataan = $koneksi->prepare("UPDATE pengguna SET password_hash=?,must_change_password=1 WHERE id=? AND role='pegawai'");
            $pernyataan->execute([password_hash($kataSandi, PASSWORD_DEFAULT), $idPegawai]);
            if ($pernyataan->rowCount() < 1) {
                throw new RuntimeException('Akun pegawai tidak ditemukan.');
            }
            $_SESSION['flash'] = ['success', 'Kata sandi awal diperbarui. Berikan kata sandi baru kepada pegawai melalui jalur internal.'];
            alihkan_halaman('daftar_pegawai.php');
        }
        if ($penggunaAktif['role'] === 'pegawai' && $aksi === 'absensi') {
            $jenisDiizinkan = ['check_in','check_out','izin','cuti','sakit','perjadin'];
            $jenis = $_POST['jenis_absensi'] ?? 'check_in';
            if (!in_array($jenis, $jenisDiizinkan, true)) {
                throw new RuntimeException('Jenis absensi tidak valid.');
            }

            $lintang = filter_var($_POST['lintang'] ?? null, FILTER_VALIDATE_FLOAT);
            $bujur = filter_var($_POST['bujur'] ?? null, FILTER_VALIDATE_FLOAT);
            $akurasi = filter_var($_POST['akurasi'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($lintang === false || $bujur === false || $akurasi === false || abs($lintang) > 90 || abs($bujur) > 180 || $akurasi > 1000) {
                throw new RuntimeException('Lokasi tidak valid atau akurasinya melebihi 1.000 meter.');
            }

            $koneksi->beginTransaction();
            $koneksi->prepare('SELECT id FROM pengguna WHERE id=? FOR UPDATE')->execute([$penggunaAktif['id']]);
            $pernyataan = $koneksi->prepare('SELECT kind FROM absensi WHERE user_id=? AND DATE(created_at)=CURDATE()');
            $pernyataan->execute([$penggunaAktif['id']]);
            $jenisHariIni = $pernyataan->fetchAll(PDO::FETCH_COLUMN);
            $sudahAbsenMasuk = in_array('check_in', $jenisHariIni, true);
            $sudahAbsenPulang = in_array('check_out', $jenisHariIni, true);
            $adaPerjalananDinas = in_array('perjadin', $jenisHariIni, true);
            $adaStatusLain = (bool) array_intersect(['izin','cuti','sakit'], $jenisHariIni);

            if ($jenis === 'check_in' && $jenisHariIni) {
                throw new RuntimeException('Absensi masuk hanya dapat dicatat sekali dan sebelum status absensi lain.');
            }
            if ($jenis === 'check_out') {
                if ($sudahAbsenPulang) {
                    throw new RuntimeException('Absensi pulang sudah dicatat hari ini.');
                }
                if (!$sudahAbsenMasuk && !$adaPerjalananDinas) {
                    throw new RuntimeException('Absensi pulang memerlukan absensi masuk atau perjalanan dinas hari ini.');
                }
                if ($adaStatusLain) {
                    throw new RuntimeException('Absensi pulang tidak tersedia untuk status sakit, izin, atau cuti.');
                }
                if (date('H:i') < '16:00') {
                    throw new RuntimeException('Absensi pulang baru dapat dilakukan setelah pukul 16.00 WITA.');
                }
            }
            if (!in_array($jenis, ['check_in','check_out'], true) && $jenisHariIni) {
                throw new RuntimeException('Status absensi hanya dapat dicatat sekali dalam sehari.');
            }

            $swafoto = simpan_unggahan('swafoto', ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']);
            try {
                $video = simpan_unggahan('video', ['video/webm'=>'webm','video/mp4'=>'mp4','video/ogg'=>'ogv']);
            } catch (Throwable $kesalahan) {
                @unlink(DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $swafoto);
                throw $kesalahan;
            }
            $pernyataan = $koneksi->prepare('INSERT INTO absensi(user_id,kind,latitude,longitude,accuracy,selfie_path,video_path) VALUES(?,?,?,?,?,?,?)');
            $pernyataan->execute([$penggunaAktif['id'],$jenis,$lintang,$bujur,$akurasi,$swafoto,$video]);
            $koneksi->commit();

            $label = [
                'check_in' => 'Absensi masuk berhasil dicatat.',
                'check_out' => 'Absensi pulang berhasil dicatat.',
                'izin' => 'Izin berhasil dicatat.',
                'cuti' => 'Cuti berhasil dicatat.',
                'sakit' => 'Status sakit berhasil dicatat.',
                'perjadin' => 'Perjalanan dinas berhasil dicatat.',
            ];
            $pesan = $label[$jenis] ?? 'Absensi berhasil dicatat.';
            if ($jenis === 'check_in' && date('H:i:s') > '08:30:00') {
                $pesan = 'Absensi masuk terlambat berhasil dicatat.';
            }
            $_SESSION['flash'] = ['success', $pesan];
            alihkan_halaman('ringkasan_pegawai.php');
        }
    } catch (PDOException $kesalahan) {
        if ($koneksi->inTransaction()) $koneksi->rollBack();
        $_SESSION['flash'] = ['error', $kesalahan->getCode() === '23000' ? 'Nomor pegawai atau username sudah digunakan.' : 'Gagal menyimpan data; periksa koneksi database.'];
    } catch (Throwable $kesalahan) {
        if ($koneksi->inTransaction()) $koneksi->rollBack();
        $_SESSION['flash'] = ['error', $kesalahan->getMessage()];
    }
    if (!$penggunaAktif) {
        alihkan_halaman('masuk.php');
    }
    if (($aksi ?? '') === 'perbarui_profil') {
        alihkan_halaman('profil.php');
    }
    if (($aksi ?? '') === 'tambah_pegawai') {
        alihkan_halaman('pegawai_tambah.php');
    }
    if (($aksi ?? '') === 'hapus_pegawai') {
        alihkan_halaman('daftar_pegawai.php');
    }
    if (($aksi ?? '') === 'ubah_data_pegawai') {
        alihkan_halaman('pegawai_ubah.php?id=' . (int) ($_POST['id_pegawai'] ?? 0));
    }
    if (($aksi ?? '') === 'atur_kata_sandi_pegawai') {
        alihkan_halaman('pegawai_kata_sandi.php?id=' . (int) ($_POST['id_pegawai'] ?? 0));
    }
    alihkan_halaman($penggunaAktif['role'] === 'admin' ? 'ringkasan_admin.php' : 'ringkasan_pegawai.php');
}

$notifikasi = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
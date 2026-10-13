<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Makassar');
const DIREKTORI_PENYIMPANAN = __DIR__ . '/storage/attendance';
const PANJANG_MINIMUM_KATA_SANDI = 12;

function koneksi_basis_data(): PDO
{
    static $koneksi;
    if ($koneksi instanceof PDO) return $koneksi;

    $lingkungan = getenv('APP_ENV') ?: 'development';
    $tuanRumah = getenv('APP_DB_HOST') ?: '127.0.0.1';
    $namaBasisData = getenv('APP_DB_NAME') ?: 'absen_satker';
    $namaPenggunaDariLingkungan = getenv('APP_DB_USER');
    $namaPengguna = is_string($namaPenggunaDariLingkungan) && $namaPenggunaDariLingkungan !== ''
        ? $namaPenggunaDariLingkungan
        : ($lingkungan === 'production' ? '' : 'root');
    $kataSandiDariLingkungan = getenv('APP_DB_PASSWORD');
    $kataSandi = is_string($kataSandiDariLingkungan) ? $kataSandiDariLingkungan : '';
    if ($lingkungan === 'production' && ($namaPengguna === '' || strtolower($namaPengguna) === 'root' || $kataSandi === '')) {
        throw new RuntimeException('Produksi wajib menggunakan APP_DB_USER non-root dan APP_DB_PASSWORD yang kuat.');
    }
    $koneksi = new PDO("mysql:host=$tuanRumah;dbname=$namaBasisData;charset=utf8mb4", $namaPengguna, $kataSandi, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $koneksi->exec("SET time_zone = '+08:00'");
    $tabelTersedia = $koneksi->query('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
    $daftarMigrasi = ['users' => 'pengguna', 'attendance' => 'absensi', 'app_settings' => 'pengaturan_aplikasi'];
    foreach ($daftarMigrasi as $tabelLama => $tabelBaru) {
        if (in_array($tabelLama, $tabelTersedia, true) && in_array($tabelBaru, $tabelTersedia, true)) {
            throw new RuntimeException("Tabel $tabelLama dan $tabelBaru sama-sama tersedia. Gabungkan datanya sebelum menjalankan aplikasi.");
        }
        if (in_array($tabelLama, $tabelTersedia, true)) {
            $koneksi->exec("RENAME TABLE $tabelLama TO $tabelBaru");
            $tabelTersedia[] = $tabelBaru;
        }
    }
    $koneksi->exec("CREATE TABLE IF NOT EXISTS pengguna (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_no VARCHAR(80) NULL UNIQUE,
        name VARCHAR(160) NOT NULL,
        username VARCHAR(80) NOT NULL UNIQUE,
        position VARCHAR(100) NULL,
        address TEXT NULL,
        phone VARCHAR(40) NULL,
        profile_photo VARCHAR(255) NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','pegawai') NOT NULL DEFAULT 'pegawai',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        must_change_password TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    foreach ([
        'position' => 'VARCHAR(100) NULL',
        'address' => 'TEXT NULL',
        'phone' => 'VARCHAR(40) NULL',
        'profile_photo' => 'VARCHAR(255) NULL',
    ] as $kolom => $definisi) {
        $pernyataan = $koneksi->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $pernyataan->execute(['pengguna', $kolom]);
        if (!$pernyataan->fetch()) {
            $koneksi->exec("ALTER TABLE pengguna ADD COLUMN $kolom $definisi");
        }
    }
    $koneksi->exec("CREATE TABLE IF NOT EXISTS absensi (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        kind ENUM('check_in','check_out','izin','cuti','sakit','perjadin') NOT NULL,
        latitude DECIMAL(10,7) NOT NULL,
        longitude DECIMAL(10,7) NOT NULL,
        accuracy DECIMAL(9,2) NOT NULL,
        selfie_path VARCHAR(255) NOT NULL,
        video_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_absensi_pengguna FOREIGN KEY (user_id) REFERENCES pengguna(id),
        INDEX idx_absensi_pengguna_dibuat (user_id,created_at),
        INDEX idx_absensi_dibuat (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $koneksi->exec("ALTER TABLE absensi MODIFY kind ENUM('check_in','check_out','izin','cuti','sakit','perjadin') NOT NULL");
    $koneksi->exec("CREATE TABLE IF NOT EXISTS pengaturan_aplikasi (
        setting_key VARCHAR(80) PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $koneksi->exec("CREATE TABLE IF NOT EXISTS pembatasan_masuk (
        kunci CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
        jumlah_percobaan TINYINT UNSIGNED NOT NULL DEFAULT 0,
        jendela_dimulai DATETIME NOT NULL,
        diblokir_sampai DATETIME NULL,
        INDEX idx_pembatasan_masuk_jendela (jendela_dimulai)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!is_dir(DIREKTORI_PENYIMPANAN) && !mkdir(DIREKTORI_PENYIMPANAN, 0750, true) && !is_dir(DIREKTORI_PENYIMPANAN)) {
        throw new RuntimeException('Folder penyimpanan bukti tidak dapat dibuat.');
    }
    if ((int) $koneksi->query("SELECT COUNT(*) FROM pengguna WHERE role='admin'")->fetchColumn() === 0) {
        $nama = getenv('APP_ADMIN_NAME') ?: 'Administrator Satker';
        $penggunaAdmin = getenv('APP_ADMIN_USERNAME') ?: 'admin';
        $kataSandiAwal = getenv('APP_ADMIN_PASSWORD');
        if (!is_string($kataSandiAwal) || mb_strlen($kataSandiAwal, 'UTF-8') < 16) {
            throw new RuntimeException('Atur APP_ADMIN_PASSWORD minimal 16 karakter sebelum membuat akun admin pertama.');
        }
        $pernyataan = $koneksi->prepare("INSERT INTO pengguna(name,username,password_hash,role,must_change_password) VALUES(?,?,?,'admin',1)");
        $pernyataan->execute([$nama, $penggunaAdmin, password_hash($kataSandiAwal, PASSWORD_DEFAULT)]);
    }
    return $koneksi;
}
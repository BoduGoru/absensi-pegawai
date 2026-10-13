<?php
declare(strict_types=1);

function mulai_sesi_aman(): void
{
    $koneksiAman = (isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off' && $_SERVER['HTTPS'] !== '')
        || (getenv('APP_ENV') === 'production' && getenv('APP_HTTPS_TERMINATED') === '1');
    if (getenv('APP_ENV') === 'production' && !$koneksiAman) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Koneksi HTTPS wajib digunakan.');
    }
    if (getenv('APP_ENV') === 'production') {
        ini_set('display_errors', '0');
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('PRESENSI_SATKER');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $koneksiAman,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    $waktuSekarang = time();
    if (!empty($_SESSION['user'])) {
        $aktivitasTerakhir = filter_var($_SESSION['aktivitas_terakhir'] ?? null, FILTER_VALIDATE_INT);
        $waktuLogin = filter_var($_SESSION['waktu_login'] ?? null, FILTER_VALIDATE_INT);
        if (($aktivitasTerakhir && $waktuSekarang - $aktivitasTerakhir > 1800)
            || ($waktuLogin && $waktuSekarang - $waktuLogin > 43200)) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
    }
    if (!empty($_SESSION['user'])) {
        $_SESSION['waktu_login'] ??= $waktuSekarang;
        $_SESSION['aktivitas_terakhir'] = $waktuSekarang;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), geolocation=(self), microphone=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'");
    header('Cache-Control: private, no-store');
    if ($koneksiAman) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function hapus_sesi_aman(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $parameterCookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parameterCookie['path'],
            'domain' => $parameterCookie['domain'],
            'secure' => $parameterCookie['secure'],
            'httponly' => $parameterCookie['httponly'],
            'samesite' => $parameterCookie['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function kunci_pembatasan_masuk(string $username): string
{
    $alamatIp = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
    return hash('sha256', mb_strtolower(trim($username), 'UTF-8') . "\0" . ($alamatIp ?: 'unknown'));
}

function periksa_pembatasan_masuk(PDO $koneksi, string $username): bool
{
    $pernyataan = $koneksi->prepare('SELECT diblokir_sampai FROM pembatasan_masuk WHERE kunci=?');
    $pernyataan->execute([kunci_pembatasan_masuk($username)]);
    $diblokirSampai = $pernyataan->fetchColumn();
    return is_string($diblokirSampai) && strtotime($diblokirSampai) > time();
}

function catat_percobaan_masuk(PDO $koneksi, string $username, bool $berhasil): void
{
    $kunci = kunci_pembatasan_masuk($username);
    $koneksi->beginTransaction();
    try {
        $koneksi->prepare('INSERT IGNORE INTO pembatasan_masuk(kunci,jumlah_percobaan,jendela_dimulai) VALUES(?,0,CURRENT_TIMESTAMP)')->execute([$kunci]);
        $pernyataan = $koneksi->prepare('SELECT jumlah_percobaan,jendela_dimulai,diblokir_sampai FROM pembatasan_masuk WHERE kunci=? FOR UPDATE');
        $pernyataan->execute([$kunci]);
        $catatan = $pernyataan->fetch();

        if ($berhasil) {
            $koneksi->prepare('DELETE FROM pembatasan_masuk WHERE kunci=?')->execute([$kunci]);
            $koneksi->commit();
            return;
        }

        $waktuSekarang = time();
        if ($catatan['diblokir_sampai'] && strtotime($catatan['diblokir_sampai']) > $waktuSekarang) {
            $koneksi->commit();
            return;
        }

        $waktuMulai = strtotime($catatan['jendela_dimulai']);
        $percobaan = $waktuSekarang - $waktuMulai >= 900 ? 1 : (int) $catatan['jumlah_percobaan'] + 1;
        $jendelaDimulai = $waktuSekarang - $waktuMulai >= 900 ? date('Y-m-d H:i:s', $waktuSekarang) : $catatan['jendela_dimulai'];
        $diblokirSampai = $percobaan >= 5 ? date('Y-m-d H:i:s', $waktuSekarang + 900) : null;
        $koneksi->prepare('UPDATE pembatasan_masuk SET jumlah_percobaan=?,jendela_dimulai=?,diblokir_sampai=? WHERE kunci=?')->execute([$percobaan,$jendelaDimulai,$diblokirSampai,$kunci]);
        $koneksi->commit();
        $koneksi->exec('DELETE FROM pembatasan_masuk WHERE jendela_dimulai < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY) LIMIT 100');
    } catch (Throwable $kesalahan) {
        if ($koneksi->inTransaction()) $koneksi->rollBack();
        throw $kesalahan;
    }
}

<?php
require_once __DIR__ . '/includes/inisialisasi.php';

if ($penggunaAktif) {
    alihkan_halaman($penggunaAktif['must_change_password']
        ? 'ubah_kata_sandi.php'
        : ($penggunaAktif['role'] === 'admin' ? 'ringkasan_admin.php' : 'ringkasan_pegawai.php'));
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#083a74">
    <title>Masuk · Presensi Satker NTT</title>
    <link rel="stylesheet" href="assets/gaya.css?v=<?= filemtime(__DIR__ . '/assets/gaya.css') ?>">
    <link rel="stylesheet" href="assets/identitas.css?v=<?= filemtime(__DIR__ . '/assets/identitas.css') ?>">
    <link rel="stylesheet" href="assets/warna.css?v=<?= filemtime(__DIR__ . '/assets/warna.css') ?>">
</head>
<body>
<main class="login-shell">
    <section class="login-intro">
        <?php if ($namaBerkasLogo): ?><img class="ministry-mark ministry-mark-image" src="includes/inisialisasi.php?action=site_logo&amp;v=<?= rawurlencode($namaBerkasLogo) ?>" alt="Logo instansi"><?php else: ?><div class="ministry-mark">PU</div><?php endif; ?>
        <p class="eyebrow">Kementerian Pekerjaan Umum</p>
        <h1>Presensi<br>pegawai.</h1>
        <p class="intro-copy">Satuan Kerja Pelaksanaan Prasarana Strategis Nusa Tenggara Timur</p>
        <div class="intro-rule"></div>
        <p class="intro-foot">Layanan presensi internal</p>
    </section>
    <section class="login-panel">
        <form class="login-form" method="post">
            <input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>">
            <input type="hidden" name="aksi_formulir" value="masuk">
            <p class="eyebrow">AKSES SISTEM</p>
            <h2>Masuk ke akun</h2>
            <p class="muted">Gunakan akun yang diberikan administrator.</p>
            <?php if ($notifikasi): ?>
                <div class="notice <?= teks_aman($notifikasi[0]) ?>" role="status"><?= teks_aman($notifikasi[1]) ?></div>
            <?php endif; ?>
            <label>Username<input name="nama_pengguna" autocomplete="username" required autofocus></label>
            <label>Kata sandi<input name="kata_sandi" type="password" autocomplete="current-password" required></label>
            <button class="button primary full" type="submit">Masuk <span>→</span></button>
            <p class="login-note">Belum memiliki akun? Hubungi administrator satker.</p>
        </form>
    </section>
</main>
</body>
</html>

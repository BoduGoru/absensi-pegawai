<?php
$penggunaAktif = $GLOBALS['currentUser'] ?? $penggunaAktif ?? null;
$namaBerkasLogo = $GLOBALS['siteLogoFilename'] ?? $namaBerkasLogo ?? null;
$notifikasi = $GLOBALS['flash'] ?? $notifikasi ?? null;
$judulHalaman = $judulHalaman ?? 'Ringkasan';
$halamanAktif = $halamanAktif ?? 'ringkasan';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#083a74">
    <title><?= teks_aman($judulHalaman) ?> · Presensi Satker NTT</title>
    <link rel="stylesheet" href="assets/gaya.css?v=<?= filemtime(__DIR__ . '/../assets/gaya.css') ?>">
    <link rel="stylesheet" href="assets/identitas.css?v=<?= filemtime(__DIR__ . '/../assets/identitas.css') ?>">
    <?php if ($halamanAktif === 'pegawai'): ?><link rel="stylesheet" href="assets/pegawai.css?v=<?= filemtime(__DIR__ . '/../assets/pegawai.css') ?>"><?php endif; ?>
    <?php if ($halamanAktif === 'profil'): ?><link rel="stylesheet" href="assets/profil.css?v=<?= filemtime(__DIR__ . '/../assets/profil.css') ?>"><?php endif; ?>
    <?php if ($halamanAktif === 'absensi'): ?><link rel="stylesheet" href="assets/absensi.css?v=<?= filemtime(__DIR__ . '/../assets/absensi.css') ?>"><?php endif; ?>
    <?php if ($halamanAktif === 'pengaturan'): ?><link rel="stylesheet" href="assets/pengaturan.css?v=<?= filemtime(__DIR__ . '/../assets/pengaturan.css') ?>"><?php endif; ?>
    <?php if ($halamanAktif === 'laporan'): ?><link rel="stylesheet" href="assets/laporan.css?v=<?= filemtime(__DIR__ . '/../assets/laporan.css') ?>"><?php endif; ?>
    <?php if ($penggunaAktif['role'] === 'pegawai' && $halamanAktif === 'ringkasan'): ?><link rel="stylesheet" href="assets/kamera.css?v=<?= filemtime(__DIR__ . '/../assets/kamera.css') ?>"><?php endif; ?>
    <link rel="stylesheet" href="assets/warna.css?v=<?= filemtime(__DIR__ . '/../assets/warna.css') ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php"><?php if ($namaBerkasLogo): ?><img class="brand-mark brand-mark-image" src="includes/inisialisasi.php?action=site_logo&amp;v=<?= rawurlencode($namaBerkasLogo) ?>" alt="Logo instansi"><?php else: ?><span class="brand-mark">PU</span><?php endif; ?><span><strong>Presensi Satker</strong><small>Prasarana Strategis NTT</small></span></a>
    <div class="topbar-right">
        <span class="today-label"><?= teks_aman(format_tanggal_indonesia(date('Y-m-d'), 'l, d F Y')) ?></span>
        <div class="user-chip"><?php if (!empty($penggunaAktif['profile_photo'])): ?><img class="avatar" src="includes/inisialisasi.php?action=profile_photo&amp;id=<?= (int) $penggunaAktif['id'] ?>" alt=""><?php else: ?><span class="avatar"><?= teks_aman(strtoupper(substr($penggunaAktif['name'], 0, 1))) ?></span><?php endif; ?><span><?= teks_aman($penggunaAktif['name']) ?><small><?= $penggunaAktif['role'] === 'admin' ? 'Administrator' : 'Pegawai' ?></small></span></div>
        <form method="post" data-konfirmasi="Apakah Anda yakin ingin Log Out?"><input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>"><input type="hidden" name="aksi_formulir" value="keluar"><button class="icon-button" aria-label="Keluar" title="Keluar">↗</button></form>
    </div>
</header>
<div class="app-layout">
    <aside class="sidebar">
        <p class="nav-caption">MENU UTAMA</p>
        <a class="nav-link <?= $halamanAktif === 'ringkasan' ? 'active' : '' ?>" href="<?= $penggunaAktif['role'] === 'admin' ? 'ringkasan_admin.php' : 'ringkasan_pegawai.php' ?>"><span>⌂</span>Ringkasan</a>
        <?php if ($penggunaAktif['role'] === 'admin'): ?>
            <a class="nav-link <?= $halamanAktif === 'pegawai' ? 'active' : '' ?>" href="daftar_pegawai.php"><span>♙</span>Data pegawai</a>
            <a class="nav-link <?= $halamanAktif === 'arsip' ? 'active' : '' ?>" href="arsip_absensi.php"><span>▤</span>Semua absensi</a>
            <a class="nav-link <?= $halamanAktif === 'laporan' ? 'active' : '' ?>" href="laporan_absensi.php"><span>▥</span>Laporan absensi</a>
            <a class="nav-link <?= $halamanAktif === 'pengaturan' ? 'active' : '' ?>" href="pengaturan_admin.php"><span>⚙</span>Pengaturan</a>
        <?php else: ?>
            <a class="nav-link <?= $halamanAktif === 'absensi' ? 'active' : '' ?>" href="riwayat_absensi.php"><span>◷</span>Riwayat absensi</a>
            <a class="nav-link <?= $halamanAktif === 'profil' ? 'active' : '' ?>" href="profil.php"><span>♙</span>Profil saya</a>
        <?php endif; ?>
        <div class="sidebar-bottom"><span class="status-dot"></span>Sistem aktif<small>Presensi internal satker</small></div>
    </aside>
    <main class="main-content">
        <div class="page-heading"><div><p class="eyebrow">SATKER PELAKSANAAN PRASARANA STRATEGIS NTT</p><h1><?= teks_aman($judulHalaman) ?></h1></div></div>
        <?php if ($notifikasi): ?><div class="notice <?= teks_aman($notifikasi[0]) ?>" role="status"><?= teks_aman($notifikasi[1]) ?></div><?php endif; ?>
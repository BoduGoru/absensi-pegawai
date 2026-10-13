<?php
require_once __DIR__ . '/includes/inisialisasi.php';

if (!$penggunaAktif) {
    alihkan_halaman('masuk.php');
}
if ($penggunaAktif['must_change_password']) {
    alihkan_halaman('ubah_kata_sandi.php');
}

if ($penggunaAktif['role'] === 'admin') {
    $tampilan = $_GET['view'] ?? '';
    alihkan_halaman($tampilan === 'employees' ? 'daftar_pegawai.php' : ($tampilan === 'records' ? 'arsip_absensi.php' : 'ringkasan_admin.php'));
}
alihkan_halaman(($_GET['view'] ?? '') === 'attendance' ? 'riwayat_absensi.php' : 'ringkasan_pegawai.php');
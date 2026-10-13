<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$judulHalaman = 'Tambah pegawai';
$halamanAktif = 'pegawai';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<section class="panel add-panel">
    <p class="eyebrow">AKUN BARU</p>
    <h2>Tambah pegawai</h2>
    <form method="post" class="stack-form">
        <input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>">
        <input type="hidden" name="aksi_formulir" value="tambah_pegawai">
        <label>Nama lengkap<input name="nama" required></label>
        <label>Jabatan / status kepegawaian<input name="jabatan" maxlength="100" placeholder="Contoh: Kasatker, PNS, Satpam"></label>
        <label>Nomor pegawai<input name="nomor_pegawai" required></label>
        <label>Username<input name="nama_pengguna" required autocomplete="off"></label>
        <label>Kata sandi awal<input type="password" name="kata_sandi" minlength="10" required><small>Minimal 10 karakter; pegawai wajib menggantinya saat masuk.</small></label>
        <div class="profile-form-actions"><a class="button secondary" href="daftar_pegawai.php">Batal</a><button class="button primary" type="submit">Buat akun <span>→</span></button></div>
    </form>
</section>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
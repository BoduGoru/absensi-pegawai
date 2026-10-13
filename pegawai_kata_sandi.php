<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$idPegawai = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$idPegawai || $idPegawai < 1) alihkan_halaman('daftar_pegawai.php');
$pernyataan = $koneksi->prepare("SELECT id,name,employee_no FROM pengguna WHERE id=? AND role='pegawai'");
$pernyataan->execute([$idPegawai]);
$pegawai = $pernyataan->fetch();
if (!$pegawai) alihkan_halaman('daftar_pegawai.php');

$judulHalaman = 'Atur kata sandi pegawai';
$halamanAktif = 'pegawai';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<section class="panel add-panel">
    <p class="eyebrow">PENGATURAN AKUN</p>
    <h2>Atur kata sandi</h2>
    <p><?= teks_aman($pegawai['name']) ?> · <?= teks_aman($pegawai['employee_no']) ?></p>
    <p class="muted">Pegawai wajib mengganti kata sandi ini saat masuk berikutnya.</p>
    <form method="post" class="stack-form">
        <input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>">
        <input type="hidden" name="aksi_formulir" value="atur_kata_sandi_pegawai">
        <input type="hidden" name="id_pegawai" value="<?= (int) $pegawai['id'] ?>">
        <label>Kata sandi awal<input type="password" name="kata_sandi_baru" minlength="10" autocomplete="new-password" required></label>
        <div class="profile-form-actions"><a class="button secondary" href="daftar_pegawai.php">Batal</a><button class="button primary" type="submit">Atur kata sandi <span>→</span></button></div>
    </form>
</section>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
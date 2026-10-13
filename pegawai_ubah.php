<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$idPegawai = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$idPegawai || $idPegawai < 1) alihkan_halaman('daftar_pegawai.php');
$pernyataan = $koneksi->prepare("SELECT id,employee_no,name,username,position,address,phone FROM pengguna WHERE id=? AND role='pegawai'");
$pernyataan->execute([$idPegawai]);
$pegawai = $pernyataan->fetch();
if (!$pegawai) alihkan_halaman('daftar_pegawai.php');

$judulHalaman = 'Ubah data pegawai';
$halamanAktif = 'pegawai';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<section class="panel add-panel">
    <p class="eyebrow">DATA PEGAWAI</p>
    <h2>Ubah data pegawai</h2>
    <form method="post" class="stack-form" enctype="multipart/form-data">
        <input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>">
        <input type="hidden" name="aksi_formulir" value="ubah_data_pegawai">
        <input type="hidden" name="id_pegawai" value="<?= (int) $pegawai['id'] ?>">
        <label>Nama<input name="nama" maxlength="160" value="<?= teks_aman($pegawai['name']) ?>" required></label>
        <label>Jabatan / status kepegawaian<input name="jabatan" maxlength="100" value="<?= teks_aman($pegawai['position'] ?? '') ?>" placeholder="Contoh: Kasatker, PNS, Satpam"></label>
        <label>Nomor pegawai<input name="nomor_pegawai" maxlength="80" value="<?= teks_aman($pegawai['employee_no']) ?>" required></label>
        <label>Username<input name="nama_pengguna" maxlength="80" value="<?= teks_aman($pegawai['username']) ?>" required></label>
        <label>Alamat<textarea name="alamat" rows="2"><?= teks_aman($pegawai['address'] ?? '') ?></textarea></label>
        <label>Nomor telepon<input name="telepon" type="tel" maxlength="40" value="<?= teks_aman($pegawai['phone'] ?? '') ?>"></label>
        <label>Foto profil<input name="foto_profil" type="file" accept="image/jpeg,image/png,image/webp"><small>Biarkan kosong untuk mempertahankan foto saat ini.</small></label>
        <div class="profile-form-actions"><a class="button secondary" href="daftar_pegawai.php">Batal</a><button class="button primary" type="submit">Simpan perubahan <span>→</span></button></div>
    </form>
</section>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
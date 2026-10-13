<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'pegawai') alihkan_halaman('ringkasan_admin.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$judulHalaman = 'Profil saya';
$halamanAktif = 'profil';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<section class="panel profile-edit-panel">
    <div class="profile-edit-heading">
        <?php if (!empty($penggunaAktif['profile_photo'])): ?>
            <img class="profile-photo-large" src="includes/inisialisasi.php?action=profile_photo&amp;id=<?= (int) $penggunaAktif['id'] ?>" alt="Foto profil <?= teks_aman($penggunaAktif['name']) ?>">
        <?php else: ?>
            <div class="profile-photo-large profile-photo-placeholder"><?= teks_aman(strtoupper(substr($penggunaAktif['name'], 0, 1))) ?></div>
        <?php endif; ?>
        <div><p class="eyebrow">INFORMASI AKUN</p><h2>Perbarui profil pegawai</h2><p class="muted">Data ini tersimpan pada akun yang dikelola administrator.</p></div>
    </div>
    <form method="post" enctype="multipart/form-data" class="profile-form">
        <input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>">
        <input type="hidden" name="aksi_formulir" value="perbarui_profil">
        <div class="profile-fields">
            <label>Nama pegawai<input name="nama" maxlength="160" value="<?= teks_aman($penggunaAktif['name']) ?>" required></label>
            <label>Nomor pegawai<input name="nomor_pegawai" maxlength="80" value="<?= teks_aman($penggunaAktif['employee_no'] ?? '') ?>"></label>
            <label>Nama user / username<input name="nama_pengguna" maxlength="80" autocomplete="username" value="<?= teks_aman($penggunaAktif['username']) ?>" required></label>
            <label>Nomor telepon<input name="telepon" type="tel" maxlength="40" autocomplete="tel" value="<?= teks_aman($penggunaAktif['phone'] ?? '') ?>"></label>
            <label class="profile-address">Alamat<textarea name="alamat" rows="3" autocomplete="street-address"><?= teks_aman($penggunaAktif['address'] ?? '') ?></textarea></label>
            <label>Foto profil<input name="foto_profil" type="file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, atau WebP. Maksimal 5 MB.</small></label>
            <label>Kata sandi baru<input name="kata_sandi_baru" type="password" minlength="10" autocomplete="new-password"><small>Kosongkan bila tidak ingin mengganti kata sandi. Minimal 10 karakter.</small></label>
        </div>
        <div class="profile-form-actions"><a class="button secondary" href="ringkasan_pegawai.php">Batal</a><button class="button primary" type="submit">Simpan profil <span>→</span></button></div>
    </form>
</section>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
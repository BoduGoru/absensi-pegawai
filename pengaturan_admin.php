<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$judulHalaman = 'Pengaturan';
$halamanAktif = 'pengaturan';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<div class="settings-grid">
    <section class="panel settings-panel">
        <div class="settings-heading"><p class="eyebrow">KEAMANAN AKUN</p><h2>Ganti kata sandi</h2><p class="muted">Masukkan kata sandi saat ini dan kata sandi baru minimal 10 karakter.</p></div>
        <form method="post" class="settings-form">
            <input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>">
            <input type="hidden" name="aksi_formulir" value="ubah_kata_sandi_admin">
            <label>Kata sandi saat ini<input type="password" name="kata_sandi_saat_ini" autocomplete="current-password" required></label>
            <label>Kata sandi baru<input type="password" name="kata_sandi_baru" minlength="10" autocomplete="new-password" required></label>
            <label>Konfirmasi kata sandi baru<input type="password" name="konfirmasi_kata_sandi_baru" minlength="10" autocomplete="new-password" required></label>
            <div class="settings-actions"><button class="button primary" type="submit">Perbarui kata sandi</button></div>
        </form>
    </section>
    <section class="panel settings-panel">
        <div class="settings-heading"><p class="eyebrow">IDENTITAS SISTEM</p><h2>Logo instansi</h2><p class="muted">Logo ditampilkan pada halaman masuk dan bagian atas setiap halaman.</p></div>
        <div class="logo-preview">
            <?php if ($namaBerkasLogo): ?><img src="includes/inisialisasi.php?action=site_logo&amp;v=<?= rawurlencode($namaBerkasLogo) ?>" alt="Logo instansi saat ini"><?php else: ?><span class="brand-mark">PU</span><?php endif; ?>
            <span><?= $namaBerkasLogo ? 'Logo saat ini' : 'Logo bawaan sistem' ?></span>
        </div>
        <form method="post" enctype="multipart/form-data" class="settings-form">
            <input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>">
            <input type="hidden" name="aksi_formulir" value="perbarui_logo_instansi">
            <label>Pilih logo<input type="file" name="logo_instansi" accept="image/jpeg,image/png,image/webp" required><small>JPG, PNG, atau WebP. Maksimal 5 MB.</small></label>
            <div class="settings-actions"><button class="button primary" type="submit">Simpan logo</button></div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
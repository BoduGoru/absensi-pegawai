<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if (!$penggunaAktif['must_change_password']) alihkan_halaman($penggunaAktif['role'] === 'admin' ? 'ringkasan_admin.php' : 'ringkasan_pegawai.php');
$judulHalaman = 'Perbarui kata sandi';
$halamanAktif = 'ringkasan';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<section class="panel password-panel">
    <div><p class="eyebrow">KEAMANAN AKUN</p><h2>Perbarui kata sandi awal</h2><p class="muted">Gunakan kata sandi baru minimal 10 karakter.</p></div>
    <form method="post" class="inline-form"><input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>"><input type="hidden" name="aksi_formulir" value="ubah_kata_sandi"><input type="password" name="kata_sandi_baru" minlength="10" placeholder="Kata sandi baru" required><button class="button primary" type="submit">Simpan</button></form>
</section>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
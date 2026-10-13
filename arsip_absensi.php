<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$judulHalaman = 'Semua absensi';
$halamanAktif = 'arsip';
$daftarPegawai = $koneksi->query("SELECT id,name FROM pengguna WHERE role='pegawai' ORDER BY name")->fetchAll();
$daftarJabatan = $koneksi->query("SELECT DISTINCT position FROM pengguna WHERE role='pegawai' AND position IS NOT NULL AND TRIM(position)<>'' ORDER BY position")->fetchAll(PDO::FETCH_COLUMN);
$penyaring = [];
$parameter = [];
if (!empty($_GET['employee'])) {
    $penyaring[] = 'u.id=?';
    $parameter[] = (int) $_GET['employee'];
}
if (!empty($_GET['date'])) {
    $penyaring[] = 'DATE(a.created_at)=?';
    $parameter[] = $_GET['date'];
}
$filterJabatan = isset($_GET['position']) && is_string($_GET['position']) ? trim($_GET['position']) : '';
if ($filterJabatan !== '') {
    $penyaring[] = 'u.position=?';
    $parameter[] = $filterJabatan;
}
$syaratSql = $penyaring ? 'WHERE ' . implode(' AND ', $penyaring) : '';
$pernyataan = $koneksi->prepare("SELECT a.*,u.name,u.employee_no FROM absensi a JOIN pengguna u ON u.id=a.user_id $syaratSql ORDER BY a.created_at DESC");
$pernyataan->execute($parameter);
$daftarCatatan = $pernyataan->fetchAll();
require __DIR__ . '/includes/baris_absensi.php';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<section class="panel filter-panel"><form method="get" class="filters">
    <label>Pegawai<select name="employee"><option value="">Semua pegawai</option><?php foreach ($daftarPegawai as $pegawai): ?><option value="<?= (int) $pegawai['id'] ?>" <?= (string) ($_GET['employee'] ?? '') === (string) $pegawai['id'] ? 'selected' : '' ?>><?= teks_aman($pegawai['name']) ?></option><?php endforeach; ?></select></label>
    <label>Jabatan / status<select name="jabatan"><option value="">Semua jabatan</option><?php foreach ($daftarJabatan as $jabatan): ?><option value="<?= teks_aman($jabatan) ?>" <?= $filterJabatan === $jabatan ? 'selected' : '' ?>><?= teks_aman($jabatan) ?></option><?php endforeach; ?></select></label>
    <label>Tanggal<input type="date" name="date" value="<?= teks_aman((string) ($_GET['date'] ?? '')) ?>"></label>
    <button class="button secondary" type="submit">Terapkan filter</button><a class="text-link" href="arsip_absensi.php">Reset</a>
</form></section>
<section class="panel table-panel"><div class="panel-header table-title"><div><p class="eyebrow">ARSIP PRESENSI · SEMUA CATATAN</p><h2><?= count($daftarCatatan) ?> catatan ditemukan</h2></div></div>
    <div class="table-wrap"><table><thead><tr><th>Pegawai</th><th>Tanggal & waktu</th><th>Jenis</th><th>Lokasi</th><th>Bukti</th></tr></thead><tbody>
    <?php if (!$daftarCatatan): ?><tr><td colspan="5" class="empty-state">Belum ada data untuk filter ini.</td></tr><?php else: foreach ($daftarCatatan as $catatan) tampilkan_baris_absensi($catatan, true); endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
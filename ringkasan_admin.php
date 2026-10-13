<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$judulHalaman = 'Ringkasan';
$halamanAktif = 'ringkasan';
$jumlahPegawai = (int) $koneksi->query("SELECT COUNT(*) FROM pengguna WHERE role='pegawai'")->fetchColumn();
$jumlahPegawaiHariIni = (int) $koneksi->query('SELECT COUNT(DISTINCT user_id) FROM absensi WHERE DATE(created_at)=CURDATE()')->fetchColumn();
$jumlahHariIni = (int) $koneksi->query("SELECT COUNT(*) FROM absensi WHERE DATE(created_at)=CURDATE() AND kind='check_in'")->fetchColumn();
$daftarCatatan = $koneksi->query('SELECT a.*,u.name,u.employee_no FROM absensi a JOIN pengguna u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 8')->fetchAll();
require __DIR__ . '/includes/baris_absensi.php';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<div class="admin-stats">
    <section class="panel stat-panel"><span class="stat-icon">♙</span><p class="eyebrow">PEGAWAI TERDAFTAR</p><strong><?= $jumlahPegawai ?></strong><small>Akun pegawai</small></section>
    <section class="panel stat-panel"><span class="stat-icon green">◷</span><p class="eyebrow">PRESENSI MASUK HARI INI</p><strong><?= $jumlahHariIni ?></strong><small>Catatan masuk</small></section>
</div>
<section class="panel table-panel dashboard-table">
    <div class="panel-header table-title"><div><p class="eyebrow">PEMANTAUAN TERBARU</p><h2>Aktivitas presensi</h2><p class="muted"><?= $jumlahPegawaiHariIni ?> dari <?= $jumlahPegawai ?> pegawai sudah absen hari ini</p></div><a class="button secondary small" href="arsip_absensi.php">Semua absensi →</a></div>
    <div class="table-wrap"><table><thead><tr><th>Pegawai</th><th>Tanggal & waktu</th><th>Jenis</th><th>Lokasi</th><th>Bukti</th></tr></thead><tbody>
    <?php if (!$daftarCatatan): ?><tr><td colspan="5" class="empty-state">Belum ada catatan absensi.</td></tr><?php else: foreach ($daftarCatatan as $catatan) tampilkan_baris_absensi($catatan, true); endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
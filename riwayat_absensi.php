<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'pegawai') alihkan_halaman('ringkasan_admin.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$judulHalaman = 'Riwayat absensi';
$halamanAktif = 'absensi';
$bulanDiminta = $_GET['month'] ?? date('Y-m');
if (!is_string($bulanDiminta) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulanDiminta)) {
	$bulanDiminta = date('Y-m');
}
$awalBulan = DateTimeImmutable::createFromFormat('!Y-m-d', $bulanDiminta . '-01');
$bulanBerikutnya = $awalBulan->modify('+1 month');
$akhirBulan = $bulanBerikutnya->format('Y-m-d');
$bulanTerpilih = $awalBulan->format('Y-m');
$tanggalTerpilih = null;
if (isset($_GET['date']) && is_string($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
	$tanggalKandidat = DateTimeImmutable::createFromFormat('!Y-m-d', $_GET['date']);
	if ($tanggalKandidat && $tanggalKandidat->format('Y-m-d') === $_GET['date'] && $tanggalKandidat->format('Y-m') === $bulanTerpilih) {
		$tanggalTerpilih = $tanggalKandidat->format('Y-m-d');
	}
}
$pernyataan = $koneksi->prepare('SELECT DATE(created_at) AS attendance_date,COUNT(*) AS record_count FROM absensi WHERE user_id=? AND created_at>=? AND created_at<? GROUP BY DATE(created_at)');
$pernyataan->execute([$penggunaAktif['id'], $awalBulan->format('Y-m-d'), $akhirBulan]);
$absensiPerTanggal = [];
foreach ($pernyataan->fetchAll() as $catatanHarian) {
	$absensiPerTanggal[$catatanHarian['attendance_date']] = (int) $catatanHarian['record_count'];
}
$awalCatatan = $tanggalTerpilih ?? $awalBulan->format('Y-m-d');
$akhirCatatan = $tanggalTerpilih ? (new DateTimeImmutable($tanggalTerpilih))->modify('+1 day')->format('Y-m-d') : $akhirBulan;
$pernyataan = $koneksi->prepare('SELECT * FROM absensi WHERE user_id=? AND created_at>=? AND created_at<? ORDER BY created_at DESC');
$pernyataan->execute([$penggunaAktif['id'], $awalCatatan, $akhirCatatan]);
$daftarCatatan = $pernyataan->fetchAll();
$jumlahHariDalamBulan = (int) $awalBulan->format('t');
$jarakHariPertama = (int) $awalBulan->format('N') - 1;
$bulanSebelumnya = $awalBulan->modify('-1 month')->format('Y-m');
$nilaiBulanBerikutnya = $bulanBerikutnya->format('Y-m');
$judulRiwayat = $tanggalTerpilih
	? 'Absensi ' . format_tanggal_indonesia($tanggalTerpilih, 'd F Y')
	: 'Semua absensi · ' . format_tanggal_indonesia($awalBulan->format('Y-m-d'), 'F Y');
require __DIR__ . '/includes/baris_absensi.php';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<div class="history-layout">
	<aside class="panel history-calendar" aria-label="Kalender riwayat absensi">
		<div class="calendar-heading">
			<a class="calendar-nav" href="riwayat_absensi.php?month=<?= teks_aman($bulanSebelumnya) ?>" aria-label="Bulan sebelumnya">‹</a>
			<h2><?= teks_aman(format_tanggal_indonesia($awalBulan->format('Y-m-d'), 'F Y')) ?></h2>
			<a class="calendar-nav" href="riwayat_absensi.php?month=<?= teks_aman($nilaiBulanBerikutnya) ?>" aria-label="Bulan berikutnya">›</a>
		</div>
		<form method="get" class="history-month-form">
			<label for="history-month">Pilih bulan</label>
			<input id="history-month" type="month" name="month" value="<?= teks_aman($bulanTerpilih) ?>" required>
			<button class="button secondary small" type="submit">Tampilkan</button>
		</form>
		<div class="calendar-grid" role="grid" aria-label="<?= teks_aman(format_tanggal_indonesia($awalBulan->format('Y-m-d'), 'F Y')) ?>">
			<?php foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $hariPekan): ?><span class="calendar-weekday" role="columnheader"><?= $hariPekan ?></span><?php endforeach; ?>
			<?php for ($selKosong = 0; $selKosong < $jarakHariPertama; $selKosong++): ?><span class="calendar-empty" aria-hidden="true"></span><?php endfor; ?>
			<?php for ($hari = 1; $hari <= $jumlahHariDalamBulan; $hari++):
				$tanggalKalender = $awalBulan->setDate((int) $awalBulan->format('Y'), (int) $awalBulan->format('m'), $hari)->format('Y-m-d');
				$jumlahAbsensi = $absensiPerTanggal[$tanggalKalender] ?? 0;
				$kelasHari = ['calendar-day'];
				if ($jumlahAbsensi > 0) $kelasHari[] = 'has-attendance';
				if ($tanggalTerpilih === $tanggalKalender) $kelasHari[] = 'selected';
				if ($tanggalKalender === date('Y-m-d')) $kelasHari[] = 'today';
			?>
				<a class="<?= implode(' ', $kelasHari) ?>" href="riwayat_absensi.php?month=<?= teks_aman($bulanTerpilih) ?>&amp;date=<?= teks_aman($tanggalKalender) ?>" role="gridcell" aria-label="<?= teks_aman(format_tanggal_indonesia($tanggalKalender, 'd F Y')) ?><?= $jumlahAbsensi ? ', ' . $jumlahAbsensi . ' catatan absensi' : ', tidak ada catatan absensi' ?>" <?= $tanggalTerpilih === $tanggalKalender ? 'aria-current="date"' : '' ?>><?= $hari ?><?php if ($jumlahAbsensi): ?><span class="calendar-count"><?= $jumlahAbsensi ?></span><?php endif; ?></a>
			<?php endfor; ?>
		</div>
		<p class="calendar-legend"><span></span>Tanggal dengan absensi</p>
		<?php if ($tanggalTerpilih): ?><a class="calendar-reset" href="riwayat_absensi.php?month=<?= teks_aman($bulanTerpilih) ?>">Tampilkan seluruh bulan</a><?php endif; ?>
	</aside>
	<section class="panel table-panel history-table-panel">
		<div class="table-title"><p class="eyebrow">RIWAYAT PEGAWAI</p><h2><?= teks_aman($judulRiwayat) ?></h2><small><?= count($daftarCatatan) ?> catatan</small></div>
		<div class="table-wrap"><table><thead><tr><th>Tanggal & waktu</th><th>Jenis</th><th>Lokasi</th><th>Bukti</th></tr></thead><tbody>
		<?php if (!$daftarCatatan): ?><tr><td colspan="4" class="empty-state"><?= $tanggalTerpilih ? 'Tidak ada absensi pada tanggal ini.' : 'Belum ada riwayat absensi pada bulan ini.' ?></td></tr><?php else: foreach ($daftarCatatan as $catatan) tampilkan_baris_absensi($catatan); endif; ?>
		</tbody></table></div>
	</section>
</div>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
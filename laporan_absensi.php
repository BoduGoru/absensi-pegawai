<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');
require_once __DIR__ . '/includes/laporan_absensi_data.php';

try {
    $filterLaporan = filter_laporan_absensi();
} catch (InvalidArgumentException $kesalahan) {
    http_response_code(400);
    exit(teks_aman($kesalahan->getMessage()));
}
$daftarPegawai = daftar_pegawai_laporan($koneksi);
$barisLaporan = baris_laporan_absensi($koneksi, $filterLaporan);
$namaPegawaiTerpilih = label_pegawai_laporan($daftarPegawai, $filterLaporan['id_pegawai']);
$jumlahPegawai = count(array_unique(array_column($barisLaporan, 'id')));
$jumlahHariTercatat = count(array_unique(array_column($barisLaporan, 'tanggal')));
$jumlahMasuk = array_sum(array_map(static fn(array $baris): int => (int) $baris['jumlah_masuk'], $barisLaporan));
$jumlahPulang = array_sum(array_map(static fn(array $baris): int => (int) $baris['jumlah_pulang'], $barisLaporan));
$tautanEkspor = http_build_query([
    'bulan' => $filterLaporan['bulan'],
    'pegawai' => $filterLaporan['id_pegawai'] ?? '',
]);

$judulHalaman = 'Laporan absensi';
$halamanAktif = 'laporan';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<section class="panel report-filter-panel">
    <form method="get" class="report-filters">
        <label>Bulan laporan<input type="month" name="bulan" value="<?= teks_aman($filterLaporan['bulan']) ?>" required></label>
        <label>Pegawai<select name="pegawai"><option value="">Semua pegawai</option><?php foreach ($daftarPegawai as $pegawai): ?><option value="<?= (int) $pegawai['id'] ?>" <?= $filterLaporan['id_pegawai'] === (int) $pegawai['id'] ? 'selected' : '' ?>><?= teks_aman($pegawai['name']) ?><?= $pegawai['employee_no'] ? ' · ' . teks_aman($pegawai['employee_no']) : '' ?></option><?php endforeach; ?></select></label>
        <div class="report-actions"><button class="button primary" type="submit">Tampilkan laporan</button></div>
    </form>
</section>

<section class="report-summary" aria-label="Ringkasan laporan">
    <div class="panel report-metric"><span>Pegawai tercatat</span><strong><?= $jumlahPegawai ?></strong></div>
    <div class="panel report-metric"><span>Tanggal tercatat</span><strong><?= $jumlahHariTercatat ?></strong></div>
    <div class="panel report-metric"><span>Absensi masuk</span><strong><?= $jumlahMasuk ?></strong></div>
    <div class="panel report-metric"><span>Absensi pulang</span><strong><?= $jumlahPulang ?></strong></div>
</section>

<section class="panel table-panel report-table-panel">
    <div class="panel-header table-title report-title">
        <div><p class="eyebrow"><?= teks_aman(strtoupper(format_tanggal_indonesia($filterLaporan['bulan'] . '-01', 'F Y'))) ?> · <?= teks_aman($namaPegawaiTerpilih) ?></p><h2>Rekap per hari</h2></div>
        <div class="report-actions">
            <a class="button secondary" href="ekspor_laporan_excel.php?<?= teks_aman($tautanEkspor) ?>">Ekspor Excel</a>
            <a class="button secondary" href="ekspor_laporan_pdf.php?<?= teks_aman($tautanEkspor) ?>">Ekspor PDF</a>
        </div>
    </div>
    <div class="table-wrap"><table class="report-table"><thead><tr><th>Pegawai</th><th>Nomor pegawai</th><th>Tanggal</th><th>Masuk</th><th>Pulang</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
        <?php if (!$barisLaporan): ?><tr><td colspan="7" class="empty-state">Tidak ada catatan absensi untuk periode dan pegawai ini.</td></tr><?php else: foreach ($barisLaporan as $baris): ?>
            <tr>
                <td><strong><?= teks_aman($baris['name']) ?></strong></td>
                <td><?= teks_aman($baris['employee_no'] ?? '') ?></td>
                <td><?= teks_aman(format_tanggal_indonesia($baris['tanggal'], 'd M Y')) ?></td>
                <td><?= teks_aman($baris['jam_masuk'] ? substr($baris['jam_masuk'], 0, 8) : '—') ?></td>
                <td><?= teks_aman($baris['jam_pulang'] ? substr($baris['jam_pulang'], 0, 8) : '—') ?></td>
                <td><?= teks_aman($baris['status'] ?: 'Hadir') ?></td>
                <td><?= (int) $baris['jumlah_catatan'] ?></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
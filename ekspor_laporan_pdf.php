<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');
require_once __DIR__ . '/includes/laporan_absensi_data.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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
$jumlahTanggal = count(array_unique(array_column($barisLaporan, 'tanggal')));
$jumlahMasuk = array_sum(array_map(static fn(array $baris): int => (int) $baris['jumlah_masuk'], $barisLaporan));
$jumlahPulang = array_sum(array_map(static fn(array $baris): int => (int) $baris['jumlah_pulang'], $barisLaporan));
$html = '<!doctype html><html lang="id"><head><meta charset="utf-8"><style>
    @page{margin:26px 28px}body{font-family:DejaVu Sans,sans-serif;color:#1f2d3d;font-size:9px}
    h1{font-size:18px;margin:0 0 5px;color:#083a74}.meta{color:#526274;margin:3px 0}
    .summary{margin:16px 0 12px;padding:9px;background:#edf5ff;color:#083a74;font-size:9px}
    table{width:100%;border-collapse:collapse}th{background:#174678;color:#fff;text-align:left;font-size:8px}
    th,td{padding:7px 6px;border:1px solid #dfe9f5}tr:nth-child(even) td{background:#f5f8fc}
    .empty{text-align:center;color:#66758a;padding:18px}.footer{margin-top:14px;color:#66758a;font-size:8px}
    </style></head><body>';
$html .= '<h1>Laporan Absensi Pegawai</h1><p class="meta">Periode: ' . teks_aman(format_tanggal_indonesia($filterLaporan['bulan'] . '-01', 'F Y')) . '</p>';
$html .= '<p class="meta">Pegawai: ' . teks_aman($namaPegawaiTerpilih) . '</p>';
$html .= '<div class="summary">' . $jumlahPegawai . ' pegawai tercatat &nbsp; | &nbsp; ' . $jumlahTanggal . ' tanggal tercatat &nbsp; | &nbsp; ' . $jumlahMasuk . ' absensi masuk &nbsp; | &nbsp; ' . $jumlahPulang . ' absensi pulang</div>';
$html .= '<table><thead><tr><th>Pegawai</th><th>Nomor Pegawai</th><th>Tanggal</th><th>Masuk</th><th>Pulang</th><th>Status</th><th>Catatan</th></tr></thead><tbody>';
if (!$barisLaporan) {
    $html .= '<tr><td class="empty" colspan="7">Tidak ada catatan absensi untuk periode dan pegawai ini.</td></tr>';
} else {
    foreach ($barisLaporan as $baris) {
        $html .= '<tr><td>' . teks_aman($baris['name']) . '</td><td>' . teks_aman($baris['employee_no'] ?? '') . '</td>';
        $html .= '<td>' . teks_aman(format_tanggal_indonesia($baris['tanggal'], 'd M Y')) . '</td>';
        $html .= '<td>' . teks_aman($baris['jam_masuk'] ? substr($baris['jam_masuk'], 0, 8) : '-') . '</td>';
        $html .= '<td>' . teks_aman($baris['jam_pulang'] ? substr($baris['jam_pulang'], 0, 8) : '-') . '</td>';
        $html .= '<td>' . teks_aman($baris['status'] ?: 'Hadir') . '</td><td>' . (int) $baris['jumlah_catatan'] . '</td></tr>';
    }
}
$html .= '</tbody></table><p class="footer">Dicetak pada ' . teks_aman(format_tanggal_indonesia(date('Y-m-d'), 'd F Y')) . ' · Presensi Satker NTT</p></body></html>';
$opsi = new Options();
$opsi->set('defaultFont', 'DejaVu Sans');
$opsi->set('isRemoteEnabled', false);
$pdf = new Dompdf($opsi);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'landscape');
$pdf->render();
$namaBerkas = 'laporan-absensi-' . $filterLaporan['bulan'] . '-' . ($filterLaporan['id_pegawai'] ?? 'semua') . '.pdf';
$pdf->stream($namaBerkas, ['Attachment' => true]);
exit;
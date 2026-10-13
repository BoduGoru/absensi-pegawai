<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('ringkasan_pegawai.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');
require_once __DIR__ . '/includes/laporan_absensi_data.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $filterLaporan = filter_laporan_absensi();
} catch (InvalidArgumentException $kesalahan) {
    http_response_code(400);
    exit(teks_aman($kesalahan->getMessage()));
}
$daftarPegawai = daftar_pegawai_laporan($koneksi);
$barisLaporan = baris_laporan_absensi($koneksi, $filterLaporan);
$namaPegawaiTerpilih = label_pegawai_laporan($daftarPegawai, $filterLaporan['id_pegawai']);
$bukuKerja = new Spreadsheet();
$lembar = $bukuKerja->getActiveSheet();
$lembar->setTitle('Laporan Absensi');
$lembar->mergeCells('A1:H1');
$lembar->setCellValue('A1', 'LAPORAN ABSENSI PEGAWAI');
$lembar->mergeCells('A2:H2');
$lembar->setCellValue('A2', 'Periode: ' . format_tanggal_indonesia($filterLaporan['bulan'] . '-01', 'F Y'));
$lembar->mergeCells('A3:H3');
$lembar->setCellValue('A3', 'Pegawai: ' . $namaPegawaiTerpilih);
$lembar->fromArray(['No', 'Nomor Pegawai', 'Nama Pegawai', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status', 'Jumlah Catatan'], null, 'A5');
$lembar->getStyle('A1:H1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D4E9D']],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
]);
$lembar->getStyle('A2:H3')->applyFromArray(['font' => ['color' => ['rgb' => '40534C']]]);
$lembar->getStyle('A5:H5')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '174678']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$nomorBaris = 6;
foreach ($barisLaporan as $indeks => $baris) {
    $nilaiBaris = [
        $indeks + 1,
        (string) ($baris['employee_no'] ?? ''),
        (string) $baris['name'],
        format_tanggal_indonesia($baris['tanggal'], 'd M Y'),
        $baris['jam_masuk'] ? substr($baris['jam_masuk'], 0, 8) : '',
        $baris['jam_pulang'] ? substr($baris['jam_pulang'], 0, 8) : '',
        (string) ($baris['status'] ?: 'Hadir'),
        (int) $baris['jumlah_catatan'],
    ];
    foreach ($nilaiBaris as $indeksKolom => $nilai) {
        $sel = $lembar->getCell(Coordinate::stringFromColumnIndex($indeksKolom + 1) . $nomorBaris);
        if (in_array($indeksKolom, [1, 2, 3, 4, 5, 6], true)) {
            $sel->setValueExplicit($nilai, DataType::TYPE_STRING);
        } else {
            $sel->setValue($nilai);
        }
    }
    $nomorBaris++;
}
foreach (range('A', 'H') as $kolom) {
    $lembar->getColumnDimension($kolom)->setAutoSize(true);
}
$lembar->freezePane('A6');
$lembar->setAutoFilter('A5:H' . max(5, $nomorBaris - 1));
$namaBerkas = 'laporan-absensi-' . $filterLaporan['bulan'] . '-' . ($filterLaporan['id_pegawai'] ?? 'semua') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $namaBerkas . '"');
header('Cache-Control: max-age=0, no-cache, must-revalidate');
(new Xlsx($bukuKerja))->save('php://output');
$bukuKerja->disconnectWorksheets();
exit;
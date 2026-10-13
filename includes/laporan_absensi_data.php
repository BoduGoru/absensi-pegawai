<?php
declare(strict_types=1);

function filter_laporan_absensi(): array
{
    $bulan = $_GET['bulan'] ?? date('Y-m');
    if (!is_string($bulan) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
        throw new InvalidArgumentException('Bulan laporan tidak valid.');
    }
    $awalBulan = DateTimeImmutable::createFromFormat('!Y-m', $bulan);
    if (!$awalBulan || $awalBulan->format('Y-m') !== $bulan) {
        throw new InvalidArgumentException('Bulan laporan tidak valid.');
    }

    $nilaiPegawai = $_GET['pegawai'] ?? '';
    if (!is_string($nilaiPegawai) && !is_int($nilaiPegawai)) {
        throw new InvalidArgumentException('Pilihan pegawai tidak valid.');
    }
    $idPegawai = $nilaiPegawai === '' ? null : filter_var($nilaiPegawai, FILTER_VALIDATE_INT);
    if ($idPegawai === false || ($idPegawai !== null && $idPegawai < 1)) {
        throw new InvalidArgumentException('Pilihan pegawai tidak valid.');
    }

    return [
        'bulan' => $bulan,
        'id_pegawai' => $idPegawai,
        'mulai' => $awalBulan->format('Y-m-d 00:00:00'),
        'sampai' => $awalBulan->modify('+1 month')->format('Y-m-d 00:00:00'),
    ];
}

function daftar_pegawai_laporan(PDO $koneksi): array
{
    return $koneksi->query("SELECT id,employee_no,name FROM pengguna WHERE role='pegawai' ORDER BY name")->fetchAll();
}

function baris_laporan_absensi(PDO $koneksi, array $filter): array
{
    $filterPegawai = $filter['id_pegawai'] === null ? '' : ' AND u.id=?';
    $parameter = [$filter['mulai'], $filter['sampai']];
    if ($filter['id_pegawai'] !== null) {
        $parameter[] = $filter['id_pegawai'];
    }

    $kueri = "SELECT
            u.id,u.employee_no,u.name,DATE(a.created_at) AS tanggal,
            MIN(CASE WHEN a.kind='check_in' THEN TIME(a.created_at) END) AS jam_masuk,
            MAX(CASE WHEN a.kind='check_out' THEN TIME(a.created_at) END) AS jam_pulang,
            GROUP_CONCAT(DISTINCT CASE a.kind
                WHEN 'izin' THEN 'Izin'
                WHEN 'cuti' THEN 'Cuti'
                WHEN 'sakit' THEN 'Sakit'
                WHEN 'perjadin' THEN 'Perjalanan Dinas'
                ELSE NULL
            END ORDER BY a.kind SEPARATOR ', ') AS status,
            COUNT(*) AS jumlah_catatan,
            SUM(a.kind='check_in') AS jumlah_masuk,
            SUM(a.kind='check_out') AS jumlah_pulang
        FROM absensi a
        JOIN pengguna u ON u.id=a.user_id
        WHERE u.role='pegawai' AND a.created_at>=? AND a.created_at<? $filterPegawai
        GROUP BY u.id,u.employee_no,u.name,DATE(a.created_at)
        ORDER BY u.name, tanggal";
    $pernyataan = $koneksi->prepare($kueri);
    $pernyataan->execute($parameter);
    return $pernyataan->fetchAll();
}

function label_pegawai_laporan(array $daftarPegawai, ?int $idPegawai): string
{
    if ($idPegawai === null) {
        return 'Semua pegawai';
    }
    foreach ($daftarPegawai as $pegawai) {
        if ((int) $pegawai['id'] === $idPegawai) {
            return $pegawai['name'] . ($pegawai['employee_no'] ? ' (' . $pegawai['employee_no'] . ')' : '');
        }
    }
    return 'Pegawai tidak ditemukan';
}
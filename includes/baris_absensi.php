<?php
function label_jenis_absensi(string $jenis): string
{
    $label = [
        'check_in' => 'Masuk',
        'check_out' => 'Pulang',
        'izin' => 'Izin',
        'cuti' => 'Cuti',
        'sakit' => 'Sakit',
        'perjadin' => 'Perjalanan Dinas',
    ];
    return $label[$jenis] ?? ucfirst(str_replace('_', ' ', $jenis));
}

function tautan_bukti(array $catatan): string
{
    $identitas = (int) $catatan['id'];
    return '<a class="evidence-link" href="?action=evidence&id=' . $identitas . '&kind=selfie">Foto</a>'
        . '<a class="evidence-link" href="?action=evidence&id=' . $identitas . '&kind=video">Video</a>';
}

function tampilkan_baris_absensi(array $catatan, bool $sertakanPegawai = false): void
{
    ?>
    <tr>
        <?php if ($sertakanPegawai): ?>
            <td><strong><?= teks_aman($catatan['name']) ?></strong><small><?= teks_aman($catatan['employee_no']) ?></small></td>
        <?php endif; ?>
        <td><strong><?= teks_aman(format_tanggal_indonesia($catatan['created_at'], 'd M Y')) ?></strong><small><?= teks_aman(date('H:i:s', strtotime($catatan['created_at']))) ?> WITA</small></td>
        <td><span class="kind-pill <?= teks_aman($catatan['kind']) ?>"><?= label_jenis_absensi($catatan['kind']) ?></span></td>
        <td><a class="text-link" target="_blank" rel="noopener" href="https://maps.google.com/?q=<?= teks_aman($catatan['latitude'] . ',' . $catatan['longitude']) ?>">Lihat peta ↗</a><small>±<?= teks_aman((string) round((float) $catatan['accuracy'])) ?> m</small></td>
        <td><?= tautan_bukti($catatan) ?></td>
    </tr>
    <?php
}
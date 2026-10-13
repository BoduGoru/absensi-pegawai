<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'admin') alihkan_halaman('employee_ringkasan_admin.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$judulHalaman = 'Data pegawai';
$halamanAktif = 'employees';
$pencarian = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? 'all');
$syarat = ["role='pegawai'"];
$parameter = [];
if ($pencarian !== '') {
    $syarat[] = '(name LIKE ? OR employee_no LIKE ? OR username LIKE ? OR position LIKE ?)';
    $kataPencarian = '%' . $pencarian . '%';
    array_push($parameter, $kataPencarian, $kataPencarian, $kataPencarian, $kataPencarian);
}
if ($status === 'active' || $status === 'inactive') {
    $syarat[] = 'is_active = ?';
    $parameter[] = $status === 'active' ? 1 : 0;
}
$pernyataan = $koneksi->prepare('SELECT id,employee_no,name,username,position,address,phone,profile_photo,is_active FROM pengguna WHERE ' . implode(' AND ', $syarat) . ' ORDER BY name');
$pernyataan->execute($parameter);
$daftarPegawai = $pernyataan->fetchAll();
require __DIR__ . '/includes/kerangka_awal.php';
?>
<div class="admin-layout">
    <section class="panel table-panel">
        <div class="employee-toolbar">
            <div><p class="eyebrow">AKUN TERDAFTAR</p><h2><?= count($daftarPegawai) ?> pegawai</h2></div>
            <a class="button primary small" href="pegawai_tambah.php">Tambah pegawai</a>
            <form class="employee-filters" method="get">
                <label class="search-field"><span class="sr-only">Cari pegawai</span><input type="search" name="q" value="<?= teks_aman($pencarian) ?>" placeholder="Cari nama, nomor, jabatan"></label>
                <label><span class="sr-only">Filter status</span><select name="status"><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua status</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option><option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option></select></label>
                <button class="button secondary small" type="submit">Cari</button>
                <?php if ($pencarian !== '' || $status !== 'all'): ?><a class="text-link" href="daftar_pegawai.php">Reset</a><?php endif; ?>
            </form>
        </div>
        <div class="table-wrap"><table><thead><tr><th>Nama pegawai</th><th>Jabatan / status kepegawaian</th><th>Nomor pegawai</th><th>Username</th><th>Status akun</th><th></th></tr></thead><tbody>
        <?php if (!$daftarPegawai): ?><tr><td colspan="6" class="empty-state">Belum ada akun pegawai.</td></tr><?php else: foreach ($daftarPegawai as $pegawai): ?>
            <tr>
                <td><?php if ($pegawai['profile_photo']): ?><img class="employee-avatar" src="includes/inisialisasi.php?action=profile_photo&amp;id=<?= (int) $pegawai['id'] ?>" alt="Foto <?= teks_aman($pegawai['name']) ?>"><?php endif; ?><strong><?= teks_aman($pegawai['name']) ?></strong></td>
            <td><?= teks_aman($pegawai['position'] ?: 'Belum diisi') ?></td>
                <td><?= teks_aman($pegawai['employee_no']) ?></td>
                <td><?= teks_aman($pegawai['username']) ?></td>
                <td><span class="status-pill <?= $pegawai['is_active'] ? 'active' : 'inactive' ?>"><?= $pegawai['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td><details class="employee-tools"><summary class="text-button">Kelola</summary><div class="employee-tools-panel">
                    <a class="text-link" href="pegawai_ubah.php?id=<?= (int) $pegawai['id'] ?>">Ubah data pegawai</a>
                    <a class="text-link" href="pegawai_kata_sandi.php?id=<?= (int) $pegawai['id'] ?>">Atur kata sandi</a>
                    <form method="post" class="employee-status-form"><input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>"><input type="hidden" name="aksi_formulir" value="ubah_status_pegawai"><input type="hidden" name="id_pegawai" value="<?= (int) $pegawai['id'] ?>"><button class="text-button" type="submit"><?= $pegawai['is_active'] ? 'Nonaktifkan akun' : 'Aktifkan akun' ?></button></form>
                </div></details></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
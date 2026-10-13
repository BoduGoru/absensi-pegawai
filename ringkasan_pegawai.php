<?php
require_once __DIR__ . '/includes/inisialisasi.php';
if (!$penggunaAktif) alihkan_halaman('masuk.php');
if ($penggunaAktif['role'] !== 'pegawai') alihkan_halaman('ringkasan_admin.php');
if ($penggunaAktif['must_change_password']) alihkan_halaman('ubah_kata_sandi.php');

$judulHalaman = 'Selamat datang, ' . $penggunaAktif['name'];
$halamanAktif = 'ringkasan';
require __DIR__ . '/includes/baris_absensi.php';
$pernyataan = $koneksi->prepare('SELECT * FROM absensi WHERE user_id=? ORDER BY created_at DESC');
$pernyataan->execute([$penggunaAktif['id']]);
$daftarCatatan = $pernyataan->fetchAll();
$pernyataan = $koneksi->prepare('SELECT * FROM absensi WHERE user_id=? AND DATE(created_at)=CURDATE() ORDER BY id');
$pernyataan->execute([$penggunaAktif['id']]);
$daftarCatatanHariIni = $pernyataan->fetchAll();
$absensiDiubah = null;
$idAbsensiDiminta = filter_var($_GET['edit_attendance'] ?? null, FILTER_VALIDATE_INT);
if ($idAbsensiDiminta) {
    foreach ($daftarCatatanHariIni as $catatanHariIni) {
        if ((int) $catatanHariIni['id'] === $idAbsensiDiminta) {
            $absensiDiubah = $catatanHariIni;
            break;
        }
    }
}
$sedangAbsenUlang = $absensiDiubah !== null;
$jenisHariIni = array_column($daftarCatatanHariIni, 'kind');
$catatanTerakhirHariIni = $daftarCatatanHariIni ? $daftarCatatanHariIni[count($daftarCatatanHariIni) - 1] : null;
$sudahAbsenMasuk = in_array('check_in', $jenisHariIni, true);
$sudahAbsenPulang = in_array('check_out', $jenisHariIni, true);
$adaPerjalananDinas = in_array('perjadin', $jenisHariIni, true);
$adaStatusLain = (bool) array_intersect(['izin', 'cuti', 'sakit'], $jenisHariIni);
$dapatAbsenMasuk = !$daftarCatatanHariIni;
$dapatAbsenPulang = !$sudahAbsenPulang && !$adaStatusLain && ($sudahAbsenMasuk || $adaPerjalananDinas);
$batasAbsenMasuk = strtotime(date('Y-m-d') . ' 08:30:00');
$waktuAbsenPulang = strtotime(date('Y-m-d') . ' 16:00:00');
$tampilkanFormAbsensi = $sedangAbsenUlang || $dapatAbsenMasuk || ($dapatAbsenPulang && time() >= $waktuAbsenPulang);
$targetHitungMundur = $dapatAbsenMasuk ? $batasAbsenMasuk : $waktuAbsenPulang;
$menitHitungMundur = max(0, (int) ceil(($targetHitungMundur - time()) / 60));
$teksHitungMundur = $menitHitungMundur >= 60
    ? (int) floor($menitHitungMundur / 60) . ' jam ' . ($menitHitungMundur % 60) . ' menit'
    : $menitHitungMundur . ' menit';
require __DIR__ . '/includes/kerangka_awal.php';
?>
<div class="employee-grid">
    <section class="panel attendance-panel">
        <div class="panel-header"><div><p class="eyebrow">PRESENSI HARI INI</p><h2><?= $sedangAbsenUlang ? 'Absen ulang' : ($dapatAbsenMasuk ? 'Catat waktu masuk' : ($dapatAbsenPulang ? 'Catat waktu pulang' : 'Presensi hari ini')) ?></h2></div><span class="live-tag"><span></span>Hari ini</span></div>
        <?php if ($sedangAbsenUlang): ?>
            <div class="notice success" role="status">Ambil ulang bukti untuk <?= teks_aman(label_jenis_absensi($absensiDiubah['kind'])) ?>. Foto, video, dan lokasi sebelumnya akan diganti.</div>
        <?php elseif ($dapatAbsenMasuk): ?>
            <div class="notice success" role="status"><strong>Pengingat absen masuk.</strong> <?php if (time() < $batasAbsenMasuk): ?>Batas absen masuk pukul 08.30, tersisa <?= teks_aman($teksHitungMundur) ?>.<?php else: ?>Batas pukul 08.30 telah lewat. Anda tetap dapat absen masuk dan rekamannya akan tersimpan.<?php endif; ?></div>
        <?php elseif ($dapatAbsenPulang && time() < $waktuAbsenPulang): ?>
            <div class="notice success" role="status"><strong>Absen pulang belum tersedia.</strong> Dapat dilakukan pukul 16.00, tersisa <?= teks_aman($teksHitungMundur) ?>.</div>
        <?php elseif ($dapatAbsenPulang): ?>
            <div class="notice success" role="status"><strong>Pengingat absen pulang.</strong> Anda sudah dapat melakukan absen pulang.</div>
        <?php endif; ?>
        <?php if (!$tampilkanFormAbsensi): ?>
            <?php if (!$dapatAbsenPulang): ?><div class="complete-state"><span class="complete-icon">✓</span><div><strong>Presensi hari ini sudah dicatat</strong><p><?= teks_aman(label_jenis_absensi($catatanTerakhirHariIni['kind'])) ?> · pukul <?= teks_aman(date('H:i', strtotime($catatanTerakhirHariIni['created_at']))) ?></p></div></div><?php endif; ?>
        <?php else: ?>
            <p class="muted guidance">Lengkapi bukti foto wajah, video singkat, dan lokasi perangkat.</p>
            <form id="attendance-form" method="post" enctype="multipart/form-data" data-challenge="<?= teks_aman(['Kedipkan mata beberapa kali','Tengok ke kiri lalu ke kanan','Anggukkan kepala perlahan'][random_int(0, 2)]) ?>">
                <input type="hidden" name="token_csrf" value="<?= teks_aman(token_csrf()) ?>"><input type="hidden" name="aksi_formulir" value="<?= $sedangAbsenUlang ? 'absen_ulang' : 'absensi' ?>">
                <?php if ($sedangAbsenUlang): ?><input type="hidden" name="id_absensi" value="<?= (int) $absensiDiubah['id'] ?>"><?php endif; ?>
                <input type="hidden" name="lintang"><input type="hidden" name="bujur"><input type="hidden" name="akurasi">
                <?php if ($sedangAbsenUlang): ?>
                    <label class="attendance-kind-field">
                        <span>Jenis absensi</span>
                        <select name="jenis_absensi" required>
                            <?php foreach (['check_in','check_out','izin','cuti','sakit','perjadin'] as $pilihanJenis): ?>
                                <option value="<?= teks_aman($pilihanJenis) ?>" <?= $absensiDiubah['kind'] === $pilihanJenis ? 'selected' : '' ?>><?= teks_aman(label_jenis_absensi($pilihanJenis)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php elseif ($dapatAbsenMasuk): ?>
                    <label class="attendance-kind-field">
                        <span>Jenis absensi</span>
                        <select name="jenis_absensi" required>
                            <option value="check_in">Masuk</option>
                            <option value="izin">Izin</option>
                            <option value="cuti">Cuti</option>
                            <option value="sakit">Sakit</option>
                            <option value="perjadin">Perjalanan dinas</option>
                        </select>
                    </label>
                <?php else: ?>
                    <input type="hidden" name="jenis_absensi" value="check_out">
                <?php endif; ?>
                <input class="file-input" type="file" name="swafoto" accept="image/jpeg,image/png,image/webp" required>
                <input class="file-input" type="file" name="video" accept="video/webm,video/mp4,video/ogg" required>
                <div class="camera-controls"><label for="camera-select">Perangkat kamera</label><select id="camera-select" disabled><option value="">Kamera utama</option></select><button class="button secondary small" id="switch-camera" type="button" hidden>Ganti kamera</button></div>
                <div class="camera-stage"><video id="camera-preview" autoplay muted playsinline hidden></video><div id="camera-placeholder"><span class="camera-symbol">◎</span><span id="camera-status">Kamera belum diaktifkan</span></div><img id="selfie-preview" alt="Pratinjau foto wajah" hidden></div>
                <div class="capture-steps">
                    <div class="capture-step"><span class="step-number">1</span><div><strong>Foto wajah</strong><small id="photo-status">Ambil foto langsung dari kamera</small></div><div class="capture-actions"><button type="button" class="button secondary small" id="preview-photo" hidden>Lihat foto</button><button type="button" class="button secondary small" id="capture-photo">Ambil foto</button></div></div>
                    <div class="capture-step"><span class="step-number">2</span><div><strong>Video liveness · 4 detik</strong><small id="video-status">Ikuti instruksi gerakan</small></div><div class="capture-actions"><button type="button" class="button secondary small" id="preview-video" hidden>Lihat video</button><button type="button" class="button secondary small" id="record-video">Rekam video</button></div></div>
                    <p class="challenge" id="challenge-text"></p>
                    <div class="capture-step"><span class="step-number">3</span><div><strong>Lokasi perangkat</strong><small id="location-status">Lokasi belum diambil</small></div><button type="button" class="button secondary small" id="get-location">Ambil lokasi</button></div>
                </div>
                <div class="evidence-preview" id="evidence-preview" hidden><img id="evidence-photo" alt="Pratinjau foto absensi" hidden><video id="evidence-video" controls playsinline hidden></video></div>
                <div class="submit-row"><p class="fine-print">Izin kamera dan lokasi diperlukan. Bukti hanya dapat dilihat pegawai dan admin.</p><button class="button primary" id="submit-attendance" disabled><?= $sedangAbsenUlang ? 'Simpan absen ulang' : 'Kirim absensi' ?> <span>→</span></button></div>
            </form>
        <?php endif; ?>
        <?php foreach ($daftarCatatanHariIni as $catatanHariIni): ?>
            <form method="get" class="attendance-edit attendance-edit-form" data-konfirmasi="Anda yakin ingin melakukan absen ulang?">
                <input type="hidden" name="edit_attendance" value="<?= (int) $catatanHariIni['id'] ?>">
                <button class="button secondary small" type="submit">Edit absen · <?= teks_aman(label_jenis_absensi($catatanHariIni['kind'])) ?> · <?= teks_aman(date('H:i', strtotime($catatanHariIni['created_at']))) ?></button>
            </form>
        <?php endforeach; ?>
    </section>
    <aside class="employee-side">
        <section class="panel profile-panel"><p class="eyebrow">PROFIL PEGAWAI</p><?php if (!empty($penggunaAktif['profile_photo'])): ?><img class="profile-avatar profile-avatar-photo" src="includes/inisialisasi.php?action=profile_photo&amp;id=<?= (int) $penggunaAktif['id'] ?>" alt="Foto profil"><?php else: ?><div class="profile-avatar"><?= teks_aman(strtoupper(substr($penggunaAktif['name'], 0, 1))) ?></div><?php endif; ?><h2><?= teks_aman($penggunaAktif['name']) ?></h2><p class="muted"><?= teks_aman($penggunaAktif['employee_no'] ?: 'Nomor pegawai belum tersedia') ?></p><a class="text-link" href="profil.php">Edit profil</a><div class="profile-divider"></div><div class="profile-meta"><span>Status hari ini</span><strong><?= $dapatAbsenMasuk ? 'Belum absen' : ($sudahAbsenMasuk ? ($sudahAbsenPulang ? 'Sudah masuk dan pulang' : 'Sudah masuk') : ($adaPerjalananDinas && !$sudahAbsenPulang ? 'Perjalanan dinas' : 'Lengkap')) ?></strong></div></section>
        <section class="panel recent-panel"><div class="panel-header"><div><p class="eyebrow">AKTIVITAS TERBARU</p><h2>Riwayat singkat</h2></div><a class="text-link" href="riwayat_absensi.php">Lihat semua</a></div>
            <?php if (!$daftarCatatan): ?><p class="empty-state">Belum ada riwayat absensi.</p><?php else: foreach (array_slice($daftarCatatan, 0, 4) as $catatan): ?><div class="mini-record"><span class="record-mark <?= teks_aman($catatan['kind']) ?>"></span><span><strong><?= teks_aman(label_jenis_absensi($catatan['kind'])) ?></strong><small><?= teks_aman(format_tanggal_indonesia($catatan['created_at'], 'd M Y')) ?> · <?= teks_aman(date('H:i', strtotime($catatan['created_at']))) ?></small></span><span class="record-check">✓</span></div><?php endforeach; endif; ?>
        </section>
    </aside>
</div>
<?php require __DIR__ . '/includes/kerangka_akhir.php'; ?>
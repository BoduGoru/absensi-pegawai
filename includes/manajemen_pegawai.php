<?php
declare(strict_types=1);

function hapus_akun_pegawai(PDO $koneksi, int $idPegawai): int
{
    if ($idPegawai < 1) {
        throw new InvalidArgumentException('Akun pegawai yang akan dihapus tidak valid.');
    }

    $daftarBerkas = [];
    $koneksi->beginTransaction();
    try {
        $pernyataanPegawai = $koneksi->prepare("SELECT profile_photo FROM pengguna WHERE id=? AND role='pegawai' FOR UPDATE");
        $pernyataanPegawai->execute([$idPegawai]);
        $fotoProfil = $pernyataanPegawai->fetchColumn();
        if ($fotoProfil === false) {
            throw new RuntimeException('Akun pegawai tidak ditemukan.');
        }
        if (is_string($fotoProfil) && $fotoProfil !== '') {
            $daftarBerkas[] = $fotoProfil;
        }

        $pernyataanBukti = $koneksi->prepare('SELECT selfie_path,video_path FROM absensi WHERE user_id=? FOR UPDATE');
        $pernyataanBukti->execute([$idPegawai]);
        foreach ($pernyataanBukti->fetchAll() as $bukti) {
            $daftarBerkas[] = $bukti['selfie_path'];
            $daftarBerkas[] = $bukti['video_path'];
        }

        $koneksi->prepare('DELETE FROM absensi WHERE user_id=?')->execute([$idPegawai]);
        $pernyataanHapusPegawai = $koneksi->prepare("DELETE FROM pengguna WHERE id=? AND role='pegawai'");
        $pernyataanHapusPegawai->execute([$idPegawai]);
        if ($pernyataanHapusPegawai->rowCount() !== 1) {
            throw new RuntimeException('Akun pegawai tidak dapat dihapus.');
        }
        $koneksi->commit();
    } catch (Throwable $kesalahan) {
        if ($koneksi->inTransaction()) $koneksi->rollBack();
        throw $kesalahan;
    }

    $jumlahBerkasGagal = 0;
    foreach (array_unique($daftarBerkas) as $namaBerkas) {
        $namaAman = basename((string) $namaBerkas);
        $jalurBerkas = DIREKTORI_PENYIMPANAN . DIRECTORY_SEPARATOR . $namaAman;
        if ($namaAman !== '' && is_file($jalurBerkas) && !@unlink($jalurBerkas)) {
            $jumlahBerkasGagal++;
        }
    }
    if ($jumlahBerkasGagal > 0) {
        error_log('Sebagian berkas bukti gagal dihapus setelah akun pegawai dihapus.');
    }
    return $jumlahBerkasGagal;
}

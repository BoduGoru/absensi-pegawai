(() => {
    document.querySelectorAll('form[data-konfirmasi]').forEach((formulirKonfirmasi) => {
        formulirKonfirmasi.addEventListener('submit', (peristiwaFormulir) => {
            if (!window.confirm(formulirKonfirmasi.dataset.konfirmasi)) peristiwaFormulir.preventDefault();
        });
    });

    const formulir = document.querySelector('#attendance-form');
    if (!formulir) return;

    const pratinjauKamera = document.querySelector('#camera-preview');
    const penandaKamera = document.querySelector('#camera-placeholder');
    const statusKamera = document.querySelector('#camera-status');
    const pilihanKamera = document.querySelector('#camera-select');
    const tombolGantiKamera = document.querySelector('#switch-camera');
    const pratinjauSwafoto = document.querySelector('#selfie-preview');
    const pratinjauBukti = document.querySelector('#evidence-preview');
    const fotoBukti = document.querySelector('#evidence-photo');
    const videoBukti = document.querySelector('#evidence-video');
    const tombolPratinjauFoto = document.querySelector('#preview-photo');
    const tombolPratinjauVideo = document.querySelector('#preview-video');
    const tombolAmbilFoto = document.querySelector('#capture-photo');
    const masukanSwafoto = formulir.elements.swafoto;
    const masukanVideo = formulir.elements.video;
    const tombolKirim = document.querySelector('#submit-attendance');
    const tombolRekam = document.querySelector('#record-video');
    let aliranKamera;
    let idKameraTerpilih = '';
    let idKameraAktif = '';
    let lokasiTersedia = false;
    let perekamVideo = null;
    let pewaktuRekaman = null;
    let sedangMerekam = false;
    let urlFoto = '';
    let urlVideo = '';

    document.querySelector('#challenge-text').textContent = `Instruksi video: ${formulir.dataset.challenge}.`;
    function aturStatusTombolRekam(statusPerekaman, pesanStatus) {
        const elemenStatus = document.querySelector('#video-status');
        sedangMerekam = statusPerekaman;
        tombolRekam.classList.toggle('recording', statusPerekaman);
        tombolRekam.textContent = statusPerekaman ? 'Stop rekam' : (masukanVideo.files.length ? 'Rekam ulang video' : 'Rekam video');
        tombolRekam.setAttribute('aria-pressed', String(statusPerekaman));
        elemenStatus.textContent = pesanStatus || (statusPerekaman ? 'Rekaman sedang berjalan...' : 'Belum mulai merekam');
        elemenStatus.classList.toggle('recording', statusPerekaman);
    }
    aturStatusTombolRekam(false, 'Belum mulai merekam');
    const perbaruiTombolKirim = () => { tombolKirim.disabled = !(masukanSwafoto.files.length && masukanVideo.files.length && lokasiTersedia); };
    function tampilkanBukti(jenisPratinjau) {
        const fotoDitampilkan = jenisPratinjau === 'photo' && fotoBukti.hidden;
        const videoDitampilkan = jenisPratinjau === 'video' && videoBukti.hidden;
        fotoBukti.hidden = !fotoDitampilkan;
        videoBukti.hidden = !videoDitampilkan;
        pratinjauBukti.hidden = !(fotoDitampilkan || videoDitampilkan);
        tombolPratinjauFoto.textContent = fotoDitampilkan ? 'Tutup foto' : 'Lihat foto';
        tombolPratinjauVideo.textContent = videoDitampilkan ? 'Tutup video' : 'Lihat video';
        if (!videoDitampilkan) videoBukti.pause();
    }
    function pesanKesalahanKamera(kesalahanKamera) {
        if (!window.isSecureContext) return 'Kamera diblokir karena halaman bukan koneksi aman. Di HP, localhost berarti HP itu sendiri; gunakan situs HTTPS untuk mengakses server laptop.';
        if (kesalahanKamera?.name === 'NotAllowedError' || kesalahanKamera?.name === 'PermissionDeniedError') return 'Izin kamera ditolak. Izinkan kamera untuk situs ini dari ikon gembok/pengaturan situs, lalu muat ulang.';
        if (kesalahanKamera?.name === 'NotFoundError' || kesalahanKamera?.name === 'DevicesNotFoundError') return 'Kamera tidak ditemukan. Pastikan perangkat kamera terhubung dan terdeteksi sistem.';
        if (kesalahanKamera?.name === 'NotReadableError' || kesalahanKamera?.name === 'TrackStartError') return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi seperti Zoom/Teams, lalu coba lagi.';
        if (kesalahanKamera?.name === 'OverconstrainedError' || kesalahanKamera?.name === 'ConstraintNotSatisfiedError') return 'Kamera tidak mendukung pengaturan ini. Pilih perangkat kamera lain.';
        return kesalahanKamera?.message || 'Kamera gagal dibuka. Periksa izin kamera dan pengaturan browser.';
    }

    async function daftarKamera() {
        if (!navigator.mediaDevices?.enumerateDevices) return;
        const nilaiSebelumnya = idKameraTerpilih || pilihanKamera.value;
        const daftarPerangkat = (await navigator.mediaDevices.enumerateDevices()).filter((perangkatKamera) => perangkatKamera.kind === 'videoinput');
        pilihanKamera.replaceChildren();
        daftarPerangkat.forEach((perangkatKamera, indeks) => {
            const pilihan = document.createElement('option');
            pilihan.value = perangkatKamera.deviceId;
            pilihan.textContent = perangkatKamera.label || `Kamera ${indeks + 1}`;
            pilihanKamera.append(pilihan);
        });
        pilihanKamera.disabled = daftarPerangkat.length < 2;
        tombolGantiKamera.hidden = daftarPerangkat.length < 2;
        if (daftarPerangkat.some((perangkat) => perangkat.deviceId === nilaiSebelumnya)) pilihanKamera.value = nilaiSebelumnya;
        else if (daftarPerangkat[0]) pilihanKamera.value = daftarPerangkat[0].deviceId;
        idKameraTerpilih = pilihanKamera.value;
    }

    async function bukaKamera(idPerangkatKamera = idKameraTerpilih) {
        if (aliranKamera && (!idPerangkatKamera || idPerangkatKamera === idKameraAktif)) return aliranKamera;
        if (!window.isSecureContext) throw new Error(pesanKesalahanKamera());
        if (!navigator.mediaDevices?.getUserMedia) throw new Error('Browser ini tidak menyediakan akses kamera. Gunakan HTTPS atau localhost dan browser versi terbaru.');

        aliranKamera?.getTracks().forEach((jalurKamera) => jalurKamera.stop());
        const pengaturanKamera = idPerangkatKamera
            ? { video: { deviceId: { exact: idPerangkatKamera } }, audio: false }
            : { video: { facingMode: { ideal: 'user' } }, audio: false };
        try {
            aliranKamera = await navigator.mediaDevices.getUserMedia(pengaturanKamera);
        } catch (kesalahanKamera) {
            if (idPerangkatKamera || kesalahanKamera.name !== 'OverconstrainedError') throw kesalahanKamera;
            aliranKamera = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        }

        idKameraAktif = aliranKamera.getVideoTracks()[0]?.getSettings().deviceId || idPerangkatKamera || '';
        pratinjauKamera.srcObject = aliranKamera;
        pratinjauKamera.hidden = false;
        penandaKamera.hidden = true;
        await pratinjauKamera.play();
        await daftarKamera();
        return aliranKamera;
    }

    pilihanKamera.addEventListener('change', async () => {
        idKameraTerpilih = pilihanKamera.value;
        try {
            await bukaKamera(idKameraTerpilih);
            statusKamera.textContent = 'Kamera aktif';
        } catch (kesalahanKamera) {
            statusKamera.textContent = pesanKesalahanKamera(kesalahanKamera);
            penandaKamera.hidden = false;
            pratinjauKamera.hidden = true;
        }
    });

    tombolGantiKamera.addEventListener('click', async () => {
        if (pilihanKamera.options.length < 2) return;
        pilihanKamera.selectedIndex = (pilihanKamera.selectedIndex + 1) % pilihanKamera.options.length;
        pilihanKamera.dispatchEvent(new Event('change'));
    });

    document.querySelector('#capture-photo').addEventListener('click', async (peristiwaFoto) => {
        const tombolAksiFoto = peristiwaFoto.currentTarget;
        tombolAksiFoto.disabled = true;
        try {
            await bukaKamera();
            if (!pratinjauKamera.videoWidth) await new Promise((selesaikan) => pratinjauKamera.addEventListener('loadeddata', selesaikan, { once: true }));
            const kanvas = document.createElement('canvas');
            kanvas.width = pratinjauKamera.videoWidth;
            kanvas.height = pratinjauKamera.videoHeight;
            kanvas.getContext('2d').drawImage(pratinjauKamera, 0, 0);
            const dataBiner = await new Promise((selesaikan) => kanvas.toBlob(selesaikan, 'image/jpeg', 0.88));
            if (!dataBiner) throw new Error('Foto gagal dibuat. Silakan ulangi.');
            const berkas = new File([dataBiner], 'presensi.jpg', { type: 'image/jpeg' });
            const pemindahBerkas = new DataTransfer();
            pemindahBerkas.items.add(berkas);
            masukanSwafoto.files = pemindahBerkas.files;
            if (urlFoto) URL.revokeObjectURL(urlFoto);
            urlFoto = URL.createObjectURL(dataBiner);
            pratinjauSwafoto.src = urlFoto;
            fotoBukti.src = urlFoto;
            pratinjauSwafoto.hidden = false;
            tombolPratinjauFoto.hidden = false;
            tombolPratinjauFoto.textContent = 'Lihat foto';
            tombolAmbilFoto.textContent = 'Ambil ulang foto';
            document.querySelector('#photo-status').textContent = 'Foto berhasil diambil';
            perbaruiTombolKirim();
        } catch (kesalahanFoto) {
            const pesanKesalahan = pesanKesalahanKamera(kesalahanFoto);
            statusKamera.textContent = pesanKesalahan;
            penandaKamera.hidden = false;
            pratinjauKamera.hidden = true;
            document.querySelector('#photo-status').textContent = pesanKesalahan;
        } finally { tombolAksiFoto.disabled = false; }
    });

    tombolPratinjauFoto.addEventListener('click', () => tampilkanBukti('photo'));
    tombolPratinjauVideo.addEventListener('click', () => tampilkanBukti('video'));

    tombolRekam.addEventListener('click', async (peristiwaRekam) => {
        const tombolAksiRekam = peristiwaRekam.currentTarget;
        if (sedangMerekam && perekamVideo && perekamVideo.state === 'recording') {
            perekamVideo.stop();
            if (pewaktuRekaman) window.clearTimeout(pewaktuRekaman);
            aturStatusTombolRekam(false, 'Mengakhiri rekaman...');
            tombolAksiRekam.disabled = true;
            return;
        }

        tombolAksiRekam.disabled = true;
        aturStatusTombolRekam(true, 'Merekam video sedang berjalan...');
        try {
            const aliranAktif = await bukaKamera();
            if (!window.MediaRecorder) throw new Error('Browser tidak mendukung perekaman video.');
            const tipeMime = ['video/webm;codecs=vp8', 'video/webm', 'video/mp4'].find((jenisMime) => MediaRecorder.isTypeSupported(jenisMime));
            if (!tipeMime) throw new Error('Format video tidak didukung browser.');
            const potonganVideo = [];
            perekamVideo = new MediaRecorder(aliranAktif, { mimeType: tipeMime });
            perekamVideo.addEventListener('dataavailable', (peristiwaDataVideo) => { if (peristiwaDataVideo.data.size) potonganVideo.push(peristiwaDataVideo.data); });
            perekamVideo.addEventListener('stop', () => {
                const dataBiner = new Blob(potonganVideo, { type: tipeMime.split(';')[0] });
                const ekstensiBerkas = dataBiner.type === 'video/mp4' ? 'mp4' : 'webm';
                const berkas = new File([dataBiner], `liveness.${ekstensiBerkas}`, { type: dataBiner.type });
                const pemindahBerkas = new DataTransfer();
                pemindahBerkas.items.add(berkas);
                masukanVideo.files = pemindahBerkas.files;
                if (urlVideo) URL.revokeObjectURL(urlVideo);
                urlVideo = URL.createObjectURL(dataBiner);
                videoBukti.src = urlVideo;
                tombolPratinjauVideo.hidden = false;
                tombolPratinjauVideo.textContent = 'Lihat video';
                aturStatusTombolRekam(false, `Video direkam (${(dataBiner.size / 1048576).toFixed(1)} MB)`);
                perbaruiTombolKirim();
                tombolAksiRekam.disabled = false;
                perekamVideo = null;
            }, { once: true });
            perekamVideo.start();
            pewaktuRekaman = window.setTimeout(() => { if (perekamVideo && perekamVideo.state === 'recording') perekamVideo.stop(); }, 4000);
            tombolAksiRekam.disabled = false;
        } catch (kesalahanRekaman) {
            aturStatusTombolRekam(false, pesanKesalahanKamera(kesalahanRekaman));
            tombolAksiRekam.disabled = false;
            perekamVideo = null;
        }
    });

    document.querySelector('#get-location').addEventListener('click', (peristiwaLokasi) => {
        const tombolAksiLokasi = peristiwaLokasi.currentTarget;
        if (!navigator.geolocation) { document.querySelector('#location-status').textContent = 'Browser tidak mendukung lokasi'; return; }
        tombolAksiLokasi.disabled = true;
        document.querySelector('#location-status').textContent = 'Mencari lokasi perangkat...';
        navigator.geolocation.getCurrentPosition((posisiPerangkat) => {
            formulir.elements.lintang.value = posisiPerangkat.coords.latitude;
            formulir.elements.bujur.value = posisiPerangkat.coords.longitude;
            formulir.elements.akurasi.value = posisiPerangkat.coords.accuracy;
            lokasiTersedia = posisiPerangkat.coords.accuracy <= 1000;
            document.querySelector('#location-status').textContent = lokasiTersedia ? `Lokasi didapat · akurasi ±${Math.round(posisiPerangkat.coords.accuracy)} m` : 'Akurasi lebih dari 1.000 m, coba lagi.';
            tombolAksiLokasi.disabled = false;
            perbaruiTombolKirim();
        }, (kesalahanLokasi) => {
            document.querySelector('#location-status').textContent = kesalahanLokasi.code === 1 ? 'Izin lokasi ditolak' : 'Lokasi gagal didapat, coba lagi';
            tombolAksiLokasi.disabled = false;
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });

    formulir.addEventListener('submit', (peristiwaFormulir) => {
        if (!(masukanSwafoto.files.length && masukanVideo.files.length && lokasiTersedia)) { peristiwaFormulir.preventDefault(); return; }
        tombolKirim.disabled = true;
        tombolKirim.textContent = 'Mengirim bukti...';
    });
    window.addEventListener('pagehide', () => {
        aliranKamera?.getTracks().forEach((jalurKamera) => jalurKamera.stop());
        if (urlFoto) URL.revokeObjectURL(urlFoto);
        if (urlVideo) URL.revokeObjectURL(urlVideo);
    });
})();
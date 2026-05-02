<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Tamu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .stat-num { font-variant-numeric: tabular-nums; }
        @media (min-width: 768px) {
            body { height: 100dvh; overflow: hidden; }
        }
        #qr-disabilitas canvas, #qr-disabilitas img,
        #qr-umum canvas, #qr-umum img {
            max-width: 100%;
            max-height: 100%;
            width: auto !important;
            height: auto !important;
        }
    </style>
</head>
<body class="bg-gray-950 text-white flex flex-col p-3 md:p-4 gap-3 md:gap-4">

    <?php
    include 'db.php';
    $tanggal = date('Y-m-d');

    $res = $mysqli->query("SELECT nomor FROM antrian WHERE tanggal='$tanggal' AND jenis='disabilitas' AND status='dipanggil' ORDER BY id DESC LIMIT 1");
    $nomorDisabilitas = ($row = $res->fetch_assoc()) ? $row['nomor'] : '-';

    $res = $mysqli->query("SELECT nomor FROM antrian WHERE tanggal='$tanggal' AND jenis='umum' AND status='dipanggil' ORDER BY id DESC LIMIT 1");
    $nomorUmum = ($row = $res->fetch_assoc()) ? $row['nomor'] : '-';

    $stmt = $mysqli->prepare("SELECT jenis, COUNT(*) AS total FROM antrian WHERE tanggal=? AND jenis IN ('disabilitas','umum','whatsapp','surat') GROUP BY jenis");
    $stmt->bind_param("s", $tanggal);
    $stmt->execute();
    $totals = ['disabilitas' => 0, 'umum' => 0, 'whatsapp' => 0, 'surat' => 0];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $totals[$r['jenis']] = (int)$r['total'];
    }
    $stmt->close();
    ?>

    <!-- HEADER -->
    <header class="text-center shrink-0">
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-wide">BUKU TAMU</h1>
        <p class="text-gray-400 text-sm sm:text-base mt-0.5">Pelayanan Statistik Terpadu — BPS Kabupaten Buleleng</p>
        <p class="text-gray-500 text-sm" id="tanggal-hari"></p>
    </header>

    <!-- ROW 1: QR Codes — takes majority of remaining height -->
    <div id="qr-section" class="flex-[3] grid grid-cols-2 gap-3 md:gap-4 min-h-0">

        <!-- QR Disabilitas -->
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col items-center min-h-0 border-4 border-blue-600">
            <!-- Header band biru -->
            <div class="w-full bg-blue-600 text-white flex items-center justify-center py-3 px-4 shrink-0 gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 sm:w-10 sm:h-10 shrink-0 opacity-90" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="4" r="2"/><path d="M10 7a2 2 0 0 0-2 2v4l-2 4h2l1.5-3H12v3h2v-4h-2V9h4V7h-6z"/><path d="M15.5 15a4.5 4.5 0 1 1-5.83 4.33l-1.94.52A6.5 6.5 0 1 0 17.4 14.1l-.5 1.86A4.47 4.47 0 0 1 15.5 15z"/></svg>
                <p class="font-black text-2xl sm:text-3xl md:text-4xl uppercase tracking-wide leading-none">Disabilitas</p>
            </div>
            <div id="qr-disabilitas" class="flex justify-center items-center flex-1 min-h-0 py-2 px-3"></div>
            <div class="w-full p-3 shrink-0">
                <a id="link-disabilitas" href="<?= APP_URL ?>/disabilitas" target="_blank" rel="noopener"
                   class="block bg-blue-600 text-white text-sm py-2.5 rounded-xl font-semibold w-full text-center">Buka Link Pendaftaran</a>
            </div>
        </div>

        <!-- QR Umum -->
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col items-center min-h-0 border-4 border-emerald-600">
            <!-- Header band hijau -->
            <div class="w-full bg-emerald-600 text-white flex items-center justify-center py-3 px-4 shrink-0 gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 sm:w-10 sm:h-10 shrink-0 opacity-90" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="4" r="2"/><path d="M12 7c-2.21 0-4 1.79-4 4v5h2v5h4v-5h2v-5c0-2.21-1.79-4-4-4z"/></svg>
                <p class="font-black text-2xl sm:text-3xl md:text-4xl uppercase tracking-wide leading-none">Umum</p>
            </div>
            <div id="qr-umum" class="flex justify-center items-center flex-1 min-h-0 py-2 px-3"></div>
            <div class="w-full p-3 shrink-0">
                <a id="link-umum" href="<?= APP_URL ?>/umum" target="_blank" rel="noopener"
                   class="block bg-emerald-600 text-white text-sm py-2.5 rounded-xl font-semibold w-full text-center">Buka Link Pendaftaran</a>
            </div>
        </div>

    </div>

    <!-- ROW 2: Rekap Kunjungan Hari Ini -->
    <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 min-h-0">

        <!-- Nomor Disabilitas -->
        <div class="bg-blue-800 rounded-2xl p-3 md:p-4 flex flex-col min-h-0">
            <p class="text-xs sm:text-sm font-bold uppercase tracking-widest text-blue-300 shrink-0">Antrean Disabilitas</p>
            <div class="flex-1 flex flex-col items-center justify-center min-h-0">
                <div class="stat-num text-5xl sm:text-6xl md:text-7xl font-bold leading-none" id="nomor-disabilitas"><?= htmlspecialchars($nomorDisabilitas) ?></div>
                <p class="text-xs text-blue-200 mt-1">sedang dipanggil</p>
            </div>
            <p class="text-xs text-blue-300 text-center shrink-0">
                <span class="font-semibold" id="total-disabilitas"><?= $totals['disabilitas'] ?></span> mendaftar hari ini
            </p>
        </div>

        <!-- Nomor Umum -->
        <div class="bg-emerald-800 rounded-2xl p-3 md:p-4 flex flex-col min-h-0">
            <p class="text-xs sm:text-sm font-bold uppercase tracking-widest text-emerald-300 shrink-0">Antrean Umum</p>
            <div class="flex-1 flex flex-col items-center justify-center min-h-0">
                <div class="stat-num text-5xl sm:text-6xl md:text-7xl font-bold leading-none" id="nomor-umum"><?= htmlspecialchars($nomorUmum) ?></div>
                <p class="text-xs text-emerald-200 mt-1">sedang dipanggil</p>
            </div>
            <p class="text-xs text-emerald-300 text-center shrink-0">
                <span class="font-semibold" id="total-umum"><?= $totals['umum'] ?></span> mendaftar hari ini
            </p>
        </div>

        <!-- WhatsApp -->
        <div class="bg-green-700 rounded-2xl p-3 md:p-4 flex flex-col min-h-0">
            <p class="text-xs sm:text-sm font-bold uppercase tracking-widest text-green-200 shrink-0">Via WhatsApp</p>
            <div class="flex-1 flex items-center justify-center min-h-0">
                <div class="stat-num text-5xl sm:text-6xl md:text-7xl font-bold leading-none" id="count-whatsapp"><?= $totals['whatsapp'] ?></div>
            </div>
            <p class="text-xs text-green-300 text-center shrink-0">mendaftar hari ini</p>
        </div>

        <!-- Surat -->
        <div class="bg-amber-700 rounded-2xl p-3 md:p-4 flex flex-col min-h-0">
            <p class="text-xs sm:text-sm font-bold uppercase tracking-widest text-amber-200 shrink-0">Surat</p>
            <div class="flex-1 flex items-center justify-center min-h-0">
                <div class="stat-num text-5xl sm:text-6xl md:text-7xl font-bold leading-none" id="count-surat"><?= $totals['surat'] ?></div>
            </div>
            <p class="text-xs text-amber-300 text-center shrink-0">mendaftar hari ini</p>
        </div>

    </div>

    <script>
        // ── Tanggal hari ini ─────────────────────────────────────────────────
        (function() {
            const d = new Date();
            const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][d.getDay()];
            const bln  = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][d.getMonth()];
            document.getElementById('tanggal-hari').textContent = `${hari}, ${d.getDate()} ${bln} ${d.getFullYear()}`;
        })();

        // ── Config dari PHP ──────────────────────────────────────────────────
        const APP_BASE = '<?= APP_BASE ?>';
        const QR_URLS  = {
            disabilitas: '<?= APP_URL ?>/disabilitas',
            umum:        '<?= APP_URL ?>/umum',
        };

        // ── QR Codes ─────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            // Measure the QR section to size codes to fill available space
            const section = document.getElementById('qr-section');
            const rect    = section.getBoundingClientRect();
            // border=8px×2, header-band~70px, button-area~62px, py-2~16px
            const byH  = Math.floor(rect.height - 16 - 70 - 62 - 16);
            const byW  = Math.floor(rect.width / 2 - 16 - 24); // border + px-3
            const qrSize = Math.max(100, Math.min(byH, byW));

            new QRCode(document.getElementById('qr-disabilitas'), {
                text: QR_URLS.disabilitas, width: qrSize, height: qrSize,
                colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M,
            });
            new QRCode(document.getElementById('qr-umum'), {
                text: QR_URLS.umum, width: qrSize, height: qrSize,
                colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M,
            });

            document.getElementById('link-disabilitas').href = QR_URLS.disabilitas;
            document.getElementById('link-umum').href        = QR_URLS.umum;
        });

        // ── Speech synthesis ─────────────────────────────────────────────────
        const synth = window.speechSynthesis;

        function getBestVoice() {
            const voices = synth.getVoices();
            const pref = localStorage.getItem('preferredVoice');
            if (pref) { const f = voices.find(v => v.name === pref); if (f) return f; }
            const msNat = voices.find(v => /microsoft/i.test(v.name) && /natural/i.test(v.name) && (v.lang||'').toLowerCase().startsWith('id'));
            if (msNat) return msNat;
            const gId = voices.find(v => /google/i.test(v.name) && (v.lang||'').toLowerCase().startsWith('id'));
            if (gId) return gId;
            const anyId = voices.find(v => (v.lang||'').toLowerCase().startsWith('id'));
            if (anyId) return anyId;
            return voices.find(v => /microsoft/i.test(v.name) && /natural/i.test(v.name) && /nathasha|aria|jenny|emma|michelle|elizabeth/i.test(v.name)) || null;
        }

        function populateVoiceList() {
            const select = document.getElementById('voiceSelect');
            if (!select) return;
            const voices = synth.getVoices();
            select.innerHTML = '';
            voices.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.name; opt.textContent = `${v.name} — ${v.lang}`;
                select.appendChild(opt);
            });
            const pref = localStorage.getItem('preferredVoice');
            if (pref) select.value = pref;
        }

        synth.onvoiceschanged = populateVoiceList;

        function speakText(teks, repeat = 3) {
            synth.cancel();
            const voice = getBestVoice();
            let remaining = repeat;
            function next() {
                if (remaining <= 0) return;
                remaining--;
                const utter = new SpeechSynthesisUtterance(teks);
                utter.lang = 'id-ID';
                if (voice) utter.voice = voice;
                utter.onend = () => { if (remaining > 0) setTimeout(next, 500); };
                synth.speak(utter);
            }
            if (!synth.getVoices().length) { synth.onvoiceschanged = next; } else { next(); }
        }

        document.addEventListener('DOMContentLoaded', () => {
            populateVoiceList();
            const btnRefresh = document.getElementById('refreshVoices');
            const btnSave    = document.getElementById('saveVoice');
            const btnUji     = document.getElementById('btn-uji-suara');
            if (btnRefresh) btnRefresh.addEventListener('click', populateVoiceList);
            if (btnSave)    btnSave.addEventListener('click', () => {
                const val = document.getElementById('voiceSelect').value;
                if (val) { localStorage.setItem('preferredVoice', val); alert('Suara disimpan: ' + val); }
            });
            if (btnUji) btnUji.addEventListener('click', () => {
                const utter = new SpeechSynthesisUtterance('Uji suara. Ini hanya tes.');
                utter.lang = 'id-ID';
                const voices = synth.getVoices();
                const pref = localStorage.getItem('preferredVoice');
                let voice = pref ? voices.find(v => v.name === pref) : getBestVoice();
                if (voice) utter.voice = voice;
                if (!voices || voices.length === 0) { synth.onvoiceschanged = () => synth.speak(utter); }
                else synth.speak(utter);
            });
        });

        // ── Auto-refresh nomor antrean setiap 3 detik ────────────────────────
        function parseNomor(text) {
            const parts = text.trim().split('-');
            return parts.length > 1 ? parts[parts.length - 1] : '-';
        }

        let lastNomor = {
            disabilitas: document.getElementById('nomor-disabilitas')?.textContent.trim() || '-',
            umum:        document.getElementById('nomor-umum')?.textContent.trim()        || '-',
        };

        setInterval(() => {
            fetch(APP_BASE + '/cs/antrean_sekarang.php?jenis=disabilitas', {cache:'no-store'})
                .then(r => r.text()).then(data => {
                    const nomor = parseNomor(data);
                    const el = document.getElementById('nomor-disabilitas');
                    if (el) el.textContent = nomor;
                    if (nomor !== '-' && nomor !== lastNomor.disabilitas) {
                        speakText(`Nomor antrean berikutnya, antrean disabilitas, ${nomor}`, 3);
                    }
                    lastNomor.disabilitas = nomor;
                }).catch(() => {});

            fetch(APP_BASE + '/cs/antrean_sekarang.php?jenis=umum', {cache:'no-store'})
                .then(r => r.text()).then(data => {
                    const nomor = parseNomor(data);
                    const el = document.getElementById('nomor-umum');
                    if (el) el.textContent = nomor;
                    if (nomor !== '-' && nomor !== lastNomor.umum) {
                        speakText(`Nomor antrean berikutnya, antrean umum, ${nomor}`, 3);
                    }
                    lastNomor.umum = nomor;
                }).catch(() => {});
        }, 3000);

        // ── Auto-refresh total pengunjung setiap 15 detik ────────────────────
        function refreshStats() {
            fetch(APP_BASE + '/action/stats_today.php', {cache:'no-store'})
                .then(r => r.json())
                .then(data => {
                    const d = data.disabilitas ?? 0;
                    const u = data.umum        ?? 0;
                    const w = data.whatsapp    ?? 0;
                    const s = data.surat       ?? 0;
                    const elDis = document.getElementById('total-disabilitas');
                    const elUmm = document.getElementById('total-umum');
                    const elWa  = document.getElementById('count-whatsapp');
                    const elSur = document.getElementById('count-surat');
                    if (elDis) elDis.textContent = d;
                    if (elUmm) elUmm.textContent = u;
                    if (elWa)  elWa.textContent  = w;
                    if (elSur) elSur.textContent = s;
                }).catch(() => {});
        }
        setInterval(refreshStats, 15000);
    </script>

    <!-- ── Pilih Suara (tersembunyi) ── -->
    <div class="shrink-0 bg-white text-black p-4 rounded shadow hidden">
        <label class="block font-semibold mb-2 text-gray-800">Pilih Suara (jika tersedia)</label>
        <div class="flex items-center gap-2">
            <select id="voiceSelect" class="border p-2 rounded flex-1 text-gray-800"></select>
            <button id="refreshVoices" class="bg-gray-300 text-black px-3 py-2 rounded">Refresh</button>
            <button id="saveVoice" class="bg-green-600 text-white px-3 py-2 rounded">Simpan</button>
            <button id="btn-uji-suara" class="bg-indigo-600 text-white px-3 py-2 rounded">Uji</button>
        </div>
        <p class="text-sm text-gray-600 mt-2">Jika tidak ada suara Indonesia, instal paket suara Indonesian pada sistem atau gunakan browser yang menyediakan suara berbahasa Indonesia.</p>
    </div>

</body>
</html>

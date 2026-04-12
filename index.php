<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Layar Antrean</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        /* Ensure comfortable touch targets on mobile */
        .touch-btn { padding: .75rem 1rem; font-size: 1rem; }
    </style>
</head>
<body class="bg-black text-white p-6 min-h-screen flex flex-col">
    <header class="mx-auto mb-3 flex items-center gap-4">
        <div>
            <h1 class="text-3xl md:text-5xl font-bold">LAYAR ANTREAN</h1>
            <p class="text-xl text-gray-300">Sistem antrean — Pelayanan Statistik Terpadu BPS Kabupaten Buleleng</p>
        </div>
    </header>

    <!-- KOTAK ANTREAN -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10 text-center text-4xl md:text-6xl font-bold mb-8 md:mb-10">
        <div class="bg-blue-700 p-4 md:p-6 rounded">DISABILITAS:<br>
            <span id="nomor-disabilitas"><?php
            include 'db.php';
            $tanggal = date('Y-m-d');
            $res = $mysqli->query("SELECT nomor FROM antrian WHERE tanggal = '$tanggal' AND jenis = 'disabilitas' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1");
            echo ($row = $res->fetch_assoc()) ? $row['nomor'] : '-';
            ?></span>
        </div>
        <div class="bg-green-700 p-4 md:p-6 rounded">UMUM:<br>
            <span id="nomor-umum"><?php
            $res = $mysqli->query("SELECT nomor FROM antrian WHERE tanggal = '$tanggal' AND jenis = 'umum' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1");
            echo ($row = $res->fetch_assoc()) ? $row['nomor'] : '-';
            ?></span>
        </div>
    </div>

    <!-- KOTAK BARCODE TERPISAH DENGAN LATAR BELAKANG PUTIH -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10 text-center">
        <div class="bg-white text-black p-4 md:p-6 rounded shadow">
            <p class="font-semibold mb-2">SCAN UNTUK DISABILITAS</p>
            <div id="qr-disabilitas" class="flex justify-center my-2"></div>
            <div class="qr-actions flex flex-col md:flex-row items-center justify-center gap-3 mt-3">
                <a id="link-disabilitas" href="<?= APP_URL ?>/disabilitas" target="_blank" rel="noopener" class="bg-blue-300 text-black touch-btn rounded font-semibold w-full md:w-auto text-center">Buka Link</a>
            </div>
        </div>
        <div class="bg-white text-black p-4 md:p-6 rounded shadow">
            <p class="font-semibold mb-2">SCAN UNTUK UMUM</p>
            <div id="qr-umum" class="flex justify-center my-2"></div>
            <div class="qr-actions flex flex-col md:flex-row items-center justify-center gap-3 mt-3">
                <a id="link-umum" href="<?= APP_URL ?>/umum" target="_blank" rel="noopener" class="bg-green-300 text-black touch-btn rounded font-semibold w-full md:w-auto text-center">Buka Link</a>
            </div>
        </div>
    </div>

    <script>
        // small SVG icons
        const ICONS = {
            spinner: '<svg width="16" height="16" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="#fff" d="M25 5a20 20 0 1 0 20 20h-4a16 16 0 1 1-16-16V5z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.8s" repeatCount="indefinite"/></path></svg>',
            check: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };

        function showToast(msg) {
            const t = document.createElement('div');
            t.textContent = msg;
            Object.assign(t.style, { position: 'fixed', right: '18px', bottom: '18px', background: '#0b74de', color: '#fff', padding: '8px 12px', borderRadius: '8px', zIndex: 9999, boxShadow: '0 6px 18px rgba(0,0,0,.2)', opacity: '1', transition: 'opacity .3s' });
            document.body.appendChild(t);
            setTimeout(()=>{ t.style.opacity='0'; setTimeout(()=>t.remove(),1000); },1400);
        }

        function fallbackCopy(text){ const ta = document.createElement('textarea'); ta.value = text; ta.style.position='fixed'; ta.style.left='-9999px'; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); showToast('Link disalin'); }catch(e){ alert('Gagal menyalin'); } ta.remove(); }

        // URL diambil otomatis dari config.php
        const APP_BASE = '<?= APP_BASE ?>';
        const QR_URLS = {
            disabilitas: '<?= APP_URL ?>/disabilitas',
            umum:        '<?= APP_URL ?>/umum',
        };

        // Generate QR codes dinamis
        document.addEventListener('DOMContentLoaded', function(){
            const qrSize = Math.min(window.innerWidth < 768 ? 192 : 256, 256);

            new QRCode(document.getElementById('qr-disabilitas'), {
                text:         QR_URLS.disabilitas,
                width:        qrSize,
                height:       qrSize,
                colorDark:    '#000000',
                colorLight:   '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });
            new QRCode(document.getElementById('qr-umum'), {
                text:         QR_URLS.umum,
                width:        qrSize,
                height:       qrSize,
                colorDark:    '#000000',
                colorLight:   '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });

            // Sinkronkan href tombol dengan URL QR
            document.getElementById('link-disabilitas').href = QR_URLS.disabilitas;
            document.getElementById('link-umum').href        = QR_URLS.umum;
        });

        // interactive handlers
        document.addEventListener('DOMContentLoaded', function(){
            document.querySelectorAll('.btn-download').forEach(btn => {
                btn.addEventListener('click', async function(){
                    const url = btn.getAttribute('data-src');
                    const filename = btn.getAttribute('data-filename') || 'file.png';
                    // set loading state
                    const orig = btn.innerHTML;
                    btn.classList.add('loading');
                    btn.innerHTML = ICONS.spinner + ' Mengunduh...';
                    try{
                        const resp = await fetch(url, {cache:'no-store'});
                        if(!resp.ok) throw new Error('HTTP '+resp.status);
                        const blob = await resp.blob();
                        const link = document.createElement('a');
                        const objUrl = URL.createObjectURL(blob);
                        link.href = objUrl; link.download = filename; document.body.appendChild(link); link.click(); link.remove();
                        setTimeout(()=>URL.revokeObjectURL(objUrl),1500);
                        // success state
                        btn.classList.remove('loading'); btn.classList.add('success'); btn.innerHTML = ICONS.check + ' Terunduh';
                        setTimeout(()=>{ btn.classList.remove('success'); btn.innerHTML = orig; },1400);
                    }catch(err){ btn.classList.remove('loading'); btn.innerHTML = orig; alert('Gagal mengunduh: '+err.message); }
                });
            });

            document.querySelectorAll('.btn-copy').forEach(btn => {
                btn.addEventListener('click', function(){
                    const url = btn.getAttribute('data-url');
                    const orig = btn.innerHTML;
                    // try clipboard API
                    if(navigator.clipboard && window.isSecureContext){
                        navigator.clipboard.writeText(url).then(()=>{
                            btn.classList.add('success'); btn.innerHTML = ICONS.check + ' Disalin';
                            showToast('Link disalin ke clipboard');
                            setTimeout(()=>{ btn.classList.remove('success'); btn.innerHTML = orig; },1200);
                        }).catch(()=>{ fallbackCopy(url); });
                    } else {
                        fallbackCopy(url);
                        btn.classList.add('success'); btn.innerHTML = ICONS.check + ' Disalin';
                        setTimeout(()=>{ btn.classList.remove('success'); btn.innerHTML = orig; },1200);
                    }
                });
            });
        });

        // ── Speech synthesis (sama dengan halaman atur antrean) ──────────────
        const synth = window.speechSynthesis;

        function getBestVoice() {
            const voices = synth.getVoices();
            const pref = localStorage.getItem('preferredVoice');
            if (pref) {
                const found = voices.find(v => v.name === pref);
                if (found) return found;
            }
            // 1. Microsoft Natural Indonesian (perempuan, paling natural — Windows 11)
            const msNaturalId = voices.find(v =>
                /microsoft/i.test(v.name) && /natural/i.test(v.name) &&
                (v.lang || '').toLowerCase().startsWith('id')
            );
            if (msNaturalId) return msNaturalId;
            // 2. Google Indonesian (perempuan, Chrome/Android)
            const googleId = voices.find(v =>
                /google/i.test(v.name) && (v.lang || '').toLowerCase().startsWith('id')
            );
            if (googleId) return googleId;
            // 3. Semua suara Indonesian lainnya
            const anyId = voices.find(v => (v.lang || '').toLowerCase().startsWith('id'));
            if (anyId) return anyId;
            // 4. Fallback: Microsoft Natural English female (Nathasha/Aria/Jenny/Emma)
            return voices.find(v =>
                /microsoft/i.test(v.name) && /natural/i.test(v.name) &&
                /nathasha|aria|jenny|emma|michelle|elizabeth/i.test(v.name)
            ) || null;
        }

        function populateVoiceList() {
            const select = document.getElementById('voiceSelect');
            if (!select) return;
            const voices = synth.getVoices();
            select.innerHTML = '';
            voices.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.name;
                opt.textContent = `${v.name} — ${v.lang}`;
                select.appendChild(opt);
            });
            const pref = localStorage.getItem('preferredVoice');
            if (pref) select.value = pref;
        }

        synth.onvoiceschanged = populateVoiceList;

        function speakText(teks, repeat = 3) {
            synth.cancel(); // bersihkan antrian yang mungkin masih berjalan
            const voice = getBestVoice();
            let remaining = repeat;

            function next() {
                if (remaining <= 0) return;
                remaining--;
                const utter = new SpeechSynthesisUtterance(teks);
                utter.lang = 'id-ID';
                if (voice) utter.voice = voice;
                // Tunggu utterance selesai sebelum mengulang, jeda 500ms
                utter.onend = () => { if (remaining > 0) setTimeout(next, 500); };
                synth.speak(utter);
            }

            if (!synth.getVoices().length) {
                synth.onvoiceschanged = next;
            } else {
                next();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            populateVoiceList();
            document.getElementById('refreshVoices').addEventListener('click', populateVoiceList);
            document.getElementById('saveVoice').addEventListener('click', () => {
                const val = document.getElementById('voiceSelect').value;
                if (val) { localStorage.setItem('preferredVoice', val); alert('Suara disimpan: ' + val); }
            });
            document.getElementById('btn-uji-suara').addEventListener('click', () => {
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
            // Response: "DISABILITAS-3" atau "Belum ada antrean dipanggil"
            const parts = text.trim().split('-');
            return parts.length > 1 ? parts[parts.length - 1] : '-';
        }

        // Inisialisasi dari nilai PHP saat halaman pertama dimuat
        let lastNomor = {
            disabilitas: document.getElementById('nomor-disabilitas')?.textContent.trim() || '-',
            umum:        document.getElementById('nomor-umum')?.textContent.trim()        || '-',
        };

        setInterval(() => {
            fetch(APP_BASE + '/cs/antrean_sekarang.php?jenis=disabilitas', {cache: 'no-store'})
                .then(r => r.text())
                .then(data => {
                    const nomor = parseNomor(data);
                    const el = document.getElementById('nomor-disabilitas');
                    if (el) el.textContent = nomor;
                    if (nomor !== '-' && nomor !== lastNomor.disabilitas) {
                        speakText(`Nomor antrean berikutnya, antrean disabilitas, ${nomor}`, 3, 1500);
                    }
                    lastNomor.disabilitas = nomor;
                }).catch(() => {});

            fetch(APP_BASE + '/cs/antrean_sekarang.php?jenis=umum', {cache: 'no-store'})
                .then(r => r.text())
                .then(data => {
                    const nomor = parseNomor(data);
                    const el = document.getElementById('nomor-umum');
                    if (el) el.textContent = nomor;
                    if (nomor !== '-' && nomor !== lastNomor.umum) {
                        speakText(`Nomor antrean berikutnya, antrean umum, ${nomor}`, 3, 1500);
                    }
                    lastNomor.umum = nomor;
                }).catch(() => {});
        }, 3000);
    </script>

    <!-- ── Pilih Suara ── -->
    <div class="mt-6 bg-white text-black p-4 rounded shadow hidden">
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

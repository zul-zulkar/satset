<?php include_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu — Sistem Antrean BPS Buleleng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes pulse-ring {
            0%   { box-shadow: 0 0 0 0 rgba(255,255,255,0.25); }
            70%  { box-shadow: 0 0 0 12px rgba(255,255,255,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }

        .hero-title {
            background: linear-gradient(90deg, #38bdf8, #818cf8, #e879f9, #38bdf8);
            background-size: 300% 300%;
            animation: gradientShift 5s ease infinite;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card {
            animation: fadeUp 0.5s ease both;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .card:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .card:active {
            transform: translateY(-2px) scale(1.01);
        }

        .icon-wrap {
            transition: transform 0.3s ease;
        }
        .card:hover .icon-wrap {
            transform: scale(1.18) rotate(-4deg);
        }

        .badge-new {
            animation: pulse-ring 2s ease-out infinite;
        }

        /* stagger delay per card */
        .card:nth-child(1)  { animation-delay: 0.05s; }
        .card:nth-child(2)  { animation-delay: 0.12s; }
        .card:nth-child(3)  { animation-delay: 0.19s; }
        .card:nth-child(4)  { animation-delay: 0.26s; }
        .card:nth-child(5)  { animation-delay: 0.33s; }
        .card:nth-child(6)  { animation-delay: 0.40s; }
        .card:nth-child(7)  { animation-delay: 0.47s; }

        .section-label {
            animation: fadeUp 0.4s ease both;
        }
        .section-label:nth-of-type(1) { animation-delay: 0s; }
        .section-label:nth-of-type(2) { animation-delay: 0.1s; }
        .section-label:nth-of-type(3) { animation-delay: 0.2s; }

        .divider {
            background: linear-gradient(90deg, transparent, rgba(148,163,184,0.3), transparent);
            height: 1px;
        }
    </style>
</head>
<body class="text-white p-6 sm:p-10">

    <!-- Header -->
    <header class="text-center mb-10" style="animation: fadeUp 0.4s ease both;">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 backdrop-blur mb-4 text-4xl">
            📋
        </div>
        <h1 class="hero-title text-3xl sm:text-5xl font-extrabold tracking-tight mb-2">
            Sistem Antrean
        </h1>
        <p class="text-slate-400 text-base sm:text-lg">
            Pelayanan Statistik Terpadu · BPS Kabupaten Buleleng
        </p>
    </header>

    <div class="max-w-4xl mx-auto space-y-10">

        <!-- ── SEKSI 1: LAYAR UTAMA ── -->
        <section>
            <p class="section-label text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4 flex items-center gap-2">
                <span class="inline-block w-4 h-0.5 bg-slate-600"></span>
                Layar Utama
                <span class="inline-block flex-1 h-0.5 bg-slate-700/50"></span>
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="<?= APP_BASE ?>/" target="_blank" class="card block rounded-2xl p-5 sm:p-6
                    bg-gradient-to-br from-indigo-600 to-blue-700
                    border border-indigo-500/30 sm:col-span-2">
                    <div class="flex items-center gap-4">
                        <div class="icon-wrap text-4xl sm:text-5xl flex-shrink-0">🖥️</div>
                        <div>
                            <div class="font-bold text-lg sm:text-xl">Layar Antrean</div>
                            <div class="text-indigo-200 text-sm mt-0.5">Tampilan nomor antrean + QR buku tamu (auto-refresh)</div>
                        </div>
                        <div class="ml-auto text-indigo-300 text-xl">›</div>
                    </div>
                </a>

                <a href="<?= APP_BASE ?>/absensi" target="_blank" class="card block rounded-2xl p-5 sm:p-6
                    bg-gradient-to-br from-sky-500 to-blue-600
                    border border-sky-500/30">
                    <div class="flex items-center gap-4">
                        <div class="icon-wrap text-4xl sm:text-5xl flex-shrink-0">🕐</div>
                        <div>
                            <div class="font-bold text-lg sm:text-xl">Absensi Piket PST</div>
                            <div class="text-sky-200 text-sm mt-0.5">Catat kehadiran petugas piket berbasis GPS</div>
                        </div>
                        <div class="ml-auto text-sky-300 text-xl">›</div>
                    </div>
                </a>

                <a href="<?= APP_BASE ?>/absensi/admin" target="_blank" class="card block rounded-2xl p-5 sm:p-6
                    bg-gradient-to-br from-slate-600 to-slate-700
                    border border-slate-500/30">
                    <div class="flex items-center gap-4">
                        <div class="icon-wrap text-4xl sm:text-5xl flex-shrink-0">⚙️</div>
                        <div>
                            <div class="font-bold text-lg sm:text-xl">Admin Absensi</div>
                            <div class="text-slate-300 text-sm mt-0.5">Atur koordinat & radius PST, lihat rekap absensi</div>
                        </div>
                        <div class="ml-auto text-slate-400 text-xl">›</div>
                    </div>
                </a>
            </div>
        </section>

        <div class="divider"></div>

        <!-- ── SEKSI 2: FORM PENDAFTARAN ── -->
        <section>
            <p class="section-label text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4 flex items-center gap-2">
                <span class="inline-block w-4 h-0.5 bg-slate-600"></span>
                Form Pendaftaran
                <span class="inline-block flex-1 h-0.5 bg-slate-700/50"></span>
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <a href="<?= APP_BASE ?>/disabilitas" target="_blank" class="card block rounded-2xl p-5
                    bg-gradient-to-br from-blue-600 to-cyan-600
                    border border-blue-500/30">
                    <div class="icon-wrap text-4xl mb-3">♿</div>
                    <div class="font-bold text-base sm:text-lg">Antrean Disabilitas</div>
                    <div class="text-blue-200 text-xs mt-1">Form pendaftaran prioritas disabilitas</div>
                </a>

                <a href="<?= APP_BASE ?>/umum" target="_blank" class="card block rounded-2xl p-5
                    bg-gradient-to-br from-emerald-600 to-teal-600
                    border border-emerald-500/30">
                    <div class="icon-wrap text-4xl mb-3">👤</div>
                    <div class="font-bold text-base sm:text-lg">Antrean Umum</div>
                    <div class="text-emerald-200 text-xs mt-1">Form pendaftaran antrean umum</div>
                </a>

                <a href="<?= APP_BASE ?>/whatsapp" target="_blank" class="card block rounded-2xl p-5
                    bg-gradient-to-br from-green-600 to-lime-600
                    border border-green-500/30">
                    <div class="icon-wrap text-4xl mb-3">💬</div>
                    <div class="font-bold text-base sm:text-lg">Buku Tamu WhatsApp</div>
                    <div class="text-green-200 text-xs mt-1">Daftar hadir via WhatsApp</div>
                </a>

                <button onclick="bukaModalSurvei()" class="card text-left w-full rounded-2xl p-5
                    bg-gradient-to-br from-amber-500 to-orange-600
                    border border-amber-500/30 cursor-pointer">
                    <div class="icon-wrap text-4xl mb-3">⭐</div>
                    <div class="font-bold text-base sm:text-lg">Survei Kepuasan</div>
                    <div class="text-amber-100 text-xs mt-1">Pengguna siap digenerate kode survei</div>
                </button>

                <button onclick="bukaModalPes()" class="card text-left w-full rounded-2xl p-5
                    bg-gradient-to-br from-teal-500 to-cyan-600
                    border border-teal-500/30 cursor-pointer">
                    <div class="icon-wrap text-4xl mb-3">📋</div>
                    <div class="font-bold text-base sm:text-lg">Form PES</div>
                    <div class="text-teal-100 text-xs mt-1">Post Enumeration Survey — diisi petugas PST</div>
                </button>

            </div>
        </section>

        <div class="divider"></div>

        <!-- ── SEKSI 3: MANAJEMEN ── -->
        <section>
            <p class="section-label text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4 flex items-center gap-2">
                <span class="inline-block w-4 h-0.5 bg-slate-600"></span>
                Manajemen
                <span class="inline-block flex-1 h-0.5 bg-slate-700/50"></span>
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <a href="<?= APP_BASE ?>/cs" target="_blank" class="card block rounded-2xl p-5 sm:p-6
                    bg-gradient-to-br from-violet-600 to-purple-700
                    border border-violet-500/30">
                    <div class="flex items-center gap-4">
                        <div class="icon-wrap text-4xl sm:text-5xl flex-shrink-0">📊</div>
                        <div>
                            <div class="font-bold text-base sm:text-lg">Daftar Pengguna</div>
                            <div class="text-violet-200 text-xs mt-1">Rekap data pengunjung, ekspor Excel / CSV</div>
                        </div>
                    </div>
                </a>

                <a href="<?= APP_BASE ?>/cs/antrean-atur" target="_blank" class="card block rounded-2xl p-5 sm:p-6
                    bg-gradient-to-br from-orange-500 to-rose-600
                    border border-orange-500/30">
                    <div class="flex items-center gap-4">
                        <div class="icon-wrap text-4xl sm:text-5xl flex-shrink-0">🔔</div>
                        <div>
                            <div class="font-bold text-base sm:text-lg">Atur Antrean</div>
                            <div class="text-orange-200 text-xs mt-1">Panggil / kembalikan nomor antrean, suara TTS</div>
                        </div>
                    </div>
                </a>

            </div>
        </section>

    </div>

    <!-- Footer -->
    <footer class="text-center text-slate-600 text-xs mt-14" style="animation: fadeUp 0.5s ease 0.5s both;">
        BPS Kabupaten Buleleng · Sistem Antrean v2
    </footer>

    <!-- ── MODAL PES ── -->
    <div id="modal-pes" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="tutupModalPes()"></div>
        <div class="relative z-10 flex items-start justify-center min-h-screen pt-10 px-4 pb-10">
            <div class="w-full max-w-2xl bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden">

                <!-- header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-teal-600/20 to-cyan-600/20">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📋</span>
                        <div>
                            <div class="font-bold text-white text-base">Form PES — Post Enumeration Survey</div>
                            <div id="modal-pes-tanggal" class="text-teal-300 text-xs"></div>
                        </div>
                    </div>
                    <button onclick="tutupModalPes()" class="text-slate-400 hover:text-white text-xl leading-none">✕</button>
                </div>

                <!-- filter -->
                <div class="flex flex-wrap items-center gap-2 px-6 py-3 border-b border-slate-800 bg-slate-800/50">
                    <label class="text-slate-400 text-xs font-semibold uppercase tracking-wide shrink-0">Periode</label>
                    <input id="pes-filter-dari" type="date" class="bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <span class="text-slate-500 text-xs shrink-0">s/d</span>
                    <input id="pes-filter-sampai" type="date" class="bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <button onclick="muatDaftarPes()" class="text-xs bg-teal-600 hover:bg-teal-500 text-white font-semibold px-3 py-1.5 rounded-lg transition shrink-0">Tampilkan</button>
                    <label class="ml-auto flex items-center gap-2 text-slate-400 text-xs cursor-pointer select-none shrink-0">
                        <input id="pes-filter-belum" type="checkbox" checked onchange="renderDaftarPes()" class="accent-teal-500">
                        Belum saja
                    </label>
                </div>

                <!-- body -->
                <div id="modal-pes-body" class="overflow-y-auto max-h-[55vh] px-6 py-4 space-y-2">
                    <div class="text-slate-500 text-sm text-center py-8">Memuat data…</div>
                </div>

                <!-- footer info -->
                <div id="modal-pes-footer" class="px-6 py-3 border-t border-slate-800 bg-slate-800/30 text-slate-500 text-xs"></div>
            </div>
        </div>
    </div>

    <!-- ── MODAL SURVEI KEPUASAN ── -->
    <div id="modal-survei" class="fixed inset-0 z-50 hidden">
        <!-- backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="tutupModalSurvei()"></div>

        <!-- panel -->
        <div class="relative z-10 flex items-start justify-center min-h-screen pt-10 px-4 pb-10">
            <div class="w-full max-w-2xl bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden">

                <!-- header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-amber-600/20 to-orange-600/20">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">⭐</span>
                        <div>
                            <div class="font-bold text-white text-base">Pengguna Siap Survei Kepuasan</div>
                            <div id="modal-tanggal" class="text-amber-300 text-xs"></div>
                        </div>
                    </div>
                    <button onclick="tutupModalSurvei()" class="text-slate-400 hover:text-white text-xl leading-none">✕</button>
                </div>

                <!-- filter rentang tanggal -->
                <div class="flex flex-wrap items-center gap-2 px-6 py-3 border-b border-slate-800 bg-slate-800/50">
                    <label class="text-slate-400 text-xs font-semibold uppercase tracking-wide shrink-0">Periode</label>
                    <input id="filter-dari" type="date" class="bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <span class="text-slate-500 text-xs shrink-0">s/d</span>
                    <input id="filter-sampai" type="date" class="bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button onclick="muatDaftarSurvei()" class="text-xs bg-amber-600 hover:bg-amber-500 text-white font-semibold px-3 py-1.5 rounded-lg transition shrink-0">Tampilkan</button>
                    <label class="ml-auto flex items-center gap-2 text-slate-400 text-xs cursor-pointer select-none shrink-0">
                        <input id="filter-belum" type="checkbox" checked onchange="renderDaftarSurvei()" class="accent-amber-500">
                        Belum saja
                    </label>
                </div>

                <!-- body -->
                <div id="modal-body" class="overflow-y-auto max-h-[55vh] px-6 py-4 space-y-2">
                    <div class="text-slate-500 text-sm text-center py-8">Memuat data…</div>
                </div>

                <!-- footer info -->
                <div id="modal-footer-info" class="px-6 py-3 border-t border-slate-800 bg-slate-800/30 text-slate-500 text-xs"></div>
            </div>
        </div>
    </div>

    <!-- ── MODAL QR CODE ── -->
    <div id="modal-qr" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="tutupModalQr()"></div>
        <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-xs text-center">
                <div id="qr-nama" class="font-semibold text-slate-800 text-sm mb-1 truncate"></div>
                <div id="qr-link" class="text-slate-500 text-xs mb-4 break-all"></div>
                <div id="qr-canvas" class="flex justify-center mb-4"></div>
                <div class="flex gap-2 justify-center">
                    <button onclick="tutupModalQr()"
                        class="text-sm bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg transition">
                        Tutup</button>
                    <button id="qr-salin-btn" onclick=""
                        class="text-sm bg-blue-600 hover:bg-blue-500 text-white font-semibold px-4 py-2 rounded-lg transition">
                        Salin Link</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    const APP_BASE_MENU = '<?= APP_BASE ?>';
    const APP_URL_MENU  = '<?= APP_URL ?>';
    let _surveiData = [];

    const BADGE = {
        umum:        'bg-blue-900/60 text-blue-300 border border-blue-700',
        disabilitas: 'bg-purple-900/60 text-purple-300 border border-purple-700',
        whatsapp:    'bg-green-900/60 text-green-300 border border-green-700',
    };
    const LABEL = { umum: 'Umum', disabilitas: 'Disabilitas', whatsapp: 'WhatsApp' };

    function isoDate(d) { return d.toISOString().slice(0, 10); }

    function defaultRentang() {
        const today  = new Date();
        const tiga   = new Date(today);
        tiga.setMonth(tiga.getMonth() - 3);
        return { dari: isoDate(tiga), sampai: isoDate(today) };
    }

    function bukaModalSurvei() {
        const modal = document.getElementById('modal-survei');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        // set default rentang jika belum diisi
        const inpDari   = document.getElementById('filter-dari');
        const inpSampai = document.getElementById('filter-sampai');
        if (!inpDari.value && !inpSampai.value) {
            const def = defaultRentang();
            inpDari.value   = def.dari;
            inpSampai.value = def.sampai;
        }
        muatDaftarSurvei();
    }

    function tutupModalSurvei() {
        document.getElementById('modal-survei').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function formatTgl(str) {
        return new Date(str + 'T00:00:00').toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' });
    }

    async function muatDaftarSurvei() {
        const dari   = document.getElementById('filter-dari').value;
        const sampai = document.getElementById('filter-sampai').value;
        document.getElementById('modal-tanggal').textContent =
            (dari && sampai) ? `${formatTgl(dari)} — ${formatTgl(sampai)}` : '';
        document.getElementById('modal-body').innerHTML =
            '<div class="text-slate-500 text-sm text-center py-8">Memuat data…</div>';
        document.getElementById('modal-footer-info').textContent = '';

        try {
            const params = new URLSearchParams();
            if (dari)   params.set('dari',   dari);
            if (sampai) params.set('sampai', sampai);
            const res  = await fetch(APP_BASE_MENU + '/action/list_siap_penilaian.php?' + params, { cache: 'no-store' });
            const json = await res.json();
            _surveiData = json.data || [];
            renderDaftarSurvei();
        } catch (e) {
            document.getElementById('modal-body').innerHTML =
                '<div class="text-red-400 text-sm text-center py-8">Gagal memuat data.</div>';
        }
    }

    function renderDaftarSurvei() {
        const hanyaBelum = document.getElementById('filter-belum').checked;
        const list = hanyaBelum ? _surveiData.filter(r => !parseInt(r.sudah_penilaian)) : _surveiData;

        const footer = document.getElementById('modal-footer-info');
        const total   = _surveiData.length;
        const sudah   = _surveiData.filter(r => parseInt(r.sudah_penilaian)).length;
        const belum   = total - sudah;
        footer.textContent = `Total PST: ${total} pengguna · Sudah survei: ${sudah} · Belum: ${belum}`;

        if (list.length === 0) {
            document.getElementById('modal-body').innerHTML =
                `<div class="text-slate-500 text-sm text-center py-8">${total === 0 ? 'Belum ada pengunjung PST pada periode ini.' : 'Semua pengguna sudah mengisi survei.'}</div>`;
            return;
        }

        const html = list.map(r => {
            const isDone = parseInt(r.sudah_penilaian);
            const badge  = BADGE[r.jenis] || 'bg-slate-700 text-slate-300';
            const label  = LABEL[r.jenis] || r.jenis;
            const nomor  = r.nomor ? `<span class="text-slate-500 text-xs">#${r.nomor}</span>` : '';
            const tgl    = r.tanggal ? `<span class="text-slate-500 text-xs">${formatTgl(r.tanggal)}</span>` : '';

            const statusHtml = isDone
                ? `<span class="text-xs text-emerald-400 font-semibold">✓ Sudah</span>`
                : `<span class="text-xs text-amber-400 font-semibold">○ Belum</span>`;

            let aksiHtml;
            if (isDone) {
                const link = APP_URL_MENU + '/penilaian/' + r.token;
                aksiHtml = `<a href="${link}" target="_blank"
                    class="text-xs text-slate-400 hover:text-white border border-slate-600 hover:border-slate-400 px-2.5 py-1 rounded-lg transition">
                    Lihat Survei</a>`;
            } else if (r.token) {
                const link = APP_URL_MENU + '/penilaian/' + r.token;
                aksiHtml = `
                    <button onclick="tampilkanQR('${link}', '${escHtml(r.nama).replace(/'/g, '&#39;')}')"
                        class="text-xs bg-slate-600 hover:bg-slate-500 text-white font-semibold px-2.5 py-1 rounded-lg transition">
                        QR</button>
                    <button onclick="salinLink('${link}', this)"
                        class="text-xs bg-amber-600 hover:bg-amber-500 text-white font-semibold px-2.5 py-1 rounded-lg transition">
                        Salin Link</button>
                    <a href="${link}" target="_blank"
                        class="text-xs text-slate-400 hover:text-white border border-slate-600 hover:border-slate-400 px-2.5 py-1 rounded-lg transition">
                        Buka</a>`;
            } else {
                aksiHtml = `
                    <button onclick="generateToken(${r.id}, this)"
                        class="text-xs bg-amber-600 hover:bg-amber-500 text-white font-semibold px-2.5 py-1 rounded-lg transition">
                        Generate Token</button>`;
            }

            return `
            <div class="flex items-center gap-3 bg-slate-800/60 border border-slate-700 rounded-xl px-4 py-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        ${nomor}
                        <span class="font-semibold text-white text-sm truncate">${escHtml(r.nama)}</span>
                        <span class="text-[11px] px-1.5 py-0.5 rounded-md font-medium ${badge}">${label}</span>
                        ${tgl}
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    ${statusHtml}
                    ${aksiHtml}
                </div>
            </div>`;
        }).join('');

        document.getElementById('modal-body').innerHTML = html;
    }

    async function generateToken(id, btn) {
        const orig = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Membuat…';
        try {
            const fd = new FormData();
            fd.append('id', id);
            const res  = await fetch(APP_BASE_MENU + '/action/generate_token.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                // refresh baris
                const idx = _surveiData.findIndex(r => parseInt(r.id) === parseInt(id));
                if (idx !== -1) _surveiData[idx].token = json.token;
                renderDaftarSurvei();
            } else {
                btn.disabled = false;
                btn.textContent = orig;
                alert('Gagal: ' + (json.message || 'Error'));
            }
        } catch(e) {
            btn.disabled = false;
            btn.textContent = orig;
            alert('Gagal menghubungi server.');
        }
    }

    function salinLink(url, btn) {
        const orig = btn.textContent;
        const ok = () => {
            btn.textContent = '✓ Disalin';
            btn.classList.remove('bg-amber-600','hover:bg-amber-500');
            btn.classList.add('bg-emerald-600');
            setTimeout(() => {
                btn.textContent = orig;
                btn.classList.add('bg-amber-600','hover:bg-amber-500');
                btn.classList.remove('bg-emerald-600');
            }, 1500);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(ok).catch(() => fallbackSalin(url, ok));
        } else {
            fallbackSalin(url, ok);
        }
    }

    function fallbackSalin(text, cb) {
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.left = '-9999px';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); cb(); } catch(e) { alert('Gagal menyalin link.'); }
        ta.remove();
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Modal QR Code ─────────────────────────────────────────────────────
    let _qrInstance = null;

    function tampilkanQR(url, nama) {
        document.getElementById('qr-nama').textContent = nama;
        document.getElementById('qr-link').textContent = url;
        const canvas = document.getElementById('qr-canvas');
        canvas.innerHTML = '';
        _qrInstance = new QRCode(canvas, {
            text: url,
            width: 200,
            height: 200,
            colorDark: '#0f172a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M,
        });
        const btn = document.getElementById('qr-salin-btn');
        btn.onclick = () => salinLink(url, btn);
        document.getElementById('modal-qr').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function tutupModalQr() {
        document.getElementById('modal-qr').classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Tutup modal dengan Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { tutupModalSurvei(); tutupModalPes(); tutupModalQr(); }
    });

    // ── Modal PES ────────────────────────────────────────────────────────
    let _pesData = [];

    function bukaModalPes() {
        document.getElementById('modal-pes').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        const inpDari   = document.getElementById('pes-filter-dari');
        const inpSampai = document.getElementById('pes-filter-sampai');
        if (!inpDari.value && !inpSampai.value) {
            const def = defaultRentang();
            inpDari.value   = def.dari;
            inpSampai.value = def.sampai;
        }
        muatDaftarPes();
    }

    function tutupModalPes() {
        document.getElementById('modal-pes').classList.add('hidden');
        document.body.style.overflow = '';
    }

    async function muatDaftarPes() {
        const dari   = document.getElementById('pes-filter-dari').value;
        const sampai = document.getElementById('pes-filter-sampai').value;
        document.getElementById('modal-pes-tanggal').textContent =
            (dari && sampai) ? `${formatTgl(dari)} — ${formatTgl(sampai)}` : '';
        document.getElementById('modal-pes-body').innerHTML =
            '<div class="text-slate-500 text-sm text-center py-8">Memuat data…</div>';
        document.getElementById('modal-pes-footer').textContent = '';
        try {
            const params = new URLSearchParams();
            if (dari)   params.set('dari',   dari);
            if (sampai) params.set('sampai', sampai);
            const res  = await fetch(APP_BASE_MENU + '/action/list_siap_pes.php?' + params, { cache: 'no-store' });
            const text = await res.text();
            let json;
            try { json = JSON.parse(text); } catch (_) {
                document.getElementById('modal-pes-body').innerHTML =
                    `<div class="text-red-400 text-sm text-center py-8">Respons tidak valid:<br><pre class="text-left text-xs mt-2 overflow-auto max-h-40">${escHtml(text.slice(0, 500))}</pre></div>`;
                return;
            }
            if (json.error) {
                document.getElementById('modal-pes-body').innerHTML =
                    `<div class="text-red-400 text-sm text-center py-8">Error DB: ${escHtml(json.error)}</div>`;
                return;
            }
            _pesData = json.data || [];
            renderDaftarPes();
        } catch (e) {
            document.getElementById('modal-pes-body').innerHTML =
                `<div class="text-red-400 text-sm text-center py-8">Gagal memuat data: ${escHtml(String(e))}</div>`;
        }
    }

    function renderDaftarPes() {
        const hanyaBelum = document.getElementById('pes-filter-belum').checked;
        const list = hanyaBelum ? _pesData.filter(r => !parseInt(r.sudah_pes)) : _pesData;

        const footer = document.getElementById('modal-pes-footer');
        const total  = _pesData.length;
        const sudah  = _pesData.filter(r => parseInt(r.sudah_pes)).length;
        footer.textContent = `Total pengunjung: ${total} · Sudah PES: ${sudah} · Belum: ${total - sudah}`;

        if (list.length === 0) {
            document.getElementById('modal-pes-body').innerHTML =
                `<div class="text-slate-500 text-sm text-center py-8">${total === 0 ? 'Belum ada pengunjung pada periode ini.' : 'Semua pengunjung sudah mengisi PES.'}</div>`;
            return;
        }

        const html = list.map(r => {
            const isDone = parseInt(r.sudah_pes);
            const badge  = BADGE[r.jenis] || 'bg-slate-700 text-slate-300';
            const label  = LABEL[r.jenis] || r.jenis;
            const nomor  = r.nomor ? `<span class="text-slate-500 text-xs">#${r.nomor}</span>` : '';
            const tgl    = r.tanggal ? `<span class="text-slate-500 text-xs">${formatTgl(r.tanggal)}</span>` : '';

            const statusHtml = isDone
                ? `<span class="text-xs text-emerald-400 font-semibold">✓ Sudah</span>`
                : `<span class="text-xs text-amber-400 font-semibold">○ Belum</span>`;

            let aksiHtml;
            if (isDone) {
                const link = APP_URL_MENU + '/pes/' + r.token_pes;
                aksiHtml = `<a href="${link}" target="_blank"
                    class="text-xs text-slate-400 hover:text-white border border-slate-600 hover:border-slate-400 px-2.5 py-1 rounded-lg transition">
                    Lihat PES</a>`;
            } else if (r.token_pes) {
                const link = APP_URL_MENU + '/pes/' + r.token_pes;
                aksiHtml = `
                    <button onclick="tampilkanQR('${link}', '${escHtml(r.nama).replace(/'/g, '&#39;')}')"
                        class="text-xs bg-slate-600 hover:bg-slate-500 text-white font-semibold px-2.5 py-1 rounded-lg transition">
                        QR</button>
                    <button onclick="salinLink('${link}', this)"
                        class="text-xs bg-teal-600 hover:bg-teal-500 text-white font-semibold px-2.5 py-1 rounded-lg transition">
                        Salin Link</button>
                    <a href="${link}" target="_blank"
                        class="text-xs text-slate-400 hover:text-white border border-slate-600 hover:border-slate-400 px-2.5 py-1 rounded-lg transition">
                        Buka</a>`;
            } else {
                aksiHtml = `
                    <button onclick="generateTokenPes(${r.id}, this)"
                        class="text-xs bg-teal-600 hover:bg-teal-500 text-white font-semibold px-2.5 py-1 rounded-lg transition">
                        Generate Token</button>`;
            }

            return `
            <div class="flex items-center gap-3 bg-slate-800/60 border border-slate-700 rounded-xl px-4 py-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        ${nomor}
                        <span class="font-semibold text-white text-sm truncate">${escHtml(r.nama)}</span>
                        <span class="text-[11px] px-1.5 py-0.5 rounded-md font-medium ${badge}">${label}</span>
                        ${tgl}
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    ${statusHtml}
                    ${aksiHtml}
                </div>
            </div>`;
        }).join('');

        document.getElementById('modal-pes-body').innerHTML = html;
    }

    async function generateTokenPes(id, btn) {
        const orig = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Membuat…';
        try {
            const fd = new FormData();
            fd.append('id', id);
            const res  = await fetch(APP_BASE_MENU + '/action/generate_token_pes.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                const idx = _pesData.findIndex(r => parseInt(r.id) === parseInt(id));
                if (idx !== -1) _pesData[idx].token_pes = json.token;
                renderDaftarPes();
            } else {
                btn.disabled = false;
                btn.textContent = orig;
                alert('Gagal: ' + (json.message || 'Error'));
            }
        } catch(e) {
            btn.disabled = false;
            btn.textContent = orig;
            alert('Gagal menghubungi server.');
        }
    }
    </script>

</body>
</html>

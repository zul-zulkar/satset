<?php
include '../db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); die('ID tidak valid.'); }

// ── Antrian ───────────────────────────────────────────────────────────────────
$stmt = $mysqli->prepare("SELECT * FROM antrian WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$antrian = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$antrian) { http_response_code(404); die('Data tidak ditemukan.'); }

// ── Penilaian ─────────────────────────────────────────────────────────────────
$stmt = $mysqli->prepare("SELECT * FROM penilaian WHERE antrian_id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$penilaian = $stmt->get_result()->fetch_assoc();
$stmt->close();

$pDataItems = [];
if ($penilaian) {
    $stmt = $mysqli->prepare("SELECT * FROM penilaian_data_item WHERE penilaian_id = ? ORDER BY id");
    $stmt->bind_param("i", $penilaian['id']);
    $stmt->execute();
    $pDataItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ── PES ───────────────────────────────────────────────────────────────────────
$stmt = $mysqli->prepare(
    "SELECT p.*, g.nama AS petugas_nama, g.nip AS petugas_nip
     FROM pes p LEFT JOIN pegawai g ON p.petugas_utama_id = g.id
     WHERE p.antrian_id = ? LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$pes = $stmt->get_result()->fetch_assoc();
$stmt->close();

$pesPembantu      = [];
$pesKebutuhanData = [];
if ($pes) {
    $stmt = $mysqli->prepare(
        "SELECT g.nama, g.nip FROM pes_pembantu pp
         JOIN pegawai g ON pp.pegawai_id = g.id WHERE pp.pes_id = ? ORDER BY pp.id"
    );
    $stmt->bind_param("i", $pes['id']);
    $stmt->execute();
    $pesPembantu = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT * FROM pes_kebutuhan_data WHERE pes_id = ? ORDER BY id");
    $stmt->bind_param("i", $pes['id']);
    $stmt->execute();
    $pesKebutuhanData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ── Merge kebutuhan data ──────────────────────────────────────────────────────
$rawDataItems = [];
if (!empty($antrian['data_dibutuhkan'])) {
    $parsed = json_decode($antrian['data_dibutuhkan'], true);
    if (is_array($parsed)) $rawDataItems = $parsed;
}
$combinedItems = [];
foreach ($rawDataItems as $i => $item) {
    $pdi = $pDataItems[$i] ?? null;
    $pkd = $pesKebutuhanData[$i] ?? null;
    $combinedItems[] = [
        'nama'              => $item['data']         ?? '',
        'tahun_dari'        => $item['tahun_dari']   ?? '',
        'tahun_sampai'      => $item['tahun_sampai'] ?? '',
        'nilai'             => $pdi['nilai']             ?? null,
        'status_perolehan'  => $pdi['status_perolehan']  ?? null,
        'untuk_perencanaan' => $pdi['untuk_perencanaan'] ?? null,
        'jenis_sumber'      => $pkd['jenis_sumber_data'] ?? null,
        'judul_sumber'      => $pkd['judul_sumber_data'] ?? null,
        'tahun_sumber'      => $pkd['tahun_sumber_data'] ?? null,
    ];
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function h($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function tglLong($d) { return $d ? date('d F Y', strtotime($d)) : '-'; }
function nilaiStars($v) {
    $v = intval($v);
    if (!$v) return '<span style="color:#9ca3af">—</span>';
    $color = $v <= 4 ? '#dc2626' : ($v <= 6 ? '#d97706' : ($v <= 8 ? '#16a34a' : '#059669'));
    return "<span style='font-size:1rem;font-weight:800;color:$color;'>$v"
         . "<span style='font-size:0.65rem;font-weight:600;opacity:0.65'>/10</span></span>";
}

$QUESTIONS = ['',
    'Informasi pelayanan pada unit layanan ini tersedia melalui media elektronik maupun non elektronik.',
    'Persyaratan pelayanan yang ditetapkan mudah dipenuhi/disiapkan oleh konsumen.',
    'Prosedur/alur pelayanan yang ditetapkan mudah diikuti/dilakukan.',
    'Jangka waktu penyelesaian pelayanan yang diterima sesuai dengan yang ditetapkan.',
    'Biaya pelayanan yang dibayarkan sesuai dengan biaya yang ditetapkan.',
    'Produk pelayanan yang diterima sesuai dengan yang dijanjikan.',
    'Sarana dan prasarana pendukung pelayanan memberikan kenyamanan.',
    'Data BPS mudah diakses.',
    'Petugas pelayanan dan/atau aplikasi pelayanan online merespon dengan baik.',
    'Petugas pelayanan dan/atau aplikasi pelayanan online mampu memberikan informasi yang jelas.',
    'Fasilitas pengaduan PST mudah diakses.',
    'Tidak ada diskriminasi dalam pelayanan.',
    'Tidak ada pelayanan di luar prosedur/kecurangan pelayanan.',
    'Tidak ada penerimaan gratifikasi.',
    'Tidak ada pungutan liar (pungli) dalam pelayanan.',
    'Tidak ada praktik percaloan dalam pelayanan.',
];

$jkLabel      = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$jenisLabel   = ['umum' => 'Umum', 'disabilitas' => 'Disabilitas', 'whatsapp' => 'WhatsApp', 'surat' => 'Via Surat'];
$sentimenLabel = ['negatif' => '😞 Negatif', 'normal' => '😐 Normal', 'positif' => '😊 Positif'];

$jenis   = $antrian['jenis'] ?? '';
$tanggal = $antrian['tanggal'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Pengunjung — <?= h($antrian['nama']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; min-height: 0 !important; }
            .print-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; break-inside: avoid; }
            .print-header { margin-bottom: 0.3rem; }
            .content-wrapper { padding-top: 0 !important; padding-bottom: 0 !important; }
            .content-wrapper > * { margin-top: 0.3rem !important; }

            /* Paksa tabel desktop tampil saat cetak */
            #section-data div.sm\:hidden { display: none !important; }
            #section-data div.hidden { display: block !important; }
            #section-data .overflow-x-auto { overflow: visible !important; }
            #section-data table { table-layout: fixed; width: 100%; border-collapse: collapse; }
            #section-data th, #section-data td {
                word-break: break-word;
                overflow-wrap: break-word;
                font-size: 0.6rem !important;
                padding: 3px 4px !important;
                vertical-align: top;
            }

            /* PDF 1 — Daftar Kebutuhan Data */
            body.pdf1 #section-penilaian { display: none !important; }
            body.pdf1 #section-data { display: none !important; }
            body.pdf1 .field-email { display: none !important; }
            body.pdf1 .field-telepon { display: none !important; }
            body.pdf1 .field-nip { display: none !important; }
            body.pdf1 #section-catatan { display: none !important; }
            body.pdf1 .print-title-pdf2 { display: none !important; }
            body.pdf1 .print-title-pdf1 { display: block !important; }
            body.pdf1 .print-card { padding: 0.5rem 0.75rem !important; margin-bottom: 0.3rem !important; }

            /* PDF 2 — Tindak Lanjut */
            body.pdf2 #section-identitas {
                display: none !important;
                height: 0 !important;
                min-height: 0 !important;
                max-height: 0 !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
            }
            body.pdf2 .print-title-pdf1 { display: none !important; }
            body.pdf2 .print-title-pdf2 { display: block !important; }
            body.pdf2 #section-penilaian { margin-top: 0 !important; }
            body.pdf2 #section-penilaian .print-card { padding: 0.35rem 0.6rem !important; break-inside: avoid; }
            body.pdf2 #section-penilaian .print-card > div:first-child { margin-bottom: 0.25rem !important; }
            body.pdf2 #section-penilaian .rounded-lg > div { padding-top: 0.28rem !important; padding-bottom: 0.28rem !important; padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
            body.pdf2 #section-penilaian .rounded-lg { margin-bottom: 0 !important; }
            body.pdf2 #section-penilaian p.text-xs { font-size: 0.72rem !important; line-height: 1.35 !important; }
            body.pdf2 #section-penilaian .w-6 { width: 1.1rem !important; height: 1.1rem !important; min-width: 1.1rem !important; font-size: 0.6rem !important; }
            /* Izinkan section-data pecah lintas halaman */
            body.pdf2 #section-data.print-card { break-inside: auto !important; }
            body.pdf2 #section-data > div { break-inside: auto !important; }
        }
        @page { margin: 0.8cm 1.5cm 1.5cm 1.5cm; }
        .print-only { display: none; }
        .score-badge {
            display: inline-block;
            padding: 0.1rem 0.4rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<!-- ── Print Header (only visible when printing) ────────────────────────────── -->
<div class="print-only print-header px-0">
    <?php include '../templat/kop_html.php'; ?>
    <h2 class="print-title-pdf1" style="display:none;font-size:11pt;font-weight:bold;color:#000;text-align:center;margin:1rem 0 0.2rem;">
        DAFTAR KEBUTUHAN DATA PENGUNJUNG PST
    </h2>
    <h2 class="print-title-pdf2" style="display:none;font-size:11pt;font-weight:bold;color:#000;text-align:center;margin:1rem 0 0.2rem;">
        TINDAK LANJUT PELAYANAN PENGUNJUNG PST
    </h2>
</div>

<!-- ── Screen Header ─────────────────────────────────────────────────────────── -->
<div class="no-print sticky top-0 z-10 bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-4xl mx-auto px-4 py-3 flex items-center gap-3 flex-wrap">
        <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-800 text-sm flex items-center gap-1.5 shrink-0">
            <i class="fas fa-arrow-left text-xs"></i> Kembali
        </a>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-gray-800 text-sm truncate"><?= h($antrian['nama']) ?></p>
            <p class="text-xs text-gray-500"><?= h($jenisLabel[$jenis] ?? $jenis) ?> &middot; <?= tglLong($tanggal) ?></p>
        </div>
        <div class="flex gap-2 shrink-0">
            <button onclick="cetakPDF(1)"
                class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                <i class="fas fa-file-pdf"></i>
                <span class="hidden sm:inline">PDF Lampiran 1</span>
                <span class="sm:hidden">PDF 1</span>
            </button>
            <button onclick="cetakPDF(2)"
                class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                <i class="fas fa-file-pdf"></i>
                <span class="hidden sm:inline">PDF Lampiran 2</span>
                <span class="sm:hidden">PDF 2</span>
            </button>
        </div>
    </div>
</div>

<div class="content-wrapper max-w-4xl mx-auto px-4 py-6 space-y-6">

<!-- ════════════════════════════════════════════════════════════════════════════
     BAGIAN 1 — Identitas & Layanan
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="section-identitas">
    <!-- Section label -->
    <div class="flex items-center gap-2 mb-3 no-print">
        <span class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
        <h2 class="font-bold text-gray-700 text-base">Identitas &amp; Data Layanan</h2>
    </div>

    <!-- ── Identitas Pengunjung + Data PES ── -->
    <div id="section-pes" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-4 print-card">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Identitas Pengunjung</h3>
        <?php
        $jenisLayanan = json_decode($pes['jenis_layanan'] ?? '[]', true) ?: [];
        $sarana       = json_decode($pes['sarana']       ?? '[]', true) ?: [];
        ?>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm mb-0">
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Nama Lengkap</dt>
                <dd class="font-semibold text-gray-800"><?= h($antrian['nama']) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Jenis Kunjungan</dt>
                <dd class="font-semibold text-gray-800"><?= h($jenisLabel[$jenis] ?? $jenis) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Tanggal Kunjungan</dt>
                <dd class="font-semibold text-gray-800"><?= tglLong($tanggal) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Jenis Kelamin</dt>
                <dd class="font-semibold text-gray-800"><?= h($jkLabel[$antrian['jk'] ?? ''] ?? ($antrian['jk'] ?? '-')) ?></dd>
            </div>
            <div class="field-telepon">
                <dt class="text-gray-500 text-xs mb-0.5">Nomor HP</dt>
                <dd class="font-semibold text-gray-800"><?= h($antrian['telepon'] ?: '—') ?></dd>
            </div>
            <div class="field-email">
                <dt class="text-gray-500 text-xs mb-0.5">Email</dt>
                <dd class="font-semibold text-gray-800"><?= h($antrian['email'] ?: '—') ?></dd>
            </div>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Kategori Instansi</dt>
                <dd class="font-semibold text-gray-800">
                    <?php if ($pes && !empty($pes['kategori_instansi'])): ?>
                        <?= h($pes['kategori_instansi']) ?>
                        <?php if (!empty($pes['kategori_instansi_lainnya'])): ?>
                        <span class="block text-xs font-normal text-gray-500"><?= h($pes['kategori_instansi_lainnya']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= h($antrian['instansi'] ?: '—') ?>
                    <?php endif; ?>
                </dd>
            </div>
            <?php if ($antrian['pendidikan']): ?>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Pendidikan Tertinggi</dt>
                <dd class="font-semibold text-gray-800"><?= h($antrian['pendidikan']) ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($antrian['kelompok_umur']): ?>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Kelompok Umur</dt>
                <dd class="font-semibold text-gray-800"><?= h($antrian['kelompok_umur']) ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($antrian['pekerjaan']): ?>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Pekerjaan</dt>
                <dd class="font-semibold text-gray-800"><?= h($antrian['pekerjaan']) ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($antrian['pemanfaatan_data']): ?>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Pemanfaatan Data</dt>
                <dd class="font-semibold text-gray-800"><?= h($antrian['pemanfaatan_data']) ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($pes): ?>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Petugas Utama</dt>
                <dd class="font-semibold text-gray-800">
                    <?= h($pes['petugas_nama'] ?? '—') ?>
                    <?php if ($pes['petugas_nip']): ?>
                    <span class="field-nip block text-xs font-normal text-gray-500">NIP <?= h($pes['petugas_nip']) ?></span>
                    <?php endif; ?>
                </dd>
            </div>
            <?php if (!empty($pesPembantu)): ?>
            <div>
                <dt class="text-gray-500 text-xs mb-0.5">Petugas Pembantu</dt>
                <dd class="space-y-0.5">
                    <?php foreach ($pesPembantu as $pmb): ?>
                    <div class="font-semibold text-gray-800">
                        <?= h($pmb['nama']) ?>
                        <?php if ($pmb['nip']): ?>
                        <span class="field-nip block text-xs font-normal text-gray-500">NIP <?= h($pmb['nip']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </dd>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($jenis === 'surat'): ?>
            <div class="sm:col-span-2">
                <dt class="text-gray-500 text-xs mb-0.5">Link Surat Masuk</dt>
                <dd id="link-surat-display" class="flex items-center gap-2 font-semibold text-gray-800">
                    <?php if (!empty($antrian['link_surat'])): ?>
                        <a href="<?= h($antrian['link_surat']) ?>" target="_blank" rel="noopener" class="text-blue-600 underline break-all text-sm"><?= h($antrian['link_surat']) ?></a>
                    <?php else: ?>
                        <span class="text-gray-400 italic font-normal text-sm">Belum diisi</span>
                    <?php endif; ?>
                    <button onclick="editLinkSurat()" class="no-print shrink-0 text-xs text-blue-500 hover:text-blue-700 underline">Edit</button>
                </dd>
                <dd id="link-surat-edit" class="hidden mt-1">
                    <div class="flex gap-2 items-center">
                        <input type="url" id="link-surat-input" value="<?= h($antrian['link_surat'] ?? '') ?>"
                               placeholder="https://drive.google.com/..."
                               class="flex-1 border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                        <button onclick="saveLinkSurat()" class="no-print shrink-0 bg-blue-600 text-white text-xs px-3 py-1.5 rounded hover:bg-blue-700 transition-colors">Simpan</button>
                        <button onclick="cancelLinkSurat()" class="no-print shrink-0 text-gray-500 text-xs px-2 py-1.5 hover:underline">Batal</button>
                    </div>
                    <p id="link-surat-err" class="hidden text-red-500 text-xs mt-1"></p>
                </dd>
            </div>
            <?php endif; ?>
        </dl>
        <?php if ($pes && (!empty($jenisLayanan) || !empty($sarana))): ?>
        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <?php if (!empty($jenisLayanan)): ?>
            <div class="sm:col-span-2">
                <dt class="text-gray-500 text-xs mb-1.5">Jenis Layanan</dt>
                <dd class="flex flex-wrap gap-1.5">
                    <?php foreach ($jenisLayanan as $jl): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200"><?= h($jl) ?></span>
                    <?php endforeach; ?>
                </dd>
            </div>
            <?php endif; ?>
            <?php if (!empty($sarana)): ?>
            <div class="sm:col-span-2">
                <dt class="text-gray-500 text-xs mb-1.5">Sarana</dt>
                <dd class="flex flex-wrap gap-1.5">
                    <?php foreach ($sarana as $sar): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200"><?= h($sar) ?></span>
                    <?php endforeach; ?>
                    <?php if (!empty($pes['sarana_lainnya'])): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200"><?= h($pes['sarana_lainnya']) ?></span>
                    <?php endif; ?>
                </dd>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Daftar Data yang Dibutuhkan ── -->
    <?php if (!empty($rawDataItems)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-4 print-card">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Data yang Dibutuhkan</h3>
        <ol class="space-y-2">
            <?php foreach ($rawDataItems as $i => $item): ?>
            <li class="flex items-start gap-2.5 text-sm">
                <span class="shrink-0 w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center mt-0.5"><?= $i + 1 ?></span>
                <div>
                    <span class="font-medium text-gray-800"><?= h($item['data'] ?? '') ?></span>
                    <?php if (!empty($item['tahun_dari']) || !empty($item['tahun_sampai'])): ?>
                    <span class="ml-1.5 text-xs text-gray-500">
                        (<?= h($item['tahun_dari'] ?? '—') ?>
                        <?php if (($item['tahun_sampai'] ?? '') && $item['tahun_sampai'] != $item['tahun_dari']): ?>
                        – <?= h($item['tahun_sampai']) ?>
                        <?php endif; ?>)
                    </span>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php endif; ?>
</div>


<!-- ════════════════════════════════════════════════════════════════════════════
     BAGIAN 2 — Penilaian Pelayanan
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="section-penilaian">
    <div class="flex items-center gap-2 mb-3 no-print">
        <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
        <h2 class="font-bold text-gray-700 text-base">Penilaian Pelayanan</h2>
    </div>

    <?php if ($penilaian): ?>
    <div class="bg-white rounded-xl shadow-sm border border-indigo-200 p-5 print-card">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-indigo-500">
                <i class="fas fa-star mr-1"></i>Survei Kepuasan Pelayanan
            </h3>
            <p class="text-xs text-gray-400">
                Diisi: <?= tglLong($penilaian['tanggal']) ?>
                <?php if ($penilaian['submitted_at']): ?>
                &middot; Submit: <?= h($penilaian['submitted_at']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 overflow-hidden divide-y divide-gray-100 mb-4">
            <?php for ($q = 1; $q <= 16; $q++):
                $val = intval($penilaian['q' . $q] ?? 0);
                $bg  = $q % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                $color = $val <= 4 ? '#dc2626' : ($val <= 6 ? '#d97706' : ($val <= 8 ? '#16a34a' : '#059669'));
            ?>
            <div class="flex items-center gap-2 px-3 py-2.5 <?= $bg ?>">
                <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center"><?= $q ?></span>
                <p class="flex-1 text-xs text-gray-600 leading-snug"><?= h($QUESTIONS[$q]) ?></p>
                <span class="shrink-0 pl-2 text-sm"><?= nilaiStars($val) ?></span>
            </div>
            <?php endfor; ?>
        </div>

    </div>
    <?php else: ?>
    <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-5 text-center text-sm text-gray-400">
        <i class="fas fa-star text-2xl mb-2 block"></i>
        Data penilaian belum diisi.
    </div>
    <?php endif; ?>
</div>


<!-- ════════════════════════════════════════════════════════════════════════════
     BAGIAN 3 — Rincian Kebutuhan Data
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="section-data">
    <div class="flex items-center gap-2 mb-3 no-print">
        <span class="w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shrink-0">3</span>
        <h2 class="font-bold text-gray-700 text-base">Rincian Kebutuhan Data</h2>
    </div>

    <?php if (!empty($combinedItems)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-emerald-200 print-card overflow-hidden">
        <!-- Subjudul tabel — hanya muncul saat cetak -->
        <div class="print-only px-3 pt-3 pb-1">
            <p style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#065f46;border-bottom:1px solid #d1fae5;padding-bottom:0.35rem;">Rincian Kebutuhan Data</p>
        </div>
        <!-- Mobile: card per item -->
        <div class="sm:hidden divide-y divide-gray-100">
            <?php foreach ($combinedItems as $i => $ci): ?>
            <div class="p-4 <?= $i % 2 === 0 ? '' : 'bg-gray-50' ?>">
                <div class="flex items-start gap-2 mb-2">
                    <span class="shrink-0 w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] font-bold flex items-center justify-center mt-0.5"><?= $i + 1 ?></span>
                    <p class="font-semibold text-gray-800 text-sm"><?= h($ci['nama']) ?></p>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs pl-7">
                    <dt class="text-gray-500">Tahun</dt>
                    <dd class="font-medium"><?= h($ci['tahun_dari'] ?: '—') ?><?= ($ci['tahun_sampai'] && $ci['tahun_sampai'] != $ci['tahun_dari']) ? '–' . h($ci['tahun_sampai']) : '' ?></dd>
                    <?php if ($ci['nilai'] !== null): ?>
                    <dt class="text-gray-500 col-nilai">Rating</dt>
                    <dd class="col-nilai"><?= nilaiStars($ci['nilai']) ?></dd>
                    <?php endif; ?>
                    <?php if ($ci['status_perolehan']): ?>
                    <dt class="text-gray-500 col-perolehan">Status Perolehan</dt>
                    <dd class="font-medium col-perolehan"><?= h($ci['status_perolehan']) ?></dd>
                    <?php endif; ?>
                    <?php if ($ci['untuk_perencanaan']): ?>
                    <dt class="text-gray-500 col-perencanaan">Perencanaan</dt>
                    <dd class="font-medium col-perencanaan"><?= h($ci['untuk_perencanaan']) ?></dd>
                    <?php endif; ?>
                    <?php if ($ci['jenis_sumber']): ?>
                    <dt class="text-gray-500 col-sumber">Jenis Sumber</dt>
                    <dd class="font-medium col-sumber"><?= h($ci['jenis_sumber']) ?></dd>
                    <?php endif; ?>
                    <?php if ($ci['judul_sumber']): ?>
                    <dt class="text-gray-500 col-sumber">Judul Sumber</dt>
                    <dd class="font-medium col-sumber"><?= h($ci['judul_sumber']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Desktop: table -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-emerald-50 text-emerald-800 border-b border-emerald-200">
                        <th class="px-3 py-2.5 text-left font-semibold w-6">#</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Nama Data</th>
                        <th class="px-3 py-2.5 text-center font-semibold whitespace-nowrap">Tahun</th>
                        <th class="px-3 py-2.5 text-center font-semibold th-nilai">Rating</th>
                        <th class="px-3 py-2.5 text-center font-semibold th-perolehan whitespace-nowrap">Status Perolehan</th>
                        <th class="px-3 py-2.5 text-center font-semibold th-perencanaan">Perencanaan</th>
                        <th class="px-3 py-2.5 text-center font-semibold th-sumber whitespace-nowrap">Jenis Sumber</th>
                        <th class="px-3 py-2.5 text-left font-semibold th-sumber w-1/3">Judul Sumber Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($combinedItems as $i => $ci): ?>
                    <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-emerald-50 transition-colors">
                        <td class="px-3 py-2.5 text-gray-400 font-medium"><?= $i + 1 ?></td>
                        <td class="px-3 py-2.5 font-medium text-gray-800"><?= h($ci['nama']) ?></td>
                        <td class="px-3 py-2.5 text-center text-gray-600 whitespace-nowrap">
                            <?= h($ci['tahun_dari'] ?: '—') ?>
                            <?= ($ci['tahun_sampai'] && $ci['tahun_sampai'] != $ci['tahun_dari']) ? '–' . h($ci['tahun_sampai']) : '' ?>
                        </td>
                        <td class="px-3 py-2.5 text-center col-nilai"><?= $ci['nilai'] !== null ? nilaiStars($ci['nilai']) : '<span class="text-gray-400">—</span>' ?></td>
                        <td class="px-3 py-2.5 text-center col-perolehan"><?= h($ci['status_perolehan'] ?: '—') ?></td>
                        <td class="px-3 py-2.5 text-center col-perencanaan"><?= h($ci['untuk_perencanaan'] ?: '—') ?></td>
                        <td class="px-3 py-2.5 text-center col-sumber"><?= h($ci['jenis_sumber'] ?: '—') ?></td>
                        <td class="px-3 py-2.5 col-sumber"><?= h($ci['judul_sumber'] ?: '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-5 text-center text-sm text-gray-400">
        <i class="fas fa-table text-2xl mb-2 block"></i>
        Tidak ada data kebutuhan yang tercatat.
    </div>
    <?php endif; ?>
</div>

<!-- ── Catatan & Saran (setelah tabel rincian data) ───────────────────────────── -->
<?php if ($penilaian && !empty($penilaian['catatan']) && trim($penilaian['catatan'])): ?>
<div id="section-catatan" class="bg-white rounded-xl shadow-sm border border-purple-200 p-5 print-card">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Catatan &amp; Saran</p>
    <div class="bg-purple-50 border border-purple-100 rounded-lg px-4 py-3 text-sm text-gray-700 leading-relaxed">
        <?= nl2br(h($penilaian['catatan'])) ?>
    </div>
</div>
<?php endif; ?>

</div><!-- /max-w-4xl -->

<!-- ── PDF Export Modal / Toast ──────────────────────────────────────────────── -->
<div id="pdf-toast" class="no-print fixed bottom-4 right-4 z-50 hidden bg-gray-800 text-white text-sm px-4 py-2.5 rounded-xl shadow-lg transition-all">
    Menyiapkan PDF…
</div>

<script>
function cetakPDF(mode) {
    var toast = document.getElementById('pdf-toast');
    toast.classList.remove('hidden');

    // Cabut section-identitas SEBELUM menambah body class agar Chrome tidak
    // pernah melihat elemen dengan break-inside:avoid dalam state tersembunyi.
    var detached = null, detachedParent = null, detachedNext = null;
    if (mode === 2) {
        var el = document.getElementById('section-identitas');
        if (el) {
            detachedParent = el.parentNode;
            detachedNext   = el.nextSibling;
            detachedParent.removeChild(el);
            detached = el;
        }
    }

    document.body.className = document.body.className.replace(/\bpdf\d\b/g, '').trim();
    document.body.classList.add('pdf' + mode);

    setTimeout(function() {
        toast.classList.add('hidden');
        window.print();
        setTimeout(function() {
            document.body.classList.remove('pdf' + mode);
            if (detached && detachedParent) {
                detachedNext
                    ? detachedParent.insertBefore(detached, detachedNext)
                    : detachedParent.appendChild(detached);
            }
        }, 500);
    }, 400);
}
</script>
<?php if ($jenis === 'surat'): ?>
<script>
const _APP_BASE = '<?= APP_BASE ?>';
function editLinkSurat() {
    document.getElementById('link-surat-display').classList.add('hidden');
    document.getElementById('link-surat-edit').classList.remove('hidden');
    document.getElementById('link-surat-input').focus();
}
function cancelLinkSurat() {
    document.getElementById('link-surat-display').classList.remove('hidden');
    document.getElementById('link-surat-edit').classList.add('hidden');
    document.getElementById('link-surat-err').classList.add('hidden');
}
function saveLinkSurat() {
    var link = document.getElementById('link-surat-input').value.trim();
    var err  = document.getElementById('link-surat-err');
    err.classList.add('hidden');
    if (link && !/^https?:\/\/.+/.test(link)) {
        err.textContent = 'URL harus diawali dengan http:// atau https://';
        err.classList.remove('hidden');
        return;
    }
    fetch(_APP_BASE + '/action/save_link_surat.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=<?= $id ?>&link=' + encodeURIComponent(link)
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (data.ok) {
            var display = document.getElementById('link-surat-display');
            var html = link
                ? '<a href="' + link.replace(/"/g,'&quot;') + '" target="_blank" rel="noopener" class="text-blue-600 underline break-all text-sm">' + link.replace(/</g,'&lt;') + '</a>'
                : '<span class="text-gray-400 italic font-normal text-sm">Belum diisi</span>';
            display.innerHTML = html + ' <button onclick="editLinkSurat()" class="no-print shrink-0 text-xs text-blue-500 hover:text-blue-700 underline">Edit</button>';
            cancelLinkSurat();
        } else {
            err.textContent = data.msg || 'Gagal menyimpan.';
            err.classList.remove('hidden');
        }
    });
}
</script>
<?php endif; ?>
</body>
</html>

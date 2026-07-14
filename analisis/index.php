<?php
/**
 * Analisis Kepuasan Pengguna Data PST
 * ------------------------------------
 * Dashboard periodik (Tahunan / Triwulanan) untuk menyusun laporan kepuasan
 * pengguna data. Tiap triwulan, petugas dapat memilih responden mana yang
 * dimasukkan ke dalam analisis (default: semua masuk, dapat di-exclude).
 *
 * Universe responden  : antrian PST (whatsapp/surat ATAU kunjungan_pst=1)
 * Exclude disimpan per : (tahun, triwulan, antrian_id) di tabel analisis_exclude
 * Indeks dihitung dari : penilaian q1..q16 (skala 1-4)
 *   IKM  = rata² NRR q1..q16 × 25
 *   IPKP = rata² NRR q1..q11 × 25   (Indeks Persepsi Kualitas Pelayanan)
 *   IPAK = rata² NRR q12..q16 × 25  (Indeks Persepsi Anti Korupsi)
 */
include __DIR__ . '/../app/db.php';

// ── Helpers ───────────────────────────────────────────────────────────────────
function quarterOf(string $tanggal): int {
    $m = (int)date('n', strtotime($tanggal));
    return intdiv($m - 1, 3) + 1;
}
function dist(array $rows, string $col): array {
    $d = [];
    foreach ($rows as $r) { $v = trim((string)($r[$col] ?? '')); if ($v !== '') $d[$v] = ($d[$v] ?? 0) + 1; }
    arsort($d); return $d;
}
// Grade berdasar skor indeks 0–100 (konversi Permenpan PANRB 14/2017)
function ikmGrade(?float $score): array {
    if ($score === null)   return ['–', 'Belum ada data', 'text-gray-500',   'bg-gray-100'];
    if ($score >= 88.31)   return ['A', 'Sangat Baik',    'text-green-700',  'bg-green-100'];
    if ($score >= 76.61)   return ['B', 'Baik',           'text-blue-700',   'bg-blue-100'];
    if ($score >= 65.00)   return ['C', 'Kurang Baik',    'text-yellow-700', 'bg-yellow-100'];
    return                       ['D', 'Tidak Baik',      'text-red-700',    'bg-red-100'];
}

// ── Periode aktif ─────────────────────────────────────────────────────────────
$bulanIndo = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$twLabel   = [0 => 'Tahunan (Jan–Des)', 1 => 'Triwulan I (Jan–Mar)', 2 => 'Triwulan II (Apr–Jun)',
              3 => 'Triwulan III (Jul–Sep)', 4 => 'Triwulan IV (Okt–Des)'];

$selYear = max(2020, min(2035, (int)($_GET['tahun'] ?? date('Y'))));
$selTw   = (int)($_GET['tw'] ?? 0);
if (!in_array($selTw, [0, 1, 2, 3, 4], true)) $selTw = 0;

if ($selTw === 0) {
    $firstDay = sprintf('%04d-01-01', $selYear);
    $lastDay  = sprintf('%04d-12-31', $selYear);
} else {
    $startM   = ($selTw - 1) * 3 + 1;
    $firstDay = sprintf('%04d-%02d-01', $selYear, $startM);
    $lastDay  = (new DateTime(sprintf('%04d-%02d-01', $selYear, $startM + 2)))
                ->modify('last day of this month')->format('Y-m-d');
}

// ── Queries ───────────────────────────────────────────────────────────────────
$st = $mysqli->prepare("SELECT * FROM antrian WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal, id");
$st->bind_param("ss", $firstDay, $lastDay); $st->execute();
$allAntrian = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

$st = $mysqli->prepare(
    "SELECT pn.* FROM penilaian pn JOIN antrian a ON a.id = pn.antrian_id
     WHERE a.tanggal BETWEEN ? AND ?");
$st->bind_param("ss", $firstDay, $lastDay); $st->execute();
$penilaianAll = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
$penByAntrian = [];
foreach ($penilaianAll as $p) $penByAntrian[$p['antrian_id']] = $p;

$st = $mysqli->prepare(
    "SELECT pes.* FROM pes JOIN antrian a ON a.id = pes.antrian_id
     WHERE a.tanggal BETWEEN ? AND ?");
$st->bind_param("ss", $firstDay, $lastDay); $st->execute();
$pesAll = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
$pesByAntrian = [];
foreach ($pesAll as $p) $pesByAntrian[$p['antrian_id']] = $p;

// Exclude set untuk seluruh tahun (key "tw:antrian_id")
$st = $mysqli->prepare("SELECT triwulan, antrian_id FROM analisis_exclude WHERE tahun = ?");
$st->bind_param("i", $selYear); $st->execute();
$excludeRows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
$excludeSet = [];
foreach ($excludeRows as $e) $excludeSet[$e['triwulan'] . ':' . $e['antrian_id']] = true;

$qKeys = [];
for ($i = 1; $i <= 16; $i++) $qKeys[] = "q$i";

// ── Universe PST & seleksi ─────────────────────────────────────────────────────
$isPST = fn($r) => in_array($r['jenis'], ['whatsapp', 'surat']) || (!empty($r['kunjungan_pst']) && $r['kunjungan_pst'] == 1);
$pstRows = array_values(array_filter($allAntrian, $isPST));

$respondents = [];   // baris untuk tabel kurasi (semua PST di periode)
$included    = [];    // baris yang masuk analisis
foreach ($pstRows as $r) {
    $tw  = quarterOf($r['tanggal']);
    $exc = isset($excludeSet["$tw:{$r['id']}"]);
    $pen = $penByAntrian[$r['id']] ?? null;
    $r['_tw']       = $tw;
    $r['_excluded'] = $exc;
    $r['_survei']   = $pen !== null;
    $r['_pes']      = isset($pesByAntrian[$r['id']]);
    if ($pen !== null) {
        $vals = array_filter(array_map(fn($q) => $pen[$q], $qKeys), fn($v) => $v !== null && $v !== '');
        $r['_avg'] = count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
    } else {
        $r['_avg'] = null;
    }
    $respondents[]  = $r;
    if (!$exc) $included[] = $r;
}
$includedIds = array_column($included, 'id');
$includedSet = array_flip($includedIds);

// ── Indeks IKM / IPKP / IPAK ────────────────────────────────────────────────────
$qLabels = [
    'q1' => 'Informasi layanan tersedia',        'q2' => 'Persyaratan mudah dipenuhi',
    'q3' => 'Prosedur mudah diikuti',            'q4' => 'Jangka waktu sesuai',
    'q5' => 'Biaya sesuai ketentuan',            'q6' => 'Produk sesuai dijanjikan',
    'q7' => 'Sarana & prasarana nyaman',         'q8' => 'Data BPS mudah diakses',
    'q9' => 'Petugas merespons baik',            'q10' => 'Informasi diberikan jelas',
    'q11' => 'Fasilitas pengaduan mudah',        'q12' => 'Tidak ada diskriminasi',
    'q13' => 'Tidak ada di luar prosedur',       'q14' => 'Tidak ada gratifikasi',
    'q15' => 'Tidak ada pungutan liar',          'q16' => 'Tidak ada percaloan',
];
// Penilaian hanya dari responden yang masuk
$penIncluded = [];
foreach ($included as $r) if (isset($penByAntrian[$r['id']])) $penIncluded[] = $penByAntrian[$r['id']];

$nrr = [];
foreach ($qKeys as $q) {
    $vals = array_filter(array_column($penIncluded, $q), fn($v) => $v !== null && $v !== '');
    $nrr[$q] = count($vals) ? array_sum($vals) / count($vals) : null;
}
$avgOf = function(array $keys) use ($nrr) {
    $vals = array_filter(array_map(fn($k) => $nrr[$k], $keys), fn($v) => $v !== null);
    return count($vals) ? array_sum($vals) / count($vals) : null;
};
// NRR skala 1–10 → indeks 0–100 dikali 10
$nrrIKM  = $avgOf($qKeys);
$nrrIPKP = $avgOf(array_slice($qKeys, 0, 11));   // q1..q11
$nrrIPAK = $avgOf(array_slice($qKeys, 11, 5));   // q12..q16
$ikm  = $nrrIKM  !== null ? round($nrrIKM  * 10, 2) : null;
$ipkp = $nrrIPKP !== null ? round($nrrIPKP * 10, 2) : null;
$ipak = $nrrIPAK !== null ? round($nrrIPAK * 10, 2) : null;

// ── Distribusi demografi (responden masuk) ──────────────────────────────────────
$jkRaw   = dist($included, 'jk');
$jkDist  = [];
foreach ($jkRaw as $k => $v) $jkDist[$k === 'L' ? 'Laki-laki' : ($k === 'P' ? 'Perempuan' : $k)] = $v;
$umurDist  = dist($included, 'kelompok_umur');
$pendDist  = dist($included, 'pendidikan');
$kerjaDist = dist($included, 'pekerjaan');
$manfaatDist = dist($included, 'pemanfaatan_data');

// Disabilitas
$disYa = count(array_filter($included, fn($r) => $r['jenis'] === 'disabilitas'));
$disDist = ['Non-Disabilitas' => count($included) - $disYa, 'Disabilitas' => $disYa];
$jenisDisDist = dist(array_filter($included, fn($r) => $r['jenis'] === 'disabilitas'), 'jenis_disabilitas');

// Instansi (kategori dari PES), jenis layanan & sarana (PES, multi)
$instDist = []; $layDist = []; $saranaDist = [];
foreach ($included as $r) {
    $p = $pesByAntrian[$r['id']] ?? null;
    if (!$p) continue;
    $k = $p['kategori_instansi'] ?: '';
    if ($k === 'Lainnya' && !empty($p['kategori_instansi_lainnya'])) $k = $p['kategori_instansi_lainnya'];
    if ($k !== '') $instDist[$k] = ($instDist[$k] ?? 0) + 1;
    foreach (json_decode($p['jenis_layanan'] ?? '[]', true) ?: [] as $j) $layDist[$j] = ($layDist[$j] ?? 0) + 1;
    foreach (json_decode($p['sarana'] ?? '[]', true) ?: [] as $s) $saranaDist[$s] = ($saranaDist[$s] ?? 0) + 1;
}
arsort($instDist); arsort($layDist); arsort($saranaDist);

// ── Totals ──────────────────────────────────────────────────────────────────────
$totResp   = count($included);
$totSurvei = count($penIncluded);
$totPES    = count(array_filter($included, fn($r) => isset($pesByAntrian[$r['id']])));
$totExcl   = count($respondents) - count($included);

// Charts payload
$charts = [
    ['jkChart',     'Jenis Kelamin',          'doughnut', $jkDist],
    ['disChart',    'Status Disabilitas',     'doughnut', $disDist],
    ['umurChart',   'Kelompok Umur',          'bar',      $umurDist],
    ['pendChart',   'Pendidikan Ditamatkan',  'bar',      $pendDist],
    ['kerjaChart',  'Pekerjaan Utama',        'bar',      $kerjaDist],
    ['manfaatChart','Pemanfaatan Hasil Data', 'bar',      $manfaatDist],
    ['instChart',   'Instansi / Institusi',   'bar',      $instDist],
    ['layChart',    'Jenis Layanan',          'bar',      $layDist],
    ['saranaChart', 'Sarana yang Digunakan',  'bar',      $saranaDist],
    ['jdisChart',   'Ragam Disabilitas',      'bar',      $jenisDisDist],
];
$nrrChartData = [];
foreach ($qKeys as $q) $nrrChartData[$qLabels[$q]] = $nrr[$q] !== null ? round($nrr[$q], 3) : 0;

$page_title = 'Analisis Kepuasan Pengguna Data';
include __DIR__ . '/../app/partials/_head.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<style>
    body { background: #f1f5f9; }
    .card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; }
</style>
</head>
<body class="text-gray-800">
<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Header & periode -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <a href="<?= APP_BASE ?>/menu" class="text-sm text-blue-600 hover:underline">&larr; Menu</a>
            <h1 class="text-2xl font-bold mt-1">📈 Analisis Kepuasan Pengguna Data</h1>
            <p class="text-gray-500 text-sm"><?= htmlspecialchars($twLabel[$selTw]) ?> · Tahun <?= $selYear ?></p>
        </div>
        <form method="get" class="flex flex-wrap items-end gap-2 bg-white p-3 rounded-xl border">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tahun</label>
                <select name="tahun" class="border p-2 rounded text-sm">
                    <?php for ($y = (int)date('Y') + 1; $y >= 2023; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $selYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Periode</label>
                <select name="tw" class="border p-2 rounded text-sm">
                    <?php foreach ($twLabel as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= $k === $selTw ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Tampilkan</button>
        </form>
    </div>

    <!-- KPI -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Jumlah Responden</div>
            <div class="text-3xl font-bold text-blue-600 mt-1"><?= $totResp ?></div>
            <div class="text-xs text-gray-400 mt-1"><?= $totExcl ?> dikeluarkan</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Survei Kepuasan Terisi</div>
            <div class="text-3xl font-bold text-teal-600 mt-1"><?= $totSurvei ?></div>
            <div class="text-xs text-gray-400 mt-1">dari <?= $totResp ?> responden</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">PES Terisi</div>
            <div class="text-3xl font-bold text-indigo-600 mt-1"><?= $totPES ?></div>
            <div class="text-xs text-gray-400 mt-1">Post-Event Survey</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Disabilitas</div>
            <div class="text-3xl font-bold text-purple-600 mt-1"><?= $disYa ?></div>
            <div class="text-xs text-gray-400 mt-1">responden</div>
        </div>
    </div>

    <!-- Indeks IKM / IPKP / IPAK -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <?php
        $idxCards = [
            ['IKM',  'Indeks Kepuasan Masyarakat',         $ikm],
            ['IPKP', 'Indeks Persepsi Kualitas Pelayanan', $ipkp],
            ['IPAK', 'Indeks Persepsi Anti Korupsi',       $ipak],
        ];
        foreach ($idxCards as [$kode, $nama, $skor]):
            [$grade, $ket, $tx, $bg] = ikmGrade($skor);
        ?>
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-bold text-gray-700"><?= $kode ?></div>
                    <div class="text-xs text-gray-400"><?= $nama ?></div>
                </div>
                <span class="<?= $tx ?> <?= $bg ?> text-xs font-bold px-2.5 py-1 rounded-full">Mutu <?= $grade ?></span>
            </div>
            <div class="mt-3 flex items-end gap-2">
                <span class="text-4xl font-extrabold <?= $tx ?>"><?= $skor !== null ? number_format($skor, 2) : '–' ?></span>
                <span class="text-gray-400 text-sm mb-1">/ 100</span>
            </div>
            <div class="text-xs <?= $tx ?> font-medium mt-1"><?= $ket ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- NRR per unsur -->
    <div class="card p-5 mb-6">
        <h2 class="font-semibold text-gray-700 mb-3">Nilai Rata-Rata (NRR) per Unsur · skala 1–4</h2>
        <div style="height:380px"><canvas id="nrrChart"></canvas></div>
    </div>

    <!-- Toggle mode tampilan -->
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-gray-700 text-lg">Distribusi Demografi &amp; Layanan</h2>
        <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm" id="modeToggle">
            <button type="button" data-mode="count" class="mode-btn px-3 py-1.5 font-medium bg-blue-600 text-white">Jumlah</button>
            <button type="button" data-mode="percent" class="mode-btn px-3 py-1.5 font-medium bg-white text-gray-600">Persentase</button>
        </div>
    </div>

    <!-- Grid distribusi -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($charts as [$id, $title, $type, $data]): ?>
        <div class="card p-5">
            <h2 class="font-semibold text-gray-700 mb-3"><?= htmlspecialchars($title) ?>
                <span class="text-xs font-normal text-gray-400">(<?= array_sum($data) ?>)</span></h2>
            <?php if (empty($data)): ?>
                <p class="text-sm text-gray-400 py-8 text-center">Belum ada data pada periode ini.</p>
            <?php else: ?>
                <div style="height:<?= $type === 'doughnut' ? '260' : '300' ?>px"><canvas id="<?= $id ?>"></canvas></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Kurasi responden -->
    <div class="card p-5 mb-10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
            <div>
                <h2 class="font-semibold text-gray-700">Kurasi Responden</h2>
                <p class="text-xs text-gray-500">Hilangkan centang untuk mengeluarkan responden dari analisis.
                   Indeks &amp; grafik dihitung ulang otomatis. Pengaturan disimpan per triwulan.</p>
            </div>
            <span class="text-xs text-gray-500"><?= count($included) ?> masuk / <?= count($respondents) ?> total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2 pr-3">Masuk</th>
                        <th class="py-2 pr-3">Tanggal</th>
                        <?php if ($selTw === 0): ?><th class="py-2 pr-3">TW</th><?php endif; ?>
                        <th class="py-2 pr-3">Nama</th>
                        <th class="py-2 pr-3">Jenis</th>
                        <th class="py-2 pr-3 text-center">Survei</th>
                        <th class="py-2 pr-3 text-center">Rata² Nilai</th>
                        <th class="py-2 pr-3 text-center">PES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($respondents)): ?>
                        <tr><td colspan="8" class="py-6 text-center text-gray-400">Tidak ada responden PST pada periode ini.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($respondents as $r):
                        $jbadge = ['umum' => 'bg-blue-100 text-blue-700', 'disabilitas' => 'bg-purple-100 text-purple-700',
                                   'whatsapp' => 'bg-green-100 text-green-700', 'surat' => 'bg-amber-100 text-amber-700'];
                        $avgTx = $r['_avg'] !== null ? ikmGrade($r['_avg'] * 10)[2] : 'text-gray-300';
                    ?>
                    <tr class="border-b last:border-0 <?= $r['_excluded'] ? 'opacity-50' : '' ?>" data-row="<?= $r['id'] ?>">
                        <td class="py-2 pr-3">
                            <input type="checkbox" class="resp-toggle w-4 h-4 accent-blue-600"
                                   data-id="<?= $r['id'] ?>" <?= $r['_excluded'] ? '' : 'checked' ?>>
                        </td>
                        <td class="py-2 pr-3 whitespace-nowrap text-gray-600"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                        <?php if ($selTw === 0): ?><td class="py-2 pr-3 text-gray-500">TW<?= $r['_tw'] ?></td><?php endif; ?>
                        <td class="py-2 pr-3 font-medium"><?= htmlspecialchars($r['nama']) ?></td>
                        <td class="py-2 pr-3">
                            <span class="text-xs px-2 py-0.5 rounded-full <?= $jbadge[$r['jenis']] ?? 'bg-gray-100 text-gray-600' ?>"><?= htmlspecialchars($r['jenis']) ?></span>
                        </td>
                        <td class="py-2 pr-3 text-center"><?= $r['_survei'] ? '✅' : '<span class="text-gray-300">–</span>' ?></td>
                        <td class="py-2 pr-3 text-center font-semibold <?= $avgTx ?>"><?= $r['_avg'] !== null ? number_format($r['_avg'], 2) : '–' ?></td>
                        <td class="py-2 pr-3 text-center"><?= $r['_pes'] ? '✅' : '<span class="text-gray-300">–</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const PALETTE = ['#3b82f6','#14b8a6','#8b5cf6','#f59e0b','#ef4444','#10b981','#6366f1','#ec4899','#06b6d4','#84cc16','#f97316','#a855f7'];
const charts = <?= json_encode(array_map(fn($c) => ['id' => $c[0], 'type' => $c[2], 'data' => $c[3]], $charts), JSON_UNESCAPED_UNICODE) ?>;

let currentMode = 'count'; // 'count' | 'percent'
const chartInstances = {};

function valuesForMode(rawData, mode) {
    const raw = Object.values(rawData);
    if (mode !== 'percent') return raw;
    const total = raw.reduce((a, b) => a + b, 0) || 1;
    return raw.map(v => Math.round((v / total) * 1000) / 10);
}

function makeChart(cfg) {
    const el = document.getElementById(cfg.id);
    if (!el) return;
    const labels = Object.keys(cfg.data);
    const isDoughnut = cfg.type === 'doughnut';
    const chart = new Chart(el, {
        type: cfg.type,
        data: {
            labels,
            datasets: [{
                data: valuesForMode(cfg.data, currentMode),
                backgroundColor: isDoughnut ? labels.map((_, i) => PALETTE[i % PALETTE.length]) : '#3b82f6',
                borderRadius: isDoughnut ? 0 : 4,
                borderWidth: isDoughnut ? 2 : 0,
                borderColor: '#fff'
            }]
        },
        options: {
            indexAxis: isDoughnut ? 'x' : 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: isDoughnut, position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const v = ctx.parsed.x !== undefined ? ctx.parsed.x : ctx.parsed;
                            const txt = currentMode === 'percent' ? `${v}%` : `${v} responden`;
                            return `${ctx.label}: ${txt}`;
                        }
                    }
                }
            },
            scales: isDoughnut ? {} : {
                x: {
                    beginAtZero: true,
                    max: currentMode === 'percent' ? 100 : undefined,
                    ticks: { callback: v => currentMode === 'percent' ? v + '%' : v }
                }
            }
        }
    });
    chartInstances[cfg.id] = { chart, raw: cfg.data, isDoughnut };
}
charts.forEach(makeChart);

function setMode(mode) {
    currentMode = mode;
    Object.values(chartInstances).forEach(({ chart, raw, isDoughnut }) => {
        chart.data.datasets[0].data = valuesForMode(raw, mode);
        if (!isDoughnut) chart.options.scales.x.max = mode === 'percent' ? 100 : undefined;
        chart.update();
    });
    document.querySelectorAll('.mode-btn').forEach(btn => {
        const active = btn.dataset.mode === mode;
        btn.classList.toggle('bg-blue-600', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('bg-white', !active);
        btn.classList.toggle('text-gray-600', !active);
    });
}
document.querySelectorAll('.mode-btn').forEach(btn => {
    btn.addEventListener('click', () => setMode(btn.dataset.mode));
});

// NRR per unsur (1-4)
const nrrData = <?= json_encode($nrrChartData, JSON_UNESCAPED_UNICODE) ?>;
new Chart(document.getElementById('nrrChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(nrrData),
        datasets: [{
            label: 'NRR', data: Object.values(nrrData),
            backgroundColor: Object.values(nrrData).map(v => v >= 8.831 ? '#16a34a' : v >= 7.661 ? '#2563eb' : v >= 6.5 ? '#d97706' : '#dc2626'),
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, max: 10, ticks: { stepSize: 2 } } }
    }
});

// Toggle responden → simpan exclude, lalu reload untuk hitung ulang
document.querySelectorAll('.resp-toggle').forEach(cb => {
    cb.addEventListener('change', function () {
        const id = this.dataset.id;
        const include = this.checked ? 1 : 0;
        this.disabled = true;
        fetch('<?= APP_BASE ?>/analisis/action/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'antrian_id=' + encodeURIComponent(id) + '&include=' + include
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) { location.reload(); }
            else { alert(res.message || 'Gagal menyimpan.'); this.checked = !this.checked; this.disabled = false; }
        })
        .catch(() => { alert('Kesalahan jaringan.'); this.checked = !this.checked; this.disabled = false; });
    });
});
</script>
</body>
</html>

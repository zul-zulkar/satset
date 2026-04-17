<?php
/**
 * Dashboard Laporan Mingguan PST
 * Pekan = Senin s/d Jumat; nomor pekan berdasarkan bulan di mana Senin-nya berada.
 */
include __DIR__ . '/../db.php';

// ── Helpers ──────────────────────────────────────────────────────────────────
function getWeeksInMonth(int $year, int $month): array {
    $weeks = [];
    $cur   = new DateTime(sprintf('%04d-%02d-01', $year, $month));
    $dow   = (int)$cur->format('N');
    if ($dow !== 1) $cur->modify('next Monday');
    $n = 1;
    while ((int)$cur->format('n') === $month) {
        $mon = clone $cur;
        $fri = (clone $cur)->modify('+4 days');
        $weeks[] = ['num' => $n++, 'monday' => $mon->format('Y-m-d'), 'friday' => $fri->format('Y-m-d')];
        $cur->modify('+7 days');
    }
    return $weeks;
}

function ikmGrade(float $v): array {
    if ($v >= 3.5325) return ['A', 'Sangat Baik',   'text-green-700',  'bg-green-100'];
    if ($v >= 2.5100) return ['B', 'Baik',           'text-blue-700',   'bg-blue-100'];
    if ($v >= 1.7600) return ['C', 'Kurang Baik',    'text-yellow-700', 'bg-yellow-100'];
    return                    ['D', 'Tidak Baik',    'text-red-700',    'bg-red-100'];
}

// ── Tentukan pekan aktif ─────────────────────────────────────────────────────
$todayDt  = new DateTime();
$todayDow = (int)$todayDt->format('N');
$thisMon  = clone $todayDt;
if ($todayDow !== 1) $thisMon->modify('-' . ($todayDow - 1) . ' days');

$defYear  = (int)$thisMon->format('Y');
$defMonth = (int)$thisMon->format('n');
$defWeeks = getWeeksInMonth($defYear, $defMonth);
$defWeek  = 1;
foreach ($defWeeks as $w) {
    if ($w['monday'] === $thisMon->format('Y-m-d')) { $defWeek = $w['num']; break; }
}

$selYear  = max(2020, min(2030, (int)($_GET['tahun'] ?? $defYear)));
$selMonth = max(1, min(12, (int)($_GET['bulan'] ?? $defMonth)));
$selWeek  = (int)($_GET['pekan'] ?? $defWeek);
$filterJenis = in_array(($_GET['filter_jenis'] ?? ''), ['whatsapp','surat','langsung','umum','disabilitas'])
    ? $_GET['filter_jenis'] : 'all';
$fParam = $filterJenis !== 'all' ? '&filter_jenis=' . urlencode($filterJenis) : '';

$weeksInMonth = getWeeksInMonth($selYear, $selMonth);
$selWeek      = max(1, min(count($weeksInMonth), $selWeek));
$activeWeek   = $weeksInMonth[$selWeek - 1];
$monday       = $activeWeek['monday'];
$friday       = $activeWeek['friday'];

// ── Prev/next navigation ──────────────────────────────────────────────────────
$pW = $selWeek - 1; $pM = $selMonth; $pY = $selYear;
if ($pW < 1) { $pM--; if ($pM < 1) { $pM = 12; $pY--; } $pW = count(getWeeksInMonth($pY, $pM)); }
$nW = $selWeek + 1; $nM = $selMonth; $nY = $selYear;
if ($nW > count($weeksInMonth)) { $nM++; if ($nM > 12) { $nM = 1; $nY++; } $nW = 1; }

$prevUrl = APP_BASE . "/laporan/minggu?tahun=$pY&bulan=$pM&pekan=$pW$fParam";
$nextUrl = APP_BASE . "/laporan/minggu?tahun=$nY&bulan=$nM&pekan=$nW$fParam";

// ── Hari kerja (Mon–Fri) ──────────────────────────────────────────────────────
$dayKeys  = [];
$dt = new DateTime($monday);
for ($i = 0; $i < 5; $i++) { $dayKeys[] = $dt->format('Y-m-d'); $dt->modify('+1 day'); }
$dayNamesShort = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'];
$dayNamesFull  = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

// ── Queries ───────────────────────────────────────────────────────────────────
// 1. Antrian
$st = $mysqli->prepare("SELECT * FROM antrian WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal, id");
$st->bind_param("ss", $monday, $friday); $st->execute();
$allAntrian = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

$isPST = fn($r) => $r['jenis'] === 'whatsapp' || (!empty($r['kunjungan_pst']) && $r['kunjungan_pst'] == 1);
$pstRows    = array_values(array_filter($allAntrian, $isPST));
$nonPstRows = array_values(array_filter($allAntrian, fn($r) => !($isPST)($r)));

// Apply jenis filter to PST rows
if ($filterJenis === 'whatsapp') {
    $pstRows = array_values(array_filter($pstRows, fn($r) => $r['jenis'] === 'whatsapp'));
} elseif ($filterJenis === 'surat') {
    $pstRows = array_values(array_filter($pstRows, fn($r) => $r['jenis'] === 'surat'));
} elseif ($filterJenis === 'langsung') {
    $pstRows = array_values(array_filter($pstRows, fn($r) => in_array($r['jenis'], ['umum', 'disabilitas'])));
} elseif ($filterJenis === 'umum') {
    $pstRows = array_values(array_filter($pstRows, fn($r) => $r['jenis'] === 'umum'));
} elseif ($filterJenis === 'disabilitas') {
    $pstRows = array_values(array_filter($pstRows, fn($r) => $r['jenis'] === 'disabilitas'));
}

// 2. Absensi piket
$st = $mysqli->prepare(
    "SELECT ap.pegawai_id, ap.tanggal, ap.jam_masuk, ap.jam_keluar, p.nama, p.jabatan
     FROM absensi_piket ap JOIN pegawai p ON p.id = ap.pegawai_id
     WHERE ap.tanggal BETWEEN ? AND ? ORDER BY p.nama, ap.tanggal"
);
$st->bind_param("ss", $monday, $friday); $st->execute();
$absensiRows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

// 3. Penilaian
$st = $mysqli->prepare(
    "SELECT pn.* FROM penilaian pn JOIN antrian a ON a.id = pn.antrian_id
     WHERE a.tanggal BETWEEN ? AND ?"
);
$st->bind_param("ss", $monday, $friday); $st->execute();
$penilaianRows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

// 4. PES
$st = $mysqli->prepare(
    "SELECT pes.*, a.nama AS nama_pengunjung, a.instansi, a.tanggal AS tgl_kunjungan
     FROM pes JOIN antrian a ON a.id = pes.antrian_id
     WHERE a.tanggal BETWEEN ? AND ? ORDER BY a.tanggal"
);
$st->bind_param("ss", $monday, $friday); $st->execute();
$pesRows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

// ── Pengolahan ────────────────────────────────────────────────────────────────
// Kunjungan per hari
$visitByDay = [];
foreach ($dayKeys as $d) {
    $p = array_filter($pstRows,    fn($r) => $r['tanggal'] === $d);
    $n = array_filter($nonPstRows, fn($r) => $r['tanggal'] === $d);
    $jmlPst = 0;
    foreach ($p as $r) $jmlPst += $r['jenis'] === 'whatsapp' ? 1 : max(1, (int)($r['jumlah_orang'] ?? 1));
    $visitByDay[$d] = ['pst' => count($p), 'non' => count($n), 'jml' => $jmlPst];
}

// Absensi per pegawai
$absByPeg = [];
foreach ($absensiRows as $ab) {
    $pid = $ab['pegawai_id'];
    if (!isset($absByPeg[$pid])) $absByPeg[$pid] = ['nama' => $ab['nama'], 'jabatan' => $ab['jabatan'] ?? '', 'days' => []];
    $absByPeg[$pid]['days'][$ab['tanggal']] = ['masuk' => $ab['jam_masuk'], 'keluar' => $ab['jam_keluar']];
}

// IKM (q1–q16, skala 1–4)
$qLabels = [
    'q1'  => 'Informasi layanan tersedia (elektronik/non-elektronik)',
    'q2'  => 'Persyaratan pelayanan mudah dipenuhi',
    'q3'  => 'Prosedur/alur pelayanan mudah diikuti',
    'q4'  => 'Jangka waktu penyelesaian sesuai',
    'q5'  => 'Biaya pelayanan sesuai yang ditetapkan',
    'q6'  => 'Produk pelayanan sesuai yang dijanjikan',
    'q7'  => 'Sarana & prasarana memberikan kenyamanan',
    'q8'  => 'Data BPS mudah diakses',
    'q9'  => 'Petugas/aplikasi merespons dengan baik',
    'q10' => 'Petugas/aplikasi memberi informasi yang jelas',
    'q11' => 'Fasilitas pengaduan PST mudah diakses',
    'q12' => 'Tidak ada diskriminasi pelayanan',
    'q13' => 'Tidak ada pelayanan di luar prosedur',
    'q14' => 'Tidak ada penerimaan gratifikasi',
    'q15' => 'Tidak ada pungutan liar',
    'q16' => 'Tidak ada praktik percaloan',
];
$qScores = [];
foreach (array_keys($qLabels) as $q) {
    $vals = array_filter(array_column($penilaianRows, $q), fn($v) => $v !== null && $v !== '');
    $qScores[$q] = count($vals) ? round(array_sum($vals) / count($vals), 3) : null;
}
$validScores = array_filter($qScores, fn($v) => $v !== null);
$ikmNRR   = count($validScores) ? array_sum($validScores) / count($validScores) : null;
$ikmScore = $ikmNRR !== null ? round($ikmNRR * 25, 2) : null;

// Distribusi PST
function dist(array $rows, string $col): array {
    $d = [];
    foreach ($rows as $r) { $v = $r[$col] ?? ''; if ($v !== '') $d[$v] = ($d[$v] ?? 0) + 1; }
    arsort($d); return $d;
}
$jenisDist     = dist($pstRows, 'jenis');
$pendDist      = dist($pstRows, 'pendidikan');
$umurDist      = dist($pstRows, 'kelompok_umur');
$pekerjaanDist = dist($pstRows, 'pekerjaan');
$jkDist        = dist($pstRows, 'jk');

// Data yang dibutuhkan (PST)
// Sumber: JSON data_dibutuhkan (key "data"), fallback ke data_yang_diperlukan (teks bebas)
$rawNeeds = [];
foreach ($pstRows as $r) {
    $items = [];
    if (!empty($r['data_dibutuhkan'])) {
        $parsed = json_decode($r['data_dibutuhkan'], true);
        if (is_array($parsed)) {
            foreach ($parsed as $it) {
                $nm = is_array($it) ? ($it['data'] ?? ($it['nama'] ?? '')) : (string)$it;
                $nm = trim($nm);
                if ($nm !== '') $items[] = $nm;
            }
        }
    }
    if (empty($items) && !empty($r['data_yang_diperlukan'])) {
        $nm = trim($r['data_yang_diperlukan']);
        if ($nm !== '') $items[] = $nm;
    }
    foreach ($items as $nm) {
        $key = mb_strtolower(preg_replace('/\s+/', ' ', $nm));
        if (!isset($rawNeeds[$key])) $rawNeeds[$key] = ['label' => $nm, 'count' => 0, 'len' => mb_strlen($key)];
        $rawNeeds[$key]['count']++;
    }
}
// Gabung entri yang mirip (similarity >= 70%, hanya teks pendek ≤ 120 karakter)
$nkeys = array_keys($rawNeeds);
$elim  = [];
foreach ($nkeys as $i => $k1) {
    if (isset($elim[$k1]) || $rawNeeds[$k1]['len'] > 120) continue;
    foreach ($nkeys as $j => $k2) {
        if ($j <= $i || isset($elim[$k2]) || $rawNeeds[$k2]['len'] > 120) continue;
        similar_text($k1, $k2, $sim);
        if ($sim >= 70) {
            if ($rawNeeds[$k2]['count'] > $rawNeeds[$k1]['count']) {
                $rawNeeds[$k2]['count'] += $rawNeeds[$k1]['count'];
                $elim[$k1] = true; break;
            } else {
                $rawNeeds[$k1]['count'] += $rawNeeds[$k2]['count'];
                $elim[$k2] = true;
            }
        }
    }
}
$dataNeeds = [];
foreach ($rawNeeds as $key => $d) { if (!isset($elim[$key])) $dataNeeds[$key] = $d; }
uasort($dataNeeds, fn($a, $b) => $b['count'] <=> $a['count']);

// Distribusi PES
$katInstDist  = [];
foreach ($pesRows as $p) {
    $k = $p['kategori_instansi'] ?: 'Belum diisi';
    if ($k === 'Lainnya' && !empty($p['kategori_instansi_lainnya'])) $k = $p['kategori_instansi_lainnya'];
    $katInstDist[$k] = ($katInstDist[$k] ?? 0) + 1;
}
arsort($katInstDist);

$jenisLayDist = [];
foreach ($pesRows as $p) {
    foreach (json_decode($p['jenis_layanan'] ?? '[]', true) ?: [] as $j)
        $jenisLayDist[$j] = ($jenisLayDist[$j] ?? 0) + 1;
}
arsort($jenisLayDist);

$saranaDist = [];
foreach ($pesRows as $p) {
    foreach (json_decode($p['sarana'] ?? '[]', true) ?: [] as $s)
        $saranaDist[$s] = ($saranaDist[$s] ?? 0) + 1;
}
arsort($saranaDist);

$sentimenDist = ['positif' => 0, 'normal' => 0, 'negatif' => 0, '' => 0];
foreach ($pesRows as $p) { $sentimenDist[$p['sentimen_kritik_saran'] ?? ''] = ($sentimenDist[$p['sentimen_kritik_saran'] ?? ''] ?? 0) + 1; }

// ── Labels ────────────────────────────────────────────────────────────────────
$bulanIndo   = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$totalPST    = count($pstRows);
$totalNonPST = count($nonPstRows);
$totalPiket  = count($absByPeg);
$totalSurvei = count($penilaianRows);
$totalPES    = count($pesRows);

$monDt = new DateTime($monday);
$friDt = new DateTime($friday);
$periodLabel = "Pekan {$selWeek} — {$bulanIndo[$selMonth]} {$selYear} (" .
    $monDt->format('d M') . ' – ' . $friDt->format('d M Y') . ')';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Mingguan PST · BPS Buleleng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  .stat-card { transition: box-shadow .15s; }
  .stat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.1); }
  .progress-bar { height: 6px; border-radius: 3px; background: #e5e7eb; }
  .progress-fill { height: 100%; border-radius: 3px; }
  /* Cegah pemotongan per section saat cetak/PDF */
  main > div   { break-inside: avoid; page-break-inside: avoid; }
  thead        { display: table-header-group; }
  tr           { break-inside: avoid; page-break-inside: avoid; }
  @media print {
    *           { print-color-adjust: exact !important; -webkit-print-color-adjust: exact !important; }
    .no-print   { display: none !important; }
    body        { background: #f9fafb; }
    header.sticky { position: static !important; }
    .print-break  { page-break-before: always; }
    @page         { margin: 14mm 12mm; }
  }
</style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

<!-- ── SIDEBAR + MOBILE TOPBAR ───────────────────────────────────────────── -->

<!-- Mobile top bar -->
<div class="lg:hidden fixed top-0 inset-x-0 z-40 bg-white border-b border-gray-200 flex items-center gap-2 px-3 h-11 no-print">
  <button onclick="toggleSidebar()" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-600">
    <i class="fas fa-bars"></i>
  </button>
  <span class="text-sm font-semibold text-gray-700 truncate">Laporan Mingguan</span>
</div>

<!-- Overlay -->
<div id="sidebarOverlay" onclick="closeSidebar()"
     class="hidden fixed inset-0 bg-black/30 z-40 no-print"></div>

<!-- Sidebar -->
<aside id="sidebar"
     class="fixed top-0 left-0 h-full w-56 bg-white border-r border-gray-200 z-50
            flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-200 no-print overflow-y-auto">

  <!-- Judul -->
  <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between shrink-0">
    <div>
      <a href="<?= APP_BASE ?>/" class="text-xs text-gray-400 hover:text-blue-600 flex items-center gap-1">
        <i class="fas fa-home text-xs"></i> Menu Utama
      </a>
      <div class="text-sm font-semibold text-gray-700 mt-0.5">Laporan Mingguan</div>
    </div>
    <button onclick="closeSidebar()" class="lg:hidden p-1 rounded hover:bg-gray-100 text-gray-400">
      <i class="fas fa-times text-xs"></i>
    </button>
  </div>

  <!-- Navigasi Periode -->
  <div class="px-3 py-3 border-b border-gray-100 space-y-2">
    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide">Periode</div>
    <div class="flex gap-1">
      <a href="<?= htmlspecialchars($prevUrl) ?>"
         class="flex-1 text-center text-xs py-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 transition">
        <i class="fas fa-chevron-left"></i> Sblm
      </a>
      <a href="<?= htmlspecialchars($nextUrl) ?>"
         class="flex-1 text-center text-xs py-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 transition">
        Brktn <i class="fas fa-chevron-right"></i>
      </a>
    </div>
    <form method="GET" id="selectorForm" class="space-y-1.5">
      <input type="hidden" name="filter_jenis" value="<?= $filterJenis !== 'all' ? htmlspecialchars($filterJenis) : '' ?>">
      <select name="bulan" onchange="updatePekanOptions(); this.form.submit()"
              class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
        <?php foreach ($bulanIndo as $i => $nm): if (!$i) continue; ?>
        <option value="<?= $i ?>" <?= $i === $selMonth ? 'selected' : '' ?>><?= $nm ?></option>
        <?php endforeach; ?>
      </select>
      <select name="tahun" onchange="this.form.submit()"
              class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
        <option value="<?= $y ?>" <?= $y === $selYear ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <select name="pekan" id="pekanSelect"
              class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
        <?php foreach ($weeksInMonth as $w): ?>
        <option value="<?= $w['num'] ?>" <?= $w['num'] === $selWeek ? 'selected' : '' ?>>
          P<?= $w['num'] ?> (<?= (new DateTime($w['monday']))->format('d') ?>–<?= (new DateTime($w['friday']))->format('d') ?>)
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="w-full text-sm py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-1">
        <i class="fas fa-search text-xs"></i> Tampilkan
      </button>
    </form>
  </div>

  <!-- Filter Jenis -->
  <div class="px-3 py-3 border-b border-gray-100 space-y-1.5">
    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide">Filter Jenis</div>
    <?php
    $baseUrl     = APP_BASE . "/laporan/minggu?tahun=$selYear&bulan=$selMonth&pekan=$selWeek";
    $filterOpts  = ['all' => 'Semua', 'whatsapp' => 'WhatsApp', 'surat' => 'Via Surat', 'langsung' => 'Kunjungan Langsung', 'umum' => 'Umum', 'disabilitas' => 'Disabilitas'];
    $filterActiv = ['all' => 'bg-blue-600 text-white', 'whatsapp' => 'bg-green-600 text-white', 'surat' => 'bg-amber-600 text-white', 'langsung' => 'bg-indigo-600 text-white', 'umum' => 'bg-blue-500 text-white', 'disabilitas' => 'bg-purple-600 text-white'];
    ?>
    <?php foreach ($filterOpts as $key => $label):
      $isActive = $filterJenis === $key;
      $href = $key === 'all' ? $baseUrl : "$baseUrl&filter_jenis=" . urlencode($key);
      $isSub = in_array($key, ['umum', 'disabilitas']);
    ?>
    <?php if ($isSub): ?>
    <a href="<?= htmlspecialchars($href) ?>"
       class="flex items-center gap-1 ml-3 text-xs px-2 py-1 rounded-lg font-medium transition <?= $isActive ? $filterActiv[$key] : 'border border-gray-200 text-gray-500 hover:bg-gray-50' ?>">
      <span class="text-gray-300 leading-none">└</span><?= $label ?>
    </a>
    <?php else: ?>
    <a href="<?= htmlspecialchars($href) ?>"
       class="block text-xs px-3 py-1.5 rounded-lg font-medium transition <?= $isActive ? $filterActiv[$key] : 'border border-gray-200 text-gray-500 hover:bg-gray-50' ?>">
      <?= $label ?>
    </a>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- Aksi -->
  <div class="px-3 py-3 space-y-1.5">
    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide">Aksi</div>
    <a href="<?= APP_BASE ?>/laporan/bulan?bulan=<?= $selMonth ?>&tahun=<?= $selYear ?>"
       class="flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg border border-violet-200 text-violet-600 hover:bg-violet-50 transition">
      <i class="fas fa-calendar-alt text-xs w-4 text-center"></i> Per Bulan
    </a>
    <button onclick="window.print()"
            class="w-full flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 transition text-gray-600">
      <i class="fas fa-print text-xs w-4 text-center"></i> Cetak
    </button>
    <button id="btnPDF" onclick="downloadPDF()"
            class="w-full flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg bg-red-600 text-white hover:bg-red-700 active:bg-red-800 transition font-medium">
      <i class="fas fa-file-pdf text-xs w-4 text-center"></i> Unduh PDF
    </button>
  </div>
</aside>

<!-- ── MAIN WRAPPER ───────────────────────────────────────────────────────── -->
<div class="lg:pl-56">
<main id="reportContent" class="max-w-7xl mx-auto px-4 pt-14 lg:pt-6 pb-6 space-y-6">

  <!-- Judul periode -->
  <div class="flex items-start gap-4">
    <div>
      <h1 class="text-xl font-bold text-gray-800">Laporan Mingguan Pelayanan PST</h1>
      <p class="text-sm text-gray-500 mt-0.5">
        <i class="fas fa-calendar-week text-blue-500 mr-1.5"></i><?= htmlspecialchars($periodLabel) ?>
      </p>
    </div>
    <div class="ml-auto text-right hidden sm:block">
      <p class="text-xs text-gray-400">BPS Kabupaten Buleleng</p>
      <p class="text-xs text-gray-400">Dicetak <?= (new DateTime())->format('d M Y H:i') ?></p>
    </div>
  </div>

  <!-- ── KARTU RINGKASAN ──────────────────────────────────────────────────── -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
    <?php
    $cards = [
      ['label' => 'Pengunjung PST',     'val' => $totalPST,    'icon' => 'fa-building',        'color' => 'blue'],
      ['label' => 'Pengunjung Non-PST', 'val' => $totalNonPST, 'icon' => 'fa-users',           'color' => 'slate'],
      ['label' => 'Petugas Piket',      'val' => $totalPiket,  'icon' => 'fa-user-tie',        'color' => 'teal'],
      ['label' => 'Survei Kepuasan',    'val' => $totalSurvei, 'icon' => 'fa-star',            'color' => 'yellow'],
      ['label' => 'PES Terisi',         'val' => $totalPES,    'icon' => 'fa-clipboard-check', 'color' => 'purple'],
    ];
    $colorMap = [
      'blue'   => ['bg-blue-50',   'text-blue-600',   'text-blue-700'],
      'slate'  => ['bg-slate-50',  'text-slate-500',  'text-slate-700'],
      'teal'   => ['bg-teal-50',   'text-teal-600',   'text-teal-700'],
      'yellow' => ['bg-yellow-50', 'text-yellow-600', 'text-yellow-700'],
      'purple' => ['bg-purple-50', 'text-purple-600', 'text-purple-700'],
    ];
    foreach ($cards as $c):
      [$bg, $ic, $tx] = $colorMap[$c['color']];
    ?>
    <div class="stat-card <?= $bg ?> rounded-2xl p-4 border border-white shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-medium <?= $tx ?> opacity-70"><?= $c['label'] ?></span>
        <i class="fas <?= $c['icon'] ?> <?= $ic ?> text-sm opacity-60"></i>
      </div>
      <div class="text-3xl font-bold <?= $tx ?>"><?= $c['val'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── KUNJUNGAN HARIAN ─────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
      <i class="fas fa-chart-bar text-blue-500"></i>
      <h2 class="font-semibold text-gray-700">Kunjungan Harian</h2>
      <span class="ml-auto text-xs text-gray-400">Total: <?= $totalPST + $totalNonPST ?> pengunjung</span>
    </div>
    <div class="p-5">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart -->
        <div class="relative h-48">
          <canvas id="chartVisit"></canvas>
        </div>
        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-center">
            <thead>
              <tr class="bg-gray-50">
                <th class="px-3 py-2 text-left font-semibold text-gray-600">Hari</th>
                <th class="px-3 py-2 font-semibold text-blue-600">PST</th>
                <th class="px-3 py-2 font-semibold text-slate-500">Non-PST</th>
                <th class="px-3 py-2 font-semibold text-gray-700">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($dayKeys as $i => $d):
                $v = $visitByDay[$d];
                $tot = $v['pst'] + $v['non'];
              ?>
              <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 text-left text-gray-600">
                  <span class="font-medium"><?= $dayNamesFull[$i] ?></span>
                  <span class="text-xs text-gray-400 ml-1"><?= (new DateTime($d))->format('d/m') ?></span>
                </td>
                <td class="px-3 py-2 font-semibold text-blue-700"><?= $v['pst'] ?: '—' ?></td>
                <td class="px-3 py-2 text-slate-500"><?= $v['non'] ?: '—' ?></td>
                <td class="px-3 py-2 font-bold text-gray-800"><?= $tot ?: '—' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="bg-blue-50 font-bold">
                <td class="px-3 py-2 text-left text-blue-700">Total Pekan</td>
                <td class="px-3 py-2 text-blue-700"><?= $totalPST ?></td>
                <td class="px-3 py-2 text-slate-600"><?= $totalNonPST ?></td>
                <td class="px-3 py-2 text-gray-800"><?= $totalPST + $totalNonPST ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── KEHADIRAN PIKET ──────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
      <i class="fas fa-user-check text-teal-500"></i>
      <h2 class="font-semibold text-gray-700">Kehadiran Petugas Piket</h2>
      <span class="ml-auto text-xs text-gray-400"><?= $totalPiket ?> petugas hadir pekan ini</span>
    </div>
    <?php if (empty($absByPeg)): ?>
    <div class="px-5 py-8 text-center text-gray-400 text-sm">
      <i class="fas fa-calendar-xmark text-2xl mb-2 block"></i>
      Belum ada data absensi pekan ini.
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
            <th class="px-4 py-3 text-left font-semibold">Nama Petugas</th>
            <?php foreach ($dayKeys as $i => $d): ?>
            <th class="px-3 py-3 font-semibold text-center">
              <?= $dayNamesShort[$i] ?><br>
              <span class="font-normal normal-case"><?= (new DateTime($d))->format('d/m') ?></span>
            </th>
            <?php endforeach; ?>
            <th class="px-3 py-3 text-center font-semibold">Hari Hadir</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($absByPeg as $pg):
            $hadir = count($pg['days']);
          ?>
          <tr class="hover:bg-teal-50/30">
            <td class="px-4 py-2.5">
              <p class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($pg['nama']) ?></p>
              <?php if ($pg['jabatan']): ?>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($pg['jabatan']) ?></p>
              <?php endif; ?>
            </td>
            <?php foreach ($dayKeys as $d):
              $ab = $pg['days'][$d] ?? null;
            ?>
            <td class="px-3 py-2.5 text-center">
              <?php if ($ab): ?>
                <div class="inline-flex flex-col items-center">
                  <span class="w-2 h-2 rounded-full bg-teal-500 mb-1"></span>
                  <span class="text-xs text-teal-700 font-medium">
                    <?= $ab['masuk'] ? (new DateTime($ab['masuk']))->format('H:i') : '—' ?>
                  </span>
                  <?php if ($ab['keluar']): ?>
                  <span class="text-xs text-gray-400">
                    <?= (new DateTime($ab['keluar']))->format('H:i') ?>
                  </span>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <span class="text-gray-300">—</span>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
            <td class="px-3 py-2.5 text-center">
              <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold
                <?= $hadir >= 4 ? 'bg-teal-100 text-teal-700' : ($hadir >= 2 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500') ?>">
                <?= $hadir ?>/5
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── GRID: PST DETAIL & DATA KEBUTUHAN ───────────────────────────────── -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Profil Pengunjung PST -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
        <i class="fas fa-users text-blue-500"></i>
        <h2 class="font-semibold text-gray-700">Profil Pengunjung PST</h2>
        <span class="ml-auto text-xs text-gray-400"><?= $totalPST ?> pengunjung</span>
      </div>
      <?php if (!$totalPST): ?>
      <div class="px-5 py-8 text-center text-gray-400 text-sm">Belum ada pengunjung PST pekan ini.</div>
      <?php else: ?>
      <div class="p-5 space-y-5">

        <!-- Jenis -->
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Jenis Antrian</p>
          <div class="flex flex-wrap gap-2">
            <?php
            $jenisColor = ['umum' => 'bg-blue-100 text-blue-700', 'disabilitas' => 'bg-purple-100 text-purple-700', 'whatsapp' => 'bg-green-100 text-green-700'];
            $jenisLabel = ['umum' => 'Umum', 'disabilitas' => 'Disabilitas', 'whatsapp' => 'WhatsApp'];
            foreach ($jenisDist as $j => $cnt):
            ?>
            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $jenisColor[$j] ?? 'bg-gray-100 text-gray-600' ?>">
              <?= $jenisLabel[$j] ?? $j ?>: <?= $cnt ?>
            </span>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Jenis Kelamin -->
        <?php if ($jkDist): ?>
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Jenis Kelamin</p>
          <div class="flex gap-4">
            <?php foreach (['L' => ['fas fa-mars', 'text-blue-500'], 'P' => ['fas fa-venus', 'text-pink-500']] as $jk => $cfg):
              $cnt = $jkDist[$jk] ?? 0;
              if (!$cnt) continue;
            ?>
            <div class="flex items-center gap-2">
              <i class="<?= $cfg[0] ?> <?= $cfg[1] ?>"></i>
              <span class="text-sm font-semibold text-gray-700"><?= $cnt ?></span>
              <span class="text-xs text-gray-400"><?= $jk === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Pendidikan -->
        <?php if ($pendDist): ?>
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Pendidikan</p>
          <?php foreach ($pendDist as $p => $cnt): $pct = round($cnt / $totalPST * 100); ?>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="text-xs text-gray-600 w-24 shrink-0 truncate"><?= htmlspecialchars($p) ?></span>
            <div class="progress-bar flex-1">
              <div class="progress-fill bg-blue-400" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="text-xs font-semibold text-gray-700 w-6 text-right"><?= $cnt ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Kelompok Umur -->
        <?php if ($umurDist): ?>
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Kelompok Umur</p>
          <div class="flex flex-wrap gap-1.5">
            <?php foreach ($umurDist as $u => $cnt): ?>
            <span class="px-2 py-1 text-xs rounded-lg bg-indigo-50 text-indigo-700 font-medium">
              <?= htmlspecialchars($u) ?>: <b><?= $cnt ?></b>
            </span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Pekerjaan -->
        <?php if ($pekerjaanDist): ?>
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Pekerjaan</p>
          <?php foreach ($pekerjaanDist as $pj => $cnt): $pct = round($cnt / $totalPST * 100); ?>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="text-xs text-gray-600 w-28 shrink-0 truncate"><?= htmlspecialchars($pj) ?></span>
            <div class="progress-bar flex-1">
              <div class="progress-fill bg-teal-400" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="text-xs font-semibold text-gray-700 w-6 text-right"><?= $cnt ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
      <?php endif; ?>
    </div>

    <!-- Data yang Dibutuhkan -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
        <i class="fas fa-database text-indigo-500"></i>
        <h2 class="font-semibold text-gray-700">Data yang Dibutuhkan</h2>
        <span class="ml-auto text-xs text-gray-400"><?= count($dataNeeds) ?> jenis data</span>
      </div>
      <?php if (empty($dataNeeds)): ?>
      <div class="px-5 py-8 text-center text-gray-400 text-sm">Belum ada data yang tercatat pekan ini.</div>
      <?php else: ?>
      <div class="p-5">
        <ul class="space-y-2">
          <?php $maxCnt = max(array_column($dataNeeds, 'count')); foreach ($dataNeeds as $item):
            $pct = round($item['count'] / $maxCnt * 100);
          ?>
          <li class="flex items-start gap-3">
            <span class="mt-0.5 w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0"><?= $item['count'] ?></span>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-700 leading-tight"><?= htmlspecialchars($item['label']) ?></p>
              <div class="progress-bar mt-1">
                <div class="progress-fill bg-indigo-300" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- ── SURVEI KEPUASAN IKM ──────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
      <i class="fas fa-star text-yellow-500"></i>
      <h2 class="font-semibold text-gray-700">Survei Kepuasan (IKM)</h2>
      <span class="ml-auto text-xs text-gray-400"><?= $totalSurvei ?> responden</span>
    </div>
    <?php if (!$totalSurvei): ?>
    <div class="px-5 py-8 text-center text-gray-400 text-sm">Belum ada survei pekan ini.</div>
    <?php else: ?>
    <div class="p-5">
      <!-- Skor IKM keseluruhan -->
      <?php if ($ikmScore !== null):
        [$grade, $kategori, $txc, $bgc] = ikmGrade($ikmNRR);
      ?>
      <div class="flex items-center gap-4 mb-6 p-4 rounded-xl border <?= $bgc ?> <?= $txc ?>">
        <div class="text-center">
          <div class="text-4xl font-black"><?= number_format($ikmScore, 2) ?></div>
          <div class="text-xs font-semibold mt-0.5">Nilai IKM</div>
        </div>
        <div class="w-px h-12 bg-current opacity-20"></div>
        <div>
          <span class="text-2xl font-black"><?= $grade ?></span>
          <p class="text-sm font-semibold"><?= $kategori ?></p>
          <p class="text-xs opacity-70">NRR rata-rata: <?= number_format($ikmNRR, 3) ?> dari skala 4.000</p>
        </div>
      </div>
      <?php endif; ?>

      <!-- Detail per pertanyaan -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-xs text-gray-500">
              <th class="px-3 py-2 text-left">No</th>
              <th class="px-3 py-2 text-left">Unsur Pelayanan</th>
              <th class="px-3 py-2 text-center w-24">Rata-rata</th>
              <th class="px-3 py-2 text-center w-20">NRR×25</th>
              <th class="px-3 py-2 text-left w-40">Grafik</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($qLabels as $q => $lbl):
              $score = $qScores[$q];
              if ($score === null) continue;
              $ikm25 = round($score * 25, 2);
              $pct   = round($score / 4 * 100);
              [$g,,$tx,] = ikmGrade($score);
            ?>
            <tr class="hover:bg-gray-50">
              <td class="px-3 py-2 text-gray-400 text-xs"><?= substr($q, 1) ?></td>
              <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars($lbl) ?></td>
              <td class="px-3 py-2 text-center font-semibold <?= $tx ?>"><?= number_format($score, 2) ?></td>
              <td class="px-3 py-2 text-center text-xs font-medium text-gray-600"><?= number_format($ikm25, 2) ?></td>
              <td class="px-3 py-2">
                <div class="progress-bar">
                  <div class="progress-fill <?= str_replace('text-', 'bg-', $tx) ?>/50" style="width:<?= $pct ?>%"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Catatan pengunjung -->
      <?php
      $catatan = array_filter(array_column($penilaianRows, 'catatan'), fn($c) => trim($c ?? '') !== '');
      if ($catatan):
      ?>
      <div class="mt-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Catatan / Saran Pengunjung</p>
        <div class="space-y-2">
          <?php foreach ($catatan as $cat): ?>
          <div class="flex gap-2 bg-yellow-50 border border-yellow-100 rounded-lg px-3 py-2">
            <i class="fas fa-quote-left text-yellow-400 text-xs mt-0.5 flex-shrink-0"></i>
            <p class="text-sm text-gray-700"><?= htmlspecialchars($cat) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── PES ──────────────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
      <i class="fas fa-clipboard-list text-teal-500"></i>
      <h2 class="font-semibold text-gray-700">Post Enumeration Survey (PES)</h2>
      <span class="ml-auto text-xs text-gray-400"><?= $totalPES ?> entri PES</span>
    </div>
    <?php if (!$totalPES): ?>
    <div class="px-5 py-8 text-center text-gray-400 text-sm">Belum ada PES pekan ini.</div>
    <?php else: ?>
    <div class="p-5">
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

        <!-- Kategori Instansi -->
        <?php if ($katInstDist): ?>
        <div class="md:col-span-1">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Kategori Instansi</p>
          <div class="space-y-2">
            <?php $max = max($katInstDist); foreach ($katInstDist as $k => $cnt):
              $pct = round($cnt / $max * 100);
            ?>
            <div>
              <div class="flex justify-between text-xs mb-0.5">
                <span class="text-gray-600 truncate max-w-[80%]"><?= htmlspecialchars($k) ?></span>
                <span class="font-semibold text-gray-700"><?= $cnt ?></span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill bg-teal-400" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Jenis Layanan -->
        <?php if ($jenisLayDist): ?>
        <div class="md:col-span-1">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Jenis Layanan</p>
          <div class="space-y-2">
            <?php $max = max($jenisLayDist); foreach ($jenisLayDist as $j => $cnt):
              $pct = round($cnt / $max * 100);
            ?>
            <div>
              <div class="flex justify-between text-xs mb-0.5">
                <span class="text-gray-600 truncate max-w-[80%]"><?= htmlspecialchars($j) ?></span>
                <span class="font-semibold text-gray-700"><?= $cnt ?></span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill bg-blue-400" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Sarana -->
        <?php if ($saranaDist): ?>
        <div class="md:col-span-1">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Sarana yang Digunakan</p>
          <div class="space-y-2">
            <?php $max = max($saranaDist); foreach ($saranaDist as $s => $cnt):
              $pct = round($cnt / $max * 100);
            ?>
            <div>
              <div class="flex justify-between text-xs mb-0.5">
                <span class="text-gray-600 truncate max-w-[80%]"><?= htmlspecialchars($s) ?></span>
                <span class="font-semibold text-gray-700"><?= $cnt ?></span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill bg-purple-400" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Sentimen -->
        <div class="md:col-span-1">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Sentimen Kritik &amp; Saran</p>
          <div class="space-y-3">
            <?php
            $sentiConfig = [
              'positif' => ['bg-green-100 text-green-700', 'fa-face-smile'],
              'normal'  => ['bg-blue-100 text-blue-700',   'fa-face-meh'],
              'negatif' => ['bg-red-100 text-red-700',     'fa-face-frown'],
            ];
            $sentiLabel = ['positif' => 'Positif', 'normal' => 'Netral', 'negatif' => 'Negatif'];
            foreach (['positif','normal','negatif'] as $s):
              $cnt = $sentimenDist[$s] ?? 0;
              if (!$cnt) continue;
              [$cls, $ico] = $sentiConfig[$s];
            ?>
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?= $cls ?>">
              <i class="fas <?= $ico ?> text-lg"></i>
              <div>
                <p class="font-semibold text-sm"><?= $sentiLabel[$s] ?></p>
              </div>
              <span class="ml-auto text-2xl font-black"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (($sentimenDist[''] ?? 0) > 0): ?>
            <p class="text-xs text-gray-400"><?= $sentimenDist[''] ?> belum diisi sentimen.</p>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Daftar PES -->
      <div class="mt-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Daftar Entri PES</p>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-xs text-gray-500">
                <th class="px-3 py-2 text-left">Tanggal</th>
                <th class="px-3 py-2 text-left">Pengunjung</th>
                <th class="px-3 py-2 text-left">Instansi</th>
                <th class="px-3 py-2 text-left">Kategori</th>
                <th class="px-3 py-2 text-left">Sentimen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($pesRows as $p):
                $tgl = (new DateTime($p['tgl_kunjungan']))->format('d M');
                $kat = $p['kategori_instansi'] ?: '—';
                if ($kat === 'Lainnya' && !empty($p['kategori_instansi_lainnya'])) $kat = $p['kategori_instansi_lainnya'];
                $st  = $p['sentimen_kritik_saran'] ?? '';
                $stCls = $st === 'positif' ? 'text-green-600' : ($st === 'negatif' ? 'text-red-500' : 'text-gray-400');
                $stIco = $st === 'positif' ? 'fa-face-smile' : ($st === 'negatif' ? 'fa-face-frown' : 'fa-face-meh');
              ?>
              <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 text-gray-500 text-xs"><?= $tgl ?></td>
                <td class="px-3 py-2 font-medium text-gray-700"><?= htmlspecialchars($p['nama_pengunjung'] ?? '—') ?></td>
                <td class="px-3 py-2 text-gray-500 text-xs"><?= htmlspecialchars($p['instansi'] ?? '—') ?></td>
                <td class="px-3 py-2 text-xs text-gray-600"><?= htmlspecialchars($kat) ?></td>
                <td class="px-3 py-2">
                  <?php if ($st): ?>
                  <i class="fas <?= $stIco ?> <?= $stCls ?>"></i>
                  <span class="text-xs <?= $stCls ?> ml-1"><?= ucfirst($st) ?></span>
                  <?php else: ?>
                  <span class="text-gray-300 text-xs">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── PENGUNJUNG NON-PST ───────────────────────────────────────────────── -->
  <?php if ($nonPstRows): ?>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
      <i class="fas fa-book-open text-slate-500"></i>
      <h2 class="font-semibold text-gray-700">Pengunjung Non-PST</h2>
      <span class="ml-auto text-xs text-gray-400"><?= $totalNonPST ?> pengunjung</span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-xs text-gray-500">
            <th class="px-4 py-2 text-left">Tanggal</th>
            <th class="px-4 py-2 text-left">Nama</th>
            <th class="px-4 py-2 text-left">Instansi</th>
            <th class="px-4 py-2 text-left">Keperluan</th>
            <th class="px-4 py-2 text-center">Jml Orang</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($nonPstRows as $r): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-2 text-gray-400 text-xs"><?= (new DateTime($r['tanggal']))->format('d M') ?></td>
            <td class="px-4 py-2 font-medium text-gray-700"><?= htmlspecialchars($r['nama']) ?></td>
            <td class="px-4 py-2 text-gray-500 text-xs"><?= htmlspecialchars($r['instansi'] ?? '—') ?></td>
            <td class="px-4 py-2 text-gray-600 text-xs max-w-xs truncate"><?= htmlspecialchars($r['keperluan'] ?? '—') ?></td>
            <td class="px-4 py-2 text-center text-gray-600"><?= max(1, (int)($r['jumlah_orang'] ?? 1)) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</main>

<!-- ── FOOTER ─────────────────────────────────────────────────────────────── -->
<footer class="text-center text-gray-400 text-xs py-8">
  BPS Kabupaten Buleleng · Sistem Antrean v2 · Laporan Mingguan
</footer>
</div><!-- end .lg:pl-56 -->

<!-- ── CHART.JS ───────────────────────────────────────────────────────────── -->
<script>
(function() {
  const labels = <?= json_encode($dayNamesShort) ?>;
  const pst    = <?= json_encode(array_map(fn($d) => $visitByDay[$d]['pst'], $dayKeys)) ?>;
  const nonPst = <?= json_encode(array_map(fn($d) => $visitByDay[$d]['non'], $dayKeys)) ?>;

  new Chart(document.getElementById('chartVisit'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'PST',     data: pst,    backgroundColor: '#3b82f680', borderColor: '#3b82f6', borderWidth: 1.5, borderRadius: 4 },
        { label: 'Non-PST', data: nonPst, backgroundColor: '#94a3b840', borderColor: '#94a3b8', borderWidth: 1.5, borderRadius: 4 },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } },
        x: { ticks: { font: { size: 11 } }, grid: { display: false } },
      }
    }
  });
})();
</script>

<!-- ── PDF EXPORT ─────────────────────────────────────────────────────────── -->
<script>
async function downloadPDF() {
  const btn = document.getElementById('btnPDF');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Memproses…';

  // Scroll ke atas agar getBoundingClientRect konsisten
  const origScrollY = window.scrollY;
  window.scrollTo(0, 0);
  await new Promise(r => setTimeout(r, 80));

  // Konversi canvas Chart.js → <img> agar ter-render dalam PDF
  const cv  = document.getElementById('chartVisit');
  let imgEl = null;
  if (cv) {
    imgEl = document.createElement('img');
    imgEl.src          = cv.toDataURL('image/png');
    imgEl.style.width  = cv.offsetWidth  + 'px';
    imgEl.style.height = cv.offsetHeight + 'px';
    imgEl.style.display = 'block';
    cv.parentNode.replaceChild(imgEl, cv);
  }

  // Sembunyikan elemen no-print sementara (termasuk sticky header)
  document.querySelectorAll('.no-print').forEach(el => el.style.display = 'none');

  try {
    await html2pdf()
      .set({
        margin:      [14, 10, 14, 10],
        filename:    'laporan-mingguan-<?= $selYear ?>-<?= sprintf('%02d',$selMonth) ?>-pekan<?= $selWeek ?>.pdf',
        image:       { type: 'jpeg', quality: 0.97 },
        html2canvas: {
          scale:     2,
          useCORS:   true,
          logging:   false,
          scrollX:   0,
          scrollY:   0,
        },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:   { mode: ['avoid-all', 'css', 'legacy'] },
      })
      .from(document.getElementById('reportContent'))
      .save();
  } catch (e) {
    alert('Gagal membuat PDF. Coba gunakan tombol Cetak lalu simpan sebagai PDF.');
  }

  // Pulihkan canvas, elemen tersembunyi, dan posisi scroll
  if (cv && imgEl) imgEl.parentNode.replaceChild(cv, imgEl);
  document.querySelectorAll('.no-print').forEach(el => el.style.display = '');
  window.scrollTo(0, origScrollY);

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-file-pdf text-xs"></i> Unduh PDF';
}

document.addEventListener('DOMContentLoaded', function() {
  if (window.innerWidth < 1024 && localStorage.getItem('sidebarOpen') === '1') {
    document.getElementById('sidebar').classList.remove('-translate-x-full');
    document.getElementById('sidebarOverlay').classList.remove('hidden');
  }
});
function toggleSidebar() {
  const s = document.getElementById('sidebar');
  const o = document.getElementById('sidebarOverlay');
  if (s.classList.contains('-translate-x-full')) {
    s.classList.remove('-translate-x-full');
    o.classList.remove('hidden');
    localStorage.setItem('sidebarOpen', '1');
  } else {
    s.classList.add('-translate-x-full');
    o.classList.add('hidden');
    localStorage.removeItem('sidebarOpen');
  }
}
function closeSidebar() {
  document.getElementById('sidebar').classList.add('-translate-x-full');
  document.getElementById('sidebarOverlay').classList.add('hidden');
  localStorage.removeItem('sidebarOpen');
}
</script>
</body>
</html>

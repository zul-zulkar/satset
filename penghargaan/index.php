<?php
include __DIR__ . '/../db.php';

// Auto-create tables
$mysqli->query("CREATE TABLE IF NOT EXISTS penghargaan_penilaian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL, bulan TINYINT UNSIGNED NOT NULL, tahun SMALLINT UNSIGNED NOT NULL,
    nilai_kinerja TINYINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pk (pegawai_id, bulan, tahun)
)");
$mysqli->query("CREATE TABLE IF NOT EXISTS penghargaan_tim_penilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL, bulan TINYINT UNSIGNED NOT NULL, tahun SMALLINT UNSIGNED NOT NULL,
    nama_penilai VARCHAR(50) NOT NULL,
    nilai_kerja_sama TINYINT UNSIGNED DEFAULT NULL,
    nilai_inovatif   TINYINT UNSIGNED DEFAULT NULL,
    nilai_penampilan TINYINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tp (pegawai_id, bulan, tahun, nama_penilai)
)");
// Migrate old evaluator names to new names (idempotent)
$mysqli->query("UPDATE penghargaan_tim_penilai SET nama_penilai='madekariasa' WHERE nama_penilai='madepratiwi'");
$mysqli->query("UPDATE penghargaan_tim_penilai SET nama_penilai='paseksusena' WHERE nama_penilai='ariwijaya'");

$TIM_PENILAI = ['iwansantika', 'madekariasa', 'paseksusena'];
$bulanIndo   = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$selYear     = max(2020, min(2030, (int)($_GET['tahun'] ?? date('Y'))));
$selMonth    = max(1, min(12, (int)($_GET['bulan'] ?? date('n'))));
$activeTab   = in_array($_GET['tab'] ?? '', ['peringkat','kinerja','tim']) ? $_GET['tab'] : 'peringkat';
$selPenilai  = in_array($_GET['penilai'] ?? '', $TIM_PENILAI) ? $_GET['penilai'] : '';
$firstDay    = sprintf('%04d-%02d-01', $selYear, $selMonth);
$lastDay     = (new DateTime($firstDay))->modify('last day of this month')->format('Y-m-d');
$pM = $selMonth - 1; $pY = $selYear; if ($pM < 1)  { $pM = 12; $pY--; }
$nM = $selMonth + 1; $nY = $selYear; if ($nM > 12) { $nM = 1;  $nY++; }

// Weeks in month (Mon–Fri ranges)
function weeksInMonth(int $y, int $m): array {
    $weeks = []; $cur = new DateTime(sprintf('%04d-%02d-01', $y, $m));
    if ((int)$cur->format('N') !== 1) $cur->modify('next Monday');
    $n = 1;
    while ((int)$cur->format('n') === $m) {
        $mon = clone $cur; $fri = (clone $cur)->modify('+4 days');
        $weeks[] = ['num' => $n++, 'mon' => $mon->format('Y-m-d'), 'fri' => $fri->format('Y-m-d')];
        $cur->modify('+7 days');
    }
    return $weeks;
}
$weeks = weeksInMonth($selYear, $selMonth);

// Holidays from jadwal.json
$jadwalCfg = @json_decode(@file_get_contents(__DIR__ . '/../absensi/config/jadwal.json'), true) ?? [];
$liburArr  = array_column($jadwalCfg['libur'] ?? [], 'tanggal');

// Full month working days (for display in header only)
$hariKerja = 0;
for ($dt = new DateTime($firstDay), $end = new DateTime($lastDay); $dt <= $end; $dt->modify('+1 day')) {
    if ((int)$dt->format('N') <= 5 && !in_array($dt->format('Y-m-d'), $liburArr)) $hariKerja++;
}

// Officers who had piket this month
$st = $mysqli->prepare("
    SELECT p.id, p.nama, p.jabatan,
           COUNT(DISTINCT ap.tanggal) AS hari_hadir,
           GROUP_CONCAT(DISTINCT ap.tanggal ORDER BY ap.tanggal) AS tgl_hadir
    FROM pegawai p JOIN absensi_piket ap ON ap.pegawai_id = p.id
    WHERE MONTH(ap.tanggal) = ? AND YEAR(ap.tanggal) = ?
    GROUP BY p.id, p.nama, p.jabatan ORDER BY p.nama
");
$st->bind_param("ii", $selMonth, $selYear); $st->execute();
$officers = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

// PST visitors per day
$st = $mysqli->prepare("SELECT tanggal FROM antrian
    WHERE (kunjungan_pst = 1 OR jenis IN ('whatsapp','surat')) AND tanggal BETWEEN ? AND ?");
$st->bind_param("ss", $firstDay, $lastDay); $st->execute();
$pstRows  = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
$totalPST = count($pstRows);

// Sentimen negatif per day
$st = $mysqli->prepare("SELECT a.tanggal FROM pes p JOIN antrian a ON a.id = p.antrian_id
    WHERE p.sentimen_kritik_saran = 'negatif' AND a.tanggal BETWEEN ? AND ?");
$st->bind_param("ss", $firstDay, $lastDay); $st->execute();
$negatifRows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

// Per-week PST and negatif counts
$pstW = []; $negW = [];
foreach ($weeks as $w) {
    $pstW[$w['num']] = count(array_filter($pstRows,     fn($r) => $r['tanggal'] >= $w['mon'] && $r['tanggal'] <= $w['fri']));
    $negW[$w['num']] = count(array_filter($negatifRows, fn($r) => $r['tanggal'] >= $w['mon'] && $r['tanggal'] <= $w['fri']));
}

// Kinerja scores from DB
$st = $mysqli->prepare("SELECT pegawai_id, nilai_kinerja FROM penghargaan_penilaian WHERE bulan=? AND tahun=?");
$st->bind_param("ii", $selMonth, $selYear); $st->execute();
$kinerjaMap = array_column($st->get_result()->fetch_all(MYSQLI_ASSOC), 'nilai_kinerja', 'pegawai_id');
$st->close();

// Tim penilai scores from DB
$st = $mysqli->prepare("SELECT pegawai_id, nama_penilai, nilai_kerja_sama, nilai_inovatif, nilai_penampilan
    FROM penghargaan_tim_penilai WHERE bulan=? AND tahun=?");
$st->bind_param("ii", $selMonth, $selYear); $st->execute();
$timMap = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $timMap[$r['pegawai_id']][$r['nama_penilai']] = [
        'ks' => $r['nilai_kerja_sama'], 'iv' => $r['nilai_inovatif'], 'pe' => $r['nilai_penampilan']
    ];
}
$st->close();

// Calculate per officer
$results = [];
foreach ($officers as $o) {
    $pid = $o['id'];

    // Piket weeks — determined from absensi dates
    $tglHadir   = $o['tgl_hadir'] ? explode(',', $o['tgl_hadir']) : [];
    $piketWeeks = [];
    foreach ($weeks as $w) {
        foreach ($tglHadir as $tgl) {
            if ($tgl >= $w['mon'] && $tgl <= $w['fri']) { $piketWeeks[] = $w['num']; break; }
        }
    }
    $piketWeeks = array_unique($piketWeeks);

    // Kehadiran — working days in piket weeks only (fair for part-month officers)
    $hariKerjaPiket = 0;
    foreach ($piketWeeks as $wn) {
        $wDef = current(array_filter($weeks, fn($x) => $x['num'] === $wn));
        if (!$wDef) continue;
        for ($dt = new DateTime($wDef['mon']), $dtEnd = new DateTime($wDef['fri']); $dt <= $dtEnd; $dt->modify('+1 day')) {
            if (!in_array($dt->format('Y-m-d'), $liburArr)) $hariKerjaPiket++;
        }
    }
    $kehadiran = $hariKerjaPiket > 0 ? min(100, round($o['hari_hadir'] / $hariKerjaPiket * 100, 2)) : 0;

    // Performa — positive visit ratio in piket weeks (fair for part-month officers)
    $pstPiket = 0; $negPiket = 0;
    foreach ($piketWeeks as $wn) { $pstPiket += $pstW[$wn] ?? 0; $negPiket += $negW[$wn] ?? 0; }
    $performa = $pstPiket > 0 ? round(($pstPiket - $negPiket) / $pstPiket * 100, 2) : 0;

    // Kinerja
    $kinerja = isset($kinerjaMap[$pid]) ? (int)$kinerjaMap[$pid] : null;

    // Tim averages
    $ts  = $timMap[$pid] ?? [];
    $avg = function(string $key) use ($ts, $TIM_PENILAI) {
        $vals = array_values(array_filter(array_map(fn($t) => $ts[$t][$key] ?? null, $TIM_PENILAI), fn($v) => $v !== null));
        return count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
    };
    $avgKS = $avg('ks'); $avgIV = $avg('iv'); $avgPE = $avg('pe');

    $final = null;
    if ($kinerja !== null && $avgKS !== null && $avgIV !== null && $avgPE !== null) {
        $final = round($kehadiran*0.20 + $kinerja*0.30 + $avgKS*0.10 + $avgIV*0.10 + $avgPE*0.10 + $performa*0.20, 2);
    }

    $results[] = [
        'id' => $pid, 'nama' => $o['nama'], 'jabatan' => $o['jabatan'],
        'hari_hadir' => (int)$o['hari_hadir'], 'hari_kerja' => $hariKerjaPiket,
        'kehadiran' => $kehadiran, 'performa' => $performa,
        'piket_weeks' => $piketWeeks,
        'kinerja' => $kinerja, 'avg_ks' => $avgKS, 'avg_iv' => $avgIV, 'avg_pe' => $avgPE,
        'tim_scores' => $ts, 'final' => $final,
    ];
}

usort($results, fn($a, $b) =>
    $a['final'] === null && $b['final'] === null ? strcmp($a['nama'], $b['nama']) :
    ($a['final'] === null ? 1 : ($b['final'] === null ? -1 : $b['final'] <=> $a['final']))
);

$periodLabel = $bulanIndo[$selMonth] . ' ' . $selYear;

$page_title  = 'Petugas PST Terbaik · ' . $periodLabel;
$head_extras = ['fontawesome'];
include __DIR__ . '/../partials/_head.php';
?>
<style>
  body { font-family: system-ui, sans-serif; }
  .tab-btn.active { border-bottom: 2px solid #3b82f6; color: #3b82f6; font-weight: 600; }
  .tab-panel { display: none; } .tab-panel.active { display: block; }
  @media print { .no-print { display:none!important; } }
</style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

<!-- Mobile topbar -->
<div class="lg:hidden fixed top-0 inset-x-0 z-40 bg-white border-b flex items-center gap-2 px-3 h-11 no-print">
  <button onclick="const sb=document.getElementById('sidebar'),ov=document.getElementById('sidebarOverlay');sb.classList.toggle('-translate-x-full');ov.classList.toggle('hidden',!sb.classList.contains('-translate-x-full'));" class="p-1.5 rounded hover:bg-gray-100">
    <i class="fas fa-bars text-gray-600"></i>
  </button>
  <span class="text-sm font-semibold truncate">Petugas PST Terbaik</span>
  <span class="ml-auto inline-flex items-center gap-1 bg-blue-600 text-white text-xs font-bold px-2.5 py-1 rounded-full shrink-0">
    <i class="fas fa-calendar-alt text-[10px]"></i><?= htmlspecialchars($periodLabel) ?>
  </span>
</div>
<div id="sidebarOverlay" onclick="document.getElementById('sidebar').classList.add('-translate-x-full');this.classList.add('hidden')"
     class="hidden fixed inset-0 bg-black/30 z-40 no-print"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-full w-56 bg-white border-r z-50 flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-200 no-print overflow-y-auto">
  <div class="px-4 py-3 border-b">
    <a href="<?= APP_BASE ?>/menu" class="text-xs text-gray-400 hover:text-blue-600"><i class="fas fa-home mr-1"></i>Menu Utama</a>
    <div class="text-sm font-bold text-gray-700 mt-1">Petugas PST Terbaik</div>
  </div>

  <div class="px-3 py-3 border-b space-y-2">
    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Periode</div>
    <!-- Periode aktif — prominent badge -->
    <div class="bg-blue-600 rounded-xl px-3 py-2.5 text-center text-white">
      <div class="text-[10px] font-semibold uppercase tracking-widest opacity-75 mb-0.5">Periode Aktif</div>
      <div class="text-xl font-black leading-none"><?= $bulanIndo[$selMonth] ?></div>
      <div class="text-sm font-semibold opacity-80 mt-0.5"><?= $selYear ?></div>
    </div>
    <div class="flex gap-1">
      <a href="?bulan=<?= $pM ?>&tahun=<?= $pY ?>&tab=<?= $activeTab ?>" class="flex-1 text-center text-xs py-1.5 rounded border hover:bg-gray-100"><i class="fas fa-chevron-left"></i></a>
      <a href="?bulan=<?= $nM ?>&tahun=<?= $nY ?>&tab=<?= $activeTab ?>" class="flex-1 text-center text-xs py-1.5 rounded border hover:bg-gray-100"><i class="fas fa-chevron-right"></i></a>
    </div>
    <form method="GET" class="space-y-1.5">
      <input type="hidden" name="tab" value="<?= $activeTab ?>">
      <select name="bulan" onchange="this.form.submit()" class="w-full text-sm border rounded px-2 py-1.5 bg-white">
        <?php foreach ($bulanIndo as $i => $nm): if (!$i) continue; ?>
        <option value="<?= $i ?>" <?= $i===$selMonth?'selected':'' ?>><?= $nm ?></option>
        <?php endforeach; ?>
      </select>
      <select name="tahun" onchange="this.form.submit()" class="w-full text-sm border rounded px-2 py-1.5 bg-white">
        <?php for ($y=date('Y')-2; $y<=date('Y')+1; $y++): ?>
        <option value="<?= $y ?>" <?= $y===$selYear?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </form>
  </div>

  <div class="px-3 py-3 space-y-1 text-xs text-gray-500">
    <div class="font-semibold text-gray-400 uppercase tracking-wide mb-2">Keterangan Bobot</div>
    <div class="flex justify-between"><span>Kehadiran</span><span class="font-semibold text-gray-700">20%</span></div>
    <div class="flex justify-between"><span>Kinerja</span><span class="font-semibold text-gray-700">30%</span></div>
    <div class="flex justify-between"><span>Kerja Sama</span><span class="font-semibold text-gray-700">10%</span></div>
    <div class="flex justify-between"><span>Inovatif</span><span class="font-semibold text-gray-700">10%</span></div>
    <div class="flex justify-between"><span>Penampilan</span><span class="font-semibold text-gray-700">10%</span></div>
    <div class="flex justify-between"><span>Performa</span><span class="font-semibold text-gray-700">20%</span></div>
    <div class="border-t mt-1 pt-1 flex justify-between font-semibold text-gray-700"><span>Total</span><span>100%</span></div>
  </div>
</aside>

<!-- Main -->
<div class="lg:pl-56">
  <div class="pt-14 lg:pt-0 px-4 py-6 max-w-5xl mx-auto">

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Petugas PST Terbaik</h1>
      <div class="mt-2 flex items-center gap-3 flex-wrap">
        <span class="inline-flex items-center gap-2 bg-blue-600 text-white font-extrabold px-4 py-1.5 rounded-lg text-lg leading-none shadow-sm">
          <i class="fas fa-calendar-alt text-sm opacity-80"></i>
          <?= htmlspecialchars($periodLabel) ?>
        </span>
        <span class="text-gray-400 text-sm">
          <?= count($officers) ?> petugas piket &middot;
          <?= $hariKerja ?> hari kerja &middot;
          <?= $totalPST ?> pengunjung PST
        </span>
      </div>
    </div>

    <?php if (empty($officers)): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center text-yellow-700">
      <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
      <p class="font-semibold">Tidak ada data absensi piket untuk <?= htmlspecialchars($periodLabel) ?></p>
      <p class="text-sm mt-1">Pastikan petugas sudah melakukan absensi di bulan ini.</p>
    </div>
    <?php else: ?>

    <!-- Tabs -->
    <div class="border-b mb-6 flex gap-6 no-print">
      <button class="tab-btn <?= $activeTab==='peringkat'?'active':'text-gray-500' ?> py-2 text-sm" data-tab="peringkat"><i class="fas fa-trophy mr-1"></i>Peringkat</button>
      <button class="tab-btn <?= $activeTab==='kinerja'?'active':'text-gray-500' ?> py-2 text-sm" data-tab="kinerja"><i class="fas fa-star mr-1"></i>Input Kinerja</button>
      <button class="tab-btn <?= $activeTab==='tim'?'active':'text-gray-500' ?> py-2 text-sm" data-tab="tim"><i class="fas fa-users mr-1"></i>Tim Penilai</button>
    </div>

    <!-- TAB: PERINGKAT -->
    <div id="tab-peringkat" class="tab-panel <?= $activeTab==='peringkat'?'active':'' ?>">

      <?php
      $rank = 0; $prevScore = null;
      foreach ($results as $idx => $r):
          if ($r['final'] !== null) {
              if ($r['final'] !== $prevScore) { $rank = $idx + 1; $prevScore = $r['final']; }
          }
          $rankDisplay = $r['final'] !== null ? $rank : '-';
          $medal = ['1' => '🥇', '2' => '🥈', '3' => '🥉'][(string)$rankDisplay] ?? '';
      ?>
      <div class="bg-white border rounded-xl mb-4 overflow-hidden shadow-sm">
        <div class="flex items-center gap-4 px-5 py-4 <?= $rankDisplay === 1 ? 'bg-yellow-50 border-b border-yellow-100' : 'border-b' ?>">
          <div class="text-3xl w-10 text-center">
            <?= $medal ?: '<span class="text-lg font-bold text-gray-400">' . $rankDisplay . '</span>' ?>
          </div>
          <div class="flex-1">
            <div class="font-bold text-base"><?= htmlspecialchars($r['nama']) ?></div>
            <div class="text-xs text-gray-500"><?= htmlspecialchars($r['jabatan'] ?? '') ?></div>
          </div>
          <div class="text-right">
            <?php if ($r['final'] !== null): ?>
            <div class="text-2xl font-black <?= $rankDisplay === 1 ? 'text-yellow-600' : 'text-blue-600' ?>"><?= $r['final'] ?></div>
            <div class="text-xs text-gray-400">Skor Akhir</div>
            <?php else: ?>
            <div class="text-sm text-gray-400 italic">Belum lengkap</div>
            <?php endif; ?>
          </div>
        </div>
        <!-- Score breakdown -->
        <div class="grid grid-cols-3 sm:grid-cols-6 divide-x divide-y sm:divide-y-0 text-center text-xs">
          <?php
          $cells = [
            ['Kehadiran', '20%', $r['kehadiran'] !== null ? $r['kehadiran'] : null,
             $r['hari_hadir'].'/'.$r['hari_kerja'].' hari', 'bg-sky-50 text-sky-700'],
            ['Kinerja',   '30%', $r['kinerja'],   null, 'bg-purple-50 text-purple-700'],
            ['Kerja Sama','10%', $r['avg_ks'],     null, 'bg-pink-50 text-pink-700'],
            ['Inovatif',  '10%', $r['avg_iv'],     null, 'bg-orange-50 text-orange-700'],
            ['Penampilan','10%', $r['avg_pe'],     null, 'bg-green-50 text-green-700'],
            ['Performa',  '20%', $r['performa'],
             'Pekan: '.implode(',',$r['piket_weeks']), 'bg-teal-50 text-teal-700'],
          ];
          foreach ($cells as [$label, $bobot, $val, $sub, $cls]): ?>
          <div class="py-3 px-2 <?= $cls ?>">
            <div class="font-semibold text-gray-600"><?= $label ?></div>
            <div class="text-gray-400 text-[10px]"><?= $bobot ?></div>
            <div class="font-black text-base mt-0.5"><?= $val !== null ? $val : '<span class="text-gray-300">—</span>' ?></div>
            <?php if ($sub): ?><div class="text-[10px] text-gray-400 mt-0.5"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (count(array_filter(array_column($results, 'final'))) === 0): ?>
      <div class="text-center text-gray-400 py-6 text-sm">
        Isi Kinerja dan penilaian Tim Penilai untuk melihat peringkat.
      </div>
      <?php endif; ?>
    </div>

    <!-- TAB: INPUT KINERJA -->
    <div id="tab-kinerja" class="tab-panel <?= $activeTab==='kinerja'?'active':'' ?>">
      <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5 text-sm text-blue-800">
        <div class="flex items-start justify-between gap-4 flex-wrap">
          <div>
            <i class="fas fa-info-circle mr-1"></i>
            Masukkan <strong>nilai CKP / kinerja</strong> (1–100) untuk setiap petugas yang piket bulan ini.
            Bobot: <strong>30%</strong>.
          </div>
          <div class="inline-flex items-center gap-1.5 bg-blue-600 text-white font-bold px-3 py-1.5 rounded-lg text-sm shrink-0 shadow-sm">
            <i class="fas fa-calendar-check text-xs opacity-80"></i>
            <?= htmlspecialchars($periodLabel) ?>
          </div>
        </div>
      </div>

      <div class="space-y-3">
        <?php foreach ($results as $r): ?>
        <div class="bg-white border rounded-xl px-5 py-4 flex items-center gap-4">
          <div class="flex-1">
            <div class="font-semibold"><?= htmlspecialchars($r['nama']) ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($r['jabatan'] ?? '') ?></div>
          </div>
          <div class="flex items-center gap-2">
            <input type="number" min="1" max="100"
              id="kinerja-<?= $r['id'] ?>"
              value="<?= $r['kinerja'] !== null ? $r['kinerja'] : '' ?>"
              placeholder="1–100"
              class="w-20 border rounded px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-400"
              data-original="<?= $r['kinerja'] !== null ? $r['kinerja'] : '' ?>">
            <?php if ($r['kinerja'] !== null): ?>
            <button type="button" onclick="hapusKinerja(<?= $r['id'] ?>, <?= $selMonth ?>, <?= $selYear ?>)"
              title="Hapus nilai kinerja ini"
              class="text-red-400 hover:text-red-600 text-xs px-1.5 py-1.5 rounded transition">
              <i class="fas fa-times-circle"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-4 flex items-center justify-end gap-3 flex-wrap">
        <span id="kinerja-all-status" class="text-sm mr-auto"></span>
        <button type="button" onclick="batalkanKinerja()"
          class="text-gray-500 hover:text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-300 hover:border-gray-400 transition">
          <i class="fas fa-undo mr-1"></i>Batalkan Perubahan
        </button>
        <button type="button" onclick="saveAllKinerja()"
          class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-5 py-2 rounded-lg font-semibold transition">
          <i class="fas fa-save mr-1"></i>Simpan Semua
        </button>
      </div>
    </div>

    <!-- TAB: TIM PENILAI -->
    <div id="tab-tim" class="tab-panel <?= $activeTab==='tim'?'active':'' ?>">
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-sm text-amber-800">
        <div class="flex items-start justify-between gap-4 flex-wrap">
          <div>
            <i class="fas fa-user-check mr-1"></i>
            Pilih nama Anda, lalu isi <strong>Kerja Sama</strong>, <strong>Inovatif</strong>, dan <strong>Penampilan</strong>
            (masing-masing 1–100) untuk setiap petugas. Bobot masing-masing: <strong>10%</strong>.
          </div>
          <div class="inline-flex items-center gap-1.5 bg-amber-600 text-white font-bold px-3 py-1.5 rounded-lg text-sm shrink-0 shadow-sm">
            <i class="fas fa-calendar-check text-xs opacity-80"></i>
            <?= htmlspecialchars($periodLabel) ?>
          </div>
        </div>
      </div>

      <!-- Select penilai -->
      <div class="bg-white border rounded-xl px-5 py-4 mb-5 flex items-center gap-3">
        <label class="text-sm font-semibold text-gray-700 shrink-0">Saya adalah:</label>
        <select id="select-penilai" onchange="updateTimInputs()"
          class="border rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
          <option value="">— Pilih nama —</option>
          <?php foreach ($TIM_PENILAI as $tp): ?>
          <option value="<?= $tp ?>" <?= $tp === $selPenilai ? 'selected' : '' ?>><?= $tp ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="tim-form" class="space-y-4 hidden">
        <?php foreach ($results as $r): ?>
        <div class="bg-white border rounded-xl px-5 py-4">
          <div class="font-semibold mb-3"><?= htmlspecialchars($r['nama']) ?>
            <span class="text-xs text-gray-400 font-normal ml-1"><?= htmlspecialchars($r['jabatan'] ?? '') ?></span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?php
            $fields = [
              ['ks', 'Kerja Sama', 'pink'],
              ['iv', 'Inovatif',   'orange'],
              ['pe', 'Penampilan', 'green'],
            ];
            foreach ($fields as [$key, $label, $color]): ?>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1"><?= $label ?> <span class="font-normal text-gray-400">(10%)</span></label>
              <input type="number" min="1" max="100"
                id="tim-<?= $r['id'] ?>-<?= $key ?>"
                data-pid="<?= $r['id'] ?>" data-key="<?= $key ?>"
                placeholder="1–100"
                class="tim-input w-full border rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-<?= $color ?>-400">
            </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <button type="button" onclick="hapusTimPenilai(<?= $r['id'] ?>, <?= $selMonth ?>, <?= $selYear ?>)"
              id="tim-hapus-<?= $r['id'] ?>"
              class="hidden text-red-400 hover:text-red-600 text-xs px-2 py-1 rounded border border-red-200 hover:border-red-400 transition">
              <i class="fas fa-trash-alt mr-1"></i>Batalkan Penilaian Saya
            </button>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="mt-4 flex items-center justify-end gap-3 flex-wrap">
          <span id="tim-all-status" class="text-sm mr-auto"></span>
          <button type="button" onclick="batalkanTim()"
            class="text-gray-500 hover:text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-300 hover:border-gray-400 transition">
            <i class="fas fa-undo mr-1"></i>Batalkan Perubahan
          </button>
          <button type="button" onclick="saveAllTimPenilai()"
            class="bg-amber-600 hover:bg-amber-700 text-white text-sm px-5 py-2 rounded-lg font-semibold transition">
            <i class="fas fa-save mr-1"></i>Simpan Semua
          </button>
        </div>
      </div>

      <div id="tim-placeholder" class="text-center text-gray-400 py-8 text-sm">
        Pilih nama Anda terlebih dahulu.
      </div>
    </div>

    <?php endif; ?>
  </div>
</div>

<script>
const APP_BASE    = '<?= APP_BASE ?>';
const BULAN       = <?= $selMonth ?>;
const TAHUN       = <?= $selYear ?>;
const SEL_PENILAI = <?= json_encode($selPenilai) ?>;
const OFFICER_IDS = <?= json_encode(array_column($results, 'id')) ?>;

// Stored tim penilai scores from PHP (indexed by pegawai_id)
const timScores = <?= json_encode(array_column($results, 'tim_scores', 'id')) ?>;

// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});

// Save all kinerja
async function saveAllKinerja() {
  const statusEl = document.getElementById('kinerja-all-status');
  const toSave = [];
  for (const pid of OFFICER_IDS) {
    const inp = document.getElementById('kinerja-' + pid);
    if (!inp) continue;
    const val = inp.value.trim();
    if (!val) continue;
    if (parseInt(val) < 1 || parseInt(val) > 100) {
      setStatus(statusEl, 'Nilai harus 1–100', 'red'); return;
    }
    toSave.push({ pid, val });
  }
  if (toSave.length === 0) { setStatus(statusEl, 'Tidak ada nilai yang diisi', 'red'); return; }
  setStatus(statusEl, 'Menyimpan…', 'gray');
  let ok = 0, fail = 0;
  for (const { pid, val } of toSave) {
    const fd = new FormData();
    fd.append('tipe', 'kinerja'); fd.append('pegawai_id', pid);
    fd.append('bulan', BULAN); fd.append('tahun', TAHUN); fd.append('nilai', val);
    const res = await postSave(fd);
    res.success ? ok++ : fail++;
  }
  if (fail === 0) {
    setStatus(statusEl, `✓ ${ok} tersimpan`, 'green');
    setTimeout(() => location.href = `?bulan=${BULAN}&tahun=${TAHUN}&tab=kinerja`, 800);
  } else {
    setStatus(statusEl, `${ok} tersimpan, ${fail} gagal`, 'red');
  }
}

// Hapus nilai kinerja satu petugas dari DB
async function hapusKinerja(pid, bulan, tahun) {
  if (!confirm('Hapus nilai kinerja ini dari database?')) return;
  const statusEl = document.getElementById('kinerja-all-status');
  const fd = new FormData();
  fd.append('tipe', 'kinerja'); fd.append('pegawai_id', pid);
  fd.append('bulan', bulan); fd.append('tahun', tahun);
  const res = await postDelete(fd);
  if (res.success) {
    setStatus(statusEl, '✓ Nilai dihapus', 'green');
    setTimeout(() => location.href = `?bulan=${BULAN}&tahun=${TAHUN}&tab=kinerja`, 800);
  } else {
    setStatus(statusEl, res.message || 'Gagal', 'red');
  }
}

// Batalkan semua perubahan kinerja yang belum disimpan (kembalikan ke nilai DB)
function batalkanKinerja() {
  for (const pid of OFFICER_IDS) {
    const inp = document.getElementById('kinerja-' + pid);
    if (inp) inp.value = inp.dataset.original || '';
  }
  setStatus(document.getElementById('kinerja-all-status'), 'Perubahan dibatalkan', 'gray');
}

// Pre-fill tim inputs when penilai is selected; show/hide Hapus buttons
function updateTimInputs() {
  const penilai     = document.getElementById('select-penilai').value;
  const form        = document.getElementById('tim-form');
  const placeholder = document.getElementById('tim-placeholder');
  if (!penilai) { form.classList.add('hidden'); placeholder.classList.remove('hidden'); return; }
  form.classList.remove('hidden'); placeholder.classList.add('hidden');

  document.querySelectorAll('.tim-input').forEach(inp => {
    const pid = inp.dataset.pid;
    const key = inp.dataset.key;
    let score = (timScores[pid] && timScores[pid][penilai]) ? timScores[pid][penilai][key] : '';
    // For iwansantika: default to avg of paseksusena + madekariasa if not yet saved
    if (!score && penilai === 'iwansantika') {
      const a = timScores[pid]?.paseksusena?.[key] ?? null;
      const b = timScores[pid]?.madekariasa?.[key] ?? null;
      if (a !== null && b !== null) score = Math.round((+a + +b) / 2);
      else if (a !== null) score = a;
      else if (b !== null) score = b;
    }
    inp.value = score || '';
  });

  // Show "Batalkan Penilaian Saya" only for rows this penilai already submitted
  for (const pid of OFFICER_IDS) {
    const hapusBtn = document.getElementById('tim-hapus-' + pid);
    if (!hapusBtn) continue;
    const hasScore = !!(timScores[pid] && timScores[pid][penilai]);
    hasScore ? hapusBtn.classList.remove('hidden') : hapusBtn.classList.add('hidden');
  }
}

// Save all tim penilai
async function saveAllTimPenilai() {
  const penilai  = document.getElementById('select-penilai').value;
  const statusEl = document.getElementById('tim-all-status');
  if (!penilai) { setStatus(statusEl, 'Pilih nama Anda dulu', 'red'); return; }
  const toSave = [];
  for (const pid of OFFICER_IDS) {
    const ks = document.getElementById('tim-' + pid + '-ks').value.trim();
    const iv = document.getElementById('tim-' + pid + '-iv').value.trim();
    const pe = document.getElementById('tim-' + pid + '-pe').value.trim();
    if (!ks && !iv && !pe) continue;
    for (const v of [ks, iv, pe]) {
      if (!v || parseInt(v) < 1 || parseInt(v) > 100) {
        setStatus(statusEl, 'Semua nilai harus 1–100 (atau kosongkan semua)', 'red'); return;
      }
    }
    toSave.push({ pid, ks, iv, pe });
  }
  if (toSave.length === 0) { setStatus(statusEl, 'Tidak ada nilai yang diisi', 'red'); return; }
  setStatus(statusEl, 'Menyimpan…', 'gray');
  let ok = 0, fail = 0;
  for (const { pid, ks, iv, pe } of toSave) {
    const fd = new FormData();
    fd.append('tipe', 'tim_penilai'); fd.append('pegawai_id', pid);
    fd.append('bulan', BULAN); fd.append('tahun', TAHUN);
    fd.append('nama_penilai', penilai);
    fd.append('nilai_kerja_sama', ks); fd.append('nilai_inovatif', iv); fd.append('nilai_penampilan', pe);
    const res = await postSave(fd);
    res.success ? ok++ : fail++;
  }
  if (fail === 0) {
    setStatus(statusEl, `✓ ${ok} tersimpan`, 'green');
    setTimeout(() => location.href = `?bulan=${BULAN}&tahun=${TAHUN}&tab=tim&penilai=${encodeURIComponent(penilai)}`, 800);
  } else {
    setStatus(statusEl, `${ok} tersimpan, ${fail} gagal`, 'red');
  }
}

// Hapus penilaian tim penilai satu petugas dari DB
async function hapusTimPenilai(pid, bulan, tahun) {
  const penilai  = document.getElementById('select-penilai').value;
  const statusEl = document.getElementById('tim-all-status');
  if (!penilai) { setStatus(statusEl, 'Pilih nama Anda dulu', 'red'); return; }
  if (!confirm(`Batalkan penilaian ${penilai} untuk petugas ini?`)) return;
  const fd = new FormData();
  fd.append('tipe', 'tim_penilai'); fd.append('pegawai_id', pid);
  fd.append('bulan', bulan); fd.append('tahun', tahun);
  fd.append('nama_penilai', penilai);
  const res = await postDelete(fd);
  if (res.success) {
    setStatus(statusEl, '✓ Penilaian dibatalkan', 'green');
    setTimeout(() => location.href = `?bulan=${BULAN}&tahun=${TAHUN}&tab=tim&penilai=${encodeURIComponent(penilai)}`, 800);
  } else {
    setStatus(statusEl, res.message || 'Gagal', 'red');
  }
}

// Batalkan perubahan tim penilai yang belum disimpan (kembalikan ke nilai DB)
function batalkanTim() {
  updateTimInputs();
  setStatus(document.getElementById('tim-all-status'), 'Perubahan dibatalkan', 'gray');
}

async function postSave(fd) {
  try {
    const r = await fetch(APP_BASE + '/penghargaan/action/save.php', { method: 'POST', body: fd });
    return await r.json();
  } catch (e) {
    return { success: false, message: 'Gagal menghubungi server' };
  }
}

async function postDelete(fd) {
  try {
    const r = await fetch(APP_BASE + '/penghargaan/action/delete.php', { method: 'POST', body: fd });
    return await r.json();
  } catch (e) {
    return { success: false, message: 'Gagal menghubungi server' };
  }
}

function setStatus(el, msg, color) {
  const colors = { green: 'text-green-600', red: 'text-red-500', gray: 'text-gray-400' };
  el.className  = 'text-xs ' + (colors[color] || 'text-gray-400');
  el.textContent = msg;
}

// Auto-init Tim Penilai tab if penilai was pre-selected from URL
if (SEL_PENILAI) updateTimInputs();
</script>
</body>
</html>

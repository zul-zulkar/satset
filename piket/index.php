<?php
/**
 * Kelola Jadwal Piket PST + Nomor HP Pegawai.
 *
 * Jadwal piket per minggu (Senin–Jumat, kunci = tanggal Senin) dipakai oleh
 * notifikasi WhatsApp: saat ada isian buku tamu /whatsapp baru, petugas yang
 * terjadwal pada minggu berjalan menerima pesan WA (lihat app/wa.php).
 *
 * Akses: petugas yang sudah login absensi ($_SESSION['absensi_auth']).
 */
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

include_once __DIR__ . '/../app/config.php';

if (empty($_SESSION['absensi_auth'])) {
    header('Location: ' . APP_URL . '/absensi/login');
    exit;
}

include __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/wa.php';
wa_ensure_tables($mysqli);

// ── Navigasi bulan ────────────────────────────────────────────────────────
$bulanIndo = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$selYear   = max(2020, min(2035, (int)($_GET['tahun'] ?? date('Y'))));
$selMonth  = max(1, min(12, (int)($_GET['bulan'] ?? date('n'))));
$pM = $selMonth - 1; $pY = $selYear; if ($pM < 1)  { $pM = 12; $pY--; }
$nM = $selMonth + 1; $nY = $selYear; if ($nM > 12) { $nM = 1;  $nY++; }

// Minggu Sen–Jum dalam bulan — konsep sama dengan penghargaan/index.php
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
$weeks    = weeksInMonth($selYear, $selMonth);
$seninIni = wa_monday_of();

// ── Penugasan piket untuk minggu-minggu bulan terpilih ────────────────────
$jadwal = [];   // senin => [ [pegawai_id, nama, jabatan, no_hp], ... ]
$monList = array_column($weeks, 'mon');
if ($monList) {
    $ph = implode(',', array_fill(0, count($monList), '?'));
    $st = $mysqli->prepare(
        "SELECT jp.senin, p.id AS pegawai_id, p.nama, p.jabatan, p.no_hp
         FROM jadwal_piket jp JOIN pegawai p ON p.id = jp.pegawai_id
         WHERE jp.senin IN ($ph) ORDER BY p.nama"
    );
    $st->bind_param(str_repeat('s', count($monList)), ...$monList);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $jadwal[$r['senin']][] = $r;
    }
    $st->close();
}

// ── Daftar pegawai (untuk dropdown + tabel no_hp) ─────────────────────────
$pegawai = $mysqli->query("SELECT id, nama, jabatan, no_hp FROM pegawai ORDER BY nama")
                  ->fetch_all(MYSQLI_ASSOC);

function tglPendek(string $iso): string {
    $bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $ts  = strtotime($iso);
    return date('j', $ts) . ' ' . $bln[(int)date('n', $ts)];
}

$page_title  = 'Jadwal Piket PST';
$head_extras = ['fontawesome'];
include __DIR__ . '/../app/partials/_head.php';
?>
</head>
<body class="min-h-screen bg-gray-50">

  <!-- Top bar -->
  <header class="bg-blue-700 text-white">
    <div class="max-w-2xl mx-auto flex items-center gap-3 px-4 py-3">
      <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
        <i class="fas fa-calendar-week text-white"></i>
      </div>
      <div class="flex-1">
        <p class="font-bold text-sm leading-tight">Jadwal Piket PST</p>
        <p class="text-xs text-blue-200">BPS Kabupaten Buleleng</p>
      </div>
      <a href="<?= APP_BASE ?>/absensi" class="text-xs bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition font-semibold flex items-center gap-1.5">
        <i class="fas fa-clipboard-check"></i>
        <span class="hidden sm:inline">Absensi</span>
      </a>
      <a href="<?= APP_BASE ?>/menu.php" class="text-xs bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition font-semibold flex items-center gap-1.5">
        <i class="fas fa-house"></i>
        <span class="hidden sm:inline">Menu</span>
      </a>
    </div>
  </header>

  <main class="max-w-2xl mx-auto px-4 py-5 space-y-4 pb-12">

    <!-- Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700 flex items-start gap-2">
      <i class="fas fa-circle-info mt-0.5 flex-shrink-0"></i>
      <span>Petugas yang terjadwal pada minggu berjalan akan menerima <strong>notifikasi WhatsApp</strong> setiap ada isian buku tamu baru dari kanal WhatsApp. Pastikan nomor HP pegawai terisi.</span>
    </div>

    <div id="toast" class="hidden rounded-xl px-4 py-3 text-sm font-medium text-center"></div>

    <!-- Navigasi bulan -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3 flex items-center justify-between">
      <a href="?bulan=<?= $pM ?>&tahun=<?= $pY ?>"
         class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition">
        <i class="fas fa-chevron-left text-sm"></i>
      </a>
      <p class="font-bold text-gray-800"><?= $bulanIndo[$selMonth] . ' ' . $selYear ?></p>
      <a href="?bulan=<?= $nM ?>&tahun=<?= $nY ?>"
         class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition">
        <i class="fas fa-chevron-right text-sm"></i>
      </a>
    </div>

    <!-- Kartu per minggu -->
    <?php foreach ($weeks as $w):
        $isNow    = $w['mon'] === $seninIni;
        $assigned = $jadwal[$w['mon']] ?? [];
        $adaHpValid = false;
        foreach ($assigned as $a) {
            if (wa_normalize_phone($a['no_hp'] ?? '') !== null) { $adaHpValid = true; break; }
        }
        $assignedIds = array_column($assigned, 'pegawai_id');
    ?>
    <div class="bg-white rounded-2xl border <?= $isNow ? 'border-blue-300 ring-2 ring-blue-100' : 'border-gray-100' ?> shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
        <i class="fas fa-calendar-day <?= $isNow ? 'text-blue-600' : 'text-gray-400' ?>"></i>
        <h2 class="font-bold text-gray-800 text-sm">Minggu <?= $w['num'] ?></h2>
        <span class="text-xs text-gray-400">Sen <?= tglPendek($w['mon']) ?> – Jum <?= tglPendek($w['fri']) ?></span>
        <?php if ($isNow): ?>
          <span class="ml-auto text-[10px] font-bold uppercase tracking-wide bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Minggu ini</span>
        <?php endif; ?>
      </div>
      <div class="p-4 space-y-3">

        <?php if (empty($assigned)): ?>
          <p class="text-xs text-gray-400 italic">Belum ada petugas terjadwal.</p>
        <?php else: ?>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($assigned as $a):
                $hpValid = wa_normalize_phone($a['no_hp'] ?? '') !== null; ?>
            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl pl-3 pr-1.5 py-1.5 text-sm">
              <i class="fas fa-user-circle text-gray-400"></i>
              <div class="leading-tight">
                <p class="font-semibold text-gray-800 text-xs"><?= htmlspecialchars($a['nama']) ?></p>
                <?php if ($hpValid): ?>
                  <p class="text-[10px] text-green-600"><i class="fab fa-whatsapp"></i> <?= htmlspecialchars($a['no_hp']) ?></p>
                <?php else: ?>
                  <p class="text-[10px] text-red-500"><i class="fas fa-triangle-exclamation"></i> belum ada no. HP</p>
                <?php endif; ?>
              </div>
              <button onclick="hapusPiket('<?= $w['mon'] ?>', <?= (int)$a['pegawai_id'] ?>, '<?= htmlspecialchars($a['nama'], ENT_QUOTES) ?>')"
                      class="text-red-400 hover:text-red-600 transition p-1.5">
                <i class="fas fa-trash text-xs"></i>
              </button>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!$adaHpValid): ?>
          <div class="bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 text-xs text-amber-700 flex items-start gap-2">
            <i class="fas fa-triangle-exclamation mt-0.5 flex-shrink-0"></i>
            <span>Belum ada petugas dengan nomor HP valid di minggu ini — notifikasi akan dikirim ke nomor fallback.</span>
          </div>
        <?php endif; ?>

        <div class="flex gap-2">
          <select id="sel-<?= $w['mon'] ?>"
                  class="flex-1 min-w-0 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
            <option value="">— pilih pegawai —</option>
            <?php foreach ($pegawai as $p): if (in_array((int)$p['id'], array_map('intval', $assignedIds), true)) continue; ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['nama']) ?><?= wa_normalize_phone($p['no_hp'] ?? '') !== null ? '' : ' (tanpa no. HP)' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button onclick="tambahPiket('<?= $w['mon'] ?>')"
                  class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">
            Tambah
          </button>
        </div>

      </div>
    </div>
    <?php endforeach; ?>

    <!-- Nomor HP pegawai -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
        <i class="fab fa-whatsapp text-green-500"></i>
        <h2 class="font-bold text-gray-800 text-sm">Nomor HP Pegawai</h2>
        <span class="ml-auto text-xs text-gray-400">format 08xx / +62xx</span>
      </div>
      <div class="divide-y divide-gray-50">
        <?php foreach ($pegawai as $p): ?>
        <div class="px-4 py-2.5 flex items-center gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-gray-800 truncate"><?= htmlspecialchars($p['nama']) ?></p>
            <p class="text-[10px] text-gray-400 truncate"><?= htmlspecialchars($p['jabatan'] ?? '') ?></p>
          </div>
          <input type="tel" id="hp-<?= $p['id'] ?>" value="<?= htmlspecialchars($p['no_hp'] ?? '') ?>"
                 placeholder="08xxxxxxxxxx"
                 class="w-36 sm:w-44 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-green-400">
          <button onclick="simpanHp(<?= $p['id'] ?>)"
                  class="flex-shrink-0 bg-green-600 hover:bg-green-700 text-white w-8 h-8 rounded-lg text-xs transition"
                  title="Simpan nomor">
            <i class="fas fa-floppy-disk"></i>
          </button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </main>

<script>
var APP_BASE = '<?= APP_BASE ?>';

function showToast(msg, type) {
    var el = document.getElementById('toast');
    el.className = 'rounded-xl px-4 py-3 text-sm font-medium text-center ' +
        (type === 'success'
            ? 'bg-green-100 text-green-700 border border-green-200'
            : 'bg-red-100 text-red-700 border border-red-200');
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(el._t);
    el._t = setTimeout(function() { el.classList.add('hidden'); }, 4000);
}

function tambahPiket(senin) {
    var sel = document.getElementById('sel-' + senin);
    if (!sel.value) { showToast('Pilih pegawai terlebih dahulu.', 'error'); return; }

    var fd = new FormData();
    fd.append('action',     'add');
    fd.append('senin',      senin);
    fd.append('pegawai_id', sel.value);

    fetch(APP_BASE + '/piket/action/save_piket.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) { location.reload(); }
            else { showToast(res.message, 'error'); }
        })
        .catch(function() { showToast('Gagal menghubungi server.', 'error'); });
}

function hapusPiket(senin, pegawaiId, nama) {
    if (!confirm('Hapus ' + nama + ' dari jadwal piket minggu ini?')) return;

    var fd = new FormData();
    fd.append('action',     'remove');
    fd.append('senin',      senin);
    fd.append('pegawai_id', pegawaiId);

    fetch(APP_BASE + '/piket/action/save_piket.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) { location.reload(); }
            else { showToast(res.message, 'error'); }
        })
        .catch(function() { showToast('Gagal menghubungi server.', 'error'); });
}

function simpanHp(pegawaiId) {
    var inp = document.getElementById('hp-' + pegawaiId);

    var fd = new FormData();
    fd.append('pegawai_id', pegawaiId);
    fd.append('no_hp',      inp.value.trim());

    fetch(APP_BASE + '/piket/action/save_hp.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                inp.classList.add('ring-2', 'ring-green-400');
                setTimeout(function() { inp.classList.remove('ring-2', 'ring-green-400'); }, 1500);
            }
        })
        .catch(function() { showToast('Gagal menghubungi server.', 'error'); });
}
</script>
</body>
</html>

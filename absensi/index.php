<?php
// Session bertahan 30 hari
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

include_once __DIR__ . '/../config.php';

if (empty($_SESSION['absensi_auth'])) {
    header('Location: ' . APP_URL . '/absensi/login');
    exit;
}

include '../db.php';

// ── Data pegawai dari session ─────────────────────────────────────────
$pegawaiId     = (int) $_SESSION['pegawai_id'];
$pegawaiNama   = $_SESSION['pegawai_nama']    ?? '';
$pegawaiJabatan = $_SESSION['pegawai_jabatan'] ?? '';

// ── Koordinat PST — baca dari config/pst.json ────────────────────────
$_pstCfg  = file_exists(__DIR__ . '/config/pst.json')
           ? (json_decode(file_get_contents(__DIR__ . '/config/pst.json'), true) ?? [])
           : [];
$PST_LAT    = (float) ($_pstCfg['lat']    ?? -8.1134);
$PST_LNG    = (float) ($_pstCfg['lng']    ?? 115.0940);
$PST_RADIUS = (int)   ($_pstCfg['radius'] ?? 100);

// ── Jadwal WFH & Libur ───────────────────────────────────────────────
$_jadwal    = file_exists(__DIR__ . '/config/jadwal.json')
            ? (json_decode(file_get_contents(__DIR__ . '/config/jadwal.json'), true) ?? [])
            : [];
$_wfhHari   = $_jadwal['wfh_hari']    ?? [5];          // 5 = Jumat
$_wfhCustom = array_column($_jadwal['wfh_tanggal'] ?? [], 'tanggal');
$_liburArr  = array_column($_jadwal['libur']        ?? [], 'tanggal');
$_todayDow  = (int) date('w');   // 0=Min … 6=Sab

$isWFH   = in_array($_todayDow, $_wfhHari) || in_array(date('Y-m-d'), $_wfhCustom);
$isLibur = in_array(date('Y-m-d'), $_liburArr);
if ($isLibur) { $isWFH = false; }   // libur > WFH

$wfhKet = '';
if (in_array(date('Y-m-d'), $_wfhCustom)) {
    foreach ($_jadwal['wfh_tanggal'] ?? [] as $_w) {
        if ($_w['tanggal'] === date('Y-m-d')) { $wfhKet = $_w['keterangan'] ?? ''; break; }
    }
} elseif ($isWFH) {
    $wfhKet = 'Hari Jumat — WFH rutin';
}
$liburKet = '';
foreach ($_jadwal['libur'] ?? [] as $_l) {
    if ($_l['tanggal'] === date('Y-m-d')) { $liburKet = $_l['keterangan'] ?? ''; break; }
}

// ── Tanggal hari ini (tanpa IntlDateFormatter) ────────────────────────
$today  = date('Y-m-d');
$ts     = strtotime($today);
$days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$months = ['','Januari','Februari','Maret','April','Mei','Juni',
           'Juli','Agustus','September','Oktober','November','Desember'];
$hariIni = $days[date('w', $ts)] . ', ' . date('j', $ts) . ' '
         . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);

// ── Absensi saya hari ini ─────────────────────────────────────────────
$stmtSaya = $mysqli->prepare(
    "SELECT jam_masuk, jam_keluar FROM absensi_piket
     WHERE pegawai_id = ? AND tanggal = ? LIMIT 1"
);
$stmtSaya->bind_param("is", $pegawaiId, $today);
$stmtSaya->execute();
$absensiSaya = $stmtSaya->get_result()->fetch_assoc();
$stmtSaya->close();

$jamMasukSaya  = $absensiSaya && $absensiSaya['jam_masuk']
               ? date('H:i:s', strtotime($absensiSaya['jam_masuk'])) : null;
$jamKeluarSaya = $absensiSaya && $absensiSaya['jam_keluar']
               ? date('H:i:s', strtotime($absensiSaya['jam_keluar'])) : null;

// ── Rekap seluruh pegawai hari ini ────────────────────────────────────
$stmtRekap = $mysqli->prepare(
    "SELECT ap.pegawai_id, ap.jam_masuk, ap.jam_keluar,
            p.nama AS nama_pegawai, p.jabatan
     FROM absensi_piket ap
     JOIN pegawai p ON p.id = ap.pegawai_id
     WHERE ap.tanggal = ?
     ORDER BY ap.jam_masuk ASC"
);
$stmtRekap->bind_param("s", $today);
$stmtRekap->execute();
$rekapHariIni = $stmtRekap->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtRekap->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Absensi Piket PST · BPS Buleleng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  @keyframes spin-gps { to { transform: rotate(360deg); } }
  .spin-gps { animation: spin-gps 1s linear infinite; }
</style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- ── Top Bar ────────────────────────────────────────────────────────── -->
<header class="bg-blue-700 text-white">
  <div class="max-w-lg mx-auto flex items-center gap-3 px-4 py-3">
    <!-- Info pegawai -->
    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
      <i class="fas fa-user text-white"></i>
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-bold text-sm leading-tight truncate"><?= htmlspecialchars($pegawaiNama) ?></p>
      <p class="text-xs text-blue-200 truncate"><?= htmlspecialchars($pegawaiJabatan) ?></p>
    </div>
    <button onclick="bukaModalPass()"
            class="flex-shrink-0 flex items-center gap-1.5 text-xs bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition font-semibold">
      <i class="fas fa-key"></i>
    </button>
    <a href="<?= APP_BASE ?>/absensi/admin"
       class="flex-shrink-0 flex items-center gap-1.5 text-xs bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition font-semibold">
      <i class="fas fa-gear"></i>
      <span>Admin</span>
    </a>
    <a href="<?= APP_BASE ?>/absensi/logout"
       onclick="return confirm('Keluar dari sistem absensi?')"
       class="flex-shrink-0 flex items-center gap-1.5 text-xs bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition font-semibold">
      <i class="fas fa-right-from-bracket"></i>
      <span>Keluar</span>
    </a>
  </div>
</header>

<!-- ── Modal Ganti Password ───────────────────────────────────────────── -->
<div id="modalGantiPass" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="tutupModalPass()"></div>
  <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2">
          <i class="fas fa-key text-blue-500"></i>
          <h3 class="font-bold text-gray-800 text-sm">Ganti Password</h3>
        </div>
        <button onclick="tutupModalPass()" class="text-gray-400 hover:text-gray-600 text-lg leading-none">✕</button>
      </div>
      <div class="p-5 space-y-3">
        <div id="toastModalPass" class="hidden rounded-xl px-3 py-2.5 text-sm text-center"></div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Password Lama</label>
          <input type="password" id="passLama" placeholder="Password saat ini"
                 class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Password Baru</label>
          <input type="password" id="passBaru" placeholder="Minimal 6 karakter"
                 class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Konfirmasi Password Baru</label>
          <input type="password" id="passKonfirm" placeholder="Ulangi password baru"
                 class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <button onclick="kirimGantiPass()"
                class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition flex items-center justify-center gap-2">
          <i class="fas fa-floppy-disk"></i> Simpan Password
        </button>
      </div>
    </div>
  </div>
</div>

<main class="max-w-lg mx-auto px-4 py-5 space-y-4 pb-12">

  <!-- ── Tanggal & Jam ──────────────────────────────────────────────── -->
  <div class="flex items-center gap-2 text-sm text-gray-500">
    <i class="fas fa-calendar-day text-blue-400 flex-shrink-0"></i>
    <span><?= htmlspecialchars($hariIni) ?></span>
    <span class="ml-auto font-mono font-bold text-gray-700 tabular-nums" id="jamSekarang">--:--:--</span>
  </div>

  <?php if ($isLibur): ?>
  <!-- Banner Libur -->
  <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-700 rounded-2xl px-4 py-3">
    <div class="w-10 h-10 rounded-full bg-amber-200 flex items-center justify-center flex-shrink-0">
      <i class="fas fa-umbrella-beach text-amber-600 text-lg"></i>
    </div>
    <div>
      <p class="font-bold text-sm">Hari Libur</p>
      <p class="text-xs text-amber-600"><?= htmlspecialchars($liburKet ?: 'Tidak perlu absensi hari ini') ?></p>
    </div>
  </div>
  <?php elseif ($isWFH): ?>
  <!-- Banner WFH -->
  <div class="flex items-center gap-3 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-2xl px-4 py-3">
    <div class="w-10 h-10 rounded-full bg-indigo-200 flex items-center justify-center flex-shrink-0">
      <i class="fas fa-house-laptop text-indigo-600 text-lg"></i>
    </div>
    <div>
      <p class="font-bold text-sm">Mode WFH Aktif</p>
      <p class="text-xs text-indigo-500"><?= htmlspecialchars($wfhKet ?: 'Absensi dapat dilakukan dari mana saja') ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── GPS Status Card ────────────────────────────────────────────── -->
  <div id="gpsCard" class="rounded-2xl border-2 border-gray-200 bg-white p-4 transition-all duration-500">
    <div class="flex items-center gap-3">
      <div id="gpsIconWrap" class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
        <i id="gpsIcon" class="fas fa-location-crosshairs text-gray-400 text-xl"></i>
      </div>
      <div class="flex-1 min-w-0">
        <p id="gpsTitle" class="font-bold text-gray-700 text-sm">Mendeteksi lokasi…</p>
        <p id="gpsDesc"  class="text-xs text-gray-400 mt-0.5">Mohon izinkan akses GPS pada browser</p>
      </div>
      <button onclick="getLocation()" id="btnRetryGps"
              class="hidden flex-shrink-0 text-xs text-blue-600 hover:text-blue-800 font-semibold">
        <i class="fas fa-rotate-right mr-1"></i>Coba lagi
      </button>
    </div>
    <div id="distanceWrap" class="mt-3 hidden">
      <div class="flex justify-between text-xs text-gray-500 mb-1">
        <span>Jarak ke PST</span>
        <span id="distanceLabel" class="font-semibold">--</span>
      </div>
      <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div id="distanceBar" class="h-full rounded-full transition-all duration-700 bg-green-500" style="width:0%"></div>
      </div>
    </div>
  </div>

  <!-- ── Status Absensi Saya ────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
    <h2 class="font-bold text-gray-800 text-sm flex items-center gap-2 mb-3">
      <i class="fas fa-user-check text-blue-500"></i>
      Kehadiran Saya Hari Ini
    </h2>

    <!-- Status jam masuk / keluar -->
    <div class="grid grid-cols-2 gap-2 mb-4">
      <div class="bg-green-50 border border-green-100 rounded-xl p-3 text-center">
        <p class="text-xs text-green-600 font-medium mb-1 flex items-center justify-center gap-1">
          <i class="fas fa-arrow-right-to-bracket text-[10px]"></i> Masuk
        </p>
        <p id="statusMasuk" class="text-base font-bold text-green-700 tabular-nums">
          <?= $jamMasukSaya ?? '--:--:--' ?>
        </p>
      </div>
      <div class="bg-orange-50 border border-orange-100 rounded-xl p-3 text-center">
        <p class="text-xs text-orange-600 font-medium mb-1 flex items-center justify-center gap-1">
          <i class="fas fa-arrow-right-from-bracket text-[10px]"></i> Keluar
        </p>
        <p id="statusKeluar" class="text-base font-bold text-orange-700 tabular-nums">
          <?= $jamKeluarSaya ?? '--:--:--' ?>
        </p>
      </div>
    </div>

    <!-- Tombol Absen (1 tombol) -->
    <button id="btnAbsen" onclick="absen()" disabled
            class="w-full flex flex-col items-center gap-2 py-5 rounded-xl font-semibold text-sm transition-all
                   bg-gray-100 text-gray-400 cursor-not-allowed">
      <i class="fas fa-location-crosshairs text-2xl"></i>
      <span>Mendeteksi Lokasi…</span>
    </button>

    <!-- Feedback toast -->
    <div id="toastAbsen" class="hidden mt-3 rounded-xl px-4 py-3 text-sm font-medium text-center"></div>
  </div>

  <!-- ── Rekap Seluruh Petugas ──────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-bold text-gray-800 text-sm flex items-center gap-2">
        <i class="fas fa-list-check text-blue-500"></i>
        Rekap Hari Ini
      </h2>
      <span class="text-xs bg-blue-50 text-blue-600 font-semibold px-2 py-0.5 rounded-full" id="jumlahPetugas">
        <?= count($rekapHariIni) ?> petugas
      </span>
    </div>

    <div id="rekapContainer">
    <?php if (empty($rekapHariIni)): ?>
      <div id="rekapEmpty" class="py-10 text-center text-gray-400 text-sm">
        <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
        Belum ada yang absen hari ini
      </div>
    <?php else: ?>
      <ul class="divide-y divide-gray-50" id="rekapList">
        <?php foreach ($rekapHariIni as $row): ?>
        <li id="rekap-<?= $row['pegawai_id'] ?>" class="px-4 py-3 flex items-center gap-3">
          <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center
                      <?= $row['pegawai_id'] == $pegawaiId ? 'bg-blue-500' : 'bg-gray-100' ?>">
            <i class="fas fa-user text-sm <?= $row['pegawai_id'] == $pegawaiId ? 'text-white' : 'text-gray-400' ?>"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-800 text-sm truncate">
              <?= htmlspecialchars($row['nama_pegawai']) ?>
              <?php if ($row['pegawai_id'] == $pegawaiId): ?>
                <span class="ml-1 text-[10px] bg-blue-100 text-blue-600 font-bold px-1.5 py-0.5 rounded-full">Saya</span>
              <?php endif; ?>
            </p>
            <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($row['jabatan']) ?></p>
          </div>
          <div class="flex-shrink-0 text-right text-xs space-y-0.5">
            <div class="flex items-center gap-1 justify-end text-green-600 font-semibold tabular-nums">
              <i class="fas fa-arrow-right-to-bracket text-[10px]"></i>
              <?= $row['jam_masuk']  ? date('H:i:s', strtotime($row['jam_masuk']))  : '--:--:--' ?>
            </div>
            <div class="flex items-center gap-1 justify-end text-orange-500 font-semibold tabular-nums">
              <i class="fas fa-arrow-right-from-bracket text-[10px]"></i>
              <?= $row['jam_keluar'] ? date('H:i:s', strtotime($row['jam_keluar'])) : '--:--:--' ?>
            </div>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    </div>
  </div>

  <!-- ── Rekap Pribadi ────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
      <i class="fas fa-calendar-days text-blue-500"></i>
      <h2 class="font-bold text-gray-800 text-sm">Riwayat Absensi Saya</h2>
      <input type="month" id="filterBulanPribadi"
             value="<?= date('Y-m') ?>"
             class="ml-auto text-xs border border-gray-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>
    <div id="rekapPribadiWrap" class="divide-y divide-gray-50">
      <div class="py-8 text-center text-gray-400 text-sm">
        <i class="fas fa-spinner fa-spin text-2xl mb-2 block text-gray-300"></i>Memuat…
      </div>
    </div>
  </div>

</main>

<script>
// ── Data dari server ──────────────────────────────────────────────────
var PEGAWAI_ID   = <?= $pegawaiId ?>;
var PEGAWAI_NAMA = <?= json_encode($pegawaiNama) ?>;
var JAM_MASUK    = <?= json_encode($jamMasukSaya) ?>;   // null atau "H:i:s"
var JAM_KELUAR   = <?= json_encode($jamKeluarSaya) ?>;  // null atau "H:i:s"
var PST_LAT      = <?= $PST_LAT ?>;
var PST_LNG      = <?= $PST_LNG ?>;
var PST_RADIUS   = <?= $PST_RADIUS ?>;
var ABSEN_URL    = 'action/absen.php';
var IS_WFH       = <?= $isWFH   ? 'true' : 'false' ?>;
var IS_LIBUR     = <?= $isLibur  ? 'true' : 'false' ?>;
var WFH_KET      = <?= json_encode($wfhKet) ?>;
var LIBUR_KET    = <?= json_encode($liburKet) ?>;

var currentLat   = null;
var currentLng   = null;
var withinRange  = false;

// ── Jam berjalan ─────────────────────────────────────────────────────
(function tick() {
    var d = new Date();
    document.getElementById('jamSekarang').textContent =
        String(d.getHours()).padStart(2,'0')   + ':' +
        String(d.getMinutes()).padStart(2,'0') + ':' +
        String(d.getSeconds()).padStart(2,'0');
    setTimeout(tick, 1000);
})();

// ── Haversine ─────────────────────────────────────────────────────────
function haversine(lat1, lng1, lat2, lng2) {
    var R    = 6371000;
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLng = (lng2 - lng1) * Math.PI / 180;
    var a    = Math.sin(dLat/2) * Math.sin(dLat/2) +
               Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
               Math.sin(dLng/2) * Math.sin(dLng/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// ── GPS ───────────────────────────────────────────────────────────────
function getLocation() {
    if (IS_LIBUR) { setGpsState('libur'); return; }
    if (IS_WFH)   { withinRange = true; setGpsState('wfh'); updateButtons(); return; }
    setGpsState('loading');
    if (!navigator.geolocation) { setGpsState('unsupported'); return; }
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            currentLat  = pos.coords.latitude;
            currentLng  = pos.coords.longitude;
            var dist    = haversine(currentLat, currentLng, PST_LAT, PST_LNG);
            withinRange = dist <= PST_RADIUS;
            setGpsState(withinRange ? 'ok' : 'far', dist);
            updateButtons();
        },
        function(err) {
            var msg = 'Tidak dapat mendeteksi lokasi.';
            if (err.code === 1) msg = 'Izin GPS ditolak. Aktifkan lokasi di pengaturan browser.';
            if (err.code === 3) msg = 'GPS timeout. Pastikan sinyal GPS aktif lalu coba lagi.';
            setGpsState('error', null, msg);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

function setGpsState(state, dist, errMsg) {
    var card     = document.getElementById('gpsCard');
    var iconWrap = document.getElementById('gpsIconWrap');
    var icon     = document.getElementById('gpsIcon');
    var title    = document.getElementById('gpsTitle');
    var desc     = document.getElementById('gpsDesc');
    var retry    = document.getElementById('btnRetryGps');
    var dWrap    = document.getElementById('distanceWrap');
    var dLabel   = document.getElementById('distanceLabel');
    var dBar     = document.getElementById('distanceBar');

    card.className = 'rounded-2xl border-2 p-4 transition-all duration-500';
    icon.className = 'text-xl';
    icon.classList.remove('spin-gps');
    retry.classList.add('hidden');
    dWrap.classList.add('hidden');

    if (state === 'loading') {
        card.classList.add('border-gray-200', 'bg-white');
        iconWrap.className = 'w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0';
        icon.classList.add('fas', 'fa-location-crosshairs', 'text-blue-500', 'spin-gps');
        title.className = 'font-bold text-gray-700 text-sm';
        title.textContent = 'Mendeteksi lokasi…';
        desc.className  = 'text-xs text-gray-400 mt-0.5';
        desc.textContent = 'Mohon tunggu, sedang membaca GPS';

    } else if (state === 'ok') {
        card.classList.add('border-green-300', 'bg-green-50');
        iconWrap.className = 'w-12 h-12 rounded-full bg-green-200 flex items-center justify-center flex-shrink-0';
        icon.classList.add('fas', 'fa-circle-check', 'text-green-600');
        title.className = 'font-bold text-green-700 text-sm';
        title.textContent = 'Anda berada di area PST';
        desc.className  = 'text-xs text-green-500 mt-0.5';
        desc.textContent = 'Lokasi terverifikasi — absensi dapat dilakukan';
        dWrap.classList.remove('hidden');
        dLabel.textContent = Math.round(dist) + ' m';
        dBar.style.width   = Math.min(100, Math.round(dist / PST_RADIUS * 100)) + '%';
        dBar.className     = 'h-full rounded-full transition-all duration-700 bg-green-500';

    } else if (state === 'far') {
        card.classList.add('border-red-300', 'bg-red-50');
        iconWrap.className = 'w-12 h-12 rounded-full bg-red-200 flex items-center justify-center flex-shrink-0';
        icon.classList.add('fas', 'fa-location-xmark', 'text-red-500');
        title.className = 'font-bold text-red-600 text-sm';
        title.textContent = 'Di luar area PST';
        desc.className  = 'text-xs text-red-400 mt-0.5';
        desc.textContent = 'Jarak Anda ' + Math.round(dist) + ' m dari PST (maks. ' + PST_RADIUS + ' m)';
        retry.classList.remove('hidden');
        dWrap.classList.remove('hidden');
        dLabel.textContent = Math.round(dist) + ' m';
        dBar.style.width   = Math.min(100, Math.round(dist / (PST_RADIUS * 3) * 100)) + '%';
        dBar.className     = 'h-full rounded-full transition-all duration-700 bg-red-500';

    } else if (state === 'error') {
        card.classList.add('border-yellow-300', 'bg-yellow-50');
        iconWrap.className = 'w-12 h-12 rounded-full bg-yellow-200 flex items-center justify-center flex-shrink-0';
        icon.classList.add('fas', 'fa-triangle-exclamation', 'text-yellow-600');
        title.className = 'font-bold text-yellow-700 text-sm';
        title.textContent = 'GPS tidak tersedia';
        desc.className  = 'text-xs text-yellow-600 mt-0.5';
        desc.textContent = errMsg || 'Tidak dapat membaca lokasi';
        retry.classList.remove('hidden');

    } else if (state === 'wfh') {
        card.classList.add('border-indigo-300', 'bg-indigo-50');
        iconWrap.className = 'w-12 h-12 rounded-full bg-indigo-200 flex items-center justify-center flex-shrink-0';
        icon.classList.add('fas', 'fa-house-laptop', 'text-indigo-600');
        title.className = 'font-bold text-indigo-700 text-sm';
        title.textContent = 'Mode WFH — absensi dari mana saja';
        desc.className  = 'text-xs text-indigo-500 mt-0.5';
        desc.textContent = WFH_KET || 'Lokasi tidak diwajibkan hari ini';

    } else if (state === 'libur') {
        card.classList.add('border-amber-300', 'bg-amber-50');
        iconWrap.className = 'w-12 h-12 rounded-full bg-amber-200 flex items-center justify-center flex-shrink-0';
        icon.classList.add('fas', 'fa-umbrella-beach', 'text-amber-600');
        title.className = 'font-bold text-amber-700 text-sm';
        title.textContent = 'Hari Libur';
        desc.className  = 'text-xs text-amber-600 mt-0.5';
        desc.textContent = LIBUR_KET || 'Tidak perlu absensi hari ini';

    } else { // unsupported
        card.classList.add('border-gray-300', 'bg-gray-50');
        iconWrap.className = 'w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0';
        icon.classList.add('fas', 'fa-ban', 'text-gray-500');
        title.className = 'font-bold text-gray-600 text-sm';
        title.textContent = 'GPS tidak didukung';
        desc.className  = 'text-xs text-gray-400 mt-0.5';
        desc.textContent = 'Browser ini tidak mendukung geolocation';
    }
}

// ── Update tombol (1 tombol) ───────────────────────────────────────────
function updateButtons() {
    var btn = document.getElementById('btnAbsen');
    var base = 'w-full flex flex-col items-center gap-2 py-5 rounded-xl font-semibold text-sm transition-all ';

    if (IS_LIBUR) {
        btn.disabled  = true;
        btn.className = base + 'bg-amber-100 text-amber-400 cursor-not-allowed';
        btn.innerHTML = '<i class="fas fa-umbrella-beach text-2xl"></i><span>Hari Libur</span>';
        return;
    }

    if (!JAM_MASUK) {
        // Belum masuk — wajib dalam jangkauan GPS
        btn.disabled  = !withinRange;
        btn.className = base + (withinRange
            ? 'bg-green-500 hover:bg-green-600 active:bg-green-700 text-white shadow-md shadow-green-200 cursor-pointer'
            : 'bg-green-100 text-green-400 cursor-not-allowed');
        btn.innerHTML = '<i class="fas fa-arrow-right-to-bracket text-2xl"></i><span>Absen Masuk</span>';
    } else if (!JAM_KELUAR) {
        // Sudah masuk, belum keluar — wajib dalam jangkauan GPS
        btn.disabled  = !withinRange;
        btn.className = base + (withinRange
            ? 'bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white shadow-md shadow-orange-200 cursor-pointer'
            : 'bg-orange-100 text-orange-400 cursor-not-allowed');
        btn.innerHTML = '<i class="fas fa-arrow-right-from-bracket text-2xl"></i><span>Absen Keluar</span>';
    } else {
        // Sudah keluar — perbarui, wajib dalam jangkauan GPS
        btn.disabled  = !withinRange;
        btn.className = base + (withinRange
            ? 'bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white shadow-md shadow-orange-200 cursor-pointer'
            : 'bg-orange-100 text-orange-400 cursor-not-allowed');
        btn.innerHTML = '<i class="fas fa-rotate-right text-2xl"></i><span>Perbarui Keluar</span>';
    }
}

// ── Absen ──────────────────────────────────────────────────────────────
function absen() {
    var tipe = !JAM_MASUK ? 'masuk' : 'keluar';
    if (!withinRange) return;
    var btn  = document.getElementById('btnAbsen');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-2xl"></i><span>Menyimpan…</span>';

    var now    = new Date();
    var nowStr = String(now.getHours()).padStart(2,'0')   + ':' +
                 String(now.getMinutes()).padStart(2,'0') + ':' +
                 String(now.getSeconds()).padStart(2,'0') + ' WITA';

    var fd = new FormData();
    fd.append('tipe', tipe);
    fd.append('lat',  currentLat !== null ? currentLat : '');
    fd.append('lng',  currentLng !== null ? currentLng : '');

    fetch(ABSEN_URL, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                var wl = res.waktu_lengkap || res.waktu;
                if (tipe === 'masuk') {
                    JAM_MASUK = wl;
                    document.getElementById('statusMasuk').textContent = wl;
                } else {
                    JAM_KELUAR = wl;
                    document.getElementById('statusKeluar').textContent = wl;
                }
                showToast(res.message + ' — ' + nowStr, 'success');
                updateRekapRow(wl, tipe);
                updateButtons();
            } else {
                showToast(res.message, 'error');
                updateButtons();
            }
        })
        .catch(function() {
            showToast('Gagal menghubungi server. Coba lagi.', 'error');
            updateButtons();
        });
}

// ── Rekap ──────────────────────────────────────────────────────────────
function updateRekapRow(jam, tipe) {
    var existing = document.getElementById('rekap-' + PEGAWAI_ID);
    var mHtml    = tipe === 'masuk'  ? jam : (JAM_MASUK  || '--:--:--');
    var kHtml    = tipe === 'keluar' ? jam : (JAM_KELUAR || '--:--:--');

    var html = '<li id="rekap-' + PEGAWAI_ID + '" class="px-4 py-3 flex items-center gap-3">' +
        '<div class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0">' +
          '<i class="fas fa-user text-white text-sm"></i>' +
        '</div>' +
        '<div class="flex-1 min-w-0">' +
          '<p class="font-semibold text-gray-800 text-sm truncate">' + escHtml(PEGAWAI_NAMA) +
            ' <span class="text-[10px] bg-blue-100 text-blue-600 font-bold px-1.5 py-0.5 rounded-full">Saya</span></p>' +
        '</div>' +
        '<div class="flex-shrink-0 text-right text-xs space-y-0.5">' +
          '<div class="flex items-center gap-1 justify-end text-green-600 font-semibold tabular-nums">' +
            '<i class="fas fa-arrow-right-to-bracket text-[10px]"></i>' + mHtml + '</div>' +
          '<div class="flex items-center gap-1 justify-end text-orange-500 font-semibold tabular-nums">' +
            '<i class="fas fa-arrow-right-from-bracket text-[10px]"></i>' + kHtml + '</div>' +
        '</div></li>';

    if (existing) {
        existing.outerHTML = html;
    } else {
        var empty = document.getElementById('rekapEmpty');
        if (empty) {
            document.getElementById('rekapContainer').innerHTML =
                '<ul class="divide-y divide-gray-50" id="rekapList"></ul>';
        }
        document.getElementById('rekapList').insertAdjacentHTML('beforeend', html);
        // Update jumlah
        var badge = document.getElementById('jumlahPetugas');
        var count = document.querySelectorAll('#rekapList li').length;
        badge.textContent = count + ' petugas';
    }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Toast ──────────────────────────────────────────────────────────────
function showToast(msg, type) {
    var el = document.getElementById('toastAbsen');
    el.className = 'mt-3 rounded-xl px-4 py-3 text-sm font-medium text-center ' +
        (type === 'success'
            ? 'bg-green-100 text-green-700 border border-green-200'
            : 'bg-red-100 text-red-700 border border-red-200');
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(el._t);
    el._t = setTimeout(function() { el.classList.add('hidden'); }, 5000);
}

// ── Init ───────────────────────────────────────────────────────────────
updateButtons();  // set disabled state dari data PHP
getLocation();

// ── Rekap Pribadi per Bulan ────────────────────────────────────────────
var HARI = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

function muatRekapPribadi() {
    var bulan = document.getElementById('filterBulanPribadi').value;
    var wrap  = document.getElementById('rekapPribadiWrap');
    wrap.innerHTML = '<div class="py-6 text-center text-gray-400 text-sm"><i class="fas fa-spinner fa-spin text-xl"></i></div>';
    fetch('action/rekap_pribadi.php?bulan=' + encodeURIComponent(bulan))
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.data || res.data.length === 0) {
                wrap.innerHTML = '<div class="py-8 text-center text-gray-400 text-sm"><i class="fas fa-calendar-xmark text-2xl mb-2 block text-gray-300"></i>Tidak ada data untuk bulan ini</div>';
                return;
            }
            var html = '';
            res.data.forEach(function(r) {
                var d    = new Date(r.tanggal + 'T00:00:00');
                var hari = HARI[d.getDay()];
                var tgl  = d.getDate() + ' ' + ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][d.getMonth()+1];
                html += '<div class="px-4 py-3 flex items-center gap-3">' +
                    '<div class="w-9 text-center flex-shrink-0">' +
                      '<p class="text-[10px] text-gray-400 leading-none">' + hari + '</p>' +
                      '<p class="text-base font-bold text-gray-700 leading-tight">' + d.getDate() + '</p>' +
                    '</div>' +
                    '<div class="flex-1 h-px bg-gray-100"></div>' +
                    '<div class="flex-shrink-0 text-right text-xs space-y-0.5">' +
                      '<div class="flex items-center gap-1 justify-end ' + (r.jam_masuk ? 'text-green-600 font-semibold' : 'text-gray-300') + ' tabular-nums">' +
                        '<i class="fas fa-arrow-right-to-bracket text-[10px]"></i>' + (r.jam_masuk || '--:--:--') + '</div>' +
                      '<div class="flex items-center gap-1 justify-end ' + (r.jam_keluar ? 'text-orange-500 font-semibold' : 'text-gray-300') + ' tabular-nums">' +
                        '<i class="fas fa-arrow-right-from-bracket text-[10px]"></i>' + (r.jam_keluar || '--:--:--') + '</div>' +
                    '</div>' +
                '</div>';
            });
            // Summary footer
            var total = res.data.length;
            html += '<div class="px-4 py-2 bg-gray-50 text-xs text-gray-500 text-right border-t border-gray-100">' +
                total + ' hari hadir bulan ini' +
            '</div>';
            wrap.innerHTML = html;
        })
        .catch(function() {
            wrap.innerHTML = '<div class="py-6 text-center text-red-400 text-sm">Gagal memuat data</div>';
        });
}

document.getElementById('filterBulanPribadi').addEventListener('change', muatRekapPribadi);
muatRekapPribadi();

// ── Ganti Password ─────────────────────────────────────────────────────
function bukaModalPass() {
    document.getElementById('passLama').value    = '';
    document.getElementById('passBaru').value    = '';
    document.getElementById('passKonfirm').value = '';
    document.getElementById('toastModalPass').className = 'hidden';
    document.getElementById('modalGantiPass').classList.remove('hidden');
}
function tutupModalPass() {
    document.getElementById('modalGantiPass').classList.add('hidden');
}
function kirimGantiPass() {
    var lama    = document.getElementById('passLama').value;
    var baru    = document.getElementById('passBaru').value;
    var konfirm = document.getElementById('passKonfirm').value;
    if (!lama || !baru || !konfirm) { toastPass('Semua field wajib diisi.', 'error'); return; }
    if (baru !== konfirm)           { toastPass('Password baru tidak cocok.', 'error'); return; }
    if (baru.length < 6)            { toastPass('Password baru minimal 6 karakter.', 'error'); return; }

    var fd = new FormData();
    fd.append('password_lama', lama);
    fd.append('password_baru', baru);
    fetch('action/ganti_password.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(res){
            toastPass(res.message, res.success ? 'success' : 'error');
            if (res.success) { setTimeout(tutupModalPass, 2000); }
        })
        .catch(function(){ toastPass('Gagal menghubungi server.', 'error'); });
}
function toastPass(msg, type) {
    var el = document.getElementById('toastModalPass');
    el.className = 'rounded-xl px-3 py-2.5 text-sm text-center ' +
        (type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
    el.textContent = msg;
}
</script>
</body>
</html>

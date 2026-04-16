<?php
/**
 * Halaman admin — atur koordinat PST dan radius absensi.
 * Password admin default: admin2024
 * Ganti hash di bawah setelah pertama kali digunakan.
 */
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

include_once __DIR__ . '/../config.php';

// ── bcrypt hash untuk password admin ────────────────────────────────────
// Default: admin2024 — ubah hash ini setelah pertama pakai
const ADMIN_PASS_HASH = '$2y$10$lKUwHqZcuYMOKKxNKyQ3.ejJ1Tzl5.NL.2mrbdAIB/Du4nNgoMWGy';

$error = '';

// Proses login admin
if (!empty($_POST['admin_password'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASS_HASH)) {
        $_SESSION['absensi_admin'] = true;
        header('Location: ' . APP_URL . '/absensi/admin');
        exit;
    }
    $error = 'Password admin salah.';
}

// Proses logout admin
if (isset($_GET['logout'])) {
    unset($_SESSION['absensi_admin']);
    header('Location: ' . APP_URL . '/absensi/admin');
    exit;
}

// Baca konfigurasi saat ini
$configPath = __DIR__ . '/config/pst.json';
$cfg = file_exists($configPath)
     ? (json_decode(file_get_contents($configPath), true) ?? [])
     : [];
$curLat    = $cfg['lat']    ?? -8.1134;
$curLng    = $cfg['lng']    ?? 115.0940;
$curRadius = $cfg['radius'] ?? 100;

$isAdmin = !empty($_SESSION['absensi_admin']);

// Ambil daftar bulan yang ada data (untuk dropdown)
$bulanList = [];
if ($isAdmin) {
    include_once __DIR__ . '/../db.php';
    $res = $mysqli->query(
        "SELECT DISTINCT DATE_FORMAT(tanggal,'%Y-%m') AS bln
         FROM absensi_piket ORDER BY bln DESC LIMIT 24"
    );
    while ($r = $res->fetch_assoc()) { $bulanList[] = $r['bln']; }
    if (empty($bulanList)) { $bulanList[] = date('Y-m'); }

    // Daftar pegawai untuk reset password
    $daftarPegawai = [];
    $resPeg = $mysqli->query("SELECT id, nama, username FROM pegawai WHERE username IS NOT NULL ORDER BY nama ASC");
    if ($resPeg) {
        while ($r = $resPeg->fetch_assoc()) { $daftarPegawai[] = $r; }
    }

    // Jadwal WFH & Libur
    $jadwalPath     = __DIR__ . '/config/jadwal.json';
    $jadwal         = file_exists($jadwalPath)
                    ? (json_decode(file_get_contents($jadwalPath), true) ?? [])
                    : [];
    $jadwalWfhCustom = $jadwal['wfh_tanggal'] ?? [];
    $jadwalLibur     = $jadwal['libur']        ?? [];
}

// Helper: format tanggal Indonesia singkat
function formatTanggalAdmin(string $iso): string {
    $days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $months = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $ts = strtotime($iso);
    return $days[date('w',$ts)] . ', ' . date('j',$ts) . ' ' . $months[(int)date('n',$ts)] . ' ' . date('Y',$ts);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · Konfigurasi PST</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  body { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #3b82f6 100%); }
  #map { z-index: 0; }
  .leaflet-container { border-radius: 0.75rem; }
</style>
</head>
<body class="min-h-screen <?= $isAdmin ? 'bg-gray-50' : '' ?> p-4">

<?php if (!$isAdmin): ?>
<!-- ══ Login Admin ══════════════════════════════════════════════════════ -->
<div class="min-h-screen flex items-center justify-center">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/20 backdrop-blur mb-4">
        <i class="fas fa-shield-halved text-white text-3xl"></i>
      </div>
      <h1 class="text-white text-2xl font-bold tracking-tight">Admin · Absensi PST</h1>
      <p class="text-blue-200 text-sm mt-1">BPS Kabupaten Buleleng</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-6">
      <?php if ($error): ?>
        <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
          <i class="fas fa-circle-exclamation flex-shrink-0"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password Admin</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
              <i class="fas fa-lock text-sm"></i>
            </span>
            <input type="password" name="admin_password" id="adminPass" autocomplete="current-password" required
                   class="w-full pl-9 pr-10 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                   placeholder="Password admin">
            <button type="button" onclick="togglePass()"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition">
              <i id="passEye" class="fas fa-eye text-sm"></i>
            </button>
          </div>
        </div>
        <button type="submit"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-md shadow-blue-200">
          <i class="fas fa-right-to-bracket"></i>
          Masuk sebagai Admin
        </button>
      </form>

      <div class="mt-4 text-center">
        <a href="<?= APP_BASE ?>/absensi/login" class="text-xs text-gray-400 hover:text-gray-600 transition">
          <i class="fas fa-arrow-left mr-1"></i>Kembali ke login petugas
        </a>
      </div>
    </div>
  </div>
</div>

<script>
function togglePass() {
    var inp = document.getElementById('adminPass');
    var eye = document.getElementById('passEye');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    eye.className = inp.type === 'text' ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
}
</script>

<?php else: ?>
<!-- ══ Halaman Konfigurasi ══════════════════════════════════════════════ -->
<div class="min-h-screen bg-gray-50">

  <!-- Top bar -->
  <header class="bg-blue-700 text-white">
    <div class="max-w-2xl mx-auto flex items-center gap-3 px-4 py-3">
      <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
        <i class="fas fa-shield-halved text-white"></i>
      </div>
      <div class="flex-1">
        <p class="font-bold text-sm leading-tight">Admin · Konfigurasi PST</p>
        <p class="text-xs text-blue-200">BPS Kabupaten Buleleng</p>
      </div>
      <a href="<?= APP_BASE ?>/absensi" class="text-xs bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition font-semibold flex items-center gap-1.5">
        <i class="fas fa-clipboard-check"></i>
        <span class="hidden sm:inline">Absensi</span>
      </a>
      <a href="<?= APP_BASE ?>/absensi/admin?logout=1"
         onclick="return confirm('Keluar dari panel admin?')"
         class="text-xs bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition font-semibold flex items-center gap-1.5">
        <i class="fas fa-right-from-bracket"></i>
        <span class="hidden sm:inline">Keluar</span>
      </a>
    </div>
  </header>

  <main class="max-w-2xl mx-auto px-4 py-5 space-y-4 pb-12">

    <!-- Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700 flex items-start gap-2">
      <i class="fas fa-circle-info mt-0.5 flex-shrink-0"></i>
      <span>Klik pada peta untuk menentukan titik tengah area PST. Atur radius agar mencakup area yang diperbolehkan untuk absensi.</span>
    </div>

    <!-- Peta -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
        <i class="fas fa-map-location-dot text-blue-500"></i>
        <h2 class="font-bold text-gray-800 text-sm">Titik Lokasi PST</h2>
        <span class="ml-auto text-xs text-gray-400">Klik peta untuk mengatur ulang titik</span>
      </div>
      <div id="map" class="w-full" style="height: 340px;"></div>
    </div>

    <!-- Form koordinat -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-4">
      <h2 class="font-bold text-gray-800 text-sm flex items-center gap-2">
        <i class="fas fa-sliders text-blue-500"></i>
        Pengaturan Koordinat
      </h2>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1.5">Latitude</label>
          <input type="number" id="inpLat" step="any"
                 value="<?= htmlspecialchars($curLat) ?>"
                 class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                 placeholder="-8.1134">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1.5">Longitude</label>
          <input type="number" id="inpLng" step="any"
                 value="<?= htmlspecialchars($curLng) ?>"
                 class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                 placeholder="115.0940">
        </div>
      </div>

      <!-- Radius -->
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
          Radius Area Absensi
          <span id="radiusLabel" class="ml-1 text-blue-600 font-bold"><?= $curRadius ?> m</span>
        </label>
        <input type="range" id="inpRadius" min="10" max="500" step="5"
               value="<?= $curRadius ?>"
               class="w-full accent-blue-600"
               oninput="onRadiusChange(this.value)">
        <div class="flex justify-between text-xs text-gray-400 mt-1">
          <span>10 m</span>
          <span>500 m</span>
        </div>
      </div>

      <!-- Koordinat saat ini -->
      <div class="bg-gray-50 rounded-xl px-3 py-2.5 text-xs text-gray-500 font-mono flex items-center gap-2">
        <i class="fas fa-location-dot text-blue-400"></i>
        <span id="coordDisplay"><?= $curLat ?>, <?= $curLng ?></span>
        <span class="ml-auto text-gray-400">radius <span id="coordRadius"><?= $curRadius ?></span> m</span>
      </div>

      <!-- Tombol simpan -->
      <button onclick="simpan()"
              class="w-full py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-md shadow-blue-200">
        <i class="fas fa-floppy-disk"></i>
        Simpan Konfigurasi
      </button>

      <!-- Toast -->
      <div id="toast" class="hidden rounded-xl px-4 py-3 text-sm font-medium text-center"></div>
    </div>

    <!-- Info koordinat kantor (referensi) -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
      <h2 class="font-bold text-gray-800 text-sm flex items-center gap-2 mb-3">
        <i class="fas fa-building text-blue-500"></i>
        Konfigurasi Tersimpan
      </h2>
      <div class="grid grid-cols-3 gap-3 text-center">
        <div class="bg-gray-50 rounded-xl p-3">
          <p class="text-xs text-gray-500 mb-1">Latitude</p>
          <p id="savedLat" class="font-bold text-gray-800 text-sm font-mono"><?= $curLat ?></p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3">
          <p class="text-xs text-gray-500 mb-1">Longitude</p>
          <p id="savedLng" class="font-bold text-gray-800 text-sm font-mono"><?= $curLng ?></p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3">
          <p class="text-xs text-gray-500 mb-1">Radius</p>
          <p id="savedRadius" class="font-bold text-gray-800 text-sm"><?= $curRadius ?> m</p>
        </div>
      </div>
    </div>

    <!-- ── Jadwal WFH & Libur ────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
        <i class="fas fa-calendar-check text-blue-500"></i>
        <h2 class="font-bold text-gray-800 text-sm">Jadwal WFH &amp; Libur</h2>
      </div>

      <div class="p-4 space-y-5">

        <!-- WFH Rutin -->
        <div>
          <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 flex items-center gap-2">
            <i class="fas fa-house-laptop text-indigo-500"></i> WFH Rutin
          </p>
          <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-3 py-2.5 text-sm text-indigo-700 flex items-center gap-2">
            <i class="fas fa-circle-info text-indigo-400 flex-shrink-0"></i>
            <span>Setiap hari <strong>Jumat</strong> otomatis berlaku WFH — petugas dapat absen dari mana saja.</span>
          </div>
        </div>

        <!-- WFH Tambahan -->
        <div>
          <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 flex items-center gap-2">
            <i class="fas fa-calendar-plus text-indigo-400"></i> WFH Tambahan
            <span class="font-normal normal-case text-gray-400">(kebijakan pemerintah / fakultatif)</span>
          </p>

          <div id="listWfhCustom" class="space-y-1.5 mb-3">
            <?php if (empty($jadwalWfhCustom)): ?>
            <p id="wfhCustomEmpty" class="text-xs text-gray-400 italic py-1">Belum ada jadwal WFH tambahan.</p>
            <?php else: ?>
            <?php foreach ($jadwalWfhCustom as $w): ?>
            <div class="wfh-item flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-xl px-3 py-2.5 text-sm"
                 data-tanggal="<?= htmlspecialchars($w['tanggal']) ?>">
              <i class="fas fa-house-laptop text-indigo-400 flex-shrink-0 text-xs"></i>
              <div class="flex-1 min-w-0">
                <span class="font-semibold text-indigo-700"><?= htmlspecialchars(formatTanggalAdmin($w['tanggal'])) ?></span>
                <?php if (!empty($w['keterangan'])): ?>
                <span class="text-indigo-400 text-xs ml-1">— <?= htmlspecialchars($w['keterangan']) ?></span>
                <?php endif; ?>
              </div>
              <button onclick="hapusJadwal('wfh','<?= htmlspecialchars($w['tanggal']) ?>')"
                      class="flex-shrink-0 text-red-400 hover:text-red-600 transition p-1">
                <i class="fas fa-trash text-xs"></i>
              </button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="flex gap-2">
            <input type="date" id="inputWfhTanggal"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 flex-shrink-0">
            <input type="text" id="inputWfhKet" placeholder="Keterangan (opsional)"
                   class="flex-1 min-w-0 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <button onclick="tambahJadwal('wfh')"
                    class="flex-shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">
              Tambah
            </button>
          </div>
        </div>

        <div class="border-t border-gray-100"></div>

        <!-- Libur Nasional -->
        <div>
          <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 flex items-center gap-2">
            <i class="fas fa-umbrella-beach text-amber-500"></i> Libur Nasional &amp; Cuti Bersama
          </p>

          <div id="listLibur" class="space-y-1.5 mb-3">
            <?php if (empty($jadwalLibur)): ?>
            <p id="liburEmpty" class="text-xs text-gray-400 italic py-1">Belum ada jadwal libur tersimpan.</p>
            <?php else: ?>
            <?php foreach ($jadwalLibur as $l): ?>
            <div class="libur-item flex items-center gap-2 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2.5 text-sm"
                 data-tanggal="<?= htmlspecialchars($l['tanggal']) ?>">
              <i class="fas fa-umbrella-beach text-amber-400 flex-shrink-0 text-xs"></i>
              <div class="flex-1 min-w-0">
                <span class="font-semibold text-amber-700"><?= htmlspecialchars(formatTanggalAdmin($l['tanggal'])) ?></span>
                <?php if (!empty($l['keterangan'])): ?>
                <span class="text-amber-400 text-xs ml-1">— <?= htmlspecialchars($l['keterangan']) ?></span>
                <?php endif; ?>
              </div>
              <button onclick="hapusJadwal('libur','<?= htmlspecialchars($l['tanggal']) ?>')"
                      class="flex-shrink-0 text-red-400 hover:text-red-600 transition p-1">
                <i class="fas fa-trash text-xs"></i>
              </button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="flex gap-2">
            <input type="date" id="inputLiburTanggal"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 flex-shrink-0">
            <input type="text" id="inputLiburKet" placeholder="Nama hari libur (wajib)"
                   class="flex-1 min-w-0 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
            <button onclick="tambahJadwal('libur')"
                    class="flex-shrink-0 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">
              Tambah
            </button>
          </div>
        </div>

        <div id="toastJadwal" class="hidden rounded-xl px-4 py-3 text-sm font-medium text-center"></div>
      </div>
    </div>

    <!-- ── Rekap Absensi Bulanan ─────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
        <i class="fas fa-table-list text-blue-500"></i>
        <h2 class="font-bold text-gray-800 text-sm">Rekap Absensi Bulanan</h2>
        <select id="filterBulanAdmin"
                class="ml-auto text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
          <?php
          $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                        'Juli','Agustus','September','Oktober','November','Desember'];
          foreach ($bulanList as $b):
              [$y, $m] = explode('-', $b);
          ?>
          <option value="<?= $b ?>" <?= $b === date('Y-m') ? 'selected' : '' ?>>
            <?= $namaBulan[(int)$m] . ' ' . $y ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100" id="rekapAdminSummary">
        <div class="p-3 text-center"><p class="text-xs text-gray-400 mb-0.5">Total Hadir</p><p class="font-bold text-gray-700 text-lg" id="sumHadir">–</p></div>
        <div class="p-3 text-center"><p class="text-xs text-gray-400 mb-0.5">Masuk Lengkap</p><p class="font-bold text-green-600 text-lg" id="sumLengkap">–</p></div>
        <div class="p-3 text-center"><p class="text-xs text-gray-400 mb-0.5">Blm Keluar</p><p class="font-bold text-orange-500 text-lg" id="sumBelumKeluar">–</p></div>
      </div>

      <!-- Tabel -->
      <div id="rekapAdminWrap" class="divide-y divide-gray-50">
        <div class="py-8 text-center text-gray-400 text-sm">
          <i class="fas fa-spinner fa-spin text-2xl mb-2 block text-gray-300"></i>Memuat…
        </div>
      </div>
    </div>

    <!-- ── Reset Password Pegawai ───────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
        <i class="fas fa-key text-blue-500"></i>
        <h2 class="font-bold text-gray-800 text-sm">Reset Password Pegawai</h2>
      </div>
      <div class="p-4 space-y-3">
        <div id="toastResetPass" class="hidden rounded-xl px-3 py-2.5 text-sm text-center"></div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1.5">Pilih Pegawai</label>
          <input type="hidden" id="resetPegawaiId" value="">
          <div class="relative" id="resetPegawaiWrap">
            <div class="relative">
              <input type="text" id="resetPegawaiSearch" autocomplete="off"
                     placeholder="Cari nama atau username…"
                     class="w-full pl-3 pr-8 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white cursor-pointer"
                     onfocus="bukaDropdownPegawai()" oninput="filterDropdownPegawai()">
              <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                <i class="fas fa-chevron-down text-xs" id="resetPegawaiChevron"></i>
              </span>
            </div>
            <ul id="resetPegawaiList"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-52 overflow-y-auto hidden text-sm">
              <?php foreach ($daftarPegawai as $p): ?>
              <li class="px-3 py-2.5 hover:bg-blue-50 cursor-pointer flex flex-col gap-0.5 pegawai-option"
                  data-id="<?= $p['id'] ?>"
                  data-label="<?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['username']) ?>)"
                  data-search="<?= strtolower(htmlspecialchars($p['nama']) . ' ' . htmlspecialchars($p['username'])) ?>"
                  onclick="pilihPegawai(this)">
                <span class="font-medium text-gray-800"><?= htmlspecialchars($p['nama']) ?></span>
                <span class="text-xs text-gray-400"><?= htmlspecialchars($p['username']) ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1.5">Password Baru</label>
          <input type="password" id="resetPasswordBaru" placeholder="Minimal 6 karakter"
                 class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <button onclick="kirimResetPass()"
                class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition flex items-center justify-center gap-2">
          <i class="fas fa-rotate-right"></i> Reset Password
        </button>
      </div>
    </div>

  </main>
</div>

<script>
var curLat    = <?= $curLat ?>;
var curLng    = <?= $curLng ?>;
var curRadius = <?= $curRadius ?>;
var APP_BASE  = '<?= APP_BASE ?>';

// ── Leaflet map ─────────────────────────────────────────────────────────
var map     = L.map('map').setView([curLat, curLng], 17);
var marker  = null;
var circle  = null;

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

function placeMarker(lat, lng, radius) {
    if (marker) { marker.remove(); }
    if (circle) { circle.remove(); }

    marker = L.marker([lat, lng], {
        draggable: true,
        title: 'Titik PST — seret untuk pindah'
    }).addTo(map);

    circle = L.circle([lat, lng], {
        radius: radius,
        color: '#2563eb',
        fillColor: '#3b82f6',
        fillOpacity: 0.15,
        weight: 2
    }).addTo(map);

    marker.on('dragend', function(e) {
        var pos = e.target.getLatLng();
        setCoords(pos.lat, pos.lng);
    });
}

function setCoords(lat, lng) {
    curLat = parseFloat(lat.toFixed(8));
    curLng = parseFloat(lng.toFixed(8));
    document.getElementById('inpLat').value = curLat;
    document.getElementById('inpLng').value = curLng;
    document.getElementById('coordDisplay').textContent = curLat + ', ' + curLng;
    var r = parseInt(document.getElementById('inpRadius').value);
    placeMarker(curLat, curLng, r);
}

// Klik peta → pindah titik
map.on('click', function(e) {
    setCoords(e.latlng.lat, e.latlng.lng);
});

// Input manual lat/lng
document.getElementById('inpLat').addEventListener('change', function() {
    var lat = parseFloat(this.value);
    var lng = parseFloat(document.getElementById('inpLng').value);
    if (!isNaN(lat) && !isNaN(lng)) {
        setCoords(lat, lng);
        map.setView([lat, lng], map.getZoom());
    }
});
document.getElementById('inpLng').addEventListener('change', function() {
    var lat = parseFloat(document.getElementById('inpLat').value);
    var lng = parseFloat(this.value);
    if (!isNaN(lat) && !isNaN(lng)) {
        setCoords(lat, lng);
        map.setView([lat, lng], map.getZoom());
    }
});

function onRadiusChange(val) {
    val = parseInt(val);
    document.getElementById('radiusLabel').textContent = val + ' m';
    document.getElementById('coordRadius').textContent = val;
    if (circle) { circle.setRadius(val); }
}

// Init
placeMarker(curLat, curLng, curRadius);

// ── Simpan ──────────────────────────────────────────────────────────────
function simpan() {
    var lat    = parseFloat(document.getElementById('inpLat').value);
    var lng    = parseFloat(document.getElementById('inpLng').value);
    var radius = parseInt(document.getElementById('inpRadius').value);

    if (isNaN(lat) || isNaN(lng)) {
        showToast('Koordinat tidak valid.', 'error'); return;
    }

    var fd = new FormData();
    fd.append('lat',    lat);
    fd.append('lng',    lng);
    fd.append('radius', radius);

    fetch(APP_BASE + '/absensi/action/save_config.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                document.getElementById('savedLat').textContent    = lat;
                document.getElementById('savedLng').textContent    = lng;
                document.getElementById('savedRadius').textContent = radius + ' m';
                showToast('Konfigurasi berhasil disimpan.', 'success');
            } else {
                showToast(res.message, 'error');
            }
        })
        .catch(function() { showToast('Gagal menghubungi server.', 'error'); });
}

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

// ── Rekap Absensi Bulanan ───────────────────────────────────────────────
var HARI_ADMIN = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

function muatRekapAdmin() {
    var bulan = document.getElementById('filterBulanAdmin').value;
    var wrap  = document.getElementById('rekapAdminWrap');
    wrap.innerHTML = '<div class="py-6 text-center text-gray-400 text-sm"><i class="fas fa-spinner fa-spin text-xl"></i></div>';

    fetch(APP_BASE + '/absensi/action/rekap_admin.php?bulan=' + encodeURIComponent(bulan))
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.data || res.data.length === 0) {
                document.getElementById('sumHadir').textContent       = '0';
                document.getElementById('sumLengkap').textContent     = '0';
                document.getElementById('sumBelumKeluar').textContent = '0';
                wrap.innerHTML = '<div class="py-8 text-center text-gray-400 text-sm"><i class="fas fa-calendar-xmark text-2xl mb-2 block text-gray-300"></i>Tidak ada data untuk bulan ini</div>';
                return;
            }

            var hadir = res.data.length;
            var lengkap = res.data.filter(function(r){ return r.jam_masuk && r.jam_keluar; }).length;
            var belum   = res.data.filter(function(r){ return r.jam_masuk && !r.jam_keluar; }).length;
            document.getElementById('sumHadir').textContent       = hadir;
            document.getElementById('sumLengkap').textContent     = lengkap;
            document.getElementById('sumBelumKeluar').textContent = belum;

            // Kelompokkan per tanggal
            var byDate = {};
            res.data.forEach(function(r) {
                if (!byDate[r.tanggal]) byDate[r.tanggal] = [];
                byDate[r.tanggal].push(r);
            });

            var html = '';
            Object.keys(byDate).sort().reverse().forEach(function(tgl) {
                var d    = new Date(tgl + 'T00:00:00');
                var hari = HARI_ADMIN[d.getDay()];
                var label = hari + ', ' + d.getDate() + '/' + (d.getMonth()+1);

                html += '<div class="px-4 pt-3 pb-1">' +
                    '<p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">' + label + '</p>';
                byDate[tgl].forEach(function(r) {
                    html += '<div class="flex items-center gap-2 mb-2 pl-1">' +
                        '<div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">' +
                          '<i class="fas fa-user text-gray-400 text-xs"></i>' +
                        '</div>' +
                        '<div class="flex-1 min-w-0">' +
                          '<p class="text-xs font-semibold text-gray-800 truncate">' + escHtmlAdmin(r.nama) + '</p>' +
                          '<p class="text-[10px] text-gray-400 truncate">' + escHtmlAdmin(r.jabatan) + '</p>' +
                        '</div>' +
                        '<div class="flex-shrink-0 text-right text-xs space-y-0.5">' +
                          '<div class="flex items-center gap-1 justify-end ' + (r.jam_masuk ? 'text-green-600 font-semibold' : 'text-gray-300') + ' tabular-nums">' +
                            '<i class="fas fa-arrow-right-to-bracket text-[10px]"></i>' + (r.jam_masuk || '--:--') + '</div>' +
                          '<div class="flex items-center gap-1 justify-end ' + (r.jam_keluar ? 'text-orange-500 font-semibold' : 'text-gray-300') + ' tabular-nums">' +
                            '<i class="fas fa-arrow-right-from-bracket text-[10px]"></i>' + (r.jam_keluar || '--:--') + '</div>' +
                        '</div>' +
                    '</div>';
                });
                html += '</div><div class="mx-4 h-px bg-gray-100"></div>';
            });
            wrap.innerHTML = html;
        })
        .catch(function() {
            wrap.innerHTML = '<div class="py-6 text-center text-red-400 text-sm">Gagal memuat data</div>';
        });
}

function escHtmlAdmin(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('filterBulanAdmin').addEventListener('change', muatRekapAdmin);
muatRekapAdmin();

// ── Reset Password ──────────────────────────────────────────────────────
function kirimResetPass() {
    var pegawaiId = document.getElementById('resetPegawaiId').value;
    var passBaru  = document.getElementById('resetPasswordBaru').value;
    if (!pegawaiId)          { toastReset('Pilih pegawai terlebih dahulu.', 'error'); return; }
    if (passBaru.length < 6) { toastReset('Password minimal 6 karakter.', 'error'); return; }

    var fd = new FormData();
    fd.append('pegawai_id',   pegawaiId);
    fd.append('password_baru', passBaru);
    fetch(APP_BASE + '/absensi/action/reset_password.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(res){
            toastReset(res.message, res.success ? 'success' : 'error');
            if (res.success) { document.getElementById('resetPasswordBaru').value = ''; }
        })
        .catch(function(){ toastReset('Gagal menghubungi server.', 'error'); });
}
function toastReset(msg, type) {
    var el = document.getElementById('toastResetPass');
    el.className = 'rounded-xl px-3 py-2.5 text-sm text-center ' +
        (type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
    el.textContent = msg;
    el.classList.remove('hidden');
}

// ── Searchable dropdown pegawai ─────────────────────────────────────────
function bukaDropdownPegawai() {
    document.getElementById('resetPegawaiList').classList.remove('hidden');
    document.getElementById('resetPegawaiChevron').className = 'fas fa-chevron-up text-xs';
    filterDropdownPegawai();
}

function tutupDropdownPegawai() {
    document.getElementById('resetPegawaiList').classList.add('hidden');
    document.getElementById('resetPegawaiChevron').className = 'fas fa-chevron-down text-xs';
}

function filterDropdownPegawai() {
    var q = document.getElementById('resetPegawaiSearch').value.toLowerCase();
    var items = document.querySelectorAll('.pegawai-option');
    var ada = false;
    items.forEach(function(li) {
        var match = li.dataset.search.indexOf(q) !== -1;
        li.style.display = match ? '' : 'none';
        if (match) ada = true;
    });
    var list = document.getElementById('resetPegawaiList');
    list.classList.remove('hidden');
    // Tampilkan pesan kosong jika tidak ada hasil
    var noRes = document.getElementById('resetPegawaiNoResult');
    if (!ada) {
        if (!noRes) {
            var el = document.createElement('li');
            el.id = 'resetPegawaiNoResult';
            el.className = 'px-3 py-2.5 text-gray-400 text-xs text-center';
            el.textContent = 'Tidak ditemukan';
            list.appendChild(el);
        }
    } else {
        if (noRes) noRes.remove();
    }
}

function pilihPegawai(li) {
    document.getElementById('resetPegawaiId').value = li.dataset.id;
    document.getElementById('resetPegawaiSearch').value = li.dataset.label;
    tutupDropdownPegawai();
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('resetPegawaiWrap');
    if (wrap && !wrap.contains(e.target)) {
        tutupDropdownPegawai();
        // Jika tidak ada pilihan valid, kosongkan
        if (!document.getElementById('resetPegawaiId').value) {
            document.getElementById('resetPegawaiSearch').value = '';
        }
    }
});

// ── Jadwal WFH & Libur ──────────────────────────────────────────────────
function tambahJadwal(tipe) {
    var inpTgl = document.getElementById(tipe === 'wfh' ? 'inputWfhTanggal' : 'inputLiburTanggal');
    var inpKet = document.getElementById(tipe === 'wfh' ? 'inputWfhKet'     : 'inputLiburKet');
    var tanggal = inpTgl.value;
    var ket     = inpKet.value.trim();

    if (!tanggal) { toastJadwal('Pilih tanggal terlebih dahulu.', 'error'); return; }
    if (tipe === 'libur' && !ket) { toastJadwal('Nama hari libur wajib diisi.', 'error'); return; }

    var fd = new FormData();
    fd.append('action',      'add_' + tipe);
    fd.append('tanggal',     tanggal);
    fd.append('keterangan',  ket);

    fetch(APP_BASE + '/absensi/action/save_jadwal.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                toastJadwal('Berhasil ditambahkan.', 'success');
                renderJadwalItem(tipe, tanggal, ket);
                inpTgl.value = '';
                inpKet.value = '';
            } else {
                toastJadwal(res.message, 'error');
            }
        })
        .catch(function() { toastJadwal('Gagal menghubungi server.', 'error'); });
}

function hapusJadwal(tipe, tanggal) {
    if (!confirm('Hapus tanggal ini dari daftar ' + (tipe === 'wfh' ? 'WFH' : 'libur') + '?')) return;

    var fd = new FormData();
    fd.append('action',  'remove_' + tipe);
    fd.append('tanggal', tanggal);

    fetch(APP_BASE + '/absensi/action/save_jadwal.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                var el = document.querySelector('.' + tipe + '-item[data-tanggal="' + tanggal + '"]');
                if (el) el.remove();
                // tampilkan placeholder kosong jika list sudah habis
                var listId  = tipe === 'wfh' ? 'listWfhCustom' : 'listLibur';
                var emptyId = tipe === 'wfh' ? 'wfhCustomEmpty' : 'liburEmpty';
                if (!document.querySelector('#' + listId + ' .' + tipe + '-item') && !document.getElementById(emptyId)) {
                    var p = document.createElement('p');
                    p.id = emptyId;
                    p.className = 'text-xs text-gray-400 italic py-1';
                    p.textContent = tipe === 'wfh' ? 'Belum ada jadwal WFH tambahan.' : 'Belum ada jadwal libur tersimpan.';
                    document.getElementById(listId).appendChild(p);
                }
                toastJadwal('Berhasil dihapus.', 'success');
            } else {
                toastJadwal(res.message, 'error');
            }
        })
        .catch(function() { toastJadwal('Gagal menghubungi server.', 'error'); });
}

function renderJadwalItem(tipe, tanggal, ket) {
    var emptyId = tipe === 'wfh' ? 'wfhCustomEmpty' : 'liburEmpty';
    var emp = document.getElementById(emptyId);
    if (emp) emp.remove();

    var colorBg  = tipe === 'wfh' ? 'bg-indigo-50 border-indigo-100' : 'bg-amber-50 border-amber-100';
    var colorIc  = tipe === 'wfh' ? 'text-indigo-400' : 'text-amber-400';
    var colorTx  = tipe === 'wfh' ? 'text-indigo-700' : 'text-amber-700';
    var colorSub = tipe === 'wfh' ? 'text-indigo-400' : 'text-amber-400';
    var icon     = tipe === 'wfh' ? 'fa-house-laptop'  : 'fa-umbrella-beach';
    var label    = formatTglJS(tanggal);
    var ketHtml  = ket ? ' <span class="' + colorSub + ' text-xs ml-1">— ' + escHtmlAdmin(ket) + '</span>' : '';
    var tglEsc   = escHtmlAdmin(tanggal);

    var html = '<div class="' + tipe + '-item flex items-center gap-2 ' + colorBg + ' border rounded-xl px-3 py-2.5 text-sm" data-tanggal="' + tglEsc + '">' +
        '<i class="fas ' + icon + ' ' + colorIc + ' flex-shrink-0 text-xs"></i>' +
        '<div class="flex-1 min-w-0"><span class="font-semibold ' + colorTx + '">' + escHtmlAdmin(label) + '</span>' + ketHtml + '</div>' +
        '<button onclick="hapusJadwal(\'' + tipe + '\',\'' + tglEsc + '\')" class="flex-shrink-0 text-red-400 hover:text-red-600 transition p-1">' +
          '<i class="fas fa-trash text-xs"></i></button></div>';

    document.getElementById(tipe === 'wfh' ? 'listWfhCustom' : 'listLibur').insertAdjacentHTML('beforeend', html);
}

function formatTglJS(iso) {
    var d    = new Date(iso + 'T00:00:00');
    var hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][d.getDay()];
    var bln  = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][d.getMonth()];
    return hari + ', ' + d.getDate() + ' ' + bln + ' ' + d.getFullYear();
}

function toastJadwal(msg, type) {
    var el = document.getElementById('toastJadwal');
    el.className = 'rounded-xl px-4 py-3 text-sm font-medium text-center ' +
        (type === 'success'
            ? 'bg-green-100 text-green-700 border border-green-200'
            : 'bg-red-100 text-red-700 border border-red-200');
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(el._t);
    el._t = setTimeout(function() { el.classList.add('hidden'); }, 4000);
}
</script>

<?php endif; ?>
</body>
</html>

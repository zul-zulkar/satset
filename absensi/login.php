<?php
// Session bertahan 30 hari
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

include_once __DIR__ . '/../config.php';

if (!empty($_SESSION['absensi_auth'])) {
    header('Location: ' . APP_URL . '/absensi/index.php');
    exit;
}

include '../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $mysqli->prepare("SELECT id, nama, jabatan, password FROM pegawai WHERE username = ? LIMIT 1");
        if (!$stmt) {
            $error = 'Konfigurasi database belum lengkap. Hubungi admin.';
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $pegawai = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$pegawai) {
                $error = 'Username tidak ditemukan.';
            } elseif (!$pegawai['password'] || !password_verify($password, $pegawai['password'])) {
                $error = 'Password salah.';
            } else {
                session_regenerate_id(true);
                $_SESSION['absensi_auth']     = true;
                $_SESSION['pegawai_id']       = $pegawai['id'];
                $_SESSION['pegawai_nama']     = $pegawai['nama'];
                $_SESSION['pegawai_jabatan']  = $pegawai['jabatan'];
                header('Location: ' . APP_URL . '/absensi/index.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login · Absensi PST BPS Buleleng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  body { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #3b82f6 100%); }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
  <!-- Logo / Header -->
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/20 backdrop-blur mb-4">
      <i class="fas fa-clipboard-check text-white text-3xl"></i>
    </div>
    <h1 class="text-white text-2xl font-bold tracking-tight">Absensi Piket PST</h1>
    <p class="text-blue-200 text-sm mt-1">BPS Kabupaten Buleleng</p>
  </div>

  <!-- Card -->
  <div class="bg-white rounded-2xl shadow-2xl p-6">
    <?php if ($error): ?>
      <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
        <i class="fas fa-circle-exclamation flex-shrink-0"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <!-- Username -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
            <i class="fas fa-user text-sm"></i>
          </span>
          <input type="text" name="username" autocomplete="username" required
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 class="w-full pl-9 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                 placeholder="Masukkan username Anda">
        </div>
      </div>

      <!-- Password -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
            <i class="fas fa-lock text-sm"></i>
          </span>
          <input type="password" name="password" id="passInput" autocomplete="current-password" required
                 class="w-full pl-9 pr-10 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                 placeholder="Password">
          <button type="button" onclick="togglePass()"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition">
            <i id="passEye" class="fas fa-eye text-sm"></i>
          </button>
        </div>
        <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
          <i class="fas fa-circle-info"></i>
          Gunakan password default yang diberikan admin
        </p>
      </div>

      <button type="submit"
              class="w-full py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-md shadow-blue-200">
        <i class="fas fa-right-to-bracket"></i>
        Masuk
      </button>
    </form>
  </div>

  <p class="text-center text-blue-200 text-xs mt-6">
    <i class="fas fa-shield-halved mr-1"></i>Hanya untuk petugas piket PST
  </p>
</div>

<script>
function togglePass() {
    var inp = document.getElementById('passInput');
    var eye = document.getElementById('passEye');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    eye.className = inp.type === 'text' ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
}
</script>
</body>
</html>

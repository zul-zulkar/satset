<?php
/**
 * Kelola jadwal WFH custom dan libur nasional.
 * Hanya bisa diakses oleh admin yang sudah login.
 *
 * POST action  : add_wfh | remove_wfh | add_libur | remove_libur
 * POST tanggal : YYYY-MM-DD
 * POST keterangan : string (opsional, untuk add)
 *
 * Response: JSON { success, message }
 */
ob_start();
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();
ob_clean();

header('Content-Type: application/json');

if (empty($_SESSION['absensi_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi admin habis.']);
    exit;
}

$action      = trim($_POST['action']      ?? '');
$tanggal     = trim($_POST['tanggal']     ?? '');
$keterangan  = trim($_POST['keterangan']  ?? '');

$validActions = ['add_wfh', 'remove_wfh', 'add_libur', 'remove_libur'];
if (!in_array($action, $validActions)) {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid.']);
    exit;
}

$path   = __DIR__ . '/../config/jadwal.json';
$jadwal = file_exists($path)
        ? (json_decode(file_get_contents($path), true) ?? [])
        : [];

$jadwal['wfh_hari']    = $jadwal['wfh_hari']    ?? [5];
$jadwal['wfh_tanggal'] = $jadwal['wfh_tanggal'] ?? [];
$jadwal['libur']       = $jadwal['libur']        ?? [];

switch ($action) {

    case 'add_wfh':
        foreach ($jadwal['wfh_tanggal'] as $w) {
            if ($w['tanggal'] === $tanggal) {
                echo json_encode(['success' => false, 'message' => 'Tanggal sudah ada dalam daftar WFH.']);
                exit;
            }
        }
        $jadwal['wfh_tanggal'][] = ['tanggal' => $tanggal, 'keterangan' => $keterangan];
        usort($jadwal['wfh_tanggal'], fn($a, $b) => $a['tanggal'] <=> $b['tanggal']);
        break;

    case 'remove_wfh':
        $jadwal['wfh_tanggal'] = array_values(array_filter(
            $jadwal['wfh_tanggal'],
            fn($w) => $w['tanggal'] !== $tanggal
        ));
        break;

    case 'add_libur':
        if (empty($keterangan)) {
            echo json_encode(['success' => false, 'message' => 'Keterangan libur wajib diisi.']);
            exit;
        }
        foreach ($jadwal['libur'] as $l) {
            if ($l['tanggal'] === $tanggal) {
                echo json_encode(['success' => false, 'message' => 'Tanggal sudah ada dalam daftar libur.']);
                exit;
            }
        }
        $jadwal['libur'][] = ['tanggal' => $tanggal, 'keterangan' => $keterangan];
        usort($jadwal['libur'], fn($a, $b) => $a['tanggal'] <=> $b['tanggal']);
        break;

    case 'remove_libur':
        $jadwal['libur'] = array_values(array_filter(
            $jadwal['libur'],
            fn($l) => $l['tanggal'] !== $tanggal
        ));
        break;
}

$ok = file_put_contents($path, json_encode($jadwal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($ok === false) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan konfigurasi.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Berhasil disimpan.']);

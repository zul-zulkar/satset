<?php
/**
 * API Tests — HTTP Endpoint penghargaan/action/save.php
 *
 * Membutuhkan server HTTP yang berjalan (XAMPP / hosting).
 * URL endpoint diambil dari config.php (APP_URL) sehingga
 * test ini bisa dijalankan di dev maupun production.
 *
 * Jalankan: php tests/penghargaan/run.php api
 */
require_once __DIR__ . '/../_lib/T.php';

chdir(dirname(__DIR__, 2));
include_once 'app/config.php';

T::reset();

// ── Helper ────────────────────────────────────────────────────────────────────

/** POST multipart/form-data ke endpoint, kembalikan array decoded JSON. */
function postEndpoint(string $url, array $fields): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $fields,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, // dev environment
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['_curl_error' => $err, '_http_code' => 0];
    $decoded = json_decode($raw, true);
    if ($decoded === null) return ['_raw' => $raw, '_http_code' => $code, 'success' => false];
    $decoded['_http_code'] = $code;
    return $decoded;
}

/** GET ke URL, kembalikan array decoded JSON. */
function getEndpoint(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['_curl_error' => $err, '_http_code' => 0];
    $decoded = json_decode($raw, true);
    if ($decoded === null) return ['_raw' => $raw, '_http_code' => $code, 'success' => false];
    $decoded['_http_code'] = $code;
    return $decoded;
}

// Cek apakah cURL tersedia
if (!function_exists('curl_init')) {
    T::header('API Tests');
    T::skip('Semua API test', 'cURL tidak tersedia di PHP install ini');
    return T::summary();
}

$ENDPOINT = APP_URL . '/penghargaan/action/save.php';

// Cek server aktif terlebih dahulu
$ping = @file_get_contents(APP_URL . '/penghargaan/', false, stream_context_create(['http' => ['timeout' => 5]]));
if ($ping === false) {
    T::header('API Tests');
    T::skip('Semua API test', "Server tidak bisa dijangkau: " . APP_URL);
    return T::summary();
}

$testPid   = 99999;
$testBulan = 1;
$testTahun = 2099;

// ── 1. Method tidak diizinkan ────────────────────────────────────────────────
T::header('Method Validation');

$res = getEndpoint($ENDPOINT);
T::ok('GET → success=false',  ($res['success'] ?? true) === false);
T::ok('GET → ada message',    isset($res['message']));

// ── 2. tipe=kinerja — valid ──────────────────────────────────────────────────
T::header('tipe=kinerja — Valid');

$res = postEndpoint($ENDPOINT, [
    'tipe'       => 'kinerja',
    'pegawai_id' => $testPid,
    'bulan'      => $testBulan,
    'tahun'      => $testTahun,
    'nilai'      => 85,
]);
T::ok('Simpan kinerja valid → success=true',     ($res['success'] ?? false) === true);
T::ok('HTTP 200',                                ($res['_http_code'] ?? 0) === 200);

// Upsert — nilai berbeda
$res2 = postEndpoint($ENDPOINT, [
    'tipe'       => 'kinerja',
    'pegawai_id' => $testPid,
    'bulan'      => $testBulan,
    'tahun'      => $testTahun,
    'nilai'      => 90,
]);
T::ok('Upsert kinerja (nilai beda) → success=true', ($res2['success'] ?? false) === true);

// ── 3. tipe=kinerja — nilai tidak valid ──────────────────────────────────────
T::header('tipe=kinerja — Validasi Nilai');

$cases = [
    ['nilai' => 0,   'label' => 'nilai=0'],
    ['nilai' => 101, 'label' => 'nilai=101'],
    ['nilai' => -1,  'label' => 'nilai=-1'],
    ['nilai' => '',  'label' => 'nilai kosong'],
];
foreach ($cases as $c) {
    $r = postEndpoint($ENDPOINT, [
        'tipe' => 'kinerja', 'pegawai_id' => $testPid,
        'bulan' => $testBulan, 'tahun' => $testTahun, 'nilai' => $c['nilai'],
    ]);
    T::ok($c['label'] . ' → success=false', ($r['success'] ?? true) === false);
}

// ── 4. tipe=kinerja — data tidak lengkap ────────────────────────────────────
T::header('tipe=kinerja — Data Tidak Lengkap');

$r = postEndpoint($ENDPOINT, ['tipe' => 'kinerja', 'nilai' => 80]);
T::ok('Tanpa pegawai_id → success=false', ($r['success'] ?? true) === false);

$r = postEndpoint($ENDPOINT, ['tipe' => 'kinerja', 'pegawai_id' => $testPid, 'nilai' => 80]);
T::ok('Tanpa bulan+tahun → success=false', ($r['success'] ?? true) === false);

$r = postEndpoint($ENDPOINT, [
    'tipe' => 'kinerja', 'pegawai_id' => $testPid,
    'bulan' => 0, 'tahun' => $testTahun, 'nilai' => 80,
]);
T::ok('bulan=0 → success=false', ($r['success'] ?? true) === false);

// ── 5. tipe=tim_penilai — valid ──────────────────────────────────────────────
T::header('tipe=tim_penilai — Valid');

$validPenilai = ['iwansantika', 'madekariasa', 'paseksusena'];
foreach ($validPenilai as $np) {
    $r = postEndpoint($ENDPOINT, [
        'tipe'            => 'tim_penilai',
        'pegawai_id'      => $testPid,
        'bulan'           => $testBulan,
        'tahun'           => $testTahun,
        'nama_penilai'    => $np,
        'nilai_kerja_sama' => 80,
        'nilai_inovatif'  => 85,
        'nilai_penampilan' => 90,
    ]);
    T::ok("Simpan tim_penilai ({$np}) → success=true", ($r['success'] ?? false) === true);
}

// Upsert
$r = postEndpoint($ENDPOINT, [
    'tipe'            => 'tim_penilai',
    'pegawai_id'      => $testPid,
    'bulan'           => $testBulan,
    'tahun'           => $testTahun,
    'nama_penilai'    => 'iwansantika',
    'nilai_kerja_sama' => 95,
    'nilai_inovatif'  => 95,
    'nilai_penampilan' => 95,
]);
T::ok('Upsert tim_penilai → success=true', ($r['success'] ?? false) === true);

// ── 6. tipe=tim_penilai — penilai tidak valid ────────────────────────────────
T::header('tipe=tim_penilai — Penilai Tidak Valid');

$invalidPenilai = ['admin', 'root', '', 'IWANSANTIKA', 'iwansantika; DROP TABLE'];
foreach ($invalidPenilai as $np) {
    $r = postEndpoint($ENDPOINT, [
        'tipe'            => 'tim_penilai',
        'pegawai_id'      => $testPid,
        'bulan'           => $testBulan,
        'tahun'           => $testTahun,
        'nama_penilai'    => $np,
        'nilai_kerja_sama' => 80,
        'nilai_inovatif'  => 80,
        'nilai_penampilan' => 80,
    ]);
    T::ok("Penilai tidak valid '" . ($np ?: '(kosong)') . "' → success=false", ($r['success'] ?? true) === false);
}

// ── 7. tipe=tim_penilai — nilai tidak valid ──────────────────────────────────
T::header('tipe=tim_penilai — Validasi Nilai');

$invalidVals = [0, 101, -1];
foreach ($invalidVals as $bad) {
    $r = postEndpoint($ENDPOINT, [
        'tipe'            => 'tim_penilai',
        'pegawai_id'      => $testPid,
        'bulan'           => $testBulan,
        'tahun'           => $testTahun,
        'nama_penilai'    => 'iwansantika',
        'nilai_kerja_sama' => $bad,
        'nilai_inovatif'  => 80,
        'nilai_penampilan' => 80,
    ]);
    T::ok("nilai_kerja_sama={$bad} → success=false", ($r['success'] ?? true) === false);
}

// ── 8. tipe tidak dikenal ────────────────────────────────────────────────────
T::header('Tipe Tidak Dikenal');

$r = postEndpoint($ENDPOINT, [
    'tipe'       => 'hapus',
    'pegawai_id' => $testPid,
    'bulan'      => $testBulan,
    'tahun'      => $testTahun,
]);
T::ok("tipe='hapus' → success=false", ($r['success'] ?? true) === false);

$r = postEndpoint($ENDPOINT, [
    'tipe'       => '',
    'pegawai_id' => $testPid,
    'bulan'      => $testBulan,
    'tahun'      => $testTahun,
]);
T::ok("tipe='' → success=false", ($r['success'] ?? true) === false);

// ── 9. Halaman utama dapat diakses ──────────────────────────────────────────
T::header('Halaman Penghargaan (HTTP)');

$pages = [
    ['url' => APP_URL . '/penghargaan/',                              'label' => 'Tab default (peringkat)'],
    ['url' => APP_URL . '/penghargaan/?tab=kinerja',                  'label' => 'Tab kinerja'],
    ['url' => APP_URL . '/penghargaan/?tab=tim',                      'label' => 'Tab tim'],
    ['url' => APP_URL . '/penghargaan/?tab=tim&penilai=iwansantika',  'label' => 'Tab tim + penilai pre-selected'],
    ['url' => APP_URL . '/penghargaan/?bulan=5&tahun=2025',           'label' => 'Filter periode bulan/tahun'],
];
foreach ($pages as $p) {
    $ch = curl_init($p['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    T::ok($p['label'] . " → HTTP 200", $code === 200);
}

// ── 10. Delete endpoint ──────────────────────────────────────────────────────
T::header('Delete Endpoint');

$DEL = APP_URL . '/penghargaan/action/delete.php';

// GET → ditolak
$r = getEndpoint($DEL);
T::ok('GET delete → success=false', ($r['success'] ?? true) === false);

// Hapus kinerja yang sudah disimpan di tes sebelumnya
$r = postEndpoint($DEL, [
    'tipe' => 'kinerja', 'pegawai_id' => $testPid,
    'bulan' => $testBulan, 'tahun' => $testTahun,
]);
T::ok('Hapus kinerja valid → success=true', ($r['success'] ?? false) === true);

// Hapus lagi (tidak ada baris) → tetap success (DELETE 0 rows bukan error)
$r = postEndpoint($DEL, [
    'tipe' => 'kinerja', 'pegawai_id' => $testPid,
    'bulan' => $testBulan, 'tahun' => $testTahun,
]);
T::ok('Hapus kinerja tidak ada → success=true', ($r['success'] ?? false) === true);

// Hapus tim penilai
$r = postEndpoint($DEL, [
    'tipe' => 'tim_penilai', 'pegawai_id' => $testPid,
    'bulan' => $testBulan, 'tahun' => $testTahun, 'nama_penilai' => 'iwansantika',
]);
T::ok('Hapus tim_penilai valid → success=true', ($r['success'] ?? false) === true);

// Penilai tidak valid → ditolak
$r = postEndpoint($DEL, [
    'tipe' => 'tim_penilai', 'pegawai_id' => $testPid,
    'bulan' => $testBulan, 'tahun' => $testTahun, 'nama_penilai' => 'admin',
]);
T::ok('Hapus penilai tidak valid → success=false', ($r['success'] ?? true) === false);

// Tipe tidak dikenal
$r = postEndpoint($DEL, [
    'tipe' => 'semua', 'pegawai_id' => $testPid,
    'bulan' => $testBulan, 'tahun' => $testTahun,
]);
T::ok("tipe='semua' → success=false", ($r['success'] ?? true) === false);

// Data tidak lengkap
$r = postEndpoint($DEL, ['tipe' => 'kinerja']);
T::ok('Tanpa pegawai_id → success=false', ($r['success'] ?? true) === false);

// ── Cleanup sisa data test ───────────────────────────────────────────────────
include_once 'app/config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$db->connect_error) {
    $db->query("DELETE FROM penghargaan_penilaian WHERE pegawai_id=$testPid AND tahun=$testTahun");
    $db->query("DELETE FROM penghargaan_tim_penilai WHERE pegawai_id=$testPid AND tahun=$testTahun");
    $db->close();
}

// ── Hasil ─────────────────────────────────────────────────────────────────────
echo "\n";
$s = T::summary();
$col = $s['fail'] > 0 ? "\e[31m" : "\e[32m";
echo "{$col}  api: {$s['pass']} passed, {$s['fail']} failed, {$s['skip']} skipped\e[0m\n";
return $s;

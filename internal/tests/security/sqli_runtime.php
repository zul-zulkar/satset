<?php
/**
 * Runtime SQLi regression test untuk cs/antrean_atur.php.
 *
 * Sebelum fix: $_POST['jenis'] diinterpolasi langsung ke SQL → SQLi exploitable.
 * Setelah fix: whitelist + prepared statement → payload diabaikan, halaman 200.
 *
 * Test ini skip jika server tidak bisa dijangkau (mirror perilaku api.php).
 */
T::header('Runtime — SQLi guard di cs/antrean_atur.php');

$base = 'http://localhost/satset';

/** Cek server reachable dulu. */
$ch = curl_init($base . '/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY         => true,
    CURLOPT_TIMEOUT        => 3,
    CURLOPT_SSL_VERIFYPEER => false,
]);
curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    T::skip('Runtime SQLi tests (Server tidak bisa dijangkau: ' . $base . ')');
    return;
}

/** Helper POST ke endpoint, return ['code' => int, 'body' => string]. */
function postJSON(string $url, array $fields): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => (int)$code, 'body' => (string)$body];
}

$url = $base . '/cs/antrean_atur.php';

// 1. Payload klasik SQLi — sebelum fix bisa menyebabkan SQL error / data anomaly.
$r1 = postJSON($url, ['jenis' => "' OR 1=1 --", 'aksi' => 'next']);
T::ok('SQLi payload OR 1=1 → halaman tetap 200 (HTTP=' . $r1['code'] . ')', $r1['code'] === 200);

// 2. Payload union-based.
$r2 = postJSON($url, ['jenis' => "umum' UNION SELECT 1,2,3,4,5,6,7,8,9,10 --", 'aksi' => 'next']);
T::ok('SQLi UNION payload → halaman tetap 200 (HTTP=' . $r2['code'] . ')', $r2['code'] === 200);

// 3. Aksi invalid — harus diabaikan, tidak crash.
$r3 = postJSON($url, ['jenis' => 'disabilitas', 'aksi' => 'drop_table']);
T::ok('Aksi invalid diabaikan → 200 (HTTP=' . $r3['code'] . ')', $r3['code'] === 200);

// 4. Jenis valid + aksi valid → 200 (sanity check, tidak memverifikasi state DB karena destructive).
$r4 = postJSON($url, ['jenis' => 'umum', 'aksi' => 'next']);
T::ok('Request valid → 200 (HTTP=' . $r4['code'] . ')', $r4['code'] === 200);

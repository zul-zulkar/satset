<?php
/**
 * Runtime tests untuk endpoint modul cs/*.
 * Skip jika server tidak bisa dijangkau (pola sama dengan security/sqli_runtime).
 */
T::header('Runtime — Endpoint CS');

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
    T::skip('Runtime CS tests (Server tidak bisa dijangkau: ' . $base . ')');
    return;
}

/** Helper GET → ['code' => int, 'body' => string]. */
function csGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => (int)$code, 'body' => (string)$body];
}

// ── 1. antrean_atur.php — GET sanity check ─────────────────────────────────
$r = csGet($base . '/cs/antrean_atur.php');
T::ok('GET antrean_atur.php → 200 (HTTP=' . $r['code'] . ')', $r['code'] === 200);
T::ok('antrean_atur.php berisi label "Halaman Petugas CS"', str_contains($r['body'], 'Halaman Petugas CS'));
T::ok('antrean_atur.php render kolom Disabilitas + Umum', str_contains($r['body'], 'Antrean Disabilitas') && str_contains($r['body'], 'Antrean Umum'));

// ── 2. antrean_sekarang.php — GET dengan jenis ────────────────────────────
$r = csGet($base . '/cs/antrean_sekarang.php?jenis=umum');
T::ok('GET antrean_sekarang.php?jenis=umum → 200', $r['code'] === 200);
T::ok(
    'antrean_sekarang.php response berformat "JENIS-NOMOR" atau placeholder',
    preg_match('/^(UMUM-\d+|DISABILITAS-\d+|Belum ada antrean dipanggil)/', trim($r['body'])) === 1
);

$r = csGet($base . '/cs/antrean_sekarang.php?jenis=disabilitas');
T::ok('GET antrean_sekarang.php?jenis=disabilitas → 200', $r['code'] === 200);

// ── 3. pengunjung_hari_ini.php — JSON endpoint ────────────────────────────
$r = csGet($base . '/cs/pengunjung_hari_ini.php');
T::ok('GET pengunjung_hari_ini.php → 200', $r['code'] === 200);

$decoded = json_decode($r['body'], true);
T::ok('pengunjung_hari_ini.php return JSON array yang valid', is_array($decoded));

if (is_array($decoded) && count($decoded) > 0) {
    $first = $decoded[0];
    $expectedKeys = ['id', 'nomor', 'jenis', 'nama', 'telepon', 'instansi', 'status'];
    $hasAllKeys = true;
    foreach ($expectedKeys as $k) {
        if (!array_key_exists($k, $first)) { $hasAllKeys = false; break; }
    }
    T::ok('Row pengunjung punya semua kolom wajib (id, nomor, jenis, ...)', $hasAllKeys);
} else {
    T::skip('Struktur row pengunjung (tabel antrian kosong hari ini)');
}

// ── 4. daftar_pengguna.php — halaman daftar pegawai ───────────────────────
$r = csGet($base . '/cs/daftar_pengguna.php');
T::ok('GET daftar_pengguna.php → 200', $r['code'] === 200);

// ── 5. detail_pengunjung.php — tanpa token harus 400 ──────────────────────
$r = csGet($base . '/cs/detail_pengunjung.php');
T::ok('GET detail_pengunjung.php tanpa token → 400 (HTTP=' . $r['code'] . ')', $r['code'] === 400);

// Token random valid format tapi tidak ada → 404
$r = csGet($base . '/cs/detail_pengunjung.php?token=' . str_repeat('a', 32));
T::ok('GET detail_pengunjung.php token tidak ada → 404 (HTTP=' . $r['code'] . ')', $r['code'] === 404);

// Token mengandung karakter aneh tetap di-sanitize → 400 setelah strip
$r = csGet($base . '/cs/detail_pengunjung.php?token=' . urlencode("'; DROP TABLE--"));
T::ok('GET detail_pengunjung.php payload SQLi → 400 atau 404 (sanitized)', in_array($r['code'], [400, 404], true));

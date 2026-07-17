<?php
/**
 * Runtime Tests — auth guard di endpoint absensi/action/*.
 * Verifikasi bahwa endpoint sensitif menolak request tanpa session
 * dengan response JSON { success: false, message: ... }.
 *
 * Skip jika server tidak bisa dijangkau.
 */
T::header('Runtime — Auth Guard di Absensi Endpoints');

$base = 'http://localhost/satset';

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
    T::skip('Runtime absensi tests (Server tidak bisa dijangkau: ' . $base . ')');
    return;
}

function absPost(string $url, array $fields, array $cookies = []): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if ($cookies) {
        $opts[CURLOPT_COOKIE] = implode('; ', array_map(
            fn($k, $v) => "$k=$v",
            array_keys($cookies), array_values($cookies)
        ));
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => (int)$code, 'body' => (string)$body];
}

function absGet(string $url): array {
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

// ── 1. absen.php — wajib session pegawai ──────────────────────────────────
$r = absPost($base . '/absensi/action/absen.php', [
    'tipe' => 'masuk', 'lat' => -8.11, 'lng' => 115.09
]);
T::ok('POST absen.php tanpa session → 401 (HTTP=' . $r['code'] . ')', $r['code'] === 401);
$d = json_decode($r['body'], true);
T::ok('absen.php response success=false', is_array($d) && ($d['success'] ?? null) === false);
T::ok('absen.php response berisi message tentang sesi',
    is_array($d) && str_contains($d['message'] ?? '', 'Sesi'));

// ── 2. ganti_password.php — wajib session pegawai ─────────────────────────
$r = absPost($base . '/absensi/action/ganti_password.php', [
    'password_lama' => 'x', 'password_baru' => 'newpass1'
]);
$d = json_decode($r['body'], true);
T::ok('POST ganti_password.php tanpa session → success=false',
    is_array($d) && ($d['success'] ?? null) === false);
T::ok('ganti_password.php message tentang sesi tidak valid',
    is_array($d) && stripos($d['message'] ?? '', 'sesi') !== false);

// ── 3. rekap_pribadi.php — wajib session pegawai ──────────────────────────
$r = absGet($base . '/absensi/action/rekap_pribadi.php');
$d = json_decode($r['body'], true);
T::ok('GET rekap_pribadi.php tanpa session → JSON error=Unauthorized',
    is_array($d) && ($d['error'] ?? null) === 'Unauthorized');

// ── 4. rekap_admin.php — wajib session admin ──────────────────────────────
$r = absGet($base . '/absensi/action/rekap_admin.php');
$d = json_decode($r['body'], true);
T::ok('GET rekap_admin.php tanpa admin session → JSON error=Unauthorized',
    is_array($d) && ($d['error'] ?? null) === 'Unauthorized');

// ── 5. save_config.php — wajib session admin ──────────────────────────────
$r = absPost($base . '/absensi/action/save_config.php', [
    'lat' => -8.11, 'lng' => 115.09, 'radius' => 100
]);
T::ok('POST save_config.php tanpa admin session → 401 (HTTP=' . $r['code'] . ')',
    $r['code'] === 401);
$d = json_decode($r['body'], true);
T::ok('save_config.php success=false', is_array($d) && ($d['success'] ?? null) === false);

// ── 6. save_jadwal.php — wajib session admin ──────────────────────────────
$r = absPost($base . '/absensi/action/save_jadwal.php', [
    'action' => 'add_wfh', 'tanggal' => '2026-06-10'
]);
T::ok('POST save_jadwal.php tanpa admin session → 401',
    $r['code'] === 401);

// ── 7. reset_password.php — wajib session admin ───────────────────────────
$r = absPost($base . '/absensi/action/reset_password.php', [
    'pegawai_id' => 1, 'password_baru' => 'newpass1'
]);
$d = json_decode($r['body'], true);
T::ok('POST reset_password.php tanpa admin session → success=false',
    is_array($d) && ($d['success'] ?? null) === false);

// ── 8. Halaman publik: login.php → 200 ────────────────────────────────────
$r = absGet($base . '/absensi/login.php');
T::ok('GET absensi/login.php → 200', $r['code'] === 200);
T::ok('login.php render form login dgn field username & password',
    str_contains($r['body'], 'name="username"') &&
    str_contains($r['body'], 'name="password"'));

// ── 9. POST login.php dgn credential salah → tetap di login + error ───────
$r = absPost($base . '/absensi/login.php', [
    'username' => 'user_yang_tidak_ada_xyz',
    'password' => 'salah',
]);
T::ok('POST login.php credential invalid → 200 + halaman login dgn error',
    $r['code'] === 200 && (
        str_contains($r['body'], 'Username tidak ditemukan') ||
        str_contains($r['body'], 'Password salah')
    ));

<?php
/**
 * Smoke Tests — Verifikasi semua route utama HTTP 200 (atau 302 untuk redirect).
 *
 * Memerlukan: server XAMPP berjalan + DB terhubung.
 * Tidak menyentuh data — hanya GET.
 *
 * Jalankan: php tests/smoke/run.php
 */
require_once __DIR__ . '/../_lib/T.php';

chdir(dirname(__DIR__, 2));
include_once 'config.php';

T::reset();
T::header('Smoke Tests — Route Reachability');

/** Daftar route + HTTP code yang dianggap valid. */
$routes = [
    '/'                              => [200],            // root index / QR display
    '/menu'                          => [200, 301],     // Apache redirect ke /menu/
    '/menu/'                         => [200],
    '/menu.php'                      => [200],          // entry point root (legacy)
    '/disabilitas/'                  => [200],
    '/umum/'                         => [200],
    '/cs/daftar_pengguna.php'        => [200],
    '/cs/antrean_atur.php'           => [200],
    '/cs/antrean_sekarang.php'       => [200],
    '/absensi/'                      => [200, 302],       // mungkin redirect ke login
    '/absensi/login.php'             => [200],
    '/laporan/minggu.php'            => [200],
    '/laporan/bulan.php'             => [200],
    '/penghargaan/'                  => [200],
    '/penilaian/'                    => [200],
    '/pes/'                          => [200],
    '/monitor/'                      => [200],
    '/surat/'                        => [200],
    '/whatsapp/'                     => [200],
    '/form/buku_tamu.php'            => [200],
];

// Ambil base URL — pakai localhost saat run dari mesin XAMPP supaya tidak tergantung LAN IP
$base = 'http://localhost/satset';

foreach ($routes as $path => $okCodes) {
    $url = $base . $path;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY         => true,         // HEAD-like, jangan transfer body
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_FOLLOWLOCATION => false,        // jangan ikuti redirect supaya bisa cek code asli
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        T::ok("GET {$path} (err: {$err})", false);
        continue;
    }
    $ok = in_array((int)$code, $okCodes, true);
    T::ok("GET {$path} → {$code}", $ok);
}

echo "\n\e[32m  smoke: " . T::$pass . " passed, " . T::$fail . " failed, " . T::$skip . " skipped\e[0m\n";
return T::summary();

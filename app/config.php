<?php
// =====================================================================
//  KONFIGURASI APLIKASI — Sistem Antrean BPS Kabupaten Buleleng
//
//  Untuk berpindah lingkungan, cukup ubah nilai ENV di bawah.
//  Semua URL dan koneksi database akan menyesuaikan otomatis.
// =====================================================================

// Pilih lingkungan aktif: 'local' atau 'production'
define('ENV', 'local');
// define('ENV', 'production');  // ← ganti ke 'production' saat deploy ke hosting

// ── URL ──────────────────────────────────────────────────────────────
//
//  APP_URL  : URL lengkap aplikasi (tanpa trailing slash)
//  APP_BASE : hanya bagian path-nya (tanpa trailing slash)
//             → kosongkan jika aplikasi berada di root domain
//
//  Contoh lokal   : APP_URL = 'http://192.168.2.54/satset'
//                   APP_BASE = '/satset'
//
//  Contoh hosting : APP_URL = 'https://satset.wuaze.com'
//                   APP_BASE = '' (kosong)
//
$_urlConf = [
    'local' => [
        'url'  => 'http://192.168.2.54/satset',   // ← sesuaikan IP/hostname lokal
        'base' => '/satset',
    ],
    'production' => [
        'url'  => 'https://satset.statsbali.id',
        'base' => '',                              // app di root domain → kosong
    ],
];

define('APP_URL',  $_urlConf[ENV]['url']);
define('APP_BASE', $_urlConf[ENV]['base']);

// ── DATABASE ─────────────────────────────────────────────────────────
$_dbConf = [
    'local' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'db_5108_satset',
    ],
    'production' => [
        'host' => 'localhost',        // ← ganti sesuai hosting
        'user' => 'satset',                   // ← ganti sesuai hosting
        'pass' => 'CYqyG0fsGNanY87vO9xb',                    // ← ganti sesuai hosting
        'name' => 'dbsatset',    // ← ganti jika nama DB berbeda
    ],
    // InfinityFree (lama — sudah tidak dipakai):
    // 'production' => [
    //     'host' => 'sql213.infinityfree.com',
    //     'user' => 'if0_41029675',
    //     'pass' => 'Singorojo08',
    //     'name' => 'if0_41029675_db_5108_satset',
    // ],
];

$_c = $_dbConf[ENV];
define('DB_HOST', $_c['host']);
define('DB_USER', $_c['user']);
define('DB_PASS', $_c['pass']);
define('DB_NAME', $_c['name']);

unset($_urlConf, $_dbConf, $_c);

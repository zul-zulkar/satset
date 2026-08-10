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
//             → samakan dengan nama folder aplikasi di hosting
//
//  Aplikasi ini hidup di sub-path agar satu domain bisa menampung banyak
//  aplikasi:  satset.statsbali.id/pst , satset.statsbali.id/<app-lain> , dst.
//  Nama folder aplikasi di hosting HARUS sama dengan 'base' di bawah.
//  (lihat internal/DEPLOY-CPANEL.md). Untuk pindah path, cukup ubah 'base' di sini.
//
//  Catatan: hosting memakai nginx (tanpa .htaccess/mod_rewrite), sehingga
//  struktur folder = struktur URL. Folder aplikasi langsung berisi index.php,
//  dan kode privat ada di subfolder app/.
//
//  Contoh lokal   : APP_URL = 'http://192.168.2.54/satset'
//                   APP_BASE = '/satset'
//
//  Contoh hosting : APP_URL = 'https://satset.statsbali.id/pst'
//                   APP_BASE = '/pst'
//
$_urlConf = [
    'local' => [
        'url'  => 'http://192.168.2.54/satset',   // ← sesuaikan IP/hostname lokal
        'base' => '/satset',
    ],
    'production' => [
        'url'  => 'https://satset.statsbali.id/pst',
        'base' => '/pst',                          // ← nama folder aplikasi di hosting
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

// ── WHATSAPP / FONNTE ────────────────────────────────────────────────
//
//  Integrasi WhatsApp memakai gateway Fonnte (https://fonnte.com) paket
//  gratis: nomor WA PST dihubungkan lewat scan QR di dashboard Fonnte.
//  Token/secret hanya boleh berada di file .php (di produksi nginx, file
//  non-PHP bisa diunduh publik).
//
//  token       : token API device dari dashboard Fonnte.
//                KOSONGKAN ('') = MODE SIMULASI — tidak ada HTTP call ke
//                Fonnte; pesan keluar hanya dicatat di tabel wa_log dengan
//                status 'simulasi'. Dipakai saat pengembangan/test lokal.
//  secret      : rahasia webhook. URL webhook di dashboard Fonnte HARUS
//                memakai ?key=<secret>:
//                https://satset.statsbali.id/pst/wa-webhook/?key=<secret>
//  fallback_hp : nomor tujuan notifikasi bila tidak ada petugas piket
//                (atau tak ada no_hp valid) pada minggu berjalan.
//  device      : nomor WA PST yang terhubung ke Fonnte (format 628xx),
//                dipakai mengabaikan pesan dari diri sendiri (anti-loop).
//
$_waConf = [
    'local' => [
        'token'       => '',
        'secret'      => 'dev-secret-lokal',
        'fallback_hp' => '081234567890',
        'device'      => '6281234567890',
    ],
    'production' => [
        'token'       => 'nsNEqvqR9tzWN2otWRKkfsszkNgdVMTXbh',                    // ← isi token Fonnte saat go-live
        'secret'      => 'e5872c63c996f7772b1880b0157a2a29',
        'fallback_hp' => '6285155001904',                    // ← isi nomor koordinator PST
        'device'      => '6285111325108',                    // ← isi nomor WA PST (628xx)
    ],
];
define('FONNTE_TOKEN',      $_waConf[ENV]['token']);
define('WA_WEBHOOK_SECRET', $_waConf[ENV]['secret']);
define('WA_FALLBACK_HP',    $_waConf[ENV]['fallback_hp']);
define('WA_DEVICE',         $_waConf[ENV]['device']);

unset($_urlConf, $_dbConf, $_c, $_waConf);

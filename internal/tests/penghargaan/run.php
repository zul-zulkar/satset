#!/usr/bin/env php
<?php
/**
 * Test Runner — Fitur Penghargaan PST Terbaik
 *
 * Usage:
 *   php internal/tests/penghargaan/run.php          # jalankan semua suite
 *   php internal/tests/penghargaan/run.php unit     # hanya unit test
 *   php internal/tests/penghargaan/run.php schema   # hanya schema/DB test
 *   php internal/tests/penghargaan/run.php api      # hanya HTTP endpoint test
 *
 * Keluar dengan exit code 1 jika ada test yang gagal (cocok untuk CI).
 */

chdir(dirname(__DIR__, 3)); // Set ke root project (c:\xampp\htdocs\satset)

$suite  = $argv[1] ?? 'all';
$suites = ['unit', 'schema', 'api'];
$toRun  = $suite === 'all' ? $suites : (in_array($suite, $suites) ? [$suite] : null);

if ($toRun === null) {
    echo "\e[31mSuite tidak dikenal: {$suite}\e[0m\n";
    echo "Suite yang tersedia: " . implode(', ', $suites) . ", all\n";
    exit(1);
}

// Header
echo "\n";
echo "\e[1m\e[34m╔══════════════════════════════════════════════════╗\e[0m\n";
echo "\e[1m\e[34m║   Test Suite: Penghargaan PST Terbaik            ║\e[0m\n";
echo "\e[1m\e[34m╚══════════════════════════════════════════════════╝\e[0m\n";

// Tampilkan info environment
include_once 'app/config.php';
echo "\e[90m  ENV: " . ENV . " | DB: " . DB_NAME . "@" . DB_HOST . " | URL: " . APP_URL . "\e[0m\n";
echo "\e[90m  PHP: " . PHP_VERSION . " | Waktu: " . date('Y-m-d H:i:s') . "\e[0m\n";

// Jalankan setiap suite
$totalPass = 0;
$totalFail = 0;
$totalSkip = 0;

foreach ($toRun as $s) {
    $result = include __DIR__ . "/{$s}.php";
    if (!is_array($result)) {
        echo "\e[31m  [!] Suite '{$s}' tidak mengembalikan array hasil\e[0m\n";
        $totalFail++;
        continue;
    }
    $totalPass += $result['pass'] ?? 0;
    $totalFail += $result['fail'] ?? 0;
    $totalSkip += $result['skip'] ?? 0;
}

// Ringkasan akhir
echo "\n";
echo str_repeat('─', 52) . "\n";
$color = $totalFail > 0 ? "\e[1m\e[31m" : "\e[1m\e[32m";
$icon  = $totalFail > 0 ? "GAGAL" : "LULUS";
echo "{$color}{$icon}: {$totalPass} passed · {$totalFail} failed · {$totalSkip} skipped\e[0m\n";
echo str_repeat('─', 52) . "\n\n";

exit($totalFail > 0 ? 1 : 0);

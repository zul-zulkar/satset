<?php
/**
 * Security Test Runner — static scan + SQLi regression.
 * Jalankan: php tests/security/run.php
 */
require_once __DIR__ . '/../_lib/T.php';

echo "\n\e[1m\e[34m╔══════════════════════════════════════════════════╗\e[0m\n";
echo "\e[1m\e[34m║   Security Tests — SQLi & XSS Regression Guards  ║\e[0m\n";
echo "\e[1m\e[34m╚══════════════════════════════════════════════════╝\e[0m\n";

T::reset();

include __DIR__ . '/static_scan.php';
include __DIR__ . '/sqli_runtime.php';

echo "\n" . str_repeat('─', 52) . "\n";
$color = T::$fail > 0 ? "\e[1m\e[31m" : "\e[1m\e[32m";
$icon  = T::$fail > 0 ? "GAGAL" : "LULUS";
echo "{$color}{$icon}: " . T::$pass . " passed · " . T::$fail . " failed · " . T::$skip . " skipped\e[0m\n";
echo str_repeat('─', 52) . "\n\n";

exit(T::$fail > 0 ? 1 : 0);

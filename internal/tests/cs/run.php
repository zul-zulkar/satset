<?php
/**
 * CS Test Runner — runtime tests untuk endpoint cs/*.php.
 * Jalankan: php internal/tests/cs/run.php
 */
require_once __DIR__ . '/../_lib/T.php';

echo "\n\e[1m\e[34m╔══════════════════════════════════════════════════╗\e[0m\n";
echo "\e[1m\e[34m║   CS Tests — Endpoint Antrean & Pengunjung       ║\e[0m\n";
echo "\e[1m\e[34m╚══════════════════════════════════════════════════╝\e[0m\n";

T::reset();

include __DIR__ . '/runtime.php';

echo "\n" . str_repeat('─', 52) . "\n";
$color = T::$fail > 0 ? "\e[1m\e[31m" : "\e[1m\e[32m";
$icon  = T::$fail > 0 ? "GAGAL" : "LULUS";
echo "{$color}{$icon}: " . T::$pass . " passed · " . T::$fail . " failed · " . T::$skip . " skipped\e[0m\n";
echo str_repeat('─', 52) . "\n\n";

exit(T::$fail > 0 ? 1 : 0);

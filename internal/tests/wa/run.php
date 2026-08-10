<?php
/**
 * WA Test Runner — unit (helper wa.php) + runtime (webhook & piket actions).
 * Jalankan: php internal/tests/wa/run.php
 */
require_once __DIR__ . '/../_lib/T.php';

echo "\n\e[1m\e[34m╔══════════════════════════════════════════════════╗\e[0m\n";
echo "\e[1m\e[34m║   WA Tests — Integrasi WhatsApp (Fonnte)         ║\e[0m\n";
echo "\e[1m\e[34m╚══════════════════════════════════════════════════╝\e[0m\n";

T::reset();

include __DIR__ . '/unit.php';
include __DIR__ . '/runtime.php';

echo "\n" . str_repeat('─', 52) . "\n";
$color = T::$fail > 0 ? "\e[1m\e[31m" : "\e[1m\e[32m";
$icon  = T::$fail > 0 ? "GAGAL" : "LULUS";
echo "{$color}{$icon}: " . T::$pass . " passed · " . T::$fail . " failed · " . T::$skip . " skipped\e[0m\n";
echo str_repeat('─', 52) . "\n\n";

exit(T::$fail > 0 ? 1 : 0);

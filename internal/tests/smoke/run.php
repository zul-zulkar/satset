<?php
/**
 * Smoke Test Runner.
 * Jalankan: php internal/tests/smoke/run.php
 */
require_once __DIR__ . '/../_lib/T.php';

echo "\n\e[1m\e[34m╔══════════════════════════════════════════════════╗\e[0m\n";
echo "\e[1m\e[34m║   Smoke Tests — Route Reachability               ║\e[0m\n";
echo "\e[1m\e[34m╚══════════════════════════════════════════════════╝\e[0m\n";

$result = include __DIR__ . '/routes.php';
if (!is_array($result)) {
    echo "\e[31m  [!] routes.php tidak return array\e[0m\n";
    exit(1);
}

$fail = (int)($result['fail'] ?? 0);
$pass = (int)($result['pass'] ?? 0);
$skip = (int)($result['skip'] ?? 0);

echo "\n" . str_repeat('─', 52) . "\n";
$color = $fail > 0 ? "\e[1m\e[31m" : "\e[1m\e[32m";
$icon  = $fail > 0 ? "GAGAL" : "LULUS";
echo "{$color}{$icon}: {$pass} passed · {$fail} failed · {$skip} skipped\e[0m\n";
echo str_repeat('─', 52) . "\n\n";

exit($fail > 0 ? 1 : 0);

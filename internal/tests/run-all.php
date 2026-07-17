<?php
/**
 * Top-level Test Runner — semua suite di project.
 *
 * Cara pakai:
 *   php internal/tests/run-all.php              # semua suite
 *   php internal/tests/run-all.php smoke        # hanya smoke
 *   php internal/tests/run-all.php penghargaan  # hanya penghargaan
 *
 * Exit 1 jika ada test gagal (cocok untuk CI/hook).
 */

chdir(dirname(__DIR__, 2));

$filter = $argv[1] ?? null;

// Map: nama suite → command yang menjalankannya
$suites = [
    'smoke'       => 'php internal/tests/smoke/run.php',
    'penghargaan' => 'php internal/tests/penghargaan/run.php',
    'security'    => 'php internal/tests/security/run.php',
    'cs'          => 'php internal/tests/cs/run.php',
    'form'        => 'php internal/tests/form/run.php',
    'absensi'     => 'php internal/tests/absensi/run.php',
];

if ($filter !== null && !isset($suites[$filter])) {
    echo "\e[31mSuite tidak dikenal: {$filter}\e[0m\n";
    echo "Tersedia: " . implode(', ', array_keys($suites)) . "\n";
    exit(1);
}

$toRun = $filter ? [$filter => $suites[$filter]] : $suites;

echo "\n\e[1m\e[34m═══ Running " . count($toRun) . " test suite(s) ═══\e[0m\n";

$totalFail = 0;
$suiteResults = [];
foreach ($toRun as $name => $cmd) {
    echo "\n\e[1m→ Suite: {$name}\e[0m\n";
    passthru($cmd . ' 2>&1', $code);
    $suiteResults[$name] = $code;
    if ($code !== 0) $totalFail++;
}

echo "\n\e[1m\e[34m═══ Final ═══\e[0m\n";
foreach ($suiteResults as $name => $code) {
    $marker = $code === 0 ? "\e[32m✓\e[0m" : "\e[31m✗\e[0m";
    echo "  {$marker} {$name}\n";
}
echo "\n";
exit($totalFail > 0 ? 1 : 0);

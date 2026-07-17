<?php
/**
 * Static scanner: cari pola SQL string-interpolation di query() calls.
 *
 * Anti-pattern: $mysqli->query("... '$var' ..."), $mysqli->query("... = $var")
 * Diizinkan:    $mysqli->prepare("... = ?") + bind_param()
 *
 * Test files dikecualikan — mereka boleh interpolate untuk test fixtures (data terkontrol).
 */
T::header('Static Scan — SQL Injection Anti-Pattern');

$ROOT = realpath(__DIR__ . '/../../..');

/** Folder yang di-skip karena bukan kode aplikasi. */
$EXCLUDE_DIRS = [
    $ROOT . DIRECTORY_SEPARATOR . 'internal',
    $ROOT . DIRECTORY_SEPARATOR . 'vendor',
    $ROOT . DIRECTORY_SEPARATOR . 'node_modules',
    $ROOT . DIRECTORY_SEPARATOR . '.git',
];

/** Cari semua .php file di project (exclude folder di atas). */
function findPhpFiles(string $root, array $excludeDirs): array {
    $files = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $f) {
        if (!$f->isFile() || $f->getExtension() !== 'php') continue;
        $path = $f->getPathname();
        foreach ($excludeDirs as $ex) {
            if (str_starts_with($path, $ex . DIRECTORY_SEPARATOR)) continue 2;
        }
        $files[] = $path;
    }
    return $files;
}

/** Cek apakah satu baris mengandung interpolation berbahaya di dalam query()/prepare()/exec(). */
function hasUnsafeInterpolation(string $line): bool {
    // Patokan: panggilan ->query(...) atau ->exec(...) yang isinya berisi $var dalam string ".." atau '..'.
    if (!preg_match('/->\s*(query|exec|multi_query|real_query)\s*\(/i', $line)) return false;
    // Match: "... $var ..." atau "... '$var' ..." atau "... = $var" (concat juga).
    // Allowlist: tidak match jika literal SQL tanpa $.
    if (preg_match('/->\s*(query|exec|multi_query|real_query)\s*\(\s*["\'][^"\']*\$[a-zA-Z_]/i', $line)) return true;
    // Detect concat dengan dot: ->query("... " . $var)
    if (preg_match('/->\s*(query|exec|multi_query|real_query)\s*\([^)]*\.\s*\$[a-zA-Z_]/i', $line)) return true;
    return false;
}

$files = findPhpFiles($ROOT, $EXCLUDE_DIRS);
$violations = [];

foreach ($files as $path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $i => $line) {
        if (hasUnsafeInterpolation($line)) {
            $rel = ltrim(str_replace($ROOT, '', $path), DIRECTORY_SEPARATOR);
            $violations[] = "{$rel}:" . ($i + 1) . "  " . trim($line);
        }
    }
}

T::ok('Tidak ada SQL string interpolation di kode aplikasi (' . count($files) . ' file dipindai)', empty($violations));

if (!empty($violations)) {
    echo "\e[31m  Violations:\e[0m\n";
    foreach ($violations as $v) echo "\e[31m    - {$v}\e[0m\n";
}

<?php
/**
 * Generic PostToolUse hook — auto-run suite test yang relevan dengan file yang baru diedit.
 *
 * Mapping path → suite:
 *   penghargaan/*  → penghargaan
 *   apapun .php    → smoke (kalau bukan file test sendiri)
 *
 * Exit code 0  = OK, lanjut
 * Exit code 2  = block, kasih feedback ke Claude
 */

$in = json_decode(file_get_contents('php://stdin'), true) ?: [];
$fp = $in['tool_input']['file_path'] ?? $in['tool_input']['path'] ?? '';

if (!$fp) exit(0);

// Skip non-PHP, non-HTML, non-JS edits
$ext = strtolower(pathinfo($fp, PATHINFO_EXTENSION));
if (!in_array($ext, ['php', 'html', 'js', 'css'], true)) exit(0);

// Skip edit pada file test sendiri (supaya tidak infinite loop)
if (stripos($fp, '/tests/') !== false || stripos($fp, '\\tests\\') !== false) exit(0);

chdir(dirname(__DIR__));

// Pilih suite yang sesuai
$suite = stripos($fp, 'penghargaan') !== false ? 'penghargaan' : 'smoke';

passthru("php tests/run-all.php {$suite} 2>&1", $code);

if ($code !== 0) {
    // Format JSON output untuk Claude Code hook
    $payload = [
        'decision' => 'block',
        'reason'   => "Test suite '{$suite}' GAGAL setelah edit {$fp}. Periksa output di atas dan fix sebelum lanjut.",
    ];
    echo "\n" . json_encode($payload);
    exit(2);
}

exit(0);

<?php
// Dipanggil oleh Claude Code PostToolUse hook.
// Baca file_path dari stdin JSON, jalankan tests jika menyentuh penghargaan/.
$in = json_decode(file_get_contents('php://stdin'), true);
$fp = $in['tool_input']['file_path'] ?? $in['tool_input']['path'] ?? '';

if (stripos($fp, 'penghargaan') === false) {
    exit(0); // Bukan file penghargaan — skip tanpa output
}

chdir(dirname(__DIR__, 3)); // Project root
passthru('php internal/tests/penghargaan/run.php 2>&1', $code);
exit($code);

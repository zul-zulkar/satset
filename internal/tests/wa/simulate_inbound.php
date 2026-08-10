<?php
/**
 * Simulator webhook Fonnte untuk pengembangan lokal.
 *
 * Fonnte tidak bisa menjangkau server lokal, jadi skrip ini mengirim POST
 * ke wa-webhook/ persis seperti yang dikirim Fonnte, lalu menampilkan
 * respons + 5 baris wa_log terakhir nomor tersebut.
 *
 * Pakai:
 *   php internal/tests/wa/simulate_inbound.php 6281234567890 "minta data" [Nama]
 */

require_once __DIR__ . '/../../../app/config.php';

$nomor = $argv[1] ?? '';
$pesan = $argv[2] ?? '';
$nama  = $argv[3] ?? 'Simulator';

if ($nomor === '' || $pesan === '') {
    echo "Pakai: php internal/tests/wa/simulate_inbound.php <nomor> \"<pesan>\" [nama]\n";
    echo "Contoh: php internal/tests/wa/simulate_inbound.php 6281234567890 \"minta data\" Budi\n";
    exit(1);
}

$url = 'http://localhost/satset/wa-webhook/?key=' . urlencode(WA_WEBHOOK_SECRET);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        // Nama field persis payload nyata Fonnte (Webhook Log dashboard):
        // 'pengirim' (bukan 'sender'), 'pesan' (bukan 'message').
        'device'   => WA_DEVICE !== '' ? WA_DEVICE : '6280000000000',
        'pengirim' => $nomor,
        'pesan'    => $pesan,
        'name'     => $nama,
    ]),
    CURLOPT_TIMEOUT        => 10,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo "POST {$url}\n";
echo "  sender  : {$nomor}\n";
echo "  message : {$pesan}\n";
echo "  → HTTP {$code}" . ($err ? " (curl: {$err})" : '') . "\n";
echo "  → {$body}\n\n";

// 5 baris wa_log terakhir untuk nomor ini
require_once __DIR__ . '/../../../app/wa.php';
$db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    echo "(DB tidak terhubung: {$db->connect_error})\n";
    exit(0);
}
$norm = wa_normalize_phone($nomor) ?? $nomor;
$st = $db->prepare("SELECT id, arah, intent, status, LEFT(COALESCE(pesan,''), 60) AS cuplikan, created_at
                    FROM wa_log WHERE nomor = ? ORDER BY id DESC LIMIT 5");
$st->bind_param("s", $norm);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
$db->close();

echo "wa_log terakhir untuk {$norm}:\n";
if (!$rows) {
    echo "  (kosong)\n";
    exit(0);
}
foreach ($rows as $r) {
    printf("  #%-5d %-6s %-18s %-10s %s | %s\n",
        $r['id'], $r['arah'], $r['intent'] ?? '-', $r['status'],
        $r['created_at'], str_replace("\n", ' ', $r['cuplikan']));
}

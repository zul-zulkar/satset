<?php
/**
 * Runtime tests — webhook wa-webhook/ + action piket/ via HTTP lokal.
 *
 * Prasyarat: XAMPP jalan + FONNTE_TOKEN kosong (mode simulasi) supaya
 * tidak ada pesan sungguhan yang terkirim. Skip otomatis bila tidak.
 * Memakai nomor uji prefiks 62899999000xx, dibersihkan sebelum & sesudah.
 */
T::header('Runtime — Webhook & Piket');

require_once __DIR__ . '/../../../app/config.php';

$base = 'http://localhost/satset';

$ch = curl_init($base . '/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY         => true,
    CURLOPT_TIMEOUT        => 3,
    CURLOPT_SSL_VERIFYPEER => false,
]);
curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    T::skip('Runtime WA tests (Server tidak bisa dijangkau: ' . $base . ')');
    return;
}
if (FONNTE_TOKEN !== '') {
    T::skip('Runtime WA tests (FONNTE_TOKEN terisi — hindari kirim pesan sungguhan)');
    return;
}

function waGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => (int)$code, 'body' => (string)$body];
}

function waPost(string $url, array $fields): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => (int)$code, 'body' => (string)$body];
}

$webhook    = $base . '/wa-webhook/';
$webhookKey = $webhook . '?key=' . urlencode(WA_WEBHOOK_SECRET);

// ── Koneksi DB untuk asersi wa_log ─────────────────────────────────────────
$db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    T::skip('Runtime WA tests (DB tidak terhubung: ' . $db->connect_error . ')');
    return;
}
$bersihkan = function () use ($db) {
    $db->query("DELETE FROM wa_log WHERE nomor LIKE '62899999000%'");
};
$logRows = function (string $nomor, string $arah) use ($db): array {
    $st = $db->prepare("SELECT * FROM wa_log WHERE nomor = ? AND arah = ? ORDER BY id");
    $st->bind_param("ss", $nomor, $arah);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
};
$db->query("CREATE TABLE IF NOT EXISTS wa_log (
    id INT AUTO_INCREMENT PRIMARY KEY, arah ENUM('masuk','keluar') NOT NULL,
    nomor VARCHAR(25) NOT NULL, pesan TEXT NULL, intent VARCHAR(40) NULL,
    status VARCHAR(20) NOT NULL, respon TEXT NULL, antrian_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_nomor_arah_created (nomor, arah, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$bersihkan();

// ── 1. Proteksi secret ─────────────────────────────────────────────────────
$r = waGet($webhook);
T::eq('GET webhook tanpa key → 403', 403, $r['code']);

$r = waGet($webhook . '?key=salah');
T::eq('GET webhook key salah → 403', 403, $r['code']);

$r = waGet($webhookKey);
T::eq('GET webhook key benar → 200', 200, $r['code']);
T::ok('respons "webhook aktif"', str_contains($r['body'], 'webhook aktif'));

// ── 2. Pesan tanpa kata kunci → balasan menu (simulasi) ────────────────────
$n1 = '6289999900011';
$r  = waPost($webhookKey, ['device' => '6280000000000', 'sender' => $n1, 'message' => 'halo selamat pagi', 'name' => 'Tester Satu']);
T::eq('POST "halo" → 200', 200, $r['code']);

$masuk  = $logRows($n1, 'masuk');
$keluar = $logRows($n1, 'keluar');
T::eq('1 row masuk tercatat',             1, count($masuk));
T::eq('masuk status ok',                  'ok', $masuk[0]['status'] ?? '');
T::eq('1 row keluar (balasan)',           1, count($keluar));
T::eq('balasan status simulasi',          'simulasi', $keluar[0]['status'] ?? '');
T::eq('balasan intent menu',              'menu', $keluar[0]['intent'] ?? '');
T::ok('balasan memuat opsi menu',         str_contains($keluar[0]['pesan'] ?? '', '1. Permintaan Data'));

// ── 3. Jawaban menu "1" → balasan intent Permintaan Data ───────────────────
$n2 = '6289999900012';
waPost($webhookKey, ['device' => '6280000000000', 'sender' => $n2, 'message' => '1', 'name' => 'Tester Dua']);
$keluar = $logRows($n2, 'keluar');
T::eq('balasan intent Permintaan Data',   'Permintaan Data', $keluar[0]['intent'] ?? '');
T::ok('balasan memuat tautan buku tamu',  str_contains($keluar[0]['pesan'] ?? '', '/whatsapp/'));

// ── 4. Dedup: pesan identik dalam 60 detik tidak dibalas lagi ──────────────
waPost($webhookKey, ['device' => '6280000000000', 'sender' => $n1, 'message' => 'halo selamat pagi', 'name' => 'Tester Satu']);
$masuk  = $logRows($n1, 'masuk');
$keluar = $logRows($n1, 'keluar');
T::eq('duplikat tercatat diabaikan',      'diabaikan', end($masuk)['status'] ?? '');
T::eq('tidak ada balasan kedua',          1, count($keluar));

// ── 5. Pesan grup diabaikan ────────────────────────────────────────────────
$n3 = '6289999900013';
waPost($webhookKey, ['device' => '6280000000000', 'sender' => $n3, 'message' => 'minta data', 'name' => 'Grup PST', 'member' => '628111222333']);
$masuk  = $logRows($n3, 'masuk');
$keluar = $logRows($n3, 'keluar');
T::eq('pesan grup status diabaikan',      'diabaikan', $masuk[0]['status'] ?? '');
T::eq('pesan grup tidak dibalas',         0, count($keluar));

// ── 6. Pesan dari device sendiri diabaikan (anti-loop) ─────────────────────
$dev = wa_normalize_phone(WA_DEVICE);
if ($dev !== null) {
    $r = waPost($webhookKey, ['device' => WA_DEVICE, 'sender' => WA_DEVICE, 'message' => 'tes loop', 'name' => 'PST']);
    T::eq('POST dari device sendiri → 200', 200, $r['code']);
    T::ok('device sendiri: diabaikan', str_contains($r['body'], 'diabaikan'));
} else {
    T::skip('anti-loop device (WA_DEVICE kosong di config)');
}

// ── 7. Action piket butuh sesi ─────────────────────────────────────────────
$r = waPost($base . '/piket/action/save_piket.php', ['action' => 'add', 'senin' => '2026-07-13', 'pegawai_id' => 1]);
T::eq('save_piket tanpa sesi → 401', 401, $r['code']);

$r = waPost($base . '/piket/action/save_hp.php', ['pegawai_id' => 1, 'no_hp' => '081234567890']);
T::eq('save_hp tanpa sesi → 401', 401, $r['code']);

$r = waGet($base . '/piket/');
T::ok('GET /piket/ tanpa sesi → redirect login', $r['code'] === 302);

// ── Bersihkan data uji ─────────────────────────────────────────────────────
$bersihkan();
$db->close();

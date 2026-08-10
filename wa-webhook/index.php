<?php
/**
 * Webhook pesan masuk WhatsApp (Fonnte) — bot balasan otomatis PST.
 *
 * URL yang didaftarkan di dashboard Fonnte (menu Device → Webhook):
 *   <APP_URL>/wa-webhook/?key=<WA_WEBHOOK_SECRET>
 *
 * Fonnte mengirim POST form-encoded untuk tiap pesan masuk; field yang
 * dipakai: sender, message, name, member (terisi bila pesan grup), device.
 *
 * Alur: validasi secret → saring (grup / diri sendiri / kosong) →
 * dedup & batas balasan (hemat kuota paket gratis, anti loop) →
 * deteksi intent kata kunci → balas tautan buku tamu atau menu 1-4.
 *
 * Endpoint SELALU membalas HTTP 200 pada request yang lolos secret,
 * apa pun yang terjadi di dalam — agar Fonnte tidak melakukan retry-flood.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../app/config.php';

if (!hash_equals(WA_WEBHOOK_SECRET, (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['status' => false, 'note' => 'key tidak valid']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => true, 'note' => 'webhook aktif']);
    exit;
}

try {
    include __DIR__ . '/../app/db.php';
    require_once __DIR__ . '/../app/wa.php';
    wa_ensure_tables($mysqli);

    $in = $_POST;
    if (empty($in)) {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
    }

    $senderRaw = trim((string)($in['sender']  ?? ''));
    $message   = trim((string)($in['message'] ?? ''));
    $name      = trim((string)($in['name']    ?? ''));
    $member    = trim((string)($in['member']  ?? ''));
    $device    = trim((string)($in['device']  ?? ''));
    $isGroup   = !empty($in['isgroup']) || !empty($in['isGroup']);
    $fromMe    = !empty($in['fromme'])  || !empty($in['fromMe']);

    $selesai = function (string $note) {
        echo json_encode(['status' => true, 'note' => $note]);
        exit;
    };
    $abaikan = function (string $alasan, ?string $nomor) use ($mysqli, $message, $selesai) {
        wa_log_msg($mysqli, 'masuk', $nomor ?? '-', $message, null, 'diabaikan', $alasan);
        $selesai('diabaikan: ' . $alasan);
    };

    // ── Saringan dasar ──────────────────────────────────────────────────
    $sender = wa_normalize_phone($senderRaw);

    if ($member !== '' || $isGroup || str_contains($senderRaw, '-') || str_contains($senderRaw, '@g')) {
        $abaikan('pesan grup', $sender);
    }
    if ($sender === null) {
        $abaikan('sender tidak valid: ' . $senderRaw, null);
    }
    if ($fromMe
        || (WA_DEVICE !== '' && $sender === wa_normalize_phone(WA_DEVICE))
        || ($device !== '' && $sender === wa_normalize_phone($device))) {
        $abaikan('pesan dari device sendiri', $sender);
    }
    if ($message === '') {
        $abaikan('pesan kosong / media tanpa teks', $sender);
    }

    // ── Dedup: pesan identik dari nomor sama dalam 60 detik (retry Fonnte)
    $st = $mysqli->prepare(
        "SELECT COUNT(*) AS n FROM wa_log
         WHERE arah = 'masuk' AND nomor = ? AND pesan = ? AND status <> 'diabaikan'
           AND created_at >= NOW() - INTERVAL 60 SECOND"
    );
    $st->bind_param("ss", $sender, $message);
    $st->execute();
    $dup = (int)$st->get_result()->fetch_assoc()['n'];
    $st->close();
    if ($dup > 0) {
        $abaikan('duplikat dalam 60 detik', $sender);
    }

    // ── Batas balasan: maks 6 keluar per nomor per jam (anti loop bot,
    //    sekaligus menjaga kuota paket gratis Fonnte) ────────────────────
    $st = $mysqli->prepare(
        "SELECT COUNT(*) AS n FROM wa_log
         WHERE arah = 'keluar' AND nomor = ?
           AND created_at >= NOW() - INTERVAL 1 HOUR"
    );
    $st->bind_param("s", $sender);
    $st->execute();
    $keluarSejam = (int)$st->get_result()->fetch_assoc()['n'];
    $st->close();

    $intent = wa_detect_intent($message);
    wa_log_msg($mysqli, 'masuk', $sender, $message, $intent, 'ok');

    if ($keluarSejam >= 6) {
        $selesai('batas balasan per jam tercapai, tidak membalas');
    }

    $balasan = $intent !== null ? wa_tpl_intent($name, $intent) : wa_tpl_menu($name);
    fonnte_send($mysqli, $sender, $balasan, $intent ?? 'menu');

    $selesai('ok');
} catch (Throwable $e) {
    error_log('wa-webhook error: ' . $e->getMessage());
    echo json_encode(['status' => true, 'note' => 'internal error tercatat']);
    exit;
}

<?php
/**
 * Helper integrasi WhatsApp via gateway Fonnte (paket gratis).
 *
 * Dipakai oleh:
 *   - wa-webhook/index.php        → bot balasan pesan masuk (R1)
 *   - form/buku_tamu_whatsapp.php → notifikasi petugas piket saat ada
 *                                   isian buku tamu baru (R2)
 *   - piket/                      → kelola jadwal piket + no_hp pegawai (R3)
 *
 * File ini hanya berisi definisi fungsi (tanpa side effect) sehingga bisa
 * di-require dari test CLI tanpa koneksi DB. Semua fungsi yang butuh DB
 * menerima $mysqli sebagai parameter.
 *
 * MODE SIMULASI: bila FONNTE_TOKEN kosong (default di ENV local), tidak ada
 * HTTP call ke Fonnte — pesan keluar dicatat di wa_log dengan status
 * 'simulasi' dan dianggap sukses.
 */

require_once __DIR__ . '/config.php';

// ── Normalisasi nomor ────────────────────────────────────────────────────

/**
 * Normalisasi nomor telepon Indonesia ke format 628xx.
 * '08xx' → '628xx', '+62xx' → '62xx', '8xx' → '628xx'.
 * Mengembalikan null jika bukan nomor yang masuk akal (10–15 digit).
 */
function wa_normalize_phone(?string $telepon): ?string {
    $digits = preg_replace('/\D+/', '', (string)$telepon);
    if ($digits === '') return null;
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    } elseif (!str_starts_with($digits, '62')) {
        $digits = '62' . $digits;
    }
    $len = strlen($digits);
    return ($len >= 10 && $len <= 15) ? $digits : null;
}

// ── Skema (auto-create, pola penghargaan/) ───────────────────────────────

/**
 * Buat tabel wa_log & jadwal_piket bila belum ada, plus kolom
 * pegawai.no_hp (MySQL 8 tidak mendukung ADD COLUMN IF NOT EXISTS,
 * jadi dicek lewat SHOW COLUMNS).
 */
function wa_ensure_tables(mysqli $mysqli): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $mysqli->query("CREATE TABLE IF NOT EXISTS wa_log (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        arah       ENUM('masuk','keluar') NOT NULL,
        nomor      VARCHAR(25) NOT NULL,
        pesan      TEXT NULL,
        intent     VARCHAR(40) NULL,
        status     VARCHAR(20) NOT NULL,
        respon     TEXT NULL,
        antrian_id INT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_nomor_arah_created (nomor, arah, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $mysqli->query("CREATE TABLE IF NOT EXISTS jadwal_piket (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        senin      DATE NOT NULL,
        pegawai_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_minggu_pegawai (senin, pegawai_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $r = $mysqli->query("SHOW COLUMNS FROM pegawai LIKE 'no_hp'");
    if ($r && $r->num_rows === 0) {
        $mysqli->query("ALTER TABLE pegawai ADD COLUMN no_hp VARCHAR(20) NULL AFTER nip");
    }
}

// ── Logging ──────────────────────────────────────────────────────────────

function wa_log_msg(
    mysqli $mysqli, string $arah, string $nomor, ?string $pesan,
    ?string $intent, string $status, ?string $respon = null, ?int $antrianId = null
): void {
    $st = $mysqli->prepare(
        "INSERT INTO wa_log (arah, nomor, pesan, intent, status, respon, antrian_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $st->bind_param("ssssssi", $arah, $nomor, $pesan, $intent, $status, $respon, $antrianId);
    $st->execute();
    $st->close();
}

// ── Klien Fonnte ─────────────────────────────────────────────────────────

/**
 * Kirim pesan WhatsApp via Fonnte. Tidak pernah melempar exception dan
 * tidak boleh menghambat alur pemanggil — kegagalan cukup tercatat di wa_log.
 *
 * @return array{success: bool, raw: ?string}
 */
function fonnte_send(
    mysqli $mysqli, string $target, string $message,
    ?string $intent = null, ?int $antrianId = null
): array {
    $nomor = wa_normalize_phone($target);
    if ($nomor === null) {
        wa_log_msg($mysqli, 'keluar', (string)$target, $message, $intent, 'gagal', 'Nomor tidak valid', $antrianId);
        return ['success' => false, 'raw' => null];
    }

    if (FONNTE_TOKEN === '') {
        wa_log_msg($mysqli, 'keluar', $nomor, $message, $intent, 'simulasi', null, $antrianId);
        return ['success' => true, 'raw' => null];
    }

    $raw = null;
    try {
        $ch = curl_init('https://api.fonnte.com/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . FONNTE_TOKEN],
            CURLOPT_POSTFIELDS     => http_build_query([
                'target'      => $nomor,
                'message'     => $message,
                'countryCode' => '62',
            ]),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err) {
            wa_log_msg($mysqli, 'keluar', $nomor, $message, $intent, 'gagal', 'curl: ' . $err, $antrianId);
            return ['success' => false, 'raw' => null];
        }

        $json    = json_decode($raw, true);
        $success = is_array($json) && !empty($json['status']);
        wa_log_msg($mysqli, 'keluar', $nomor, $message, $intent, $success ? 'ok' : 'gagal', $raw, $antrianId);
        return ['success' => $success, 'raw' => $raw];
    } catch (Throwable $e) {
        wa_log_msg($mysqli, 'keluar', $nomor, $message, $intent, 'gagal', 'exception: ' . $e->getMessage(), $antrianId);
        return ['success' => false, 'raw' => is_string($raw) ? $raw : null];
    }
}

/**
 * Cek apakah nomor terdaftar di WhatsApp via endpoint validate Fonnte.
 * Respons diparsing best-effort; bentuk tak dikenal / error / token kosong /
 * fitur tak tersedia di paket gratis → null (tidak menggagalkan alur apa pun).
 */
function fonnte_validate(string $target): ?bool {
    $nomor = wa_normalize_phone($target);
    if ($nomor === null || FONNTE_TOKEN === '') return null;

    try {
        $ch = curl_init('https://api.fonnte.com/validate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . FONNTE_TOKEN],
            CURLOPT_POSTFIELDS     => http_build_query([
                'target'      => $nomor,
                'countryCode' => '62',
            ]),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $err) return null;

        $json = json_decode($raw, true);
        if (!is_array($json)) return null;
        $reg    = $json['registered']     ?? null;
        $notReg = $json['not_registered'] ?? null;
        if (is_array($reg)    && in_array($nomor, array_map('strval', $reg), true))    return true;
        if (is_array($notReg) && in_array($nomor, array_map('strval', $notReg), true)) return false;
        return null;
    } catch (Throwable $e) {
        return null;
    }
}

// ── Deteksi intent (kata kunci + menu) ───────────────────────────────────

/**
 * Deteksi niat pengunjung dari isi pesan. Mengembalikan label persis
 * seperti nilai antrian.jenis_pelayanan, atau null → kirim menu.
 * Urutan cek: spesifik → generik, agar "konsultasi data" tidak jatuh ke
 * Permintaan Data hanya karena memuat kata "data".
 */
function wa_detect_intent(string $message): ?string {
    $m = mb_strtolower(trim($message));
    if ($m === '') return null;

    // Jawaban menu: "1" / "2." / "3)" / "4"
    if (preg_match('/^([1-4])\s*[.)]?$/', $m, $mt)) {
        return [
            '1' => 'Permintaan Data',
            '2' => 'Konsultasi Statistik',
            '3' => 'Rekomendasi Statistik',
            '4' => 'Pengaduan',
        ][$mt[1]];
    }

    $peta = [
        'Pengaduan'             => ['pengaduan', 'aduan', 'keluhan', 'komplain', 'lapor'],
        'Rekomendasi Statistik' => ['rekomendasi', 'romantik'],
        'Konsultasi Statistik'  => ['konsultasi', 'konsul'],
        'Permintaan Data'       => ['permintaan data', 'minta data', 'butuh data', 'perlu data',
                                    'cari data', 'publikasi', 'data'],
    ];
    foreach ($peta as $intent => $kataKunci) {
        foreach ($kataKunci as $kata) {
            if (str_contains($m, $kata)) return $intent;
        }
    }
    return null;
}

// ── Jadwal piket ─────────────────────────────────────────────────────────

/** Tanggal Senin (Y-m-d) dari minggu yang memuat $tanggal. */
function wa_monday_of(?string $tanggal = null): string {
    $d = new DateTime($tanggal ?? 'now');
    if ((int)$d->format('N') !== 1) $d->modify('last monday');
    return $d->format('Y-m-d');
}

/**
 * Petugas piket minggu berjalan dengan no_hp valid (sudah dinormalisasi).
 * Selalu mengembalikan minimal 1 target: bila jadwal kosong / tak ada no_hp
 * valid, jatuh ke nomor fallback WA_FALLBACK_HP.
 *
 * @return array<array{pegawai_id: ?int, nama: string, no_hp: string}>
 */
function wa_officers_on_duty(mysqli $mysqli, ?string $tanggal = null): array {
    $senin = wa_monday_of($tanggal);
    $st = $mysqli->prepare(
        "SELECT p.id, p.nama, p.no_hp
         FROM jadwal_piket jp
         JOIN pegawai p ON p.id = jp.pegawai_id
         WHERE jp.senin = ?
         ORDER BY p.nama"
    );
    $st->bind_param("s", $senin);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $targets = [];
    foreach ($rows as $r) {
        $hp = wa_normalize_phone($r['no_hp'] ?? '');
        if ($hp === null) continue;
        $targets[] = ['pegawai_id' => (int)$r['id'], 'nama' => $r['nama'], 'no_hp' => $hp];
    }
    if (empty($targets)) {
        $fb = wa_normalize_phone(WA_FALLBACK_HP);
        if ($fb !== null) {
            $targets[] = ['pegawai_id' => null, 'nama' => 'Nomor Fallback PST', 'no_hp' => $fb];
        }
    }
    return $targets;
}

// ── Template pesan (Bahasa Indonesia, formal-ramah) ──────────────────────

function wa_tpl_sapa(string $name): string {
    $name = trim($name);
    return $name !== '' ? ', Bapak/Ibu ' . $name : '';
}

function wa_tpl_footer(): string {
    return "_Pesan ini dikirim otomatis oleh sistem PST BPS Kabupaten Buleleng._";
}

function wa_tpl_intent(string $name, string $layanan): string {
    $sapa = wa_tpl_sapa($name);
    $link = APP_URL . '/whatsapp/';
    return "Halo{$sapa}, terima kasih telah menghubungi Pelayanan Statistik Terpadu (PST) BPS Kabupaten Buleleng.\n\n"
         . "Untuk layanan *{$layanan}*, mohon berkenan mengisi buku tamu daring terlebih dahulu melalui tautan berikut:\n"
         . "{$link}\n\n"
         . "Setelah buku tamu terisi, petugas kami akan menindaklanjuti kebutuhan Anda pada jam layanan (Senin-Jumat, pukul 08.00-15.30 WITA).\n\n"
         . "Terima kasih.\n\n"
         . wa_tpl_footer();
}

function wa_tpl_menu(string $name): string {
    $sapa = wa_tpl_sapa($name);
    $link = APP_URL . '/whatsapp/';
    return "Halo{$sapa}, selamat datang di layanan WhatsApp PST BPS Kabupaten Buleleng.\n\n"
         . "Silakan balas pesan ini dengan angka sesuai kebutuhan Anda:\n"
         . "1. Permintaan Data\n"
         . "2. Konsultasi Statistik\n"
         . "3. Rekomendasi Kegiatan Statistik\n"
         . "4. Pengaduan\n\n"
         . "Anda juga dapat langsung mengisi buku tamu daring melalui tautan:\n"
         . "{$link}\n\n"
         . wa_tpl_footer();
}

/**
 * Notifikasi ke petugas piket saat ada isian buku tamu WhatsApp baru.
 *
 * @param array $antrian      row tabel antrian
 * @param ?bool $waTerdaftar  hasil fonnte_validate (null = tidak diketahui)
 * @param ?string $lastChatAt datetime chat bot terakhir ≤24 jam, null jika tidak ada
 */
function wa_tpl_notifikasi(array $antrian, ?bool $waTerdaftar, ?string $lastChatAt): string {
    $jpDataLabels = [
        'Permintaan Data'       => 'Data yang Dibutuhkan',
        'Konsultasi Statistik'  => 'Statistik yang Dikonsultasikan',
        'Rekomendasi Statistik' => 'Kegiatan Statistik yang Akan Dilaksanakan',
        'Pengaduan'             => 'Detail Pengaduan',
    ];
    $layanan   = $antrian['jenis_pelayanan'] ?? '-';
    $label     = $jpDataLabels[$layanan] ?? 'Data yang Dibutuhkan';
    $items     = json_decode($antrian['data_dibutuhkan'] ?? '[]', true) ?: [];

    if ($layanan === 'Pengaduan') {
        $isiData = ($items[0]['data'] ?? '-');
    } else {
        $baris = [];
        foreach ($items as $it) {
            $thn = '';
            if (!empty($it['tahun_dari']) || !empty($it['tahun_sampai'])) {
                $thn = ' (' . intval($it['tahun_dari']) . '-' . intval($it['tahun_sampai']) . ')';
            }
            $baris[] = '- ' . ($it['data'] ?? '-') . $thn;
        }
        $isiData = $baris ? implode("\n", $baris) : '-';
    }

    $hp62  = wa_normalize_phone($antrian['telepon'] ?? '') ?? '-';
    $waTxt = $waTerdaftar === true ? 'Ya' : ($waTerdaftar === false ? 'Tidak' : 'Tidak diketahui');
    $chatTxt = $lastChatAt
        ? 'Ya, terakhir ' . date('d/m H.i', strtotime($lastChatAt)) . ' WITA'
        : 'Tidak';
    $linkDetail = APP_URL . '/cs/detail_pengunjung.php?token=' . ($antrian['token'] ?? '');

    return "*Pengunjung PST Baru (WhatsApp)*\n\n"
         . "Nama: " . ($antrian['nama'] ?? '-') . "\n"
         . "Instansi: " . ($antrian['instansi'] ?? '-') . "\n"
         . "Layanan: {$layanan}\n"
         . "{$label}:\n{$isiData}\n"
         . "No. HP: " . ($antrian['telepon'] ?? '-') . " (WA {$hp62}, terdaftar: {$waTxt})\n"
         . "Chat bot 24 jam terakhir: {$chatTxt}\n\n"
         . "Detail dan tindak lanjut:\n{$linkDetail}\n\n"
         . "Catatan: pengunjung masih dapat merevisi isian hingga 2 jam setelah pengiriman; tautan di atas selalu menampilkan data terbaru.\n\n"
         . wa_tpl_footer();
}

// ── Orkestrasi notifikasi (R2) ───────────────────────────────────────────

/**
 * Kirim notifikasi WA ke petugas piket untuk record antrian baru.
 * Dipanggil dari form/buku_tamu_whatsapp.php setelah INSERT sukses.
 * Tidak melempar exception keluar — pemanggil tetap membungkus try/catch
 * sebagai lapis pengaman terakhir.
 */
function wa_notify_new_antrian(mysqli $mysqli, int $antrianId): void {
    wa_ensure_tables($mysqli);

    $st = $mysqli->prepare("SELECT * FROM antrian WHERE id = ? LIMIT 1");
    $st->bind_param("i", $antrianId);
    $st->execute();
    $antrian = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$antrian) return;

    $hp = wa_normalize_phone($antrian['telepon'] ?? '');
    $waTerdaftar = $hp !== null ? fonnte_validate($hp) : false;

    $lastChatAt = null;
    if ($hp !== null) {
        $st = $mysqli->prepare(
            "SELECT MAX(created_at) AS t FROM wa_log
             WHERE arah = 'masuk' AND nomor = ?
               AND created_at >= NOW() - INTERVAL 1 DAY"
        );
        $st->bind_param("s", $hp);
        $st->execute();
        $lastChatAt = $st->get_result()->fetch_assoc()['t'] ?? null;
        $st->close();
    }

    $pesan = wa_tpl_notifikasi($antrian, $waTerdaftar, $lastChatAt);
    foreach (wa_officers_on_duty($mysqli) as $t) {
        fonnte_send($mysqli, $t['no_hp'], $pesan, 'notifikasi_petugas', $antrianId);
    }
}

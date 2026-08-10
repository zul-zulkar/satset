# Sistem Antrean PST BPS Kabupaten Buleleng

PHP 8.1+ (mysqli, prepared statements) + MySQL, XAMPP lokal. Tanpa framework, tanpa build step. Kode privat di `app/`, semua tooling/dokumentasi non-web di `internal/`.

## Batasan #1 — WAJIB dibaca sebelum menambah folder/berkas

Produksi (`satset.statsbali.id`) memakai **nginx**, yang **mengabaikan total** `.htaccess`/`mod_rewrite`/`DirectoryIndex`. Sudah dibuktikan langsung (baris ngawur di `.htaccess` tak memicu 500).

Konsekuensi:
- **Struktur folder = struktur URL.** Setiap folder aplikasi (root repo) harus langsung berisi `index.php`. **Jangan pernah** menyarangkan folder aplikasi ke dalam folder pembungkus (mis. `public/`) — akan 404 di produksi.
- **Berkas non-PHP yang ter-upload bisa diunduh publik** (tidak ada proteksi `.htaccess`). Karena itu `internal/` (sql dump, backup, dsb.) **tidak boleh** ikut ke server produksi — sudah dijamin dikecualikan oleh `internal/deploy/build-zip.ps1`.
- `.user.ini` tetap berlaku (fitur PHP-FPM, bukan Apache) — dipakai untuk `session.name` unik per aplikasi.

Peta folder lengkap + alasan: [internal/STRUCTURE.md](internal/STRUCTURE.md). Cara deploy: [internal/DEPLOY-CPANEL.md](internal/DEPLOY-CPANEL.md).

## Struktur

- **Root** = dilayani web langsung: `index.php` (QR antrean), `menu.php`, `absensi/`, `cs/`, `form/`, `laporan/`, `penilaian/`, `pes/`, `penghargaan/`, `disabilitas/`, `umum/`, `whatsapp/`, `surat/`, `monitor/`, `piket/`, `wa-webhook/`, `templat/`, `assets/`, `vendor/`.
- **`app/`** — kode privat, tidak dirender langsung:
  - `config.php` — sumber tunggal `APP_URL`/`APP_BASE`, kredensial DB, dan konfigurasi Fonnte (WhatsApp). Switch `ENV` ('local'/'production') menentukan blok mana yang aktif.
  - `db.php` — buat `$mysqli`, set timezone `Asia/Makassar`.
  - `wa.php` — helper WhatsApp (lihat bagian Integrasi WhatsApp).
  - `partials/_head.php` — head HTML bersama (`$page_title`, `$head_extras` sebelum include).
- **`action/`** — endpoint AJAX lintas-modul, dikelompokkan per fungsi (`action/pengguna/`, `action/survei/`, `action/surat/`, `action/antrean/`). Berkas lama `action/*.php` flat masih ada sebagai shim 1-baris untuk kompatibilitas — kode baru pakai path baru.
- **`internal/`** — TIDAK dilayani web, TIDAK ikut deploy: `tests/`, `deploy/`, `sql_production/` (gitignored — dump DB), `backup/`, `input/`, `DEPLOY-CPANEL.md`, `STRUCTURE.md`.
- Include selalu pakai `__DIR__` relatif (mis. dari `action/x.php` → `__DIR__ . '/../app/db.php'`).
- Clean-URL = folder berisi `index.php`. Token (survei, revisi kuesioner, PES) lewat `$_GET['token']`.

## Alur data pengunjung

Semua kunjungan (walk-in umum/disabilitas, WhatsApp, surat) masuk ke **satu tabel `antrian`**, dibedakan lewat kolom `jenis` (channel: `umum`/`disabilitas`/`whatsapp`/`surat`) dan `jenis_pelayanan` (intent: `Permintaan Data`/`Konsultasi Statistik`/`Rekomendasi Statistik`/`Pengaduan` — hanya form WhatsApp yang punya opsi Pengaduan). `kunjungan_pst=1` menandai kunjungan terkait data PST.

- Form buku tamu: `form/buku_tamu.php` (umum/disabilitas, inline POST), `form/buku_tamu_whatsapp.php` (kanal WA, alur token+revisi 2 jam), `form/buku_tamu_surat.php`, `form/buku_tamu_penilaian.php` (survei kepuasan via token, tabel `penilaian`+`penilaian_data_item`), `form/buku_tamu_pes.php` (staff-only, token beda: `antrian.token_pes`).
- Nomor telepon disimpan **apa adanya** (08xx/+62xx/0362xx kantor), regex `^(\+62|0)\d{6,13}$`. Normalisasi ke `628xx` hanya terjadi di titik pemakaian (JS click-to-chat lama di `cs/daftar_pengguna.php`, dan `wa_normalize_phone()` di integrasi WhatsApp baru).
- Petugas (login) = tabel `pegawai` (auth: `absensi/login.php`, session `$_SESSION['absensi_auth']`/`pegawai_id`). Jadwal piket mingguan = tabel `jadwal_piket` (baru, lihat di bawah); presensi harian = `absensi_piket`.

## Integrasi WhatsApp (Fonnte, paket gratis)

Dibangun 2026-07-17. Gateway **Fonnte** (fonnte.com) paket gratis — bot kata kunci **tanpa AI**, tanpa komponen berbayar lain.

- **`app/wa.php`** — semua logika: `wa_normalize_phone()`, `fonnte_send()`/`fonnte_validate()` (klien HTTP Fonnte), `wa_detect_intent()` (kata kunci + menu angka 1–4), template pesan (`wa_tpl_menu`, `wa_tpl_intent`, `wa_tpl_notifikasi`), `wa_officers_on_duty()` (lookup jadwal piket minggu berjalan + fallback), `wa_ensure_tables()` (auto-create tabel `wa_log`+`jadwal_piket`+kolom `pegawai.no_hp` — dipanggil di titik masuk, tak perlu migrasi manual).
- **`wa-webhook/index.php`** — endpoint webhook pesan masuk Fonnte, diamankan `?key=<WA_WEBHOOK_SECRET>` (`hash_equals`). Deteksi intent kata kunci → balas tautan kuesioner `/whatsapp/` atau menu 1–4. Pengaman: abaikan grup/diri sendiri, dedup 60 detik, maks 6 balasan/nomor/jam.
- **`piket/`** — halaman admin (guard sesi `absensi_auth`) untuk kelola jadwal piket mingguan (kunci = tanggal Senin) dan nomor HP pegawai. `piket/action/save_piket.php`, `piket/action/save_hp.php`.
- **Hook notifikasi (R2)** — di `form/buku_tamu_whatsapp.php`, cabang INSERT sukses: setelah kuesioner WA baru tersimpan, `wa_notify_new_antrian()` mengirim ringkasan ke petugas piket. Dibungkus try/catch — kegagalan Fonnte **tidak pernah** memblokir pengunjung. **Revisi kuesioner sengaja tidak menotifikasi ulang** (link detail selalu tampilkan data terbaru).
- **Mode simulasi**: `FONNTE_TOKEN` kosong (default lokal) → nol HTTP call keluar, pesan dicatat di `wa_log` berstatus `simulasi`. Dipakai otomatis oleh test suite dan `internal/tests/wa/simulate_inbound.php` (simulator webhook lokal, karena Fonnte tak bisa menjangkau `localhost`).
- Config production (token, secret, fallback_hp, device) ada di `app/config.php` blok `$_waConf['production']`.

## Testing

```powershell
php internal/tests/run-all.php              # semua suite
php internal/tests/run-all.php wa            # hanya suite WhatsApp
php internal/tests/run-all.php smoke         # cukup cek semua route 200/302
```

Suite: `smoke`, `penghargaan`, `security`, `cs`, `form`, `absensi`, `wa`. Tiap suite gabungan unit (murni, tanpa DB/HTTP) + runtime (HTTP ke `http://localhost/satset`, auto-skip kalau server tak jalan). Ada PostToolUse hook (`internal/tests/_hook.php`) yang otomatis menjalankan suite relevan setelah edit PHP.

## Deploy

Panduan lengkap: [internal/DEPLOY-CPANEL.md](internal/DEPLOY-CPANEL.md). Ringkas:

1. `powershell -ExecutionPolicy Bypass -File internal\deploy\build-zip.ps1` → hasil `internal/deploy/pst-deploy.zip` (root repo dikurangi `internal/`, `.git/`, `.claude/`).
2. Upload+extract ke `~/satset.statsbali.id/pst/` di cPanel (document root = wadah, bukan folder aplikasi).
3. Pastikan `pst/app/config.php` baris `define('ENV', 'production');` aktif, kredensial DB blok `production` benar.
4. Uji: `/pst`, `/pst/menu.php`, `/pst/absensi/login`, dan (untuk fitur WA) set webhook Fonnte ke `/pst/wa-webhook/?key=<secret>`, buka `/pst/piket/` sekali untuk auto-migrasi tabel.
5. Hapus sisa berkas versi lama yang tergeletak langsung di document root (bukan di `pst/`).

## Konvensi kode

- DB: **mysqli**, selalu prepared statements (`->prepare()`/`->bind_param()`/`->get_result()`). Tidak ada PDO.
- Tabel baru: pola **auto-create** `CREATE TABLE IF NOT EXISTS` dijalankan di titik masuk PHP (lihat `penghargaan/index.php`, `app/wa.php` `wa_ensure_tables()`) — bukan migrasi manual. MySQL 8 tidak dukung `ADD COLUMN IF NOT EXISTS`, jadi kolom baru dicek lewat `SHOW COLUMNS` dulu.
- Halaman baru: set `$page_title` (+ `$head_extras` bila perlu CDN seperti `fontawesome`) lalu `include __DIR__ . '/../app/partials/_head.php'`.
- Action endpoint (AJAX) yang perlu login: guard `if (empty($_SESSION['absensi_auth'])) { http_response_code(401); ... exit; }` di awal, balas JSON `{success, message}`.
- Jangan commit perubahan tanpa diminta eksplisit oleh user.

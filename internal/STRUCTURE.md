# Struktur Project

Peta folder untuk sistem Antrean PST BPS Kabupaten Buleleng. Baca ini dulu
sebelum menambah folder baru — ada satu batasan hosting yang menentukan ke
mana sebuah berkas boleh diletakkan.

## Batasan yang membentuk struktur ini

Produksi (`satset.statsbali.id`) memakai **nginx**, yang mengabaikan total
`.htaccess`/`mod_rewrite`. Konsekuensinya:

- **Struktur folder = struktur URL.** Folder yang berisi halaman/endpoint
  aplikasi (mis. `absensi/`, `laporan/`, `cs/`) **wajib** tetap rata di root
  repo — dipindah ke dalam folder pembungkus akan membuat URL-nya 404 di
  produksi.
- Karena itu pemisahan di bawah ini terjadi di **dua level berbeda**:
  1. **Root** — dipisah dari `internal/` (lihat di bawah), tapi folder
     aplikasi sendiri tidak disarangkan lebih dalam.
  2. **Di dalam `action/`** — folder ini bukan folder ber-`index.php` (tiap
     berkas adalah endpoint AJAX yang dipanggil langsung by-filename), jadi
     amannya boleh dikelompokkan ke subfolder tanpa memengaruhi routing.

Detail lengkap & alasan teknisnya ada di [`DEPLOY-CPANEL.md`](DEPLOY-CPANEL.md).

## Root repo — dilayani web

Setiap folder berikut = satu prefix URL (`/pst/<folder>`), dan **harus**
langsung berisi `index.php` atau berkas `.php` yang diakses langsung.

| Folder | Fungsi |
|---|---|
| `index.php` | Halaman utama — QR antrean (Disabilitas/Umum) |
| `menu.php` + `menu/` | Menu navigasi petugas (shim clean-URL → `menu.php`) |
| `app/` | **Kode privat** (tidak dirender langsung): `config.php` (APP_URL/APP_BASE/DB), `db.php`, `partials/` |
| `action/` | Endpoint AJAX lintas-modul, dikelompokkan per fungsi (lihat di bawah) |
| `absensi/` | Presensi pegawai (punya `action/` sendiri, terpisah dari `action/` global) |
| `analisis/` | Analisis kepuasan (punya `action/` sendiri) |
| `cs/` | Dashboard petugas CS — daftar & atur antrean |
| `form/` | Form buku tamu publik (per jenis: umum, disabilitas, whatsapp, dst.) |
| `laporan/` | Rekap mingguan/bulanan |
| `penilaian/`, `pes/` | Halaman survei publik (diakses via token) |
| `penghargaan/` | Penilaian Petugas PST Terbaik (punya `action/` sendiri) |
| `disabilitas/`, `umum/`, `whatsapp/`, `surat/` | Form buku tamu per jenis kunjungan |
| `monitor/` | Papan monitor tampilan |
| `templat/` | Komponen HTML bersama (mis. kop surat) |
| `assets/` | Gambar & CSS statis |
| `vendor/` | Dependensi Composer (PhpSpreadsheet) |

## `action/` — dikelompokkan per fungsi

```
action/
├── pengguna/   delete_pengguna.php, delete_selected_pengguna.php,
│               download_pengguna.php, update_pengguna.php, get_penilaian.php
├── survei/     generate_token.php, generate_token_pes.php,
│               list_siap_penilaian.php, list_siap_pes.php
├── surat/      save_link_surat.php, save_link_surat_balasan.php,
│               list_surat_tindak_lanjut.php
└── antrean/    count_whatsapp_today.php, stats_today.php, reset.php
```

Berkas lama di `action/*.php` (flat) masih ada sebagai **shim 1-baris**
(`include __DIR__ . '/<subfolder>/<nama>.php';`) — jaga-jaga kalau ada
cron job atau link lama yang masih memanggil path lama. Kode baru harus
memanggil path baru (`action/pengguna/...`, dst).

## `internal/` — TIDAK dilayani web, TIDAK ikut deploy

Satu folder pembungkus untuk semua berkas pendukung yang bukan bagian dari
aplikasi yang diakses pengguna. `internal/deploy/build-zip.ps1` mengecualikan
seluruh folder ini dari paket deploy.

| Folder/berkas | Fungsi |
|---|---|
| `internal/tests/` | Test suite (`run-all.php` + suite per modul) |
| `internal/deploy/` | Skrip build zip, redirect docroot lama, `pst-deploy.zip` |
| `internal/sql_production/` | Dump/migrasi SQL produksi — **jangan pernah** di-commit isinya ke git (lihat `.gitignore`) |
| `internal/backup/` | Backup database lokal (gitignored) |
| `internal/input/` | Data input lokal, mis. `daftar_pegawai.xlsx` (gitignored) |
| `internal/DEPLOY-CPANEL.md` | Panduan deploy cPanel + konsep multi-aplikasi |
| `internal/STRUCTURE.md` | Berkas ini |

## Menjalankan test setelah reorganisasi ini

```powershell
php internal/tests/run-all.php              # semua suite
php internal/tests/run-all.php security      # cukup static SQLi scan + regression
```

`.claude/settings.json` (hook auto-test penghargaan) sudah diarahkan ke
`internal/tests/penghargaan/hook.php`.

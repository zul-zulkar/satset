# Paket Deploy — aplikasi PST ke `satset.statsbali.id/pst`

Panduan lengkap & konsep multi-aplikasi: [`../DEPLOY-CPANEL.md`](../DEPLOY-CPANEL.md).

> **Hosting ini memakai nginx** → `.htaccess` **diabaikan total**. Aplikasi
> karena itu dirancang berjalan tanpa `.htaccess`: struktur folder = struktur URL.

## Isi paket
| Berkas | Tujuan di server |
|---|---|
| `pst-deploy.zip` | Diekstrak ke dalam folder **`pst/`** di document root |
| `docroot-index.html` | (Opsional) → rename **`index.html`**, taruh di **document root** (daftar aplikasi) |
| `docroot-redirects/` | (Opsional) → isinya diunggah ke **document root**, agar link/QR lama (`/umum`, `/penilaian/?token=…`) tetap hidup lewat redirect PHP 301 |
| `build-zip.ps1` | Membangun ulang `pst-deploy.zip` |

## Struktur benar setelah ekstrak
```
pst/
├── index.php          ← HARUS ada langsung di sini (bukan di dalam public/)
├── menu.php
├── app/               ← privat: config.php, db.php, partials/
├── vendor/  templat/
├── action/ cs/ form/ laporan/ absensi/ penilaian/ ...
└── .user.ini          ← session.name unik
```

## Yang TIDAK diikutkan (sengaja — akan bisa diunduh publik di nginx)
`.git/`, `backup/`, `input/`, `sql_production/`, `tests/`, `deploy/`, `node_modules/`, `*.zip`

> Butuh berkas SQL untuk import database? Ambil dari folder `sql_production/`
> di repo lokal, import lewat phpMyAdmin, dan **jangan** unggah berkasnya.

## Langkah singkat
1. File Manager → document root `~/satset.statsbali.id/` → buat folder `pst`.
2. Masuk `pst/` → Upload `pst-deploy.zip` → **Extract**.
3. Edit `pst/app/config.php` → `define('ENV', 'production');` + cek kredensial DB.
4. (Opsional) `docroot-index.html` → `index.html` di document root.
5. (Opsional) unggah isi `docroot-redirects/` ke document root.
6. Buka `https://satset.statsbali.id/pst`.
7. **Hapus** sisa berkas aplikasi lama yang masih langsung di document root
   (`app/`, `public/`, `vendor/`, …) — kalau tidak, semuanya terekspos.

## Membuat ulang zip
```powershell
.\deploy\build-zip.ps1
```

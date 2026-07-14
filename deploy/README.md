# Paket Deploy — aplikasi PST ke `satset.statsbali.id/pst`

Ringkas. Panduan lengkap & konsep multi-aplikasi ada di [`../DEPLOY-CPANEL.md`](../DEPLOY-CPANEL.md).

## Isi paket
| Berkas | Tujuan di server |
|---|---|
| `pst-deploy.zip` | Diekstrak ke dalam folder **`pst/`** di document root subdomain |
| `docroot.htaccess` | (Opsional) diganti nama jadi **`.htaccess`** dan ditaruh di **document root** (parent dari `pst/`) untuk menjaga link/QR lama |

## Yang IKUT di dalam `pst-deploy.zip`
```
.htaccess            ← funnel ke public/
app/                 ← config, db, partials (privat)
public/              ← folder web (dilayani)
vendor/              ← dependency (sudah disertakan, tak perlu composer di server)
templat/             ← kop cetak (dipakai halaman detail, privat)
sql_production/      ← file SQL untuk import DB (privat)
composer.json, composer.lock
```

## Yang TIDAK diikutkan (sengaja)
`.git/`, `.claude/`, `input/`, `backup/`, `tests/`, `node_modules/`, `*.zip`, folder `deploy/` ini sendiri.

## Langkah singkat
1. **File Manager** → document root subdomain → buat folder `pst`.
2. Unggah `pst-deploy.zip` ke `pst/` → **Extract**.
3. Edit `pst/app/config.php`: set `ENV = 'production'` + isi kredensial DB.
4. **MySQL Databases**: buat DB + user (All Privileges) → **phpMyAdmin** import dari `pst/sql_production/`.
   (Untuk pemasangan baru, mulai dari `dbsatset_20260625.sql`, lalu jalankan file `migration_*.sql` bila perlu.)
5. (Opsional) taruh `docroot.htaccess` → `.htaccess` di document root agar link lama tetap hidup.
6. Buka `https://satset.statsbali.id/pst`.

## Membuat ulang zip (dari mesin lokal)
Jalankan skrip `deploy/build-zip.ps1`, atau perintah manual di root repo:
```powershell
$items = '.htaccess','app','public','vendor','templat','sql_production','composer.json','composer.lock'
Compress-Archive -Path $items -DestinationPath ..\pst-deploy.zip -Force
```
Catatan: `input/`, `backup/`, `.git/` otomatis tidak ikut karena tidak masuk daftar `$items`.

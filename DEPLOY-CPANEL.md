# Hosting Banyak Aplikasi dalam Satu Domain (cPanel)

Panduan men-deploy aplikasi ini ke **`satset.statsbali.id/pst`**, dan menambah
aplikasi lain di **`satset.statsbali.id/<nama>`** tanpa saling mengganggu.

---

## 1. Konsep

Satu domain/subdomain di cPanel punya **satu document root** (folder web).
Untuk menampung banyak aplikasi, tiap aplikasi diberi **satu folder** di bawah
document root:

```
satset.statsbali.id/pst       → aplikasi ini (Antrean/PST BPS Buleleng)
satset.statsbali.id/simpeg    → aplikasi lain
satset.statsbali.id/apapun    → cukup tambah folder baru
```

Tiap folder berisi **repo lengkap** (struktur `app/` + `public/`). Sebuah file
`.htaccess` "funnel" di akar folder membuat Apache menyajikan isi `public/`
**tanpa** menampilkan `/public` di URL. Jadi:

* URL tetap rapi: `https://satset.statsbali.id/pst/menu.php`
* Kode privat (`app/`, `vendor/`, dll.) tidak bisa diakses dari browser
* Menambah aplikasi = cukup menyalin satu folder. Tidak ada konfigurasi global
  yang perlu diubah, sehingga **antar aplikasi tidak saling mengganggu**.

---

## 2. Struktur folder di hosting

Misal document root subdomain adalah `~/satset.statsbali.id`:

```
~/satset.statsbali.id/                ← DOCUMENT ROOT (web root subdomain)
│
├── pst/                              ← APLIKASI INI  → URL /pst
│   ├── .htaccess                     ← funnel: alirkan semua ke public/
│   ├── app/                          ← PRIVAT (config, db, partials) — diblokir HTTP
│   │   ├── .htaccess                 ←   Require all denied
│   │   ├── config.php                ←   ENV, URL, kredensial DB
│   │   ├── db.php
│   │   └── partials/
│   ├── public/                       ← satu-satunya folder yang dilayani web
│   │   ├── .htaccess
│   │   ├── .user.ini                 ← nama sesi unik (anti-bentrok antar app)
│   │   ├── index.php
│   │   ├── menu.php
│   │   └── ... (action/, cs/, form/, laporan/, absensi/, dst.)
│   └── vendor/                       ← PRIVAT — diblokir HTTP
│
└── simpeg/                           ← APLIKASI LAIN → URL /simpeg
    ├── .htaccess
    ├── app/  public/  vendor/ ...
```

> Folder `app/`, `vendor/`, `tests/`, `templat/`, `sql_production/`, `input/`,
> `backup/` **tidak** ikut dilayani: funnel hanya menyajikan `public/`, dan
> tiap folder privat juga punya `.htaccess` "Require all denied" sebagai lapis
> kedua.

---

## 3. Bagian A — Deploy aplikasi ini ke `/pst`

### Langkah 1 — Temukan document root subdomain
cPanel → **Domains** → cari `satset.statsbali.id` → lihat kolom **Document Root**
(mis. `/home/USER/satset.statsbali.id` atau `/home/USER/public_html/...`).

### Langkah 2 — Buat folder aplikasi
cPanel → **File Manager** → masuk ke document root → **+ Folder** → beri nama `pst`.

### Langkah 3 — Unggah berkas aplikasi
Unggah isi repo ini ke dalam `pst/` sehingga menjadi:
`pst/.htaccess`, `pst/app/`, `pst/public/`, `pst/composer.json`, `pst/composer.lock`.

Cara termudah: kompres jadi `.zip`, unggah ke `pst/`, lalu **Extract** di File Manager.

**Jangan diunggah** (data lokal / tidak perlu di web): `.git/`, `input/`,
`backup/`, `node_modules/`, file `*.zip`. (Folder `tests/`, `templat/`,
`sql_production/` aman karena sudah diblokir, tapi boleh juga tidak diunggah.)

### Langkah 4 — Pasang dependency (`vendor/`)
`vendor/` tidak ikut Git. Dua pilihan:
* **Terminal cPanel:** `cd ~/satset.statsbali.id/pst && composer install --no-dev`
* **Tanpa Terminal:** jalankan `composer install` di komputer, lalu unggah folder
  `vendor/` hasilnya ke `pst/vendor/`.

### Langkah 5 — Buat database
cPanel → **MySQL® Databases**:
1. Buat database, mis. `USER_dbsatset`.
2. Buat user DB + password, mis. `USER_satset`.
3. **Add User To Database** → beri **All Privileges**.
4. cPanel → **phpMyAdmin** → pilih database itu → **Import** file SQL dari
   folder `sql_production/`.

### Langkah 6 — Atur konfigurasi aplikasi
Edit `pst/app/config.php`:
```php
define('ENV', 'production');          // ← aktifkan mode produksi
```
Bagian `production` sudah disetel untuk `/pst`:
```php
'production' => [
    'url'  => 'https://satset.statsbali.id/pst',
    'base' => '/pst',                 // ← samakan dgn nama folder
],
```
Lalu isi kredensial DB `production` (host biasanya `localhost`) sesuai Langkah 5.

### Langkah 7 — Pilih versi PHP (jika perlu)
cPanel → **MultiPHP Manager** → set versi PHP (mis. 8.1+) untuk domain ini.

### Langkah 8 — Uji
Buka `https://satset.statsbali.id/pst` → halaman **Buku Tamu** muncul.
Cek juga `/pst/menu.php`, `/pst/laporan/bulan`, `/pst/absensi/login`.

---

## 4. Bagian B — Menambah aplikasi baru (mis. `/simpeg`)

1. **File Manager** → di document root, buat folder baru `simpeg`.
2. Unggah aplikasi ke `simpeg/`. Jika aplikasi memakai pola yang sama
   (`app/` + `public/` + funnel `.htaccess`), tinggal salin polanya.
   Jika aplikasi lain belum berstruktur `public/`, cukup taruh file webnya di
   `simpeg/` langsung.
3. Set base/URL aplikasi itu ke `/simpeg`.
4. Buat **database + user DB terpisah** untuk aplikasi itu.
5. Beri **nama sesi unik** (lihat bagian 5 poin 4).
6. Selesai — `/pst` sama sekali tidak terpengaruh.

---

## 5. Checklist "tidak saling mengganggu"

1. **Folder terpisah.** Tiap aplikasi di foldernya sendiri. Funnel `.htaccess`
   memakai path **relatif tanpa `RewriteBase`**, jadi aturannya tidak pernah
   "bocor" ke folder aplikasi lain.
2. **Tidak ada `.htaccess` global di document root.** Biarkan document root
   bersih (tanpa aturan rewrite). Semua aturan tinggal di dalam folder
   masing-masing aplikasi. (Lihat juga bagian 6 soal kompatibilitas link lama.)
3. **Database terpisah.** Satu database + satu user DB per aplikasi. Jangan
   berbagi tabel antar aplikasi.
4. **Sesi PHP terpisah.** Tiap aplikasi punya `public/.user.ini` dengan
   `session.name` **unik** (aplikasi ini memakai `SATSETSID`). Tanpa ini, dua
   aplikasi di domain yang sama berbagi cookie `PHPSESSID` dan bisa saling
   menimpa login (mis. fitur absensi). Untuk aplikasi baru, ganti nilainya.
5. **Folder privat tidak bisa diakses HTTP.** `app/` dan `vendor/` terlindungi
   ganda: funnel hanya melayani `public/`, dan `app/.htaccess` memblokir akses
   langsung. Sudah diuji: `/(app)/config.php` → **403 Forbidden**.
6. **Versi PHP per folder.** Bila aplikasi butuh versi PHP berbeda, atur lewat
   **MultiPHP Manager** per domain/subfolder.

---

## 6. Transisi: menjaga link lama tetap hidup (opsional)

Sebelumnya aplikasi berada di **root** (`satset.statsbali.id/...`). Setelah
pindah ke `/pst`, link/QR lama seperti `satset.statsbali.id/umum`,
`/disabilitas`, atau link survei `/penilaian/?token=...` akan **404**.

Bila masih ada QR atau link yang tersebar, pasang **satu** `.htaccess` di
document root untuk mengarahkan rute lama ke `/pst` (hapus nanti bila sudah
tidak diperlukan, atau bila menambah aplikasi yang namanya bentrok):

```apache
# ~/satset.statsbali.id/.htaccess  — redirect kompatibilitas (opsional, sementara)
<IfModule mod_rewrite.c>
    RewriteEngine On
    # Hanya arahkan rute milik aplikasi PST yang dulu di root:
    RewriteRule ^(umum|disabilitas|whatsapp|surat|penilaian|pes|cs|laporan|penghargaan|absensi|menu\.php)(/.*)?$ /pst/$1$2 [R=301,L]
</IfModule>
```

Alternatif paling bersih: **cetak ulang QR** agar menunjuk `/pst/...`, lalu
biarkan document root tanpa `.htaccess`.

---

## 7. Alternatif paling rapi (jika ada Terminal/SSH): symlink

Bila hosting menyediakan **Terminal/SSH**, kode privat bisa ditaruh
**sepenuhnya di luar web root**:

```bash
# Taruh repo di luar document root:
~/apps/pst/          ← berisi app/ public/ vendor/ ...

# Arahkan URL /pst ke folder public/ lewat symlink:
ln -s ~/apps/pst/public ~/satset.statsbali.id/pst
```

Dengan cara ini `app/` benar-benar tak tersentuh web, dan **funnel tidak
diperlukan** (URL `/pst` langsung = isi `public/`). Pastikan
`Options +FollowSymLinks` aktif (sudah ada di `public/.htaccess`).

---

## 8. Catatan pengembangan lokal (XAMPP)

* Di `app/config.php` biarkan `ENV` = `local`.
* Akses lewat `http://<ip-lokal>/satset` — funnel yang sama membuatnya jalan
  **tanpa** `/public` di URL.
* File `.user.ini` diabaikan di XAMPP (mod_php) — wajar, dan tidak masalah
  karena bentrok sesi hanya relevan saat banyak aplikasi satu domain.

---

## 9. Ringkasan berkas yang mengatur URL/routing

| Berkas | Fungsi |
|---|---|
| `app/config.php` | Sumber tunggal `APP_URL` & `APP_BASE` (ENV `local`/`production`). Ubah `base` untuk pindah path. |
| `.htaccess` (akar folder) | **Funnel** — alirkan semua permintaan ke `public/` tanpa `/public` di URL. Path-independen (tanpa `RewriteBase`). |
| `public/.htaccess` | Sajikan clean-URL folder (mis. `/laporan/bulan`) langsung dari `index.php`, dan blokir `.user.ini`. |
| `public/.user.ini` | `session.name` unik per aplikasi (anti-bentrok sesi). |
| `app/.htaccess` | Blokir akses HTTP ke kode privat. |

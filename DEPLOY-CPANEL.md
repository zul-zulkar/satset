# Hosting Banyak Aplikasi dalam Satu Domain (cPanel + nginx)

Panduan men-deploy aplikasi ini ke **`satset.statsbali.id/pst`**, dan menambah
aplikasi lain di **`satset.statsbali.id/<nama>`** tanpa saling mengganggu.

---

## 0. Fakta penting tentang hosting ini

Hosting `satset.statsbali.id` melayani PHP lewat **nginx**, **bukan Apache**.
Ini sudah diuji langsung (menaruh baris ngawur di `.htaccess` tidak memicu
error 500 → berkas itu tidak pernah dibaca).

Konsekuensinya:

| Fitur | Status di hosting ini |
|---|---|
| `.htaccess` (rewrite, `DirectoryIndex`, `Require all denied`) | ❌ **Diabaikan total** |
| `mod_rewrite` / funnel ke `public/` | ❌ Tidak bisa dipakai |
| Folder berisi `index.php` | ✅ Dilayani (nginx `index`) |
| `.user.ini` (mis. `session.name`) | ✅ Berlaku (fitur PHP-FPM, bukan Apache) |

**Karena itu aplikasi dirancang agar berjalan tanpa `.htaccess` sama sekali:**
struktur folder = struktur URL.

> ⚠️ **Implikasi keamanan:** tanpa `.htaccess`, berkas **non-PHP** apa pun yang
> ikut terunggah bisa **diunduh publik**. Berkas `.php` aman karena dieksekusi
> (bukan ditampilkan) — sama seperti `wp-config.php` pada WordPress.
> Maka `sql_production/`, `backup/`, `input/`, dan `.git/` **tidak boleh**
> diunggah. Paket `deploy/pst-deploy.zip` sudah otomatis mengecualikannya.

---

## 1. Konsep multi-aplikasi

Satu subdomain = satu **document root**. Tiap aplikasi = **satu folder** di
bawahnya, dan nama folder itulah yang menjadi URL-nya:

```
~/satset.statsbali.id/            ← DOCUMENT ROOT (wadah, bukan aplikasi)
├── index.html                    ← halaman daftar aplikasi
├── pst/                          ← APLIKASI INI          → /pst
│   ├── index.php                 ←   halaman utama (Buku Tamu)
│   ├── menu.php
│   ├── action/ cs/ form/ laporan/ absensi/ penilaian/ ...
│   ├── app/                      ←   KODE PRIVAT: config.php, db.php, partials/
│   ├── templat/
│   ├── vendor/
│   └── .user.ini                 ←   nama sesi unik (anti-bentrok antar app)
└── simpeg/                       ← APLIKASI LAIN         → /simpeg
```

Tiap aplikasi **mandiri penuh** di foldernya sendiri: kodenya, `app/`-nya,
`vendor/`-nya, dan sesinya. Menambah aplikasi = menambah satu folder. Tidak ada
konfigurasi global yang perlu disentuh → **tidak mungkin saling mengganggu**.

**Document Root wajib menunjuk ke wadah** (`satset.statsbali.id`), **bukan** ke
folder salah satu aplikasi. Kalau docroot menunjuk ke aplikasi, domain hanya
bisa melayani satu aplikasi.

---

## 2. Deploy aplikasi ini ke `/pst`

### Langkah 1 — Siapkan folder
cPanel → **File Manager** → masuk ke document root `~/satset.statsbali.id/` →
**+ Folder** → beri nama **`pst`**.

### Langkah 2 — Unggah & ekstrak
Masuk ke `pst/` → **Upload** `deploy/pst-deploy.zip` → klik-kanan → **Extract**.

Hasil yang benar: **`pst/index.php` ada langsung di dalam `pst/`** (bukan di
dalam subfolder `public/`), berdampingan dengan `app/`, `vendor/`, `templat/`.

> Aktifkan **Settings → Show Hidden Files (dotfiles)** (tombol Settings di pojok
> kanan atas File Manager) agar `.user.ini` terlihat.

### Langkah 3 — Aktifkan mode produksi
Edit **`pst/app/config.php`** baris 10:
```php
define('ENV', 'production');
```
Blok `production` sudah menunjuk ke `/pst`:
```php
'production' => [
    'url'  => 'https://satset.statsbali.id/pst',
    'base' => '/pst',          // ← harus sama dengan nama folder
],
```
Pastikan kredensial database di blok `production` sudah benar.

### Langkah 4 — Halaman depan domain (opsional)
Unggah `deploy/docroot-index.html` ke `~/satset.statsbali.id/` dan rename
menjadi **`index.html`** — agar `satset.statsbali.id/` menampilkan daftar
aplikasi, bukan error.

### Langkah 5 — Uji
Buka **`https://satset.statsbali.id/pst`** → halaman **Buku Tamu**.
Cek juga `/pst/menu.php`, `/pst/laporan/bulan`, `/pst/absensi/login`.

### Langkah 6 — Bersihkan sisa lama ⚠️
Hapus berkas aplikasi **versi lama** yang masih tergeletak **langsung di
document root** (`app/`, `public/`, `vendor/`, `templat/`, `composer.*`, dst.).
Kalau dibiarkan, semuanya terekspos di `/app`, `/public`, `/vendor`.

Setelah bersih, document root hanya berisi: `index.html` dan `pst/`.

---

## 3. Menjaga link & QR lama tetap hidup (opsional)

Dulu aplikasi berada di root, jadi link/QR lama seperti
`satset.statsbali.id/umum` atau `satset.statsbali.id/penilaian/?token=...`
kini **404**.

Karena nginx mengabaikan `.htaccess`, redirect harus lewat **PHP**. Sudah
disiapkan di **`deploy/docroot-redirects/`** — berisi folder `umum/`,
`disabilitas/`, `penilaian/`, `pes/`, dll., masing-masing dengan `index.php`
yang me-redirect 301 ke `/pst/...` **beserta query string** (jadi link survei
bertoken tetap berfungsi).

Cara pakai: unggah isi `docroot-redirects/` ke **document root**. Hapus
folder-folder itu bila link lama sudah tidak dipakai (atau bila nama folder
bentrok dengan aplikasi baru).

---

## 4. Menambah aplikasi baru (mis. `/simpeg`)

1. Buat folder `simpeg` di document root.
2. Unggah aplikasi ke situ, dengan **`index.php` langsung di `simpeg/`**.
3. Set base/URL aplikasi itu ke `/simpeg`.
4. Buat **database + user DB terpisah**.
5. Beri **`session.name` unik** di `simpeg/.user.ini` (lihat bagian 5).
6. Tambahkan kartunya di `index.html` document root.

`/pst` sama sekali tidak terpengaruh.

---

## 5. Checklist "tidak saling mengganggu"

1. **Folder terpisah & mandiri.** Tiap aplikasi membawa `app/` dan `vendor/`-nya
   sendiri. Tidak ada berkas yang dibagi pakai.
2. **Tidak ada konfigurasi global.** Tidak ada rewrite/`.htaccess` di document
   root yang bisa "bocor" ke aplikasi lain.
3. **Database terpisah.** Satu database + satu user DB per aplikasi.
4. **Sesi terpisah.** Tiap aplikasi punya `.user.ini` dengan `session.name`
   **unik** (aplikasi ini: `SATSETSID`). Tanpa ini, dua aplikasi di domain yang
   sama berbagi cookie `PHPSESSID` dan bisa saling menimpa login (mis. absensi).
   Untuk aplikasi baru, **wajib ganti** nilainya.
5. **Jangan unggah berkas sensitif.** Di nginx tidak ada proteksi `.htaccess`:
   `sql_production/`, `backup/`, `input/`, `.git/` harus tetap di luar server.

---

## 6. Pengembangan lokal (XAMPP)

* `app/config.php` → biarkan `ENV` = `local`.
* Akses `http://<ip-lokal>/satset` — jalan apa adanya, **tanpa** perlu
  `.htaccess`, karena struktur folder = struktur URL (sama seperti di nginx).
* `.htaccess` di root hanya penyempurna untuk Apache (matikan listing folder,
  blokir `.user.ini`). Di produksi berkas ini diabaikan — dan aplikasi memang
  tidak membutuhkannya.

---

## 7. Berkas yang mengatur URL

| Berkas | Fungsi |
|---|---|
| `app/config.php` | Sumber tunggal `APP_URL` & `APP_BASE`. Ubah `base` agar sama dengan nama folder aplikasi. |
| `.user.ini` | `session.name` unik per aplikasi. |
| `.htaccess` | Hanya untuk Apache/lokal. **Tidak dipakai di produksi (nginx).** |

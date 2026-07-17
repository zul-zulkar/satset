# Membangun paket deploy bersih: internal/deploy/pst-deploy.zip
#
# Struktur aplikasi sudah "rata": folder root = folder yang dilayani web,
# kode privat ada di subfolder app/. Tidak ada .htaccess/mod_rewrite yang
# dibutuhkan — hosting produksi memakai nginx yang mengabaikan .htaccess.
#
# Semua berkas non-aplikasi (tests, deploy, sql_production, backup, input,
# dokumentasi) dikumpulkan di satu folder internal/ — dikecualikan total dari
# paket deploy di bawah, sehingga aman meski isinya sensitif (dump DB, dll).
#
# PENTING (nginx): karena .htaccess diabaikan di produksi, berkas NON-PHP
# apa pun yang ikut terunggah BISA DIUNDUH publik. Karena itu internal/ dan
# .git WAJIB dikecualikan.
#
# Memakai System.IO.Compression dengan separator "/" agar ekstraksi di
# cPanel/Linux benar (Compress-Archive PS 5.1 memakai "\").

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$repo = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)   # folder root repo (dua level di atas internal/deploy/)

# JANGAN diunggah ke server (sensitif / tidak perlu / bisa diunduh publik di nginx)
$exclude = @(
    '.git',            # seluruh riwayat & source — fatal bila bocor
    '.claude',
    'internal',        # tests, deploy (folder ini sendiri), sql_production, backup, input, docs
    'node_modules',
    '.gitignore'
)

$items = Get-ChildItem -LiteralPath $repo -Force |
    Where-Object { $exclude -notcontains $_.Name -and $_.Extension -ne '.zip' }

# Kumpulkan semua file (termasuk dotfile lewat -Force)
$files = New-Object System.Collections.Generic.List[string]
foreach ($it in $items) {
    if ($it.PSIsContainer) {
        Get-ChildItem -LiteralPath $it.FullName -Recurse -File -Force |
            ForEach-Object { $files.Add($_.FullName) }
    } else {
        $files.Add($it.FullName)
    }
}

$dest = Join-Path $repo 'internal\deploy\pst-deploy.zip'
if (Test-Path $dest) { Remove-Item $dest -Force }

$zip = [System.IO.Compression.ZipFile]::Open($dest, 'Create')
try {
    $prefix = $repo.TrimEnd('\') + '\'
    foreach ($f in $files) {
        $rel = $f.Substring($prefix.Length) -replace '\\', '/'   # separator "/"
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zip, $f, $rel, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally {
    $zip.Dispose()
}

$mb = [math]::Round((Get-Item $dest).Length / 1MB, 1)
Write-Host "OK -> $dest ($mb MB, $($files.Count) file)"
Write-Host "Isi top-level:"
$items | ForEach-Object { "  - $($_.Name)" }

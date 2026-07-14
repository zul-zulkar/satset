# Membangun paket deploy bersih: deploy/pst-deploy.zip
# Memakai System.IO.Compression dengan separator "/" agar ekstraksi di
# cPanel/Linux benar (Compress-Archive PS 5.1 memakai "\" yang bisa merusak
# struktur folder saat di-unzip di server).
#
# Jalankan dari mana saja; skrip pindah ke root repo secara otomatis.

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$repo = Split-Path -Parent $PSScriptRoot   # folder root repo (parent dari deploy/)

# Hanya item ini yang masuk paket (input/, backup/, .git/, tests/, *.zip tidak ikut)
$items = @(
    '.htaccess',
    'app',
    'public',
    'vendor',
    'templat',
    'sql_production',
    'composer.json',
    'composer.lock'
)

$missing = $items | Where-Object { -not (Test-Path (Join-Path $repo $_)) }
if ($missing) { throw "Item tidak ditemukan: $($missing -join ', ')" }

# Kumpulkan semua file (termasuk dotfile lewat -Force)
$files = New-Object System.Collections.Generic.List[string]
foreach ($it in $items) {
    $p = Join-Path $repo $it
    if (Test-Path -LiteralPath $p -PathType Leaf) {
        $files.Add((Resolve-Path -LiteralPath $p).Path)
    } else {
        Get-ChildItem -LiteralPath $p -Recurse -File -Force |
            ForEach-Object { $files.Add($_.FullName) }
    }
}

$dest = Join-Path $repo 'deploy\pst-deploy.zip'
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
Write-Host "OK -> $dest ($mb MB, $($files.Count) file, separator '/')"

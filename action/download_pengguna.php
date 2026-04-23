<?php
require_once '../db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$bulan  = $_GET['bulan']  ?? date('m');
$tahun  = $_GET['tahun']  ?? date('Y');
$format = $_GET['format'] ?? 'excel';

$stmt = $mysqli->prepare("
    SELECT a.*,
           p.id          AS penilaian_id,
           p.tanggal     AS penilaian_tanggal,
           p.q1,  p.q2,  p.q3,  p.q4,  p.q5,  p.q6,  p.q7,  p.q8,
           p.q9,  p.q10, p.q11, p.q12, p.q13, p.q14, p.q15, p.q16,
           p.catatan     AS penilaian_catatan
    FROM antrian a
    LEFT JOIN penilaian p ON p.antrian_id = a.id
    WHERE MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?
    ORDER BY a.tanggal DESC, a.id DESC
");
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();

$bulanNama = date('F', mktime(0, 0, 0, $bulan, 1));
$filename  = "Daftar_Pengguna_{$bulanNama}_{$tahun}";

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Daftar Pengguna');

// ── Kolom A–AK (37 kolom) ───────────────────────────────────────────────────
$headers = [
    // Data tamu (A–Q)
    'No',
    'Jenis',
    'Nomor Urut',
    'Tanggal',
    'Nama Lengkap',
    'Email',
    'Telepon',
    'Instansi / Organisasi',
    'Jenis Kelamin',
    'Jumlah Orang',
    'Keperluan',
    'Pendidikan',
    'Kelompok Umur',
    'Pekerjaan',
    'Pemanfaatan Data',
    'Data Dibutuhkan',
    'Jenis Pelayanan',
    // Penilaian pelayanan (R–AK)
    'Status Penilaian',
    'Tgl. Penilaian',
    'Rata-rata IKM',
    'P1 - Ketersediaan Informasi',
    'P2 - Persyaratan Pelayanan',
    'P3 - Prosedur Pelayanan',
    'P4 - Waktu Penyelesaian',
    'P5 - Biaya Pelayanan',
    'P6 - Produk Pelayanan',
    'P7 - Sarana & Prasarana',
    'P8 - Aksesibilitas Data',
    'P9 - Responsivitas Petugas',
    'P10 - Kejelasan Informasi',
    'P11 - Kemudahan Pengaduan',
    'P12 - Non-diskriminasi',
    'P13 - Anti-kecurangan',
    'P14 - Anti-gratifikasi',
    'P15 - Anti-pungli',
    'P16 - Anti-percaloan',
    'Catatan & Saran',
];

$totalCols    = count($headers); // 37
$lastColLetter = Coordinate::stringFromColumnIndex($totalCols); // AK

// ── Tulis header ────────────────────────────────────────────────────────────
foreach ($headers as $i => $header) {
    $col = Coordinate::stringFromColumnIndex($i + 1);
    $sheet->setCellValue($col . '1', $header);
}

// ── Style header ────────────────────────────────────────────────────────────
$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
        'wrapText'   => true,
    ],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
];
$sheet->getStyle('A1:' . $lastColLetter . '1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(36);

// ── Lebar kolom ─────────────────────────────────────────────────────────────
$widths = [
    5,  // A  No
    12, // B  Jenis
    12, // C  Nomor Urut
    14, // D  Tanggal
    25, // E  Nama Lengkap
    28, // F  Email
    15, // G  Telepon
    28, // H  Instansi
    14, // I  Jenis Kelamin
    13, // J  Jumlah Orang
    30, // K  Keperluan
    14, // L  Pendidikan
    16, // M  Kelompok Umur
    22, // N  Pekerjaan
    25, // O  Pemanfaatan Data
    30, // P  Data Dibutuhkan
    22, // Q  Jenis Pelayanan
    16, // R  Status Penilaian
    16, // S  Tgl. Penilaian
    14, // T  Rata-rata IKM
    10, 10, 10, 10, 10, 10, 10, 10, // U–AB  P1–P8
    10, 10, 10, 10, 10, 10, 10, 10, // AC–AJ P9–P16
    35, // AK Catatan & Saran
];
foreach ($widths as $i => $w) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($w);
}

// Freeze header row
$sheet->freezePane('A2');

// ── Warna pemisah: kolom penilaian diberi header biru tua ───────────────────
$penilaianHeaderStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
        'wrapText'   => true,
    ],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
];
$sheet->getStyle('R1:' . $lastColLetter . '1')->applyFromArray($penilaianHeaderStyle);

// ── Isi data ─────────────────────────────────────────────────────────────────
$row = 2;
$no  = 1;
while ($data = $result->fetch_assoc()) {
    $hasPenilaian = !empty($data['penilaian_id']);

    // Hitung rata-rata IKM
    $avgIkm = '';
    if ($hasPenilaian) {
        $sum = 0;
        $cnt = 0;
        for ($q = 1; $q <= 16; $q++) {
            $v = intval($data['q' . $q] ?? 0);
            if ($v > 0) { $sum += $v; $cnt++; }
        }
        $avgIkm = $cnt > 0 ? round($sum / $cnt, 2) : '';
    }

    // Data tamu (A–Q)
    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $data['jenis']             ?? '');
    $sheet->setCellValue('C' . $row, $data['nomor']             ?? '');
    $sheet->setCellValue('D' . $row, $data['tanggal']           ?? '');
    $sheet->setCellValue('E' . $row, $data['nama']              ?? '');
    $sheet->setCellValue('F' . $row, $data['email']             ?? '');
    $sheet->setCellValue('G' . $row, $data['telepon']           ?? '');
    $sheet->setCellValue('H' . $row, $data['instansi']          ?? '');
    $sheet->setCellValue('I' . $row, $data['jk']                ?? '');
    $sheet->setCellValue('J' . $row, $data['jumlah_orang']      ?? '');
    $sheet->setCellValue('K' . $row, $data['keperluan']         ?? '');
    $sheet->setCellValue('L' . $row, $data['pendidikan']        ?? '');
    $sheet->setCellValue('M' . $row, $data['kelompok_umur']     ?? '');
    $sheet->setCellValue('N' . $row, $data['pekerjaan']         ?? '');
    $sheet->setCellValue('O' . $row, $data['pemanfaatan_data']  ?? '');
    $sheet->setCellValue('P' . $row, $data['data_dibutuhkan']   ?? '');
    $sheet->setCellValue('Q' . $row, $data['jenis_pelayanan']   ?? '');

    // Penilaian (R–AK)
    $sheet->setCellValue('R' . $row, $hasPenilaian ? 'Sudah' : 'Belum');
    $sheet->setCellValue('S' . $row, $hasPenilaian ? ($data['penilaian_tanggal'] ?? '') : '');
    $sheet->setCellValue('T' . $row, $avgIkm);

    for ($q = 1; $q <= 16; $q++) {
        // P1=U(col21), P2=V(col22), … P16=AJ(col36)
        $colLetter = Coordinate::stringFromColumnIndex(20 + $q);
        $sheet->setCellValue($colLetter . $row, $hasPenilaian ? ($data['q' . $q] ?? '') : '');
    }
    $sheet->setCellValue('AK' . $row, $hasPenilaian ? ($data['penilaian_catatan'] ?? '') : '');

    $row++;
}

// ── Style data baris ─────────────────────────────────────────────────────────
$lastRow = $row - 1;
if ($lastRow > 1) {
    $sheet->getStyle('A1:' . $lastColLetter . $lastRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
    ]);

    for ($i = 2; $i <= $lastRow; $i++) {
        // Warna baris selang-seling
        $baseColor = ($i % 2 === 0) ? 'F2F2F2' : 'FFFFFF';
        $sheet->getStyle('A' . $i . ':Q' . $i)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($baseColor);

        // Kolom penilaian: latar lebih terang
        $pColor = ($i % 2 === 0) ? 'EFF6FF' : 'F8FAFF';
        $sheet->getStyle('R' . $i . ':' . $lastColLetter . $i)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($pColor);

        // Warna sel Status Penilaian
        $status = $sheet->getCell('R' . $i)->getValue();
        if ($status === 'Sudah') {
            $sheet->getStyle('R' . $i)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCFCE7');
            $sheet->getStyle('R' . $i)->getFont()->setBold(true)->getColor()->setRGB('15803D');
        } else {
            $sheet->getStyle('R' . $i)->getFont()->getColor()->setRGB('9CA3AF');
        }

        // Warna sel Rata-rata IKM berdasarkan nilai
        $avg = $sheet->getCell('T' . $i)->getValue();
        if ($avg !== '') {
            $avgF = floatval($avg);
            $ikmColor = $avgF >= 8.5 ? 'DCFCE7'
                      : ($avgF >= 7   ? 'D1FAE5'
                      : ($avgF >= 5   ? 'FEF9C3'
                      : ($avgF >= 3   ? 'FFEDD5'
                      :                'FEE2E2')));
            $sheet->getStyle('T' . $i)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($ikmColor);
            $sheet->getStyle('T' . $i)->getFont()->setBold(true);
            $sheet->getStyle('T' . $i)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
}

// ── Output ───────────────────────────────────────────────────────────────────
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: max-age=0');
    $writer = new CsvWriter($spreadsheet);
    $writer->setDelimiter(',');
    $writer->setEnclosure('"');
    $writer->setLineEnding("\r\n");
    $writer->setSheetIndex(0);
    $writer->save('php://output');
} else {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

$stmt->close();
$mysqli->close();
exit;

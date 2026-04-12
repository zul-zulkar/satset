<?php
require_once '../db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$bulan  = $_GET['bulan']  ?? date('m');
$tahun  = $_GET['tahun']  ?? date('Y');
$format = $_GET['format'] ?? 'excel';

$stmt = $mysqli->prepare("SELECT * FROM antrian WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? ORDER BY tanggal DESC, id DESC");
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();

$bulanNama = date('F', mktime(0, 0, 0, $bulan, 1));
$filename  = "Daftar_Pengguna_{$bulanNama}_{$tahun}";

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Daftar Pengguna');

// Kolom A–P
$headers = [
    'No',
    'Jenis',
    'Nomor Urut',
    'Tanggal',
    'Nama Lengkap',
    'Email',
    'Telepon',
    'Instansi / Organisasi',
    'Jenis Kelamin',
    // Kunjungan langsung (umum / disabilitas)
    'Jumlah Orang',
    'Keperluan',
    // WhatsApp
    'Pendidikan',
    'Kelompok Umur',
    'Pekerjaan',
    'Pemanfaatan Data',
    'Data Dibutuhkan',
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
];
$sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

$widths  = [5, 12, 12, 14, 25, 28, 15, 28, 14, 13, 30, 14, 16, 22, 25, 30];
$letters = range('A', 'P');
foreach ($letters as $i => $letter) {
    $sheet->getColumnDimension($letter)->setWidth($widths[$i]);
}

$row = 2;
$no  = 1;
while ($data = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $data['jenis']            ?? '');
    $sheet->setCellValue('C' . $row, $data['nomor']            ?? '');
    $sheet->setCellValue('D' . $row, $data['tanggal']          ?? '');
    $sheet->setCellValue('E' . $row, $data['nama']             ?? '');
    $sheet->setCellValue('F' . $row, $data['email']            ?? '');
    $sheet->setCellValue('G' . $row, $data['telepon']          ?? '');
    $sheet->setCellValue('H' . $row, $data['instansi']         ?? '');
    $sheet->setCellValue('I' . $row, $data['jk']               ?? '');
    // Kunjungan langsung
    $sheet->setCellValue('J' . $row, $data['jumlah_orang']     ?? '');
    $sheet->setCellValue('K' . $row, $data['keperluan']        ?? '');
    // WhatsApp
    $sheet->setCellValue('L' . $row, $data['pendidikan']       ?? '');
    $sheet->setCellValue('M' . $row, $data['kelompok_umur']    ?? '');
    $sheet->setCellValue('N' . $row, $data['pekerjaan']        ?? '');
    $sheet->setCellValue('O' . $row, $data['pemanfaatan_data'] ?? '');
    $sheet->setCellValue('P' . $row, $data['data_dibutuhkan']  ?? '');
    $row++;
}

$lastRow = $row - 1;
if ($lastRow > 1) {
    $dataStyle = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]];
    $sheet->getStyle('A1:P' . $lastRow)->applyFromArray($dataStyle);
    for ($i = 2; $i <= $lastRow; $i++) {
        if ($i % 2 === 0) {
            $sheet->getStyle('A' . $i . ':P' . $i)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
        }
    }
}

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
?>

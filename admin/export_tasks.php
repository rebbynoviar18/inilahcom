<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php'; // Pastikan sudah install PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini!";
    header('Location: ../index.php');
    exit;
}

// Filter periode
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Ambil data task
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.title,
        t.description,
        c.name as category_name,
        ct.name as content_type_name,
        cp.name as content_pillar_name,
        a.name as account_name,
        t.priority,
        t.status,
        t.created_at,
        t.deadline,
        t.created_at as assigned_at,
        t.completed_at,
        t.verified_at,
        u1.name as created_by_name,
        u2.name as assigned_to_name,
        u3.name as verified_by_name
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN content_types ct ON t.content_type_id = ct.id
    JOIN content_pillars cp ON t.content_pillar_id = cp.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users u1 ON t.created_by = u1.id
    LEFT JOIN users u2 ON t.assigned_to = u2.id
    LEFT JOIN users u3 ON t.verified_by = u3.id
    WHERE t.created_at BETWEEN ? AND ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$tasks = $stmt->fetchAll();

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set judul
$sheet->setCellValue('A1', 'LAPORAN DATA TASK');
$sheet->setCellValue('A2', 'Periode: ' . date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate)));
$sheet->mergeCells('A1:N1');
$sheet->mergeCells('A2:N2');

// Set header kolom
$sheet->setCellValue('A4', 'ID');
$sheet->setCellValue('B4', 'Judul');
$sheet->setCellValue('C4', 'Kategori');
$sheet->setCellValue('D4', 'Tipe Konten');
$sheet->setCellValue('E4', 'Pilar Konten');
$sheet->setCellValue('F4', 'Akun');
$sheet->setCellValue('G4', 'Prioritas');
$sheet->setCellValue('H4', 'Status');
$sheet->setCellValue('I4', 'Dibuat Oleh');
$sheet->setCellValue('J4', 'Tanggal Dibuat');
$sheet->setCellValue('K4', 'Deadline');
$sheet->setCellValue('L4', 'Ditugaskan Kepada');
$sheet->setCellValue('M4', 'Tanggal Ditugaskan');
$sheet->setCellValue('N4', 'Tanggal Selesai');

// Isi data
$row = 5;
foreach ($tasks as $task) {
    $sheet->setCellValue('A' . $row, $task['id']);
    $sheet->setCellValue('B' . $row, $task['title']);
    $sheet->setCellValue('C' . $row, $task['category_name']);
    $sheet->setCellValue('D' . $row, $task['content_type_name']);
    $sheet->setCellValue('E' . $row, $task['content_pillar_name']);
    $sheet->setCellValue('F' . $row, $task['account_name']);
    $sheet->setCellValue('G' . $row, ucfirst($task['priority']));
    $sheet->setCellValue('H' . $row, getStatusLabel($task['status']));
    $sheet->setCellValue('I' . $row, $task['created_by_name']);
    $sheet->setCellValue('J' . $row, date('d M Y H:i', strtotime($task['created_at'])));
    $sheet->setCellValue('K' . $row, date('d M Y H:i', strtotime($task['deadline'])));
    $sheet->setCellValue('L' . $row, $task['assigned_to_name'] ?? '-');
    $sheet->setCellValue('M' . $row, $task['assigned_at'] ? date('d M Y H:i', strtotime($task['assigned_at'])) : '-');
    $sheet->setCellValue('N' . $row, $task['verified_at'] ? date('d M Y H:i', strtotime($task['verified_at'])) : '-');
    $row++;
}

// Auto size kolom
foreach (range('A', 'N') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Style header
$styleArray = [
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '4e73df',
        ],
    ],
];
$sheet->getStyle('A4:N4')->applyFromArray($styleArray);
$sheet->getStyle('A4:N4')->getFont()->getColor()->setRGB('FFFFFF');

// Style judul
$sheet->getStyle('A1:A2')->getFont()->setBold(true);
$sheet->getStyle('A1')->getFont()->setSize(16);
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Style data
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A5:N' . ($row - 1))->applyFromArray($styleArray);

// Set nama file
$filename = 'Laporan_Task_' . date('Y-m-d') . '.xlsx';

// Set header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Output file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
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

// Ambil data kinerja tim produksi
$stmt = $pdo->prepare("
    SELECT 
        u.id as user_id,
        u.name as user_name,
        COUNT(t.id) as assigned_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN t.status = 'rejected' THEN 1 ELSE 0 END) as rejected_tasks,
        AVG(TIMESTAMPDIFF(HOUR, t.assigned_at, 
            CASE 
                WHEN t.status = 'completed' THEN t.verified_at
                ELSE NOW()
            END
        )) as avg_completion_time
    FROM users u
    LEFT JOIN tasks t ON u.id = t.assigned_to AND t.assigned_at BETWEEN ? AND ?
    WHERE u.role = 'production_team'
    GROUP BY u.id
    ORDER BY completed_tasks DESC
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$performanceData = $stmt->fetchAll();

// Ambil detail task untuk setiap anggota tim
$userTasks = [];
foreach ($performanceData as $data) {
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.title,
            t.status,
            t.assigned_at,
            t.completed_at,
            t.verified_at,
            TIMESTAMPDIFF(HOUR, t.assigned_at, 
                CASE 
                    WHEN t.status = 'completed' THEN t.verified_at
                    ELSE NOW()
                END
            ) as completion_time
        FROM tasks t
        WHERE t.assigned_to = ?
        AND t.assigned_at BETWEEN ? AND ?
        ORDER BY t.assigned_at DESC
    ");
    $stmt->execute([$data['user_id'], $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $userTasks[$data['user_id']] = $stmt->fetchAll();
}

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ringkasan');

// Set judul
$sheet->setCellValue('A1', 'LAPORAN KINERJA TIM PRODUKSI');
$sheet->setCellValue('A2', 'Periode: ' . date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate)));
$sheet->mergeCells('A1:G1');
$sheet->mergeCells('A2:G2');

// Set header kolom
$sheet->setCellValue('A4', 'No');
$sheet->setCellValue('B4', 'Nama');
$sheet->setCellValue('C4', 'Task Ditugaskan');
$sheet->setCellValue('D4', 'Task Selesai');
$sheet->setCellValue('E4', 'Task Ditolak');
$sheet->setCellValue('F4', 'Persentase Penyelesaian');
$sheet->setCellValue('G4', 'Waktu Rata-rata (jam)');

// Isi data ringkasan
$row = 5;
$no = 1;
foreach ($performanceData as $data) {
    $completionPercentage = $data['assigned_tasks'] > 0 ? 
        round(($data['completed_tasks'] / $data['assigned_tasks']) * 100, 2) : 0;
    
    $sheet->setCellValue('A' . $row, $no);
    $sheet->setCellValue('B' . $row, $data['user_name']);
    $sheet->setCellValue('C' . $row, $data['assigned_tasks']);
    $sheet->setCellValue('D' . $row, $data['completed_tasks']);
    $sheet->setCellValue('E' . $row, $data['rejected_tasks']);
    $sheet->setCellValue('F' . $row, $completionPercentage . '%');
    $sheet->setCellValue('G' . $row, round($data['avg_completion_time'], 1));
    
    $no++;
    $row++;
}

// Auto size kolom
foreach (range('A', 'G') as $col) {
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
$sheet->getStyle('A4:G4')->applyFromArray($styleArray);
$sheet->getStyle('A4:G4')->getFont()->getColor()->setRGB('FFFFFF');

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
$sheet->getStyle('A5:G' . ($row - 1))->applyFromArray($styleArray);

// Buat sheet detail untuk setiap anggota tim
foreach ($performanceData as $index => $data) {
    if (empty($userTasks[$data['user_id']])) continue;
    
    // Buat sheet baru
    $detailSheet = $spreadsheet->createSheet();
    $detailSheet->setTitle(substr($data['user_name'], 0, 30)); // Batasi panjang nama sheet
    
    // Set judul
    $detailSheet->setCellValue('A1', 'DETAIL KINERJA: ' . $data['user_name']);
    $detailSheet->setCellValue('A2', 'Periode: ' . date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate)));
    $detailSheet->mergeCells('A1:G1');
    $detailSheet->mergeCells('A2:G2');
    
    // Set header kolom
    $detailSheet->setCellValue('A4', 'No');
    $detailSheet->setCellValue('B4', 'ID Task');
    $detailSheet->setCellValue('C4', 'Judul Task');
    $detailSheet->setCellValue('D4', 'Status');
    $detailSheet->setCellValue('E4', 'Tanggal Ditugaskan');
    $detailSheet->setCellValue('F4', 'Tanggal Selesai');
    $detailSheet->setCellValue('G4', 'Waktu Pengerjaan (jam)');
    
    // Isi data detail
    $detailRow = 5;
    $detailNo = 1;
    foreach ($userTasks[$data['user_id']] as $task) {
        $detailSheet->setCellValue('A' . $detailRow, $detailNo);
        $detailSheet->setCellValue('B' . $detailRow, $task['id']);
        $detailSheet->setCellValue('C' . $detailRow, $task['title']);
        $detailSheet->setCellValue('D' . $detailRow, getStatusLabel($task['status']));
        $detailSheet->setCellValue('E' . $detailRow, date('d M Y H:i', strtotime($task['assigned_at'])));
        $detailSheet->setCellValue('F' . $detailRow, $task['verified_at'] ? date('d M Y H:i', strtotime($task['verified_at'])) : '-');
        $detailSheet->setCellValue('G' . $detailRow, round($task['completion_time'], 1));
        
        $detailNo++;
        $detailRow++;
    }
    
    // Auto size kolom
    foreach (range('A', 'G') as $col) {
        $detailSheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Style header
    $detailSheet->getStyle('A4:G4')->applyFromArray($styleArray);
    $detailSheet->getStyle('A4:G4')->getFont()->getColor()->setRGB('FFFFFF');
    
    // Style judul
    $detailSheet->getStyle('A1:A2')->getFont()->setBold(true);
    $detailSheet->getStyle('A1')->getFont()->setSize(16);
    $detailSheet->getStyle('A1:A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Style data
    $detailSheet->getStyle('A5:G' . ($detailRow - 1))->applyFromArray($styleArray);
}

// Kembali ke sheet pertama
$spreadsheet->setActiveSheetIndex(0);

// Set nama file
$filename = 'Laporan_Kinerja_' . date('Y-m-d') . '.xlsx';

// Set header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Output file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
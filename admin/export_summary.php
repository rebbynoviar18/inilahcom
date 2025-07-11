<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php'; // Pastikan sudah install TCPDF

use TCPDF as TCPDF;

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

// 1. Statistik Task
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_tasks,
        SUM(CASE WHEN status IN ('waiting_confirmation', 'in_production', 'ready_for_review', 'uploaded') THEN 1 ELSE 0 END) as ongoing_tasks,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_tasks
    FROM tasks
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$taskStats = $stmt->fetch();

// 2. Statistik berdasarkan kategori
$stmt = $pdo->prepare("
    SELECT 
        c.name as category_name,
        COUNT(*) as task_count,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    WHERE t.created_at BETWEEN ? AND ?
    GROUP BY t.category_id
    ORDER BY task_count DESC
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$categoryStats = $stmt->fetchAll();

// 3. Statistik berdasarkan akun
$stmt = $pdo->prepare("
    SELECT 
        a.name as account_name,
        COUNT(*) as task_count
    FROM tasks t
    JOIN accounts a ON t.account_id = a.id
    WHERE t.created_at BETWEEN ? AND ?
    GROUP BY t.account_id
    ORDER BY task_count DESC
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$accountStats = $stmt->fetchAll();

// 4. Kinerja tim produksi
$stmt = $pdo->prepare("
    SELECT 
        u.name as user_name,
        COUNT(*) as assigned_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        AVG(TIMESTAMPDIFF(HOUR, t.assigned_at, 
            CASE 
                WHEN t.status = 'completed' THEN t.verified_at
                ELSE NOW()
            END
        )) as avg_completion_time
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    WHERE t.assigned_at IS NOT NULL
    AND t.assigned_at BETWEEN ? AND ?
    GROUP BY t.assigned_to
    ORDER BY completed_tasks DESC
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$productionStats = $stmt->fetchAll();

// 5. Waktu pengerjaan rata-rata
$stmt = $pdo->prepare("
    SELECT 
        AVG(TIMESTAMPDIFF(HOUR, t.assigned_at, t.verified_at)) as avg_completion_time
    FROM tasks t
    WHERE t.status = 'completed'
    AND t.assigned_at BETWEEN ? AND ?
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$avgCompletionTime = $stmt->fetchColumn();

// Buat PDF
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'LAPORAN RINGKASAN KINERJA TIM PRODUKSI', 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Periode: ' . date('d M Y', strtotime($_GET['start_date'])) . ' - ' . date('d M Y', strtotime($_GET['end_date'])), 0, 1, 'C');
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// Inisialisasi PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Creative Management System');
$pdf->SetAuthor('Creative Director');
$pdf->SetTitle('Laporan Ringkasan');
$pdf->SetSubject('Laporan Ringkasan Kinerja Tim Produksi');
$pdf->SetKeywords('laporan, kinerja, tim produksi');

// Set default header dan footer
$pdf->setHeaderFont(Array('helvetica', '', 10));
$pdf->setFooterFont(Array('helvetica', '', 8));

// Set margin
$pdf->SetMargins(15, 20, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Tambah halaman
$pdf->AddPage();

// Statistik Umum
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, '1. STATISTIK UMUM', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Tabel statistik umum
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(60, 7, 'Total Task', 1, 0, 'L', 1);
$pdf->Cell(30, 7, $taskStats['total_tasks'], 1, 1, 'C');

$pdf->Cell(60, 7, 'Task Selesai', 1, 0, 'L', 1);
$pdf->Cell(30, 7, $taskStats['completed_tasks'] . ' (' . 
    ($taskStats['total_tasks'] > 0 ? round(($taskStats['completed_tasks'] / $taskStats['total_tasks']) * 100) : 0) . '%)', 1, 1, 'C');

$pdf->Cell(60, 7, 'Task Berjalan', 1, 0, 'L', 1);
$pdf->Cell(30, 7, $taskStats['ongoing_tasks'] . ' (' . 
    ($taskStats['total_tasks'] > 0 ? round(($taskStats['ongoing_tasks'] / $taskStats['total_tasks']) * 100) : 0) . '%)', 1, 1, 'C');

$pdf->Cell(60, 7, 'Task Ditolak', 1, 0, 'L', 1);
$pdf->Cell(30, 7, $taskStats['rejected_tasks'] . ' (' . 
    ($taskStats['total_tasks'] > 0 ? round(($taskStats['rejected_tasks'] / $taskStats['total_tasks']) * 100) : 0) . '%)', 1, 1, 'C');

$pdf->Cell(60, 7, 'Task Draft', 1, 0, 'L', 1);
$pdf->Cell(30, 7, $taskStats['draft_tasks'] . ' (' . 
    ($taskStats['total_tasks'] > 0 ? round(($taskStats['draft_tasks'] / $taskStats['total_tasks']) * 100) : 0) . '%)', 1, 1, 'C');

$pdf->Cell(60, 7, 'Waktu Pengerjaan Rata-rata', 1, 0, 'L', 1);
$pdf->Cell(30, 7, ($avgCompletionTime ? round($avgCompletionTime, 1) : 0) . ' jam', 1, 1, 'C');

$pdf->Ln(5);

// Statistik Kategori
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, '2. STATISTIK BERDASARKAN KATEGORI', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Header tabel kategori
$pdf->SetFillColor(78, 115, 223);
$pdf->SetTextColor(255);
$pdf->Cell(80, 7, 'Kategori', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Jumlah Task', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Task Selesai', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Persentase', 1, 1, 'C', 1);
$pdf->SetTextColor(0);

// Isi tabel kategori
foreach ($categoryStats as $stat) {
    $pdf->Cell(80, 7, $stat['category_name'], 1, 0, 'L');
    $pdf->Cell(30, 7, $stat['task_count'], 1, 0, 'C');
    $pdf->Cell(30, 7, $stat['completed_count'], 1, 0, 'C');
    $pdf->Cell(30, 7, ($stat['task_count'] > 0 ? round(($stat['completed_count'] / $stat['task_count']) * 100) : 0) . '%', 1, 1, 'C');
}

$pdf->Ln(5);

// Statistik Akun
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, '3. STATISTIK BERDASARKAN AKUN', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Header tabel akun
$pdf->SetFillColor(78, 115, 223);
$pdf->SetTextColor(255);
$pdf->Cell(110, 7, 'Akun', 1, 0, 'C', 1);
$pdf->Cell(60, 7, 'Jumlah Task', 1, 1, 'C', 1);
$pdf->SetTextColor(0);

// Isi tabel akun
foreach ($accountStats as $stat) {
    $pdf->Cell(110, 7, $stat['account_name'], 1, 0, 'L');
    $pdf->Cell(60, 7, $stat['task_count'], 1, 1, 'C');
}

$pdf->Ln(5);

// Kinerja Tim Produksi
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, '4. KINERJA TIM PRODUKSI', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Header tabel kinerja
$pdf->SetFillColor(78, 115, 223);
$pdf->SetTextColor(255);
$pdf->Cell(60, 7, 'Nama', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Task Ditugaskan', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Task Selesai', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Persentase', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Waktu Rata-rata', 1, 1, 'C', 1);
$pdf->SetTextColor(0);

// Isi tabel kinerja
foreach ($productionStats as $stat) {
    $pdf->Cell(60, 7, $stat['user_name'], 1, 0, 'L');
    $pdf->Cell(30, 7, $stat['assigned_tasks'], 1, 0, 'C');
    $pdf->Cell(30, 7, $stat['completed_tasks'], 1, 0, 'C');
    $pdf->Cell(30, 7, ($stat['assigned_tasks'] > 0 ? round(($stat['completed_tasks'] / $stat['assigned_tasks']) * 100) : 0) . '%', 1, 0, 'C');
    $pdf->Cell(30, 7, round($stat['avg_completion_time'], 1) . ' jam', 1, 1, 'C');
}

$pdf->Ln(10);

// Tanda tangan
$pdf->Cell(0, 7, 'Jakarta, ' . date('d M Y'), 0, 1, 'R');
$pdf->Ln(15);
$pdf->Cell(0, 7, 'Creative Director', 0, 1, 'R');

// Output PDF
$pdf->Output('Laporan_Ringkasan_' . date('Y-m-d') . '.pdf', 'D');
exit;
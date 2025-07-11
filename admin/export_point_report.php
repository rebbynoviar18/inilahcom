<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header('Location: ../index.php');
    exit;
}

// Filter
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$team = isset($_GET['team']) ? $_GET['team'] : 'all';
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

// Filter tim
$teamFilter = '';
$userFilter = '';
$params = [];

if ($team !== 'all') {
    $teamFilter = "AND u.role = :team";
    $params[':team'] = $team;
}

if ($userId > 0) {
    $userFilter = "AND u.id = :user_id";
    $params[':user_id'] = $userId;
}

// Tambahkan filter tanggal
$params[':start_date'] = $startDate . ' 00:00:00';
$params[':end_date'] = $endDate . ' 23:59:59';

// Ambil data laporan poin
$query = "
    SELECT 
        u.id,
        u.name,
        u.role,
        t.id as task_id,
        t.title as task_title,
        c.name as category_name,
        ct.name as content_type_name,
        up.points,
        up.earned_at,
        a.name as account_name
    FROM 
        users u
    JOIN 
        user_points up ON u.id = up.user_id
    JOIN 
        tasks t ON up.task_id = t.id
    JOIN 
        categories c ON t.category_id = c.id
    JOIN 
        content_types ct ON t.content_type_id = ct.id
    JOIN 
        accounts a ON t.account_id = a.id
    WHERE 
        u.role IN ('content_team', 'production_team')
        AND up.earned_at BETWEEN :start_date AND :end_date
        $teamFilter
        $userFilter
    ORDER BY 
        up.earned_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pointReports = $stmt->fetchAll();

// Set header untuk download file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="laporan_poin_' . date('Ymd') . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Poin</title>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th colspan="8">Laporan Poin: <?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?></th>
            </tr>
            <tr>
                <th>Tanggal</th>
                <th>Anggota Tim</th>
                <th>Tim</th>
                <th>Akun</th>
                <th>Kategori</th>
                <th>Jenis Konten</th>
                <th>Task</th>
                <th>Poin</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pointReports)): ?>
                <tr>
                    <td colspan="8">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php 
                $totalPoints = 0;
                foreach ($pointReports as $report): 
                    $totalPoints += $report['points'];
                ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($report['earned_at'])) ?></td>
                        <td><?= $report['name'] ?></td>
                        <td><?= $report['role'] === 'content_team' ? 'Content' : 'Production' ?></td>
                        <td><?= $report['account_name'] ?></td>
                        <td><?= $report['category_name'] ?></td>
                        <td><?= $report['content_type_name'] ?></td>
                        <td><?= $report['task_title'] ?></td>
                        <td><?= number_format($report['points'], 1) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="7" align="right"><strong>Total Poin:</strong></td>
                    <td><strong><?= number_format($totalPoints, 1) ?></strong></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
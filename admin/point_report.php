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

$userId = $_SESSION['user_id'];

// Filter
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default: awal bulan ini
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default: hari ini
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

// Ambil daftar user untuk filter
$userQuery = "
    SELECT id, name, role
    FROM users
    WHERE role IN ('content_team', 'production_team')
    ORDER BY role, name
";
$users = $pdo->query($userQuery)->fetchAll();

// Hitung total poin
$totalPoints = 0;
foreach ($pointReports as $report) {
    $totalPoints += $report['points'];
}

// Hitung statistik per kategori
$categoryStats = [];
foreach ($pointReports as $report) {
    $category = $report['category_name'];
    if (!isset($categoryStats[$category])) {
        $categoryStats[$category] = [
            'count' => 0,
            'points' => 0
        ];
    }
    $categoryStats[$category]['count']++;
    $categoryStats[$category]['points'] += $report['points'];
}

$pageTitle = "Laporan Poin";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Laporan Poin</h4>
                    <a href="point_settings.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-cog"></i> Pengaturan Poin
                    </a>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $startDate ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $endDate ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="team" class="form-label">Tim</label>
                                    <select name="team" id="team" class="form-select">
                                        <option value="all" <?= $team === 'all' ? 'selected' : '' ?>>Semua Tim</option>
                                        <option value="content_team" <?= $team === 'content_team' ? 'selected' : '' ?>>Tim Content</option>
                                        <option value="production_team" <?= $team === 'production_team' ? 'selected' : '' ?>>Tim Production</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="user_id" class="form-label">Anggota Tim</label>
                                    <select name="user_id" id="user_id" class="form-select">
                                        <option value="0">Semua Anggota</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['name']) ?> (<?= $user['role'] === 'content_team' ? 'Content' : 'Production' ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Poin</h5>
                                    <h2><?= number_format($totalPoints, 1) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Task</h5>
                                    <h2><?= count($pointReports) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Rata-rata Poin/Task</h5>
                                    <h2><?= count($pointReports) > 0 ? number_format($totalPoints / count($pointReports), 1) : '0.0' ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Stats -->
                    <h5 class="mb-3">Statistik per Kategori</h5>
                    <div class="row mb-4">
                        <?php foreach ($categoryStats as $category => $stats): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6><?= htmlspecialchars($category) ?></h6>
                                        <div class="display-6"><?= number_format($stats['points'], 1) ?></div>
                                        <div class="text-muted"><?= $stats['count'] ?> task</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Detail Table -->
                    <h5 class="mb-3">Detail Poin</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
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
                                        <td colspan="8" class="text-center">Tidak ada data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pointReports as $report): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($report['earned_at'])) ?></td>
                                            <td><?= htmlspecialchars($report['name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $report['role'] === 'content_team' ? 'info' : 'success' ?>">
                                                    <?= $report['role'] === 'content_team' ? 'Content' : 'Production' ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($report['account_name']) ?></td>
                                            <td><?= htmlspecialchars($report['category_name']) ?></td>
                                            <td><?= htmlspecialchars($report['content_type_name']) ?></td>
                                            <td>
                                                <a href="../content/view_task.php?id=<?= $report['task_id'] ?>">
                                                    <?= htmlspecialchars($report['task_title']) ?>
                                                </a>
                                            </td>
                                            <td><strong><?= number_format($report['points'], 1) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Export Button -->
                    <div class="mt-3">
                        <button id="exportBtn" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i> Export ke Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('exportBtn').addEventListener('click', function() {
    // Dapatkan parameter filter saat ini
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const team = document.getElementById('team').value;
    const userId = document.getElementById('user_id').value;
    
    // Buat URL untuk export
    const exportUrl = `export_point_report.php?start_date=${startDate}&end_date=${endDate}&team=${team}&user_id=${userId}`;
    
    // Buka URL di tab baru
    window.open(exportUrl, '_blank');
});
</script>

<?php include '../includes/footer.php'; ?>
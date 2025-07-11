<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];

// Filter periode
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$team = isset($_GET['team']) ? $_GET['team'] : 'all';

// Tentukan rentang tanggal berdasarkan periode
$dateRange = '';
switch ($period) {
    case 'week':
        $dateRange = "AND up.earned_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        break;
    case 'month':
        $dateRange = "AND up.earned_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    case 'quarter':
        $dateRange = "AND up.earned_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        break;
    case 'year':
        $dateRange = "AND up.earned_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
    default:
        $dateRange = "";
}

// Filter tim
$teamFilter = '';
if ($team !== 'all') {
    $teamFilter = "AND u.role = :team";
}

// Ambil data leaderboard
$query = "
    SELECT 
        u.id,
        u.name,
        u.role,
        SUM(up.points) as total_points,
        COUNT(DISTINCT up.task_id) as completed_tasks,
        AVG(up.points) as avg_points
    FROM 
        users u
    LEFT JOIN 
        user_points up ON u.id = up.user_id
    WHERE 
        u.role IN ('content_team', 'production_team')
        $dateRange
        $teamFilter
    GROUP BY 
        u.id
    ORDER BY 
        total_points DESC
";

$stmt = $pdo->prepare($query);
if ($team !== 'all') {
    $stmt->bindParam(':team', $team);
}
$stmt->execute();
$leaderboard = $stmt->fetchAll();

// Ambil statistik per kategori
$categoryQuery = "
    SELECT 
        c.name as category_name,
        COUNT(t.id) as task_count,
        SUM(up.points) as total_points
    FROM 
        categories c
    JOIN 
        tasks t ON c.id = t.category_id
    JOIN 
        user_points up ON t.id = up.task_id
    WHERE 
        1=1
        $dateRange
    GROUP BY 
        c.id
    ORDER BY 
        total_points DESC
";

$categoryStats = $pdo->query($categoryQuery)->fetchAll();

$pageTitle = "Leaderboard";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Leaderboard Produktivitas Tim</h4>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form method="GET" class="d-flex gap-2">
                                <select name="period" class="form-select">
                                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Minggu Ini</option>
                                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Bulan Ini</option>
                                    <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>3 Bulan Terakhir</option>
                                    <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Tahun Ini</option>
                                    <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Semua Waktu</option>
                                </select>
                                <select name="team" class="form-select">
                                    <option value="all" <?= $team === 'all' ? 'selected' : '' ?>>Semua Tim</option>
                                    <option value="content_team" <?= $team === 'content_team' ? 'selected' : '' ?>>Tim Content</option>
                                    <option value="production_team" <?= $team === 'production_team' ? 'selected' : '' ?>>Tim Production</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Leaderboard Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Peringkat</th>
                                    <th>Nama</th>
                                    <th>Tim</th>
                                    <th>Total Poin</th>
                                    <th>Task Selesai</th>
                                    <th>Rata-rata Poin/Task</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaderboard)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $rank = 1; ?>
                                    <?php foreach ($leaderboard as $user): ?>
                                        <tr>
                                            <td><?= $rank++ ?></td>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $user['role'] === 'content_team' ? 'info' : 'success' ?>">
                                                    <?= $user['role'] === 'content_team' ? 'Content' : 'Production' ?>
                                                </span>
                                            </td>
                                            <td><strong><?= number_format($user['total_points'], 1) ?></strong></td>
                                            <td><?= $user['completed_tasks'] ?></td>
                                            <td><?= number_format($user['avg_points'], 1) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Category Stats -->
                    <h5 class="mt-4">Statistik per Kategori</h5>
                    <div class="row">
                        <?php foreach ($categoryStats as $category): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h5><?= htmlspecialchars($category['category_name']) ?></h5>
                                        <div class="display-6"><?= number_format($category['total_points'], 1) ?></div>
                                        <div class="text-muted"><?= $category['task_count'] ?> task</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini!";
    header('Location: ../index.php');
    exit;
}

// Filter periode
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default: awal bulan ini
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Default: hari ini

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

// 3. Statistik berdasarkan tipe konten
$stmt = $pdo->prepare("
    SELECT 
        ct.name as content_type_name,
        COUNT(*) as task_count
    FROM tasks t
    JOIN content_types ct ON t.content_type_id = ct.id
    WHERE t.created_at BETWEEN ? AND ?
    GROUP BY t.content_type_id
    ORDER BY task_count DESC
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$contentTypeStats = $stmt->fetchAll();

// 4. Statistik berdasarkan akun
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

// 5. Kinerja tim produksi
$stmt = $pdo->prepare("
    SELECT 
        u.name as user_name,
        COUNT(*) as assigned_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        AVG(TIMESTAMPDIFF(HOUR, t.created_at, 
            CASE 
                WHEN t.status = 'completed' THEN t.verified_at
                ELSE NOW()
            END
        )) as avg_completion_time
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    WHERE t.created_at BETWEEN ? AND ?
    GROUP BY t.assigned_to
    ORDER BY completed_tasks DESC
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$productionStats = $stmt->fetchAll();

// 6. Waktu pengerjaan rata-rata
$stmt = $pdo->prepare("
    SELECT 
        AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.verified_at)) as avg_completion_time
    FROM tasks t
    WHERE t.status = 'completed'
    AND t.created_at BETWEEN ? AND ?
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$avgCompletionTime = $stmt->fetchColumn();

$pageTitle = "Laporan & Statistik";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-bar"></i> Laporan & Statistik</h2>
        
        <form class="d-flex" method="GET">
            <div class="input-group me-2">
                <span class="input-group-text">Dari</span>
                <input type="date" class="form-control" name="start_date" value="<?= $startDate ?>">
            </div>
            <div class="input-group me-2">
                <span class="input-group-text">Sampai</span>
                <input type="date" class="form-control" name="end_date" value="<?= $endDate ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total Task</h5>
                    <h2 class="display-4"><?= $taskStats['total_tasks'] ?></h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span>Periode: <?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Task Selesai</h5>
                    <h2 class="display-4"><?= $taskStats['completed_tasks'] ?></h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span>
                        <?= $taskStats['total_tasks'] > 0 ? round(($taskStats['completed_tasks'] / $taskStats['total_tasks']) * 100) : 0 ?>% dari total task
                    </span>
                    <a href="#" class="small text-white stretched-link">View Details</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Task Berjalan</h5>
                    <h2 class="display-4"><?= $taskStats['ongoing_tasks'] ?></h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span>
                        <?= $taskStats['total_tasks'] > 0 ? round(($taskStats['ongoing_tasks'] / $taskStats['total_tasks']) * 100) : 0 ?>% dari total task
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Task Ditolak</h5>
                    <h2 class="display-4"><?= $taskStats['rejected_tasks'] ?></h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span>
                        <?= $taskStats['total_tasks'] > 0 ? round(($taskStats['rejected_tasks'] / $taskStats['total_tasks']) * 100) : 0 ?>% dari total task
                    </span>
                    <a href="#" class="small text-white stretched-link">View Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Distribusi Task berdasarkan Kategori</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Distribusi Task berdasarkan Tipe Konten</h5>
                </div>
                <div class="card-body">
                    <canvas id="contentTypeChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Distribusi Task berdasarkan Akun</h5>
                </div>
                <div class="card-body">
                    <canvas id="accountChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Waktu Pengerjaan Rata-rata</h5>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-4">
                        <?= $avgCompletionTime ? round($avgCompletionTime, 1) : 0 ?> <small>jam</small>
                    </h1>
                    <p class="text-muted">Waktu rata-rata dari penugasan hingga verifikasi</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-users"></i> Kinerja Tim Produksi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Task Ditugaskan</th>
                            <th>Task Selesai</th>
                            <th>Persentase Penyelesaian</th>
                            <th>Waktu Rata-rata (jam)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productionStats as $stat): ?>
                            <tr>
                                <td><?= htmlspecialchars($stat['user_name']) ?></td>
                                <td><?= $stat['assigned_tasks'] ?></td>
                                <td><?= $stat['completed_tasks'] ?></td>
                                <td>
                                    <?= $stat['assigned_tasks'] > 0 ? round(($stat['completed_tasks'] / $stat['assigned_tasks']) * 100) : 0 ?>%
                                </td>
                                <td><?= round($stat['avg_completion_time'], 1) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-download"></i> Ekspor Data</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="d-grid">
                        <a href="export_tasks.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline-primary">
                            <i class="fas fa-file-excel"></i> Ekspor Data Task
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-grid">
                        <a href="export_performance.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline-success">
                            <i class="fas fa-file-excel"></i> Ekspor Data Kinerja
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-grid">
                        <a href="export_summary.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline-info">
                            <i class="fas fa-file-pdf"></i> Ekspor Laporan Ringkasan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart untuk kategori
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php foreach ($categoryStats as $stat): ?>
                    '<?= htmlspecialchars($stat['category_name']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($categoryStats as $stat): ?>
                        <?= $stat['task_count'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#5a5c69', '#858796', '#6f42c1', '#20c9a6', '#fd7e14'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
    
    // Chart untuk tipe konten
    const contentTypeCtx = document.getElementById('contentTypeChart').getContext('2d');
    const contentTypeChart = new Chart(contentTypeCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php foreach ($contentTypeStats as $stat): ?>
                    '<?= htmlspecialchars($stat['content_type_name']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($contentTypeStats as $stat): ?>
                        <?= $stat['task_count'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#5a5c69', '#858796', '#6f42c1', '#20c9a6', '#fd7e14'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
    
    // Chart untuk akun
    const accountCtx = document.getElementById('accountChart').getContext('2d');
    const accountChart = new Chart(accountCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($accountStats as $stat): ?>
                    '<?= htmlspecialchars($stat['account_name']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Jumlah Task',
                data: [
                    <?php foreach ($accountStats as $stat): ?>
                        <?= $stat['task_count'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: '#4e73df'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
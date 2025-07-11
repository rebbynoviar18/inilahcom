<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'redaktur_pelaksana') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Filter periode
$timeRange = isset($_GET['range']) ? $_GET['range'] : 'monthly';

// Set tanggal awal dan akhir berdasarkan filter
$endDate = date('Y-m-d');
switch ($timeRange) {
    case 'weekly':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'quarterly':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'monthly':
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
}

// Ambil statistik task
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        AVG(CASE WHEN status = 'completed' THEN rating ELSE NULL END) as avg_rating
    FROM tasks 
    WHERE created_by = ? AND created_at BETWEEN ? AND ?
");
$stmt->execute([$userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Hitung persentase penyelesaian
$completionRate = $stats['total_tasks'] > 0 ? ($stats['completed_tasks'] / $stats['total_tasks']) * 100 : 0;

// Ambil data untuk grafik distribusi status
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count
    FROM tasks
    WHERE created_by = ? AND created_at BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data untuk grafik distribusi akun media
$stmt = $pdo->prepare("
    SELECT a.name, COUNT(*) as count
    FROM tasks t
    JOIN accounts a ON t.account_id = a.id
    WHERE t.created_by = ? AND t.created_at BETWEEN ? AND ?
    GROUP BY a.name
    ORDER BY count DESC
    LIMIT 5
");
$stmt->execute([$userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$accountData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil task terbaru
$stmt = $pdo->prepare("
    SELECT t.id, t.title, t.status, t.created_at, a.name as account_name, t.rating
    FROM tasks t
    JOIN accounts a ON t.account_id = a.id
    WHERE t.created_by = ?
    ORDER BY t.created_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$recentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Laporan Marketing";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Filter Periode</h5>
                    <div>
                        <select class="form-control" id="timeRangeFilter" onchange="filterTimeRange()">
                            <option value="weekly" <?= $timeRange === 'weekly' ? 'selected' : '' ?>>Mingguan</option>
                            <option value="monthly" <?= $timeRange === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                            <option value="quarterly" <?= $timeRange === 'quarterly' ? 'selected' : '' ?>>3 Bulan</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-0">Menampilkan data dari <?= date('d M Y', strtotime($startDate)) ?> sampai <?= date('d M Y', strtotime($endDate)) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Task</h5>
                    <h2><?= $stats['total_tasks'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Selesai</h5>
                    <h2><?= $stats['completed_tasks'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Tingkat Penyelesaian</h5>
                    <h2><?= number_format($completionRate, 1) ?>%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h5 class="card-title">Rating</h5>
                    <h2><?= $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) . ' ★' : '-' ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Distribusi Status Task</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Distribusi Akun Media</h5>
                </div>
                <div class="card-body">
                    <canvas id="accountChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Task Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Akun Media</th>
                                    <th>Status</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTasks as $task): ?>
                                <tr>
                                    <td>
                                        <a href="view_task.php?id=<?= $task['id'] ?>"><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></a>
                                    </td>
                                    <td><?= htmlspecialchars($task['account_name']) ?></td>
                                    <td><?= getStatusBadge($task['status']) ?></td>
                                    <td><?= date('d M Y', strtotime($task['created_at'])) ?></td>
                                    <td><?= $task['rating'] ? str_repeat('★', $task['rating']) . str_repeat('☆', 5 - $task['rating']) : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk filter periode
    window.filterTimeRange = function() {
        const range = document.getElementById('timeRangeFilter').value;
        window.location.href = 'reports.php?range=' + range;
    };

    // Data untuk chart status
    const statusData = <?= json_encode($statusData) ?>;
    const statusLabels = [];
    const statusCounts = [];
    const statusColors = [];

    // Mapping status ke label dan warna
    const statusMapping = {
        'draft': { label: 'Draft', color: '#6c757d' },
        'waiting_head_confirmation': { label: 'Menunggu Konfirmasi', color: '#ffc107' },
        'waiting_confirmation': { label: 'Menunggu Konfirmasi', color: '#ffc107' },
        'in_production': { label: 'Dalam Produksi', color: '#0d6efd' },
        'ready_for_review': { label: 'Siap Review', color: '#17a2b8' },
        'uploaded': { label: 'Telah Upload', color: '#20c997' },
        'completed': { label: 'Selesai', color: '#198754' },
        'revision': { label: 'Perlu Revisi', color: '#dc3545' },
        'rejected': { label: 'Ditolak', color: '#dc3545' },
        'cancelled': { label: 'Dibatalkan', color: '#6c757d' }
    };

    statusData.forEach(item => {
        const mappedStatus = statusMapping[item.status] || { label: item.status, color: '#6c757d' };
        statusLabels.push(mappedStatus.label);
        statusCounts.push(item.count);
        statusColors.push(mappedStatus.color);
    });

    // Chart status
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: statusColors,
                borderWidth: 1
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

    // Data untuk chart akun media
    const accountData = <?= json_encode($accountData) ?>;
    const accountLabels = accountData.map(item => item.name);
    const accountCounts = accountData.map(item => item.count);

    // Chart akun media
    const accountCtx = document.getElementById('accountChart').getContext('2d');
    new Chart(accountCtx, {
        type: 'bar',
        data: {
            labels: accountLabels,
            datasets: [{
                label: 'Jumlah Task',
                data: accountCounts,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
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
<?php
// File: c:\xampp\htdocs\creative\admin\reports.php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Filter tanggal
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Statistik utama
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_tasks,
        AVG(rating) as avg_rating,
        (SELECT COUNT(*) FROM revisions) as total_revisions
    FROM tasks
    WHERE created_at BETWEEN ? AND ?
");
$stats->execute([$startDate, $endDate]);
$stats = $stats->fetch();

// Task per kategori
$tasksByCategoryStmt = $pdo->prepare("
    SELECT c.name, COUNT(t.id) as count 
    FROM categories c 
    LEFT JOIN tasks t ON c.id = t.category_id AND t.created_at BETWEEN ? AND ?
    GROUP BY c.name
");
$tasksByCategoryStmt->execute([$startDate, $endDate]);
$tasksByCategory = $tasksByCategoryStmt->fetchAll();

// Task per status
$tasksByStatusStmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM tasks 
    WHERE created_at BETWEEN ? AND ?
    GROUP BY status
");
$tasksByStatusStmt->execute([$startDate, $endDate]);
$tasksByStatus = $tasksByStatusStmt->fetchAll();

$pageTitle = "Laporan Statistik";
include '../includes/header.php';
?>

<!-- Tambahkan ini di bagian <head> atau sebelum script inisialisasi grafik -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Filter Laporan</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row">
                        <div class="col-md-5">
                            <label for="start_date" class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $startDate ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="end_date" class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $endDate ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
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
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Terlambat</h5>
                    <h2><?= $stats['overdue_tasks'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h5 class="card-title">Rating Rata2</h5>
                    <h2><?= number_format($stats['avg_rating'], 1) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Task per Kategori</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Task per Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Detail Task</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="tasksTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Prioritas</th>
                                    <th>Deadline</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tasks = $pdo->prepare("
                                    SELECT t.id, t.title, c.name as category, t.status, t.priority, t.deadline, t.rating
                                    FROM tasks t
                                    JOIN categories c ON t.category_id = c.id
                                    WHERE t.created_at BETWEEN ? AND ?
                                    ORDER BY t.created_at DESC
                                ");
                                $tasks->execute([$startDate, $endDate]);
                                while ($task = $tasks->fetch()):
                                ?>
                                <tr>
                                    <td><?= $task['id'] ?></td>
                                    <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                                    <td><?= htmlspecialchars($task['category']) ?></td>
                                    <td><?= getStatusBadge($task['status']) ?></td>
                                    <td><?= getPriorityBadge($task['priority']) ?></td>
                                    <td><?= date('d M Y', strtotime($task['deadline'])) ?></td>
                                    <td><?= $task['rating'] ? str_repeat('★', $task['rating']) . str_repeat('☆', 5 - $task['rating']) : '-' ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($tasksByCategory, 'name')) ?>,
        datasets: [{
            label: 'Jumlah Task',
            data: <?= json_encode(array_column($tasksByCategory, 'count')) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($tasksByStatus, 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($tasksByStatus, 'count')) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ],
            borderWidth: 1
        }]
    }
});

// DataTable
$(document).ready(function() {
    $('#tasksTable').DataTable();
});
</script>

<?php include '../includes/footer.php'; ?>
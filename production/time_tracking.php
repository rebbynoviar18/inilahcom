<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'production_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Get active time tracking
$activeTracking = $pdo->prepare("
    SELECT tt.*, t.title 
    FROM time_tracking tt
    JOIN tasks t ON tt.task_id = t.id
    WHERE tt.user_id = ? AND tt.end_time IS NULL
    ORDER BY tt.start_time DESC
    LIMIT 1
");
$activeTracking->execute([$userId]);
$activeTracking = $activeTracking->fetch();

// Get time tracking history
$trackingHistory = $pdo->prepare("
    SELECT tt.*, t.title 
    FROM time_tracking tt
    JOIN tasks t ON tt.task_id = t.id
    WHERE tt.user_id = ? AND tt.end_time IS NOT NULL
    ORDER BY tt.start_time DESC
    LIMIT 50
");
$trackingHistory->execute([$userId]);
$trackingHistory = $trackingHistory->fetchAll();

// Get today's stats
$todayStats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT task_id) as total_tasks,
        SUM(TIMESTAMPDIFF(MINUTE, start_time, IFNULL(end_time, NOW()))) as total_minutes
    FROM time_tracking
    WHERE user_id = ? AND DATE(start_time) = CURDATE()
");
$todayStats->execute([$userId]);
$todayStats = $todayStats->fetch();

$hours = floor(($todayStats['total_minutes'] ?? 0) / 60);
$minutes = ($todayStats['total_minutes'] ?? 0) % 60;

// Get weekly stats
$weeklyStats = $pdo->prepare("
    SELECT 
        DATE(start_time) as date,
        SUM(TIMESTAMPDIFF(MINUTE, start_time, IFNULL(end_time, NOW()))) as minutes
    FROM time_tracking
    WHERE user_id = ? AND start_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(start_time)
    ORDER BY date
");
$weeklyStats->execute([$userId]);
$weeklyStats = $weeklyStats->fetchAll();

$pageTitle = "Time Tracking";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Time Tracking</h4>
                </div>
                <div class="card-body">
                    <?php if ($activeTracking): ?>
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Sedang mengerjakan: <?php echo htmlspecialchars($activeTracking['title']); ?></h5>
                                <p>Dimulai pada: <?php echo date('d M Y H:i', strtotime($activeTracking['start_time'])); ?></p>
                                <p>Durasi: <span id="currentDuration"><?php echo time_elapsed_string($activeTracking['start_time'], false); ?></span></p>
                            </div>
                            <a href="view_task.php?id=<?php echo $activeTracking['task_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i> Lihat Task
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <h5>Tidak ada aktivitas tracking yang aktif</h5>
                        <p>Tracking akan dimulai otomatis saat Anda membuka halaman task</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Statistik Hari Ini</h5>
                                </div>
                                <div class="card-body">
                                    <h2><?php echo $hours; ?> jam <?php echo $minutes; ?> menit</h2>
                                    <p class="text-muted">Total waktu pengerjaan hari ini</p>
                                    <hr>
                                    <h2><?php echo $todayStats['total_tasks'] ?? 0; ?></h2>
                                    <p class="text-muted">Total task dikerjakan hari ini</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Statistik Mingguan</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="weeklyChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Riwayat Time Tracking</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Task</th>
                                            <th>Mulai</th>
                                            <th>Selesai</th>
                                            <th>Durasi</th>
                                            <th>Catatan</th>
                                            <th>Jenis</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trackingHistory as $tracking): ?>
                                        <tr>
                                            <td>
                                                <a href="view_task.php?id=<?php echo $tracking['task_id']; ?>">
                                                    <?php echo htmlspecialchars($tracking['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d M H:i', strtotime($tracking['start_time'])); ?></td>
                                            <td><?php echo date('d M H:i', strtotime($tracking['end_time'])); ?></td>
                                            <td><?php echo round((strtotime($tracking['end_time']) - strtotime($tracking['start_time'])) / 3600, 1); ?> jam</td>
                                            <td><?php echo htmlspecialchars($tracking['notes'] ?? '-'); ?></td>
                                            <td><?php echo $tracking['is_auto'] ? '<span class="badge bg-info">Auto</span>' : '<span class="badge bg-primary">Manual</span>'; ?></td>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Update durasi tracking aktif setiap menit
    function updateDuration() {
        $.ajax({
            url: '../api/tracking_controller.php',
            method: 'POST',
            data: {action: 'status'},
            success: function(response) {
                if (response.success && response.activeTracking) {
                    $('#currentDuration').text(response.elapsed);
                }
            }
        });
    }
    
    setInterval(updateDuration, 60000);
    
    // Start tracking
    $('#startTrackingForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'start',
            task_id: $('#task_id').val(),
            notes: $('#notes').val()
        };
        
        $.ajax({
            url: '../api/tracking_controller.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Gagal memulai tracking: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + xhr.responseText);
            }
        });
    });
    
    // Stop tracking
    $('#stopTrackingBtn').click(function() {
        const trackingId = $(this).data('tracking-id');
        
        $.ajax({
            url: '../api/tracking_controller.php',
            method: 'POST',
            data: {
                action: 'stop',
                tracking_id: trackingId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Gagal menghentikan tracking: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + xhr.responseText);
            }
        });
    });
});

// Weekly chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('weeklyChart').getContext('2d');
    
    const weeklyData = {
        labels: [
            <?php 
            $dates = [];
            $minutes = [];
            foreach ($weeklyStats as $stat) {
                $dates[] = "'" . date('d M', strtotime($stat['date'])) . "'";
                $minutes[] = round($stat['minutes'] / 60, 1);
            }
            echo implode(', ', $dates);
            ?>
        ],
        datasets: [{
            label: 'Jam Kerja',
            data: [<?php echo implode(', ', $minutes); ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };
    
    const weeklyChart = new Chart(ctx, {
        type: 'bar',
        data: weeklyData,
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jam'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Jam Kerja 7 Hari Terakhir'
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
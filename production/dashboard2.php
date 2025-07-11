<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/functions/program_schedule.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'production_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Ambil data bio user dari database
$userStmt = $pdo->prepare("SELECT bio FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch();
$userBio = $userData['bio'] ?? '';

// Dapatkan statistik task
$taskStats = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN t.status = 'waiting_confirmation' THEN 1 END) as waiting_confirmation,
        COUNT(CASE WHEN t.status = 'in_production' THEN 1 END) as in_production,
        COUNT(CASE WHEN t.status = 'revision' THEN 1 END) as revision,
        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed,
        COUNT(*) as total
    FROM tasks t
    WHERE t.assigned_to = ?
");
$taskStats->execute([$userId]);
$taskData = $taskStats->fetch();

// Dapatkan statistik poin
$pointStats = $pdo->prepare("
    SELECT 
        SUM(up.points) as total_points,
        COUNT(DISTINCT up.task_id) as completed_tasks,
        AVG(up.points) as avg_points_per_task
    FROM 
        user_points up
    WHERE 
        up.user_id = ? 
        AND up.earned_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$pointStats->execute([$userId]);
$pointData = $pointStats->fetch();

// Dapatkan task yang akan datang
$upcomingTasks = $pdo->prepare("
    SELECT t.id, t.title, t.deadline, t.priority, t.status as task_status, a.name as account_name
    FROM tasks t
    JOIN accounts a ON t.account_id = a.id
    WHERE t.assigned_to = ? AND t.status IN ('waiting_confirmation', 'in_production', 'revision')
    ORDER BY t.deadline ASC
    LIMIT 5
");
$upcomingTasks->execute([$userId]);
$upcomingTasks = $upcomingTasks->fetchAll();

// Dapatkan aktivitas terbaru
$recentActivity = $pdo->prepare("
    SELECT tsl.task_id, tsl.status as log_status, tsl.timestamp, t.title, u.name as updated_by_name
    FROM task_status_logs tsl
    JOIN tasks t ON tsl.task_id = t.id
    JOIN users u ON tsl.updated_by = u.id
    WHERE t.assigned_to = ?
    ORDER BY tsl.timestamp DESC
    LIMIT 10
");
$recentActivity->execute([$userId]);
$recentActivity = $recentActivity->fetchAll();

// Get time tracking statistics
$trackingStats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT task_id) as total_tasks,
        SUM(TIMESTAMPDIFF(MINUTE, start_time, IFNULL(end_time, NOW()))) as total_minutes,
        AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_minutes
    FROM time_tracking
    WHERE user_id = ? AND end_time IS NOT NULL
    AND start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$trackingStats->execute([$userId]);
$timeData = $trackingStats->fetch();

// Ambil peringkat tim produksi
$productionRankings = getUserRankings('production_team', 'month', 5);

// Menghitung jam dan menit
$totalHours = floor(($timeData['total_minutes'] ?? 0) / 60);
$totalMinutes = ($timeData['total_minutes'] ?? 0) % 60;

$avgHours = floor(($timeData['avg_minutes'] ?? 0) / 60);
$avgMinutes = ($timeData['avg_minutes'] ?? 0) % 60;

// Ambil progress target individu
$userTargetProgress = getUserTargetProgress($userId, 'daily');

// Ambil progress target kolektif
$collectiveTargetProgress = getCollectiveViralTargetProgress('daily');

$pageTitle = "Dashboard";
include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div class="row">
        <div class="col-md-12">
            <h2>Dashboard Production Team</h2>
            <p class="text-muted">Selamat datang, <b><?php echo htmlspecialchars($_SESSION['name']); ?></b>! 
                <?php if (!empty($userBio)): ?>
                    <span><?php echo htmlspecialchars($userBio); ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    </div>

<div class="container mt-4">
    
    <!-- Program Schedule Widget -->
    <?php include 'program_schedule_widget.php'; ?>
    
    <div class="row mt-4">
        <div class="col-md-12 mb-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Target Individu Harian</h5>
                        </div>
                        <div class="card-body">
                            <h6>Progress: <?= number_format($userTargetProgress['completed'], 1) ?> / <?= number_format($userTargetProgress['target'], 1) ?> poin</h6>
                            <div class="progress mb-3">
                                <div class="progress-bar <?= $userTargetProgress['is_achieved'] ? 'bg-success' : 'bg-primary' ?>" 
                                    role="progressbar" 
                                    style="width: <?= $userTargetProgress['percentage'] ?>%;" 
                                    aria-valuenow="<?= $userTargetProgress['percentage'] ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    <?= $userTargetProgress['percentage'] ?>%
                                </div>
                            </div>
                            <?php if ($userTargetProgress['is_achieved']): ?>
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-trophy me-2"></i> Target poin harian tercapai!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Target Kolektif Konten Viral</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <h6><i class="fab fa-instagram me-2"></i> Instagram</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2">
                                            <div class="progress-bar bg-primary" 
                                                role="progressbar" 
                                                style="width: <?= $collectiveTargetProgress['instagram']['percentage'] ?>%;" 
                                                aria-valuenow="<?= $collectiveTargetProgress['instagram']['percentage'] ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <span class="fw-bold"><?= $collectiveTargetProgress['instagram']['achieved'] ?>/<?= $collectiveTargetProgress['instagram']['target'] ?></span>
                                    </div>
                                    <small class="text-muted">Min. 50.000 views per konten</small>
                                </div>
                                <div class="col-6">
                                    <h6><i class="fab fa-tiktok me-2"></i> TikTok</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2">
                                            <div class="progress-bar bg-danger" 
                                                role="progressbar" 
                                                style="width: <?= $collectiveTargetProgress['tiktok']['percentage'] ?>%;" 
                                                aria-valuenow="<?= $collectiveTargetProgress['tiktok']['percentage'] ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <span class="fw-bold"><?= $collectiveTargetProgress['tiktok']['achieved'] ?>/<?= $collectiveTargetProgress['tiktok']['target'] ?></span>
                                    </div>
                                    <small class="text-muted">Min. 100.000 views per konten</small>
                                </div>
                            </div>
                            
                            <?php 
                            $totalAchieved = $collectiveTargetProgress['instagram']['achieved'] + $collectiveTargetProgress['tiktok']['achieved'];
                            $totalTarget = $collectiveTargetProgress['instagram']['target'] + $collectiveTargetProgress['tiktok']['target'];
                            if ($totalAchieved >= $totalTarget): 
                            ?>
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-trophy me-2"></i> Target kolektif konten viral tercapai!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Task</h5>
                    <h2><?php echo $taskData['total'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Menunggu Konfirmasi</h5>
                    <h2><?php echo $taskData['waiting_confirmation'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Dalam Pengerjaan</h5>
                    <h2><?php echo $taskData['in_production'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Selesai</h5>
                    <h2><?php echo $taskData['completed'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Poin Anda</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h3><?php echo number_format(getUserPoints($userId), 1); ?></h3>
                            <p class="text-muted">Total Poin</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?php echo number_format(getUserPoints($userId, 'month'), 1); ?></h3>
                            <p class="text-muted">Bulan Ini</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?php echo number_format(getUserPoints($userId, 'week'), 1); ?></h3>
                            <p class="text-muted">Minggu Ini</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Peringkat Tim Produksi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Task</th>
                                    <th>Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($productionRankings as $index => $user): 
                                ?>
                                <tr <?php echo ($user['id'] == $userId) ? 'class="table-primary"' : ''; ?>>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php if (!empty($user['profile_photo'])): ?>
                                            <img src="../uploads/profiles/<?php echo $user['profile_photo']; ?>" 
                                                 class="rounded-circle me-2" width="30" height="30" alt="">
                                        <?php else: ?>
                                            <div class="avatar-circle me-2" style="width: 30px; height: 30px;">
                                                <span class="avatar-text"><?php echo substr($user['name'], 0, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </td>
                                    <td><?php echo $user['tasks_completed']; ?></td>
                                    <td><?php echo number_format($user['total_points'], 1); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Task yang Akan Datang</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingTasks)): ?>
                        <p class="text-muted">Tidak ada task yang akan datang</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th>Prioritas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingTasks as $task): ?>
                                        <tr>
                                            <td>
                                                <a href="view_task.php?id=<?php echo $task['id']; ?>">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </a>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($task['account_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($task['deadline'])); ?>
                                                <small class="d-block text-muted"><?php echo date('H:i', strtotime($task['deadline'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($task['task_status']); ?>">
                                                    <?php echo getStatusLabel($task['task_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getPriorityColor($task['priority']); ?>">
                                                    <?php echo getPriorityLabel($task['priority']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="tasks.php" class="btn btn-sm btn-primary">Lihat Semua Task</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
            <div class="card-header">
                <h5>Jadwal Shift Anda</h5>
            </div>
            <div class="card-body">
                <?php
                $today = date('Y-m-d');
                $nextWeek = date('Y-m-d', strtotime('+7 days'));
                $userShifts = getUserShifts($userId, $today, $nextWeek);
                
                if (empty($userShifts)):
                ?>
                    <p class="text-muted">Tidak ada jadwal shift dalam 7 hari ke depan</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Hari</th>
                                    <th>Shift</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userShifts as $shift): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($shift['shift_date'])) ?></td>
                                        <td>
                                            <?php
                                            $dayOfWeek = date('l', strtotime($shift['shift_date']));
                                            $hariIndonesia = [
                                                'Sunday' => 'Minggu',
                                                'Monday' => 'Senin',
                                                'Tuesday' => 'Selasa',
                                                'Wednesday' => 'Rabu',
                                                'Thursday' => 'Kamis',
                                                'Friday' => 'Jumat',
                                                'Saturday' => 'Sabtu'
                                            ];
                                            echo $hariIndonesia[$dayOfWeek];
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= getShiftTypeColor($shift['shift_type']) ?>">
                                                <?= getShiftTypeLabel($shift['shift_type']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="../shared/shifts.php" class="btn btn-sm btn-primary">Lihat Semua Jadwal</a>
                <?php endif; ?>
            </div>
        </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Statistik Time Tracking (30 Hari Terakhir)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h3><?php echo $timeData['total_tasks'] ?? 0; ?></h3>
                            <p class="text-muted">Total Task Dikerjakan</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?php echo $totalHours; ?> jam <?php echo $totalMinutes; ?> menit</h3>
                            <p class="text-muted">Total Waktu Pengerjaan</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?php echo $avgHours; ?> jam <?php echo $avgMinutes; ?> menit</h3>
                            <p class="text-muted">Rata-rata Waktu per Task</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Aktivitas Terbaru</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-muted">Tidak ada aktivitas terbaru</p>
                    <?php else: ?>
                        <div class="timeline small-timeline">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-info">
                                        <span><?php echo date('d M H:i', strtotime($activity['timestamp'])); ?></span>
                                    </div>
                                    <div class="timeline-content">
                                        <p class="mb-0">
                                            <strong><?php echo getStatusLabel($activity['log_status']); ?></strong>
                                            <a href="view_task.php?id=<?php echo $activity['task_id']; ?>">
                                                <?php echo htmlspecialchars($activity['title']); ?>
                                            </a>
                                        </p>
                                        <small class="text-muted">oleh <?php echo htmlspecialchars($activity['updated_by_name']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>Statistik Poin (30 Hari Terakhir)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h3><?= number_format($pointData['total_points'] ?? 0, 1) ?></h3>
                            <p class="text-muted">Total Poin</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?= $pointData['completed_tasks'] ?? 0 ?></h3>
                            <p class="text-muted">Task Selesai</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?= number_format($pointData['avg_points_per_task'] ?? 0, 1) ?></h3>
                            <p class="text-muted">Rata-rata Poin/Task</p>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <a href="../admin/leaderboard.php" class="btn btn-sm btn-primary">Lihat Leaderboard</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.small-timeline {
    position: relative;
    padding-left: 30px;
}
.small-timeline:before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    height: 100%;
    width: 2px;
    background: #e9ecef;
}
.small-timeline .timeline-item {
    position: relative;
    margin-bottom: 15px;
}
.small-timeline .timeline-item:before {
    content: '';
    position: absolute;
    left: -30px;
    top: 3px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
}
.small-timeline .timeline-content {
    margin-left: 20px;
}

.small-timeline .timeline-info {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 3px;
}
</style>

<?php include '../includes/footer.php'; ?>
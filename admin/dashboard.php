<?php
// File: admin/dashboard.php

require_once '../config/database.php';
require_once '../includes/auth.php'; // Tambahkan baris ini
require_once '../includes/functions.php'; // Pastikan functions.php diinclude

redirectIfNotLoggedIn();

// Cek apakah user memiliki role yang sesuai
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Proses pembersihan sesi jika tombol ditekan
if (isset($_POST['cleanup_sessions'])) {
    try {
        // Delete all sessions
        $stmt = $pdo->prepare("DELETE FROM user_sessions");
        $stmt->execute();
        $deletedCount = $stmt->rowCount();
        
        $_SESSION['success'] = "Berhasil membersihkan $deletedCount sesi pengguna";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    // Redirect kembali ke dashboard
    header('Location: dashboard.php');
    exit();
}

$pageTitle = "Dashboard Admin";
include '../includes/header.php';

// Get statistics
$totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$completedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
$overdueTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'overdue'")->fetchColumn();
$tasksNeedingReview = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'uploaded'")->fetchColumn();

// Get target settings
$targetSettings = $pdo->query("SELECT setting_key, setting_value FROM target_settings WHERE setting_key IN ('daily_points_target_production', 'daily_points_target_content')")->fetchAll(PDO::FETCH_KEY_PAIR);

$dailyTargetProduction = $targetSettings['daily_points_target_production'] ?? 3;
$dailyTargetContent = $targetSettings['daily_points_target_content'] ?? 5;

// Get individual daily target progress for all team members
$individualTargetProgress = $pdo->query("
    SELECT 
        u.id,
        u.name,
        u.role,
        u.profile_photo,
        CASE 
            WHEN u.role = 'production_team' THEN $dailyTargetProduction
            WHEN u.role = 'content_team' THEN $dailyTargetContent
            ELSE 0
        END as daily_target,
        COALESCE(SUM(up.points), 0) as points_today
    FROM users u
    LEFT JOIN user_points up ON u.id = up.user_id AND DATE(up.earned_at) = CURDATE()
    WHERE u.role IN ('production_team', 'content_team') AND u.active = 1
    GROUP BY u.id, u.name, u.role, u.profile_photo
    ORDER BY u.role, u.name
")->fetchAll();

// Calculate percentage and achievement status for each user
foreach ($individualTargetProgress as &$user) {
    $user['percentage'] = $user['daily_target'] > 0 ? min(100, round(($user['points_today'] / $user['daily_target']) * 100, 1)) : 0;
    $user['is_achieved'] = $user['points_today'] >= $user['daily_target'];
}

// Get tasks by category
$tasksByCategory = $pdo->query("
    SELECT c.name, COUNT(t.id) as count 
    FROM categories c 
    LEFT JOIN tasks t ON c.id = t.category_id 
    GROUP BY c.name
")->fetchAll();

// Get tasks by status
$tasksByStatus = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM tasks 
    GROUP BY status
")->fetchAll();

// Get top performers
$topContentTeam = $pdo->query("
    SELECT u.name, COUNT(t.id) as task_count 
    FROM users u 
    JOIN tasks t ON u.id = t.created_by 
    WHERE u.role = 'content_team' 
    GROUP BY u.id 
    ORDER BY task_count DESC 
    LIMIT 5
")->fetchAll();

$topProductionTeam = $pdo->query("
    SELECT u.name, COUNT(t.id) as task_count, 
           AVG(TIMESTAMPDIFF(HOUR, ts1.timestamp, ts2.timestamp)) as avg_time 
    FROM users u 
    JOIN tasks t ON u.id = t.assigned_to 
    JOIN task_status_logs ts1 ON t.id = ts1.task_id AND ts1.status = 'waiting_confirmation' 
    JOIN task_status_logs ts2 ON t.id = ts2.task_id AND ts2.status = 'ready_for_review' 
    WHERE u.role = 'production_team' AND t.status IN ('uploaded', 'completed') 
    GROUP BY u.id 
    ORDER BY task_count DESC, avg_time ASC 
    LIMIT 5
")->fetchAll();

// Get point leaderboard for production team
$productionLeaderboard = $pdo->query("
    SELECT u.id, u.name, u.profile_photo,
           COUNT(DISTINCT up.task_id) as tasks_completed,
           SUM(up.points) as total_points
    FROM users u
    LEFT JOIN user_points up ON u.id = up.user_id
    WHERE u.role = 'production_team'
    GROUP BY u.id
    ORDER BY total_points DESC, tasks_completed DESC
    LIMIT 5
")->fetchAll();

// Get point leaderboard for content team
$contentLeaderboard = $pdo->query("
    SELECT u.id, u.name, u.profile_photo,
           COUNT(DISTINCT up.task_id) as tasks_completed,
           SUM(up.points) as total_points
    FROM users u
    LEFT JOIN user_points up ON u.id = up.user_id
    WHERE u.role = 'content_team'
    GROUP BY u.id
    ORDER BY total_points DESC, tasks_completed DESC
    LIMIT 5
")->fetchAll();

// Get production team status
$productionTeamStatus = $pdo->query("
    SELECT 
        u.id,
        u.name,
        u.profile_photo,
        u.last_activity,
        t.id as task_id,
        t.title as current_task,
        t.status as task_status,
        CASE 
            WHEN t.status = 'in_production' THEN 'working'
            WHEN u.last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'online'
            ELSE 'offline'
        END as user_status
    FROM users u
    LEFT JOIN tasks t ON u.id = t.assigned_to AND t.status = 'in_production'
    WHERE u.role = 'production_team' AND u.active = 1
    ORDER BY user_status DESC, u.name ASC
")->fetchAll();
?>


<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard Creative Director</h2>
        <form method="POST" class="d-inline">
            <button type="submit" name="cleanup_sessions" class="btn btn-warning" onclick="return confirm('Apakah Anda yakin ingin membersihkan sesi yang tidak aktif?');">
                <i class="fas fa-broom me-2"></i> Bersihkan Sesi
            </button>
        </form>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total Task</h5>
                    <h2><?php echo $totalTasks; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Selesai</h5>
                    <h2><?php echo $completedTasks; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Overdue</h5>
                    <h2><?php echo $overdueTasks; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Perlu Verifikasi</h5>
                    <h2><?php echo $tasksNeedingReview; ?></h2>
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
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Status Task</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Top Content Team</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jumlah Brief</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topContentTeam as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo $user['task_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Top Production Team</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Task Selesai</th>
                                    <th>Rata Waktu (jam)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProductionTeam as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo $user['task_count']; ?></td>
                                    <td><?php echo number_format($user['avg_time'], 1); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahkan bagian leaderboard poin -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Leaderboard Poin - Content Team</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Task Selesai</th>
                                    <th>Total Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($contentLeaderboard)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada data poin</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($contentLeaderboard as $index => $user): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if (!empty($user['profile_photo'])): ?>
                                                <img src="../uploads/profiles/<?php echo $user['profile_photo']; ?>" 
                                                     class="rounded-circle me-2" width="30" height="30" alt="">
                                            <?php else: ?>
                                                <div class="avatar-circle me-2" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; background-color: #007bff; color: white; border-radius: 50%;">
                                                    <span class="avatar-text"><?php echo substr($user['name'], 0, 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </td>
                                        <td><?php echo $user['tasks_completed'] ?? 0; ?></td>
                                        <td><?php echo number_format($user['total_points'] ?? 0, 1); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Leaderboard Poin - Production Team</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Task Selesai</th>
                                    <th>Total Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productionLeaderboard)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada data poin</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($productionLeaderboard as $index => $user): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if (!empty($user['profile_photo'])): ?>
                                                <img src="../uploads/profiles/<?php echo $user['profile_photo']; ?>" 
                                                     class="rounded-circle me-2" width="30" height="30" alt="">
                                            <?php else: ?>
                                                <div class="avatar-circle me-2" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; background-color: #007bff; color: white; border-radius: 50%;">
                                                    <span class="avatar-text"><?php echo substr($user['name'], 0, 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </td>
                                        <td><?php echo $user['tasks_completed'] ?? 0; ?></td>
                                        <td><?php echo number_format($user['total_points'] ?? 0, 1); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Target Individu Harian -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bullseye me-2"></i>Progress Target Individu Harian</h5>
                    <small class="text-muted">Target poin harian: Production Team (<?= $dailyTargetProduction ?> poin), Content Team (<?= $dailyTargetContent ?> poin)</small>
                </div>
                <div class="card-body">
                    <?php if (empty($individualTargetProgress)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>Belum ada data tim</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $currentRole = '';
                        foreach ($individualTargetProgress as $user): 
                            // Group by role
                            if ($currentRole !== $user['role']):
                                if ($currentRole !== '') echo '</div>';
                                $currentRole = $user['role'];
                                $roleLabel = $user['role'] === 'production_team' ? 'Tim Produksi' : 'Tim Konten';
                                echo '<div class="mb-4">';
                                echo '<h6 class="text-muted mb-3"><i class="fas fa-users me-2"></i>' . $roleLabel . '</h6>';
                            endif;
                        ?>
                            <div class="d-flex align-items-center mb-3 p-3 border rounded">
                                <div class="me-3">
                                    <?php if (!empty($user['profile_photo'])): ?>
                                        <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_photo']) ?>" 
                                             class="rounded-circle" width="50" height="50" alt="">
                                    <?php else: ?>
                                        <div class="avatar-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #007bff; color: white; border-radius: 50%; font-weight: bold; font-size: 18px;">
                                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0"><?= htmlspecialchars($user['name']) ?></h6>
                                        <?php if ($user['is_achieved']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-trophy me-1"></i>Target Tercapai
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Belum Tercapai</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <?= number_format($user['points_today'], 1) ?> / <?= number_format($user['daily_target'], 1) ?> poin
                                        </small>
                                    </div>
                                    
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar <?= $user['is_achieved'] ? 'bg-success' : 'bg-primary' ?> d-flex align-items-center justify-content-center" 
                                             role="progressbar" 
                                             style="width: <?= $user['percentage'] ?>%; font-weight: bold; color: white;" 
                                             aria-valuenow="<?= $user['percentage'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $user['percentage'] ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($currentRole !== '') echo '</div>'; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Tim Produksi -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Status Tim Produksi
                        <small class="text-muted ms-2">Real-time</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (empty($productionTeamStatus)): ?>
                            <div class="col-12 text-center text-muted py-4">
                                <i class="fas fa-user-slash fa-3x mb-3"></i>
                                <p>Tidak ada tim produksi yang aktif</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($productionTeamStatus as $member): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="d-flex align-items-center p-3 border rounded">
                                    <div class="position-relative me-3">
                                        <?php if (!empty($member['profile_photo'])): ?>
                                            <img src="../uploads/profiles/<?= htmlspecialchars($member['profile_photo']) ?>" 
                                                 class="rounded-circle" width="50" height="50" 
                                                 style="object-fit: cover;" alt="">
                                        <?php else: ?>
                                            <div class="avatar-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #007bff; color: white; border-radius: 50%; font-weight: bold;">
                                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Status indicator -->
                                        <span class="position-absolute bottom-0 end-0 status-indicator status-<?= $member['user_status'] ?>"></span>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($member['name']) ?></h6>
                                        
                                        <?php if ($member['user_status'] === 'working'): ?>
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fas fa-cog fa-spin text-warning me-2"></i>
                                                <small class="text-warning fw-bold">Sedang Mengerjakan</small>
                                            </div>
                                            <?php if (!empty($member['current_task'])): ?>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                    <?= htmlspecialchars(strlen($member['current_task']) > 30 ? substr($member['current_task'], 0, 30) . '...' : $member['current_task']) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php elseif ($member['user_status'] === 'online'): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-circle text-success fa-pulse me-1" style="font-size: 0.5rem;"></i>
                                                <small class="text-success">Stand By</small>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-circle text-muted me-2" style="font-size: 0.5rem;"></i>
                                                <small class="text-muted">Offline</small>
                                            </div>
                                            <?php if (!empty($member['last_activity'])): ?>
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                    Terakhir: <?= date('H:i', strtotime($member['last_activity'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($cat) { return "'" . $cat['name'] . "'"; }, $tasksByCategory)); ?>],
            datasets: [{
                label: 'Jumlah Task',
                data: [<?php echo implode(',', array_column($tasksByCategory, 'count')); ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
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
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: [<?php echo implode(',', array_map(function($status) { return "'" . $status['status'] . "'"; }, $tasksByStatus)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_column($tasksByStatus, 'count')); ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)',
                    'rgba(255, 159, 64, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
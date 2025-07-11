
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header('Location: ../dashboard.php');
    exit();
}

// Cleanup sessions jika diminta
if (isset($_POST['cleanup_sessions'])) {
    $pdo->exec("DELETE FROM user_sessions");
    header('Location: dashboard2.php?cleaned=1');
    exit();
}

$today = date('Y-m-d');

// Statistik umum
$generalStats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM tasks WHERE DATE(created_at) = '$today') as tasks_created_today,
        (SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND DATE(updated_at) = '$today') as tasks_completed_today,
        (SELECT COUNT(*) FROM tasks WHERE status = 'overdue') as overdue_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status IN ('waiting_head_confirmation', 'waiting_redaktur_confirmation')) as pending_approval,
        (SELECT COUNT(*) FROM tasks WHERE status = 'in_production') as in_production,
        (SELECT COUNT(*) FROM tasks WHERE status = 'uploaded') as uploaded_tasks
")->fetch();

// Progress target individu - menggunakan target default jika tidak ada setting khusus
$individualProgress = $pdo->query("
    SELECT 
        u.id, u.name, u.role, u.profile_photo,
        COALESCE(ut.daily_target, 
            CASE 
                WHEN u.role = 'content_team' THEN 5
                WHEN u.role = 'production_team' THEN 3
                ELSE 1
            END
        ) as daily_target,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status = 'completed' AND DATE(updated_at) = '$today') as completed_today,
        COALESCE((SELECT SUM(points) FROM user_points WHERE user_id = u.id AND DATE(created_at) = '$today'), 0) as points_today
    FROM users u
    LEFT JOIN user_targets ut ON u.id = ut.user_id
    WHERE u.role IN ('content_team', 'production_team')
    AND u.is_active = 1
    ORDER BY u.role, u.name
")->fetchAll();

// Hitung persentase dan status untuk setiap user
foreach ($individualProgress as &$user) {
    $user['progress_percentage'] = $user['daily_target'] > 0 ? 
        round(($user['completed_today'] / $user['daily_target']) * 100, 1) : 0;
    
    if ($user['completed_today'] >= $user['daily_target']) {
        $user['target_status'] = 'achieved';
    } elseif ($user['progress_percentage'] >= 80) {
        $user['target_status'] = 'almost';
    } else {
        $user['target_status'] = 'behind';
    }
}

// Target kolektif harian
$collectiveTarget = $pdo->query("
    SELECT 
        SUM(COALESCE(ut.daily_target, 
            CASE 
                WHEN u.role = 'content_team' THEN 5
                WHEN u.role = 'production_team' THEN 3
                ELSE 1
            END
        )) as total_target,
        SUM((SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status = 'completed' AND DATE(updated_at) = '$today')) as total_completed,
        SUM(COALESCE((SELECT SUM(points) FROM user_points WHERE user_id = u.id AND DATE(created_at) = '$today'), 0)) as total_points
    FROM users u
    LEFT JOIN user_targets ut ON u.id = ut.user_id
    WHERE u.role IN ('content_team', 'production_team')
    AND u.is_active = 1
")->fetch();

// Status tim produksi
$productionTeamStatus = $pdo->query("
    SELECT 
        u.id, u.name, u.profile_photo,
        CASE 
            WHEN t.id IS NOT NULL AND t.status IN ('in_production', 'revision') THEN 'working'
            WHEN t.id IS NOT NULL AND t.status IN ('waiting_confirmation', 'ready_for_review') THEN 'pending'
            ELSE 'standby'
        END as work_status,
        t.id as task_id, t.title as task_title, t.deadline,
        a.name as account_name
    FROM users u
    LEFT JOIN tasks t ON u.id = t.assigned_to AND t.status IN ('in_production', 'revision', 'waiting_confirmation', 'ready_for_review')
    LEFT JOIN accounts a ON t.account_id = a.id
    WHERE u.role = 'production_team' AND u.is_active = 1
    ORDER BY u.name
")->fetchAll();

// Tasks yang memerlukan perhatian segera
$urgentTasks = $pdo->query("
    SELECT t.*, u_creator.name as creator_name, u_assignee.name as assignee_name, 
           a.name as account_name, c.name as category_name
    FROM tasks t
    LEFT JOIN users u_creator ON t.created_by = u_creator.id
    LEFT JOIN users u_assignee ON t.assigned_to = u_assignee.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE (
        (t.status = 'overdue') OR
        (t.deadline <= DATE_ADD(NOW(), INTERVAL 2 HOUR) AND t.status IN ('in_production', 'waiting_confirmation')) OR
        (t.status IN ('waiting_head_confirmation', 'waiting_redaktur_confirmation') AND t.created_at <= DATE_SUB(NOW(), INTERVAL 2 HOUR))
    )
    ORDER BY 
        CASE 
            WHEN t.status = 'overdue' THEN 1
            WHEN t.deadline <= NOW() THEN 2
            WHEN t.deadline <= DATE_ADD(NOW(), INTERVAL 2 HOUR) THEN 3
            ELSE 4
        END,
        t.deadline ASC
    LIMIT 10
")->fetchAll();

// Performance metrics
$performanceMetrics = $pdo->query("
    SELECT 
        AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) as avg_completion_time,
        (SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND DATE(updated_at) >= DATE_SUB('$today', INTERVAL 7 DAY)) as completed_this_week,
        (SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND DATE(updated_at) >= DATE_SUB('$today', INTERVAL 30 DAY)) as completed_this_month,
        (SELECT AVG(rating) FROM task_ratings WHERE DATE(created_at) >= DATE_SUB('$today', INTERVAL 7 DAY)) as avg_rating_this_week
    FROM tasks t
    WHERE t.status = 'completed' 
    AND DATE(t.updated_at) >= DATE_SUB('$today', INTERVAL 7 DAY)
")->fetch();



// Fungsi helper untuk format waktu
function formatTimeDifference($seconds) {
    $units = [
        'hari' => 86400,
        'jam' => 3600,
        'menit' => 60
    ];
    
    foreach ($units as $unit => $value) {
        if ($seconds >= $value) {
            $time = floor($seconds / $value);
            return $time . ' ' . $unit;
        }
    }
    
    return $seconds . ' detik';
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard Creative Director</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Alert untuk tasks urgent -->
    <?php if (count($urgentTasks) > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Perhatian!</strong> Ada <?= count($urgentTasks) ?> task yang memerlukan perhatian segera.
        <a href="#urgent-tasks" class="alert-link">Lihat detail</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Target Kolektif Harian -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bullseye"></i> Target Kolektif Harian - <?= date('d F Y') ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h3 class="text-primary"><?= $collectiveTarget['total_completed'] ?></h3>
                            <p class="mb-0">Task Selesai Hari Ini</p>
                        </div>
                        <div class="col-md-3">
                            <h3 class="text-info"><?= $collectiveTarget['total_target'] ?></h3>
                            <p class="mb-0">Target Harian Total</p>
                        </div>
                        <div class="col-md-3">
                            <?php 
                                                        $collective_percentage = $collectiveTarget['total_target'] > 0 ? 
                                round(($collectiveTarget['total_completed'] / $collectiveTarget['total_target']) * 100, 1) : 0;
                            ?>
                            <h3 class="text-<?= $collective_percentage >= 100 ? 'success' : ($collective_percentage >= 80 ? 'warning' : 'danger') ?>">
                                <?= $collective_percentage ?>%
                            </h3>
                            <p class="mb-0">Progress Kolektif</p>
                        </div>
                        <div class="col-md-3">
                            <h3 class="text-success"><?= number_format($collectiveTarget['total_points']) ?></h3>
                            <p class="mb-0">Total Poin Hari Ini</p>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 20px;">
                        <div class="progress-bar bg-<?= $collective_percentage >= 100 ? 'success' : ($collective_percentage >= 80 ? 'warning' : 'danger') ?>" 
                             style="width: <?= min($collective_percentage, 100) ?>%">
                            <?= $collective_percentage ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Umum -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-primary"><?= $generalStats['tasks_created_today'] ?></h4>
                    <p class="mb-0">Task Dibuat Hari Ini</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-success"><?= $generalStats['tasks_completed_today'] ?></h4>
                    <p class="mb-0">Task Selesai Hari Ini</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-primary"><?= $generalStats['in_production'] ?></h4>
                    <p class="mb-0">Dalam Produksi</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-warning"><?= $generalStats['pending_approval'] ?></h4>
                    <p class="mb-0">Menunggu Approval</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-danger"><?= $generalStats['overdue_tasks'] ?></h4>
                    <p class="mb-0">Overdue</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-secondary"><?= $generalStats['uploaded_tasks'] ?></h4>
                    <p class="mb-0">Uploaded</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Individual -->
    <div class="row mb-4">
        <!-- Progress Tim Konten -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Progress Individual Tim</h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php 
                    $current_role = '';
                    foreach ($individualProgress as $user): 
                        if ($current_role !== $user['role']):
                            if ($current_role !== '') echo '</div>';
                            $role_name = $user['role'] === 'content_team' ? 'Tim Konten' : 'Tim Produksi';
                            echo '<h6 class="text-muted mb-3 mt-3">' . $role_name . '</h6>';
                            echo '<div class="role-group">';
                            $current_role = $user['role'];
                        endif;
                    ?>
                        <div class="d-flex align-items-center mb-3 p-2 border rounded">
                            <div class="me-3">
                                <?php if (!empty($user['profile_photo'])): ?>
                                    <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_photo']) ?>" 
                                         class="rounded-circle" width="40" height="40" alt="Profile">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                                    <span class="badge bg-<?= $user['target_status'] === 'achieved' ? 'success' : ($user['target_status'] === 'almost' ? 'warning' : 'danger') ?>">
                                        <?= $user['completed_today'] ?>/<?= $user['daily_target'] ?>
                                    </span>
                                </div>
                                <div class="progress mt-1" style="height: 8px;">
                                    <div class="progress-bar bg-<?= $user['target_status'] === 'achieved' ? 'success' : ($user['target_status'] === 'almost' ? 'warning' : 'danger') ?>" 
                                         style="width: <?= min($user['progress_percentage'], 100) ?>%"></div>
                                </div>
                                <small class="text-muted">
                                    <?= $user['progress_percentage'] ?>% • <?= number_format($user['points_today']) ?> poin
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($current_role !== '') echo '</div>'; ?>
                </div>
            </div>
        </div>

        <!-- Status Tim Produksi -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs"></i> Status Tim Produksi</h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($productionTeamStatus as $member): ?>
                        <div class="d-flex align-items-center mb-3 p-2 border rounded">
                            <div class="me-3">
                                <?php if (!empty($member['profile_photo'])): ?>
                                    <img src="../uploads/profiles/<?= htmlspecialchars($member['profile_photo']) ?>" 
                                         class="rounded-circle" width="40" height="40" alt="Profile">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?= htmlspecialchars($member['name']) ?></strong>
                                    <span class="badge bg-<?= $member['work_status'] === 'working' ? 'success' : ($member['work_status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                        <?= $member['work_status'] === 'working' ? 'Sedang Bekerja' : ($member['work_status'] === 'pending' ? 'Menunggu' : 'Stand By') ?>
                                    </span>
                                </div>
                                <?php if ($member['work_status'] !== 'standby'): ?>
                                    <div class="mt-1">
                                        <small class="text-primary">
                                            <i class="fas fa-tasks"></i> 
                                            <a href="../view_task.php?id=<?= $member['task_id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars(cleanWhatsAppFormatting($member['task_title'])) ?>
                                            </a>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-building"></i> <?= htmlspecialchars($member['account_name']) ?>
                                            <?php if ($member['deadline']): ?>
                                                • <i class="fas fa-clock"></i> <?= date('d M H:i', strtotime($member['deadline'])) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">Siap menerima task baru</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks yang Memerlukan Perhatian -->
    <?php if (count($urgentTasks) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning" id="urgent-tasks">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Tasks yang Memerlukan Perhatian</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Prioritas</th>
                                    <th>Task</th>
                                    <th>Akun</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Ditugaskan Ke</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($urgentTasks as $task): ?>
                                    <tr class="<?= $task['status'] === 'overdue' ? 'table-danger' : 'table-warning' ?>">
                                        <td>
                                            <?php if ($task['status'] === 'overdue'): ?>
                                                <span class="badge bg-danger">OVERDUE</span>
                                                                                        <?php elseif (strtotime($task['deadline']) <= time()): ?>
                                                <span class="badge bg-danger">DEADLINE</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">URGENT</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($task['category_name']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($task['account_name']) ?></td>
                                        <td><?= getStatusBadge($task['status']) ?></td>
                                        <td>
                                            <?php if ($task['deadline']): ?>
                                                <?= date('d M Y H:i', strtotime($task['deadline'])) ?>
                                                <br>
                                                <small class="text-<?= strtotime($task['deadline']) <= time() ? 'danger' : 'warning' ?>">
                                                    <?php
                                                    $diff = time() - strtotime($task['deadline']);
                                                    if ($diff > 0) {
                                                        echo 'Terlambat ' . formatTimeDifference($diff);
                                                    } else {
                                                        echo 'Sisa ' . formatTimeDifference(abs($diff));
                                                    }
                                                    ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak ada deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($task['creator_name']) ?></td>
                                        <td><?= htmlspecialchars($task['assignee_name']) ?></td>
                                        <td>
                                            <a href="../view_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Lihat
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Performance Metrics & Quick Actions -->
    <div class="row mb-4">
        <!-- Performance Metrics -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Performance Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary"><?= round($performanceMetrics['avg_completion_time'], 1) ?> jam</h4>
                            <p class="mb-0">Rata-rata Waktu Penyelesaian</p>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success"><?= $performanceMetrics['completed_this_week'] ?></h4>
                            <p class="mb-0">Selesai Minggu Ini</p>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info"><?= $performanceMetrics['completed_this_month'] ?></h4>
                            <p class="mb-0">Selesai Bulan Ini</p>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning"><?= round($performanceMetrics['avg_rating_this_week'], 1) ?>/5</h4>
                            <p class="mb-0">Rating Rata-rata Minggu Ini</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="users.php" class="btn btn-outline-primary">
                            <i class="fas fa-users"></i> Kelola User & Target
                        </a>
                        <a href="tasks.php?status=waiting_head_confirmation" class="btn btn-outline-warning">
                            <i class="fas fa-clock"></i> Review Tasks Menunggu Approval
                        </a>
                        <a href="reports.php" class="btn btn-outline-info">
                            <i class="fas fa-chart-bar"></i> Lihat Laporan Lengkap
                        </a>
                        <a href="settings.php" class="btn btn-outline-secondary">
                            <i class="fas fa-cog"></i> Pengaturan Sistem
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cleanup Sessions (existing functionality) -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Maintenance</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="d-inline">
                        <button type="submit" name="cleanup_sessions" class="btn btn-warning" 
                                onclick="return confirm('Yakin ingin membersihkan semua sesi? Semua user akan logout.')">
                            <i class="fas fa-broom"></i> Bersihkan Semua Sesi
                        </button>
                    </form>
                    <small class="text-muted d-block mt-2">
                        Gunakan fitur ini jika ada masalah dengan sesi user atau untuk maintenance sistem.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.progress {
    background-color: #e9ecef;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
}

.badge {
    font-size: 0.75em;
}

.role-group {
    border-left: 3px solid #007bff;
    padding-left: 15px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .card-body {
        max-height: 300px !important;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

<script>
// Auto refresh setiap 5 menit
setTimeout(function() {
    location.reload();
}, 300000);

// Update waktu real-time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID');
    const dateString = now.toLocaleDateString('id-ID', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Update jika ada element untuk menampilkan waktu
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = `${dateString} - ${timeString}`;
    }
}

// Update waktu setiap detik
setInterval(updateTime, 1000);
updateTime();

// Tooltip untuk progress bars
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Smooth scroll untuk urgent tasks
document.addEventListener('DOMContentLoaded', function() {
    const urgentLink = document.querySelector('a[href="#urgent-tasks"]');
    if (urgentLink) {
        urgentLink.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('urgent-tasks').scrollIntoView({
                behavior: 'smooth'
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
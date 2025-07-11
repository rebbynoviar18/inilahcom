<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Ambil bulan dan tahun dari parameter URL atau gunakan bulan saat ini
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
list($year, $month) = explode('-', $selectedMonth);

// Validasi bulan dan tahun
if (!checkdate($month, 1, $year)) {
    $selectedMonth = date('Y-m');
    list($year, $month) = explode('-', $selectedMonth);
}

// Format untuk tampilan
$monthName = date('F Y', strtotime($selectedMonth . '-01'));

// Ambil data bio user dari database
$userStmt = $pdo->prepare("SELECT bio FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch();
$userBio = $userData['bio'] ?? '';

// Ambil data untuk ditampilkan
$redaksiAgenda = getAgendaItemsDesc('redaksi', 10);
$settingsAgenda = getAgendaItemsDesc('settings', 10);
$generalInfo = getGeneralInfo();

// Total points bulan ini
$pointsQuery = $pdo->prepare("SELECT COALESCE(SUM(points), 0) as total_points FROM user_points WHERE user_id = ? AND MONTH(earned_at) = ? AND YEAR(earned_at) = ?");
$pointsQuery->execute([$userId, $month, $year]);
$totalPoints = $pointsQuery->fetchColumn();

// Total posting (platform distribusi) - perbaikan query
$postsQuery = $pdo->prepare("
    SELECT COUNT(tl.id) as total_posts 
    FROM task_links tl
    JOIN tasks t ON tl.task_id = t.id
    WHERE (t.assigned_to = ? OR t.created_by = ?)
    AND t.status = 'completed'
    AND MONTH(tl.created_at) = ?
    AND YEAR(tl.created_at) = ?
");
$postsQuery->execute([$userId, $userId, $month, $year]);
$totalPosts = $postsQuery->fetchColumn();

// Total brief yang dibuat
$briefsQuery = $pdo->prepare("SELECT COUNT(*) as total_briefs FROM tasks WHERE created_by = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
$briefsQuery->execute([$userId, $month, $year]);
$totalBriefs = $briefsQuery->fetchColumn();

// Total task yang diselesaikan
$tasksQuery = $pdo->prepare("
    SELECT COUNT(*) as total_tasks 
    FROM tasks 
    WHERE (created_by = ? OR assigned_to = ?) 
    AND status = 'completed' 
    AND MONTH(updated_at) = ? 
    AND YEAR(updated_at) = ?
");
$tasksQuery->execute([$userId, $userId, $month, $year]);
$totalTasks = $tasksQuery->fetchColumn();

// Buat array untuk digunakan di tampilan
$monthlyStats = [
    'total_points' => $totalPoints,
    'total_posts' => $totalPosts,
    'total_briefs' => $totalBriefs,
    'total_tasks' => $totalTasks
];

// Statistik task
$stats = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM tasks WHERE created_by = ? OR assigned_to = ?) as total_tasks,
        (SELECT COUNT(*) FROM tasks WHERE (created_by = ? OR assigned_to = ?) AND status = 'completed') as completed_tasks,
        (SELECT COUNT(*) FROM tasks WHERE (created_by = ? OR assigned_to = ?) AND status = 'uploaded') as uploaded_tasks,
        (SELECT COUNT(*) FROM tasks WHERE (created_by = ? OR assigned_to = ?) AND status = 'revision') as total_revisions
    FROM dual
");
$stats->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
$stats = $stats->fetch();

// Dapatkan task yang akan datang
$upcomingTasks = $pdo->prepare("
    SELECT t.id, t.title, t.deadline, t.priority, t.status as task_status, 
           a.name as account_name, t.created_by, u.name as created_by_name
    FROM tasks t
    JOIN accounts a ON t.account_id = a.id
    JOIN users u ON t.created_by = u.id
    WHERE t.assigned_to = ? AND t.status IN ('waiting_confirmation', 'in_production', 'revision')
    ORDER BY t.deadline ASC
    LIMIT 5
");
$upcomingTasks->execute([$userId]);
$upcomingTasks = $upcomingTasks->fetchAll();

// Task terbaru
$recentTasks = $pdo->prepare("
    SELECT t.*, a.name as account_name 
    FROM tasks t
    JOIN accounts a ON t.account_id = a.id
    WHERE t.created_by = ? OR t.assigned_to = ?
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recentTasks->execute([$userId, $userId]);

// Ambil progress target individu
$userTargetProgress = getUserTargetProgress($userId, 'daily');

// Ambil progress target kolektif
$collectiveTargetProgress = getCollectiveViralTargetProgress('daily');

// Get point leaderboard for content team
$contentLeaderboard = getUserRankings('content_team', $selectedMonth, 5);

// Ambil jadwal shift
$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$userShifts = getUserShifts($userId, $today, $nextWeek);

$pageTitle = "Dashboard Content Team";
include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h2>Dashboard Content Team</h2>
        <p class="text-muted">Selamat datang, <b><?php echo htmlspecialchars($_SESSION['name']); ?></b>! 
            <?php if (!empty($userBio)): ?>
                <span><?php echo htmlspecialchars($userBio); ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div>
        <form method="get" class="d-flex align-items-center">
            <label for="month" class="me-2">Pilih Bulan:</label>
            <input type="month" id="month" name="month" value="<?= $selectedMonth ?>" class="form-control form-control-sm me-2" style="width: 200px;">
            <button type="submit" class="btn btn-sm btn-primary">Tampilkan</button>
        </form>
    </div>
</div>

<!-- Baris 1 -->
<div class="row mb-4">
    <!-- Row 1 (40%) -->
    <div class="col-md-5">
        <div class="row">
            <!-- Total Point -->
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Point</h5>
                    <h2><?= number_format($monthlyStats['total_points'], 1) ?></h2>
                    <p class="text-muted">Bulan <?= $monthName ?></p>
                </div>
            </div>
            </div>
            
            <!-- Total Posting -->
            <div class="col-md-6">
                <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Posting</h5>
                    <h2><?= $monthlyStats['total_posts'] ?></h2>
                    <p class="text-muted">Distribusi platform</p>
                </div>
            </div>
            </div>

            <!-- Total Brief -->
            <div class="col-md-6">
                <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Brief</h5>
                    <h2><?= $monthlyStats['total_briefs'] ?></h2>
                    <p class="text-muted">Brief yang dibuat</p>
                </div>
            </div>
            </div>
            
            <!-- Total Task -->
            <div class="col-md-6">
                <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Task</h5>
                    <h2><?= $monthlyStats['total_tasks'] ?></h2>
                        <p class="text-muted">Task diselesaikan</p>
                </div>
            </div>
            </div>
        </div>
        <!-- Statistik Task -->

        <div class="card mb-3">
            <div class="card-header">
                <h5>Task yang Akan Datang</h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingTasks)): ?>
                    <p class="text-muted">Tidak ada task yang akan datang</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($upcomingTasks as $task): ?>
                        <a href="view_task.php?id=<?= $task['id'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h6>
                                <small><?= getStatusBadge($task['task_status']) ?></small>
                            </div>
                            <div class="d-flex w-100 justify-content-between">
                                <small class="text-muted"><?= htmlspecialchars($task['account_name']) ?></small>
                                <small><?= date('d M Y', strtotime($task['deadline'])) ?></small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <a href="tasks.php" class="btn btn-sm btn-primary mt-2">Lihat Semua Task</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Brief terbaru -->
        <div class="card">
            <div class="card-header">
                <h5>Brief Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php while ($task = $recentTasks->fetch()): ?>
                    <a href="view_task.php?id=<?= $task['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h6>
                        <small><?= getStatusBadge($task['status']) ?></small>
                        </div>
                            <small><?= date('d M', strtotime($task['created_at'])) ?></small>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2 (30%) -->
    <div class="col-md-3">
        <!-- Target Individu Harian -->
        <div class="card mb-3">
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
                    <div class="alert alert-success mb-0 py-2">
                        <i class="fas fa-trophy me-2"></i> Target tercapai!
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Target Kolektif Konten Viral Harian -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Target Kolektif Viral Harian</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
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
                    <small class="text-muted">Min. 100.000 views</small>
                </div>
                
                <div class="mb-3">
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
                    <small class="text-muted">Min. 500.000 views</small>
                </div>
                
                <?php 
                $totalAchieved = $collectiveTargetProgress['instagram']['achieved'] + $collectiveTargetProgress['tiktok']['achieved'];
                $totalTarget = $collectiveTargetProgress['instagram']['target'] + $collectiveTargetProgress['tiktok']['target'];
                if ($totalAchieved >= $totalTarget): 
                ?>
                    <div class="alert alert-success mb-0 py-2">
                        <i class="fas fa-trophy me-2"></i> Target tercapai!
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Jadwal Shift Anda -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Jadwal Shift Anda</h5>
            </div>
            <div class="card-body">
                <?php if (empty($userShifts)): ?>
                    <p class="text-muted">Tidak ada jadwal shift hari ini</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
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
                                        <td style="padding: 0.5rem 0.5rem;font-size:0.6rem;"><?= date('d M Y', strtotime($shift['shift_date'])) ?></td>
                                        <td style="padding: 0.5rem 0.5rem;">
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
                                        <td style="padding: 0.5rem 0.5rem;">
                                            <span class="badge bg-<?= getShiftTypeColor($shift['shift_type']) ?>">
                                                <?= getShiftTypeLabel($shift['shift_type']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="../shared/shifts.php" class="btn btn-sm btn-primary mt-2">Lihat Jadwal</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Peringkat Tim Konten -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Peringkat Tim Konten</h5>
            </div>
            <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
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
                                        <td style="padding: 0.5rem 0.5rem;"><b style="padding-right:0.5rem;"><?php echo $index + 1; ?>.</b>
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
                                        <td style="padding: 0.5rem 1.5rem;"><?php echo number_format($user['total_points'] ?? 0, 1); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
    </div>
    
    <!-- Row 3 (30%) -->
    <div class="col-md-4">
        <!-- Informasi Umum -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Informasi Umum</h5>
            </div>
            <div class="card-body small">
                <?php echo $generalInfo; ?>
            </div>
        </div>
        
        <!-- Agenda Settings -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Agenda Settings</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($settingsAgenda)): ?>
                    <p class="text-muted small p-3 mb-0">Tidak ada agenda settings saat ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <tbody class="small">
                                <?php foreach ($settingsAgenda as $index => $item): ?>
                                <tr>
                                    <td width="77%" style="padding: 0.5rem 1rem;"><?= $index + 1 ?>. <?= htmlspecialchars($item['title']) ?></td>
                                    <td width="23%" style="padding: 0.5rem 1rem;font-size:0.6rem;"><?= date('d M Y', strtotime($item['date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Agenda Redaksi -->
        <div class="card">
            <div class="card-header">
                <h5>Agenda Redaksi</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($redaksiAgenda)): ?>
                    <p class="text-muted small p-3 mb-0">Tidak ada agenda redaksi saat ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <tbody class="small">
                                <?php foreach ($redaksiAgenda as $index => $item): ?>
                                <tr>
                                    <td width="77%" style="padding: 0.5rem 1rem;"><?= $index + 1 ?>. <?= htmlspecialchars($item['title']) ?></td>
                                    <td width="23%" style="padding: 0.5rem 1rem;font-size:0.6rem;"><?= date('d M Y', strtotime($item['date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/functions/program_schedule.php';

// Tambahkan fungsi untuk mengambil RSS feed
function fetchRssFeed($url, $limit = 10) {
    try {
        $feed = simplexml_load_file($url);
        if (!$feed) {
            return [];
        }
        
        $items = [];
        $count = 0;
        
        // Untuk Atom feed (inilah.com menggunakan format Atom)
        foreach ($feed->entry as $item) {
            if ($count >= $limit) break;
            
            $items[] = [
                'title' => (string)$item->title,
                'link' => (string)$item->link['href'],
                'date' => (string)$item->published
            ];
            $count++;
        }
        
        return $items;
    } catch (Exception $e) {
        return [];
    }
}

// Ambil berita dari RSS feed
$newsItems = fetchRssFeed('https://www.inilah.com/atom.xml', 10);

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

// Perbaiki query production team status untuk menambahkan nama pembuat task

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
        creator.name as task_creator_name,
        ct.name as content_type_name,
        CASE 
            WHEN t.status = 'in_production' THEN 'working'
            WHEN u.last_activity >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
            ELSE 'offline'
        END as user_status
    FROM users u
    LEFT JOIN tasks t ON u.id = t.assigned_to AND t.status = 'in_production'
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    WHERE u.role = 'production_team' AND u.active = 1
    ORDER BY user_status DESC, u.name ASC
")->fetchAll();

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

<!-- News Ticker -->
<div class="card mb-3 news-ticker-container">
    <div class="card-body p-0">
        <div class="d-flex align-items-center">
            <div class="news-ticker-label bg-primary text-white px-3 py-2">
                <img src="../assets/images/logo_inilah.png" style="max-height:20px;">
            </div>
            <div class="news-ticker-content">
                <?php if (empty($newsItems)): ?>
                    <div class="ticker-item">Tidak dapat memuat berita terkini</div>
                <?php else: ?>
                    <div class="ticker-wrapper">
                        <div class="ticker-items">
                            <?php foreach ($newsItems as $news): ?>
                                <div class="ticker-item">
                                    <a href="<?= htmlspecialchars($news['link']) ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars($news['title']) ?> 
                                    </a>
                                    <span class="ticker-separator">•</span>
                                </div>
                            <?php endforeach; ?>
                            <!-- Duplikasi item untuk efek seamless -->
                            <?php foreach ($newsItems as $news): ?>
                                <div class="ticker-item">
                                    <a href="<?= htmlspecialchars($news['link']) ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars($news['title']) ?>
                                    </a>
                                    <span class="ticker-separator">•</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Program Schedule Widget -->
<?php include 'program_schedule_widget.php'; ?>

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
                    <div class="progress-bar <?= $userTargetProgress['is_achieved'] ? 'bg-success progress-bar-animated' : 'progress-bar-striped progress-bar-animated' ?>" 
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
                    <h6><i class="fab fa-instagram me-2"></i> Instagram 
                        (<?= $collectiveTargetProgress['instagram']['achieved'] ?>/<?= $collectiveTargetProgress['instagram']['target'] ?>)</h6>
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2">
                            <div class="progress-bar <?= $collectiveTargetProgress['instagram']['percentage'] >= 100 ? 'bg-success progress-bar-animated' : 'bg-primary progress-bar-striped progress-bar-animated' ?>" 
                                role="progressbar" 
                                style="width: <?= $collectiveTargetProgress['instagram']['percentage'] ?>%;" 
                                aria-valuenow="<?= $collectiveTargetProgress['instagram']['percentage'] ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Min. <?= number_format(getViewsTarget('instagram', 'weekly')) ?> views</small>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fab fa-tiktok me-2"></i> TikTok (<?= $collectiveTargetProgress['tiktok']['achieved'] ?>/<?= $collectiveTargetProgress['tiktok']['target'] ?>)</h6>
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2">
                            <div class="progress-bar <?= $collectiveTargetProgress['tiktok']['percentage'] >= 100 ? 'bg-success progress-bar-animated' : 'bg-danger progress-bar-striped progress-bar-animated' ?>" 
                                role="progressbar" 
                                style="width: <?= $collectiveTargetProgress['tiktok']['percentage'] ?>%;" 
                                aria-valuenow="<?= $collectiveTargetProgress['tiktok']['percentage'] ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Min. <?= number_format(getViewsTarget('tiktok', 'weekly')) ?> views</small>
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
        <h5>Jadwal Shift Kamu Hari Ini</h5>
    </div>
    <div class="card-body">
        <?php 
        $today = date('Y-m-d');
        $todayShift = null;
        
        foreach ($userShifts as $shift) {
            if ($shift['shift_date'] == $today) {
                $todayShift = $shift;
                break;
            }
        }
        
        if (!$todayShift): 
        ?>
            <p class="text-muted">Tidak ada jadwal shift hari ini</p>
        <?php else: 
            $dayOfWeek = date('l', strtotime($todayShift['shift_date']));
            $hariIndonesia = [
                'Sunday' => 'Minggu',
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu'
            ];
        ?>
            <h6 class="mb-3"><?= $hariIndonesia[$dayOfWeek] ?>, <?= date('d M Y', strtotime($todayShift['shift_date'])) ?></h6>
            <div class="mt-2">
                <span class="badge bg-<?= getShiftTypeColor($todayShift['shift_type']) ?> p-2 fs-4">
                    <?= getShiftTypeLabel($todayShift['shift_type']) ?>
                </span>
            </div>
        <?php endif; ?>
        <div class="mt-3">
            <a href="../shared/shifts.php" class="btn btn-sm btn-primary">Lihat jadwal lengkap</a>
        </div>
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

    <!-- Modern Weather & Time Widget -->
    <div class="card mb-3 weather-widget-container">
        <div class="card-body p-0">
            <div class="weather-time-widget enhanced morning clear" id="weatherWidget">
                <!-- Animated Elements -->
                <div class="sun" id="sun"></div>
                <div class="moon" id="moon"></div>
                
                <!-- Weather Elements -->
                <div class="cloud-container" id="cloudContainer"></div>
                <div class="rain-container" id="rainContainer"></div>
                <div class="star-container" id="starContainer"></div>
                
                <!-- Time Section -->
                <div class="time-section">
                    <div class="time-display" id="timeDisplay">
                        <span class="hours">00</span>:<span class="minutes">00</span>:<span class="seconds">00</span>
                    </div>
                    <div class="date-display" id="dateDisplay">Rabu, 18 November 1992</div>
                </div>
                
                <!-- Weather Section -->                
                <div class="weather-section">
                    <div class="temperature" id="temperature">24<span class="degree">°C</span></div>
                    <div class="weather-details">
                        <div class="weather-condition" id="weatherCondition">Cerah</div>
                        <div class="location" id="location"><i class="fas fa-map-marker-alt" style="padding-right:0.3rem;"></i>Jakarta, ID</div>
                    </div>
                </div>
            </div>
        </div>
    </div>







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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Agenda Settings</h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#settingsAgenda" aria-expanded="false" aria-controls="settingsAgenda">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="collapse" id="settingsAgenda">
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
        </div>
        
        <!-- Agenda Redaksi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Agenda Redaksi</h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#redaksiAgenda" aria-expanded="false" aria-controls="redaksiAgenda">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="collapse" id="redaksiAgenda">
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







<div class="row mt-3">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Status Tim Produksi
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
                            <div class="mb-2">
                                <div class="d-flex align-items-center p-2 border rounded">
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
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-cog fa-spin text-warning me-1" style="font-size: 0.75rem;"></i>
                                                <small class="text-warning fw-bold">Lagi ngerjain <?= htmlspecialchars($member['content_type_name'] ?? 'Task') ?> dari <?= htmlspecialchars($member['task_creator_name'] ?? 'Unknown') ?></small>
                                            </div>
                                            <?php if (!empty($member['current_task'])): ?>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;line-height: 1.2;">
                                                    <?= htmlspecialchars(strlen($member['current_task']) > 0 ? cleanWhatsAppFormatting(substr($member['current_task'], 0)) . '.' : cleanWhatsAppFormatting($member['current_task'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php elseif ($member['user_status'] === 'online'): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-circle text-success fa-pulse me-1" style="font-size: 0.5rem;"></i>
                                                <small class="text-success">Lagi Gabut, kasih kerjaan dong</small>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-circle text-muted me-1" style="font-size: 0.5rem;"></i>
                                                <small class="text-muted">Offline</small>
                                            </div>
                                            <?php if (!empty($member['last_activity'])): ?>
                                                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                    Terakhir Aktif: <?= date('H:i', strtotime($member['last_activity'])) ?> WIB
                                                    <?php
                                                    $lastActivityDate = new DateTime($member['last_activity']);
                                                    $today = new DateTime();
                                                    $today->setTime(0, 0, 0); // Set waktu ke 00:00:00 untuk perbandingan tanggal saja
                                                    $yesterday = clone $today;
                                                    $yesterday->modify('-1 day');
                                                    
                                                    $activityDay = clone $lastActivityDate;
                                                    $activityDay->setTime(0, 0, 0); // Set waktu ke 00:00:00 untuk perbandingan tanggal saja
                                                    
                                                    if ($activityDay == $today) {
                                                        echo "(Hari ini)";
                                                    } elseif ($activityDay == $yesterday) {
                                                        echo "(Kemarin)";
                                                    } else {
                                                        $diff = $today->diff($activityDay);
                                                        echo "(" . $diff->days . " hari yang lalu)";
                                                    }
                                                    ?>
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




    </div>
</div>

<style>
.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
}

.news-ticker-container {
    overflow: hidden;
    border-radius: 7px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-top: 10px;
}

.news-ticker-label {
    white-space: nowrap;
    font-weight: bold;
    min-width: 100px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%);
}

.news-ticker-content {
    flex: 1;
    overflow: hidden;
}

.ticker-wrapper {
    width: 100%;
    overflow: hidden;
    position: relative;
}

.ticker-items {
    display: inline-flex;
    animation: tickerAnimation 80s linear infinite;
    white-space: nowrap;
}

.ticker-items:hover {
    animation-play-state: paused;
}

.ticker-item {
    padding: 0 15px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
}

.ticker-item a {
    color: #333;
    transition: color 0.2s ease;
}

.ticker-item a:hover {
    color: #5e72e4;
}

.ticker-separator {
    margin-left: 25px;
    color: #5e72e4;
    font-weight: bold;
    font-size: 1.5em;
}

/* Hilangkan separator pada item terakhir */
.ticker-item:last-child .ticker-separator {
    display: none;
}

@keyframes tickerAnimation {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}
</style>
<?php include '../includes/footer.php'; ?>
<script>
// Toggle chevron icon when collapse is shown/hidden
document.addEventListener('DOMContentLoaded', function() {
    const collapseElements = ['#settingsAgenda', '#redaksiAgenda'];
    
    collapseElements.forEach(function(elementId) {
        const collapseEl = document.querySelector(elementId);
        const toggleBtn = document.querySelector(`[data-bs-target="${elementId}"] i`);
        
        if (collapseEl && toggleBtn) {
            collapseEl.addEventListener('show.bs.collapse', function() {
                toggleBtn.classList.remove('fa-chevron-down');
                toggleBtn.classList.add('fa-chevron-up');
            });
            
            collapseEl.addEventListener('hide.bs.collapse', function() {
                toggleBtn.classList.remove('fa-chevron-up');
                toggleBtn.classList.add('fa-chevron-down');
            });
        }
    });

    // Auto reload page every 5 minutes (300000 milliseconds)
    setTimeout(function() {
        window.location.reload();
    }, 300000);

    // Auto reload news ticker setiap 10 menit
    function reloadNewsTicker() {
        fetch('get_news.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.items.length > 0) {
                    const tickerItems = document.querySelector('.ticker-items');
                    if (tickerItems) {
                        let newContent = '';
                        
                        // Tambahkan item asli
                        data.items.forEach(news => {
                            newContent += `<div class="ticker-item">
                                <a href="${news.link}" target="_blank" class="text-decoration-none">
                                    ${news.title}
                                </a>
                                <span class="ticker-separator">•</span>
                            </div>`;
                        });
                        
                        // Duplikasi item untuk efek seamless
                        data.items.forEach(news => {
                            newContent += `<div class="ticker-item">
                                <a href="${news.link}" target="_blank" class="text-decoration-none">
                                    ${news.title}
                                </a>
                                <span class="ticker-separator">•</span>
                            </div>`;
                        });
                        
                        tickerItems.innerHTML = newContent;
                        
                        // Reset animasi
                        tickerItems.style.animation = 'none';
                        tickerItems.offsetHeight; // Trigger reflow
                        tickerItems.style.animation = null;
                    }
                }
            })
            .catch(error => console.error('Error loading news:', error));
    }
    
    // Reload news ticker setiap 10 menit
    setInterval(reloadNewsTicker, 600000);
});
</script>
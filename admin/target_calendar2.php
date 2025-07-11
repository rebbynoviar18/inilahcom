<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/functions/viral_content_functions.php';

redirectIfNotLoggedIn();

$userRole = getUserRole();

// Hanya izinkan creative director
if ($userRole !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Ambil target dari database
function getTargetFromDB($key, $defaultValue) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM target_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? floatval($result) : $defaultValue;
}

// Ambil daftar user untuk dropdown
$usersQuery = $pdo->prepare("
    SELECT id, name, role 
    FROM users 
    WHERE role IN ('content_team', 'production_team') AND active = 1
    ORDER BY role, name
");
$usersQuery->execute();
$users = $usersQuery->fetchAll();

// Ambil user yang dipilih dari parameter URL
$selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$selectedUser = null;

if ($selectedUserId) {
    $userQuery = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ? AND role IN ('content_team', 'production_team')");
    $userQuery->execute([$selectedUserId]);
    $selectedUser = $userQuery->fetch();
}

// Ambil bulan dan tahun dari parameter URL atau gunakan bulan saat ini
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validasi bulan dan tahun
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2020 || $year > 2030) $year = date('Y');

// Nama bulan dalam bahasa Indonesia
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Hitung jumlah hari dalam bulan
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Tentukan hari pertama bulan (0 = Minggu, 6 = Sabtu)
$firstDayOfMonth = date('w', strtotime("$year-$month-01"));

// Ambil target poin harian berdasarkan role user yang dipilih
$dailyPointsTarget = 0;
if ($selectedUser) {
    $targetKey = ($selectedUser['role'] === 'production_team') ? 'daily_points_target_production' : 'daily_points_target_content';
    $dailyPointsTarget = getTargetFromDB($targetKey, 10.0);
}

// Ambil target views untuk platform
$igViewsTarget = getTargetFromDB('daily_views_target_instagram', 5000);
$ttViewsTarget = getTargetFromDB('daily_views_target_tiktok', 10000);

// Ambil data poin pengguna untuk bulan ini (hanya jika user dipilih)
$userPoints = [];
if ($selectedUserId) {
    try {
        $pointsQuery = $pdo->prepare("
            SELECT DATE(earned_at) as date, SUM(points) as total_points
            FROM user_points 
            WHERE user_id = ? AND MONTH(earned_at) = ? AND YEAR(earned_at) = ?
            GROUP BY DATE(earned_at)
        ");
        $pointsQuery->execute([$selectedUserId, $month, $year]);
        $pointsData = $pointsQuery->fetchAll();
        
        foreach ($pointsData as $point) {
            $userPoints[$point['date']] = $point['total_points'];
        }
    } catch (PDOException $e) {
        $userPoints = [];
    }
}

// Ambil data viral content untuk bulan ini
$viralContent = [];
try {
    $viralQuery = $pdo->prepare("
        SELECT DATE(upload_date) as date, platform, views
        FROM viral_content 
        WHERE MONTH(upload_date) = ? AND YEAR(upload_date) = ?
    ");
    $viralQuery->execute([$month, $year]);
    $viralData = $viralQuery->fetchAll();
    
    foreach ($viralData as $viral) {
        if (!isset($viralContent[$viral['date']])) {
            $viralContent[$viral['date']] = ['instagram' => 0, 'tiktok' => 0];
        }
        $viralContent[$viral['date']][$viral['platform']] += $viral['views'];
    }
} catch (PDOException $e) {
    $viralContent = [];
}

$pageTitle = "Kalender Target - Admin";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-alt me-2"></i>Kalender Target Tim</h2>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Filter User -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter me-2"></i>Filter Anggota Tim</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="user_id" class="form-label">Pilih Anggota Tim</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">-- Pilih Anggota Tim --</option>
                        <?php 
                        $currentRole = '';
                        foreach ($users as $user): 
                            if ($currentRole !== $user['role']):
                                if ($currentRole !== '') echo '</optgroup>';
                                $roleLabel = $user['role'] === 'production_team' ? 'Tim Produksi' : 'Tim Konten';
                                echo '<optgroup label="' . $roleLabel . '">';
                                $currentRole = $user['role'];
                            endif;
                        ?>
                            <option value="<?= $user['id'] ?>" <?= $selectedUserId == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($currentRole !== '') echo '</optgroup>'; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="month" class="form-label">Bulan</label>
                    <select name="month" id="month" class="form-select">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $month == $i ? 'selected' : '' ?>>
                                <?= $monthNames[$i] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label">Tahun</label>
                    <select name="year" id="year" class="form-select">
                        <?php for ($i = 2020; $i <= 2030; $i++): ?>
                            <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedUser): ?>
        <!-- Info User Terpilih -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-user me-2"></i>Performa: <?= htmlspecialchars($selectedUser['name']) ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Role</h6>
                            <span class="badge bg-<?= $selectedUser['role'] === 'production_team' ? 'primary' : 'success' ?> fs-6">
                                <?= $selectedUser['role'] === 'production_team' ? 'Tim Produksi' : 'Tim Konten' ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted">Target Poin Harian</h6>
                            <h5 class="text-primary"><?= number_format($dailyPointsTarget, 1) ?> Poin</h5>
                        </div>
                    </div>

                    <?php
                    // Hitung total poin bulan ini
                    $totalPoints = array_sum($userPoints);
                    $workDays = min($daysInMonth, date('j'));
                    $targetTotal = 600;
                    $achievementPercentage = $targetTotal > 0 ? ($totalPoints / $targetTotal) * 100 : 0;
                    
                    // Hitung jumlah konten viral
                    $totalIgViral = 0;
                    $totalTtViral = 0;
                    
                    foreach ($viralContent as $date => $platforms) {
                        $totalIgViral += $platforms['instagram'] ?? 0;
                        $totalTtViral += $platforms['tiktok'] ?? 0;
                    }
                    ?>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted">Target Poin Bulanan</h6>
                            <h5 style="color:#gdhdgd;">600 Poin</h5>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted">Total Poin Bulan Ini</h6>
                            <h5 class="text-success"><?= number_format(array_sum($userPoints), 1) ?> Poin</h5>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted">Hari Tercapai</h6>
                            <?php 
                            $achievedDays = 0;
                            foreach ($userPoints as $points) {
                                if ($points >= $dailyPointsTarget) $achievedDays++;
                            }
                            ?>
                            <h5 class="text-info"><?= $achievedDays ?> / <?= count($userPoints) ?> Hari</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Kalender -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-calendar me-2"></i><?= $monthNames[$month] ?> <?= $year ?></h5>
            <div class="btn-group">
                                <a href="?<?= http_build_query(array_merge($_GET, ['month' => $month == 1 ? 12 : $month - 1, 'year' => $month == 1 ? $year - 1 : $year])) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['month' => $month == 12 ? 1 : $month + 1, 'year' => $month == 12 ? $year + 1 : $year])) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!$selectedUser): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-user-plus fa-3x mb-3"></i>
                    <h5>Pilih Anggota Tim</h5>
                    <p>Silakan pilih anggota tim dari dropdown di atas untuk melihat kalender target mereka.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered calendar-table">
                        <thead>
                            <tr class="text-center">
                                <th>Senin</th>
                                <th>Selasa</th>
                                <th>Rabu</th>
                                <th>Kamis</th>
                                <th>Jumat</th>
                                <th class="text-primary">Sabtu</th>                                
                                <th class="text-danger">Minggu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $currentDay = 1;
                            $weeksInMonth = ceil(($daysInMonth + $firstDayOfMonth) / 7);
                            
                            for ($week = 0; $week < $weeksInMonth; $week++):
                            ?>
                                <tr>
                                    <?php for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++): ?>
                                        <td class="calendar-day">
                                            <?php
                                            $cellDay = ($week * 7 + $dayOfWeek) - $firstDayOfMonth + 2;
                                            
                                            if ($cellDay < 1 || $cellDay > $daysInMonth):
                                            ?>
                                                <div class="empty-day">&nbsp;</div>
                                            <?php else:
                                                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $cellDay);
                                                $isToday = $dateStr === date('Y-m-d');
                                                $userPointsToday = $userPoints[$dateStr] ?? 0;
                                                $igViewsToday = $viralContent[$dateStr]['instagram'] ?? 0;
                                                $ttViewsToday = $viralContent[$dateStr]['tiktok'] ?? 0;
                                                
                                                // Hitung persentase pencapaian target poin
                                                $pointsPercentage = $dailyPointsTarget > 0 ? min(100, ($userPointsToday / $dailyPointsTarget) * 100) : 0;
                                                $igPercentage = $igViewsTarget > 0 ? min(100, ($igViewsToday / $igViewsTarget) * 100) : 0;
                                                $ttPercentage = $ttViewsTarget > 0 ? min(100, ($ttViewsToday / $ttViewsTarget) * 100) : 0;
                                            ?>
                                                <div class="day-content <?= $isToday ? 'today' : '' ?>">
                                                    <div class="day-number"><?= $cellDay ?></div>
                                                    
                                                    <!-- Target Poin Individual -->
                                                    <div class="target-item mb-2">
                                                        <small class="text-muted d-block">Poin Target</small>
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <small><?= number_format($userPointsToday, 1) ?>/<?= number_format($dailyPointsTarget, 1) ?></small>
                                                            <span class="badge bg-<?= $pointsPercentage >= 100 ? 'success' : ($pointsPercentage >= 80 ? 'warning' : 'danger') ?> badge-sm">
                                                                <?= number_format($pointsPercentage, 0) ?>%
                                                            </span>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-<?= $pointsPercentage >= 100 ? 'success' : ($pointsPercentage >= 80 ? 'warning' : 'danger') ?>" 
                                                                 style="width: <?= $pointsPercentage ?>%"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-4">
        <div class="card-header">
            <h6><i class="fas fa-info-circle me-2"></i>Keterangan</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6>Status Pencapaian:</h6>
                    <div class="d-flex align-items-center mb-2">
                        <div class="badge bg-success me-2">100%</div>
                        <small>Target tercapai (≥100%)</small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="badge bg-warning me-2">80%</div>
                        <small>Hampir tercapai (80-99%)</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="badge bg-danger me-2">50%</div>
                        <small>Belum tercapai (<80%)</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>Target Harian:</h6>
                    <div class="mb-2">
                        <strong>Poin Individual:</strong> <?= number_format($dailyPointsTarget, 1) ?> poin
                    </div>
                    <div class="mb-2">
                        <strong>Instagram Views:</strong> <?= number_format($igViewsTarget) ?> views
                    </div>
                    <div>
                        <strong>TikTok Views:</strong> <?= number_format($ttViewsTarget) ?> views
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>Navigasi:</h6>
                    <div class="mb-2">
                        <i class="fas fa-chevron-left text-muted me-2"></i>
                        <small>Bulan sebelumnya</small>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-chevron-right text-muted me-2"></i>
                        <small>Bulan berikutnya</small>
                    </div>
                    <div>
                        <div class="today-indicator me-2"></div>
                        <small>Hari ini</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-table {
    font-size: 0.85rem;
}

.calendar-day {
    width: 14.28%;
    height: 150px;
    vertical-align: top;
    padding: 8px;
    position: relative;
}

.day-content {
    height: 100%;
    border-radius: 8px;
    padding: 6px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.day-number {
    font-weight: bold;
    font-size: 1rem;
    margin-bottom: 8px;
    text-align: center;
}

.target-item {
    margin-bottom: 6px;
}

.target-item small {
    font-size: 0.7rem;
}

.empty-day {
    height: 100%;
    background: #f8f9fa;
}

.today {
    background: linear-gradient(135deg, #5e72e4, #825ee4) !important;
    color: white;
    border-color #5e72e4 !important;
}
.today .day-number {
    color: white;
    font-weight: bold;
}

.today .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

.badge-sm {
    font-size: 0.6rem;
    padding: 0.2rem 0.4rem;
}

.progress {
    height: 6px;
    background-color: rgba(0, 0, 0, 0.1);
}

.progress-bar {
    transition: width 0.3s ease;
}

.progress-bar.bg-success {
    background-color: #28a745 !important;
}

.progress-bar.bg-warning {
    background-color: #ffc107 !important;
}

.progress-bar.bg-danger {
    background-color: #dc3545 !important;
}

.today .progress {
    background-color: rgba(255, 255, 255, 0.3);
}

.calendar-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    text-align: center;
    padding: 12px 8px;
    border: 1px solid #dee2e6;
}

.calendar-table td {
    border: 1px solid #dee2e6;
    padding: 0;
}

.today-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    vertical-align: middle;
}

.card-header h5, .card-header h6 {
    margin-bottom: 0;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
}

.btn-group .btn:last-child {
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}

@media (max-width: 768px) {
    .calendar-day {
        height: 120px;
        padding: 4px;
    }
    
    .day-content {
        padding: 4px;
    }
    
    .day-number {
        font-size: 0.9rem;
        margin-bottom: 4px;
    }
    
    .target-item {
        margin-bottom: 4px;
    }
    
    .target-item small {
        font-size: 0.65rem;
    }
    
    .badge-sm {
        font-size: 0.55rem;
        padding: 0.1rem 0.3rem;
    }
}

.role-badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
}

.stats-card {
    border-left: 4px solid #007bff;
}

.stats-card.success {
    border-left-color: #28a745;
}

.stats-card.warning {
    border-left-color: #ffc107;
}

.stats-card.info {
    border-left-color: #17a2b8;
}

.filter-section {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.user-info-card {
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.achievement-indicator {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #28a745;
}

.achievement-indicator.partial {
    background-color: #ffc107;
}

.achievement-indicator.none {
    background-color: #dc3545;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto submit form when user selection changes
    const userSelect = document.getElementById('user_id');
    const monthSelect = document.getElementById('month');
    const yearSelect = document.getElementById('year');
    
    function autoSubmit() {
        if (userSelect.value) {
            userSelect.form.submit();
        }
    }
    
    userSelect.addEventListener('change', autoSubmit);
    monthSelect.addEventListener('change', function() {
        if (userSelect.value) {
            userSelect.form.submit();
        }
    });
    yearSelect.addEventListener('change', function() {
        if (userSelect.value) {
            userSelect.form.submit();
        }
    });
    
    // Add hover effects to calendar days
    const calendarDays = document.querySelectorAll('.day-content');
    calendarDays.forEach(day => {
        day.addEventListener('mouseenter', function() {
            if (!this.classList.contains('today')) {
                this.style.transform = 'scale(1.02)';
                this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.15)';
                this.style.transition = 'all 0.2s ease';
            }
        });
        
        day.addEventListener('mouseleave', function() {
            if (!this.classList.contains('today')) {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
            }
        });
    });
    
    // Add animation to progress bars
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
    
    // Tooltip for achievement indicators
    const indicators = document.querySelectorAll('.achievement-indicator');
    indicators.forEach(indicator => {
        indicator.title = indicator.classList.contains('none') ? 'Target tidak tercapai' :
                         indicator.classList.contains('partial') ? 'Target hampir tercapai' :
                         'Target tercapai';
    });
});

// Function to highlight current week
function highlightCurrentWeek() {
    const today = new Date();
    const currentDate = today.getDate();
    const currentMonth = today.getMonth() + 1;
    const currentYear = today.getFullYear();
    
    // Only highlight if viewing current month
    const viewingMonth = <?= $month ?>;
    const viewingYear = <?= $year ?>;
    
    if (currentMonth === viewingMonth && currentYear === viewingYear) {
        const dayElements = document.querySelectorAll('.day-number');
        dayElements.forEach(element => {
            const dayNumber = parseInt(element.textContent);
            if (dayNumber === currentDate) {
                element.parentElement.classList.add('current-day');
            }
        });
    }
}

// Call highlight function after page load
window.addEventListener('load', highlightCurrentWeek);
</script>

<?php include '../includes/footer.php'; ?>
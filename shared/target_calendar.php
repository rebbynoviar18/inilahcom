<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/functions/viral_content_functions.php';

redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];
$userRole = getUserRole();

// Hanya izinkan creative director, content team, dan production team
$allowedRoles = ['creative_director', 'content_team', 'production_team'];
if (!in_array($userRole, $allowedRoles)) {
    header('Location: ../index.php');
    exit;
}

// Ambil target dari database
function getTargetFromDB($key, $defaultValue) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM target_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $defaultValue;
    } catch (PDOException $e) {
        error_log("Error fetching target setting: " . $e->getMessage());
        return $defaultValue;
    }
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

// Ambil target poin harian berdasarkan role
$role = getUserRole($userId);
$targetKey = ($role === 'production_team') ? 'daily_points_target_production' : 'daily_points_target_content';
$dailyPointsTarget = getTargetFromDB($targetKey, 10.0);

// Ambil target views untuk platform
$igViewsTarget = getTargetFromDB('daily_views_target_instagram', 5000);
$ttViewsTarget = getTargetFromDB('daily_views_target_tiktok', 10000);

// Ambil data poin pengguna untuk bulan ini
try {
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $pointsQuery = $pdo->prepare("
        SELECT DATE(earned_at) as date, SUM(points) as total_points
        FROM user_points
        WHERE user_id = ? AND earned_at BETWEEN ? AND ?
        GROUP BY DATE(earned_at)
    ");
    $pointsQuery->execute([$userId, $startDate, $endDate]);
    $userPoints = [];
    
    while ($row = $pointsQuery->fetch(PDO::FETCH_ASSOC)) {
        $userPoints[$row['date']] = $row['total_points'];
    }
} catch (PDOException $e) {
    error_log("Error fetching user points: " . $e->getMessage());
    $userPoints = [];
}

// Ambil data viral content untuk bulan ini
try {
    $viralQuery = $pdo->prepare("
        SELECT 
            DATE(marked_date) as date, 
            platform, 
            COUNT(*) as count
        FROM viral_content
        WHERE marked_date BETWEEN ? AND ?
        GROUP BY DATE(marked_date), platform
    ");
    $viralQuery->execute([$startDate, $endDate]);
    $viralContent = [];
    
    while ($row = $viralQuery->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($viralContent[$row['date']])) {
            $viralContent[$row['date']] = [
                'instagram' => 0,
                'tiktok' => 0
            ];
        }
        $viralContent[$row['date']][$row['platform']] = $row['count'];
    }
} catch (PDOException $e) {
    error_log("Error fetching viral content: " . $e->getMessage());
    $viralContent = [];
}

$pageTitle = "Kalender Target";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Kalender Target: <?= $monthNames[$month] ?> <?= $year ?></h4>
                    <div>
                        <div class="btn-group me-2">
                            <a href="?month=<?= $month == 1 ? 12 : $month - 1 ?>&year=<?= $month == 1 ? $year - 1 : $year ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                <?= $monthNames[$month] ?> <?= $year ?>
                            </button>
                            <a href="?month=<?= $month == 12 ? 1 : $month + 1 ?>&year=<?= $month == 12 ? $year + 1 : $year ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn btn-sm btn-outline-secondary">
                            Bulan Ini
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Minggu</th>
                                    <th>Senin</th>
                                    <th>Selasa</th>
                                    <th>Rabu</th>
                                    <th>Kamis</th>
                                    <th>Jumat</th>
                                    <th>Sabtu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Hitung jumlah baris yang dibutuhkan
                                $numRows = ceil(($daysInMonth + $firstDayOfMonth) / 7);
                                
                                // Inisialisasi counter hari
                                $day = 1;
                                
                                // Loop untuk setiap baris
                                for ($row = 0; $row < $numRows; $row++) {
                                    echo "<tr>";
                                    
                                    // Loop untuk setiap kolom (hari dalam seminggu)
                                    for ($col = 0; $col < 7; $col++) {
                                        // Jika masih dalam sel kosong sebelum hari pertama bulan
                                        if ($row == 0 && $col < $firstDayOfMonth) {
                                            echo "<td class='empty-day'></td>";
                                        }
                                        // Jika sudah melewati hari terakhir bulan
                                        elseif ($day > $daysInMonth) {
                                            echo "<td class='empty-day'></td>";
                                        }
                                        // Hari dalam bulan
                                        else {
                                            $currentDate = sprintf("%04d-%02d-%02d", $year, $month, $day);
                                            $isToday = ($day == date('j') && $month == date('n') && $year == date('Y'));
                                            
                                            // Ambil data poin untuk hari ini
                                            $todayPoints = isset($userPoints[$currentDate]) ? $userPoints[$currentDate] : 0;
                                            $targetAchieved = $todayPoints >= $dailyPointsTarget;
                                            
                                            // Ambil data viral content untuk hari ini
                                            $igCount = isset($viralContent[$currentDate]['instagram']) ? $viralContent[$currentDate]['instagram'] : 0;
                                            $ttCount = isset($viralContent[$currentDate]['tiktok']) ? $viralContent[$currentDate]['tiktok'] : 0;
                                            
                                            // Tentukan kelas CSS untuk sel
                                            $cellClass = $isToday ? 'today' : '';
                                            
                                            echo "<td class='calendar-day $cellClass'>";
                                            echo "<div class='day-number'>$day</div>";
                                            
                                            // Target individu
                                            echo "<div class='target-item'>";
                                            echo "<span class='badge " . ($targetAchieved ? "bg-success" : ($todayPoints > 0 ? "bg-danger" : "bg-secondary")) . "'>";
                                            echo "<i class='fas fa-user'></i> ";
                                            echo number_format($todayPoints, 1) . "/" . number_format($dailyPointsTarget, 1);
                                            echo "</span>";
                                            echo "</div>";
                                            
                                            
                                            // Target kolektif - Instagram
                                            echo "<div class='target-item'>";
                                            echo "<span class='badge " . ($igCount > 0 ? "bg-success" : "bg-secondary") . "'>";
                                            echo "<i class='fab fa-instagram'></i> ";
                                            echo $igCount . "/5";
                                            echo "</span>";
                                            echo "</div>";
                                            
                                            // Target kolektif - TikTok
                                            echo "<div class='target-item'>";
                                            echo "<span class='badge " . ($ttCount > 0 ? "bg-success" : "bg-secondary") . "'>";
                                            echo "<i class='fab fa-tiktok'></i> ";
                                            echo $ttCount . "/5";
                                            echo "</span>";
                                            echo "</div>";
                                            
                                            echo "</td>";
                                            
                                            $day++;
                                        }
                                    }
                                    
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">            
            <div class="card">
                <div class="card-header">
                    <h4>Ringkasan Bulan Ini</h4>
                </div>
                <div class="card-body">
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
                    
                    <h5>Target Poin Individu Bulanan</h5>
                    <div class="progress mb-2">
                        <div class="progress-bar <?= $achievementPercentage >= 100 ? 'bg-success' : 'bg-primary' ?>" 
                             role="progressbar" 
                             style="width: <?= min(100, $achievementPercentage) ?>%" 
                             aria-valuenow="<?= $achievementPercentage ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?= number_format($achievementPercentage, 1) ?>%
                        </div>
                    </div>
                    <p>Total: <?= number_format($totalPoints, 1) ?> / <?= number_format($targetTotal, 1) ?> poin</p>
                    <br>
                    <h5>Target Konten Viral</h5>
                    <p>
                        <i class="fab fa-instagram"></i> Instagram: <?= $totalIgViral ?> konten<br>
                        <i class="fab fa-tiktok"></i> TikTok: <?= $totalTtViral ?> konten
                    </p>
                    <br>
                    <h5>Keterangan</h5>
                    <p>
                        <span class="badge bg-success"><i class="fas fa-check"></i></span> Target tercapai
                    </p>
                    <p>
                        <span class="badge bg-danger"><i class="fas fa-times"></i></span> Target belum tercapai
                    </p>
                    <p>
                        <span class="badge bg-secondary"><i class="fas fa-minus"></i></span> Belum ada data
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-day {
    height: 100px;
    width: 14.28%;
    position: relative;
    padding: 5px;
    vertical-align: top;
}

.day-number {
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 5px;
}

.target-item {
    margin-bottom: 3px;
}

.empty-day {
    background-color: #f8f9fa;
}

.today {
    background-color: #e8f4ff;
}

.badge {
    font-size: 0.75em;
}

.progress {
    height: 20px;
}
.progress-bar {
    height: 20px;
    line-height: 20px;
}
.progress-bar.bg-success {
    background-color: #28a745 !important;
}


.bg-secondary {
    background-color:rgb(217, 217, 217) !important;
}




</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk mengubah bulan saat mengubah tanggal
    document.getElementById('dateSelector').addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const month = selectedDate.getMonth() + 1;
        const year = selectedDate.getFullYear();
        window.location.href = `?month=${month}&year=${year}`;
    });
});
</script>

<?php include '../includes/footer.php'; ?>
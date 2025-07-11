<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
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

// Ambil target kolektif harian dari database
$igDailyTarget = getTargetFromDB('daily_views_target_instagram', 1);
$ttDailyTarget = getTargetFromDB('daily_views_target_tiktok', 1);

// Ambil target views harian
$igViewsTarget = getTargetFromDB('weekly_views_target_instagram', 50000);
$ttViewsTarget = getTargetFromDB('weekly_views_target_tiktok', 100000);

// Ambil data target pencapaian
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validasi bulan dan tahun
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2020 || $year > 2030) $year = date('Y');

// Ambil data viral content untuk bulan yang dipilih
try {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(marked_date) as content_date,
            platform,
            COUNT(*) as content_count,
            AVG(views) as avg_views
        FROM 
            viral_content
        WHERE 
            MONTH(marked_date) = ? AND YEAR(marked_date) = ?
        GROUP BY 
            DATE(marked_date), platform
        ORDER BY 
            content_date
    ");
    $stmt->execute([$month, $year]);
    $viralData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reformat data untuk penggunaan yang lebih mudah
    $calendarData = [];
    foreach ($viralData as $row) {
        $date = $row['content_date'];
        $platform = $row['platform'];
        
        if (!isset($calendarData[$date])) {
            $calendarData[$date] = [
                'instagram' => ['count' => 0, 'avg_views' => 0],
                'tiktok' => ['count' => 0, 'avg_views' => 0]
            ];
        }
        
        $calendarData[$date][$platform]['count'] = $row['content_count'];
        $calendarData[$date][$platform]['avg_views'] = $row['avg_views'];
    }
} catch (PDOException $e) {
    $error = "Error fetching viral content data: " . $e->getMessage();
    $calendarData = [];
}

$pageTitle = "Kalender Target";
include '../includes/header.php';

// Fungsi untuk mendapatkan nama bulan dalam bahasa Indonesia
function getIndonesianMonth($month) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[$month];
}

// Fungsi untuk mendapatkan nama hari dalam bahasa Indonesia
function getIndonesianDay($dayOfWeek) {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $days[$dayOfWeek];
}

// Hitung jumlah hari dalam bulan
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Tentukan hari pertama bulan
$firstDayOfMonth = date('w', strtotime("$year-$month-01"));
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Kalender Target Konten Viral - <?= getIndonesianMonth($month) ?> <?= $year ?></h5>
                    <div>
                        <form method="get" class="d-flex">
                            <select name="month" class="form-select me-2">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $month ? 'selected' : '' ?>>
                                        <?= getIndonesianMonth($i) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="year" class="form-select me-2">
                                <?php for ($i = 2020; $i <= 2030; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6><i class="fab fa-instagram me-2"></i> Target Instagram</h6>
                                        <p class="mb-1">Target Konten Viral Harian: <strong><?= $igDailyTarget ?> konten</strong></p>
                                        <p class="mb-0">Minimum Views: <strong><?= number_format($igViewsTarget) ?> views</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6><i class="fab fa-tiktok me-2"></i> Target TikTok</h6>
                                        <p class="mb-1">Target Konten Viral Harian: <strong><?= $ttDailyTarget ?> konten</strong></p>
                                        <p class="mb-0">Minimum Views: <strong><?= number_format($ttViewsTarget) ?> views</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered calendar-table">
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
                                $dayCount = 1;
                                $calendar = '<tr>';
                                
                                // Isi sel kosong untuk hari-hari sebelum hari pertama bulan
                                for ($i = 0; $i < $firstDayOfMonth; $i++) {
                                    $calendar .= '<td class="empty-day"></td>';
                                }
                                
                                // Isi kalender dengan data
                                while ($dayCount <= $daysInMonth) {
                                    if (($dayCount + $firstDayOfMonth - 1) % 7 == 0 && $dayCount != 1) {
                                        $calendar .= '</tr><tr>';
                                    }
                                    
                                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $dayCount);
                                    $igData = isset($calendarData[$dateStr]['instagram']) ? $calendarData[$dateStr]['instagram'] : ['count' => 0, 'avg_views' => 0];
                                    $ttData = isset($calendarData[$dateStr]['tiktok']) ? $calendarData[$dateStr]['tiktok'] : ['count' => 0, 'avg_views' => 0];
                                    
                                    $igClass = $igData['count'] >= $igDailyTarget ? 'bg-success text-white' : '';
                                    $ttClass = $ttData['count'] >= $ttDailyTarget ? 'bg-success text-white' : '';
                                    
                                    $calendar .= '<td class="calendar-day">';
                                    $calendar .= '<div class="date-header">' . $dayCount . '</div>';
                                    $calendar .= '<div class="platform-data ' . $igClass . '">';
                                    $calendar .= '<i class="fab fa-instagram"></i> ' . $igData['count'] . '/' . $igDailyTarget;
                                    if ($igData['count'] > 0) {
                                        $calendar .= '<br><small>' . number_format($igData['avg_views']) . ' views</small>';
                                    }
                                    $calendar .= '</div>';
                                    $calendar .= '<div class="platform-data ' . $ttClass . '">';
                                    $calendar .= '<i class="fab fa-tiktok"></i> ' . $ttData['count'] . '/' . $ttDailyTarget;
                                    if ($ttData['count'] > 0) {
                                        $calendar .= '<br><small>' . number_format($ttData['avg_views']) . ' views</small>';
                                    }
                                    $calendar .= '</div>';
                                    $calendar .= '</td>';
                                    
                                    $dayCount++;
                                }
                                
                                // Isi sel kosong untuk hari-hari setelah hari terakhir bulan
                                $lastDayOfWeek = ($firstDayOfMonth + $daysInMonth - 1) % 7;
                                if ($lastDayOfWeek < 6) {
                                    for ($i = $lastDayOfWeek + 1; $i <= 6; $i++) {
                                        $calendar .= '<td class="empty-day"></td>';
                                    }
                                }
                                
                                $calendar .= '</tr>';
                                echo $calendar;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-table th {
    text-align: center;
    background-color: #f8f9fa;
}

.calendar-day {
    height: 120px;
    width: 14.28%;
    padding: 5px !important;
    vertical-align: top;
}

.empty-day {
    background-color: #f8f9fa;
}

.date-header {
    font-weight: bold;
    margin-bottom: 5px;
}

.platform-data {
    padding: 3px 5px;
    margin-bottom: 5px;
    border-radius: 4px;
    font-size: 0.9rem;
}

.platform-data.bg-success {
    background-color: #28a745 !important;
}
</style>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

// Hanya tim produksi dan konten yang bisa mengakses halaman ini
$userRole = getUserRole();
if (!in_array($userRole, ['production_team', 'content_team', 'creative_director'])) {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'week';
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Ambil data untuk tampilan yang dipilih
if ($viewMode == 'day') {
    // Tampilan harian
    $contentShifts = getDailyShifts($selectedDate, 'content_team');
    $productionShifts = getDailyShifts($selectedDate, 'production_team');
} elseif ($viewMode == 'week') {
    // Tampilan mingguan
    $startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));
    
    // Ambil shift untuk setiap hari dalam seminggu
    $weeklyShifts = [];
    for ($i = 0; $i < 7; $i++) {
        $currentDate = date('Y-m-d', strtotime("+$i days", strtotime($startOfWeek)));
        $weeklyShifts[$currentDate] = [
            'content' => getDailyShifts($currentDate, 'content_team'),
            'production' => getDailyShifts($currentDate, 'production_team')
        ];
    }
} elseif ($viewMode == 'month') {
    // Tampilan bulanan
    $startOfMonth = date('Y-m-01', strtotime($selectedDate));
    $endOfMonth = date('Y-m-t', strtotime($selectedDate));
    
    // Ambil shift untuk setiap hari dalam sebulan
    $monthlyShifts = [];
    $currentDate = $startOfMonth;
    while ($currentDate <= $endOfMonth) {
        $monthlyShifts[$currentDate] = [
            'content' => getDailyShifts($currentDate, 'content_team'),
            'production' => getDailyShifts($currentDate, 'production_team')
        ];
        $currentDate = date('Y-m-d', strtotime('+1 day', strtotime($currentDate)));
    }
    
    // Hitung jumlah minggu dalam bulan ini
    $firstDayOfMonth = date('N', strtotime($startOfMonth)) - 1; // 0 = Senin, 6 = Minggu
    $totalDays = date('t', strtotime($startOfMonth));
    $totalWeeks = ceil(($firstDayOfMonth + $totalDays) / 7);
}

// Set variabel untuk menandai halaman aktif di sidebar
$_SESSION['current_page'] = 'shifts';

// Redirect ke halaman shifts yang sesuai dengan role
if ($_SERVER['PHP_SELF'] == '/creative/shared/shifts.php') {
    $redirectPath = '';
    switch ($userRole) {
        case 'creative_director':
            $redirectPath = '../admin/shifts.php';
            break;
        case 'content_team':
            $redirectPath = '../content/shifts.php';
            break;
        case 'production_team':
            $redirectPath = '../production/shifts.php';
            break;
    }
    
    if (!empty($redirectPath)) {
        // Tambahkan parameter URL yang ada
        $queryParams = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        header("Location: $redirectPath$queryParams");
        exit();
    }
}

$pageTitle = "Jadwal Shift";
// Tentukan folder sidebar berdasarkan role
$sidebarPath = '';
switch ($userRole) {
    case 'creative_director':
        $sidebarPath = '../admin';
        break;
    case 'content_team':
        $sidebarPath = '../content';
        break;
    case 'production_team':
        $sidebarPath = '../production';
        break;
    default:
        $sidebarPath = '../includes';
}

include '../includes/header.php';

?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Jadwal Shift</h4>
                    <div>
                        <div class="btn-group me-2">
                            <a href="?view=day&date=<?= $selectedDate ?>" class="btn btn-sm <?= $viewMode == 'day' ? 'btn-primary' : 'btn-outline-primary' ?>">Hari</a>
                            <a href="?view=week&date=<?= $selectedDate ?>" class="btn btn-sm <?= $viewMode == 'week' ? 'btn-primary' : 'btn-outline-primary' ?>">Minggu</a>
                            <a href="?view=month&date=<?= $selectedDate ?>" class="btn btn-sm <?= $viewMode == 'month' ? 'btn-primary' : 'btn-outline-primary' ?>">Bulan</a>
                        </div>
                        
                        <input type="date" id="dateSelector" class="form-control form-control-sm d-inline-block" style="width: auto;" value="<?= $selectedDate ?>">
                        
                        <?php if (getUserRole() == 'creative_director'): ?>
                            <a href="../admin/manage_shifts.php" class="btn btn-sm btn-success ms-2">Kelola Jadwal</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($viewMode == 'day'): ?>
        <!-- Tampilan Harian -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Jadwal: <?= str_replace(
                            ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                            ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                            date('l, d F Y', strtotime($selectedDate))
                        ) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Shift</th>
                                        <th>Tim Konten</th>
                                        <th>Tim Produksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $shiftTypes = ['morning', 'afternoon', 'off'];
                                    foreach ($shiftTypes as $shiftType): 
                                        $contentStaff = array_filter($contentShifts, function($shift) use ($shiftType) {
                                            return $shift['shift_type'] == $shiftType;
                                        });
                                        $productionStaff = array_filter($productionShifts, function($shift) use ($shiftType) {
                                            return $shift['shift_type'] == $shiftType;
                                        });
                                    ?>                                    
                                    <tr>
                                        <td><?= getShiftTypeLabel($shiftType) ?></td>
                                        <td>
                                            <?php if (!empty($contentStaff)): ?>
                                                <?php foreach ($contentStaff as $staff): ?>
                                                    <span class="badge <?= $shiftType == 'off' ? 'bg-danger' : 'bg-success' ?> mb-1 <?= $staff['user_id'] == $userId ? 'border border-primary' : '' ?>">
                                                        <?= htmlspecialchars($staff['user_name']) ?>
                                                    </span><br>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($productionStaff)): ?>
                                                <?php foreach ($productionStaff as $staff): ?>
                                                    <span class="badge <?= $shiftType == 'off' ? 'bg-danger' : 'bg-warning text-dark' ?> mb-1 <?= $staff['user_id'] == $userId ? 'border border-primary' : '' ?>">
                                                        <?= htmlspecialchars($staff['user_name']) ?>
                                                    </span><br>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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
    <?php elseif ($viewMode == 'week'): ?>
        <!-- Tampilan Mingguan -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Jadwal Mingguan: <?= date('d M', strtotime($startOfWeek)) ?> - <?= date('d M Y', strtotime($endOfWeek)) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Shift</th>
                                        <?php for ($i = 0; $i < 7; $i++): ?>
                                            <?php 
                                                $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                                                $dayIndex = date('N', strtotime("+$i days", strtotime($startOfWeek))) - 1;
                                                $day = $dayNames[$dayIndex] . ', ' . date('d', strtotime("+$i days", strtotime($startOfWeek)));
                                                $dayClass = date('Y-m-d', strtotime("+$i days", strtotime($startOfWeek))) == date('Y-m-d') ? 'table-info' : '';
                                            ?>
                                            <th class="<?= $dayClass ?>"><?= $day ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (['morning', 'afternoon', 'off'] as $shiftType): ?>
                                        <tr>
                                            <td><?= getShiftTypeLabel($shiftType) ?></td>
                                            <?php for ($i = 0; $i < 7; $i++): ?>
                                                <?php 
                                                    $currentDate = date('Y-m-d', strtotime("+$i days", strtotime($startOfWeek)));
                                                    $dayClass = $currentDate == date('Y-m-d') ? 'table-info' : '';
                                                    
                                                    $contentStaff = array_filter($weeklyShifts[$currentDate]['content'], function($shift) use ($shiftType) {
                                                        return $shift['shift_type'] == $shiftType;
                                                    });
                                                    
                                                    $productionStaff = array_filter($weeklyShifts[$currentDate]['production'], function($shift) use ($shiftType) {
                                                        return $shift['shift_type'] == $shiftType;
                                                    });
                                                ?>
                                                <td class="<?= $dayClass ?>">
                                                    <div class="mb-2">
                                                        <?php if (!empty($contentStaff)): ?>
                                                            <?php foreach ($contentStaff as $staff): ?>
                                                                <span class="badge <?= $shiftType == 'off' ? 'bg-danger' : 'bg-success' ?> mb-1 <?= $staff['user_id'] == $userId ? 'border border-primary' : '' ?>">
                                                                    <?= htmlspecialchars($staff['user_name']) ?>
                                                                </span><br>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <?php if (!empty($productionStaff)): ?>
                                                            <?php foreach ($productionStaff as $staff): ?>
                                                                <span class="badge <?= $shiftType == 'off' ? 'bg-danger' : 'bg-warning text-dark' ?> mb-1 <?= $staff['user_id'] == $userId ? 'border border-primary' : '' ?>">
                                                                    <?= htmlspecialchars($staff['user_name']) ?>
                                                                </span><br>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($viewMode == 'month'): ?>
        <!-- Tampilan Bulanan -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Jadwal Bulan: <?= date('F Y', strtotime($selectedDate)) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered calendar-table">
                                <thead>
                                    <tr>
                                        <th>Senin</th>
                                        <th>Selasa</th>
                                        <th>Rabu</th>
                                        <th>Kamis</th>
                                        <th>Jumat</th>
                                        <th>Sabtu</th>
                                        <th>Minggu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Buat kalender
                                    $firstDayOfMonth = date('N', strtotime($startOfMonth)) - 1; // 0 = Senin, 6 = Minggu
                                    $totalDays = date('t', strtotime($startOfMonth));
                                    $currentDay = 1;
                                    $currentDate = $startOfMonth;
                                    
                                    // Loop untuk setiap minggu
                                    for ($week = 0; $week < $totalWeeks; $week++) {
                                        echo "<tr>";
                                        
                                        // Loop untuk setiap hari dalam seminggu
                                        for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++) {
                                            // Cek apakah hari ini masih dalam bulan yang dipilih
                                            if (($week == 0 && $dayOfWeek < $firstDayOfMonth) || ($currentDay > $totalDays)) {
                                                echo "<td class='empty-day'></td>";
                                            } else {
                                                $currentDate = date('Y-m-d', strtotime($startOfMonth . ' +' . ($currentDay - 1) . ' days'));
                                                $isToday = $currentDate == date('Y-m-d');
                                                $cellClass = $isToday ? 'today' : '';
                                                
                                                echo "<td class='calendar-day $cellClass'>";
                                                echo "<div class='date-header'>" . $currentDay . "</div>";
                                                
                                                // Tampilkan shift untuk hari ini
                                                if (isset($monthlyShifts[$currentDate])) {
                                                    echo "<div class='shifts-container'>";
                                                    
                                                    // Morning shift
                                                    echo "<div class='shift-header'>Pagi:</div>";
                                                    
                                                    // Content team - morning
                                                    $morningContentStaff = array_filter($monthlyShifts[$currentDate]['content'], function($shift) {
                                                        return $shift['shift_type'] == 'morning';
                                                    });
                                                    
                                                    if (!empty($morningContentStaff)) {
                                                        foreach ($morningContentStaff as $staff) {
                                                            echo "<span class='badge bg-success mb-1 " . ($staff['user_id'] == $userId ? 'border border-primary' : '') . "'>" . 
                                                                htmlspecialchars($staff['user_name']) . "</span><br>";
                                                        }
                                                    }
                                                    
                                                    // Production team - morning
                                                    $morningProductionStaff = array_filter($monthlyShifts[$currentDate]['production'], function($shift) {
                                                        return $shift['shift_type'] == 'morning';
                                                    });
                                                    
                                                    if (!empty($morningProductionStaff)) {
                                                        foreach ($morningProductionStaff as $staff) {
                                                            echo "<span class='badge bg-warning text-dark mb-1 " . ($staff['user_id'] == $userId ? 'border border-primary' : '') . "'>" . 
                                                                htmlspecialchars($staff['user_name']) . "</span><br>";
                                                        }
                                                    }
                                                    
                                                    // Afternoon shift
                                                    echo "<div class='shift-header mt-2'>Sore:</div>";
                                                    
                                                    // Content team - afternoon
                                                    $afternoonContentStaff = array_filter($monthlyShifts[$currentDate]['content'], function($shift) {
                                                        return $shift['shift_type'] == 'afternoon';
                                                    });
                                                    
                                                    if (!empty($afternoonContentStaff)) {
                                                        foreach ($afternoonContentStaff as $staff) {
                                                            echo "<span class='badge bg-success mb-1 " . ($staff['user_id'] == $userId ? 'border border-primary' : '') . "'>" . 
                                                                htmlspecialchars($staff['user_name']) . "</span><br>";
                                                        }
                                                    }
                                                    
                                                    // Production team - afternoon
                                                    $afternoonProductionStaff = array_filter($monthlyShifts[$currentDate]['production'], function($shift) {
                                                        return $shift['shift_type'] == 'afternoon';
                                                    });
                                                    
                                                    if (!empty($afternoonProductionStaff)) {
                                                        foreach ($afternoonProductionStaff as $staff) {
                                                            echo "<span class='badge bg-warning text-dark mb-1 " . ($staff['user_id'] == $userId ? 'border border-primary' : '') . "'>" . 
                                                                htmlspecialchars($staff['user_name']) . "</span><br>";
                                                        }
                                                    }
                                                    
                                                    // Libur shift
                                                    echo "<div class='shift-header mt-2'>Libur:</div>";
                                                    
                                                    // Content team - off
                                                    $offContentStaff = array_filter($monthlyShifts[$currentDate]['content'], function($shift) {
                                                        return $shift['shift_type'] == 'off';
                                                    });
                                                    
                                                    if (!empty($offContentStaff)) {
                                                        foreach ($offContentStaff as $staff) {
                                                            echo "<span class='badge bg-danger mb-1 " . ($staff['user_id'] == $userId ? 'border border-primary' : '') . "'>" . 
                                                                htmlspecialchars($staff['user_name']) . "</span><br>";
                                                        }
                                                    }
                                                    
                                                    // Production team - off
                                                    $offProductionStaff = array_filter($monthlyShifts[$currentDate]['production'], function($shift) {
                                                        return $shift['shift_type'] == 'off';
                                                    });
                                                    
                                                    if (!empty($offProductionStaff)) {
                                                        foreach ($offProductionStaff as $staff) {
                                                            echo "<span class='badge bg-danger mb-1 " . ($staff['user_id'] == $userId ? 'border border-primary' : '') . "'>" . 
                                                                htmlspecialchars($staff['user_name']) . "</span><br>";
                                                        }
                                                    }
                                                    
                                                    echo "</div>";
                                                }
                                                
                                                echo "</td>";
                                                $currentDay++;
                                            }
                                        }
                                        
                                        echo "</tr>";
                                        
                                        // Keluar dari loop jika sudah melewati hari terakhir bulan
                                        if ($currentDay > $totalDays) {
                                            break;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.calendar-table th {
    text-align: center;
    width: 14.28%;
}

.calendar-day {
    height: 150px;
    vertical-align: top;
    padding: 5px;
}

.empty-day {
    background-color: #f8f9fa;
}

.today {
    background-color: #d1ecf1;
}

.date-header {
    font-weight: bold;
    margin-bottom: 5px;
    text-align: right;
}

.shifts-container {
    font-size: 0.85em;
}

.shift-header {
    font-weight: bold;
    margin-top: 2px;
    font-size: 0.9em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk mengubah tanggal
    document.getElementById('dateSelector').addEventListener('change', function() {
        let currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('date', this.value);
        window.location.href = currentUrl.toString();
    });
});
</script>

<?php
// Fungsi untuk mendapatkan shift harian berdasarkan tanggal dan tim
if (!function_exists('getDailyShifts')) {
    function getDailyShifts($date, $team) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT s.*, u.name as user_name, u.id as user_id
            FROM shifts s
            JOIN users u ON s.user_id = u.id
            WHERE s.shift_date = ? AND u.role = ?
            ORDER BY s.shift_type, u.name
        ");
        $stmt->execute([$date, $team]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fungsi untuk mendapatkan semua user berdasarkan role
if (!function_exists('getUsersByRole')) {
    function getUsersByRole($role) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT id, name
            FROM users
            WHERE role = ?
            ORDER BY name
        ");
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fungsi untuk mendapatkan label shift
if (!function_exists('getShiftTypeLabel')) {
    function getShiftTypeLabel($type) {
        $labels = [
            'morning' => 'Pagi (08:00 - 16:00)',
            'afternoon' => 'Sore (16:00 - 00:00)'
        ];
        
        return $labels[$type] ?? ucfirst($type);
    }
}

// Fungsi untuk mendapatkan warna shift
if (!function_exists('getShiftTypeColor')) {
    function getShiftTypeColor($type) {
        $colors = [
            'morning' => 'info',
            'afternoon' => 'primary'
        ];
        
        return $colors[$type] ?? 'secondary';
    }
}
?>

<?php include '../includes/footer.php'; ?>
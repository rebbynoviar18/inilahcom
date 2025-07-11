<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/functions/program_schedule.php';

redirectIfNotLoggedIn();

// Ambil bulan dan tahun dari parameter URL atau gunakan bulan saat ini
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validasi bulan dan tahun
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = date('n');
if ($selectedYear < 2020 || $selectedYear > 2030) $selectedYear = date('Y');

// Format untuk tampilan
$monthName = getIndonesianMonth($selectedMonth);

// Ambil data program untuk bulan yang dipilih
$programSchedules = getMonthProgramSchedules($pdo, $selectedMonth, $selectedYear);

// Ambil statistik program
$programStats = getProgramCompletionStats($pdo, null, $selectedMonth, $selectedYear);

// Hitung jumlah hari dalam bulan
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);

// Tentukan hari pertama bulan
$firstDayOfMonth = date('w', strtotime("$selectedYear-$selectedMonth-01"));

$pageTitle = "Kalender Program - $monthName $selectedYear";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Kalender Program - <?= $monthName ?> <?= $selectedYear ?></h5>
                    <div>
                        <form method="get" class="d-flex">
                            <select name="month" class="form-select me-2">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $selectedMonth ? 'selected' : '' ?>>
                                        <?= getIndonesianMonth($i) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="year" class="form-select me-2">
                                <?php for ($i = 2020; $i <= 2030; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $selectedYear ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Total Program</h6>
                                    <h2 class="mb-0"><?= $programStats['total'] ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Selesai</h6>
                                    <h2 class="mb-0"><?= $programStats['completed'] ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Dijadwalkan</h6>
                                    <h2 class="mb-0"><?= $programStats['upcoming'] ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Terlambat</h6>
                                    <h2 class="mb-0"><?= $programStats['late'] ?></h2>
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
                                    
                                    $dateStr = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $dayCount);
                                    $dayPrograms = isset($programSchedules[$dateStr]) ? $programSchedules[$dateStr] : [];
                                    
                                    $calendar .= '<td class="calendar-day ' . (date('Y-m-d') == $dateStr ? 'today' : '') . '">';
                                    $calendar .= '<div class="date-header">' . $dayCount . '</div>';
                                    
                                    if (!empty($dayPrograms)) {
                                        $calendar .= '<div class="program-list">';
                                        foreach ($dayPrograms as $program) {
                                            $statusColor = getProgramStatusColor($program);
                                            $statusIcon = getProgramStatusIcon($program);
                                            $progressPercent = ($program['completed_count'] / $program['target_count']) * 100;
                                            
                                            $calendar .= '<div class="program-item border-' . $statusColor . '">';
                                            $calendar .= '<div class="program-title">' . $statusIcon . ' ' . htmlspecialchars($program['program_name']) . '</div>';
                                            
                                            if (!empty($program['pic_name'])) {
                                                $calendar .= '<div class="program-pic small">' . htmlspecialchars($program['pic_name']) . '</div>';
                                            }
                                            
                                            $calendar .= '<div class="progress mt-1" style="height: 5px;">';
                                            $calendar .= '<div class="progress-bar bg-' . $statusColor . '" role="progressbar" style="width: ' . $progressPercent . '%;" ';
                                            $calendar .= 'aria-valuenow="' . $progressPercent . '" aria-valuemin="0" aria-valuemax="100"></div>';
                                            $calendar .= '</div>';
                                            
                                            $calendar .= '<div class="program-progress small">' . $program['completed_count'] . '/' . $program['target_count'] . '</div>';
                                            $calendar .= '</div>';
                                        }
                                        $calendar .= '</div>';
                                    }
                                    
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
.calendar-table {
    table-layout: fixed;
}

.calendar-table th {
    text-align: center;
    background-color: #f8f9fa;
    padding: 10px;
}

.calendar-day {
    height: 150px;
    padding: 5px;
    vertical-align: top;
    position: relative;
}

.today {
    background-color: rgba(0, 123, 255, 0.05);
    border: 2px solid #007bff !important;
}

.empty-day {
    background-color: #f8f9fa;
}

.date-header {
    font-weight: bold;
    text-align: right;
    margin-bottom: 5px;
}

.program-list {
    overflow-y: auto;
    max-height: 120px;
}

.program-item {
    padding: 5px;
    margin-bottom: 5px;
    border-left: 3px solid;
    background-color: rgba(0, 0, 0, 0.03);
    border-radius: 3px;
}

.program-title {
    font-weight: 500;
    font-size: 0.85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.program-pic {
    color: #6c757d;
}

.program-progress {
    text-align: right;
    color: #6c757d;
}
</style>

<?php include '../includes/footer.php'; ?>
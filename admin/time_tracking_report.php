<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Filter
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get users
$users = $pdo->query("
    SELECT id, name 
    FROM users 
    WHERE role = 'production_team' 
    ORDER BY name
")->fetchAll();

// Get time tracking data
$params = [];
$sql = "
    SELECT 
        tt.*, 
        t.title as task_title,
        u.name as user_name
    FROM time_tracking tt
    JOIN tasks t ON tt.task_id = t.id
    JOIN users u ON tt.user_id = u.id
    WHERE tt.end_time IS NOT NULL
";

if ($userId) {
    $sql .= " AND tt.user_id = ?";
    $params[] = $userId;
}

$sql .= " AND DATE(tt.start_time) BETWEEN ? AND ?";
$params[] = $startDate;
$params[] = $endDate;

$sql .= " ORDER BY tt.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trackingData = $stmt->fetchAll();

// Calculate summary
$summary = [];
foreach ($trackingData as $tracking) {
    $userId = $tracking['user_id'];
    $duration = strtotime($tracking['end_time']) - strtotime($tracking['start_time']);
    
    if (!isset($summary[$userId])) {
        $summary[$userId] = [
            'name' => $tracking['user_name'],
            'total_duration' => 0,
            'task_count' => 0,
            'tasks' => []
        ];
    }
    
    $summary[$userId]['total_duration'] += $duration;
    
    if (!in_array($tracking['task_id'], $summary[$userId]['tasks'])) {
        $summary[$userId]['tasks'][] = $tracking['task_id'];
        $summary[$userId]['task_count']++;
    }
}

$pageTitle = "Laporan Time Tracking";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Laporan Time Tracking</h4>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="user_id">Production Team</label>
                                    <select class="form-control" id="user_id" name="user_id">
                                        <option value="">Semua</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo $userId == $user['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">Tanggal Akhir</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary form-control">Filter</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Ringkasan</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Production Team</th>
                                                    <th>Total Waktu</th>
                                                    <th>Jumlah Task</th>
                                                    <th>Rata-rata per Task</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($summary as $userId => $data): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($data['name']); ?></td>
                                                    <td><?php echo floor($data['total_duration'] / 3600); ?> jam <?php echo floor(($data['total_duration'] % 3600) / 60); ?> menit</td>
                                                    <td><?php echo $data['task_count']; ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($data['task_count'] > 0) {
                                                            $avg = $data['total_duration'] / $data['task_count'];
                                                            echo floor($avg / 3600) . ' jam ' . floor(($avg % 3600) / 60) . ' menit';
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
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
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Detail Time Tracking</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="trackingTable">
                                    <thead>
                                        <tr>
                                            <th>Production Team</th>
                                            <th>Task</th>
                                            <th>Mulai</th>
                                            <th>Selesai</th>
                                            <th>Durasi</th>
                                            <th>Catatan</th>
                                            <th>Jenis</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trackingData as $tracking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tracking['user_name']); ?></td>
                                            <td>
                                                <a href="../view_task.php?id=<?php echo $tracking['task_id']; ?>">
                                                    <?php echo htmlspecialchars($tracking['task_title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d M Y H:i', strtotime($tracking['start_time'])); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($tracking['end_time'])); ?></td>
                                            <td>
                                                <?php 
                                                $duration = strtotime($tracking['end_time']) - strtotime($tracking['start_time']);
                                                echo floor($duration / 3600) . ' jam ' . floor(($duration % 3600) / 60) . ' menit';
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($tracking['notes'] ?? '-'); ?></td>
                                            <td><?php echo $tracking['is_auto'] ? '<span class="badge bg-info">Auto</span>' : '<span class="badge bg-primary">Manual</span>'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#trackingTable').DataTable({
        order: [[2, 'desc']]
    });
});
</script>

<?php include '../includes/footer.php'; ?>
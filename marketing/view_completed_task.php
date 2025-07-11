<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'marketing_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Dapatkan detail task
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name, 
           a.name as account_name,
           cp.name as content_pillar_name,
           ct.name as content_type_name,
           u1.name as assigned_to_name,
           u2.name as created_by_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN content_pillars cp ON t.content_pillar_id = cp.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN users u1 ON t.assigned_to = u1.id
    LEFT JOIN users u2 ON t.created_by = u2.id
    WHERE t.id = ? AND t.created_by = ? AND t.status = 'completed'
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dapat diakses";
    header('Location: tasks.php');
    exit();
}

// Dapatkan link distribusi jika ada
$links = [];
if ($task['category_name'] === 'Distribusi') {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM task_links
            WHERE task_id = ?
        ");
        $stmt->execute([$taskId]);
        $links = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Jika tabel tidak ada, abaikan
        $links = [];
    }
}

// Dapatkan riwayat status
$statusLogs = [];
try {
    $stmt = $pdo->prepare("
        SELECT tsl.*, u.name as user_name
        FROM task_status_logs tsl
        LEFT JOIN users u ON tsl.updated_by = u.id
        WHERE tsl.task_id = ?
        ORDER BY tsl.timestamp DESC
    ");
    $stmt->execute([$taskId]);
    $statusLogs = $stmt->fetchAll();
} catch (PDOException $e) {
    // Jika tabel atau kolom tidak ada, abaikan
    $statusLogs = [];
}

$pageTitle = "Detail Task Selesai";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Detail Task Selesai</h4>
                    <span class="badge bg-success">Selesai</span>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Informasi Task</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Judul</th>
                            <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td><?= nl2br(htmlspecialchars($task['description'])) ?></td>
                        </tr>
                        <tr>
                            <th>Kategori</th>
                            <td><?= htmlspecialchars($task['category_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Akun</th>
                            <td><?= htmlspecialchars($task['account_name']) ?></td>
                        </tr>
                        <?php if (!empty($task['client_name'])): ?>
                        <tr>
                            <th>Nama Klien</th>
                            <td><?= htmlspecialchars($task['client_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($task['content_pillar_name'])): ?>
                        <tr>
                            <th>Pilar Konten</th>
                            <td><?= htmlspecialchars($task['content_pillar_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($task['content_type_name'])): ?>
                        <tr>
                            <th>Jenis Konten</th>
                            <td><?= htmlspecialchars($task['content_type_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Dikerjakan Oleh</th>
                            <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Tanggal Selesai</th>
                            <td><?= date('d M Y H:i', strtotime($task['updated_at'])) ?></td>
                        </tr>
                        <?php if (isset($task['rating']) && $task['rating'] > 0): ?>
                        <tr>
                            <th>Rating</th>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $task['rating']): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-warning"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <?php if (!empty($links)): ?>
                    <h5 class="mt-4 mb-3">Link Distribusi</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Platform</th>
                                    <th>Link</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $link): ?>
                                <tr>
                                    <td><?= htmlspecialchars($link['platform'] ?? 'Platform') ?></td>
                                    <td><?= htmlspecialchars($link['link']) ?></td>
                                    <td>
                                        <a href="<?= htmlspecialchars($link['link']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-external-link-alt"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($task['file_path'])): ?>
                    <h5 class="mt-4 mb-3">File Hasil</h5>
                    <div class="mb-3">
                        <a href="../uploads/<?= $task['file_path'] ?>" class="btn btn-primary" download>
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <?php if (!empty($statusLogs)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Riwayat Status</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($statusLogs as $log): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0"><?= getStatusLabel($log['status']) ?></h6>
                                <small class="text-muted">
                                    <?= date('d M Y H:i', strtotime($log['timestamp'])) ?>
                                </small>
                                <p class="mb-0">oleh <?= htmlspecialchars($log['user_name']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5>Aksi</h5>
                </div>
                <div class="card-body">
                    <a href="tasks.php" class="btn btn-secondary btn-block mb-2">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Task
                    </a>
                    
                    <a href="create_task.php?clone=<?= $taskId ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-copy"></i> Buat Task Serupa
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    left: -30px;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background-color: #007bff;
    border: 3px solid #fff;
    box-shadow: 0 0 0 1px #007bff;
}
.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -23px;
    top: 15px;
    height: calc(100% - 15px);
    width: 2px;
    background-color: #007bff;
}
</style>

<?php include '../includes/footer.php'; ?>
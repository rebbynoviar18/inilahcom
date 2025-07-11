<?php
// File: production/tasks.php

require_once '../config/database.php';
require_once '../includes/auth.php';

// Periksa login dan role
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

if (getUserRole() !== 'production_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Hitung total task untuk pagination
$countQuery = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users u ON t.created_by = u.id
    WHERE t.assigned_to = ?
");
$countQuery->execute([$userId]);
$totalTasks = $countQuery->fetchColumn();
$totalPages = ceil($totalTasks / $perPage);

// Modifikasi query utama untuk menggunakan LIMIT untuk pagination
$tasksQuery = $pdo->prepare("
    SELECT t.*, c.name as category_name, a.name as account_name, u.name as created_by_name
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users u ON t.created_by = u.id
    WHERE t.assigned_to = ?
    ORDER BY t.created_at DESC
    LIMIT $offset, $perPage
");
$tasksQuery->execute([$userId]);
$tasks = $tasksQuery->fetchAll();

$pageTitle = "Daftar Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Daftar Task</h4>
                    <div>
                        <select class="form-select" id="statusFilter" onchange="filterTasks()">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="waiting_confirmation" <?php echo $status === 'waiting_confirmation' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                            <option value="in_production" <?php echo $status === 'in_production' ? 'selected' : ''; ?>>Dalam Pengerjaan</option>
                            <option value="revision" <?php echo $status === 'revision' ? 'selected' : ''; ?>>Revisi</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <div class="alert alert-info">Tidak ada task yang ditemukan</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>PIC</th>
                                        <th>Kategori</th>
                                        <th>Akun</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th>Prioritas</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            
                                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                                            <td>
                                                <?= getUserProfilePhotoWithName($task['created_by'], $task['created_by_name'], "rounded-circle me-2", "24") ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($task['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($task['account_name']); ?></td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($task['deadline'])); ?>
                                                <small class="d-block text-muted"><?php echo date('H:i', strtotime($task['deadline'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($task['status']); ?>">
                                                    <?php echo getStatusLabel($task['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getPriorityColor($task['priority']); ?>">
                                                    <?php echo getPriorityLabel($task['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </a>
                                            </td>
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
</div>

<script>
function filterTasks() {
    const status = document.getElementById('statusFilter').value;
    window.location.href = `tasks.php${status !== 'all' ? '?status=' + status : ''}`;
}
</script>

<div class="row mt-3">
    <div class="col-md-12">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
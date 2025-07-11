<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];



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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Daftar Task</h2>
        <a href="create_task.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Task Baru
        </a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tasksTable">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Akun</th>
                            <th>Deadline</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><a href="view_task.php?id=<?= $task['id'] ?>"><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></a></td>
                            <td><?= htmlspecialchars($task['category_name']) ?></td>
                            <td><?= htmlspecialchars($task['account_name']) ?></td>
                            <td><?= date('d M Y - H:i', strtotime($task['deadline'])) ?></td>
                            <td><?= getStatusBadge($task['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


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

<script>
$(document).ready(function() {
    $('#tasksTable').DataTable({
        "order": [[0, "desc"]], // Sort by ID descending (newest first)
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
});
</script>



<?php include '../includes/footer.php'; ?>
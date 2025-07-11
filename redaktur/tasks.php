<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'redaktur_pelaksana' && getUserRole() !== 'redaksi') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = getUserRole();

// Filter dan pagination
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base query untuk menghitung total
$countQuery = "
    SELECT COUNT(*) 
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE 1=1
";

// Base query untuk mengambil data
$query = "
    SELECT t.*, 
           c.name as category_name,
           ct.name as content_type_name,
           a.name as account_name,
           creator.name as creator_name,
           creator.role as creator_role,
           assignee.name as assigned_to_name,
           assignee.role as assignee_role
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE 1=1
";

// Kondisi filter berdasarkan role
if ($userRole === 'redaktur_pelaksana') {
    // Redaktur pelaksana dapat melihat semua task yang dibuat oleh marketing untuk redaksi
    // dan task yang menunggu persetujuan redaktur pelaksana
    $roleCondition = "
        AND (
            (creator.role = 'marketing_team' AND c.name = 'Publikasi') 
            OR t.status = 'waiting_redaktur_confirmation'
            OR t.created_by = $userId
            OR t.assigned_to = $userId
        )
    ";
} else { // redaksi
    // Redaksi hanya melihat task yang ditugaskan kepadanya atau yang dia buat
    $roleCondition = "
        AND (t.assigned_to = $userId OR t.created_by = $userId)
    ";
}

$countQuery .= $roleCondition;
$query .= $roleCondition;

// Filter berdasarkan status
if ($status !== 'all') {
    $statusCondition = " AND t.status = :status";
    $countQuery .= $statusCondition;
    $query .= $statusCondition;
}

// Sorting
$query .= " ORDER BY 
    CASE 
        WHEN t.status = 'waiting_redaktur_confirmation' THEN 1
        WHEN t.status = 'waiting_confirmation' THEN 2
        WHEN t.status = 'in_production' THEN 3
        WHEN t.status = 'ready_for_review' THEN 4
        WHEN t.status = 'revision' THEN 5
        WHEN t.status = 'uploaded' THEN 6
        WHEN t.status = 'completed' THEN 7
        ELSE 8
    END,
    t.deadline ASC,
    t.created_at DESC
";

// Pagination
$query .= " LIMIT :limit OFFSET :offset";

// Execute count query
$countStmt = $pdo->prepare($countQuery);
if ($status !== 'all') {
    $countStmt->bindParam(':status', $status);
}
$countStmt->execute();
$totalTasks = $countStmt->fetchColumn();
$totalPages = ceil($totalTasks / $limit);

// Execute main query
$stmt = $pdo->prepare($query);
if ($status !== 'all') {
    $stmt->bindParam(':status', $status);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tasks = $stmt->fetchAll();

// Get status counts for filter badges
$statusCounts = [];
$statusQuery = "
    SELECT t.status, COUNT(*) as count
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE 1=1 $roleCondition
    GROUP BY t.status
";
$statusStmt = $pdo->prepare($statusQuery);
$statusStmt->execute();
$statusResults = $statusStmt->fetchAll();

foreach ($statusResults as $result) {
    $statusCounts[$result['status']] = $result['count'];
}

// Calculate total
$totalCount = array_sum($statusCounts);
$statusCounts['all'] = $totalCount;

// Status labels and colors
$statusLabels = [
    'draft' => ['label' => 'Draft', 'color' => 'secondary'],
    'waiting_head_confirmation' => ['label' => 'Menunggu Konfirmasi Head', 'color' => 'info'],
    'waiting_redaktur_confirmation' => ['label' => 'Menunggu Konfirmasi Redaktur', 'color' => 'info'],
    'waiting_confirmation' => ['label' => 'Menunggu Konfirmasi', 'color' => 'info'],
    'in_production' => ['label' => 'Dalam Pengerjaan', 'color' => 'primary'],
    'ready_for_review' => ['label' => 'Siap Direview', 'color' => 'warning'],
    'revision' => ['label' => 'Perlu Revisi', 'color' => 'danger'],
    'uploaded' => ['label' => 'Sudah Diupload', 'color' => 'success'],
    'completed' => ['label' => 'Selesai', 'color' => 'success'],
    'rejected' => ['label' => 'Ditolak', 'color' => 'danger'],
    'all' => ['label' => 'Semua', 'color' => 'dark']
];

$pageTitle = "Daftar Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Daftar Task</h1>
    
    <?php include '../includes/alerts.php'; ?>
    
    <!-- Filter Status -->
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($statusLabels as $statusKey => $statusInfo): ?>
                <?php if (isset($statusCounts[$statusKey]) && $statusCounts[$statusKey] > 0): ?>
                    <a href="?status=<?= $statusKey ?>" class="text-decoration-none">
                        <span class="badge bg-<?= $statusInfo['color'] ?> <?= $status === $statusKey ? 'border border-dark' : '' ?>">
                            <?= $statusInfo['label'] ?>: <?= $statusCounts[$statusKey] ?? 0 ?>
                        </span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Task List -->
    <div class="card">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Task <?= $statusLabels[$status]['label'] ?></h5>
                </div>
                <?php if ($userRole === 'redaktur_pelaksana'): ?>
                <div class="col-auto">
                    <a href="create_task.php" class="btn btn-primary btn-sm">Buat Task Baru</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (count($tasks) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Akun</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Dibuat Oleh</th>
                                <th>Ditugaskan Kepada</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                                    <td><?= htmlspecialchars($task['category_name']) ?></td>
                                    <td><?= htmlspecialchars($task['account_name']) ?></td>
                                    <td>
                                        <?php if ($task['deadline']): ?>
                                            <span class="<?= strtotime($task['deadline']) < time() ? 'text-danger fw-bold' : '' ?>">
                                                <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusLabels[$task['status']]['color'] ?>">
                                            <?= $statusLabels[$task['status']]['label'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($task['creator_name']) ?></td>
                                    <td><?= $task['assigned_to_name'] ? htmlspecialchars($task['assigned_to_name']) : '-' ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_task.php?id=<?= $task['id'] ?>" class="btn btn-primary">Lihat</a>
                                            
                                            <?php if ($userRole === 'redaktur_pelaksana' && $task['status'] === 'waiting_redaktur_confirmation'): ?>
                                                <a href="approve_task.php?id=<?= $task['id'] ?>" class="btn btn-success">Approve</a>
                                                <a href="reject_task.php?id=<?= $task['id'] ?>" class="btn btn-danger">Tolak</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($task['created_by'] == $userId && in_array($task['status'], ['draft', 'rejected'])): ?>
                                                <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-warning">Edit</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center">
                    <p class="mb-0">Tidak ada task yang ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?status=<?= $status ?>&page=<?= $page - 1 ?>">Previous</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?status=<?= $status ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?status=<?= $status ?>&page=<?= $page + 1 ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    $('#tasksTable').DataTable({
        "order": [],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
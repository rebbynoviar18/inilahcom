<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'redaksi') {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Filter dan pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$contentTypeId = isset($_GET['content_type_id']) ? (int)$_GET['content_type_id'] : 0;
$contentPillarId = isset($_GET['content_pillar_id']) ? (int)$_GET['content_pillar_id'] : 0;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$taskType = isset($_GET['task_type']) ? $_GET['task_type'] : 'all';

// Build query conditions
$conditions = [];
$params = [];

// Task type filter
if ($taskType === 'created') {
    $conditions[] = "t.created_by = ?";
    $params[] = $userId;
} elseif ($taskType === 'assigned') {
    $conditions[] = "t.assigned_to = ?";
    $params[] = $userId;
} else {
    $conditions[] = "(t.created_by = ? OR t.assigned_to = ?)";
    $params[] = $userId;
    $params[] = $userId;
}

if (!empty($status)) {
    $conditions[] = "t.status = ?";
    $params[] = $status;
}

if (!empty($priority)) {
    $conditions[] = "t.priority = ?";
    $params[] = $priority;
}

if (!empty($search)) {
    $conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($accountId)) {
    $conditions[] = "t.account_id = ?";
    $params[] = $accountId;
}

if (!empty($categoryId)) {
    $conditions[] = "t.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($contentTypeId)) {
    $conditions[] = "t.content_type_id = ?";
    $params[] = $contentTypeId;
}

if (!empty($contentPillarId)) {
    $conditions[] = "t.content_pillar_id = ?";
    $params[] = $contentPillarId;
}

if (!empty($dateFrom)) {
    $conditions[] = "t.created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if (!empty($dateTo)) {
    $conditions[] = "t.created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total count for pagination
$countQuery = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tasks t 
    $whereClause
");
$countQuery->execute($params);
$totalTasks = $countQuery->fetchColumn();
$totalPages = ceil($totalTasks / $limit);

// Get tasks
$query = $pdo->prepare("
    SELECT t.*, 
           a.name as account_name, 
           c.name as category_name,
           ct.name as content_type_name,
           cp.name as content_pillar_name,
           creator.name as creator_name,
           assignee.name as assignee_name
    FROM tasks t
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN content_pillars cp ON t.content_pillar_id = cp.id
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    $whereClause
    ORDER BY 
        CASE 
            WHEN t.status = 'waiting_confirmation' THEN 1
            WHEN t.status = 'in_production' THEN 2
            WHEN t.status = 'ready_for_review' THEN 3
            WHEN t.status = 'revision' THEN 4
            WHEN t.status = 'uploaded' THEN 5
            WHEN t.status = 'completed' THEN 6
            ELSE 7
        END,
        CASE 
            WHEN t.priority = 'urgent' THEN 1
            WHEN t.priority = 'high' THEN 2
            WHEN t.priority = 'medium' THEN 3
            WHEN t.priority = 'low' THEN 4
            ELSE 5
        END,
        t.deadline ASC
    LIMIT $limit OFFSET $offset
");
$query->execute($params);
$tasks = $query->fetchAll();

// Get filter options
$accounts = $pdo->query("SELECT id, name FROM accounts ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$contentTypes = $pdo->query("SELECT id, name FROM content_types ORDER BY name")->fetchAll();
$contentPillars = $pdo->query("SELECT id, name FROM content_pillars ORDER BY name")->fetchAll();
$productionTeam = $pdo->query("SELECT id, name FROM users WHERE role = 'production_team' ORDER BY name")->fetchAll();

$pageTitle = "Daftar Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Daftar Task</h1>
        <a href="create_task.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Task Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <a class="btn btn-link text-dark text-decoration-none w-100 text-start" data-bs-toggle="collapse" href="#filterCollapse">
                <i class="fas fa-filter"></i> Filter dan Pencarian
            </a>
        </div>
        <div class="collapse" id="filterCollapse">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Pencarian</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Judul atau deskripsi...">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="waiting_head_confirmation" <?= $status === 'waiting_head_confirmation' ? 'selected' : '' ?>>Menunggu Konfirmasi Head</option>
                            <option value="waiting_confirmation" <?= $status === 'waiting_confirmation' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                            <option value="in_production" <?= $status === 'in_production' ? 'selected' : '' ?>>Dalam Pengerjaan</option>
                            <option value="ready_for_review" <?= $status === 'ready_for_review' ? 'selected' : '' ?>>Siap Direview</option>
                            <option value="uploaded" <?= $status === 'uploaded' ? 'selected' : '' ?>>Telah Diupload</option>
                            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Selesai</option>
                            <option value="revision" <?= $status === 'revision' ? 'selected' : '' ?>>Revisi</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="priority" class="form-label">Prioritas</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">Semua Prioritas</option>
                            <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Rendah</option>
                            <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Sedang</option>
                            <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Tinggi</option>
                            <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="account_id" class="form-label">Akun</label>
                        <select class="form-select" id="account_id" name="account_id">
                            <option value="">Semua Akun</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?= $account['id'] ?>" <?= $accountId == $account['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($account['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="task_type" class="form-label">Tipe Task</label>
                        <select class="form-select" id="task_type" name="task_type">
                            <option value="all" <?= $taskType === 'all' ? 'selected' : '' ?>>Semua Task</option>
                            <option value="created" <?= $taskType === 'created' ? 'selected' : '' ?>>Task yang Saya Buat</option>
                            <option value="assigned" <?= $taskType === 'assigned' ? 'selected' : '' ?>>Task yang Ditugaskan ke Saya</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="date_from" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="date_to" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="content_type_id" class="form-label">Tipe Konten</label>
                        <select class="form-select" id="content_type_id" name="content_type_id">
                            <option value="">Semua Tipe Konten</option>
                            <?php foreach ($contentTypes as $contentType): ?>
                                <option value="<?= $contentType['id'] ?>" <?= $contentTypeId == $contentType['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($contentType['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        <a href="tasks.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($tasks)): ?>
                <div class="alert alert-info">
                    Tidak ada task yang ditemukan.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Akun</th>
                                <th>Kategori</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Prioritas</th>
                                <th>Ditugaskan Kepada</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <a href="view_task.php?id=<?= $task['id'] ?>">
                                            <?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($task['account_name']) ?></td>
                                    <td><?= htmlspecialchars($task['category_name']) ?></td>
                                    <td>
                                        <?= date('d M Y', strtotime($task['deadline'])) ?>
                                        <br>
                                        <small class="text-muted"><?= date('H:i', strtotime($task['deadline'])) ?></small>
                                    </td>
                                    <td><?= getStatusBadge($task['status']) ?></td>
                                    <td><?= getPriorityBadge($task['priority']) ?></td>
                                    <td>
                                        <?php if ($task['assignee_name']): ?>
                                            <?= htmlspecialchars($task['assignee_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Belum ditugaskan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&priority=<?= urlencode($priority) ?>&search=<?= urlencode($search) ?>&account_id=<?= $accountId ?>&category_id=<?= $categoryId ?>&content_type_id=<?= $contentTypeId ?>&content_pillar_id=<?= $contentPillarId ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&task_type=<?= urlencode($taskType) ?>">
                                        &laquo; Sebelumnya
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&priority=<?= urlencode($priority) ?>&search=<?= urlencode($search) ?>&account_id=<?= $accountId ?>&category_id=<?= $categoryId ?>&content_type_id=<?= $contentTypeId ?>&content_pillar_id=<?= $contentPillarId ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&task_type=<?= urlencode($taskType) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&priority=<?= urlencode($priority) ?>&search=<?= urlencode($search) ?>&account_id=<?= $accountId ?>&category_id=<?= $categoryId ?>&content_type_id=<?= $contentTypeId ?>&content_pillar_id=<?= $contentPillarId ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&task_type=<?= urlencode($taskType) ?>">
                                        Selanjutnya &raquo;
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
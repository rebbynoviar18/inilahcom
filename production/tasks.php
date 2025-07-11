<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'production_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Filter dan pencarian
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$accountId = isset($_GET['account_id']) ? $_GET['account_id'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Query dasar
$query = "
    SELECT t.*, 
           c.name as category_name, 
           ct.name as content_type_name,
           a.name as account_name, 
           u.name as created_by_name
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN content_types ct ON t.content_type_id = ct.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users u ON t.created_by = u.id
    WHERE t.assigned_to = :user_id
";

$countQuery = "
    SELECT COUNT(*) 
    FROM tasks t
    WHERE t.assigned_to = :user_id
";

$params = [':user_id' => $userId];

// Tambahkan filter status
if ($status !== 'all') {
    if ($status === 'active') {
        $query .= " AND t.status NOT IN ('completed', 'cancelled')";
        $countQuery .= " AND t.status NOT IN ('completed', 'cancelled')";
    } else {
        $query .= " AND t.status = :status";
        $countQuery .= " AND t.status = :status";
        $params[':status'] = $status;
    }
}

// Tambahkan filter akun
if (!empty($accountId)) {
    $query .= " AND t.account_id = :account_id";
    $countQuery .= " AND t.account_id = :account_id";
    $params[':account_id'] = $accountId;
}

// Tambahkan pencarian
if (!empty($search)) {
    $query .= " AND (t.title LIKE :search OR t.description LIKE :search)";
    $countQuery .= " AND (t.title LIKE :search OR t.description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Tambahkan pengurutan dan pagination
$query .= " ORDER BY 
    t.created_at DESC,
    t.deadline ASC
    LIMIT :offset, :per_page";

$params[':offset'] = $offset;
$params[':per_page'] = $perPage;

// Eksekusi query
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    if ($key === ':offset' || $key === ':per_page') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$tasks = $stmt->fetchAll();

// Hitung total untuk pagination
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    if ($key !== ':offset' && $key !== ':per_page') {
        $countStmt->bindValue($key, $value);
    }
}
$countStmt->execute();
$totalTasks = $countStmt->fetchColumn();
$totalPages = ceil($totalTasks / $perPage);

// Get accounts for filter
$accountsStmt = $pdo->query("SELECT * FROM accounts ORDER BY name");
$accounts = $accountsStmt->fetchAll();

// Get active tracking
$activeTracking = getActiveTracking($userId);

$pageTitle = "Task Saya";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Task Saya</h4>                    
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Task Aktif</option>
                                    <option value="in_production" <?= $status === 'in_production' ? 'selected' : '' ?>>Dalam Produksi</option>
                                    <option value="ready_for_review" <?= $status === 'ready_for_review' ? 'selected' : '' ?>>Siap Review</option>
                                    <option value="uploaded" <?= $status === 'uploaded' ? 'selected' : '' ?>>Telah Upload</option>
                                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="revision" <?= $status === 'revision' ? 'selected' : '' ?>>Revisi</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
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
                            
                            <div class="col-md-3">
                                <label for="search" class="form-label">Pencarian</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Cari judul atau deskripsi" value="<?= htmlspecialchars($search) ?>">
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Tasks Table -->
                    <?php if (empty($tasks)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <h5>Tidak ada task yang ditemukan</h5>
                            <p class="text-muted">Coba ubah filter atau kriteria pencarian</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Judul</th>
                                        <th>PIC Konten</th>
                                        <th>Kategori</th>
                                        <th>Akun</th>
                                        <th>Platform</th>
                                        <th>Prioritas</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr class="task-row" data-href="view_task.php?id=<?= $task['id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (isTaskOverdue($task) && $task['status'] !== 'completed'): ?>
                                                        <span class="badge bg-danger me-2" title="Terlambat">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="fw-medium"><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?= getUserProfilePhotoWithName($task['created_by'], $task['created_by_name'], "rounded-circle me-2", "24") ?>
                                            </td>
                                            <td><?= htmlspecialchars($task['category_name']) ?></td>
                                            <td><?= htmlspecialchars($task['account_name']) ?></td>
                                            <td><?= displayPlatformIcons($task['id']) ?></td>
                                            <td><?= getPriorityBadge($task['priority']) ?></td>
                                            <td>
                                                <span class="<?= isTaskOverdue($task) && $task['status'] !== 'completed' ? 'text-danger fw-bold' : '' ?>">
                                                    <?= date('d M Y', strtotime($task['deadline'])) ?>
                                                    <small class="d-block text-muted"><?= date('H:i', strtotime($task['deadline'])) ?></small>
                                                </span>
                                            </td>
                                            <td><?= getStatusBadge($task['status']) ?></td>                                            
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                        if ($startPage > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php
                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
                                    }
                                    ?>
                                                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                <i class="fas fa-chevron-right"></i>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Membuat baris tabel dapat diklik
    const taskRows = document.querySelectorAll('.task-row');
    taskRows.forEach(row => {
        row.addEventListener('click', function() {
            window.location.href = this.dataset.href;
        });
    });
    
    // Timer untuk time tracking
    const timerElement = document.querySelector('.time-tracking-timer');
    if (timerElement) {
        const startTime = new Date(timerElement.dataset.start).getTime();
        
        setInterval(function() {
            const now = new Date().getTime();
            const diff = now - startTime;
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            const timerDisplay = document.querySelector('.timer-display');
            timerDisplay.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
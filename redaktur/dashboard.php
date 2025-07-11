<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'redaktur_pelaksana') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Ambil data bio user dari database
$userStmt = $pdo->prepare("SELECT bio FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch();
$userBio = $userData['bio'] ?? '';

if (getUserRole() === 'redaktur_pelaksana') {
    // Ambil semua task yang dibuat oleh marketing
    $stmt = $pdo->prepare("
        SELECT t.*, 
               c.name as category_name, 
               ct.name as content_type_name, 
               a.name as account_name,
               u_creator.name as creator_name, 
               u_assignee.name as assignee_name
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN content_types ct ON t.content_type_id = ct.id
        LEFT JOIN accounts a ON t.account_id = a.id
        LEFT JOIN users u_creator ON t.created_by = u_creator.id
        LEFT JOIN users u_assignee ON t.assigned_to = u_assignee.id
        WHERE t.created_by = ?
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentTasks = $stmt->fetchAll();

    // Hitung jumlah task berdasarkan status
    $waitingConfirmation = getTaskCountByStatus($userId, 'waiting_head_confirmation', 'content_team');
    $inProduction = getTaskCountByStatus($userId, 'in_production', 'content_team');
    $readyForReview = getTaskCountByStatus($userId, 'ready_for_review', 'content_team');
    $completed = getTaskCountByStatus($userId, 'completed', 'content_team');
    $rejected = getTaskCountByStatus($userId, 'rejected', 'content_team');
    
    // Tambahkan untuk menghitung task publikasi yang menunggu penugasan dari redaktur pelaksana
    $waitingRedakturConfirmation = $pdo->prepare("
        SELECT COUNT(*) FROM tasks 
        WHERE status = 'waiting_redaktur_confirmation'
    ");
    $waitingRedakturConfirmation->execute();
    $waitingRedakturConfirmation = $waitingRedakturConfirmation->fetchColumn();
    
    // Tambahkan untuk menghitung task yang siap direview oleh redaktur pelaksana
    $uploadedTasks = $pdo->prepare("
        SELECT COUNT(*) FROM tasks 
        WHERE status = 'uploaded'
    ");
    $uploadedTasks->execute();
    $uploadedTasks = $uploadedTasks->fetchColumn();

    $pageTitle = "Dashboard Redaksi";
    include '../includes/header.php';
    ?>

    <div class="container-fluid">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <div class="row">
                <div class="col-md-12">
                    <h2>Dashboard Redaksi</h2>
                    <p class="text-muted">Selamat datang, <b><?php echo htmlspecialchars($_SESSION['name']); ?></b>! 
                        <?php if (!empty($userBio)): ?>
                            <span><?php echo htmlspecialchars($userBio); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Status Cards -->
        <div class="row mb-4">
            <div class="col-md-4 col-xl-3">
                <div class="card bg-danger text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Menunggu Penugasan</h5>
                        <p class="card-text display-4"><?= $waitingRedakturConfirmation ?></p>
                        <a href="tasks.php?status=waiting_redaktur_confirmation" class="text-white">Lihat Task <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 col-xl-3">
                <div class="card bg-warning text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Menunggu Konfirmasi</h5>
                        <p class="card-text display-4"><?= $waitingConfirmation ?></p>
                        <a href="tasks.php?status=waiting_head_confirmation" class="text-white">Lihat Task <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-xl-3">
                <div class="card bg-primary text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Dalam Proses</h5>
                        <p class="card-text display-4"><?= $inProduction ?></p>
                        <a href="tasks.php?status=in_production" class="text-white">Lihat Task <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-xl-3">
                <div class="card bg-info text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Siap Review</h5>
                        <p class="card-text display-4"><?= $readyForReview ?></p>
                        <a href="tasks.php?status=ready_for_review" class="text-white">Lihat Task <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-xl-3">
                <div class="card bg-secondary text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Sudah Diupload</h5>
                        <p class="card-text display-4"><?= $uploadedTasks ?></p>
                        <a href="tasks.php?status=uploaded" class="text-white">Lihat Task <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-xl-3">
                <div class="card bg-success text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Selesai</h5>
                        <p class="card-text display-4"><?= $completed ?></p>
                        <a href="tasks.php?status=completed" class="text-white">Lihat Task <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Task Menunggu Penugasan -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Task Menunggu Penugasan</h5>
                        <a href="tasks.php?status=waiting_redaktur_confirmation" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php
                        $pendingTasks = $pdo->prepare("
                            SELECT t.*, 
                                   c.name as category_name, 
                                   ct.name as content_type_name, 
                                   a.name as account_name,
                                   u_creator.name as creator_name
                            FROM tasks t
                            LEFT JOIN categories c ON t.category_id = c.id
                            LEFT JOIN content_types ct ON t.content_type_id = ct.id
                            LEFT JOIN accounts a ON t.account_id = a.id
                            LEFT JOIN users u_creator ON t.created_by = u_creator.id
                            WHERE t.status = 'waiting_redaktur_confirmation'
                            ORDER BY t.deadline ASC
                            LIMIT 5
                        ");
                        $pendingTasks->execute();
                        $pendingTasksList = $pendingTasks->fetchAll();
                        ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Kategori</th>
                                        <th>Akun</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Deadline</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($pendingTasksList) > 0): ?>
                                        <?php foreach ($pendingTasksList as $task): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                                                <td><?= htmlspecialchars($task['category_name']) ?></td>
                                                <td><?= htmlspecialchars($task['account_name']) ?></td>
                                                <td><?= htmlspecialchars($task['creator_name']) ?></td>
                                                <td><?= date('d M Y', strtotime($task['deadline'])) ?></td>
                                                <td>
                                                    <a href="assign_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-user-plus"></i> Tugaskan
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada task yang menunggu penugasan</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Task Sudah Diupload -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Task Sudah Diupload (Menunggu Konfirmasi Final)</h5>
                        <a href="tasks.php?status=uploaded" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php
                        $uploadedTasks = $pdo->prepare("
                            SELECT t.*, 
                                   c.name as category_name, 
                                   ct.name as content_type_name, 
                                   a.name as account_name,
                                   u_creator.name as creator_name,
                                   u_assignee.name as assignee_name
                            FROM tasks t
                            LEFT JOIN categories c ON t.category_id = c.id
                            LEFT JOIN content_types ct ON t.content_type_id = ct.id
                            LEFT JOIN accounts a ON t.account_id = a.id
                            LEFT JOIN users u_creator ON t.created_by = u_creator.id
                            LEFT JOIN users u_assignee ON t.assigned_to = u_assignee.id
                            WHERE t.status = 'uploaded'
                            ORDER BY t.updated_at DESC
                            LIMIT 5
                        ");
                        $uploadedTasks->execute();
                        $uploadedTasksList = $uploadedTasks->fetchAll();
                        ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Kategori</th>
                                        <th>Akun</th>
                                        <th>Dikerjakan Oleh</th>
                                        <th>Link Hasil</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($uploadedTasksList) > 0): ?>
                                        <?php foreach ($uploadedTasksList as $task): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                                                <td><?= htmlspecialchars($task['category_name']) ?></td>
                                                <td><?= htmlspecialchars($task['account_name']) ?></td>
                                                <td><?= htmlspecialchars($task['assignee_name']) ?></td>
                                                <td>
                                                    <?php if (!empty($task['result_link'])): ?>
                                                        <a href="<?= htmlspecialchars($task['result_link']) ?>" target="_blank">
                                                            <i class="fas fa-external-link-alt"></i> Lihat Hasil
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Tidak ada link</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                    <a href="complete_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Selesai
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada task yang sudah diupload</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tasks -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Task Terbaru</h5>
                        <a href="tasks.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Kategori</th>
                                        <th>Akun</th>
                                        <th>Status</th>
                                        <th>Deadline</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentTasks) > 0): ?>
                                        <?php foreach ($recentTasks as $task): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                                                <td><?= htmlspecialchars($task['category_name']) ?></td>
                                                <td><?= htmlspecialchars($task['account_name']) ?></td>
                                                <td><?= getStatusBadge($task['status']) ?></td>
                                                <td><?= date('d M Y', strtotime($task['deadline'])) ?></td>
                                                <td>
                                                    <a href="view_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($task['status'] === 'rejected'): ?>
                                                    <a href="revise_rejected_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> Revisi
                                                    </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Belum ada task</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Workflow Diagram -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Alur Kerja Publikasi Konten</h5>
                    </div>
                    <div class="card-body">
                        <div class="workflow-diagram">
                            <div class="workflow-step">
                                <div class="step-icon bg-danger">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="step-text">
                                    <h6>Task Masuk</h6>
                                    <p>Marketing membuat task publikasi</p>
                                </div>
                            </div>
                            <div class="workflow-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="workflow-step">
                                <div class="step-icon bg-warning">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="step-text">
                                    <h6>Penugasan</h6>
                                    <p>Redaktur Pelaksana memilih tim redaksi</p>
                                </div>
                            </div>
                            <div class="workflow-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="workflow-step">
                                <div class="step-icon bg-primary">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="step-text">
                                    <h6>Pengerjaan</h6>
                                    <p>Tim redaksi mengerjakan publikasi</p>
                                </div>
                            </div>
                            <div class="workflow-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="workflow-step">
                                <div class="step-icon bg-info">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="step-text">
                                    <h6>Review</h6>
                                    <p>Marketing mereview hasil publikasi</p>
                                </div>
                            </div>
                            <div class="workflow-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="workflow-step">
                                <div class="step-icon bg-success">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                                <div class="step-text">
                                    <h6>Selesai</h6>
                                    <p>Redaktur Pelaksana konfirmasi selesai</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CSS untuk workflow diagram -->
    <style>
        .workflow-diagram {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 20px 0;
        }
        .workflow-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 150px;
        }
        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            color: white;
            font-size: 24px;
        }
        .step-text h6 {
            margin-bottom: 5px;
            font-weight: bold;
        }
        .step-text p {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 0;
        }
        .workflow-arrow {
            font-size: 24px;
            color: #6c757d;
        }
        
        @media (max-width: 992px) {
            .workflow-diagram {
                flex-direction: column;
            }
            .workflow-step {
                margin-bottom: 20px;
                width: 100%;
                flex-direction: row;
                text-align: left;
            }
            .step-icon {
                margin-right: 15px;
                margin-bottom: 0;
            }
            .workflow-arrow {
                transform: rotate(90deg);
                margin: 10px 0;
            }
        }
    </style>

<?php } elseif (getUserRole() === 'redaksi') {
    // Statistik task
    $stats = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM tasks WHERE created_by = ? OR assigned_to = ?) as total_tasks,
            (SELECT COUNT(*) FROM tasks WHERE (created_by = ? OR assigned_to = ?) AND status = 'completed') as completed_tasks,
            (SELECT COUNT(*) FROM tasks WHERE (created_by = ? OR assigned_to = ?) AND status = 'uploaded') as uploaded_tasks,
            (SELECT COUNT(*) FROM revisions r JOIN tasks t ON r.task_id = t.id WHERE t.created_by = ? OR t.assigned_to = ?) as total_revisions
        FROM dual
    ");
    $stats->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
    $stats = $stats->fetch();

    // Dapatkan task yang akan datang
    $upcomingTasks = $pdo->prepare("
        SELECT t.id, t.title, t.deadline, t.priority, t.status as task_status, 
               a.name as account_name, t.created_by, u.name as created_by_name
        FROM tasks t
        JOIN accounts a ON t.account_id = a.id
        JOIN users u ON t.created_by = u.id
        WHERE t.assigned_to = ? AND t.status IN ('waiting_confirmation', 'in_production', 'revision')
        ORDER BY t.deadline ASC
        LIMIT 5
    ");
    $upcomingTasks->execute([$userId]);
    $upcomingTasks = $upcomingTasks->fetchAll();

    // Task terbaru
    $recentTasks = $pdo->prepare("
        SELECT t.*, a.name as account_name 
        FROM tasks t
        JOIN accounts a ON t.account_id = a.id
        WHERE t.created_by = ? OR t.assigned_to = ?
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $recentTasks->execute([$userId, $userId]);

    // Deadline mendatang
    $upcomingDeadlines = $pdo->prepare("
        SELECT t.*, a.name as account_name 
        FROM tasks t
        JOIN accounts a ON t.account_id = a.id
        WHERE (t.created_by = ? OR t.assigned_to = ?) AND t.deadline > NOW() AND t.status NOT IN ('completed', 'cancelled')
        ORDER BY t.deadline ASC
        LIMIT 5
    ");
    $upcomingDeadlines->execute([$userId, $userId]);

    $pageTitle = "Dashboard Redaksi";
    include '../includes/header.php';
    ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard Redaksi</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="create_task.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i> Buat Task Produksi
                </a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Task</h5>
                        <h2><?= $stats['total_tasks'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Uploaded</h5>
                        <h2><?= $stats['uploaded_tasks'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Selesai</h5>
                        <h2><?= $stats['completed_tasks'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h5 class="card-title">Revisi</h5>
                        <h2><?= $stats['total_revisions'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Task Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php while ($task = $recentTasks->fetch()): ?>
                            <a href="view_task.php?id=<?= $task['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h6>
                                    <small><?= date('d M', strtotime($task['created_at'])) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($task['account_name']) ?></p>
                                <small>Status: <?= getStatusBadge($task['status']) ?></small>
                            </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Task yang Akan Datang</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingTasks)): ?>
                            <p class="text-muted">Tidak ada task yang akan datang</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Judul</th>
                                            <th>Deadline</th>
                                            <th>Status</th>
                                            <th>Task Dari</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingTasks as $task): ?>
                                            <tr>
                                                <td>
                                                    <a href="view_task.php?id=<?php echo $task['id']; ?>">
                                                        <?php echo htmlspecialchars($task['title']); ?>
                                                    </a>
                                                    <small class="d-block text-muted"><?php echo htmlspecialchars($task['account_name']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo date('d M Y', strtotime($task['deadline'])); ?>
                                                    <small class="d-block text-muted"><?php echo date('H:i', strtotime($task['deadline'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusColor($task['task_status']); ?>">
                                                        <?php echo getStatusLabel($task['task_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo getUserProfilePhotoWithName($task['created_by'], $task['created_by_name']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="tasks.php" class="btn btn-sm btn-primary">Lihat Semua Task</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php } include '../includes/footer.php'; ?>
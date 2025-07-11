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
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil detail task
$stmt = $pdo->prepare("
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
    WHERE t.id = ? AND t.status = 'waiting_redaktur_confirmation'
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dalam status menunggu penugasan";
    header("Location: dashboard.php");
    exit();
}

// Ambil daftar tim redaksi
$redaksiTeam = $pdo->prepare("
    SELECT id, name, email, profile_photo
    FROM users
    WHERE role = 'redaksi'
    ORDER BY name ASC
");
$redaksiTeam->execute();
$redaksiMembers = $redaksiTeam->fetchAll();

// Proses form penugasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $assignedTo = $_POST['assigned_to'] ?? 0;
    
    if (!$assignedTo || !is_numeric($assignedTo)) {
        $_SESSION['error'] = "Pilih anggota tim redaksi yang valid";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update assigned_to dan ubah status ke waiting_confirmation
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET assigned_to = ?, status = 'waiting_confirmation'
                WHERE id = ?
            ");
            $stmt->execute([$assignedTo, $taskId]);
            
            // Log perubahan status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'waiting_confirmation', ?, 'Task ditugaskan ke tim redaksi oleh Redaktur Pelaksana')
            ");
            $stmt->execute([$taskId, $userId]);
            
            // Kirim notifikasi ke anggota tim redaksi
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $assignedTo, 
                "Anda mendapat tugas publikasi baru: " . $task['title'], 
                "../redaksi/view_task.php?id=" . $taskId
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Task berhasil ditugaskan ke tim redaksi";
            header("Location: dashboard.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$pageTitle = "Tugaskan Task";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Tugaskan Task ke Tim Redaksi</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <?php include '../includes/alerts.php'; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Detail Task</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Judul:</div>
                        <div class="col-md-9"><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Deskripsi:</div>
                        <div class="col-md-9"><?= nl2br(htmlspecialchars($task['description'])) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Kategori:</div>
                        <div class="col-md-9"><?= htmlspecialchars($task['category_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Tipe Konten:</div>
                        <div class="col-md-9"><?= htmlspecialchars($task['content_type_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Akun:</div>
                        <div class="col-md-9"><?= htmlspecialchars($task['account_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Deadline:</div>
                        <div class="col-md-9"><?= date('d M Y H:i', strtotime($task['deadline'])) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Prioritas:</div>
                        <div class="col-md-9">
                            <span class="badge bg-<?= getPriorityColor($task['priority']) ?>">
                                <?= getPriorityLabel($task['priority']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Dibuat oleh:</div>
                        <div class="col-md-9"><?= htmlspecialchars($task['creator_name']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pilih Tim Redaksi</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Pilih Anggota Tim:</label>
                            <select class="form-select" id="assigned_to" name="assigned_to" required>
                                <option value="">-- Pilih Anggota Tim --</option>
                                <?php foreach ($redaksiMembers as $member): ?>
                                <option value="<?= $member['id'] ?>">
                                    <?= htmlspecialchars($member['name']) ?> (<?= htmlspecialchars($member['email']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="assign_task" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus"></i> Tugaskan Task
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
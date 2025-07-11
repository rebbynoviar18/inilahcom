<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'production_team') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID task tidak valid";
    header("Location: tasks.php");
    exit();
}

$taskId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Verifikasi task
$stmt = $pdo->prepare("
    SELECT t.* FROM tasks t
    WHERE t.id = ? AND t.assigned_to = ? AND t.status = 'waiting_confirmation'
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dapat ditolak";
    header("Location: tasks.php");
    exit();
}

// Perbaiki bagian proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($reason)) {
        $_SESSION['error'] = "Alasan penolakan harus diisi";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update status task dan simpan alasan penolakan di kolom rejection_reason
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'rejected', rejection_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $taskId]);
            
            // Catat log status dengan alasan penolakan
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes)
                VALUES (?, 'rejected', ?, ?)
            ");
            $stmt->execute([$taskId, $userId, $reason]);
            
            // Coba simpan juga di tabel task_rejections jika ada
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO task_rejections (task_id, rejected_by, reason)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$taskId, $userId, $reason]);
            } catch (PDOException $e) {
                // Abaikan error jika tabel tidak ada
                if ($e->getCode() != '42S02') {
                    throw $e;
                }
            }
            
            // Kirim notifikasi ke pembuat task
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $task['created_by'],
                "Task Anda ditolak. Alasan: " . substr($reason, 0, 50) . (strlen($reason) > 50 ? '...' : ''),
                "../content/view_task.php?id=" . $taskId
            ]);
            
            $pdo->commit();
            $_SESSION['success'] = "Task berhasil ditolak";
            header("Location: tasks.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$pageTitle = "Tolak Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Tolak Task</h4>
                    <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error'] ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="reason" class="form-label">Alasan Penolakan</label>
                            <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
                            <small class="text-muted">Jelaskan alasan mengapa Anda menolak task ini</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Tolak Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
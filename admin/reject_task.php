<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID task tidak valid";
    header("Location: tasks.php");
    exit();
}

$taskId = $_GET['id'];

// Verifikasi task
$stmt = $pdo->prepare("SELECT id, created_by, assigned_to FROM tasks WHERE id = ? AND status = 'uploaded'");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dapat ditolak";
    header("Location: tasks.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $revisionNote = trim($_POST['revision_note']);
    
    if (empty($revisionNote)) {
        $_SESSION['error'] = "Catatan revisi tidak boleh kosong";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update status task
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'revision' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat perubahan status
            $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by) VALUES (?, 'revision', ?)");
            $stmt->execute([$taskId, $userId]);
            
            // Simpan catatan revisi
            $stmt = $pdo->prepare("INSERT INTO task_revisions (task_id, note, revised_by) VALUES (?, ?, ?)");
            $stmt->execute([$taskId, $revisionNote, $userId]);
            
            // Kirim notifikasi ke content team dan production team
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                SELECT ?, 'Task memerlukan revisi dari Creative Director', ?
                UNION
                SELECT ?, 'Task memerlukan revisi dari Creative Director', ?
            ");
            $stmt->execute([$task['created_by'], "view_task.php?id=$taskId", $task['assigned_to'], "view_task.php?id=$taskId"]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Task berhasil dikembalikan untuk revisi";
            header("Location: view_task.php?id=$taskId");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Gagal menolak task: " . $e->getMessage();
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
                    <h4>Tolak Task & Minta Revisi</h4>
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
                            <label for="revision_note" class="form-label">Catatan Revisi</label>
                            <textarea class="form-control" id="revision_note" name="revision_note" rows="5" required></textarea>
                            <small class="text-muted">Jelaskan secara detail apa yang perlu direvisi</small>
                        </div>
                        <button type="submit" class="btn btn-danger">Tolak & Minta Revisi</button>
                        <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
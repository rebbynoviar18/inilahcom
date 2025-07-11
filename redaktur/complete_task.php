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
           u_creator.name as creator_name,
           u_assignee.name as assignee_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN users u_creator ON t.created_by = u_creator.id
    LEFT JOIN users u_assignee ON t.assigned_to = u_assignee.id
    WHERE t.id = ? AND t.status = 'uploaded'
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dalam status sudah diupload";
    header("Location: dashboard.php");
    exit();
}

// Ambil link hasil publikasi
$stmt = $pdo->prepare("
    SELECT link, notes
    FROM task_submissions
    WHERE task_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$taskId]);
$submission = $stmt->fetch();

// Proses form penyelesaian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    $notes = $_POST['notes'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        // Update status task menjadi completed
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        
        // Log perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
            VALUES (?, 'completed', ?, ?)
        ");
        $stmt->execute([$taskId, $userId, 'Task dikonfirmasi selesai oleh Redaktur Pelaksana: ' . $notes]);
        
        // Berikan poin kepada user yang menyelesaikan task
        if ($task['assigned_to']) {
            calculateAndSavePoints($taskId, $task['assigned_to']);
        }
        
        // Kirim notifikasi ke tim redaksi yang mengerjakan
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $task['assigned_to'], 
            "Task publikasi Anda telah diverifikasi dan diselesaikan", 
            "../redaksi/view_task.php?id=" . $taskId
        ]);
        
        // Kirim notifikasi ke marketing yang membuat task
        if ($task['created_by']) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $task['created_by'], 
                "Task publikasi yang Anda buat telah selesai", 
                "../marketing/view_task.php?id=" . $taskId
            ]);
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "Task berhasil diselesaikan";
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Proses form revisi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_revision'])) {
    $revisionNotes = $_POST['revision_notes'] ?? '';
    
    if (empty($revisionNotes)) {
        $_SESSION['error'] = "Catatan revisi tidak boleh kosong";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update status task menjadi revision
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET status = 'revision', rejection_reason = ?
                WHERE id = ?
            ");
            $stmt->execute([$revisionNotes, $taskId]);
            
            // Log perubahan status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'revision', ?, ?)
            ");
            $stmt->execute([$taskId, $userId, 'Revisi diminta oleh Redaktur Pelaksana: ' . $revisionNotes]);
            
            // Kirim notifikasi ke tim redaksi
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $task['assigned_to'], 
                "Task publikasi Anda memerlukan revisi", 
                "../redaksi/view_task.php?id=" . $taskId
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Permintaan revisi berhasil dikirim";
            header("Location: dashboard.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$pageTitle = "Verifikasi Task";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Verifikasi Task Publikasi</h1>
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
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Dikerjakan oleh:</div>
                        <div class="col-md-9"><?= htmlspecialchars($task['assignee_name']) ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($submission): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Hasil Publikasi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Link Publikasi:</div>
                        <div class="col-md-9">
                            <a href="<?= htmlspecialchars($submission['link']) ?>" target="_blank">
                                <?= htmlspecialchars($submission['link']) ?>
                                <i class="fas fa-external-link-alt ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <?php if (!empty($submission['notes'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Catatan:</div>
                        <div class="col-md-9"><?= nl2br(htmlspecialchars($submission['notes'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Verifikasi Task</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan Penyelesaian (opsional):</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        <button type="submit" name="complete_task" class="btn btn-success w-100 mb-3">
                            <i class="fas fa-check-circle"></i> Konfirmasi Selesai
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Minta Revisi</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="revision_notes" class="form-label">Catatan Revisi:</label>
                            <textarea class="form-control" id="revision_notes" name="revision_notes" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="request_revision" class="btn btn-warning w-100">
                            <i class="fas fa-edit"></i> Minta Revisi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
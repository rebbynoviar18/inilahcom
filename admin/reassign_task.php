<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

// Periksa login dan role
if (getUserRole() !== 'creative_director') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Ambil ID task dari parameter URL
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$taskId) {
    $_SESSION['error'] = "ID task tidak valid";
    header('Location: tasks.php');
    exit();
}

// Ambil detail task
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name,
           ct.name as content_type_name,
           cp.name as content_pillar_name,
           a.name as account_name,
           creator.name as created_by_name,
           assignee.name as assigned_to_name
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN content_types ct ON t.content_type_id = ct.id
    JOIN content_pillars cp ON t.content_pillar_id = cp.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE t.id = ? AND t.status = 'rejected'
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dalam status ditolak";
    header('Location: tasks.php');
    exit();
}

// Ambil alasan penolakan
$stmt = $pdo->prepare("
    SELECT tsl.notes as rejection_reason, tsl.timestamp as rejection_date, u.name as rejected_by_name
    FROM task_status_logs tsl
    JOIN users u ON tsl.updated_by = u.id
    WHERE tsl.task_id = ? AND tsl.status = 'rejected'
    ORDER BY tsl.timestamp DESC
    LIMIT 1
");
$stmt->execute([$taskId]);
$rejectionData = $stmt->fetch();

// Ambil daftar tim produksi untuk reassign
$productionTeamMembers = $pdo->query("
    SELECT id, name, profile_photo 
    FROM users 
    WHERE role = 'production_team' AND active = 1 AND id != {$task['assigned_to']}
    ORDER BY name
")->fetchAll();

// Proses reassign task jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_task'])) {
    $newAssigneeId = $_POST['new_assignee'] ?? 0;
    
    if (!$newAssigneeId || !is_numeric($newAssigneeId)) {
        $_SESSION['error'] = "Pilih tim produksi yang valid";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update assigned_to dan reset status ke waiting_confirmation
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET assigned_to = ?, status = 'waiting_confirmation', rejection_reason = NULL
                WHERE id = ?
            ");
            $stmt->execute([$newAssigneeId, $taskId]);
            
            // Log perubahan status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'waiting_confirmation', ?, 'Task dialihkan ke tim produksi lain oleh Creative Director')
            ");
            $stmt->execute([$taskId, $userId]);
            
            // Kirim notifikasi ke assignee baru
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $newAssigneeId, 
                "Anda mendapat tugas baru: " . $task['title'], 
                "../production/view_task.php?id=" . $taskId
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Task berhasil dialihkan ke tim produksi lain";
            header("Location: view_task.php?id=" . $taskId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$pageTitle = "Reassign Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Reassign Task yang Ditolak</h4>
                    <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning mb-4">
                        <h5><i class="fas fa-exclamation-triangle"></i> Task Ini Ditolak</h5>
                        <p><strong>Alasan Penolakan:</strong> <?= nl2br(htmlspecialchars($rejectionData['rejection_reason'] ?? '')) ?></p>
                        <p class="mb-0"><small>Ditolak oleh <?= htmlspecialchars($rejectionData['rejected_by_name'] ?? '') ?> pada <?= date('d M Y H:i', strtotime($rejectionData['rejection_date'] ?? 'now')) ?></small></p>
                    </div>
                    
                    <h5 class="mb-3">Detail Task</h5>
                    <table class="table table-bordered mb-4">
                        <tr>
                            <th width="30%">Judul</th>
                            <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                        </tr>
                        <tr>
                            <th>Kategori</th>
                            <td><?= htmlspecialchars($task['category_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Akun Media</th>
                            <td><?= htmlspecialchars($task['account_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Deadline</th>
                            <td><?= date('d M Y - H:i', strtotime($task['deadline'])) ?> WIB</td>
                        </tr>
                        <tr>
                            <th>Ditolak Oleh</th>
                            <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                        </tr>
                    </table>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label for="new_assignee" class="form-label">Pilih Tim Produksi Lain</label>
                            <select class="form-select" id="new_assignee" name="new_assignee" required>
                                <option value="">-- Pilih Tim Produksi --</option>
                                <?php foreach ($productionTeamMembers as $member): ?>
                                    <option value="<?= $member['id'] ?>">
                                        <?= htmlspecialchars($member['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Pilih anggota tim produksi lain untuk mengerjakan task ini</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="reassign_task" class="btn btn-primary">
                                <i class="fas fa-user-edit"></i> Reassign Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
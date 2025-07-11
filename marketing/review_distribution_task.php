<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'marketing_team') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header('Location: ../index.php');
    exit();
}

$taskId = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];

// Dapatkan detail task
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name,
           ct.name as content_type_name,
           cp.name as content_pillar_name,
           a.name as account_name,
           u1.name as created_by_name,
           u2.name as assigned_to_name
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN content_types ct ON t.content_type_id = ct.id
    JOIN content_pillars cp ON t.content_pillar_id = cp.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users u1 ON t.created_by = u1.id
    JOIN users u2 ON t.assigned_to = u2.id
    WHERE t.id = ? AND t.created_by = ? AND t.status = 'ready_for_review' AND c.name = 'Distribusi'
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau bukan task distribusi yang siap direview";
    header('Location: tasks.php');
    exit();
}

// Ambil link distribusi
$stmt = $pdo->prepare("
    SELECT tl.*, p.name as platform_name
    FROM task_links tl
    JOIN platforms p ON tl.platform_id = p.id
    WHERE tl.task_id = ?
");
$stmt->execute([$taskId]);
$links = $stmt->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['approve'])) {
            // Update status task menjadi uploaded
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'uploaded' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat perubahan status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'uploaded', ?, 'Task distribusi disetujui')
            ");
            $stmt->execute([$taskId, $userId]);
            
            // Kirim notifikasi ke creative director
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                1, // Creative Director ID
                "Task distribusi menunggu verifikasi final",
                "view_task.php?id=$taskId"
            ]);
            
            $message = "Task distribusi berhasil disetujui";
        } else if (isset($_POST['revision'])) {
            $revisionNote = trim($_POST['revision_note'] ?? '');
            
            if (empty($revisionNote)) {
                throw new Exception("Catatan revisi harus diisi");
            }
            
            // Update status task menjadi revision
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'revision' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat perubahan status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'revision', ?, ?)
            ");
            $stmt->execute([$taskId, $userId, "Meminta revisi: $revisionNote"]);
            
            // Simpan catatan revisi
            $stmt = $pdo->prepare("
                INSERT INTO task_revisions (task_id, revised_by, note)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$taskId, $userId, $revisionNote]);
            
            // Kirim notifikasi ke content team
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $task['assigned_to'],
                "Task distribusi memerlukan revisi",
                "view_task.php?id=$taskId"
            ]);
            
            $message = "Permintaan revisi berhasil dikirim";
        } else {
            throw new Exception("Aksi tidak valid");
        }
        
        $pdo->commit();
        $_SESSION['success'] = $message;
        header('Location: tasks.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

$pageTitle = "Review Task Distribusi";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Review Task Distribusi</h4>
                    <span class="badge bg-info">Menunggu Review</span>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mb-3">Detail Task</h5>
                    <table class="table table-bordered mb-4">
                        <tr>
                            <th width="30%">Judul</th>
                            <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td><?= nl2br(htmlspecialchars($task['description'])) ?></td>
                        </tr>
                        <tr>
                            <th>Jenis Konten</th>
                            <td><?= htmlspecialchars($task['content_type_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Pilar Konten</th>
                            <td><?= htmlspecialchars($task['content_pillar_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Akun Media</th>
                            <td><?= htmlspecialchars($task['account_name']) ?></td>
                        </tr>
<?php if (!empty($task['client_name'])): ?>
<tr>
    <th>Nama Klien</th>
    <td><?= htmlspecialchars($task['client_name']) ?></td>
</tr>
<?php endif; ?>
                        <tr>
                            <th>Dikerjakan Oleh</th>
                            <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                        </tr>
                    </table>
                    
                    <h5 class="mb-3">Link Distribusi</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Platform</th>
                                    <th>Link</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $link): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= htmlspecialchars($link['platform_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= htmlspecialchars($link['link']) ?>" target="_blank">
                                                <?= htmlspecialchars($link['link']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="<?= htmlspecialchars($link['link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i> Buka
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Aksi</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <button type="submit" name="approve" class="btn btn-success btn-block mb-3">
                            <i class="fas fa-check-circle"></i> Setujui Distribusi
                        </button>
                        
                        <button type="button" class="btn btn-warning btn-block" data-bs-toggle="modal" data-bs-target="#revisionModal">
                            <i class="fas fa-edit"></i> Minta Revisi
                        </button>
                        
                        <hr>
                        
                        <a href="tasks.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Revisi -->
<div class="modal fade" id="revisionModal" tabindex="-1" aria-labelledby="revisionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="revisionModalLabel">Permintaan Revisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="revision_note" class="form-label">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="revision_note" name="revision_note" rows="5" required
                                  placeholder="Jelaskan secara detail apa yang perlu direvisi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="revision" class="btn btn-warning">Kirim Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation for revision modal
    const revisionForm = document.querySelector('#revisionModal form');
    revisionForm.addEventListener('submit', function(e) {
        const revisionNote = document.getElementById('revision_note').value.trim();
        if (!revisionNote) {
            e.preventDefault();
            alert('Catatan revisi harus diisi');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
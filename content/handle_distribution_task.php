<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
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
    WHERE t.id = ? AND t.assigned_to = ? AND t.status = 'waiting_confirmation' AND c.name = 'Distribusi'
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau bukan task distribusi";
    header('Location: tasks.php');
    exit();
}

// Ambil platform media sosial
$platforms = $pdo->query("SELECT * FROM platforms ORDER BY name")->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Validasi input
        if (empty($_POST['platforms']) || !is_array($_POST['platforms'])) {
            throw new Exception("Pilih minimal satu platform");
        }
        
        // Update status task
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_production' WHERE id = ?");
        $stmt->execute([$taskId]);
        
        // Catat perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
            VALUES (?, 'in_production', ?, 'Task distribusi diterima')
        ");
        $stmt->execute([$taskId, $userId]);
        
        // Simpan platform yang dipilih
        $selectedPlatforms = $_POST['platforms'];
        foreach ($selectedPlatforms as $platformId) {
            $stmt = $pdo->prepare("
                INSERT INTO task_platforms (task_id, platform_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$taskId, $platformId]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Task distribusi berhasil diterima";
        header('Location: tasks.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

$pageTitle = "Posting Task Distribusi";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Task Distribusi Konten</h4>
                    <a href="tasks.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Anda ditugaskan untuk mendistribusikan konten ini ke platform media sosial.
                    </div>
                    
                    <h5 class="mb-3">Detail Konten</h5>
                    <table class="table table-bordered">
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
                        <tr>
                            <th>Deadline</th>
                            <td><?= date('d M Y - H:i', strtotime($task['deadline'])) ?></td>
                        </tr>
                    </table>
                    
                    <hr>
                    
                    <h5 class="mb-3">Platform Distribusi</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Pilih Platform yang Akan Digunakan <span class="text-danger">*</span></label>
                            <div class="row">
                                <?php foreach ($platforms as $platform): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="platforms[]" 
                                                   value="<?= $platform['id'] ?>" id="platform<?= $platform['id'] ?>">
                                            <label class="form-check-label" for="platform<?= $platform['id'] ?>">
                                                <?= htmlspecialchars($platform['name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Terima Task
                            </button>
                            <a href="reject_task.php?id=<?= $task['id'] ?>" class="btn btn-danger">
                                <i class="fas fa-times-circle"></i> Tolak Task
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('input[name="platforms[]"]:checked');
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Pilih minimal satu platform untuk distribusi');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
    header("Location: ../index.php");
    exit();
}

$taskId = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];

// Ubah query untuk mengambil task
$stmt = $pdo->prepare("
    SELECT t.* 
    FROM tasks t
    WHERE t.id = ? AND t.created_by = ? AND t.status NOT IN ('completed', 'uploaded')
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

// Validasi task ditemukan
if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dapat diedit";
    header("Location: tasks.php");
    exit();
}

// Dapatkan data untuk dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$contentTypes = $pdo->query("SELECT * FROM content_types ORDER BY name")->fetchAll();
$productionTeam = $pdo->query("SELECT * FROM users WHERE role = 'production_team' ORDER BY name")->fetchAll();
$accounts = $pdo->query("SELECT * FROM accounts ORDER BY name")->fetchAll();

// Dapatkan content pillars untuk kategori yang dipilih
$stmt = $pdo->prepare("SELECT * FROM content_pillars WHERE category_id = ? ORDER BY name");
$stmt->execute([$task['category_id']]);
$contentPillars = $stmt->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $categoryId = $_POST['category_id'];
    $contentTypeId = $_POST['content_type_id'];
    $contentPillarId = $_POST['content_pillar_id'];
    $accountId = $_POST['account_id'];
    $assignedTo = $_POST['assigned_to'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];
    
    // Validasi input
    if (empty($title) || empty($categoryId) || empty($contentTypeId) || 
        empty($contentPillarId) || empty($accountId) || empty($assignedTo) || empty($priority) || empty($deadline)) {
        $_SESSION['error'] = "Semua field harus diisi";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update query untuk memperbarui task
$stmt = $pdo->prepare("
    UPDATE tasks 
    SET title = ?, description = ?, category_id = ?, content_type_id = ?, 
        content_pillar_id = ?, account_id = ?, assigned_to = ?, priority = ?, deadline = ?
    WHERE id = ? AND created_by = ? AND status NOT IN ('completed', 'uploaded')
");
$stmt->execute([
    $title, $description, $categoryId, $contentTypeId, $contentPillarId, 
    $accountId, $assignedTo, $priority, $deadline, $taskId, $userId
]);
            
            // Jika assigned_to berubah, kirim notifikasi ke tim produksi baru
            if ($assignedTo != $task['assigned_to']) {
                $message = "Anda memiliki task baru yang menunggu konfirmasi";
                sendNotification($assignedTo, $message, "view_task.php?id=$taskId");
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = "Task berhasil diperbarui";
            header("Location: view_task.php?id=$taskId");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Gagal memperbarui task: " . $e->getMessage();
        }
    }
}

$pageTitle = "Edit Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit Task</h4>
                    <a href="view_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-secondary">
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
                            <label for="title" class="form-label">Judul Task</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($task['description']) ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $task['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="content_type_id" class="form-label">Jenis Konten</label>
                                <select class="form-control" id="content_type_id" name="content_type_id" required>
                                    <option value="">-- Pilih Jenis Konten --</option>
                                    <?php foreach ($contentTypes as $type): ?>
                                        <option value="<?= $type['id'] ?>" <?= $task['content_type_id'] == $type['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="content_pillar_id" class="form-label">Pilar Konten</label>
                                <select class="form-control" id="content_pillar_id" name="content_pillar_id" required>
                                    <option value="">-- Pilih Pilar Konten --</option>
                                    <?php foreach ($contentPillars as $pillar): ?>
                                        <option value="<?= $pillar['id'] ?>" <?= $task['content_pillar_id'] == $pillar['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pillar['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="account_id" class="form-label">Akun Media</label>
                                <select class="form-control" id="account_id" name="account_id" required>
                                    <option value="">-- Pilih Akun Media --</option>
                                    <?php foreach ($accounts as $account): ?>
                                        <option value="<?= $account['id'] ?>" <?= $task['account_id'] == $account['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($account['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assigned_to" class="form-label">Ditugaskan Kepada</label>
                                <select class="form-control" id="assigned_to" name="assigned_to" required>
                                    <option value="">-- Pilih Tim Produksi --</option>
                                    <?php foreach ($productionTeam as $member): ?>
                                        <option value="<?= $member['id'] ?>" <?= $task['assigned_to'] == $member['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($member['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Prioritas</label>
                                <select class="form-control" id="priority" name="priority" required>
                                    <option value="low" <?= $task['priority'] == 'low' ? 'selected' : '' ?>>Rendah</option>
                                    <option value="medium" <?= $task['priority'] == 'medium' ? 'selected' : '' ?>>Sedang</option>
                                    <option value="high" <?= $task['priority'] == 'high' ? 'selected' : '' ?>>Tinggi</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Deadline</label>
                            <input type="datetime-local" class="form-control" id="deadline" name="deadline" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($task['deadline'])) ?>" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Perbarui Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load content pillars based on selected category
    const categorySelect = document.getElementById('category_id');
    const contentPillarSelect = document.getElementById('content_pillar_id');
    const currentPillarId = <?= $task['content_pillar_id'] ?>;
    
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        if (!categoryId) {
            contentPillarSelect.innerHTML = '<option value="">-- Pilih Pilar Konten --</option>';
            return;
        }
        
        fetch(`../api/get_content_pillars.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">-- Pilih Pilar Konten --</option>';
                data.forEach(pillar => {
                    const selected = pillar.id == currentPillarId ? 'selected' : '';
                    options += `<option value="${pillar.id}" ${selected}>${pillar.name}</option>`;
                });
                contentPillarSelect.innerHTML = options;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });
    
    // Set minimum deadline to today
    const deadlineInput = document.getElementById('deadline');
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
    deadlineInput.min = today.toISOString().slice(0, 16);
});
</script>

<?php include '../includes/footer.php'; ?>
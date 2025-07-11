<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'redaksi') {
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
    SELECT * FROM tasks 
    WHERE id = ? AND created_by = ? AND status = 'draft'
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dapat diedit";
    header('Location: tasks.php');
    exit();
}

// Ambil data untuk dropdown
$stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name");
$stmtContentTypes = $pdo->query("SELECT * FROM content_types ORDER BY name");
$stmtContentPillars = $pdo->query("SELECT * FROM content_pillars ORDER BY name");
$stmtAccounts = $pdo->query("SELECT * FROM accounts ORDER BY name");

// Proses form edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $categoryId = (int)$_POST['category_id'];
        $contentTypeId = (int)$_POST['content_type_id'];
        $contentPillarId = (int)$_POST['content_pillar_id'];
        $accountId = (int)$_POST['account_id'];
        $priority = $_POST['priority'];
        $deadline = $_POST['deadline'];
        $taskType = $_POST['task_type'];
        
        if (empty($title) || empty($description) || empty($deadline)) {
            throw new Exception("Semua field wajib diisi");
        }
        
        // Validasi file untuk task distribusi
        $filePath = $task['file_path']; // Default ke file yang sudah ada
        
        if ($taskType === 'distribution') {
            if (!empty($_FILES['file']['name'])) {
                // Ada file baru yang diupload
                $uploadDir = '../uploads/';
                $fileName = time() . '_' . basename($_FILES['file']['name']);
                $targetFile = $uploadDir . $fileName;
                $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                
                // Validasi ukuran file (max 10MB)
                if ($_FILES['file']['size'] > 10000000) {
                    throw new Exception("Ukuran file terlalu besar (maksimal 10MB)");
                }
                
                // Validasi tipe file
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp4', 'mov', 'avi', 'doc', 'docx'];
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Tipe file tidak didukung");
                }
                
                // Upload file
                if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
                    throw new Exception("Gagal mengupload file");
                }
                
                $filePath = $fileName;
                
                // Hapus file lama jika ada
                if (!empty($task['file_path']) && file_exists($uploadDir . $task['file_path'])) {
                    unlink($uploadDir . $task['file_path']);
                }
            } elseif (empty($task['file_path'])) {
                // Tidak ada file yang diupload dan tidak ada file lama
                throw new Exception("File konten wajib diupload untuk task distribusi");
            }
        }
        
        // Update task
        $stmt = $pdo->prepare("
            UPDATE tasks SET
                title = ?,
                description = ?,
                category_id = ?,
                content_type_id = ?,
                content_pillar_id = ?,
                account_id = ?,
                priority = ?,
                deadline = ?,
                task_type = ?,
                file_path = ?
            WHERE id = ? AND created_by = ? AND status = 'draft'
        ");
        
        $stmt->execute([
            $title,
            $description,
            $categoryId,
            $contentTypeId,
            $contentPillarId,
            $accountId,
            $priority,
            $deadline,
            $taskType,
            $filePath,
            $taskId,
            $userId
        ]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Tidak ada perubahan yang disimpan");
        }
        
        $_SESSION['success'] = "Task berhasil diperbarui";
        header("Location: view_task.php?id=$taskId");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal memperbarui task: " . $e->getMessage();
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
                    <h4>Edit Task</h4>
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
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Judul Task <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi Task <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($task['description']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipe Task <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="task_type" id="taskTypeProduction" value="production" <?= $task['task_type'] === 'production' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="taskTypeProduction">
                                    Produksi Konten
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="task_type" id="taskTypeDistribution" value="distribution" <?= $task['task_type'] === 'distribution' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="taskTypeDistribution">
                                    Distribusi Konten
                                </label>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php while ($category = $stmtCategories->fetch()): ?>
                                        <option value="<?= $category['id'] ?>" <?= $task['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="content_type_id" class="form-label">Jenis Konten <span class="text-danger">*</span></label>
                                <select class="form-select" id="content_type_id" name="content_type_id" required>
                                    <option value="">-- Pilih Jenis Konten --</option>
                                    <?php while ($contentType = $stmtContentTypes->fetch()): ?>
                                        <option value="<?= $contentType['id'] ?>" <?= $task['content_type_id'] == $contentType['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($contentType['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="content_pillar_id" class="form-label">Pilar Konten <span class="text-danger">*</span></label>
                                <select class="form-select" id="content_pillar_id" name="content_pillar_id" required>
                                    <option value="">-- Pilih Pilar Konten --</option>
                                    <?php while ($contentPillar = $stmtContentPillars->fetch()): ?>
                                        <option value="<?= $contentPillar['id'] ?>" <?= $task['content_pillar_id'] == $contentPillar['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($contentPillar['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="account_id" class="form-label">Akun Media <span class="text-danger">*</span></label>
                                <select class="form-select" id="account_id" name="account_id" required>
                                    <option value="">-- Pilih Akun Media --</option>
                                    <?php while ($account = $stmtAccounts->fetch()): ?>
                                        <option value="<?= $account['id'] ?>" <?= $task['account_id'] == $account['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($account['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Prioritas <span class="text-danger">*</span></label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low" <?= $task['priority'] === 'low' ? 'selected' : '' ?>>Rendah</option>
                                    <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>Sedang</option>
                                                                        <option value="high" <?= $task['priority'] === 'high' ? 'selected' : '' ?>>Tinggi</option>
                                    <option value="urgent" <?= $task['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="deadline" name="deadline" required value="<?= date('Y-m-d\TH:i', strtotime($task['deadline'])) ?>">
                            </div>
                        </div>
                        
                        <div id="distributionFileSection" class="mb-3" style="<?= $task['task_type'] === 'distribution' ? '' : 'display: none;' ?>">
                            <label for="file" class="form-label">File Konten</label>
                            <input type="file" class="form-control" id="file" name="file">
                            <?php if (!empty($task['file_path'])): ?>
                                <div class="mt-2">
                                    <p class="mb-1">File saat ini:</p>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file me-2"></i>
                                        <span><?= htmlspecialchars($task['file_path']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <small class="form-text text-muted">Format yang didukung: JPG, PNG, GIF, PDF, MP4, MOV, AVI, DOC, DOCX. Maksimal 10MB.</small>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const taskTypeRadios = document.querySelectorAll('input[name="task_type"]');
    const distributionFileSection = document.getElementById('distributionFileSection');
    
    taskTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'distribution') {
                distributionFileSection.style.display = 'block';
            } else {
                distributionFileSection.style.display = 'none';
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
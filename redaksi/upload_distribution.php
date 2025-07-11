<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'redaksi') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
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
        a.name as account_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN accounts a ON t.account_id = a.id
    WHERE t.id = ? AND (t.assigned_to = ? OR t.created_by = ?)
");
$stmt->execute([$taskId, $userId, $userId]);
$task = $stmt->fetch();

// Validasi task
if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau Anda tidak memiliki akses";
    header('Location: tasks.php');
    exit();
}

// Validasi status task - memperbolehkan status in_production dan revision
if (!in_array($task['status'], ['in_production', 'revision'])) {
    $_SESSION['error'] = "Task tidak dalam status yang dapat diupload link";
    header('Location: view_task.php?id=' . $taskId);
    exit();
}

// Ambil link yang sudah ada (jika ada)
$existingLink = '';
$stmt = $pdo->prepare("SELECT link FROM task_links WHERE task_id = ? AND platform = 'website' LIMIT 1");
$stmt->execute([$taskId]);
$linkData = $stmt->fetch();
if ($linkData) {
    $existingLink = $linkData['link'];
}

// Proses form upload link
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Validasi link website (wajib)
        $websiteLink = trim($_POST['website_link'] ?? '');
        if (empty($websiteLink)) {
            throw new Exception("Link berita wajib diisi");
        }
        
        // Hapus link lama jika ada
        $stmt = $pdo->prepare("DELETE FROM task_links WHERE task_id = ?");
        $stmt->execute([$taskId]);
        
        // Simpan link website
        $stmt = $pdo->prepare("INSERT INTO task_links (task_id, platform, link, added_by) VALUES (?, 'website', ?, ?)");
        $stmt->execute([$taskId, $websiteLink, $userId]);
        
        // Update status task menjadi ready_for_review
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'ready_for_review' WHERE id = ?");
        $stmt->execute([$taskId]);
        
        // Catat perubahan status
        $notes = ($task['status'] === 'revision') ? 'Link berita telah direvisi' : 'Link berita telah diupload';
        $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by, notes) VALUES (?, 'ready_for_review', ?, ?)");
        $stmt->execute([$taskId, $userId, $notes]);
        
        // Kirim notifikasi ke tim konten
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $task['created_by'],
            "Link berita telah diupload untuk task: " . $task['title'],
            "../content/view_task.php?id=" . $taskId
        ]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Link berhasil diupload dan task siap untuk direview";
        header("Location: view_task.php?id=" . $taskId);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal mengupload link: " . $e->getMessage();
    }
}

$pageTitle = "Upload Link Berita";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><?= $pageTitle ?></h4>
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
                    
                    <div class="alert alert-info">
                        <h5>Task: <?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h5>
                        <p>Akun: <?= htmlspecialchars($task['account_name']) ?></p>
                        <p>Status: <?= getStatusBadge($task['status']) ?></p>
                        <p>Setelah upload link, status akan berubah menjadi "Siap Direview" dan menunggu persetujuan.</p>
                        <p><strong>Catatan:</strong> Link berita wajib diisi.</p>
                    </div>
                    
                    <form method="POST">
                        <!-- Link Website (wajib) -->
                        <div class="mb-3">
                            <label for="website_link" class="form-label">Link Berita <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="website_link" name="website_link" 
                                   value="<?= htmlspecialchars($existingLink) ?>"
                                   placeholder="https://www.example.com/berita/..." required>
                            <div class="form-text">Masukkan URL lengkap berita yang telah dipublikasikan</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">
                                <?= $task['status'] === 'revision' ? 'Revisi & Kirim' : 'Upload & Selesaikan' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
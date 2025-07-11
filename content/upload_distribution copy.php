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

$userId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Dapatkan detail task
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name, 
           a.name as account_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN accounts a ON t.account_id = a.id
    WHERE t.id = ? AND (t.assigned_to = ? OR t.created_by = ?)
    AND t.status = 'waiting_confirmation'
");
$stmt->execute([$taskId, $userId, $userId]);
$task = $stmt->fetch();

if (!$task || $task['category_name'] !== 'Distribusi') {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dapat diakses";
    header('Location: tasks.php');
    exit();
}

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instagramLink = $_POST['instagram_link'] ?? '';
    $mirrorPlatforms = $_POST['mirror_platforms'] ?? [];
    
    // Validasi link Instagram (wajib)
    if (empty($instagramLink) || !filter_var($instagramLink, FILTER_VALIDATE_URL)) {
        $_SESSION['error'] = "Link Instagram wajib diisi dengan format URL yang valid";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update status task menjadi uploaded
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'uploaded' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Log perubahan status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'uploaded', ?, 'Link distribusi telah diupload')
            ");
            $stmt->execute([$taskId, $userId]);
            
            // Simpan link Instagram (wajib)
            $stmt = $pdo->prepare("
                INSERT INTO task_links (task_id, platform, link) 
                VALUES (?, 'instagram', ?)
            ");
            $stmt->execute([$taskId, $instagramLink]);
            
            // Simpan link platform lain jika ada
            $platformFields = [
                'tiktok' => 'tiktok_link',
                'facebook' => 'facebook_link',
                'twitter' => 'twitter_link',
                'threads' => 'threads_link'
            ];
            
            foreach ($mirrorPlatforms as $platform) {
                if (isset($platformFields[$platform]) && !empty($_POST[$platformFields[$platform]])) {
                    $link = $_POST[$platformFields[$platform]];
                    if (filter_var($link, FILTER_VALIDATE_URL)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO task_links (task_id, platform, link) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$taskId, $platform, $link]);
                    }
                }
            }
            
            // Kirim notifikasi ke Creative Director
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'creative_director' LIMIT 1");
            $stmt->execute();
            $creativeDirectorId = $stmt->fetchColumn();
            
            if ($creativeDirectorId) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, message, link)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $creativeDirectorId, 
                    "Task distribusi telah diupload dan menunggu verifikasi: " . $task['title'], 
                    "../director/view_task.php?id=" . $taskId
                ]);
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = "Link distribusi berhasil diupload dan status task diperbarui";
            header("Location: view_task.php?id=" . $taskId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$pageTitle = "Upload Link Distribusi";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Upload Link Distribusi</h4>
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
                        <p>Setelah upload link, status akan berubah menjadi "Telah Upload" dan menunggu verifikasi dari Creative Director.</p>
                    </div>
                    
                    <form method="POST">
                        <!-- Link Instagram (wajib) -->
                        <div class="mb-3">
                            <label for="instagram_link" class="form-label">Link Postingan Instagram <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="instagram_link" name="instagram_link" 
                                   placeholder="https://www.instagram.com/p/..." required>
                            <div class="form-text">Link postingan Instagram wajib diisi</div>
                        </div>
                        
                        <!-- Opsi mirroring ke platform lain -->
                        <div class="mb-3">
                            <label class="form-label">Mirroring ke Platform Lain</label>
                            <div class="card">
                                <div class="card-body">
                                    <!-- TikTok -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_tiktok" name="mirror_platforms[]" value="tiktok">
                                        <label class="form-check-label" for="mirror_tiktok">TikTok</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="tiktok_link_container" style="display: none;">
                                        <input type="url" class="form-control" id="tiktok_link" name="tiktok_link" 
                                               placeholder="https://www.tiktok.com/@username/video/...">
                                    </div>
                                    
                                    <!-- Facebook -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_facebook" name="mirror_platforms[]" value="facebook">
                                        <label class="form-check-label" for="mirror_facebook">Facebook</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="facebook_link_container" style="display: none;">
                                        <input type="url" class="form-control" id="facebook_link" name="facebook_link" 
                                               placeholder="https://www.facebook.com/...">
                                    </div>
                                    
                                    <!-- Twitter (X) -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_twitter" name="mirror_platforms[]" value="twitter">
                                        <label class="form-check-label" for="mirror_twitter">Twitter (X)</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="twitter_link_container" style="display: none;">
                                        <input type="url" class="form-control" id="twitter_link" name="twitter_link" 
                                               placeholder="https://twitter.com/...">
                                    </div>
                                    
                                    <!-- Threads -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_threads" name="mirror_platforms[]" value="threads">
                                        <label class="form-check-label" for="mirror_threads">Threads</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="threads_link_container" style="display: none;">
                                        <input type="url" class="form-control" id="threads_link" name="threads_link" 
                                               placeholder="https://www.threads.net/...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Upload & Selesaikan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script untuk menampilkan/menyembunyikan input link berdasarkan checkbox
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="mirror_platforms[]"]');
    
    checkboxes.forEach(function(checkbox) {
        const platform = checkbox.value;
        const container = document.getElementById(platform + '_link_container');
        
        // Set initial state
        container.style.display = checkbox.checked ? 'block' : 'none';
        
        // Add event listener
        checkbox.addEventListener('change', function() {
            container.style.display = this.checked ? 'block' : 'none';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
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
    AND (t.status = 'waiting_confirmation' OR t.status = 'revision')
");
$stmt->execute([$taskId, $userId, $userId]);
$task = $stmt->fetch();

if (!$task || $task['category_name'] !== 'Distribusi') {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dapat diakses";
    header('Location: tasks.php');
    exit();
}

// Ambil link yang sudah ada jika ini adalah revisi
$existingLinks = [];
if ($task['status'] === 'revision') {
    $stmt = $pdo->prepare("SELECT platform, link FROM task_links WHERE task_id = ?");
    $stmt->execute([$taskId]);
    while ($row = $stmt->fetch()) {
        $existingLinks[$row['platform']] = $row['link'];
    }
    
    // Ambil catatan revisi terbaru
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as revised_by_name 
        FROM revisions r 
        JOIN users u ON r.revised_by = u.id 
        WHERE r.task_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$taskId]);
    $revisionData = $stmt->fetch();
}

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mirrorPlatforms = $_POST['mirror_platforms'] ?? [];
    
    // Validasi minimal satu platform harus dipilih
    if (empty($mirrorPlatforms)) {
        $_SESSION['error'] = "Minimal satu platform harus dipilih";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Hapus link lama jika ini adalah revisi
            if ($task['status'] === 'revision') {
                $stmt = $pdo->prepare("DELETE FROM task_links WHERE task_id = ?");
                $stmt->execute([$taskId]);
            }
            
            // Update status task menjadi uploaded
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'uploaded' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Log perubahan status
            $notes = ($task['status'] === 'revision') ? 'Link distribusi telah direvisi' : 'Link distribusi telah diupload';
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'uploaded', ?, ?)
            ");
            $stmt->execute([$taskId, $userId, $notes]);
            
            // Simpan link untuk semua platform yang dipilih
            $platformFields = [
                'instagram' => 'instagram_link',
                'tiktok' => 'tiktok_link',
                'facebook' => 'facebook_link',
                'twitter' => 'twitter_link',
                'threads' => 'threads_link'
            ];
            
            $platformCount = 0; // Hitung jumlah platform yang berhasil disimpan
            
            foreach ($mirrorPlatforms as $platform) {
                if (isset($platformFields[$platform]) && !empty($_POST[$platformFields[$platform]])) {
                    $link = $_POST[$platformFields[$platform]];
                    if (filter_var($link, FILTER_VALIDATE_URL)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO task_links (task_id, platform, link, added_by) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$taskId, $platform, $link, $userId]);
                        $platformCount++; // Tambah hitungan platform
                    }
                }
            }
            
            // Jika ada lebih dari 1 platform, kurangi 1 untuk menghilangkan platform pertama dari perhitungan
            // Jika hanya ada 1 platform, simpan sebagai 0 (tidak ada bonus)
            $adjustedPlatformCount = $platformCount > 1 ? $platformCount - 1 : 0;
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET platform_count = ? 
                WHERE id = ?
            ");
            $stmt->execute([$adjustedPlatformCount, $taskId]);

            // Tambahkan log untuk debugging
            error_log("Task ID: $taskId - Platform Count: $platformCount - Adjusted Count: $adjustedPlatformCount");
            
            // Kirim notifikasi ke Creative Director
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'creative_director' LIMIT 1");
            $stmt->execute();
            $creativeDirectorId = $stmt->fetchColumn();
            
            if ($creativeDirectorId) {
                $message = ($task['status'] === 'revision') 
                    ? "Link distribusi telah direvisi dan menunggu verifikasi: " . $task['title']
                    : "Task distribusi telah diupload dan menunggu verifikasi: " . $task['title'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, message, link)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $creativeDirectorId, 
                    $message, 
                    "../director/view_task.php?id=" . $taskId
                ]);
            }
            
            $pdo->commit();
            
            $successMessage = ($task['status'] === 'revision') 
                ? "Link distribusi berhasil direvisi dan status task diperbarui"
                : "Link distribusi berhasil diupload dan status task diperbarui";
            
            $_SESSION['success'] = $successMessage;
            header("Location: view_task.php?id=" . $taskId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$pageTitle = ($task['status'] === 'revision') ? "Revisi Link Distribusi" : "Upload Link Distribusi";
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
                        <p>Setelah upload link, status akan berubah menjadi "Telah Upload" dan menunggu verifikasi dari Creative Director.</p>
                        <p><strong>Catatan:</strong> Minimal satu platform harus dipilih dan diisi link-nya.</p>
                    </div>
                    
                    <?php if ($task['status'] === 'revision' && isset($revisionData)): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Catatan Revisi:</h5>
                        <p><?= nl2br(htmlspecialchars($revisionData['note'])) ?></p>
                        <small class="text-muted">Oleh: <?= htmlspecialchars($revisionData['revised_by_name']) ?> - <?= date('d M Y H:i', strtotime($revisionData['created_at'])) ?></small>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <!-- Opsi platform distribusi -->
                        <div class="mb-3">
                            <label class="form-label">Platform Distribusi <span class="text-danger">*</span></label>
                            <div class="card">
                                <div class="card-body">
                                    <!-- Instagram -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_instagram" name="mirror_platforms[]" value="instagram"
                                               <?= isset($existingLinks['instagram']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mirror_instagram">Instagram</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="instagram_link_container" style="display: <?= isset($existingLinks['instagram']) ? 'block' : 'none' ?>;">
                                        <input type="url" class="form-control" id="instagram_link" name="instagram_link" 
                                               value="<?= htmlspecialchars($existingLinks['instagram'] ?? '') ?>"
                                               placeholder="https://www.instagram.com/p/...">
                                    </div>
                                    
                                    <!-- TikTok -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_tiktok" name="mirror_platforms[]" value="tiktok"
                                               <?= isset($existingLinks['tiktok']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mirror_tiktok">TikTok</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="tiktok_link_container" style="display: <?= isset($existingLinks['tiktok']) ? 'block' : 'none' ?>;">
                                        <input type="url" class="form-control" id="tiktok_link" name="tiktok_link" 
                                               value="<?= htmlspecialchars($existingLinks['tiktok'] ?? '') ?>"
                                               placeholder="https://www.tiktok.com/@username/video/...">
                                    </div>
                                    
                                    <!-- Facebook -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_facebook" name="mirror_platforms[]" value="facebook"
                                               <?= isset($existingLinks['facebook']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mirror_facebook">Facebook</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="facebook_link_container" style="display: <?= isset($existingLinks['facebook']) ? 'block' : 'none' ?>;">
                                        <input type="url" class="form-control" id="facebook_link" name="facebook_link" 
                                               value="<?= htmlspecialchars($existingLinks['facebook'] ?? '') ?>"
                                               placeholder="https://www.facebook.com/...">
                                    </div>
                                    
                                    <!-- Twitter (X) -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_twitter" name="mirror_platforms[]" value="twitter"
                                               <?= isset($existingLinks['twitter']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mirror_twitter">Twitter (X)</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="twitter_link_container" style="display: <?= isset($existingLinks['twitter']) ? 'block' : 'none' ?>;">
                                        <input type="url" class="form-control" id="twitter_link" name="twitter_link" 
                                               value="<?= htmlspecialchars($existingLinks['twitter'] ?? '') ?>"
                                               placeholder="https://twitter.com/...">
                                    </div>
                                    
                                    <!-- Threads -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mirror_threads" name="mirror_platforms[]" value="threads"
                                               <?= isset($existingLinks['threads']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mirror_threads">Threads</label>
                                    </div>
                                    <div class="mb-3 ps-4 mirror-link" id="threads_link_container" style="display: <?= isset($existingLinks['threads']) ? 'block' : 'none' ?>;">
                                        <input type="url" class="form-control" id="threads_link" name="threads_link" 
                                               value="<?= htmlspecialchars($existingLinks['threads'] ?? '') ?>"
                                               placeholder="https://www.threads.com/...">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-primary">
                                    <?= ($task['status'] === 'revision') ? 'Simpan Revisi & Selesaikan' : 'Upload & Selesaikan' ?>
                                </button>
                            </div>
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
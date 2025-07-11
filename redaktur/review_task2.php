<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'redaktur_pelaksana') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header("Location: ../index.php");
    exit();
}

$taskId = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

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
    WHERE t.id = ? AND t.created_by = ?
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau Anda tidak memiliki akses";
    header("Location: dashboard.php");
    exit();
}

// Proses form approve dengan link postingan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve') {
    // Validasi link Instagram (wajib)
    if (empty($_POST['instagram_link'])) {
        $_SESSION['error'] = "Link postingan Instagram wajib diisi";
        header("Location: review_task.php?id=$taskId&action=approve");
        exit();
    }

    // Ambil data dari form
    $instagramLink = trim($_POST['instagram_link']);
    $mirrorPlatforms = $_POST['mirror_platforms'] ?? [];
    $tiktokLink = trim($_POST['tiktok_link'] ?? '');
    $facebookLink = trim($_POST['facebook_link'] ?? '');
    $twitterLink = trim($_POST['twitter_link'] ?? '');
    $threadsLink = trim($_POST['threads_link'] ?? '');

    // Validasi link untuk platform yang dipilih
    foreach ($mirrorPlatforms as $platform) {
        $link = '';
        switch ($platform) {
            case 'tiktok':
                $link = $tiktokLink;
                break;
            case 'facebook':
                $link = $facebookLink;
                break;
            case 'twitter':
                $link = $twitterLink;
                break;
            case 'threads':
                $link = $threadsLink;
                break;
        }
        if (empty($link)) {
            $_SESSION['error'] = "Link postingan untuk $platform wajib diisi";
            header("Location: review_task.php?id=$taskId&action=approve");
            exit();
        }
    }

    try {
        $pdo->beginTransaction();

        // Update status task
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'uploaded' WHERE id = ?");
        $stmt->execute([$taskId]);

        // Log perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by)
            VALUES (?, 'uploaded', ?)
        ");
        $stmt->execute([$taskId, $userId]);

        // Simpan link postingan Instagram
        $stmt = $pdo->prepare("
            INSERT INTO task_links (task_id, platform, link, added_by)
            VALUES (?, 'instagram', ?, ?)
        ");
        $stmt->execute([$taskId, $instagramLink, $userId]);

        // Simpan link postingan untuk platform yang dipilih
        foreach ($mirrorPlatforms as $platform) {
            $link = '';
            switch ($platform) {
                case 'tiktok':
                    $link = $tiktokLink;
                    break;
                case 'facebook':
                    $link = $facebookLink;
                    break;
                case 'twitter':
                    $link = $twitterLink;
                    break;
                case 'threads':
                    $link = $threadsLink;
                    break;
            }
            if (!empty($link)) {
                $stmt = $pdo->prepare("
                    INSERT INTO task_links (task_id, platform, link, added_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$taskId, $platform, $link, $userId]);
            }
        }

        // Kirim notifikasi ke production team
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $task['assigned_to'],
            "Task telah disetujui: " . $task['title'],
            "../production/view_task.php?id=" . $taskId
        ]);

        $pdo->commit();

        $_SESSION['success'] = "Task berhasil disetujui dan telah diupload";
        header("Location: view_task.php?id=$taskId");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: review_task.php?id=$taskId&action=approve");
        exit();
    }
}

// Proses revisi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'revise') {
    $revisionNote = trim($_POST['revision_note'] ?? '');

    if (empty($revisionNote)) {
        $_SESSION['error'] = "Catatan revisi tidak boleh kosong";
        header("Location: review_task.php?id=$taskId&action=revise");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Update status task
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'revision' WHERE id = ?");
        $stmt->execute([$taskId]);

        // Log perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by)
            VALUES (?, 'revision', ?)
        ");
        $stmt->execute([$taskId, $userId]);

        // Simpan catatan revisi
        $stmt = $pdo->prepare("
            INSERT INTO task_revisions (task_id, note, revised_by, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$taskId, $revisionNote, $userId]);

        // Kirim notifikasi ke production team
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $task['assigned_to'],
            "Task perlu direvisi: " . $task['title'],
            "../production/view_task.php?id=" . $taskId
        ]);

        $pdo->commit();

        $_SESSION['success'] = "Permintaan revisi berhasil dikirim";
        header("Location: view_task.php?id=$taskId");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: review_task.php?id=$taskId&action=revise");
        exit();
    }
}

$pageTitle = "Review Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Review Task: <?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h4>
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
                    
                    <div class="row">
                        <!-- Tampilkan hasil pekerjaan -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Hasil Pekerjaan</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($task['file_path'])): ?>
                                        <?php 
                                        // Perbaikan path file
                                        $filePath = '../uploads/' . $task['file_path'];
                                        $fileUrl = '../uploads/' . $task['file_path']; // URL untuk ditampilkan di browser
                                        $fileExt = pathinfo($task['file_path'], PATHINFO_EXTENSION);
                                        $isImage = in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif']);
                                        $isPdf = strtolower($fileExt) === 'pdf';
                                        $isVideo = in_array(strtolower($fileExt), ['mp4', 'mov']);
                                        ?>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= $fileUrl ?>" class="btn btn-primary" download>
                                                    <i class="fas fa-download me-2"></i> Download File
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="file-preview mt-3">
                                            <?php if ($isImage): ?>
                                                <div class="text-center">
                                                    <img src="<?= $fileUrl ?>" class="img-fluid" style="max-height: 400px;" alt="Preview">
                                                </div>
                                            <?php elseif ($isPdf): ?>
                                                <div class="ratio ratio-16x9">
                                                    <iframe src="<?= $fileUrl ?>" allowfullscreen></iframe>
                                                </div>
                                            <?php elseif ($isVideo): ?>
                                                <div class="ratio ratio-16x9">
                                                    <video controls>
                                                        <source src="<?= $fileUrl ?>" type="video/<?= strtolower($fileExt) ?>">
                                                        Browser Anda tidak mendukung pemutaran video.
                                                    </video>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle"></i> File tidak dapat ditampilkan secara langsung. Silakan download untuk melihat.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i cyanlass="fas fa-exclamation-triangle"></i> Belum ada file yang diupload.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tindakan Review -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">Tindakan Review</h5>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <?php if ($action === 'approve'): ?>
                                        <div class="alert alert-success">
                                            <p>Anda akan menyetujui hasil pekerjaan ini. Status task akan berubah menjadi "Telah Upload".</p>
                                            <p><strong>Harap masukkan link postingan untuk melanjutkan.</strong></p>
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
                                                <button type="submit" class="btn btn-success">Setujui & Simpan Link</button>
                                            </div>
                                        </form>
                                    <?php elseif ($action === 'revise'): ?>
                                        <div class="alert alert-warning">
                                            <p>Anda akan meminta revisi untuk hasil pekerjaan ini. Status task akan berubah menjadi "Perlu Revisi".</p>
                                        </div>
                                        
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label for="revision_note" class="form-label">Catatan Revisi</label>
                                                <textarea class="form-control" id="revision_note" name="revision_note" rows="5" required></textarea>
                                                <div class="form-text">Jelaskan secara detail apa yang perlu direvisi.</div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-secondary">Kembali</a>
                                                <button type="submit" class="btn btn-warning">Kirim Permintaan Revisi</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-center gap-3">
                                            <a href="review_task.php?id=<?= $taskId ?>&action=approve" class="btn btn-success btn-lg">
                                                <i class="fas fa-check me-2"></i> Setujui Hasil
                                            </a>
                                            <a href="review_task.php?id=<?= $taskId ?>&action=revise" class="btn btn-warning btn-lg">
                                                <i class="fas fa-edit me-2"></i> Minta Revisi
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk menampilkan/menyembunyikan input link berdasarkan checkbox -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk toggle input link berdasarkan checkbox
    function toggleLinkInput(checkboxId, containerId) {
        const checkbox = document.getElementById(checkboxId);
        const container = document.getElementById(containerId);
        
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    container.style.display = 'block';
                    // Tambahkan required pada input jika checkbox dicentang
                    const input = container.querySelector('input');
                    if (input) input.required = true;
                } else {
                    container.style.display = 'none';
                    // Hapus required pada input jika checkbox tidak dicentang
                    const input = container.querySelector('input');
                    if (input) {
                        input.required = false;
                        input.value = '';
                    }
                }
            });
        }
    }
    
    // Terapkan untuk setiap platform
    toggleLinkInput('mirror_tiktok', 'tiktok_link_container');
    toggleLinkInput('mirror_facebook', 'facebook_link_container');
    toggleLinkInput('mirror_twitter', 'twitter_link_container');
    toggleLinkInput('mirror_threads', 'threads_link_container');
});
</script>

<?php include '../includes/footer.php'; ?>
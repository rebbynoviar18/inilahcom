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
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Dapatkan detail task
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name, 
           a.name as account_name,
           cp.name as content_pillar_name,
           ct.name as content_type_name,
           u1.name as assigned_to_name,
           u2.name as created_by_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN content_pillars cp ON t.content_pillar_id = cp.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN users u1 ON t.assigned_to = u1.id
    LEFT JOIN users u2 ON t.created_by = u2.id
    WHERE t.id = ? AND t.created_by = ? AND t.status = 'ready_for_review'
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dalam status siap review";
    header('Location: tasks.php');
    exit();
}

// Proses form approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve') {
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

        // Kirim notifikasi ke creative director
        $stmt = $pdo->prepare("
            SELECT id FROM users WHERE role = 'creative_director' LIMIT 1
        ");
        $stmt->execute();
        $creativeDirectorId = $stmt->fetchColumn();

        if ($creativeDirectorId) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $creativeDirectorId,
                "Task menunggu verifikasi akhir: " . $task['title'],
                "../admin/verify_task.php?id=" . $taskId
            ]);
        }

        $pdo->commit();

        $_SESSION['success'] = "Task berhasil disetujui dan menunggu verifikasi akhir";
        header("Location: view_task.php?id=$taskId");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: review_task.php?id=$taskId");
        exit();
    }
}

// Proses form revisi
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
                                        // Decode JSON file paths
                                        $filePaths = json_decode($task['file_path'], true);
                                        if (!is_array($filePaths)) {
                                            // Fallback untuk file tunggal (backward compatibility)
                                            $filePaths = [$task['file_path']];
                                        }
                                        ?>
                                        
                                        <div class="file-list">
                                            <?php foreach ($filePaths as $index => $filePath): ?>
                                                <?php
                                                $fileName = basename($filePath);
                                                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                                $fileUrl = '../uploads/' . $filePath;
                                                $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif']);
                                                $isVideo = in_array($fileExt, ['mp4', 'mov', 'avi']);
                                                $isPdf = $fileExt === 'pdf';
                                                
                                                // Get file icon
                                                $fileIcon = getFileIcon($fileExt);
                                                ?>
                                                
                                                <div class="file-item d-flex align-items-center p-2 mb-2 border rounded">
                                                    <div class="file-icon me-2">
                                                        <i class="fas <?= $fileIcon['icon'] ?> fa-lg text-<?= $fileIcon['color'] ?>"></i>
                                                    </div>
                                                    
                                                    <div class="file-info flex-grow-1">
                                                        <div class="file-name small fw-bold"><?= htmlspecialchars($fileName) ?></div>
                                                        <div class="file-type text-muted" style="font-size: 0.75rem;">
                                                            <?= strtoupper($fileExt) ?>
                                                            <?php if (file_exists('../uploads/' . $filePath)): ?>
                                                                • <?= formatFileSize(filesize('../uploads/' . $filePath)) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="file-actions">
                                                        <?php if ($isImage): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                                    onclick="previewImage('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>')">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        <?php elseif ($isVideo): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                                    onclick="previewVideo('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>')">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        <?php elseif ($isPdf): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                                    onclick="previewPdf('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>')">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <a href="<?= htmlspecialchars($fileUrl) ?>" class="btn btn-sm btn-success" download>
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <?php if (count($filePaths) > 1): ?>
                                            <div class="mt-3 text-center">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="downloadAllFiles()">
                                                    <i class="fas fa-download"></i> Download Semua
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Belum ada file yang diupload.
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
                                            <p>Anda akan menyetujui hasil pekerjaan ini. Status task akan berubah menjadi "Telah Upload" dan menunggu verifikasi akhir dari Creative Director.</p>
                                        </div>
                                        
                                        <form method="POST">
                                            <div class="d-flex justify-content-between">
                                                <a href="review_task.php?id=<?= $taskId ?>" class="btn btn-secondary">Kembali</a>
                                                <button type="submit" class="btn btn-success">Setujui Hasil</button>
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
                                                <a href="review_task.php?id=<?= $taskId ?>" class="btn btn-secondary">Kembali</a>
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

<!-- Modal Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="previewContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="downloadFromPreview" class="btn btn-primary" download>
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.file-item {
    transition: all 0.2s ease;
    background-color: #f8f9fa;
}

.file-item:hover {
    background-color: #e9ecef;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.file-icon {
    width: 40px;
    text-align: center;
}

.file-actions .btn {
    margin-left: 2px;
}

.modal-xl .modal-body {
    max-height: 70vh;
    overflow: auto;
}

.preview-image {
    max-width: 100%;
    max-height: 60vh;
    object-fit: contain;
}

.preview-video {
    max-width: 100%;
    max-height: 60vh;
}

.preview-pdf {
    width: 100%;
    height: 60vh;
    border: none;
}
</style>

<script>
// Preview functions
function previewImage(url, fileName) {
    document.getElementById('previewModalLabel').textContent = 'Preview: ' + fileName;
    document.getElementById('previewContent').innerHTML = 
        `<img src="${url}" class="preview-image" alt="${fileName}">`;
    document.getElementById('downloadFromPreview').href = url;
    document.getElementById('downloadFromPreview').download = fileName;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function previewVideo(url, fileName) {
    document.getElementById('previewModalLabel').textContent = 'Preview: ' + fileName;
    document.getElementById('previewContent').innerHTML = 
        `<video controls class="preview-video">
            <source src="${url}" type="video/mp4">
            Browser Anda tidak mendukung pemutaran video.
        </video>`;
    document.getElementById('downloadFromPreview').href = url;
    document.getElementById('downloadFromPreview').download = fileName;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function previewPdf(url, fileName) {
    document.getElementById('previewModalLabel').textContent = 'Preview: ' + fileName;
    document.getElementById('previewContent').innerHTML = 
        `<iframe src="${url}" class="preview-pdf"></iframe>`;
    document.getElementById('downloadFromPreview').href = url;
    document.getElementById('downloadFromPreview').download = fileName;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function downloadAllFiles() {
    <?php if (isset($filePaths) && is_array($filePaths)): ?>
        const files = <?= json_encode($filePaths) ?>;
        files.forEach((filePath, index) => {
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = '../uploads/' + filePath;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }, index * 500); // Delay 500ms between downloads
        });
    <?php endif; ?>
}
</script>

<?php
// Helper function untuk icon file
function getFileIcon($extension) {
    $icons = [
        'jpg' => ['icon' => 'fa-image', 'color' => 'success'],
        'jpeg' => ['icon' => 'fa-image', 'color' => 'success'],
        'png' => ['icon' => 'fa-image', 'color' => 'success'],
        'gif' => ['icon' => 'fa-image', 'color' => 'success'],
        'pdf' => ['icon' => 'fa-file-pdf', 'color' => 'danger'],
        'doc' => ['icon' => 'fa-file-word', 'color' => 'primary'],
        'docx' => ['icon' => 'fa-file-word', 'color' => 'primary'],
        'xls' => ['icon' => 'fa-file-excel', 'color' => 'success'],
        'xlsx' => ['icon' => 'fa-file-excel', 'color' => 'success'],
        'ppt' => ['icon' => 'fa-file-powerpoint', 'color' => 'warning'],
        'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => 'warning'],
        'zip' => ['icon' => 'fa-file-archive', 'color' => 'secondary'],
        'rar' => ['icon' => 'fa-file-archive', 'color' => 'secondary'],
        'ai' => ['icon' => 'fa-file-image', 'color' => 'warning'],
        'psd' => ['icon' => 'fa-file-image', 'color' => 'info'],
        'mp4' => ['icon' => 'fa-file-video', 'color' => 'danger'],
        'mov' => ['icon' => 'fa-file-video', 'color' => 'danger'],
        'avi' => ['icon' => 'fa-file-video', 'color' => 'danger'],
    ];
    
    return $icons[$extension] ?? ['icon' => 'fa-file', 'color' => 'secondary'];
}

// Helper function untuk format ukuran file
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
}
?>

<?php include '../includes/footer.php'; ?>
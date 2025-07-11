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
        ct.name as content_type_name,
        cp.name as content_pillar_name,
        a.name as account_name,
        creator.name as creator_name,
        assignee.name as assigned_to_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN content_pillars cp ON t.content_pillar_id = cp.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE t.id = ? AND (t.assigned_to = ? OR t.created_by = ?)
");
$stmt->execute([$taskId, $userId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau Anda tidak memiliki akses";
    header('Location: tasks.php');
    exit();
}

// Ambil riwayat status
$stmt = $pdo->prepare("
    SELECT tsl.*, u.name as user_name
    FROM task_status_logs tsl
    JOIN users u ON tsl.updated_by = u.id
    WHERE tsl.task_id = ?
    ORDER BY tsl.timestamp DESC
");
$stmt->execute([$taskId]);
$statusLogs = $stmt->fetchAll();

// Ambil catatan revisi jika ada
$stmt = $pdo->prepare("
    SELECT r.*, u.name as revised_by_name
    FROM task_revisions r
    JOIN users u ON r.revised_by = u.id
    WHERE r.task_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$taskId]);
$revisions = $stmt->fetchAll();

// Ambil link distribusi jika ada
$links = [];
$stmt = $pdo->prepare("
    SELECT platform, link
    FROM task_links
    WHERE task_id = ?
");
$stmt->execute([$taskId]);
while ($row = $stmt->fetch()) {
    $links[] = $row;
}

$pageTitle = "Detail Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Detail Task</h4>
                    <?= getStatusBadge($task['status']) ?>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mb-3">Informasi Task</h5>
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
                            <th>Kategori</th>
                            <td><?= htmlspecialchars($task['category_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Jenis Konten</th>
                            <td><?= htmlspecialchars($task['content_type_name']) ?></td>
                        </tr>
                        <?php if (!empty($task['content_pillar_name'])): ?>
                        <tr>
                            <th>Pilar Konten</th>
                            <td><?= htmlspecialchars($task['content_pillar_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Akun Media</th>
                            <td><?= htmlspecialchars($task['account_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Prioritas</th>
                            <td><?= getPriorityBadge($task['priority']) ?></td>
                        </tr>
                        <tr>
                            <th>Deadline</th>
                            <td><?= date('d M Y - H:i', strtotime($task['deadline'])) ?></td>
                        </tr>
                        <tr>
                            <th>Dibuat Oleh</th>
                            <td><?= htmlspecialchars($task['creator_name']) ?></td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($links)): ?>
                    <h5 class="mt-4 mb-3">Link Berita</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
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
                                        <?php 
                                        $platformLabels = [
                                            'instagram' => 'Instagram',
                                            'tiktok' => 'TikTok',
                                            'facebook' => 'Facebook',
                                            'twitter' => 'Twitter (X)',
                                            'threads' => 'Threads',
                                            'website' => 'Website'
                                        ];
                                        echo $platformLabels[$link['platform']] ?? ucfirst($link['platform']);
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($link['link']) ?></td>
                                    <td>
                                        <a href="<?= htmlspecialchars($link['link']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-external-link-alt"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Preview Hasil Pekerjaan -->
            <div class="card">
                <div class="card-header">
                    <h5>Preview Hasil Pekerjaan</h5>
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
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Aksi</h5>
                </div>
                <div class="card-body">
                    <?php if (in_array($task['status'], ['in_production'])): ?>
                        <a href="upload_distribution.php?id=<?= $task['id'] ?>" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-upload"></i> Upload Link Berita
                        </a>
                    <?php elseif ($task['status'] === 'revision'): ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Revisi Diperlukan</h5>
                            <p>Task ini memerlukan revisi. Silakan lihat catatan revisi di bawah.</p>
                        </div>
                        <a href="upload_distribution.php?id=<?= $task['id'] ?>" class="btn btn-warning btn-block mb-2">
                            <i class="fas fa-edit"></i> Revisi Link Berita
                        </a>
                    <?php elseif ($task['status'] === 'ready_for_review'): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Menunggu Review</h5>
                            <p>Link berita telah diupload dan sedang menunggu review.</p>
                        </div>
                    <?php elseif ($task['status'] === 'completed'): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Task Selesai</h5>
                            <p>Task ini telah selesai dan diverifikasi.</p>
                        </div>
                    <?php endif; ?>
                    
                    <a href="tasks.php" class="btn btn-secondary btn-block mt-2">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Task
                    </a>
                </div>
            </div>
            
            <?php if (!empty($statusLogs)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Riwayat Status</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($statusLogs as $log): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0"><?= getStatusLabel($log['status']) ?></h6>
                                <small class="text-muted">
                                    <?= date('d M Y H:i', strtotime($log['timestamp'])) ?>
                                </small>
                                <p class="mb-0">oleh <?= htmlspecialchars($log['user_name']) ?></p>
                                <?php if (!empty($log['notes'])): ?>
                                <p class="text-muted mt-1"><?= htmlspecialchars($log['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($revisions)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Catatan Revisi</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($revisions as $revision): ?>
                    <div class="mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><?= htmlspecialchars($revision['revised_by_name']) ?></strong>
                            <small class="text-muted"><?= date('d M Y H:i', strtotime($revision['created_at'])) ?></small>
                        </div>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($revision['note'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
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
if (!function_exists('getFileIcon')) {
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
            'mp3' => ['icon' => 'fa-file-audio', 'color' => 'info'],
            'wav' => ['icon' => 'fa-file-audio', 'color' => 'info'],
        ];
        
        return $icons[$extension] ?? ['icon' => 'fa-file', 'color' => 'secondary'];
    }
}

// Helper function untuk format ukuran file
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
    }
}
?>
<!-- Script untuk menampilkan/menyembunyikan input link berdasarkan checkbox -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="mirror_platforms[]"]');
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const platform = this.value;
            const container = document.getElementById(platform + '_link_container');
            
            if (this.checked) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'marketing_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
    WHERE t.id = ? AND t.created_by = ?
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dapat diakses";
    header('Location: tasks.php');
    exit();
}

// Dapatkan riwayat status
$stmt = $pdo->prepare("
    SELECT tsl.*, u.name as user_name
    FROM task_status_logs tsl
    LEFT JOIN users u ON tsl.updated_by = u.id
    WHERE tsl.task_id = ?
    ORDER BY tsl.timestamp DESC
");
$stmt->execute([$taskId]);
$statusLogs = $stmt->fetchAll();

// Dapatkan revisi jika ada
$revisions = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as revised_by_name
        FROM revisions r
        JOIN users u ON r.revised_by = u.id
        WHERE r.task_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$taskId]);
    $revisions = $stmt->fetchAll();
} catch (PDOException $e) {
    // Jika tabel tidak ada, abaikan
}

// Dapatkan link distribusi jika ada
$links = [];
if ($task['category_name'] === 'Distribusi') {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM task_links
            WHERE task_id = ?
        ");
        $stmt->execute([$taskId]);
        $links = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Jika tabel tidak ada, abaikan
    }
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
                            <th>Akun</th>
                            <td><?= htmlspecialchars($task['account_name']) ?></td>
                        </tr>
                        <?php if (!empty($task['client_name'])): ?>
                        <tr>
                            <th>Nama Klien</th>
                            <td><?= htmlspecialchars($task['client_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($task['content_pillar_name'])): ?>
                        <tr>
                            <th>Pilar Konten</th>
                            <td><?= htmlspecialchars($task['content_pillar_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Jenis Konten</th>
                            <td><?= htmlspecialchars($task['content_type_name']) ?></td>
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
                            <th>Dikerjakan Oleh</th>
                            <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($task['status'] === 'ready_for_review' || $task['status'] === 'uploaded' || $task['status'] === 'completed'): ?>
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
                                        
                                        // Potong nama file jika terlalu panjang
                                        $displayName = strlen($fileName) > 25 ? substr($fileName, 0, 22) . '...' : $fileName;
                                        
                                        // Get file icon
                                        $fileIcon = getFileIcon($fileExt);
                                        ?>
                                        
                                    <div class="file-item mb-2 p-2 border rounded bg-light">
                                        <div class="row align-items-center g-2">
                                            <div class="col-2 text-center">
                                                <i class="fas <?= $fileIcon['icon'] ?> fa-lg text-<?= $fileIcon['color'] ?>"></i>
                                            </div>
                                            <div class="col-6">
                                                <div class="file-name small fw-bold text-truncate" title="<?= htmlspecialchars($fileName) ?>">
                                                    <?= htmlspecialchars($displayName) ?>
                                                </div>
                                                <div class="file-type text-muted" style="font-size: 0.7rem;">
                                                    <?= strtoupper($fileExt) ?>
                                                    <?php if (file_exists('../uploads/' . $filePath)): ?>
                                                        • <?= formatFileSize(filesize('../uploads/' . $filePath)) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-4 text-end">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <?php if ($isImage): ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                onclick="previewImage('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>')"
                                                                title="Preview">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php elseif ($isVideo): ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                onclick="previewVideo('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>')"
                                                                title="Play">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    <?php elseif ($isPdf): ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                onclick="previewPdf('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>')"
                                                                title="Preview">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <a href="<?= htmlspecialchars($fileUrl) ?>" class="btn btn-success btn-sm" download title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
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
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> Belum ada file hasil yang diunggah.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($links)): ?>
                    <h5 class="mt-4 mb-3">Link Distribusi</h5>
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
                                    <td><?= htmlspecialchars($link['platform'] ?? 'Platform') ?></td>
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
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Aksi</h5>
                </div>
                <div class="card-body">
                    <?php if ($task['status'] === 'draft'): ?>
                        <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-edit"></i> Edit Task
                        </a>
                        <form method="POST" action="submit_task.php" class="mb-2">
                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-paper-plane"></i> Kirim Task
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger btn-block" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash"></i> Hapus Task
                        </button>
                    <?php elseif ($task['status'] === 'rejected'): ?>
                        <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-warning btn-block mb-2">
                            <i class="fas fa-edit"></i> Revisi Task
                        </a>
                    <?php elseif ($task['status'] === 'ready_for_review'): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Task Siap Direview</h5>
                            <p>Tim produksi telah menyelesaikan task ini dan menunggu review dari Anda.</p>
                            <a href="review_task.php?id=<?= $task['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Review Hasil
                            </a>
                        </div>
                    <?php elseif ($task['status'] === 'uploaded'): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Menunggu Verifikasi Akhir</h5>
                            <p>Task telah Anda setujui dan sedang menunggu verifikasi akhir dari Creative Director.</p>
                        </div>
                    <?php elseif ($task['status'] === 'completed'): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Task Selesai</h5>
                            <p>Task ini telah selesai dan diverifikasi oleh Creative Director.</p>
                            <?php if (!empty($task['file_path'])): ?>
                                <a href="../uploads/<?= $task['file_path'] ?>" class="btn btn-primary" download>
                                    <i class="fas fa-download"></i> Download Hasil
                                </a>
                            <?php endif; ?>
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

<!-- Modal Hapus Task -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus task ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="delete_task.php">
                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
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
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    left: -30px;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background-color: #007bff;
    border: 3px solid #fff;
    box-shadow: 0 0 0 1px #007bff;
}
.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -23px;
    top: 15px;
    height: calc(100% - 15px);
    width: 2px;
    background-color: #007bff;
}

.file-item {
    transition: all 0.2s ease;
    border: 1px solid #dee2e6 !important;
}

.file-item:hover {
    background-color: #e9ecef !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-color: #adb5bd !important;
}

.file-name {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
}

.modal-xl .modal-body {
    max-height: 70vh;
    overflow: auto;
}

.preview-image {
    max-width: 100%;
    max-height: 60vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.preview-video {
    max-width: 100%;
    max-height: 60vh;
    border-radius: 8px;
}

.preview-pdf {
    width: 100%;
    height: 60vh;
    border: none;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .file-item .col-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    .file-item .col-4 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
    
    .btn-group-sm .btn {
        padding: 0.2rem 0.3rem;
        font-size: 0.7rem;
    }
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
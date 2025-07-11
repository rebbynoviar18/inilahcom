<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
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
    SELECT t.*, 
           c.name as category_name,
           ct.name as content_type_name,
           cp.name as content_pillar_name,
           a.name as account_name,
           creator.name as created_by_name
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN content_types ct ON t.content_type_id = ct.id
    JOIN content_pillars cp ON t.content_pillar_id = cp.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users creator ON t.created_by = creator.id
    WHERE t.id = ? AND t.status = 'waiting_head_confirmation'
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak memerlukan persetujuan";
    header('Location: tasks.php');
    exit();
}

// Proses persetujuan task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['approve'])) {
            // Setujui task
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'waiting_confirmation' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat perubahan status
            $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by) VALUES (?, 'waiting_confirmation', ?)");
            $stmt->execute([$taskId, $userId]);
            
            // Kirim notifikasi ke tim konten
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'content_team' LIMIT 1");
            $stmt->execute();
            $contentTeamId = $stmt->fetchColumn();
            
            if ($contentTeamId) {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                $stmt->execute([
                    $contentTeamId,
                    "Ada task baru dari tim marketing yang telah disetujui",
                    "view_task.php?id=$taskId"
                ]);
            }
            
            $_SESSION['success'] = "Task berhasil disetujui dan diteruskan ke tim konten";
            
        } elseif (isset($_POST['reject'])) {
            // Tolak task
            $rejectionReason = trim($_POST['rejection_reason']);
            
            if (empty($rejectionReason)) {
                throw new Exception("Alasan penolakan wajib diisi");
            }
            
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat perubahan status dengan alasan penolakan
            $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by, notes) VALUES (?, 'rejected', ?, ?)");
            $stmt->execute([$taskId, $userId, $rejectionReason]);
            
            // Kirim notifikasi ke marketing
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            $stmt->execute([
                $task['created_by'],
                "Task Anda ditolak oleh Creative Director",
                "view_task.php?id=$taskId"
            ]);
            
            $_SESSION['success'] = "Task telah ditolak dan notifikasi dikirim ke tim marketing";
        }
        
        $pdo->commit();
        header('Location: tasks.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

$pageTitle = "Persetujuan Task Marketing";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Persetujuan Task Marketing</h4>
                    <span class="badge bg-warning">Menunggu Persetujuan</span>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Task ini dibuat oleh tim marketing dan memerlukan persetujuan Anda sebelum diteruskan ke tim konten.
                    </div>
                    
                    <h5 class="mb-3">Detail Task</h5>
                    <table class="table table-bordered mb-4">
                        <tr>
                            <th width="30%">Judul</th>
                            <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td><?= nl2br(htmlspecialchars($task['description'])) ?></td>
                        </tr>
                        <tr>
                            <th>Tipe Task</th>
                            <td>
                                <?php if ($task['task_type'] === 'production'): ?>
                                    <span class="badge bg-primary">Produksi Konten</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Distribusi Konten</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Kategori</th>
                            <td><?= htmlspecialchars($task['category_name']) ?></td>
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
<?php if (!empty($task['client_name'])): ?>
<tr>
    <th>Nama Klien</th>
    <td><?= htmlspecialchars($task['client_name']) ?></td>
</tr>
<?php endif; ?>
                        <tr>
                            <th>Prioritas</th>
                            <td><?= getPriorityBadge($task['priority']) ?></td>
                        </tr>
                        <tr>
                            <th>Deadline</th>
                            <td><?= date('d M Y - H:i', strtotime($task['deadline'])) ?> WIB</td>
                        </tr>
                        <tr>
                            <th>Dibuat Oleh</th>
                            <td><?= getUserProfilePhotoWithName($task['created_by'], $task['created_by_name']) ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($task['task_type'] === 'distribution' && !empty($task['file_path'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>File Konten</h5>
                        </div>
                        <div class="card-body">
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
                        </div>
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
                    <form method="POST">
                        <button type="submit" name="approve" class="btn btn-success btn-block mb-3">
                            <i class="fas fa-check-circle"></i> Setujui Task
                        </button>
                        
                        <button type="button" class="btn btn-danger btn-block" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fas fa-times-circle"></i> Tolak Task
                        </button>
                        
                        <hr>
                        
                        <a href="tasks.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tolak Task -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Tolak Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required></textarea>
                        <small class="form-text text-muted">Berikan alasan mengapa task ini ditolak agar tim marketing dapat memperbaikinya.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="reject" class="btn btn-danger">Tolak Task</button>
                </div>
            </form>
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
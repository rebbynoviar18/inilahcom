<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

// Pastikan taskId didefinisikan di awal file
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$taskId) {
    $_SESSION['error'] = "ID task tidak valid";
    header('Location: tasks.php');
    exit;
}

if (getUserRole() !== 'redaktur_pelaksana') {
    header("Location: ../index.php");
    exit();
}

// Tambahkan definisi $userId
$userId = $_SESSION['user_id'];

// Ambil detail task terlebih dahulu untuk digunakan dalam proses verifikasi
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name,
           ct.name as content_type_name,
           cp.name as content_pillar_name,
           a.id as account_id,
           a.name as account_name,
           uc.id as created_by,
           uc.name as created_by_name,
           ua.id as assigned_to,
           ua.name as assigned_to_name
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN content_types ct ON t.content_type_id = ct.id
    JOIN content_pillars cp ON t.content_pillar_id = cp.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users uc ON t.created_by = uc.id
    JOIN users ua ON t.assigned_to = ua.id
    WHERE t.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan";
    header("Location: tasks.php");
    exit();
}

// Proses verifikasi task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify'])) {
        // Verifikasi dan selesaikan task
        try {
            $pdo->beginTransaction();
            
            // Update status task menjadi completed
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat perubahan status
            $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by) VALUES (?, 'completed', ?)");
            $stmt->execute([$taskId, $userId]);
            
            // Kirim notifikasi ke pembuat task
            $message = "Task yang Anda buat telah selesai dan diverifikasi oleh Creative Director";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            $stmt->execute([$task['created_by'], $message, "view_task.php?id=$taskId"]);
            
            $pdo->commit();
            $_SESSION['success'] = "Task berhasil diverifikasi dan diselesaikan";
            header("Location: view_task.php?id=$taskId");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Gagal memverifikasi task: " . $e->getMessage();
        }
    } elseif (isset($_POST['reject'])) {
        // Tolak task dan minta revisi
        $rejectReason = trim($_POST['reject_reason']);
        
        if (empty($rejectReason)) {
            $_SESSION['error'] = "Alasan penolakan harus diisi";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Update status task menjadi revision
                $stmt = $pdo->prepare("UPDATE tasks SET status = 'revision' WHERE id = ?");
                $stmt->execute([$taskId]);
                
                // Catat perubahan status
                $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by) VALUES (?, 'revision', ?)");
                $stmt->execute([$taskId, $userId]);
                
                // Simpan catatan revisi
                $stmt = $pdo->prepare("INSERT INTO task_revisions (task_id, note, revised_by) VALUES (?, ?, ?)");
                $stmt->execute([$taskId, $rejectReason, $userId]);
                
                // Kirim notifikasi ke tim produksi
                $message = "Task Anda memerlukan revisi dari Creative Director";
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                $stmt->execute([$task['assigned_to'], $message, "view_task.php?id=$taskId"]);
                
                $pdo->commit();
                $_SESSION['success'] = "Task berhasil dikembalikan untuk revisi";
                header("Location: view_task.php?id=$taskId");
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Gagal mengirim permintaan revisi: " . $e->getMessage();
            }
        }
    }
}

    // Berikan poin kepada user yang menyelesaikan task
        if ($task['assigned_to']) {
            $pointsAwarded = calculateAndSavePoints($taskId, $task['assigned_to']);
            error_log("Points awarded for task $taskId to production team: " . ($pointsAwarded ? "Yes" : "No"));
        }
        
        // Berikan poin kepada user yang membuat task (tim konten)
        if ($task['created_by']) {
            $pointsAwarded = calculateAndSavePoints($taskId, $task['created_by']);
            error_log("Points awarded for task $taskId to content team: " . ($pointsAwarded ? "Yes" : "No"));
        }


// Ambil link postingan
$stmt = $pdo->prepare("
    SELECT platform, link 
    FROM task_links 
    WHERE task_id = ?
");
$stmt->execute([$taskId]);
$taskLinks = $stmt->fetchAll();

// Ambil riwayat status
$statusHistory = $pdo->prepare("
    SELECT tsl.*, u.name as updated_by_name, u.id as updated_by
    FROM task_status_logs tsl
    JOIN users u ON tsl.updated_by = u.id
    WHERE tsl.task_id = ?
    ORDER BY tsl.timestamp DESC
");
$statusHistory->execute([$taskId]);

// Ambil catatan revisi
$revisions = $pdo->prepare("
    SELECT r.*, u.name as revised_by_name, u.id as revised_by
    FROM revisions r
    JOIN users u ON r.revised_by = u.id
    WHERE r.task_id = ?
    ORDER BY r.created_at DESC
");
$revisions->execute([$taskId]);

$pageTitle = "Detail Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h4>
                    <span><?= getStatusBadge($task['status']) ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5>Deskripsi Task</h5>
                        <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Detail Konten</h5>
                            <ul class="list-unstyled">
                                <li><strong>Kategori:</strong> <?= htmlspecialchars($task['category_name']) ?></li>
                                <li><strong>Jenis Konten:</strong> <?= htmlspecialchars($task['content_type_name']) ?></li>
                                <li><strong>Pilar Konten:</strong> <?= htmlspecialchars($task['content_pillar_name']) ?></li>
                                <li><strong>Akun Media:</strong> <?= htmlspecialchars($task['account_name']) ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Informasi Task</h5>
                            <ul class="list-unstyled">
                                <li><strong>Dibuat Oleh:</strong> <?php echo getUserProfilePhotoWithName($task['created_by'], $task['created_by_name']); ?></li>
                                <li><strong>Ditugaskan Ke:</strong> <?php echo getUserProfilePhotoWithName($task['assigned_to'], $task['assigned_to_name']); ?></li>
                                <li><strong>Prioritas:</strong> <?= getPriorityBadge($task['priority']) ?></li>
                                <li><strong>Deadline:</strong> 
                                    <span class="<?= (strtotime($task['deadline']) - time() < 86400 && !in_array($task['status'], ['completed', 'uploaded'])) ? 'text-danger' : '' ?>">
                                        <?= date('d M Y - H:i', strtotime($task['deadline'])) ?> WIB
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    
<div class="card mt-4">
    <div class="card-header">
        <h5>Link Postingan</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($taskLinks)): ?>
            <ul class="list-group">
                <?php foreach ($taskLinks as $link): ?>
                    <li class="list-group-item">
                        <strong><?= ucfirst($link['platform']) ?>:</strong> 
                        <a href="<?= htmlspecialchars($link['link']) ?>" target="_blank">
                            <?= htmlspecialchars($link['link']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-info">Belum ada link postingan yang tersedia.</div>
        <?php endif; ?>
    </div>
</div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Riwayat Status</h5>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <?php while ($history = $statusHistory->fetch()): ?>
                        <li class="timeline-item <?= $history['status'] === 'completed' ? 'completed' : ($history['status'] === 'revision' ? 'revision' : '') ?>">
                            <div class="timeline-info">
                                <span><?= date('d M Y H:i', strtotime($history['timestamp'])) ?></span>
                            </div>
                            <div class="timeline-content">
                                <h6><?= getStatusBadge($history['status']) ?></h6>
                                <p>Diperbarui oleh: <?php echo getUserProfilePhotoWithName($history['updated_by'], $history['updated_by_name']); ?></p>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Catatan Revisi</h5>
                </div>
                <div class="card-body">
                    <?php if ($revisions->rowCount() > 0): ?>
                        <?php while ($revision = $revisions->fetch()): ?>
                        <div class="mb-3 p-2 border rounded">
                            <p class="mb-1"><?php echo getUserProfilePhotoWithName($revision['revised_by'], $revision['revised_by_name']); ?> - <?= date('d M Y H:i', strtotime($revision['created_at'])) ?></p>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($revision['note'])) ?></p>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted">Tidak ada catatan revisi</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
    <div class="card-header">
        <h5>Tim Bantuan Tambahan</h5>
    </div>
    <div class="card-body">
        <?php
        // Ambil daftar tim bantuan yang sudah ditambahkan
        $assistantsQuery = $pdo->prepare("
            SELECT ta.id, ta.note, ta.created_at, u.id as user_id, u.name as assistant_name, u.profile_photo, u.role
            FROM task_assistance ta
            JOIN users u ON ta.user_id = u.id
            WHERE ta.task_id = ?
            ORDER BY ta.created_at ASC
        ");
        $assistantsQuery->execute([$taskId]);
        $assistants = $assistantsQuery->fetchAll();
        
        if (count($assistants) > 0): 
        ?>
            <div class="list-group">
                <?php foreach ($assistants as $assistant): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <?php $photoUrl = getUserProfilePhoto($assistant['user_id']); ?>
                            <img src="<?= $photoUrl ?>" alt="<?= htmlspecialchars($assistant['assistant_name']) ?>" 
                                 class="rounded-circle me-2" width="40" height="40">
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($assistant['assistant_name']) ?></div>
                                <small class="text-muted">
                                    <?= $assistant['role'] === 'production_team' ? 'Tim Produksi' : 'Tim Konten' ?>
                                    <?php if (!empty($assistant['note'])): ?>
                                        - <?= htmlspecialchars($assistant['note']) ?>
                                    <?php endif; ?>
                                </small>
                                <br>
                                <i><small class="text-muted" style="font-size:0.7rem;">Ditambahkan: <?= date('d M Y, H:i', strtotime($assistant['created_at'])) ?> WIB</small></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Tidak ada tim bantuan tambahan untuk task ini.</p>
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



    <?php if ($task['status'] === 'waiting_redaktur_confirmation'): ?>
        <!-- Tambahkan tombol verifikasi dengan modal popup untuk rating -->
        <button type="button" class="btn btn-success btn-block mb-3 w-100" data-bs-toggle="modal" data-bs-target="#TerimaModal">
            <i class="fas fa-check-circle"></i> Terima Task
        </button>
    <?php endif; ?>
                        
    <?php if ($task['status'] === 'uploaded'): ?>
        <!-- Tambahkan tombol verifikasi dengan modal popup untuk rating -->
        <button type="button" class="btn btn-success btn-block mb-3 w-100" data-bs-toggle="modal" data-bs-target="#verifyModal">
            <i class="fas fa-check-circle"></i> Verifikasi Task
        </button>
        
        <button type="button" class="btn btn-danger btn-block mb-2 w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="fas fa-times"></i> Tolak & Minta Revisi
        </button>
    <?php elseif ($task['status'] === 'completed'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Task ini telah selesai dan diverifikasi
        </div>
        
    <?php endif; ?>    
    <a href="tasks.php" class="btn btn-secondary btn-block mt-2 w-100">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Task
    </a>
                </div>
            </div>
            
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
            

<!-- Modal Reject Task -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel">Tolak Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="4" required></textarea>
                        <div class="form-text">Berikan alasan yang jelas mengapa task ini ditolak dan apa yang perlu diperbaiki.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="reject" class="btn btn-danger">Tolak & Minta Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Verifikasi Task dengan Rating -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verifyModalLabel">Verifikasi Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="process_verification.php">
                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="form-text">Berikan rating untuk kualitas hasil pekerjaan</div>
                        <div class="rating" style="font-size: 2rem; direction: rtl;">
                            <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="Sangat Baik">5 stars</label>
                            <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="Baik">4 stars</label>
                            <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="Cukup">3 stars</label>
                            <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="Kurang">2 stars</label>
                            <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="Sangat Kurang">1 star</label>
                        </div>
                        <div class="clearfix mb-3"></div>
                        
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="verify" class="btn btn-success">Verifikasi & Selesaikan</button>
                </div>
            </form>
        </div>
    </div>
</div>





<!-- Modal Terima Task dari marketing -->
<div class="modal fade" id="TerimaModal" tabindex="-1" aria-labelledby="TerimaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="TerimaModalLabel">Terima Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="process_task_approval.php">
                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">Pilih Pelaksana Task</label>
                        <select class="form-select" id="assigned_to" name="assigned_to" required>
                            <option value="">-- Pilih Pelaksana --</option>
                            <?php
                            // Ambil daftar pelaksana berdasarkan kategori task
                            $role = ($task['category_name'] === 'Publikasi') ? 'redaksi' : 'production_team';
                            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = ? ORDER BY name");
                            $stmt->execute([$role]);
                            while ($user = $stmt->fetch()) {
                                echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adjusted_deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="adjusted_deadline" name="adjusted_deadline" 
                               value="<?= date('Y-m-d\TH:i', strtotime($task['deadline'])) ?>" required>
                        <div class="form-text">Tentukan deadline untuk pelaksanaan task ini</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan (opsional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" name="action" value="approve" class="btn btn-success">
                            <i class="fas fa-check"></i> Setujui Task
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                            <i class="fas fa-times"></i> Tolak Task
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (getUserRole() === 'creative_director' && $task['status'] === 'completed'): ?>

<?php endif; ?>

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

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menyalin link
    const tombolSalin = document.querySelectorAll('.salin-link');
    tombolSalin.forEach(tombol => {
        tombol.addEventListener('click', function() {
            const link = this.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                // Ubah teks tombol sementara
                const htmlAsli = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    this.innerHTML = htmlAsli;
                }, 2000);
            });
        });
    });
});

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
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

if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Handle viral content marking
$viralSuccess = $viralError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_viral']) && getUserRole() === 'creative_director') {
    $platform = $_POST['platform'];
    $views = (int)$_POST['views'];
    
    if ($views <= 0) {
        $viralError = "Jumlah views harus lebih dari 0";
    } else {
        try {
            // Check if already marked for today
            $checkStmt = $pdo->prepare("
                SELECT id FROM viral_content 
                WHERE task_id = ? AND platform = ? AND DATE(marked_date) = CURDATE()
            ");
            $checkStmt->execute([$taskId, $platform]);
            $existingId = $checkStmt->fetchColumn();
            
            if ($existingId) {
                // Update existing record
                $stmt = $pdo->prepare("
                    UPDATE viral_content 
                    SET views = ?, marked_by = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$views, $_SESSION['user_id'], $existingId]);
            } else {
                // Insert new record
                $stmt = $pdo->prepare("
                    INSERT INTO viral_content (task_id, platform, views, marked_date, marked_by)
                    VALUES (?, ?, ?, CURDATE(), ?)
                ");
                $stmt->execute([$taskId, $platform, $views, $_SESSION['user_id']]);
            }
            
            $viralSuccess = "Konten berhasil ditandai sebagai viral!";
        } catch (PDOException $e) {
            $viralError = "Error: " . $e->getMessage();
        }
    }
}

// Get viral content data for this task
$viralStmt = $pdo->prepare("
    SELECT * FROM viral_content 
    WHERE task_id = ? 
    ORDER BY marked_date DESC
");
$viralStmt->execute([$taskId]);
$viralContents = $viralStmt->fetchAll();

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
    FROM task_revisions r
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
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Aksi</h5>
                </div>               
                
                <div class="card-body">

    <?php if ($task['status'] === 'waiting_head_confirmation'): ?>
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
                                <small>
                                    
                                    <?php if (!empty($assistant['note'])): ?>
                                    <?= htmlspecialchars($assistant['note']) ?>
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
                                $fileUrl = $filePath;
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
                                                            onclick="previewS3File('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>', 'image')"
                                                            title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                <?php elseif ($isVideo): ?>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="previewS3File('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>', 'video')"
                                                            title="Play">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                <?php elseif ($isPdf): ?>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="previewS3File('<?= htmlspecialchars($fileUrl) ?>', '<?= htmlspecialchars($fileName) ?>', 'pdf')"
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
                        <label class="form-label">Rating Kualitas Hasil Pekerjaan <span class="text-danger">*</span></label>
                        <div class="form-text mb-2">Berikan rating untuk kualitas hasil pekerjaan</div>
                        
                        <div class="rating-container">
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" title="Sangat Baik">★</label>
                                
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="Baik">★</label>
                                
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="Cukup">★</label>
                                
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="Kurang">★</label>
                                
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="Sangat Kurang">★</label>
                            </div>
                        </div>
                        
                        <div class="rating-text mt-2">
                            <small class="text-muted">Klik bintang untuk memberikan rating</small>
                        </div>
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
                            $role = ($task['category_name'] === 'Distribusi') ? 'content_team' : 'production_team';
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
<div class="card mt-4">
    <div class="card-header">
        <h5>Tandai Konten Viral</h5>
    </div>
    <div class="card-body">
        <?php if ($viralSuccess): ?>
            <div class="alert alert-success"><?= $viralSuccess ?></div>
        <?php endif; ?>
        
        <?php if ($viralError): ?>
            <div class="alert alert-danger"><?= $viralError ?></div>
        <?php endif; ?>
        
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label for="platform" class="form-label">Platform</label>
                <select class="form-select" id="platform" name="platform" required>
                    <option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="views" class="form-label">Jumlah Views</label>
                <input type="number" class="form-control" id="views" name="views" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" name="mark_viral" class="btn btn-primary">Tandai Sebagai Viral</button>
            </div>
        </form>
        
        <?php if (!empty($viralContents)): ?>
            <h6 class="mt-4">Riwayat Penandaan Viral</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Platform</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viralContents as $viral): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($viral['marked_date'])) ?></td>
                                <td>
                                    <?php if ($viral['platform'] === 'instagram'): ?>
                                        <span class="badge bg-primary">Instagram</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">TikTok</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($viral['views']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
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

/* Star Rating CSS */
.rating-container {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s ease-in-out;
    padding: 0 2px;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #ffc107;
}

.star-rating label:hover {
    transform: scale(1.1);
}

.timeline {
    list-style: none;
    padding: 0;
}

.timeline-item {
    border-left: 2px solid #dee2e6;
    padding-left: 20px;
    margin-bottom: 20px;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #6c757d;
}

.timeline-item.completed::before {
    background-color: #28a745;
}

.timeline-item.revision::before {
    background-color: #dc3545;
}

.timeline-info {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.timeline-content h6 {
    margin-bottom: 5px;
}
</style>

<script>
// Preview functions
async function previewS3File(s3Key, fileName, fileType) {
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const modalLabel = document.getElementById('previewModalLabel');
    const previewContent = document.getElementById('previewContent');
    const downloadLink = document.getElementById('downloadFromPreview');

    // 1. Show the modal with a loading indicator
    modalLabel.textContent = 'Preview: ' + fileName;
    previewContent.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;
    downloadLink.href = '#';
    previewModal.show();

    try {
        // 2. Fetch the secure, temporary URL from your server
        const response = await fetch(`../includes/generate_presigned_url.php?key=${encodeURIComponent(s3Key)}`);
        if (!response.ok) {
            throw new Error(`Server error! Status: ${response.status}`);
        }
        const data = await response.json();

        if (data.success) {
            const secureUrl = data.url;
            let contentHtml = '';

            // 3. Generate the correct HTML based on fileType
            switch (fileType) {
                case 'image':
                    contentHtml = `<img src="${secureUrl}" class="preview-image" alt="${fileName}">`;
                    break;
                case 'video':
                    contentHtml = `
                        <video controls class="preview-video">
                            <source src="${secureUrl}">
                            Your browser does not support the video tag.
                        </video>`;
                    break;
                case 'pdf':
                    contentHtml = `<iframe src="${secureUrl}" class="preview-pdf"></iframe>`;
                    break;
                default:
                    contentHtml = `<div class="alert alert-warning">Preview is not available for this file type.</div>`;
            }
            
            previewContent.innerHTML = contentHtml;
            downloadLink.href = secureUrl;
            downloadLink.download = fileName;

        } else {
            throw new Error(data.error || 'Failed to get preview link.');
        }

    } catch (error) {
        // 4. Display any errors in the modal
        previewContent.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${error.message}</div>`;
    }
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
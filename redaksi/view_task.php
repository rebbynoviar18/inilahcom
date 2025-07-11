<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'redaksi') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Dapatkan detail task
// Perbaikan: Izinkan tim konten melihat task yang diassign kepada mereka
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name, 
           ct.name as content_type_name, 
           a.name as account_name,
           cp.name as content_pillar_name,
           u_creator.name as creator_name, 
           u_creator.profile_photo as creator_photo,
           u_assignee.name as assignee_name,
           u_assignee.profile_photo as assignee_photo
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN content_pillars cp ON t.content_pillar_id = cp.id
    LEFT JOIN users u_creator ON t.created_by = u_creator.id
    LEFT JOIN users u_assignee ON t.assigned_to = u_assignee.id
    WHERE t.id = ? AND (t.assigned_to = ? OR t.created_by = ?)
");
$stmt->execute([$taskId, $userId, $userId]);
$task = $stmt->fetch();

// Ambil daftar tim produksi tambahan yang terlibat
try {
    $assistantsStmt = $pdo->prepare("
        SELECT ta.*, u.name as assistant_name, u.profile_photo
        FROM task_assistance ta
        JOIN users u ON ta.user_id = u.id
        WHERE ta.task_id = ?
        ORDER BY ta.created_at ASC
    ");
    $assistantsStmt->execute([$taskId]);
    $assistants = $assistantsStmt->fetchAll();
} catch (PDOException $e) {
    // Jika tabel belum ada atau error lainnya, set assistants sebagai array kosong
    $assistants = [];
}

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau Anda tidak memiliki akses";
    header('Location: tasks.php');
    exit();
}

// Dapatkan komentar task
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name, u.profile_photo, u.role
        FROM task_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.task_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$taskId]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    // Jika tabel tidak ada, gunakan array kosong
    if ($e->getCode() == '42S02') {
        $comments = [];
    } else {
        throw $e; // Rethrow jika error bukan karena tabel tidak ada
    }
}

// Dapatkan log status task
try {
    $stmt = $pdo->prepare("
        SELECT tsl.*, u.name, u.profile_photo
        FROM task_status_logs tsl
        JOIN users u ON tsl.updated_by = u.id
        WHERE tsl.task_id = ?
        ORDER BY tsl.timestamp DESC
    ");
    $stmt->execute([$taskId]);
    $statusLogs = $stmt->fetchAll();
} catch (PDOException $e) {
    // Jika tabel tidak ada, gunakan array kosong
    if ($e->getCode() == '42S02') {
        $statusLogs = [];
    } else {
        throw $e; // Rethrow jika error bukan karena tabel tidak ada
    }
}

// Dapatkan file lampiran
try {
    $stmt = $pdo->prepare("
        SELECT * FROM task_attachments
        WHERE task_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$taskId]);
    $attachments = $stmt->fetchAll();
} catch (PDOException $e) {
    // Jika tabel tidak ada, gunakan array kosong
    if ($e->getCode() == '42S02') {
        $attachments = [];
    } else {
        throw $e; // Rethrow jika error bukan karena tabel tidak ada
    }
}

// Dapatkan platform distribusi jika ada
$platforms = [];
if ($task['category_name'] === 'Distribusi') {
    try {
        $stmt = $pdo->prepare("
            SELECT p.* FROM distribution_platforms dp
            JOIN platforms p ON dp.platform_id = p.id
            WHERE dp.task_id = ?
        ");
        $stmt->execute([$taskId]);
        $platforms = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Jika tabel tidak ada, gunakan array kosong
        if ($e->getCode() == '42S02') {
            $platforms = [];
        } else {
            throw $e; // Rethrow jika error bukan karena tabel tidak ada
        }
    }
}

// Cek apakah task ditolak dan ambil alasan penolakan
$rejectionReason = null;
$rejectedBy = null;

if ($task['status'] === 'rejected') {
    // Coba ambil dari tabel task_rejections dulu
    try {
        $stmt = $pdo->prepare("
            SELECT r.reason, r.created_at, u.name as rejected_by_name 
            FROM task_rejections r
            JOIN users u ON r.rejected_by = u.id
            WHERE r.task_id = ?
            ORDER BY r.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$taskId]);
        $rejection = $stmt->fetch();
        
        if ($rejection) {
            $rejectionReason = $rejection['reason'];
            $rejectedBy = $rejection['rejected_by_name'];
            $rejectionDate = $rejection['created_at'];
        }
    } catch (PDOException $e) {
        // Jika tabel tidak ada, coba ambil dari kolom rejection_reason di tabel tasks
        if ($e->getCode() == '42S02') {
            $rejectionReason = $task['rejection_reason'] ?? null;
            
            // Coba ambil info siapa yang menolak dari task_status_logs
            $stmt = $pdo->prepare("
                SELECT u.name as updated_by_name, l.timestamp
                FROM task_status_logs l
                JOIN users u ON l.updated_by = u.id
                WHERE l.task_id = ? AND l.status = 'rejected'
                ORDER BY l.timestamp DESC
                LIMIT 1
            ");
            $stmt->execute([$taskId]);
            $rejectionLog = $stmt->fetch();
            
            if ($rejectionLog) {
                $rejectedBy = $rejectionLog['updated_by_name'];
                $rejectionDate = $rejectionLog['timestamp'];
            }
        }
    }
}

// Tambahkan kode ini setelah blok try-catch yang mengambil alasan penolakan
if ($task['status'] === 'rejected') {
    // Tambahkan debugging untuk melihat nilai rejection_reason dari tabel tasks
    $stmt = $pdo->prepare("SELECT rejection_reason FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $debugReason = $stmt->fetchColumn();
    
    // Jika alasan penolakan masih kosong, coba ambil langsung dari kolom rejection_reason
    if (empty($rejectionReason) && !empty($debugReason)) {
        $rejectionReason = $debugReason;
    }
    
    // Jika masih kosong juga, coba ambil dari notes di task_status_logs
    if (empty($rejectionReason)) {
        $stmt = $pdo->prepare("
            SELECT notes 
            FROM task_status_logs 
            WHERE task_id = ? AND status = 'rejected'
            ORDER BY timestamp DESC
            LIMIT 1
        ");
        $stmt->execute([$taskId]);
        $logNotes = $stmt->fetchColumn();
        
        if (!empty($logNotes)) {
            $rejectionReason = $logNotes;
        }
    }
}

// Ambil daftar tim produksi untuk reassign
$productionTeam = $pdo->query("
    SELECT id, name 
    FROM users 
    WHERE role = 'production_team' 
    ORDER BY name
")->fetchAll();

// Proses form reassign jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_task'])) {
    $newAssigneeId = $_POST['new_assignee'] ?? 0;
    
    if (!$newAssigneeId || !is_numeric($newAssigneeId)) {
        $_SESSION['error'] = "Pilih tim produksi yang valid";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update assigned_to dan reset status ke waiting_confirmation
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET assigned_to = ?, status = 'waiting_confirmation', rejection_reason = NULL
                WHERE id = ?
            ");
            $stmt->execute([$newAssigneeId, $taskId]);
            
            // Log perubahan status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'waiting_confirmation', ?, 'Reassigned after rejection')
            ");
            $stmt->execute([$taskId, $userId]);
            
            // Kirim notifikasi ke assignee baru
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $newAssigneeId, 
                "Anda mendapat tugas baru: " . $task['title'], 
                "../production/view_task.php?id=" . $taskId
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Task berhasil dialihkan ke tim produksi lain";
            header("Location: view_task.php?id=" . $taskId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Dapatkan riwayat status
$statusHistory = getTaskStatusHistory($taskId);

// Periksa apakah tabel task_revisions ada
$revisions = null;
try {
    // Dapatkan catatan revisi
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as revised_by_name
        FROM task_revisions r
        JOIN users u ON r.revised_by = u.id
        WHERE r.task_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$taskId]);
    $revisions = $stmt;
} catch (PDOException $e) {
    // Jika tabel tidak ada, abaikan error
    if ($e->getCode() == '42S02') {
        $revisions = null;
    } else {
        throw $e; // Rethrow jika error bukan karena tabel tidak ada
    }
}

// Ambil link postingan
$stmt = $pdo->prepare("
    SELECT platform, link 
    FROM task_links 
    WHERE task_id = ?
");
$stmt->execute([$taskId]);
$taskLinks = $stmt->fetchAll();

$pageTitle = "Detail Task: " . $task['title'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h4>
                    <div>
                        <h4><?= getStatusBadge($task['status']) ?></h4>
                    </div>
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
                                <li>
                                    <!-- For created by user -->
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Dibuat Oleh:</strong></label>
                                        <div>
                                            <?= getUserProfilePhotoWithName($task['created_by'], $task['creator_name']) ?>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <!-- For assigned to user -->
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Ditugaskan ke:</strong></label>
                                        <div>
                                            <?= getUserProfilePhotoWithName($task['assigned_to'], $task['assignee_name']) ?>
                                        </div>
                                    </div>
                                </li>
                                <li><strong>Prioritas:</strong> <?= getPriorityBadge($task['priority']) ?></li>
                                <li><strong>Deadline:</strong> 
                                    <span class="<?= (strtotime($task['deadline']) - time() < 86400 && !in_array($task['status'], ['completed', 'uploaded'])) ? 'text-danger' : '' ?>">
                                        <?= date('d M Y - H:i', strtotime($task['deadline'])) ?> WIB
                                    </span>
                                </li>
                                <div class="mb-3">
                                    <strong>Poin Task:</strong>
                                    <span class="badge bg-info"><?= number_format($task['points'], 1) ?></span>
                                </div>
                            </ul>
                        </div>
                    </div>                                 
                    


                    <?php 
            // Get task links
            try {
                $linkStmt = $pdo->prepare("
                    SELECT * FROM task_links 
                    WHERE task_id = ? 
                    ORDER BY created_at DESC
                ");
                $linkStmt->execute([$taskId]);
                $taskLinks = $linkStmt->fetchAll();
                
                // Hanya tampilkan card jika ada link postingan
                if (count($taskLinks) > 0):
            ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Link Postingan</h5>
                        </div>
                        <div class="card-body">
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
                        </div>
                    </div>

                    
                                <?php 
                endif;
            } catch (PDOException $e) {
                // Tidak perlu menampilkan apa-apa jika terjadi error
            }
            ?>
                </div>
            </div>
            
            <?php if ($task['status'] === 'rejected'): ?>
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5><i class="fas fa-times-circle me-2"></i> Task Ditolak</h5>
                </div>
                <div class="card-body">
                    <h6>Alasan Penolakan:</h6>
                    <p><?= nl2br(htmlspecialchars($rejectionReason ?? 'Tidak ada alasan yang diberikan')) ?></p>
                    
                    <?php if ($rejectedBy): ?>
                    <p class="text-muted">
                        Ditolak oleh <?= htmlspecialchars($rejectedBy) ?> 
                        pada <?= date('d M Y H:i', strtotime($rejectionDate)) ?>
                    </p>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h6>Alihkan ke Tim Produksi Lain</h6>
                    <form method="POST">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <select name="new_assignee" class="form-select" required>
                                    <option value="">-- Pilih Tim Produksi --</option>
                                    <?php foreach ($productionTeam as $member): ?>
                                        <?php if ($member['id'] != $task['assigned_to']): ?>
                                        <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="reassign_task" class="btn btn-primary">
                                    <i class="fas fa-user-edit me-2"></i> Alihkan Tugas
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            
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
                                <div class="mt-2">
                                    <p>Diperbarui oleh:</p>
                                    <?= getUserProfilePhotoWithName($history['updated_by'], $history['updated_by_name'], "rounded-circle me-2", "32") ?>
                                </div>
                                <?php if (!empty($history['notes'])): ?>
                                    <div class="mt-2">
                                        <p class="text-muted"><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($history['notes'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Aksi</h5>
                </div>
                <div class="card-body">
                    <?php 
        // Cek apakah task masih bisa diedit (status belum completed)
        $canEdit = ($task['created_by'] == $userId && 
                   !in_array($task['status'], ['completed', 'uploaded', 'ready_for_review']));
        
        if ($canEdit): 
        ?>
            <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-primary btn-block mb-2 w-100">
                <i class="fas fa-edit"></i> Edit Task
            </a>
        <?php endif; ?>
                    
                    <?php if ($task['status'] === 'ready_for_review'): ?>
                        <a href="review_task.php?id=<?= $task['id'] ?>" class="btn btn-success btn-block mb-2 w-100">
                            <i class="fas fa-check"></i> Review Hasil
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($task['category_name'] === 'Distribusi' && ($task['status'] === 'waiting_confirmation' || $task['status'] === 'revision')): ?>
                        <a href="upload_distribution.php?id=<?= $task['id'] ?>" class="btn btn-info btn-block mb-2 w-100">
                            <i class="fas fa-upload"></i> <?= $task['status'] === 'revision' ? 'Upload Revisi Link' : 'Upload Link Distribusi' ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="tasks.php" class="btn btn-secondary btn-block w-100">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
            
            <?php 
            // Get task revisions
            try {
                $revStmt = $pdo->prepare("
                    SELECT r.*, u.name as revised_by_name 
                    FROM task_revisions r 
                    JOIN users u ON r.revised_by = u.id 
                    WHERE r.task_id = ? 
                    ORDER BY r.created_at DESC
                ");
                $revStmt->execute([$taskId]);
                $revisions = $revStmt->fetchAll();
                
                // Hanya tampilkan card jika ada revisi
                if (count($revisions) > 0):
            ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Catatan Revisi</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($revisions as $revision): ?>
                        <div class="card mb-2">
                            <div class="card-body">
                                <p><?= nl2br(htmlspecialchars($revision['note'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php 
                endif;
            } catch (PDOException $e) {
                // Tidak perlu menampilkan apa-apa jika terjadi error
            }
            ?>

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
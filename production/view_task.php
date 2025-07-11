<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'production_team') {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$taskId) {
    $_SESSION['error'] = "ID task tidak valid";
    header('Location: index.php');
    exit;
}

// Ambil data task terlebih dahulu
try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               c.name as category_name, 
               ct.name as content_type_name,
               cp.name as content_pillar_name,
               a.name as account_name,
               u1.name as creator_name,
               u2.name as assigned_to_name
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN content_types ct ON t.content_type_id = ct.id
        LEFT JOIN content_pillars cp ON t.content_pillar_id = cp.id
        LEFT JOIN accounts a ON t.account_id = a.id
        LEFT JOIN users u1 ON t.created_by = u1.id
        LEFT JOIN users u2 ON t.assigned_to = u2.id
        WHERE t.id = ?
    ");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    
    if (!$task) {
        $_SESSION['error'] = "Task tidak ditemukan";
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Cek apakah user adalah assignee utama
$isMainAssignee = ($task['assigned_to'] == $userId);

// Ubah query untuk mengambil daftar tim bantuan yang sudah ditambahkan
$assistantsQuery = $pdo->prepare("
    SELECT ta.id, ta.note, ta.created_at, u.id as user_id, u.name as assistant_name, u.profile_photo, u.role
    FROM task_assistance ta
    JOIN users u ON ta.user_id = u.id
    WHERE ta.task_id = ?
    ORDER BY ta.created_at ASC
");
$assistantsQuery->execute([$taskId]);
$assistants = $assistantsQuery->fetchAll();

// Ambil daftar tim produksi untuk dropdown
try {
    $productionTeamQuery = $pdo->prepare("
        SELECT id, name, role 
        FROM users 
        WHERE (role = 'production_team' OR role = 'content_team') AND id != ? AND active = 1
        ORDER BY role, name
    ");
    $productionTeamQuery->execute([$userId]);
    $productionTeam = $productionTeamQuery->fetchAll();
} catch (PDOException $e) {
    $productionTeam = [];
    error_log("Error fetching production team: " . $e->getMessage());
}

// Tambahkan handler untuk menambah tim bantuan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assistant']) && $isMainAssignee) {
    $assistantId = isset($_POST['assistant_id']) ? (int)$_POST['assistant_id'] : 0;
    $assistantNote = isset($_POST['assistant_note']) ? trim($_POST['assistant_note']) : '';
    
    if ($assistantId > 0) {
        try {
            // Periksa apakah user yang dipilih adalah tim produksi atau konten
            $checkStmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ? AND (role = 'production_team' OR role = 'content_team')");
            $checkStmt->execute([$assistantId]);
            $selectedUser = $checkStmt->fetch();
            
            if ($selectedUser) {
                // Periksa apakah sudah ada sebagai asisten
                $existStmt = $pdo->prepare("SELECT id FROM task_assistance WHERE task_id = ? AND user_id = ?");
                $existStmt->execute([$taskId, $assistantId]);
                
                if ($existStmt->rowCount() == 0) {
                    $pdo->beginTransaction();
                    
                    // Tambahkan tim bantuan
                    $insertStmt = $pdo->prepare("
                        INSERT INTO task_assistance (task_id, user_id, added_by, note, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $insertStmt->execute([$taskId, $assistantId, $userId, $assistantNote]);
                    
                    // Kirim notifikasi ke tim bantuan
                    $roleLabel = $selectedUser['role'] === 'production_team' ? 'tim produksi tambahan' : 'tim konten bantuan';
                    $notifStmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, message, link)
                        VALUES (?, ?, ?)
                    ");
                    $notifStmt->execute([
                        $assistantId, 
                        "Anda ditambahkan sebagai {$roleLabel} untuk task: " . $task['title'], 
                        "../production/view_task.php?id=" . $taskId
                    ]);
                    
                    $pdo->commit();
                    
                    $_SESSION['success'] = "Tim bantuan berhasil ditambahkan";
                    header("Location: view_task.php?id=" . $taskId);
                    exit();
                } else {
                    $_SESSION['error'] = "Tim ini sudah ditambahkan sebelumnya";
                }
            } else {
                $_SESSION['error'] = "User yang dipilih bukan tim produksi atau konten";
            }
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Pilih tim bantuan yang valid";
    }
}

// Handler untuk menghapus tim produksi tambahan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_assistant']) && $isMainAssignee) {
    $assistanceId = isset($_POST['assistance_id']) ? (int)$_POST['assistance_id'] : 0;
    
    if (!$assistanceId) {
        $_SESSION['error'] = "ID tidak valid";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Ambil data assistance untuk notifikasi
            $assistanceStmt = $pdo->prepare("
                SELECT user_id FROM task_assistance WHERE id = ? AND task_id = ?
            ");
            $assistanceStmt->execute([$assistanceId, $taskId]);
            $assistance = $assistanceStmt->fetch();
            
            if (!$assistance) {
                throw new Exception("Data tidak ditemukan");
            }
            
            // Hapus tim produksi tambahan
            $stmt = $pdo->prepare("DELETE FROM task_assistance WHERE id = ? AND task_id = ?");
            $stmt->execute([$assistanceId, $taskId]);
            
            // Kirim notifikasi ke tim produksi yang dihapus
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $assistance['user_id'], 
                "Anda telah dihapus dari tim produksi tambahan untuk task: " . $task['title'], 
                "../production/index.php"
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Tim produksi tambahan berhasil dihapus";
            header("Location: view_task.php?id=" . $taskId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Ambil detail task TERLEBIH DAHULU
$stmt = $pdo->prepare("
    SELECT t.*, 
        c.name as category_name,
        ct.name as content_type_name,
        cp.name as content_pillar_name,
        a.name as account_name,
        creator.name as creator_name,
        assignee.name as assigned_to_name,
        (SELECT tsl.timestamp 
        FROM task_status_logs tsl 
        WHERE tsl.task_id = t.id AND tsl.status = 'uploaded' 
        ORDER BY tsl.timestamp DESC LIMIT 1) as uploaded_at
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN content_pillars cp ON t.content_pillar_id = cp.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE t.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau Anda tidak memiliki akses";
    header("Location: tasks.php");
    exit();
}

// Proses form hapus tim produksi tambahan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_assistant'])) {
    $assistanceId = $_POST['assistance_id'] ?? 0;
    
    if (!$assistanceId || !is_numeric($assistanceId)) {
        $_SESSION['error'] = "ID tidak valid";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Ambil data assistance untuk notifikasi
            $assistanceStmt = $pdo->prepare("
                SELECT assistant_id FROM task_assistance WHERE id = ? AND task_id = ?
            ");
            $assistanceStmt->execute([$assistanceId, $taskId]);
            $assistance = $assistanceStmt->fetch();
            
            if (!$assistance) {
                throw new Exception("Data tidak ditemukan");
            }
            
            // Hapus tim produksi tambahan
            $stmt = $pdo->prepare("DELETE FROM task_assistance WHERE id = ? AND task_id = ?");
            $stmt->execute([$assistanceId, $taskId]);
            
            // Kirim notifikasi ke tim produksi yang dihapus
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $assistance['assistant_id'], 
                "Anda telah dihapus dari tim produksi tambahan untuk task: " . $task['title'], 
                "../production/index.php"
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Tim produksi tambahan berhasil dihapus";
            header("Location: view_task.php?id=" . $taskId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Cek apakah user adalah penerima task utama
$isMainAssignee = ($task['assigned_to'] == $userId);

// Cek apakah ada tracking aktif untuk task ini - SETELAH $task didefinisikan
$activeTracking = null;
if (getUserRole() === 'production_team' && $task['assigned_to'] == $userId) {
    $stmt = $pdo->prepare("
        SELECT tt.*, t.title 
        FROM time_tracking tt
        JOIN tasks t ON tt.task_id = t.id
        WHERE tt.user_id = ? AND tt.end_time IS NULL
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $activeTracking = $stmt->fetch();
}

// Dapatkan riwayat status
$statusHistory = $pdo->prepare("
    SELECT tsl.*, u.name as updated_by_name 
    FROM task_status_logs tsl 
    JOIN users u ON tsl.updated_by = u.id 
    WHERE tsl.task_id = ? 
    ORDER BY tsl.timestamp DESC
");
$statusHistory->execute([$taskId]);

// Dapatkan revisi
try {
    $revisions = $pdo->prepare("
        SELECT r.*, u.name as revised_by_name 
        FROM task_revisions r 
        JOIN users u ON r.revised_by = u.id 
        WHERE r.task_id = ? 
        ORDER BY r.created_at DESC
    ");
    $revisions->execute([$taskId]);
} catch (PDOException $e) {
    // Jika tabel tidak ada, gunakan objek kosong
    if ($e->getCode() == '42S02') {
        $revisions = new PDOStatement();
        $revisions->rowCount = function() { return 0; };
    } else {
        throw $e; // Rethrow jika error bukan karena tabel tidak ada
    }
}

// Get task comments
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as user_name
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

// Get task attachments
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

$pageTitle = "Detail Task: " . $task['title'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h3>
                    <span><?= getStatusBadge($task['status']) ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h4>Deskripsi Task</h4>
                        <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h4>Detail Konten</h4>
                            <ul class="list-unstyled">
                                <li><strong>Kategori:</strong> <?= htmlspecialchars($task['category_name']) ?></li>
                                <li><strong>Jenis Konten:</strong> <?= htmlspecialchars($task['content_type_name']) ?></li>
                                <li><strong>Pilar Konten:</strong> <?= htmlspecialchars($task['content_pillar_name']) ?></li>
                                <li><strong>Akun Media:</strong> <?= htmlspecialchars($task['account_name']) ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4>Informasi Task</h4>
                            <ul class="list-unstyled">
                                <li>
                                    <div class="mb-3">
                                        <label class="form-label">Dibuat oleh:</label>
                                        <div>
                                            <?= getUserProfilePhotoWithName($task['created_by'], $task['creator_name']) ?>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="mb-3">
                                        <label class="form-label">Ditugaskan ke:</label>
                                        <div>
                                            <?= getUserProfilePhotoWithName($task['assigned_to'], $task['assigned_to_name']) ?>
                                        </div>
                                    </div>
                                </li>
                                <li><strong>Prioritas:</strong> <?= getPriorityBadge($task['priority']) ?></li>
                                <li><strong>Deadline:</strong> 
                                    <span class="<?= (strtotime($task['deadline']) - time() < 86400 && !in_array($task['status'], ['completed', 'uploaded'])) ? 'text-danger' : '' ?>">
                                        <?= date('d M Y - H:i', strtotime($task['deadline'])) ?> WIB
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    
                </div>
            </div>

            <div class="card mb-4">
    <div class="card-header">
        <h5>Preview Hasil Pekerjaan</h5>
    </div>
    <div class="card-body">
        <?php
        // Decode JSON file paths
        $filePaths = json_decode($task['file_path'], true);
        if (!is_array($filePaths)) {
            // Fallback untuk format lama (single file)
            $filePaths = [$task['file_path']];
        }
        ?>
        
        <?php if (!empty($filePaths)): ?>
            <div class="list-group">
                <?php foreach ($filePaths as $index => $filePath): ?>
                    <?php
                    $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
                    $fileExt = strtolower($fileExt);
                    $fileName = basename($filePath);
                    $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif']);
                    $isVideo = in_array($fileExt, ['mp4', 'mov', 'avi']);
                    $isPdf = $fileExt === 'pdf';
                    $fileUrl = '../uploads/' . $filePath;
                    ?>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <?php if ($isImage): ?>
                                <i class="fas fa-image text-success me-2"></i>
                            <?php elseif ($isVideo): ?>
                                <i class="fas fa-video text-primary me-2"></i>
                            <?php elseif ($isPdf): ?>
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                            <?php else: ?>
                                <i class="fas fa-file text-secondary me-2"></i>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($fileName) ?></span>
                        </div>
                        <div>
                            <?php if ($isImage || $isVideo || $isPdf): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary me-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#previewModal"
                                        data-file-path="<?= htmlspecialchars($filePath) ?>"
                                        data-file-type="<?= $isImage ? 'image' : ($isVideo ? 'video' : 'pdf') ?>"
                                        data-file-name="<?= htmlspecialchars($fileName) ?>">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                            <?php endif; ?>                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Belum ada file yang diupload.</p>
        <?php endif; ?>
    </div>
</div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Riwayat Status</h5>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <!-- For task status history -->
<?php foreach ($statusHistory as $status): ?>
<div class="timeline-item">
    <div class="timeline-marker"></div>
    <div class="timeline-content">
        <div class="d-flex align-items-center mb-1">
            <?= getUserProfilePhotoWithName($status['updated_by'], $status['updated_by_name'], "rounded-circle me-2", "24") ?>
            <span class="badge bg-<?= getStatusColor($status['status']) ?> ms-2">
                <?= getStatusLabel($status['status']) ?>
            </span>
        </div>
        <p class="small text-muted"><?= date('d M Y, H:i', strtotime($status['timestamp'])) ?></p>
        <?php if (!empty($status['notes'])): ?>
            <p class="mt-2"><?= htmlspecialchars($status['notes']) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
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
                    
                    <?php if ($task['status'] === 'waiting_confirmation'): ?>

                        <button class="btn btn-success" id="startTrackingBtn" data-task-id="<?php echo $taskId; ?>">
                                            <i class="fas fa-play-circle me-2"></i> Terima Task
                                        </button>
                        
                        <a href="reject_task.php?id=<?= $task['id'] ?>" class="btn btn-danger btn-block">
                            <i class="fas fa-times"></i> Tolak Task
                        </a>
                    <?php elseif ($task['status'] === 'in_production' || $task['status'] === 'revision'): ?>
                        <a href="upload_result.php?id=<?= $task['id'] ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-upload"></i> Upload Hasil
                        </a>
                    <?php endif; ?>
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
            
            <?php if (isset($task['file_path']) && !empty($task['file_path'])): ?>


<!-- Modal Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="previewContent"></div>
            </div>
            <div class="modal-footer">
                <a id="downloadLink" href="#" class="btn btn-primary" download>
                    <i class="fas fa-download"></i> Download
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
            
            <?php if (getUserRole() === 'production_team' && $task['assigned_to'] == $userId): ?>
                <?php if ($task['status'] === 'in_production' || $task['status'] === 'revision' || $task['status'] === 'waiting_confirmation'): ?>
                    
                <?php endif; ?>
            <?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Tim Bantuan Tambahan</h5>
    </div>
    <div class="card-body">
        <?php if ($isMainAssignee && $task['status'] !== 'completed'): ?>
            <form method="post" class="mb-4">
                <div class="mb-3">
                    <label for="assistant_id" class="form-label">Pilih Tim Bantuan</label>
                    <select name="assistant_id" id="assistant_id" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <?php 
                        $currentRole = '';
                        foreach ($productionTeam as $member): 
                            if ($currentRole !== $member['role']):
                                if ($currentRole !== '') echo '</optgroup>';
                                $roleLabel = $member['role'] === 'production_team' ? 'Tim Produksi' : 'Tim Konten';
                                echo '<optgroup label="' . $roleLabel . '">';
                                $currentRole = $member['role'];
                            endif;
                        ?>
                            <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                        <?php endforeach; ?>
                        <?php if ($currentRole !== '') echo '</optgroup>'; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="assistant_note" class="form-label">Catatan (Opsional)</label>
                    <textarea name="assistant_note" id="assistant_note" class="form-control" 
                              placeholder="Jelaskan alasan penambahan tim bantuan ini"></textarea>
                </div>
                <div class="d-grid">
                    <button type="submit" name="add_assistant" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Tim Bantuan
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <!-- Daftar tim bantuan yang sudah ditambahkan -->
        <?php if (count($assistants) > 0): ?>
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
                        <?php if ($isMainAssignee && $task['status'] !== 'completed'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="assistance_id" value="<?= $assistant['id'] ?>">
                                <button type="submit" name="remove_assistant" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Yakin ingin menghapus tim bantuan ini?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mt-3">Belum ada tim bantuan tambahan.</p>
        <?php endif; ?>
    </div>
</div>







            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Time Tracking</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Cek apakah ada tracking yang aktif
                    $stmt = $pdo->prepare("
                        SELECT id, start_time 
                        FROM time_tracking 
                        WHERE task_id = ? AND user_id = ? AND end_time IS NULL
                        ORDER BY start_time DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$taskId, $userId]);
                    $activeTracking = $stmt->fetch();
                    
                    if ($activeTracking): 
                    ?>
                        <div class="alert alert-info">
                            <strong>Sedang mengerjakan task ini</strong>
                            <a><?= displayTimeTrackingTimer($activeTracking['start_time'])?></a>
                        </div>
                        
                        
                    <?php else: ?>
                        <?php if (in_array($task['status'], ['waiting_confirmation', 'in_production', 'revision'])): ?>
                        
                        <?php else: ?>
                        <div class="alert alert-secondary">
                            <i class="fas fa-info-circle"></i> Time tracking tidak tersedia untuk status task ini.
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h6>Riwayat Pengerjaan</h6>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT id, start_time, end_time, notes
                        FROM time_tracking
                        WHERE task_id = ? AND user_id = ? AND end_time IS NOT NULL
                        ORDER BY start_time DESC
                        LIMIT 5
                    ");
                    $stmt->execute([$taskId, $userId]);
                    $trackingHistory = $stmt->fetchAll();
                    
                    if (count($trackingHistory) > 0):
                    ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Mulai</th>
                                        <th>Durasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trackingHistory as $history): ?>
                                        <?php
                                        $start = new DateTime($history['start_time']);
                                        $end = new DateTime($history['end_time']);
                                        $interval = $start->diff($end);
                                        $duration = sprintf(
                                            '%02d:%02d:%02d',
                                            $interval->h + ($interval->d * 24),
                                            $interval->i,
                                            $interval->s
                                        );
                                        ?>
                                        <tr>
                                            <td><?= date('H:i', strtotime($history['start_time'])) ?> WIB</td>
                                            <td><?= $duration ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small">Belum ada riwayat pengerjaan</p>
                    <?php endif; ?>
                </div>
            </div>
            
            
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 50px;
    list-style: none;
}
.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 20px;
    width: 2px;
    background: #e9ecef;
}
.timeline-item {
    position: relative;
    margin-bottom: 20px;
}
.timeline-item:before {
    content: '';
    position: absolute;
    left: -40px;
    top: 5px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary);
    border: 3px solid white;
}
.timeline-item.completed:before {
    background: var(--success);
}
.timeline-item.revision:before {
    background: var(--danger);
}
.timeline-info {
    font-size: 0.8rem;
    color: #6c757d;
}
.timeline-content {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}
</style>

<?php if (getUserRole() === 'production_team'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk memperbarui durasi tracking
    function updateDuration() {
        const durationElement = document.getElementById('currentDuration');
        if (durationElement) {
            fetch('../api/tracking_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=status'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.activeTracking) {
                    durationElement.textContent = data.elapsed;
                }
            });
        }
    }
    
    // Update durasi setiap menit
    setInterval(updateDuration, 60000);
    
    // Mulai tracking
    const startTrackingBtn = document.getElementById('startTrackingBtn');
    if (startTrackingBtn) {
        startTrackingBtn.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            
            fetch('../api/tracking_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=start&task_id=${taskId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    }
    
    // Stop tracking
    const stopTrackingBtn = document.getElementById('stopTrackingBtn');
    if (stopTrackingBtn) {
        stopTrackingBtn.addEventListener('click', function() {
            const trackingId = this.getAttribute('data-tracking-id');
            
            fetch('../api/tracking_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=stop&tracking_id=${trackingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    }
    
    // Switch tracking
    const switchTrackingBtn = document.getElementById('switchTrackingBtn');
    if (switchTrackingBtn) {
        switchTrackingBtn.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            
            fetch('../api/tracking_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=start&task_id=${taskId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    }
    
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

    // Timer untuk time tracking
    function updateTimers() {
        document.querySelectorAll('.time-tracking-timer').forEach(function(timerElement) {
            const startTime = new Date(timerElement.dataset.start);
            const now = new Date();
            const diff = Math.floor((now - startTime) / 1000); // selisih dalam detik
            
            const hours = Math.floor(diff / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;
            
            const timerDisplay = timerElement.querySelector('.timer-display');
            timerDisplay.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        });
    }

    // Update timer setiap detik
    if (document.querySelector('.time-tracking-timer')) {
        updateTimers();
        setInterval(updateTimers, 1000);
    }

    // Event listener untuk tombol mulai tracking
    document.getElementById('startTracking')?.addEventListener('click', function() {
        fetch('../api/tracking_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=start&task_id=${taskId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Terjadi kesalahan saat memulai tracking');
        });
    });

    // Event listener untuk tombol hentikan tracking
    document.getElementById('stopTracking')?.addEventListener('click', function() {
        const trackingId = this.dataset.trackingId;
        
        fetch('../api/tracking_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=stop&tracking_id=${trackingId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Terjadi kesalahan saat menghentikan tracking');
        });
    });

    // Handle preview modal
    const previewModal = document.getElementById('previewModal');
    if (previewModal) {
        previewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const filePath = button.getAttribute('data-file-path');
            const fileType = button.getAttribute('data-file-type');
            const fileName = button.getAttribute('data-file-name');
            
            const modalTitle = previewModal.querySelector('.modal-title');
            const previewContent = document.getElementById('previewContent');
            const downloadLink = document.getElementById('downloadLink');
            
            modalTitle.textContent = `Preview: ${fileName}`;
            downloadLink.href = `../uploads/${filePath}`;
            downloadLink.download = fileName;
            
            // Clear previous content
            previewContent.innerHTML = '';
            
            // Build file URL without encoding
            const fileUrl = `../uploads/${filePath}`;
            
            if (fileType === 'image') {
                previewContent.innerHTML = `
                    <img src="${fileUrl}" class="img-fluid" alt="${fileName}" style="max-height: 70vh;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkdhZ2FsIG1lbXVhdCBnYW1iYXI8L3RleHQ+PC9zdmc+';">
                `;
            } else if (fileType === 'video') {
                const videoExt = filePath.split('.').pop().toLowerCase();
                previewContent.innerHTML = `
                    <video controls class="img-fluid" style="max-height: 70vh;">
                        <source src="${fileUrl}" type="video/${videoExt}">
                        Browser Anda tidak mendukung pemutaran video.
                    </video>
                `;
            } else if (fileType === 'pdf') {
                previewContent.innerHTML = `
                    <iframe src="${fileUrl}" style="width: 100%; height: 70vh;" frameborder="0"></iframe>
                `;
            } else {
                previewContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> File tidak dapat ditampilkan secara langsung. Silakan download untuk melihat.
                    </div>
                `;
            }
        });
    }
});
</script>
<?php endif; ?>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11"></div>

<?php include '../includes/footer.php'; ?>
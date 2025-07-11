<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Pastikan user sudah login dan memiliki hak akses
redirectIfNotLoggedIn();
if (!in_array(getUserRole(), ['creative_director'])) {
    header('Location: ../index.php');
    exit;
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
    WHERE t.id = ? AND t.status = 'uploaded'
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau tidak dalam status menunggu verifikasi";
    header('Location: tasks.php');
    exit();
}

// Proses verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    try {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Harap berikan rating untuk task ini");
        }
        
        $pdo->beginTransaction();

        // Update status task dan tambahkan rating
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', verified_at = NOW(), rating = ? WHERE id = ?");
        $stmt->execute([$rating, $taskId]);

        // Log perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by)
            VALUES (?, 'completed', ?)
        ");
        $stmt->execute([$taskId, $userId]);

        // Kirim notifikasi ke marketing team
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $task['created_by'],
            "Task telah selesai dan diverifikasi: " . $task['title'],
            "../marketing/view_task.php?id=" . $taskId
        ]);

        // Kirim notifikasi ke production team
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $task['assigned_to'],
            "Task telah selesai dan diverifikasi: " . $task['title'],
            "../production/view_task.php?id=" . $taskId
        ]);

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

        $pdo->commit();

        $_SESSION['success'] = "Task berhasil diverifikasi dan diselesaikan";
        header("Location: tasks.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Proses penolakan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject'])) {
    $rejectReason = trim($_POST['reject_reason'] ?? '');
    
    if (empty($rejectReason)) {
        $_SESSION['error'] = "Alasan penolakan tidak boleh kosong";
    } else {
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
            $stmt->execute([$taskId, $rejectReason, $userId]);

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

            $_SESSION['success'] = "Task dikembalikan untuk revisi";
            header("Location: tasks.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$pageTitle = "Verifikasi Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Verifikasi Task</h4>
                    <span class="badge bg-info">Menunggu Verifikasi</span>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
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
                            <th>Kategori</th>
                            <td><?= htmlspecialchars($task['category_name']) ?></td>
                        </tr>
                        <?php if (!empty($task['content_type_name'])): ?>
                        <tr>
                            <th>Jenis Konten</th>
                            <td><?= htmlspecialchars($task['content_type_name']) ?></td>
                        </tr>
                        <?php endif; ?>
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
                            <th>Dibuat Oleh</th>
                            <td><?= htmlspecialchars($task['created_by_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Dikerjakan Oleh</th>
                            <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                        </tr>
                    </table>
                    
                    <h5 class="mb-3">Hasil Pekerjaan</h5>
                    <?php if (!empty($task['file_path'])): ?>
                        <?php 
                        $filePath = '../uploads/' . $task['file_path'];
                        $fileUrl = '../uploads/' . $task['file_path'];
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
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Rating Kualitas</label>
                            <div class="rating">
                                <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="Sangat Baik">5 stars</label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="Baik">4 stars</label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="Cukup">3 stars</label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="Kurang">2 stars</label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="Sangat Kurang">1 star</label>
                            </div>
                            <div class="clearfix mb-3"></div>
                            <div class="form-text">Berikan rating untuk kualitas hasil pekerjaan</div>
                        </div>
                        
                        <button type="submit" name="verify" class="btn btn-success btn-block mb-3 w-100">
                            <i class="fas fa-check-circle"></i> Verifikasi & Selesaikan
                        </button>
                        
                        <button type="button" class="btn btn-danger btn-block w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fas fa-times-circle"></i> Tolak & Minta Revisi
                        </button>
                        
                        <hr>
                        
                        <a href="tasks.php" class="btn btn-secondary btn-block w-100">
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
                        <label for="reject_reason" class="form-label">Alasan Penolakan</label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="4" required></textarea>
                        <div class="form-text">Jelaskan secara detail mengapa task ini ditolak dan apa yang perlu diperbaiki.</div>
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

<style>
.rating {
    float: left;
}
.rating:not(:checked) > input {
    position: absolute;
    clip: rect(0,0,0,0);
}
.rating:not(:checked) > label {
    float: right;
    width: 1em;
    padding: 0 .1em;
    overflow: hidden;
    white-space: nowrap;
    cursor: pointer;
    font-size: 200%;
    line-height: 1.2;
    color: #ddd;
}
.rating:not(:checked) > label:before {
    content: '★ ';
}
.rating > input:checked ~ label {
    color: #f70;
}
.rating:not(:checked) > label:hover,
.rating:not(:checked) > label:hover ~ label {
    color: gold;
}
.rating > input:checked + label:hover,
.rating > input:checked + label:hover ~ label,
.rating > input:checked ~ label:hover,
.rating > input:checked ~ label:hover ~ label,
.rating > label:hover ~ input:checked ~ label {
    color: #ea0;
}
</style>

<?php include '../includes/footer.php'; ?>
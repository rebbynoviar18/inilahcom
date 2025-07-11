<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header('Location: ../index.php');
    exit();
}

$taskId = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];

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
    WHERE t.id = ? AND t.assigned_to = ? AND t.status = 'in_production' AND c.name = 'Distribusi'
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau bukan task distribusi yang sedang dikerjakan";
    header('Location: tasks.php');
    exit();
}

// Ambil platform yang dipilih untuk task ini
$stmt = $pdo->prepare("
    SELECT p.id, p.name
    FROM task_platforms tp
    JOIN platforms p ON tp.platform_id = p.id
    WHERE tp.task_id = ?
");
$stmt->execute([$taskId]);
$selectedPlatforms = $stmt->fetchAll();

if (empty($selectedPlatforms)) {
    $_SESSION['error'] = "Tidak ada platform yang dipilih untuk task ini";
    header('Location: tasks.php');
    exit();
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Validasi input
        $links = $_POST['links'] ?? [];
        $platformIds = $_POST['platform_ids'] ?? [];
        
        if (count($links) !== count($platformIds)) {
            throw new Exception("Data tidak valid");
        }
        
        // Validasi semua link diisi
        foreach ($links as $link) {
            if (empty($link)) {
                throw new Exception("Semua link harus diisi");
            }
            
            // Validasi format URL
            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                throw new Exception("Format URL tidak valid");
            }
        }
        
        // Simpan link untuk setiap platform
        for ($i = 0; $i < count($links); $i++) {
            $stmt = $pdo->prepare("
                INSERT INTO task_links (task_id, platform_id, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$taskId, $platformIds[$i], $links[$i]]);
        }
        
        // Update status task
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'ready_for_review' WHERE id = ?");
        $stmt->execute([$taskId]);
        
        // Catat perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
            VALUES (?, 'ready_for_review', ?, 'Link distribusi telah disubmit')
        ");
        $stmt->execute([$taskId, $userId]);
        
        // Kirim notifikasi ke marketing
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $task['created_by'],
            "Task distribusi siap direview",
            "view_task.php?id=$taskId"
        ]);
        
        $pdo->commit();
        $_SESSION['success'] = "Link distribusi berhasil disubmit";
        header('Location: tasks.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

$pageTitle = "Submit Link Distribusi";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Submit Link Distribusi</h4>
                    <a href="tasks.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Masukkan link untuk setiap platform yang telah Anda posting.
                    </div>
                    
                    <h5 class="mb-3">Detail Task</h5>
                    <table class="table table-bordered mb-4">
                        <tr>
                            <th width="30%">Judul</th>
                            <td><?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></td>
                        </tr>
                        <tr>
                            <th>Akun Media</th>
                            <td><?= htmlspecialchars($task['account_name']) ?></td>
                        </tr>
                    </table>
                    
                    <form method="POST">
                        <h5 class="mb-3">Link Distribusi</h5>
                        
                        <?php foreach ($selectedPlatforms as $platform): ?>
                            <div class="mb-3">
                                <label for="link_<?= $platform['id'] ?>" class="form-label">
                                    <?= htmlspecialchars($platform['name']) ?> <span class="text-danger">*</span>
                                </label>
                                <input type="url" class="form-control" id="link_<?= $platform['id'] ?>" 
                                       name="links[]" placeholder="https://" required>
                                <input type="hidden" name="platform_ids[]" value="<?= $platform['id'] ?>">
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Link
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const urlInputs = document.querySelectorAll('input[type="url"]');
        let isValid = true;
        
        urlInputs.forEach(input => {
            if (!input.value) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Semua link harus diisi');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
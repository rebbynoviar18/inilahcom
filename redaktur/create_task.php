<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'redaktur_pelaksana') {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil ID akun Inilah.com
$accountStmt = $pdo->prepare("SELECT id FROM accounts WHERE name = 'Inilah.com' LIMIT 1");
$accountStmt->execute();
$inilahAccountId = $accountStmt->fetchColumn();

if (!$inilahAccountId) {
    $error = "Akun Inilah.com tidak ditemukan di database";
}

// Ambil daftar kategori
$categoriesStmt = $pdo->query("SELECT id, name FROM categories WHERE name = 'Produksi'");
$categories = $categoriesStmt->fetchAll();

// Ambil kategori produksi ID
$produksiCategoryId = 0;
foreach ($categories as $category) {
    if ($category['name'] === 'Produksi') {
        $produksiCategoryId = $category['id'];
        break;
    }
}

// Ambil ID pilar konten "Redaksi"
$redaksiPillarStmt = $pdo->prepare("SELECT id FROM content_pillars WHERE name = 'Redaksi' LIMIT 1");
$redaksiPillarStmt->execute();
$redaksiPillarId = $redaksiPillarStmt->fetchColumn();

// Jika pilar Redaksi tidak ditemukan, coba buat
if (!$redaksiPillarId) {
    try {
        $stmt = $pdo->prepare("INSERT INTO content_pillars (name, category_id) VALUES ('Redaksi', ?)");
        $stmt->execute([$produksiCategoryId]);
        $redaksiPillarId = $pdo->lastInsertId();
    } catch (Exception $e) {
        $error .= "Gagal membuat pilar konten Redaksi: " . $e->getMessage();
    }
}

// Ambil tipe konten berdasarkan kategori produksi
$contentTypesStmt = $pdo->prepare("
    SELECT id, name FROM content_types 
    WHERE category_id = ? 
    ORDER BY name
");
$contentTypesStmt->execute([$produksiCategoryId]);
$contentTypes = $contentTypesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $categoryId = (int)$_POST['category_id'];
        $contentTypeId = isset($_POST['content_type_id']) ? (int)$_POST['content_type_id'] : null;
        $deadline = $_POST['deadline'];
        $priority = $_POST['priority'];

        // Validasi input
        if (empty($title) || empty($description) || empty($categoryId) || empty($contentTypeId) || 
            empty($deadline) || empty($priority)) {
            throw new Exception("Semua field wajib diisi");
        }

        // Mulai transaksi
        $pdo->beginTransaction();

        // Insert task dengan akun Inilah.com dan pilar konten Redaksi
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, account_id, category_id, content_type_id, content_pillar_id, 
                              deadline, priority, created_by, assigned_to, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'waiting_head_confirmation', NOW())
        ");
        $stmt->execute([
            $title, $description, $inilahAccountId, $categoryId, $contentTypeId, $redaksiPillarId,
            $deadline, $priority, $userId, $userId  // Menggunakan ID user yang sama untuk assigned_to
        ]);
        $taskId = $pdo->lastInsertId();

        // Catat status log
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by, timestamp)
            VALUES (?, 'waiting_head_confirmation', ?, NOW())
        ");
        $stmt->execute([$taskId, $userId]);

        // Setelah task berhasil dibuat dan sebelum commit transaction

        // Kirim notifikasi ke Creative Director
        $cdStmt = $pdo->prepare("SELECT id, name, whatsapp_number FROM users WHERE role = 'creative_director' AND active = 1");
        $cdStmt->execute();
        $creativeDirectors = $cdStmt->fetchAll();

        // Ambil nama jenis konten
        $contentTypeStmt = $pdo->prepare("SELECT name FROM content_types WHERE id = ?");
        $contentTypeStmt->execute([$contentTypeId]);
        $contentTypeName = $contentTypeStmt->fetchColumn() ?: 'Tidak ditentukan';

        foreach ($creativeDirectors as $cd) {
            // Kirim notifikasi sistem
            sendNotification($cd['id'], "Ada task baru dari Redaktur Pelaksana yang memerlukan persetujuan", "view_task.php?id=$taskId");
            
            // Kirim notifikasi WhatsApp jika ada nomor WhatsApp
            if (!empty($cd['whatsapp_number'])) {
                // Format pesan WhatsApp
                $message = "Halo *{$cd['name']}*,\n\n";
                $message .= "Ada task baru dari Redaktur Pelaksana yang memerlukan persetujuan Kamu:\n\n";
                $message .= "*Judul:*\n{$title}\n\n";
                $message .= "*Jenis Konten:* {$contentTypeName}\n";
                $message .= "*Deadline:* " . date('d M Y - H:i', strtotime($deadline)) . " WIB\n\n";
                $message .= "Silakan login ke dashboard untuk melihat detail task.\n";
                
                // Buat URL untuk Creative Director
                $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/";
                $taskUrl = $baseUrl . "admin/view_task.php?id=" . $taskId;
                $message .= $taskUrl;
                
                // Kirim notifikasi WhatsApp
                require_once '../config/whatsapp.php';
                sendWhatsAppNotification($cd['whatsapp_number'], $message);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Task berhasil dibuat!";
        header('Location: tasks.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Buat Task Produksi";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Buat Task Baru</h4>
                    <a href="tasks.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= $success ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="createTaskForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">Judul Task <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            <div class="form-text">Jelaskan secara detail apa yang dibutuhkan untuk task ini.</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Kategori Konten <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= ($category['name'] === 'Produksi') ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="content_type_id" class="form-label">Tipe Konten <span class="text-danger">*</span></label>
                                <select class="form-select" id="content_type_id" name="content_type_id" required>
                                    <option value="">-- Pilih Tipe Konten --</option>
                                    <?php foreach ($contentTypes as $contentType): ?>
                                        <option value="<?= $contentType['id'] ?>"><?= htmlspecialchars($contentType['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priority" class="form-label">Prioritas <span class="text-danger">*</span></label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="">-- Pilih Prioritas --</option>
                                <option value="low">Rendah</option>
                                <option value="medium" selected>Sedang</option>
                                <option value="high">Tinggi</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
                            <div class="form-text">Waktu hanya tersedia antara 09:00-21:00 dengan interval 30 menit</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Kirim Permintaan Task
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
    const deadlineInput = document.getElementById('deadline');
    
    // Get current date and time
    const now = new Date();
    let deadline = new Date(now);
    
    // Add 4 hours to current time
    deadline.setHours(now.getHours() + 4);
    
    // Check if the resulting time is within working hours (09:00-21:00)
    if (deadline.getHours() < 9) {
        // If before 9:00, set to same day at 09:00
        deadline.setHours(9, 0, 0, 0);
    } else if (deadline.getHours() >= 21) {
        // If after or at 21:00, set to next day at 09:00
        deadline.setDate(deadline.getDate() + 1);
        deadline.setHours(9, 0, 0, 0);
    } else {
        // Round to nearest 30 minutes
        let minutes = deadline.getMinutes();
        deadline.setMinutes(Math.ceil(minutes / 30) * 30);
        
        // If minutes became 60, adjust the hour
        if (deadline.getMinutes() === 60) {
            deadline.setMinutes(0);
            deadline.setHours(deadline.getHours() + 1);
            
            // Check again if we've gone past 21:00
            if (deadline.getHours() >= 21) {
                deadline.setDate(deadline.getDate() + 1);
                deadline.setHours(9, 0, 0, 0);
            }
        }
    }
    
    // Format for datetime-local input
    const year = deadline.getFullYear();
    const month = String(deadline.getMonth() + 1).padStart(2, '0');
    const day = String(deadline.getDate()).padStart(2, '0');
    const hours = String(deadline.getHours()).padStart(2, '0');
    const minutes = String(deadline.getMinutes()).padStart(2, '0');
    
    deadlineInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    
    // Add event listener to enforce time constraints when user changes the value
    deadlineInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        
        // Enforce working hours (09:00-21:00)
        let hours = selectedDate.getHours();
        let minutes = selectedDate.getMinutes();
        
        if (hours < 9) hours = 9;
        if (hours >= 21) {
            // Move to next day at 9:00 if after hours
            selectedDate.setDate(selectedDate.getDate() + 1);
            hours = 9;
            minutes = 0;
        }
        
        // Round to nearest 30 minutes
        minutes = Math.round(minutes / 30) * 30;
        
        // Handle case where minutes become 60
        if (minutes === 60) {
            minutes = 0;
            hours++;
            
            // Check again if we've gone past 21:00
            if (hours >= 21) {
                selectedDate.setDate(selectedDate.getDate() + 1);
                hours = 9;
            }
        }
        
        // Update the date object
        selectedDate.setHours(hours);
        selectedDate.setMinutes(minutes);
        
        // Format back to datetime-local
        const year = selectedDate.getFullYear();
        const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
        const day = String(selectedDate.getDate()).padStart(2, '0');
        const hoursStr = String(hours).padStart(2, '0');
        const minutesStr = String(minutes).padStart(2, '0');
        
        this.value = `${year}-${month}-${day}T${hoursStr}:${minutesStr}`;
    });
});
</script>

<?php include '../includes/footer.php'; ?>
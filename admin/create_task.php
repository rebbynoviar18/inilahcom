<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';


// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}
$userId = $_SESSION['user_id'];

// Dapatkan data untuk dropdown - filter hanya Daily Content dan Program
$categories = $pdo->query("SELECT * FROM categories WHERE name IN ('Daily Content', 'Program', 'Produksi') ORDER BY name")->fetchAll();
$accounts = $pdo->query("SELECT * FROM accounts ORDER BY name")->fetchAll();
$productionTeam = $pdo->query("SELECT * FROM users WHERE role = 'production_team' ORDER BY name")->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $categoryId = $_POST['category_id'];
    $contentTypeId = $_POST['content_type_id'];
    $contentPillarId = $_POST['content_pillar_id'];
    $accountId = $_POST['account_id'];
    $assignedTo = $_POST['assigned_to'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];
    
    // Validasi input
    if (empty($title) || empty($description) || empty($categoryId) || empty($contentTypeId) || 
        empty($contentPillarId) || empty($accountId) || empty($assignedTo) || empty($priority) || empty($deadline)) {
        $_SESSION['error'] = "Semua field harus diisi";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Ubah status langsung menjadi waiting_confirmation, bukan draft
            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, category_id, content_type_id, content_pillar_id, 
                                  account_id, created_by, assigned_to, priority, deadline, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'waiting_confirmation', NOW())
            ");
            $stmt->execute([
                $title, $description, $categoryId, $contentTypeId, $contentPillarId, 
                $accountId, $userId, $assignedTo, $priority, $deadline
            ]);
            
            $taskId = $pdo->lastInsertId();
            
            // Catat status awal dengan benar
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, timestamp, notes) 
                VALUES (?, 'waiting_confirmation', ?, NOW(), 'Task dibuat dan menunggu konfirmasi')
            ");
            $stmt->execute([$taskId, $userId]);
            
            // Kirim notifikasi ke tim produksi
            sendNotification($assignedTo, "Anda memiliki task baru yang menunggu konfirmasi", "view_task.php?id=$taskId");
            
            $pdo->commit();
            
            $_SESSION['success'] = "Task berhasil dibuat dan dikirim ke tim produksi";
            header("Location: view_task.php?id=$taskId");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Gagal membuat task: " . $e->getMessage();
        }
    }
}

$pageTitle = "Buat Task Baru";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Buat Task Baru</h4>
                    <a href="tasks.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error'] ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Judul Task</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="content_type_id" class="form-label">Tipe Konten</label>
                                <select class="form-select" id="content_type_id" name="content_type_id" required>
                                    <option value="">-- Pilih Kategori terlebih dahulu --</option>
                                    <!-- Akan diisi dengan AJAX -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="content_pillar_id" class="form-label">Pilar Konten</label>
                                <select class="form-select" id="content_pillar_id" name="content_pillar_id" required>
                                    <option value="">-- Pilih Kategori terlebih dahulu --</option>
                                    <!-- Akan diisi dengan AJAX -->
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="account_id" class="form-label">Akun</label>
                                <select class="form-select" id="account_id" name="account_id" required>
                                    <option value="">-- Pilih Akun --</option>
                                    <?php foreach ($accounts as $account): ?>
                                        <option value="<?= $account['id'] ?>" <?= strtolower($account['name']) === 'inilah.com' ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($account['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Ditugaskan Kepada</label>
                            <select name="assigned_to" id="assigned_to" class="form-select" required>
                                <option value="">-- Pilih Tim Produksi --</option>
                                <?php foreach ($productionTeam as $member): ?>
                                    <option value="<?= $member['id'] ?>" data-photo="<?= getUserProfilePhoto($member['id']) ?>">
                                        <?= htmlspecialchars($member['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tambahkan div untuk menampilkan preview foto profil -->
                        <div class="mb-3" id="assigneePreview" style="display: none;">
                            <label class="form-label">Preview Tim Produksi</label>
                            <div class="d-flex align-items-center p-2 border rounded">
                                <img id="assigneePhoto" src="" alt="Foto Profil" class="rounded-circle me-2" width="40" height="40">
                                <span id="assigneeName"></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Prioritas</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low">Rendah</option>
                                    <option value="medium" selected>Sedang</option>
                                    <option value="high">Tinggi</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
    <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
    <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
    <div class="form-text">Waktu hanya tersedia antara 09:00-22:00 dengan interval 30 menit</div>
</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Buat Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load content types and pillars based on selected category
    const categorySelect = document.getElementById('category_id');
    const contentTypeSelect = document.getElementById('content_type_id');
    const contentPillarSelect = document.getElementById('content_pillar_id');
    
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        
        // Reset selects if no category is selected
        if (!categoryId) {
            contentTypeSelect.innerHTML = '<option value="">-- Pilih Kategori terlebih dahulu --</option>';
            contentPillarSelect.innerHTML = '<option value="">-- Pilih Kategori terlebih dahulu --</option>';
            return;
        }
        
        // Load content types
        fetch(`../api/get_content_types.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">-- Pilih Tipe Konten --</option>';
                data.forEach(type => {
                    options += `<option value="${type.id}">${type.name}</option>`;
                });
                contentTypeSelect.innerHTML = options;
            })
            .catch(error => {
                console.error('Error loading content types:', error);
            });
        
        // Load content pillars
        fetch(`../api/get_content_pillars.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">-- Pilih Pilar Konten --</option>';
                data.forEach(pillar => {
                    options += `<option value="${pillar.id}">${pillar.name}</option>`;
                });
                contentPillarSelect.innerHTML = options;
            })
            .catch(error => {
                console.error('Error loading content pillars:', error);
            });
    });
    
    // Set deadline to 30 minutes from now
    const deadlineInput = document.getElementById('deadline');
    const now = new Date();
    now.setMinutes(now.getMinutes() + 30); // Add 30 minutes
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset()); // Adjust for timezone
    deadlineInput.value = now.toISOString().slice(0, 16);
    
    // Add click handler to make the field editable when clicked
    deadlineInput.addEventListener('click', function() {
        this.removeAttribute('readonly');
    });
    
    // Ensure minimum time is now
    const minTime = new Date();
    minTime.setMinutes(minTime.getMinutes() - minTime.getTimezoneOffset());
    deadlineInput.min = minTime.toISOString().slice(0, 16);
});

// Tambahkan script JavaScript untuk menampilkan preview
document.addEventListener('DOMContentLoaded', function() {
    const assignedToSelect = document.getElementById('assigned_to');
    const assigneePreview = document.getElementById('assigneePreview');
    const assigneePhoto = document.getElementById('assigneePhoto');
    const assigneeName = document.getElementById('assigneeName');
    
    assignedToSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            assigneePhoto.src = selectedOption.dataset.photo;
            assigneeName.textContent = selectedOption.textContent.trim();
            assigneePreview.style.display = 'block';
        } else {
            assigneePreview.style.display = 'none';
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const deadlineInput = document.getElementById('deadline');
    
    if (deadlineInput) {
        // Tambahkan event listener untuk menampilkan datepicker saat mengklik di area input
        deadlineInput.addEventListener('click', function() {
            // Teknik untuk memaksa browser menampilkan datepicker
            // dengan menggunakan showPicker() API jika tersedia
            if (typeof this.showPicker === 'function') {
                this.showPicker();
            } else {
                // Fallback untuk browser yang tidak mendukung showPicker()
                // Simulasi klik pada ikon kalender dengan memfokuskan dan mengirim event
                this.focus();
                
                // Untuk beberapa browser yang memerlukan trigger tambahan
                const event = new MouseEvent('mousedown', {
                    view: window,
                    bubbles: true,
                    cancelable: true
                });
                this.dispatchEvent(event);
            }
        });
        
        // Set nilai default ke hari ini jam 09:00
        const today = new Date();
        today.setHours(9, 0, 0, 0);
        
        // Format tanggal untuk input datetime-local
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        deadlineInput.value = `${year}-${month}-${day}T09:00`;
        
        // Tambahkan event listener untuk perubahan input
        deadlineInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            
            // Dapatkan jam dan menit
            let hours = selectedDate.getHours();
            let minutes = selectedDate.getMinutes();
            
            // Periksa apakah waktu berada dalam rentang yang diizinkan (09:00-22:00)
            if (hours < 9) hours = 9;
            if (hours > 22 || (hours === 22 && minutes > 0)) hours = 22;
            
            // Bulatkan menit ke kelipatan 30 terdekat
            minutes = Math.round(minutes / 30) * 30;
            if (minutes === 60) {
                minutes = 0;
                hours++;
                // Periksa lagi jika jam melebihi batas setelah pembulatan
                if (hours > 22) hours = 22;
            }
            
            // Perbarui nilai input
            selectedDate.setHours(hours);
            selectedDate.setMinutes(minutes);
            
            // Format tanggal kembali ke format datetime-local
            const year = selectedDate.getFullYear();
            const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
            const day = String(selectedDate.getDate()).padStart(2, '0');
            const hoursStr = String(hours).padStart(2, '0');
            const minutesStr = String(minutes).padStart(2, '0');
            
            this.value = `${year}-${month}-${day}T${hoursStr}:${minutesStr}`;
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
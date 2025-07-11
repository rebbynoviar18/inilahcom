<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';


// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director' && getUserRole() !== 'content_team') {
    header("Location: ../index.php");
    exit();
}
$userId = $_SESSION['user_id'];

// Dapatkan data untuk dropdown - filter hanya Daily Content dan Program
$categories = $pdo->query("SELECT * FROM categories WHERE name IN ('Daily Content', 'Program') ORDER BY name")->fetchAll();
$accounts = $pdo->query("SELECT * FROM accounts ORDER BY name")->fetchAll();
$productionTeam = $pdo->query("SELECT * FROM users WHERE role = 'production_team' ORDER BY name")->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']) ?: null; // Buat description menjadi null jika kosong
    $sourceLink = trim($_POST['source_link']) ?: null; // Field baru untuk link sumber
    $categoryId = $_POST['category_id'];
    $contentTypeId = $_POST['content_type_id'];
    $contentPillarId = $_POST['content_pillar_id'];
    $accountId = $_POST['account_id'];
    $assignedTo = $_POST['assigned_to'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];

    // Validasi input - tambahkan source_link ke validasi wajib
    if (empty($title) || empty($sourceLink) || empty($categoryId) || empty($contentTypeId) || 
        empty($contentPillarId) || empty($accountId) || empty($assignedTo) || empty($priority) || empty($deadline)) {
        $_SESSION['error'] = "Semua field wajib harus diisi";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Tambahkan source_link ke query INSERT
            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, source_link, category_id, content_type_id, content_pillar_id, 
                                  account_id, created_by, assigned_to, priority, deadline, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'waiting_confirmation', NOW())
            ");
            $stmt->execute([
                $title, $description, $sourceLink, $categoryId, $contentTypeId, $contentPillarId, 
                $accountId, $userId, $assignedTo, $priority, $deadline
            ]);

            // Ambil ID task yang baru dibuat
            $taskId = $pdo->lastInsertId();

            // Catat status awal dengan benar
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, timestamp, notes) 
                VALUES (?, 'waiting_confirmation', ?, NOW(), 'Task dibuat dan menunggu konfirmasi')
            ");
            $stmt->execute([$taskId, $userId]);

            // Ambil data user yang ditugaskan (tim produksi)
            $userStmt = $pdo->prepare("SELECT name, whatsapp_number, role FROM users WHERE id = ?");
            $userStmt->execute([$assignedTo]);
            $user = $userStmt->fetch();

            // Ambil nama jenis konten
            $contentTypeStmt = $pdo->prepare("SELECT name FROM content_types WHERE id = ?");
            $contentTypeStmt->execute([$contentTypeId]);
            $contentTypeName = $contentTypeStmt->fetchColumn() ?: 'Tidak ditentukan';

            // Kirim notifikasi WhatsApp jika user memiliki nomor WhatsApp
            if ($user && !empty($user['whatsapp_number'])) {
                // Format pesan WhatsApp
                $message = "Halo *{$user['name']}*,\n\n";
                $message .= "Kamu memiliki task baru dari Tim Konten yang perlu dikerjakan:\n\n";
                $message .= "*Judul:*\n{$title}\n\n";                
                $message .= "*Deadline:* " . date('d M Y - H:i', strtotime($deadline)) . " WIB\n";
                $message .= "*Jenis Konten:* {$contentTypeName}\n\n";                
                    // Tambahkan deskripsi jika ada
                    if (!empty($description)) {
                        $message .= "*Deskripsi:*\n{$description}\n\n";
                    }
                $message .= "Silakan login ke dashboard untuk melihat detail task.\n";
                
                // Buat URL berdasarkan role user
                $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/";
                $taskUrl = $baseUrl;
                
                switch ($user['role']) {
                    case 'production_team':
                        $taskUrl .= "production/view_task.php?id=" . $taskId;
                        break;
                    case 'content_team':
                        $taskUrl .= "content/view_task.php?id=" . $taskId;
                        break;
                    default:
                        $taskUrl .= "view_task.php?id=" . $taskId;
                        break;
                }
                
                $message .= $taskUrl;
                
                // Kirim notifikasi WhatsApp
                require_once '../config/whatsapp.php';
                sendWhatsAppNotification($user['whatsapp_number'], $message);
            }

            // Kirim notifikasi ke tim produksi
            sendNotification($assignedTo, "Anda memiliki task baru yang menunggu konfirmasi", "view_task.php?id=$taskId");

            // Setelah task berhasil dibuat dan transaksi di-commit
            if ($taskId) {
                try {
                    // Ambil nama pembuat task
                    $creatorStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                    $creatorStmt->execute([$userId]);
                    $creatorName = $creatorStmt->fetchColumn();
                    
                    // Ambil nama jenis konten
                    $contentTypeStmt = $pdo->prepare("SELECT name FROM content_types WHERE id = ?");
                    $contentTypeStmt->execute([$contentTypeId]);
                    $contentTypeName = $contentTypeStmt->fetchColumn() ?: 'Tidak ditentukan';
                    
                    // Fungsi untuk mendapatkan nama bulan dalam bahasa Indonesia
                    function getIndonesianMonth($month) {
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'JULI', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        return $months[(int)$month];
                    }
                    
                    // Format pesan untuk grup dengan bulan dalam bahasa Indonesia
                    $bulan = getIndonesianMonth(date('n', strtotime($deadline)));
                    $tanggal = date('d', strtotime($deadline));
                    $tahun = date('Y', strtotime($deadline));
                    
                    $message = "*KONTEN {$tanggal} {$bulan} {$tahun}*\n\n";
                    $message .= "*Judul:*\n{$title}\n\n";
                    
                    // Tambahkan informasi user yang ditugaskan                    
                    $message .= "*PIC:* {$creatorName}\n";
                    $message .= "*Editor:* {$user['name']}\n";
                    $message .= "*Jenis Konten:* {$contentTypeName}\n";
                    $message .= "*Deadline:* " . date('H:i', strtotime($deadline)) . " WIB\n\n";
                    $message .= "*Sumber:*\n{$sourceLink}\n\n";
                    
                    // Tambahkan deskripsi jika ada
                    if (!empty($description)) {
                        $message .= "*Deskripsi:*\n{$description}\n\n";
                    }
                    
                    $message .= "*Task Detail:*\n";

                // Buat URL berdasarkan role user
                $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/";
                $taskUrl = $baseUrl;
                
                switch ($user['role']) {
                    case 'production_team':
                        $taskUrl .= "production/view_task.php?id=" . $taskId;
                        break;
                    case 'content_team':
                        $taskUrl .= "content/view_task.php?id=" . $taskId;
                        break;
                    default:
                        $taskUrl .= "view_task.php?id=" . $taskId;
                        break;
                }

                $message .= $taskUrl;
                    
                    // Kirim notifikasi ke grup WhatsApp
                    require_once '../includes/functions/whatsapp_functions.php';
                    $groupResult = sendWhatsAppGroupNotification($message);
                    
                    // Log hasil pengiriman
                    error_log("Notifikasi grup WhatsApp untuk task #$taskId: " . ($groupResult ? "Berhasil" : "Gagal"));
                    
                    // Tambahkan informasi ke session jika perlu
                    if ($groupResult) {
                        $_SESSION['group_notification'] = "Notifikasi grup WhatsApp berhasil dikirim";
                    }
                } catch (Exception $e) {
                    error_log("Error saat mengirim notifikasi grup: " . $e->getMessage());
                }
            }

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
                            <label for="title" class="form-label">Judul Task <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            <div class="form-text">Opsional</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="source_link" class="form-label">Link Sumber <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="source_link" name="source_link" placeholder="https://inilah.com/..." required>
                            <div class="form-text">Masukkan URL lengkap termasuk http:// atau https://</div>
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
                        
                        <div class="mb-3">
                            <label for="points" class="form-label">Poin Task</label>
                            <input type="number" class="form-control" id="points" name="points" value="1.0" step="0.1" min="0.1" readonly>
                            <div class="form-text">Poin akan dihitung otomatis berdasarkan kategori dan jenis task</div>
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

// Fungsi untuk mengambil poin task berdasarkan kategori dan jenis
function updateTaskPoints() {
    const category = document.getElementById('category_id').options[document.getElementById('category_id').selectedIndex].text;
    const contentType = document.getElementById('content_type_id').options[document.getElementById('content_type_id').selectedIndex].text;
    const team = 'content_team'; // Sesuaikan dengan halaman yang sedang diakses
    
    if (category && contentType) {
        fetch('../api/get_task_points.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                team: team,
                category: category,
                task_type: contentType
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('points').value = data.points;
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Panggil fungsi saat kategori atau jenis task berubah
document.getElementById('category_id').addEventListener('change', updateTaskPoints);
document.getElementById('content_type_id').addEventListener('change', updateTaskPoints);

// Panggil sekali saat halaman dimuat
if (document.getElementById('category_id').value && document.getElementById('content_type_id').value) {
    updateTaskPoints();
}

document.addEventListener('DOMContentLoaded', function() {
    const deadlineInput = document.getElementById('deadline');
    
    if (deadlineInput) {
        // Set nilai default ke 30 menit dari sekarang
        const now = new Date();
        now.setMinutes(now.getMinutes() + 30);
        
        // Bulatkan menit ke kelipatan 30 terdekat
        let hours = now.getHours();
        let minutes = now.getMinutes();
        
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
        
        // Format tanggal untuk input datetime-local
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hoursStr = String(hours).padStart(2, '0');
        const minutesStr = String(minutes).padStart(2, '0');
        
        deadlineInput.value = `${year}-${month}-${day}T${hoursStr}:${minutesStr}`;
        
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

document.addEventListener('DOMContentLoaded', function() {
    const deadlineInput = document.getElementById('deadline');
    
    // Tambahkan event listener untuk menampilkan datepicker saat mengklik di area input
    if (deadlineInput) {
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
    }
});
</script>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/whatsapp.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'marketing_team') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Ambil data kategori (Produksi, Distribusi, dan Publikasi)
$stmtCategories = $pdo->query("SELECT * FROM categories WHERE name IN ('Produksi', 'Distribusi', 'Publikasi') ORDER BY name");
$categories = $stmtCategories->fetchAll();

// Set pilar konten 'Marketing' secara default
$stmtMarketingPillar = $pdo->query("SELECT id FROM content_pillars WHERE name = 'Marketing' LIMIT 1");
$marketingPillarId = $stmtMarketingPillar->fetchColumn();

if (!$marketingPillarId) {
    // Jika pilar Marketing belum ada, buat baru
    $stmtCreatePillar = $pdo->prepare("INSERT INTO content_pillars (name, category_id) VALUES ('Marketing', (SELECT id FROM categories WHERE name = 'Produksi' LIMIT 1))");
    $stmtCreatePillar->execute();
    $marketingPillarId = $pdo->lastInsertId();
}

// Ambil data akun media
$stmtAccounts = $pdo->query("SELECT * FROM accounts ORDER BY name");
$accounts = $stmtAccounts->fetchAll();

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $categoryId = (int)$_POST['category_id'];
        $contentTypeId = (int)$_POST['content_type_id'];
        $accountId = (int)$_POST['account_id'];
        $priority = $_POST['priority'];
        $deadline = $_POST['deadline'];
        
        // Cek kategori untuk menentukan status awal
        $stmtCheckCategory = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmtCheckCategory->execute([$categoryId]);
        $categoryName = $stmtCheckCategory->fetchColumn();
        
        // Perbaikan pada bagian penentuan status
        if ($categoryName === 'Publikasi') {
            // Status selalu waiting_redaktur_confirmation untuk kategori Publikasi
            $status = 'waiting_redaktur_confirmation';
        } else {
            $status = isset($_POST['submit_for_approval']) ? 'waiting_head_confirmation' : 'draft';
        }
        
        // Tetapkan assigned_to ke user yang membuat task (marketing) untuk sementara
        $assignedTo = $userId; // Default ke pembuat task (marketing)
        
        if (empty($title) || empty($description) || empty($deadline)) {
            throw new Exception("Semua field wajib diisi");
        }
        
        // Default values
        $contentPillarId = $marketingPillarId; // Default ke Marketing pillar
        $clientName = null;
        $filePath = null;
        
        // Proses khusus untuk kategori Distribusi atau Publikasi
        if ($categoryName === 'Distribusi' || $categoryName === 'Publikasi') {
            // Validasi khusus untuk kategori Distribusi dan Publikasi
            if (empty($_POST['uploaded_files']) && !isset($_POST['content_type_id'])) {
                throw new Exception("File konten wajib diupload atau jenis konten harus dipilih untuk kategori $categoryName");
            }
            
            if (empty($_POST['content_pillar_id'])) {
                throw new Exception("Pilar konten wajib dipilih untuk kategori $categoryName");
            }
            
            if (empty($_POST['client_name'])) {
                throw new Exception("Nama klien wajib diisi untuk kategori $categoryName");
            }
            
            // Set nilai dari form untuk kategori Distribusi dan Publikasi
            $contentPillarId = (int)$_POST['content_pillar_id'];
            $clientName = trim($_POST['client_name']);
            
            // Proses file yang diupload
            $filePaths = [];
            if (!empty($_POST['uploaded_files'])) {
                $uploadedFiles = json_decode($_POST['uploaded_files'], true);
                if (is_array($uploadedFiles)) {
                    foreach ($uploadedFiles as $file) {
                        // Hanya ambil path file saja, sesuai format yang digunakan di production/upload_result.php
                        $filePaths[] = $file['path'];
                    }
                }
            }

            // Simpan file_path sebagai JSON array sederhana, bukan array objek
            $filePath = !empty($filePaths) ? json_encode($filePaths) : null;
        }
        
        // Simpan task
        $stmt = $pdo->prepare("
            INSERT INTO tasks (
                title, description, category_id, content_type_id, content_pillar_id, 
                account_id, priority, deadline, status, created_by, assigned_to, file_path, client_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $title,
            $description,
            $categoryId,
            $contentTypeId,
            $contentPillarId,
            $accountId,
            $priority,
            $deadline,
            $status,
            $userId,
            $assignedTo,
            $filePath,
            $clientName
        ]);
        
        $taskId = $pdo->lastInsertId();
        
        // Catat status log
        $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $taskId, 
            $status, 
            $userId, 
            $status === 'draft' ? 'Task dibuat sebagai draft' : 'Task dikirim untuk persetujuan'
        ]);
        
        // Kirim notifikasi berdasarkan status
        if ($status === 'waiting_head_confirmation') {
            // Notifikasi ke Creative Director untuk kategori non-Publikasi
            $stmt = $pdo->prepare("SELECT id, name, whatsapp_number FROM users WHERE role = 'creative_director' AND active = 1");
            $stmt->execute();
            $creativeDirectors = $stmt->fetchAll();
            
            foreach ($creativeDirectors as $cd) {
                // Notifikasi sistem
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                $stmt->execute([
                    $cd['id'],
                    "Task baru dari marketing memerlukan persetujuan Anda",
                    "view_task.php?id=$taskId"
                ]);
                
                // Notifikasi WhatsApp
                if (!empty($cd['whatsapp_number'])) {
                    // Ambil data pembuat task
                    $creatorStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                    $creatorStmt->execute([$userId]);
                    $creatorName = $creatorStmt->fetchColumn();
                    
                    // Ambil nama pilar konten
                    $pillarStmt = $pdo->prepare("SELECT name FROM content_pillars WHERE id = ?");
                    $pillarStmt->execute([$contentPillarId]);
                    $pillarName = $pillarStmt->fetchColumn() ?: 'N/A';
                    
                    // Ambil nama tipe konten
                    $typeStmt = $pdo->prepare("SELECT name FROM content_types WHERE id = ?");
                    $typeStmt->execute([$contentTypeId]);
                    $contentTypeName = $typeStmt->fetchColumn() ?: 'N/A';
                    
                    $message = "Halo *{$cd['name']}*,\n\n";
                    $message .= "Ada task baru dari marketing yang memerlukan persetujuan Kamu:\n\n";
                    $message .= "*Judul:*\n{$title}\n\n";
                    $message .= "*Jenis Konten:* {$contentTypeName}\n";
                    $message .= "*Pilar Konten:* {$pillarName}\n";
                    $message .= "*Dibuat oleh:* {$creatorName}\n";
                    $message .= "*Deadline:* " . date('d M Y - H:i', strtotime($deadline)) . " WIB\n\n";
                    $message .= "Silakan login ke dashboard untuk menyetujui dan menugaskan task ini.\n";
                    $message .= "http://" . $_SERVER['HTTP_HOST'] . "/creative/admin/view_task.php?id=$taskId";
                    
                    sendWhatsAppNotification($cd['whatsapp_number'], $message);
                }
            }
        } elseif ($status === 'waiting_redaktur_confirmation') {
            // Notifikasi ke Redaktur Pelaksana untuk kategori Publikasi
            $stmt = $pdo->prepare("SELECT id, name, whatsapp_number FROM users WHERE role = 'redaktur_pelaksana' AND active = 1");
            $stmt->execute();
            $redakturs = $stmt->fetchAll();
            
            foreach ($redakturs as $redaktur) {
                // Notifikasi sistem
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                $stmt->execute([
                    $redaktur['id'],
                    "Task publikasi baru dari marketing memerlukan persetujuan Anda",
                    "../redaktur/view_task.php?id=$taskId"
                ]);
                
                // Notifikasi WhatsApp
                if (!empty($redaktur['whatsapp_number'])) {
                    // Ambil data pembuat task
                    $creatorStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                    $creatorStmt->execute([$userId]);
                    $creatorName = $creatorStmt->fetchColumn();
                    
                    // Ambil nama pilar konten
                    $pillarStmt = $pdo->prepare("SELECT name FROM content_pillars WHERE id = ?");
                    $pillarStmt->execute([$contentPillarId]);
                    $pillarName = $pillarStmt->fetchColumn() ?: 'N/A';
                    
                    // Ambil nama tipe konten
                    $typeStmt = $pdo->prepare("SELECT name FROM content_types WHERE id = ?");
                    $typeStmt->execute([$contentTypeId]);
                    $contentTypeName = $typeStmt->fetchColumn() ?: 'N/A';
                    
                    $message = "Halo *{$redaktur['name']}*,\n\n";
                    $message .= "Ada task publikasi baru dari marketing yang memerlukan persetujuan Kamu:\n\n";
                    $message .= "*Judul:*\n{$title}\n\n";
                    $message .= "*Jenis Artikel:* {$contentTypeName}\n";
                    $message .= "*Tipe Artikel:* {$pillarName}\n";
                    $message .= "*Dibuat oleh:* {$creatorName}\n";
                    $message .= "*Deadline:* " . date('d M Y - H:i', strtotime($deadline)) . " WIB\n\n";
                    $message .= "Silakan login ke dashboard untuk menyetujui dan menugaskan task ini.\n";
                    $message .= "http://" . $_SERVER['HTTP_HOST'] . "/redaktur/view_task.php?id=$taskId";
                    
                    sendWhatsAppNotification($redaktur['whatsapp_number'], $message);
                }
            }
        }
        
        $_SESSION['success'] = $status === 'draft' ? "Task berhasil disimpan sebagai draft" : "Task berhasil dikirim untuk persetujuan";
        header('Location: tasks.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal membuat task: " . $e->getMessage();
    }
}

$pageTitle = "Buat Task Baru";
include '../includes/header.php';
?>
<style>
.file-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.file-upload-area:hover {
    border-color: #007bff;
    background-color: #e3f2fd;
}

.file-upload-area.dragover {
    border-color: #007bff;
    background-color: #e3f2fd;
    transform: scale(1.02);
}

.file-upload-area i {
    color: #6c757d;
    margin-bottom: 15px;
}

.file-upload-area p {
    color: #6c757d;
    margin-bottom: 0;
    font-size: 14px;
}

.file-preview-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.file-preview-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.file-preview-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.file-preview-icon {
    width: 100%;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    color: #6c757d;
    font-size: 2rem;
}

.delete-file {
    position: absolute;
    top: 5px;
    right: 5px;
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 50%;
    background-color: rgba(220, 53, 69, 0.9);
    border: none;
    color: white;
}

.delete-file:hover {
    background-color: #dc3545;
}

.file-info {
    padding: 10px;
    background-color: white;
}

.file-name {
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 2px;
    word-break: break-word;
}

.file-size {
    font-size: 11px;
    color: #6c757d;
}

.upload-progress {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 5px;
}

.upload-progress-bar {
    height: 100%;
    background-color: #007bff;
    transition: width 0.3s ease;
}
</style>
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
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="createTaskForm" enctype="multipart/form-data">
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
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="content_type_id" class="form-label">Tipe Konten <span class="text-danger">*</span></label>
                                <select class="form-select" id="content_type_id" name="content_type_id" required disabled>
                                    <option value="">-- Pilih Kategori Terlebih Dahulu --</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="account_id" class="form-label">Akun Media <span class="text-danger">*</span></label>
                                <select class="form-select" id="account_id" name="account_id" required>
                                    <option value="">-- Pilih Akun Media --</option>
                                    <?php foreach ($accounts as $account): ?>
                                        <option value="<?= $account['id'] ?>" <?= strtolower($account['name']) === 'inilah.com' ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($account['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Prioritas <span class="text-danger">*</span></label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="">-- Pilih Prioritas --</option>
                                    <option value="low">Rendah</option>
                                    <option value="medium" selected>Sedang</option>
                                    <option value="high">Tinggi</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div id="distributionFieldsSection" class="mb-3" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="content_pillar_id" class="form-label">Jenis Konten <span class="text-danger">*</span></label>
                                    <select class="form-select" id="content_pillar_id" name="content_pillar_id">
                                        <option value="">-- Pilih Kategori Terlebih Dahulu --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="client_name" class="form-label">Nama Klien <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="client_name" name="client_name">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
                            <div class="form-text">Waktu hanya tersedia antara 09:00-22:00 dengan interval 30 menit</div>
                        </div>
                        
                        <div id="fileUploadSection" class="mb-4" style="display: none;">
                            <label class="form-label">File Konten <span class="text-danger">*</span></label>
                            
                            <div class="file-upload-container">
                                <div class="file-upload-area" onclick="document.getElementById('file').click()">
                                    <i class="fas fa-cloud-upload-alt fa-3x"></i>
                                    <p class="mt-2 mb-1"><strong>Klik untuk memilih file</strong> atau drag & drop di sini</p>
                                    <p class="small text-muted">Format: JPG, PNG, GIF, PDF, MP4, MOV, AVI, DOC, DOCX (Max: 10MB per file)</p>
                                </div>
                                <input type="file" class="d-none" id="file" name="file[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.mp4,.mov,.avi,.doc,.docx">
                                
                                <div id="filePreviewContainer" class="row g-3 mt-3">
                                    <!-- File previews akan ditampilkan di sini -->
                                </div>
                                
                                <input type="hidden" name="uploaded_files" id="uploadedFiles" value="">
                            </div>
                        </div>                 
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="submit_for_approval" class="btn btn-primary">
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
    const fileInput = document.getElementById('file');
    const fileUploadArea = document.querySelector('.file-upload-area');
    const filePreviewContainer = document.getElementById('filePreviewContainer');
    const uploadedFilesInput = document.getElementById('uploadedFiles');
    let uploadedFiles = [];

    // File input change handler
    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });

    // Drag and drop handlers
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            if (validateFile(file)) {
                uploadFile(file);
            }
        });
    }

    function validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'application/pdf', 'video/mp4', 'video/quicktime', 'video/x-msvideo',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (file.size > maxSize) {
            alert(`File ${file.name} terlalu besar. Maksimal 10MB.`);
            return false;
        }

        if (!allowedTypes.includes(file.type)) {
            alert(`Format file ${file.name} tidak didukung.`);
            return false;
        }

        return true;
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        // Create preview card
        const fileId = 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const previewCard = createPreviewCard(file, fileId);
        filePreviewContainer.appendChild(previewCard);

        // Upload file
        fetch('../api/upload_temp_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update uploaded files array
                uploadedFiles.push({
                    id: fileId,
                    name: file.name,
                    path: data.filePath,
                    size: file.size,
                    type: file.type
                });
                
                // Update hidden input
                uploadedFilesInput.value = JSON.stringify(uploadedFiles);
                
                // Update progress bar
                const progressBar = previewCard.querySelector('.upload-progress-bar');
                if (progressBar) {
                    progressBar.style.width = '100%';
                    setTimeout(() => {
                        previewCard.querySelector('.upload-progress').style.display = 'none';
                    }, 500);
                }
            } else {
                // Remove preview card on error
                previewCard.remove();
                alert('Gagal mengupload file: ' + data.message);
            }
        })
        .catch(error => {
            previewCard.remove();
            alert('Terjadi kesalahan saat mengupload file');
            console.error('Upload error:', error);
        });
    }

    function createPreviewCard(file, fileId) {
        const col = document.createElement('div');
        col.className = 'col-md-3 col-sm-4 col-6';
        
        const isImage = file.type.startsWith('image/');
        const fileSize = formatFileSize(file.size);
        
        col.innerHTML = `
            <div class="file-preview-card position-relative">
                <button type="button" class="btn btn-danger delete-file" onclick="removeFile('${fileId}')">
                    <i class="fas fa-times"></i>
                </button>
                
                ${isImage ? 
                    `<img src="${URL.createObjectURL(file)}" class="file-preview-image" alt="${file.name}">` :
                    `<div class="file-preview-icon">
                        <i class="fas ${getFileIcon(file.type)}"></i>
                    </div>`
                }
                
                <div class="file-info">
                    <div class="file-name" title="${file.name}">${truncateFileName(file.name, 20)}</div>
                    <div class="file-size">${fileSize}</div>
                    <div class="upload-progress">
                        <div class="upload-progress-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        `;
        
        return col;
    }

    function getFileIcon(fileType) {
        if (fileType.startsWith('video/')) return 'fa-video';
        if (fileType === 'application/pdf') return 'fa-file-pdf';
        if (fileType.includes('word')) return 'fa-file-word';
        return 'fa-file';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function truncateFileName(fileName, maxLength) {
        if (fileName.length <= maxLength) return fileName;
        const extension = fileName.split('.').pop();
        const nameWithoutExt = fileName.substring(0, fileName.lastIndexOf('.'));
        const truncatedName = nameWithoutExt.substring(0, maxLength - extension.length - 4) + '...';
        return truncatedName + '.' + extension;
    }

    // Global function to remove file
    window.removeFile = function(fileId) {
        // Remove from uploaded files array
        uploadedFiles = uploadedFiles.filter(file => file.id !== fileId);
        uploadedFilesInput.value = JSON.stringify(uploadedFiles);
        
        // Remove preview card
        const fileCard = document.querySelector(`[onclick="removeFile('${fileId}')"]`).closest('.col-md-3, .col-sm-4, .col-6');
        if (fileCard) {
            fileCard.remove();
        }
    };

    // Rest of your existing JavaScript code...
    const categorySelect = document.getElementById('category_id');
    const contentTypeSelect = document.getElementById('content_type_id');
    const fileUploadSection = document.getElementById('fileUploadSection');
    const distributionFieldsSection = document.getElementById('distributionFieldsSection');
    
    // Panggil updateFieldsVisibility() saat halaman dimuat
    updateFieldsVisibility();
    
    // Perbaikan fungsi updateFieldsVisibility() untuk memastikan file upload selalu muncul untuk kategori Publikasi
    function updateFieldsVisibility() {
        const categoryId = categorySelect.value;
        const categoryName = categorySelect.options[categorySelect.selectedIndex]?.text || '';
        
        // Selalu sembunyikan dulu, lalu tampilkan jika diperlukan
        fileUploadSection.style.display = 'none';
        distributionFieldsSection.style.display = 'none';
        
        // Tampilkan field distribusi untuk kategori Distribusi dan Publikasi
        if (categoryName === 'Distribusi' || categoryName === 'Publikasi') {
            distributionFieldsSection.style.display = 'block';
            
            // Untuk kategori Publikasi, selalu tampilkan file upload
            if (categoryName === 'Publikasi') {
                fileUploadSection.style.display = 'block';
                const fileInput = document.getElementById('file');
                if (fileInput) {
                    fileInput.setAttribute('required', 'required');
                }
            } else {
                // Untuk kategori Distribusi, tampilkan file upload berdasarkan tipe konten
                const contentTypeName = contentTypeSelect.options[contentTypeSelect.selectedIndex]?.text || '';
                
                if (contentTypeName.toLowerCase().includes('video') || 
                    contentTypeName.toLowerCase().includes('image') || 
                    contentTypeName.toLowerCase().includes('gambar') || 
                    contentTypeName.toLowerCase().includes('foto')) {
                    fileUploadSection.style.display = 'block';
                    const fileInput = document.getElementById('file');
                    if (fileInput) {
                        fileInput.setAttribute('required', 'required');
                    }
                } else if (contentTypeSelect.value) {
                    // Jika tipe konten dipilih tapi bukan tipe yang memerlukan file
                    const fileInput = document.getElementById('file');
                    if (fileInput) {
                        fileInput.removeAttribute('required');
                    }
                }
            }
        }
        
        console.log('Visibility updated:', {
            category: categoryName,
            fileUploadVisible: fileUploadSection.style.display,
            distributionFieldsVisible: distributionFieldsSection.style.display
        });
    }
    
    // Function to load content types based on selected category
    function loadContentTypes(categoryId) {
        if (!categoryId) {
            contentTypeSelect.innerHTML = '<option value="">-- Pilih Kategori Terlebih Dahulu --</option>';
            contentTypeSelect.disabled = true;
            return;
        }
        
        contentTypeSelect.disabled = true;
        contentTypeSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`../api/get_content_types.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                contentTypeSelect.innerHTML = '<option value="">-- Pilih Tipe Konten --</option>';
                data.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.id;
                    option.textContent = type.name;
                    contentTypeSelect.appendChild(option);
                });
                contentTypeSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                contentTypeSelect.innerHTML = '<option value="">Error loading content types</option>';
                contentTypeSelect.disabled = true;
            });
    }
    
    // Function to load content pillars based on selected category
    function loadContentPillars(categoryId) {
        const contentPillarSelect = document.getElementById('content_pillar_id');
        
        if (!categoryId) {
            contentPillarSelect.innerHTML = '<option value="">-- Pilih Kategori Terlebih Dahulu --</option>';
            contentPillarSelect.disabled = true;
            return;
        }
        
        contentPillarSelect.disabled = true;
        contentPillarSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`../api/get_content_pillars.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                contentPillarSelect.innerHTML = '<option value="">-- Pilih Jenis Konten --</option>';
                data.forEach(pillar => {
                    const option = document.createElement('option');
                    option.value = pillar.id;
                    option.textContent = pillar.name;
                    contentPillarSelect.appendChild(option);
                });
                contentPillarSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                contentPillarSelect.innerHTML = '<option value="">Error loading content pillars</option>';
                contentPillarSelect.disabled = true;
            });
    }
    
    // Event listener for category change
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        
        // Load content types for the selected category
        loadContentTypes(categoryId);
        
        // Update fields visibility
        updateFieldsVisibility();
        
        // Load content pillars if needed
        if (this.options[this.selectedIndex]?.text === 'Distribusi' || 
            this.options[this.selectedIndex]?.text === 'Publikasi') {
            loadContentPillars(categoryId);
        }
    });
    
    // Event listener for content type change
    contentTypeSelect.addEventListener('change', function() {
        // Update fields visibility when content type changes
        updateFieldsVisibility();
    });
    
    // Inisialisasi fields visibility
    updateFieldsVisibility();
});

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
</script>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

if (getUserRole() !== 'production_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$taskId = $_GET['id'] ?? 0;

// Verifikasi task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND assigned_to = ?");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau Anda tidak memiliki akses";
    header("Location: tasks.php");
    exit();
}

// Mulai tracking jika belum ada tracking aktif
$activeTracking = getActiveTracking($userId);
if (!$activeTracking) {
    $stmt = $pdo->prepare("
        INSERT INTO time_tracking (task_id, user_id, start_time, is_auto)
        VALUES (?, ?, NOW(), 1)
    ");
    $stmt->execute([$taskId, $userId]);
}

// Cek apakah task sudah dalam status yang tepat
if (!in_array($task['status'], ['in_production', 'revision'])) {
    $_SESSION['error'] = "Task tidak dalam status yang dapat diupload";
    header("Location: view_task.php?id=" . $taskId);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Proses upload file
    $notes = trim($_POST['notes'] ?? '');
    $uploadedLink = trim($_POST['uploaded_link'] ?? '');
    
    // Validasi file
    if (!isset($_FILES['task_files']) || empty($_FILES['task_files']['name'][0])) {
        $_SESSION['error'] = "Silakan pilih minimal satu file untuk diupload";
    } else {
        $files = $_FILES['task_files'];
        $uploadDir = '../uploads/tasks/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadedFiles = [];
        $allFilesUploaded = true;
        
        foreach ($files['name'] as $key => $fileName) {
            if (empty($fileName)) continue;
            
            $fileTmpName = $files['tmp_name'][$key];
            $fileSize = $files['size'][$key];
            $fileType = $files['type'][$key];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validasi ukuran file (max 100MB)
            $maxSize = 100 * 1024 * 1024; // 100MB
            if ($fileSize > $maxSize) {
                $_SESSION['error'] = "Ukuran file $fileName terlalu besar (maksimal 100MB)";
                $allFilesUploaded = false;
                break;
            }
            
            // Generate nama file unik
            $newFileName = uniqid('task_') . '_' . $taskId . '_' . $key . '.' . $fileExt;
            $uploadPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $uploadedFiles[] = 'tasks/' . $newFileName;
            } else {
                $_SESSION['error'] = "Gagal mengupload file $fileName";
                $allFilesUploaded = false;
                break;
            }
        }
        
        if ($allFilesUploaded && !empty($uploadedFiles)) {
            try {
                $pdo->beginTransaction();
                
                // Convert array ke JSON untuk disimpan di database
                $filePathJson = json_encode($uploadedFiles);
                
                // Update task dengan file path, upload link, catatan, dan ubah status
                $stmt = $pdo->prepare("
                    UPDATE tasks 
                    SET status = 'ready_for_review',
                        file_path = ?,
                        uploaded_link = ?,
                        notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $filePathJson,
                    $uploadedLink,
                    $notes,
                    $taskId
                ]);
                
                // Catat perubahan status
                $stmt = $pdo->prepare("
                    INSERT INTO task_status_logs (task_id, status, updated_by)
                    VALUES (?, 'ready_for_review', ?)
                ");
                $stmt->execute([$taskId, $userId]);
                
                // Kirim notifikasi ke redaktur
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, message, link)
                    SELECT id, ?, ?
                    FROM users 
                    WHERE role = 'redaktur_pelaksana'
                ");
                $stmt->execute([
                    "Task siap untuk diverifikasi: " . $task['title'],
                    "../redaktur/view_task.php?id=" . $taskId
                ]);
                
                // Hentikan tracking waktu jika ada
                $stmt = $pdo->prepare("
                    UPDATE time_tracking
                    SET end_time = NOW()
                    WHERE task_id = ? AND user_id = ? AND end_time IS NULL
                ");
                $stmt->execute([$taskId, $userId]);
                
                $pdo->commit();
                
                $_SESSION['success'] = "File berhasil diupload dan task siap untuk diverifikasi";
                header("Location: view_task.php?id=" . $taskId);
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}

$pageTitle = "Upload Hasil Pekerjaan";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Upload Hasil Pekerjaan</h4>
                    <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-sm btn-secondary">
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
                    
                    <div class="alert alert-info">
                        <h5>Task: <?= htmlspecialchars(cleanWhatsAppFormatting($task['title'])) ?></h5>
                        <p>Status saat ini: <?= getStatusBadge($task['status']) ?></p>
                        <p>Setelah upload, status akan berubah menjadi "Siap Review" dan time tracking akan berhenti otomatis.</p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="task_file" class="form-label">File Hasil Pekerjaan</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Klik untuk memilih file atau drag & drop di sini</p>
                                    <p class="small text-muted">Format yang didukung: JPG, PNG, GIF, PDF, DOC, XLS, PPT, ZIP, RAR, AI, PSD, MP4, MOV (Maks. 100MB per file)</p>
                                </div>
                                <input type="file" id="task_file" name="task_files[]" multiple style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.ai,.psd,.mp4,.mov">
                            </div>
                            <div id="filePreview" class="mt-3"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="uploaded_link" class="form-label">Link Hasil Pekerjaan (Opsional)</label>
                            <input type="url" class="form-control" id="uploaded_link" name="uploaded_link" placeholder="https://drive.google.com/...">
                            <div class="form-text">Link Google Drive atau penyimpanan lainnya</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="view_task.php?id=<?= $taskId ?>" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Upload & Selesaikan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.file-upload-area {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: #fafafa;
}

.file-upload-area:hover {
    border-color: #007bff;
    background-color: #f8f9ff;
}

.file-upload-area.dragover {
    border-color: #007bff;
    background-color: #e3f2fd;
}

.file-preview-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 10px;
    background-color: #f8f9fa;
}

.file-preview-item .file-info {
    flex: 1;
    margin-left: 10px;
}

.file-preview-item .file-name {
    font-weight: 500;
    margin-bottom: 2px;
}

.file-preview-item .file-size {
    font-size: 0.8rem;
    color: #6c757d;
}

.file-preview-item .remove-file {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 5px;
}

.file-preview-item .remove-file:hover {
    color: #c82333;
}

.file-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    color: white;
    font-size: 1.2rem;
}

.file-icon.image { background-color: #28a745; }
.file-icon.video { background-color: #dc3545; }
.file-icon.document { background-color: #007bff; }
.file-icon.archive { background-color: #6f42c1; }
.file-icon.other { background-color: #6c757d; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('task_file');
    const filePreview = document.getElementById('filePreview');
    let selectedFiles = [];

    // Click to select files
    fileUploadArea.addEventListener('click', function() {
        fileInput.click();
    });

    // Drag and drop functionality
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileUploadArea.classList.add('dragover');
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('dragover');
    });

    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('dragover');
        
        const files = Array.from(e.dataTransfer.files);
        handleFiles(files);
    });

    // File input change
    fileInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        handleFiles(files);
    });

    function handleFiles(files) {
        files.forEach(file => {
            if (validateFile(file)) {
                selectedFiles.push(file);
            }
        });
        updateFilePreview();
        updateFileInput();
    }

    function validateFile(file) {
        const maxSize = 100 * 1024 * 1024; // 100MB
        const allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip', 'application/x-rar-compressed',
            'application/postscript', // AI files
            'image/vnd.adobe.photoshop', // PSD files
            'video/mp4', 'video/quicktime', 'video/x-msvideo'
        ];

        if (file.size > maxSize) {
            alert(`File ${file.name} terlalu besar. Maksimal 100MB.`);
            return false;
        }

        if (!allowedTypes.includes(file.type) && !isAllowedExtension(file.name)) {
            alert(`File ${file.name} tidak didukung.`);
            return false;
        }

        return true;
    }

    function isAllowedExtension(filename) {
        const allowedExtensions = ['.ai', '.psd'];
        const extension = filename.toLowerCase().substring(filename.lastIndexOf('.'));
        return allowedExtensions.includes(extension);
    }

    function updateFilePreview() {
        filePreview.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-preview-item';
            
            const fileIcon = getFileIcon(file);
            const fileSize = formatFileSize(file.size);
            
            fileItem.innerHTML = `
                <div class="file-icon ${fileIcon.class}">
                    <i class="fas ${fileIcon.icon}"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${fileSize}</div>
                </div>
                <button type="button" class="remove-file" onclick="removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            filePreview.appendChild(fileItem);
        });
    }

    function getFileIcon(file) {
        const type = file.type.toLowerCase();
        const extension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
        
        if (type.startsWith('image/')) {
            return { class: 'image', icon: 'fa-image' };
        } else if (type.startsWith('video/')) {
            return { class: 'video', icon: 'fa-video' };
        } else if (type.includes('pdf')) {
            return { class: 'document', icon: 'fa-file-pdf' };
        } else if (type.includes('word') || type.includes('document')) {
            return { class: 'document', icon: 'fa-file-word' };
        } else if (type.includes('excel') || type.includes('spreadsheet')) {
            return { class: 'document', icon: 'fa-file-excel' };
        } else if (type.includes('powerpoint') || type.includes('presentation')) {
            return { class: 'document', icon: 'fa-file-powerpoint' };
        } else if (type.includes('zip') || type.includes('rar')) {
            return { class: 'archive', icon: 'fa-file-archive' };
        } else if (extension === '.ai') {
            return { class: 'document', icon: 'fa-file-image' };
        } else if (extension === '.psd') {
            return { class: 'document', icon: 'fa-file-image' };
        } else {
            return { class: 'other', icon: 'fa-file' };
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function updateFileInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => {
            dt.items.add(file);
        });
        fileInput.files = dt.files;
    }

    // Global function to remove file
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        updateFilePreview();
        updateFileInput();
    };
});
</script>

<?php include '../includes/footer.php'; ?>
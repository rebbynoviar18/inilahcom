<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];
$userRole = getUserRole();

$error = '';
$success = '';

// Get user role for correct link prefixes
$baseUrl = '';
switch ($userRole) {
    case 'creative_director':
        $baseUrl = '../admin/';
        break;
    case 'content_team':
        $baseUrl = '../content/';
        break;
    case 'production_team':
        $baseUrl = '../production/';
        break;        
    case 'marketing_team':
        $baseUrl = '../marketing/';
        break;        
    case 'redaksi':
        $baseUrl = '../redaksi/';
        break;        
    case 'redaktur_pelaksana':
        $baseUrl = '../redaksi/';
        break;
    default:
        $baseUrl = '../';
}

// Get user data with a different variable name
try {
    $correctUserId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$correctUserId]);
    $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profileUser) {
        $_SESSION['error'] = "User tidak ditemukan";
        header("Location: " . $baseUrl . "dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Error mengambil data user: " . $e->getMessage();
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio'] ?? '');
    $whatsappNumber = '+62' . preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '');
    
    // Validasi input
    if (empty($name) || empty($email)) {
        $error = "Nama dan email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid";
    } else {
        try {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->rowCount() > 0) {
                $error = "Email sudah digunakan oleh pengguna lain";
            } else {
                // Update user data
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, bio = ?, whatsapp_number = ? WHERE id = ?");
                $stmt->execute([$name, $email, $bio, $whatsappNumber, $userId]);
                
                // Update session data
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                
                $success = "Profil berhasil diperbarui";
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = "Error memperbarui profil: " . $e->getMessage();
        }
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Semua field password harus diisi";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Password baru dan konfirmasi password tidak cocok";
    } elseif (strlen($newPassword) < 6) {
        $error = "Password baru minimal 6 karakter";
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $userData['password'])) {
                $error = "Password saat ini tidak valid";
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                $success = "Password berhasil diubah";
            }
        } catch (PDOException $e) {
            $error = "Error mengubah password: " . $e->getMessage();
        }
    }
}

$pageTitle = "Profil Saya";

// Mulai output buffering untuk header
ob_start();
include '../includes/header.php';
$header_content = ob_get_clean();

// Ganti semua link di navigasi
$header_content = str_replace('href="dashboard.php"', 'href="' . $baseUrl . 'dashboard.php"', $header_content);
$header_content = str_replace('href="tasks.php"', 'href="' . $baseUrl . 'tasks.php"', $header_content);
$header_content = str_replace('href="accounts.php"', 'href="' . $baseUrl . 'accounts.php"', $header_content);
$header_content = str_replace('href="users.php"', 'href="' . $baseUrl . 'users.php"', $header_content);
$header_content = str_replace('href="reports.php"', 'href="' . $baseUrl . 'reports.php"', $header_content);
$header_content = str_replace('href="resources.php"', 'href="' . $baseUrl . 'resources.php"', $header_content);
$header_content = str_replace('href="create_task.php"', 'href="' . $baseUrl . 'create_task.php"', $header_content);
$header_content = str_replace('href="calendar.php"', 'href="' . $baseUrl . 'calendar.php"', $header_content);
$header_content = str_replace('href="templates.php"', 'href="' . $baseUrl . 'templates.php"', $header_content);
$header_content = str_replace('href="time_tracking.php"', 'href="' . $baseUrl . 'time_tracking.php"', $header_content);
$header_content = str_replace('href="performance.php"', 'href="' . $baseUrl . 'performance.php"', $header_content);

// Tampilkan header yang sudah dimodifikasi
echo $header_content;
?>

<div class="container mt-4">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Photo Column -->
        <div class="col-md-4">
            
            <!-- Change Password Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ubah Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Minimal 6 karakter</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="change_password" class="btn btn-warning">Ubah Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Profile Info Column -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Profil</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($profileUser['name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($profileUser['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="whatsapp_number" class="form-label">Nomor WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text">+62</span>
                                <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" 
                                       value="<?= substr($profileUser['whatsapp_number'] ?? '', 3) ?>" 
                                       placeholder="8123456789 (tanpa awalan 0)">
                            </div>
                            <div class="form-text">Masukkan nomor WhatsApp untuk menerima notifikasi task baru</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($profileUser['bio'] ?? '') ?></textarea>
                            <div class="form-text">Ceritakan sedikit tentang diri Anda (opsional)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" value="<?= getUserRoleLabel($profileUser['role']) ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="created_at" class="form-label">Bergabung Sejak</label>
                            <input type="text" class="form-control" id="created_at" value="<?= date('d F Y', strtotime($profileUser['created_at'])) ?>" readonly>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('profile_photo');
    const selectPhotoBtn = document.getElementById('select-photo-btn');
    const uploadBtnContainer = document.getElementById('upload-btn-container');
    const photoPreview = document.getElementById('profile-photo-preview');
    
    // Open file dialog when button is clicked
    selectPhotoBtn.addEventListener('click', function() {
        photoInput.click();
    });
    
    // Show preview and upload button when file is selected
    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
                uploadBtnContainer.style.display = 'block';
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>
<?php include '../includes/footer.php'; ?>
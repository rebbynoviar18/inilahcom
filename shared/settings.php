<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];
$settings = getUserSettings($userId);
$error = '';
$success = '';

// Get user role for correct link prefixes
$userRole = getUserRole();
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
    default:
        $baseUrl = '../';
}

// Get user settings
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "User tidak ditemukan";
        header("Location: " . $baseUrl . "dashboard.php");
        exit();
    }
    
    // Get notification settings
    $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        // Create default settings if not exists
        $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, email_notifications, task_reminders, theme) VALUES (?, 1, 1, 'light')");
        $stmt->execute([$userId]);
        
        $settings = [
            'email_notifications' => 1,
            'task_reminders' => 1,
            'theme' => 'light'
        ];
    }
} catch (PDOException $e) {
    $error = "Error mengambil pengaturan: " . $e->getMessage();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $taskReminders = isset($_POST['task_reminders']) ? 1 : 0;
    $theme = $_POST['theme'];
    
    try {
        $stmt = $pdo->prepare("UPDATE user_settings SET email_notifications = ?, task_reminders = ?, theme = ? WHERE user_id = ?");
        $stmt->execute([$emailNotifications, $taskReminders, $theme, $userId]);
        
        $success = "Pengaturan berhasil diperbarui";
        
        // Refresh settings
        $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Gagal memperbarui pengaturan: " . $e->getMessage();
    }
}

$pageTitle = "Pengaturan";

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
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Menu Pengaturan</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="list">Umum</a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">Notifikasi</a>
                    <a href="#appearance" class="list-group-item list-group-item-action" data-bs-toggle="list">Tampilan</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pengaturan</h5>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="general">
                            <h5 class="mb-3">Pengaturan Umum</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Bahasa</label>
                                    <select class="form-select" name="language" disabled>
                                        <option value="id" selected>Bahasa Indonesia</option>
                                        <option value="en">English</option>
                                    </select>
                                    <small class="text-muted">Fitur multi-bahasa akan segera hadir</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Zona Waktu</label>
                                    <select class="form-select" name="timezone" disabled>
                                        <option value="Asia/Jakarta" selected>Asia/Jakarta (GMT+7)</option>
                                    </select>
                                    <small class="text-muted">Fitur zona waktu akan segera hadir</small>
                                </div>
                                <button type="submit" name="update_general" class="btn btn-primary" disabled>Simpan Perubahan</button>
                            </form>
                        </div>
                        
                        <div class="tab-pane fade" id="notifications">
                            <h5 class="mb-3">Pengaturan Notifikasi</h5>
                            <form method="POST">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="email_notifications" name="email_notifications" <?= $settings['email_notifications'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="email_notifications">Terima notifikasi email</label>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="task_reminders" name="task_reminders" <?= $settings['task_reminders'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="task_reminders">Terima pengingat tugas</label>
                                </div>
                                <input type="hidden" name="theme" value="<?= htmlspecialchars($settings['theme']) ?>">
                                <button type="submit" name="update_settings" class="btn btn-primary">Simpan Perubahan</button>
                            </form>
                        </div>
                        
                        <div class="tab-pane fade" id="appearance">
                            <h5 class="mb-3">Pengaturan Tampilan</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Tema</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theme" id="theme_light" value="light" <?= $settings['theme'] === 'light' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="theme_light">
                                                Terang
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theme" id="theme_dark" value="dark" <?= $settings['theme'] === 'dark' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="theme_dark">
                                                Gelap
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="email_notifications" value="<?= $settings['email_notifications'] ?>">
                                <input type="hidden" name="task_reminders" value="<?= $settings['task_reminders'] ?>">
                                <button type="submit" name="update_settings" class="btn btn-primary">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aktifkan tab berdasarkan hash URL
    const hash = window.location.hash || '#general';
    const tabTrigger = document.querySelector(`a[href="${hash}"]`);
    if (tabTrigger) {
        const tab = new bootstrap.Tab(tabTrigger);
        tab.show();
    }
    
    // Update hidden fields saat tab berubah
    document.querySelectorAll('a[data-bs-toggle="list"]').forEach(function(tabEl) {
        tabEl.addEventListener('shown.bs.tab', function(event) {
            window.location.hash = event.target.getAttribute('href');
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
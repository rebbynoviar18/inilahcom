<?php
// Tangkap error koneksi database
try {
    // Cek apakah server MySQL berjalan sebelum mencoba koneksi
    $connection = @fsockopen('localhost', 3306);
    if (!$connection) {
        throw new Exception("Tidak dapat terhubung ke server MySQL. Pastikan server MySQL berjalan.");
    }
    fclose($connection);
    
    require_once '../config/database.php';
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';

    // Redirect if not logged in
    redirectIfNotLoggedIn();
    
    $userId = $_SESSION['user_id'];
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
    
    // Mark all notifications as read
    if (isset($_GET['mark_all_read'])) {
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            $success = "Semua notifikasi telah ditandai sebagai dibaca";
            
            // Redirect to remove the mark_all_read parameter
            header("Location: notifications.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Mark single notification as read
    if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
        $notificationId = (int)$_GET['mark_read'];
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $userId]);
            $success = "Notifikasi telah ditandai sebagai dibaca";
            
            // Redirect to remove the mark_read parameter
            header("Location: notifications.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Delete notification
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $notificationId = (int)$_GET['delete'];
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $userId]);
            $success = "Notifikasi telah dihapus";
            
            // Redirect to remove the delete parameter
            header("Location: notifications.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Hitung total notifikasi untuk pengguna ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalNotifications = $stmt->fetchColumn();
    
    // Get ALL notifications without any limit
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pageTitle = "Notifikasi";
    
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
    
} catch (Exception $e) {
    // Tangkap semua error dan tampilkan pesan yang lebih user-friendly
    $error = "Terjadi kesalahan: " . $e->getMessage();
    
    // Jika error terkait koneksi database, tampilkan pesan khusus
    if (strpos($e->getMessage(), 'connection') !== false || strpos($e->getMessage(), 'koneksi') !== false) {
        $error = "Tidak dapat terhubung ke database. Pastikan server MySQL berjalan dan coba lagi.";
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Notifikasi <small class="text-muted">(<?= isset($totalNotifications) ? $totalNotifications : 0 ?>)</small></h2>
        <?php if (empty($error) || strpos($error, 'database') === false): ?>
        <div>
            <a href="?mark_all_read=1" class="btn btn-outline-primary">
                <i class="fas fa-check-double"></i> Tandai Semua Dibaca
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <h4 class="alert-heading">Error!</h4>
            <p><?= htmlspecialchars($error) ?></p>
            <?php if (strpos($error, 'database') !== false || strpos($error, 'koneksi') !== false): ?>
            <hr>
            <p class="mb-0">Coba periksa hal berikut:</p>
            <ul>
                <li>Pastikan XAMPP atau server MySQL Anda berjalan</li>
                <li>Periksa pengaturan koneksi database di file config/database.php</li>
                <li>Coba refresh halaman setelah memastikan server database berjalan</li>
            </ul>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <?php 
                // Set zona waktu ke Asia/Jakarta
                date_default_timezone_set('Asia/Jakarta');
                
                if (empty($notifications)): 
                ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <p class="lead">Tidak ada notifikasi</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): 
                            // Konversi waktu database ke waktu lokal
                            $createdTime = new DateTime($notification['created_at']);
                        ?>
                            <div class="list-group-item list-group-item-action <?= $notification['is_read'] ? '' : 'bg-light' ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">
                                        <?= $notification['is_read'] ? '' : '<span class="badge bg-primary me-2">Baru</span>' ?>
                                        <a href="<?= $notification['link'] ?>"><?= htmlspecialchars($notification['message']) ?></a>
                                    </h5>
                                    <div>
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="?mark_read=<?= $notification['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Tandai Dibaca">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete=<?= $notification['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirmDelete(event, 'Hapus notifikasi ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= $createdTime->format('d M Y H:i') ?>
                                    <?php
                                    // Hitung waktu yang berlalu secara manual
                                    $now = new DateTime();
                                    $interval = $createdTime->diff($now);
                                    
                                    if ($interval->y > 0) {
                                        echo " ({$interval->y} tahun yang lalu)";
                                    } elseif ($interval->m > 0) {
                                        echo " ({$interval->m} bulan yang lalu)";
                                    } elseif ($interval->d > 0) {
                                        echo " ({$interval->d} hari yang lalu)";
                                    } elseif ($interval->h > 0) {
                                        echo " ({$interval->h} jam yang lalu)";
                                    } elseif ($interval->i > 0) {
                                        echo " ({$interval->i} menit yang lalu)";
                                    } else {
                                        echo " (baru saja)";
                                    }
                                    ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(event, message) {
    if (!confirm(message)) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>
<?php
// File: includes/header.php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php'; // Tambahkan baris ini

$unreadNotifications = 0;
if (isLoggedIn()) {
    try {
        // Cek apakah tabel notifications ada
        $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$_SESSION['user_id']]);
            $unreadNotifications = $stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        // Tangani error dengan diam-diam
        $unreadNotifications = 0;
    }
}

// Perbarui status online pengguna jika sudah login
if (isLoggedIn()) {
    try {
        $updateStmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $updateStmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // Abaikan error jika kolom belum ada
    }
}

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Ambil informasi pengguna dari database
    try {
        require_once '../config/database.php'; // Sesuaikan path jika perlu
        require_once '../includes/functions.php'; // Tambahkan ini untuk mengakses fungsi getUserProfilePhoto
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); // Ubah query ini
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $userName = htmlspecialchars($user['name']); // Sanitize nama pengguna
        
        // Gunakan fungsi getUserProfilePhoto untuk mendapatkan path foto profil
        $profilePicturePath = getUserProfilePhoto($userId);

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage()); // Log error
        $userName = "Pengguna"; // Nama default jika terjadi kesalahan
        $profilePicturePath = '../assets/images/default-avatar.jpg'; // Path default jika terjadi kesalahan
    }
} else {
    $userName = "Pengunjung"; // Nama default jika tidak ada sesi
    $profilePicturePath = '../assets/images/default-avatar.jpg'; // Path default jika tidak ada sesi
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management System - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-8F3PqD9j1i8q5T4V5Yt+V883gJ9Q1H3j90L5Y790L1G0Y5G7E4Q3T0M6S1N0Y1G3Y4P4P2V0Y2V2P4V6/a512" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">    
    <link href="../assets/css/weather.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.ico">
    
    <!-- Di bagian head, pastikan jQuery dimuat -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/desktop-notifications.js"></script>
    <script src="../assets/js/notification-checker.js"></script>
    <script src="../assets/js/weather.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <button class="navbar-toggler me-2" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-tasks me-2"></i>Task Management
            </a>
            
            <div class="d-flex align-items-center">
                <?php if (isLoggedIn()): ?>
                <div class="dropdown me-3">
                    <a href="#" class="dropdown-toggle text-dark" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadNotifications > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $unreadNotifications; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li><h6 class="dropdown-header">Notifikasi</h6></li>
                        <?php
                            // Set zona waktu ke Asia/Jakarta
                            date_default_timezone_set('Asia/Jakarta');
                            
                            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                            $stmt->execute([$_SESSION['user_id']]);
                            $notifications = $stmt->fetchAll();
                            
                            if (empty($notifications)) {
                                echo '<li><span class="dropdown-item text-muted">Tidak ada notifikasi</span></li>';
                            } else {
                                foreach ($notifications as $notification) {
                                    $isRead = $notification['is_read'] ? '' : 'fw-bold';
                                    
                                    // Konversi waktu database ke waktu lokal
                                    $createdTime = new DateTime($notification['created_at']);
                                    $now = new DateTime();
                                    $interval = $createdTime->diff($now);
                                    
                                    // Format waktu yang berlalu
                                    $timeAgo = '';
                                    if ($interval->y > 0) {
                                        $timeAgo = "{$interval->y} tahun yang lalu";
                                    } elseif ($interval->m > 0) {
                                        $timeAgo = "{$interval->m} bulan yang lalu";
                                    } elseif ($interval->d > 0) {
                                        $timeAgo = "{$interval->d} hari yang lalu";
                                    } elseif ($interval->h > 0) {
                                        $timeAgo = "{$interval->h} jam yang lalu";
                                    } elseif ($interval->i > 0) {
                                        $timeAgo = "{$interval->i} menit yang lalu";
                                    } else {
                                        $timeAgo = "baru saja";
                                    }
                                    
                                    echo '<li>
                                        <a class="dropdown-item '.$isRead.'" href="'.$notification['link'].'" data-notification-id="'.$notification['id'].'">
                                            '.$notification['message'].'
                                            <small class="text-muted d-block">'.$timeAgo.'</small>
                                        </a>
                                    </li>';
                                }
                            }
                        ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="../shared/notifications.php">Lihat Semua</a></li>
                    </ul>
                </div>
                <script>
document.addEventListener('DOMContentLoaded', function() {
    // Ambil elemen dropdown notifikasi
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationDropdown) {
        // Tambahkan event listener untuk dropdown notifikasi
        notificationDropdown.addEventListener('show.bs.dropdown', function() {
            // Tandai semua notifikasi sebagai terbaca ketika dropdown dibuka
            markAllNotificationsAsRead();
        });
        
        // Tambahkan event listener untuk setiap link notifikasi
        document.querySelectorAll('.dropdown-menu a.dropdown-item[data-notification-id]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                // Ambil ID notifikasi dari data attribute
                const notificationId = this.getAttribute('data-notification-id');
                if (notificationId) {
                    // Tandai notifikasi spesifik sebagai terbaca
                    markNotificationAsRead(notificationId);
                }
            });
        });
    }
    
    // Fungsi untuk menandai semua notifikasi sebagai terbaca
    function markAllNotificationsAsRead() {
        fetch('/creative/shared/api/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                mark_all: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hapus badge notifikasi
                const badge = document.querySelector('.fa-bell + .badge');
                if (badge) {
                    badge.style.display = 'none';
                }
                
                // Hapus class fw-bold dari semua item notifikasi
                document.querySelectorAll('.dropdown-menu a.dropdown-item.fw-bold').forEach(function(item) {
                    item.classList.remove('fw-bold');
                });
            }
        })
        .catch(error => console.error('Error marking notifications as read:', error));
    }
    
    // Fungsi untuk menandai notifikasi spesifik sebagai terbaca
    function markNotificationAsRead(notificationId) {
        fetch('/creative/shared/api/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hapus class fw-bold dari item notifikasi
                const item = document.querySelector(`.dropdown-item[data-notification-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('fw-bold');
                }
                
                // Update badge jika perlu
                updateNotificationBadge();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }
    
    // Fungsi untuk update badge notifikasi
    function updateNotificationBadge() {
        // Hitung jumlah notifikasi yang belum dibaca
        const unreadCount = document.querySelectorAll('.dropdown-menu a.dropdown-item.fw-bold').length;
        
        // Update badge
        const badge = document.querySelector('.fa-bell + .badge');
        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        }
    }
});
</script>
                
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle d-flex align-items-center text-dark text-decoration-none" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= isset($_SESSION['user_id']) ? getUserProfilePhoto($_SESSION['user_id']) : '../assets/images/avatar.png' ?>" alt="User" class="rounded-circle me-2" width="32" height="32">
                        <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php if (isLoggedIn() && getUserRole() === 'production_team'): ?>
                            <?php $activeTracking = getActiveTracking($_SESSION['user_id']); ?>
                            <?php if ($activeTracking): ?>
                            <li class="nav-item">
                                <a class="nav-link text-warning" href="<?= getUserRole() === 'production_team' ? '../production/time_tracking.php' : '#' ?>">
                                    <i class="fas fa-stopwatch fa-pulse"></i> 
                                    Tracking: <?= htmlspecialchars(substr($activeTracking['title'], 0, 20)) ?>...
                                </a>
                            </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../shared/profile.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item" href="../shared/settings.php"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <?php if (isLoggedIn()): ?>
            <div class="col-lg-2 sidebar p-0">
                <!-- User Profile Section -->
                <div class="user-profile-section p-3 border-bottom">
                    <div class="text-center">
                        <img src="<?= getUserProfilePhoto($_SESSION['user_id']) ?>" 
                             alt="Profile" 
                             class="rounded-circle mb-2" 
                             width="140" 
                             height="140"
                             style="object-fit: cover;">
                        <h4 class="mb-1 text-truncate"><?= htmlspecialchars($_SESSION['name']) ?></h4>
                        <small class="text-muted">
                            <?php 
                            $roleLabels = [
                                'creative_director' => 'Creative Director',
                                'content_team' => 'Content Team',
                                'production_team' => 'Production Team',
                                'marketing_team' => 'Marketing Team',
                                'redaksi' => 'Redaksi',
                                'redaktur_pelaksana' => 'Redaktur Pelaksana'
                            ];
                            echo $roleLabels[getUserRole()] ?? getUserRole();
                            ?>
                        </small>
                    </div>
                </div>
                
                <div class="p-3">
                    <nav class="nav flex-column">
                        <?php if (getUserRole() === 'creative_director'): ?>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : ''; ?>" href="tasks.php">
                                <i class="fas fa-tasks me-2"></i>Semua Task
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'accounts.php' ? 'active' : ''; ?>" href="accounts.php">
                                <i class="fas fa-users me-2"></i>Akun Media
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'create_task.php' ? 'active' : ''; ?>" href="create_task.php">
                                <i class="fas fa-plus-circle me-2"></i>Buat Task Baru
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="users.php">
                                <i class="fas fa-user-friends me-2"></i>Manajemen User
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Laporan
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'resources.php' ? 'active' : ''; ?>" href="resources.php">
                                <i class="fas fa-folder me-2"></i>Resource Library
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'shifts.php' || basename($_SERVER['PHP_SELF']) === 'manage_shifts.php' ? 'active' : ''; ?>" href="../admin/shifts.php">
                                <i class="fas fa-calendar-alt me-2"></i>Jadwal Shift
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_shifts.php' ? 'active' : ''; ?>" href="manage_shifts.php">
                                <i class="fas fa-clock me-2"></i>Kelola Shift
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'content_management.php' ? 'active' : ''; ?>" href="content_management.php">
                                <i class="fas fa-layer-group me-2"></i>Manajemen Konten
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'time_tracking_report.php' ? 'active' : ''; ?>" href="time_tracking_report.php">
                                <i class="fas fa-clock me-2"></i>Laporan Time Tracking
                            </a>

                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manual_points.php' ? 'active' : ''; ?>" href="manual_points.php">
                                <i class="fas fa-plus-circle me-2"></i> Tambah Poin Manual
                            </a>                         
<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_targets.php' ? 'active' : ''; ?>" href="manage_targets.php">
                                   <i class="fas fa-bullseye me-2"></i>Kelola Target
                            </a>

                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'point_settings.php' ? 'active' : ''; ?>" href="point_settings.php">
                                <i class="fas fa-star me-2"></i>Pengaturan Poin
                            </a>
                            
                        <?php elseif (getUserRole() === 'content_team'): ?>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : ''; ?>" href="tasks.php">
                                <i class="fas fa-tasks me-2"></i>Task Saya
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'create_task.php' ? 'active' : ''; ?>" href="create_task.php">
                                <i class="fas fa-plus-circle me-2"></i>Buat Task Baru
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'calendar.php' ? 'active' : ''; ?>" href="calendar.php">
                                <i class="fas fa-calendar-alt me-2"></i>Kalender
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'templates.php' ? 'active' : ''; ?>" href="templates.php">
                                <i class="fas fa-file-alt me-2"></i>Template Brief
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'shifts.php' ? 'active' : ''; ?>" href="../content/shifts.php">
                                <i class="fas fa-calendar-alt me-2"></i>Jadwal Shift
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === '../shared/target_calendar.php' ? 'active' : ''; ?>" href="../shared/target_calendar.php">
                                <i class="fas fa-chart-line me-2"></i>Performa Saya
                            </a>
                            
                        <?php elseif (getUserRole() === 'production_team'): ?>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : ''; ?>" href="tasks.php">
                                <i class="fas fa-tasks me-2"></i>Task Saya
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'time_tracking.php' ? 'active' : ''; ?>" href="time_tracking.php">
                                <i class="fas fa-clock me-2"></i>Time Tracking
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'shifts.php' ? 'active' : ''; ?>" href="../production/shifts.php">
                                <i class="fas fa-calendar-alt me-2"></i>Jadwal Shift
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === '../shared/target_calendar.php' ? 'active' : ''; ?>" href="../shared/target_calendar.php">
                                <i class="fas fa-chart-line me-2"></i>Performa Saya
                            </a>
                        
                        <?php elseif (getUserRole() === 'marketing_team'): ?>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : ''; ?>" href="tasks.php">
                                <i class="fas fa-tasks me-2"></i>Task Saya
                            </a>                            
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'create_task.php' ? 'active' : ''; ?>" href="create_task.php">
                                <i class="fas fa-plus-circle me-2"></i>Buat Task Baru
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'calendar.php' ? 'active' : ''; ?>" href="calendar.php">
                                <i class="fas fa-calendar me-2"></i>Kalender
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                                <i class="fas fa-chart-line me-2"></i>Laporan
                            </a>

                            
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/admin/leaderboard.php') !== false ? 'active' : '' ?>" href="../admin/leaderboard.php">
                                <i class="fas fa-trophy me-2"></i>
                                Leaderboard
                            </a>
                        </li>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
            
            <main class="<?php echo isLoggedIn() ? 'col-lg-10' : 'col-12'; ?> py-4 px-4">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
    <!-- Sisipkan widget chat di akhir body sebelum penutup -->
    <?php include_once 'chat_widget.php'; ?>
    
    <!-- Tambahkan file audio notifikasi -->
    <audio id="notificationSound" preload="auto" style="display: none;">
        <source src="../assets/sounds/notification.mp3" type="audio/mpeg">
    </audio>
</body>
</html>
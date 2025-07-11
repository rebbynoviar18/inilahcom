<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek notifikasi yang belum dibaca
$unreadNotifications = 0;
if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadNotifications = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Tangani error dengan diam-diam
        error_log("Error checking notifications: " . $e->getMessage());
    }
}

// Cek tracking waktu yang aktif
$activeTracking = null;
if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../includes/functions.php';
        $activeTracking = getActiveTracking($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Error checking time tracking: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>Creative Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/img/logo.png" alt="Logo" height="30" class="d-inline-block align-text-top me-2">
                Creative Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tasks.php"><i class="fas fa-tasks me-1"></i> Task</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="calendar.php"><i class="fas fa-calendar-alt me-1"></i> Kalender</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_task.php"><i class="fas fa-plus-circle me-1"></i> Buat Task</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="accounts.php"><i class="fas fa-users me-1"></i> Akun</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Laporan</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($activeTracking): ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="../shared/time_tracking.php">
                            <i class="fas fa-stopwatch me-1"></i> 
                            <span id="activeTrackingTimer" data-start="<?= strtotime($activeTracking['start_time']) ?>">
                                Tracking...
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="../shared/notifications.php">
                            <i class="fas fa-bell me-1"></i> Notifikasi
                            <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unreadNotifications ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Akun' ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../shared/profile.php"><i class="fas fa-id-card me-1"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="../shared/settings.php"><i class="fas fa-cog me-1"></i> Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <?php if ($activeTracking): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const timerElement = document.getElementById('activeTrackingTimer');
        const startTime = parseInt(timerElement.dataset.start);
        
        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const diff = now - startTime;
            
            const hours = Math.floor(diff / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;
            
            timerElement.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Update timer immediately and then every second
        updateTimer();
        setInterval(updateTimer, 1000);
    });
    </script>
    <?php endif; ?>
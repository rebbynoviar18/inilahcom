<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$lastChecked = isset($_SESSION['last_notification_check']) ? $_SESSION['last_notification_check'] : 0;

// Update waktu terakhir cek
$currentTime = time();
$_SESSION['last_notification_check'] = $currentTime;

try {
    // Hitung total notifikasi yang belum dibaca
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetchColumn();
    
    // Ambil notifikasi baru yang belum dibaca sejak terakhir cek
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        AND is_read = 0 
        AND UNIX_TIMESTAMP(created_at) > ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId, $lastChecked]);
    $newNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'unread' => $unreadCount,
        'notifications' => $newNotifications,
        'last_checked' => $lastChecked,
        'current_time' => $currentTime
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

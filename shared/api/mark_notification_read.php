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

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

try {
    // Tandai semua notifikasi sebagai terbaca
    if (isset($data['mark_all']) && $data['mark_all']) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo json_encode([
            'success' => true, 
            'message' => 'Semua notifikasi ditandai sebagai terbaca'
        ]);
        exit;
    }
    
    // Tandai notifikasi tertentu sebagai terbaca
    if (isset($data['notification_id'])) {
        $notificationId = (int)$data['notification_id'];
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
        
        // Hitung sisa notifikasi yang belum dibaca
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $unreadCount = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Notifikasi ditandai sebagai terbaca',
            'unread_count' => $unreadCount
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Parameter tidak valid'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

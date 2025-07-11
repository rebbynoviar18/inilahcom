<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Default response
$response = [
    'unread' => 0,
    'notifications' => [],
    'success' => false,
    'message' => 'Tidak ada sesi aktif'
];

// Cek apakah pengguna sudah login
if (isLoggedIn()) {
    try {
        $userId = $_SESSION['user_id'];
        
        // Hitung notifikasi yang belum dibaca
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $unreadCount = $stmt->fetchColumn();
        
        // Ambil notifikasi terbaru
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'unread' => (int)$unreadCount,
            'notifications' => $notifications,
            'success' => true,
            'message' => 'Berhasil mengambil data notifikasi'
        ];
    } catch (PDOException $e) {
        $response = [
            'unread' => 0,
            'notifications' => [],
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

echo json_encode($response);
exit;
?>
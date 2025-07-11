<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Pastikan user sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$otherUserId) {
    echo json_encode(['success' => false, 'message' => 'User ID tidak valid']);
    exit;
}

try {
    // Set timezone ke Asia/Jakarta
    date_default_timezone_set('Asia/Jakarta');
    
    // Ambil pesan antara kedua user dengan waktu yang diformat
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message, is_read, created_at,
               DATE_FORMAT(created_at, '%d %b %Y %H:%i') as formatted_time
        FROM chat_messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tandai pesan dari user lain sebagai sudah dibaca
    $updateStmt = $pdo->prepare("
        UPDATE chat_messages
        SET is_read = 1
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $updateStmt->execute([$otherUserId, $userId]);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
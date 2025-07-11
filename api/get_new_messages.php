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
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if (!$otherUserId) {
    echo json_encode(['success' => false, 'message' => 'User ID tidak valid']);
    exit;
}

try {
    // Ambil pesan baru
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message, is_read, created_at
        FROM chat_messages
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND id > ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId, $lastId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
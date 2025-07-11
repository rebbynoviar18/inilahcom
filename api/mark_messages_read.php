<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Pastikan user sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'];
$senderId = isset($_POST['sender_id']) ? (int)$_POST['sender_id'] : 0;

// Validasi input
if (!$senderId) {
    echo json_encode(['success' => false, 'message' => 'Pengirim tidak valid']);
    exit;
}

try {
    // Update status pesan menjadi sudah dibaca
    $stmt = $pdo->prepare("
        UPDATE chat_messages
        SET is_read = 1
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$senderId, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Pesan berhasil ditandai sebagai dibaca'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

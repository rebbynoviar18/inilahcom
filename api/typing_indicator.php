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

$senderId = $_SESSION['user_id'];
$receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$isTyping = isset($_POST['is_typing']) ? (int)$_POST['is_typing'] : 0;

// Validasi input
if (!$receiverId) {
    echo json_encode(['success' => false, 'message' => 'Penerima tidak valid']);
    exit;
}

try {
    // Simpan status mengetik ke database
    $stmt = $pdo->prepare("
        INSERT INTO typing_indicators (user_id, receiver_id, is_typing, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE is_typing = ?, updated_at = NOW()
    ");
    $stmt->execute([$senderId, $receiverId, $isTyping, $isTyping]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
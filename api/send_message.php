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
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validasi input
if (!$receiverId) {
    echo json_encode(['success' => false, 'message' => 'Penerima tidak valid']);
    exit;
}

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong']);
    exit;
}

try {
    // Set timezone ke Asia/Jakarta
    date_default_timezone_set('Asia/Jakarta');
    
    // Periksa apakah penerima ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND active = 1");
    $stmt->execute([$receiverId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Receiver not found or inactive']);
        exit;
    }
    
    // Simpan pesan ke database
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (sender_id, receiver_id, message, created_at, is_read)
        VALUES (?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([$senderId, $receiverId, $message]);
    
    // Ambil ID pesan yang baru saja disimpan
    $messageId = $pdo->lastInsertId();
    
    // Ambil waktu saat ini untuk dikirim kembali ke client
    $currentTime = date('Y-m-d H:i:s');
    $formattedTime = date('d M Y H:i');
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'message' => [
            'id' => $messageId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $message,
            'created_at' => $currentTime,
            'formatted_time' => $formattedTime,
            'is_read' => 0
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Pastikan user sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$lastCheck = isset($_GET['last_check']) ? (int)$_GET['last_check'] / 1000 : 0;
$lastCheckDate = date('Y-m-d H:i:s', $lastCheck);

// Simpan ID pesan terakhir yang sudah diproses
if (!isset($_SESSION['last_processed_message_ids'])) {
    $_SESSION['last_processed_message_ids'] = [];
}

try {
    // Update user's last activity
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, last_activity) 
        VALUES (?, UNIX_TIMESTAMP()) 
        ON DUPLICATE KEY UPDATE last_activity = UNIX_TIMESTAMP()
    ");
    $stmt->execute([$userId]);
    
    // Get new messages
    $stmt = $pdo->prepare("
        SELECT * FROM chat_messages 
        WHERE receiver_id = ? AND created_at > ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$userId, $lastCheckDate]);
    $allNewMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter out already processed messages
    $newMessages = [];
    foreach ($allNewMessages as $message) {
        $messageId = $message['id'];
        if (!in_array($messageId, $_SESSION['last_processed_message_ids'])) {
            $newMessages[] = $message;
            // Add to processed list, but keep the list manageable
            $_SESSION['last_processed_message_ids'][] = $messageId;
            // Keep only the last 100 message IDs to prevent session bloat
            if (count($_SESSION['last_processed_message_ids']) > 100) {
                array_shift($_SESSION['last_processed_message_ids']);
            }
        }
    }
    
    // Get online users
    $onlineUsers = [];
    $stmt = $pdo->prepare("
        SELECT u.id 
        FROM users u
        LEFT JOIN user_sessions us ON u.id = us.user_id
        WHERE u.id != ? AND us.last_activity > UNIX_TIMESTAMP() - 300
    ");
    $stmt->execute([$userId]);
    $onlineUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get typing indicators
    $stmt = $pdo->prepare("
        SELECT user_id, is_typing 
        FROM typing_indicators 
        WHERE receiver_id = ? AND updated_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
    ");
    $stmt->execute([$userId]);
    $typingUsers = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $typingUsers[$row['user_id']] = (bool)$row['is_typing'];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $newMessages,
        'online_users' => $onlineUsers,
        'typing_users' => $typingUsers
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
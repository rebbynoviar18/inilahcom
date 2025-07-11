<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$taskId = $_POST['task_id'] ?? 0;

try {
    // Cek apakah ada tracking aktif
    $stmt = $pdo->prepare("
        SELECT id FROM time_tracking 
        WHERE user_id = ? AND task_id = ? AND end_time IS NULL
    ");
    $stmt->execute([$userId, $taskId]);
    $activeTracking = $stmt->fetch();
    
    if (!$activeTracking) {
        // Jika tidak ada tracking aktif, mulai baru
        $stmt = $pdo->prepare("
            INSERT INTO time_tracking (task_id, user_id, start_time, notes) 
            VALUES (?, ?, NOW(), 'Auto tracking (ping)')
        ");
        $stmt->execute([$taskId, $userId]);
        echo json_encode(['success' => true, 'message' => 'Tracking dimulai', 'action' => 'started']);
    } else {
        // Jika ada tracking aktif, update timestamp terakhir di log
        $logFile = fopen("../logs/auto_tracking.log", "a");
        fwrite($logFile, date('Y-m-d H:i:s') . " - User: $userId, Ping for task: $taskId\n");
        fclose($logFile);
        echo json_encode(['success' => true, 'message' => 'Tracking aktif', 'action' => 'pinged']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

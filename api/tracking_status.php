<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Cek apakah ada tracking aktif
    $stmt = $pdo->prepare("
        SELECT tt.*, t.title 
        FROM time_tracking tt
        JOIN tasks t ON tt.task_id = t.id
        WHERE tt.user_id = ? AND tt.end_time IS NULL
    ");
    $stmt->execute([$userId]);
    $activeTracking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeTracking) {
        echo json_encode([
            'success' => true, 
            'activeTracking' => $activeTracking,
            'message' => 'Active tracking found'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'activeTracking' => null,
            'message' => 'No active tracking'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

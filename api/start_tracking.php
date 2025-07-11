<?php
// File: api/start_tracking.php

require_once '../config/database.php';
require_once '../includes/auth.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan']);
    exit();
}

// Hapus session_start() karena sudah ada di includes/auth.php
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

$userId = $_SESSION['user_id'];
$taskId = $_POST['task_id'];
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validasi task
$stmt = $pdo->prepare("SELECT id, status FROM tasks WHERE id = ? AND assigned_to = ?");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task tidak ditemukan atau bukan milik Anda']);
    exit();
}

// Cek apakah sudah ada tracking yang aktif
$stmt = $pdo->prepare("SELECT id FROM time_tracking WHERE user_id = ? AND end_time IS NULL");
$stmt->execute([$userId]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah memiliki tracking yang aktif. Hentikan terlebih dahulu.']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Start new tracking
    $stmt = $pdo->prepare("
        INSERT INTO time_tracking (task_id, start_time, user_id, notes, is_auto) 
        VALUES (?, NOW(), ?, ?, 0)
    ");
    $stmt->execute([$taskId, $userId, $notes]);
    
    // Update task status to in_production if it's not already
    if ($task['status'] !== 'in_production' && $task['status'] !== 'revision') {
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_production' WHERE id = ?");
        $stmt->execute([$taskId]);
        
        // Log status change
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by) 
            VALUES (?, 'in_production', ?)
        ");
        $stmt->execute([$taskId, $userId]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Time tracking berhasil dimulai',
        'tracking_id' => $pdo->lastInsertId()
    ]);
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Gagal memulai tracking: ' . $e->getMessage()]);
}
?>
<script>
// Debugging AJAX
$.ajaxSetup({
    error: function(jqXHR, textStatus, errorThrown) {
        console.error("AJAX Error:", textStatus, errorThrown);
        console.log("Response:", jqXHR.responseText);
        alert("Error: " + textStatus + "\n" + errorThrown + "\n" + jqXHR.responseText);
    }
});
</script>
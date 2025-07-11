<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'production_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$taskId) {
    $_SESSION['error'] = "ID task tidak valid";
    header("Location: tasks.php");
    exit();
}

// Verifikasi task dimiliki oleh user
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND assigned_to = ?");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Anda tidak memiliki akses ke task ini";
    header("Location: tasks.php");
    exit();
}

// Verifikasi status task
if ($task['status'] !== 'waiting_confirmation') {
    $_SESSION['error'] = "Task tidak dalam status menunggu konfirmasi";
    header("Location: view_task.php?id=$taskId");
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Update status task
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_production' WHERE id = ?");
    $stmt->execute([$taskId]);
    
    // Catat perubahan status
    $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by) VALUES (?, ?, ?)");
    $stmt->execute([$taskId, 'in_production', $userId]);
    
    // Kirim notifikasi ke content team
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message, link)
        SELECT created_by, ?, ? 
        FROM tasks WHERE id = ?
    ");
    $stmt->execute(['Task telah dikonfirmasi oleh production team', "view_task.php?id=$taskId", $taskId]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Task berhasil dikonfirmasi";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Gagal mengkonfirmasi task: " . $e->getMessage();
}

header("Location: view_task.php?id=$taskId");
exit();
?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $taskId = $_POST['task_id'];
    $action = $_POST['action'];
    $notes = trim($_POST['review_notes'] ?? '');
    $userId = $_SESSION['user_id'];
    
    try {
        // Ambil informasi task
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            $_SESSION['error'] = "Task tidak ditemukan";
            header("Location: tasks.php");
            exit();
        }
        
        $pdo->beginTransaction();
        
        if ($action === 'approve') {
            // Update status task menjadi completed dan set verified_at
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', verified_at = NOW(), is_verified = 1 WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat log status
            $logNote = !empty($notes) ? $notes : "Task telah disetujui dan diselesaikan";
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, timestamp, notes) 
                VALUES (?, 'completed', ?, NOW(), ?)
            ");
            $stmt->execute([$taskId, $userId, $logNote]);
            
            // Kirim notifikasi ke pelaksana task
            sendNotification(
                $task['assigned_to'], 
                "Task '{$task['title']}' telah disetujui dan diselesaikan", 
                "view_task.php?id=$taskId"
            );
            
            $_SESSION['success'] = "Task berhasil disetujui dan diselesaikan";
        } 
        elseif ($action === 'revise') {
            if (empty($notes)) {
                throw new Exception("Catatan revisi harus diisi");
            }
            
            // Update status task menjadi revision
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'revision' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat log status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, timestamp, notes) 
                VALUES (?, 'revision', ?, NOW(), ?)
            ");
            $stmt->execute([$taskId, $userId, $notes]);
            
            // Catat detail revisi
            $stmt = $pdo->prepare("
                INSERT INTO task_revisions (task_id, revised_by, note, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$taskId, $userId, $notes]);
            
            // Kirim notifikasi ke pelaksana task
            sendNotification(
                $task['assigned_to'], 
                "Task '{$task['title']}' memerlukan revisi", 
                "view_task.php?id=$taskId"
            );
            
            $_SESSION['success'] = "Permintaan revisi berhasil dikirim";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: view_task.php?id=$taskId");
    exit();
} else {
    header("Location: tasks.php");
    exit();
}
?>
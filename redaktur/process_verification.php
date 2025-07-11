<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'redaktur_pelaksana') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    
    if (!$taskId) {
        $_SESSION['error'] = "ID Task tidak valid";
        header('Location: tasks.php');
        exit();
    }
    
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Harap berikan rating untuk task ini";
        header('Location: view_task.php?id=' . $taskId);
        exit();
    }
    
    try {
        // Verifikasi status task - Ubah untuk menerima status ready_for_review juga
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND (status = 'uploaded' OR status = 'ready_for_review')");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            throw new Exception("Task tidak ditemukan atau tidak dalam status yang dapat diverifikasi");
        }
        
        $pdo->beginTransaction();

        // Update status task dan tambahkan rating
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET status = 'completed', 
                verified_at = NOW(),
                rating = ?,
                is_verified = 1
            WHERE id = ?
        ");
        $stmt->execute([$rating, $taskId]);

        // Log perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by, notes)
            VALUES (?, 'completed', ?, ?)
        ");
        $stmt->execute([
            $taskId, 
            $userId, 
            "Task diverifikasi dengan rating: $rating/5"
        ]);

        // Bagian yang menangani revisi dari Creative Director
        $rejectReason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : '';
        if (!empty($rejectReason)) {
            $stmt = $pdo->prepare("INSERT INTO task_revisions (task_id, note, revised_by, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$taskId, $rejectReason, $userId]);
        }

        $pdo->commit();
        
        $_SESSION['success'] = "Task berhasil diverifikasi dan diselesaikan dengan rating $rating/5";
        header("Location: tasks.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header('Location: view_task.php?id=' . $taskId);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: view_task.php?id=' . $taskId);
        exit();
    }
} else {
    header('Location: tasks.php');
    exit();
}

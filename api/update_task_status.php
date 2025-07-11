<?php
// File: api/update_task_status.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$taskId = $_POST['task_id'] ?? 0;
$newStatus = $_POST['status'] ?? '';

// Log untuk debugging
$logFile = fopen("../logs/status_update.log", "a");
fwrite($logFile, date('Y-m-d H:i:s') . " - User: $userId, Task: $taskId, New Status: $newStatus\n");

try {
    // Verifikasi kepemilikan task
    $stmt = $pdo->prepare("SELECT assigned_to, created_by, status FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) {
        fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Task tidak ditemukan\n");
        echo json_encode(['success' => false, 'message' => 'Task tidak ditemukan']);
        fclose($logFile);
        exit();
    }

    $currentStatus = $task['status'];
    fwrite($logFile, date('Y-m-d H:i:s') . " - Current Status: $currentStatus\n");

    // Validasi perubahan status berdasarkan role
    $userRole = $_SESSION['role'];
    $allowedStatusChanges = [];

    if ($userRole === 'marketing_team') {
        $allowedStatusChanges = [
            'draft' => ['waiting_head_confirmation'],
            'rejected' => ['waiting_head_confirmation']
        ];
        
        if ($task['created_by'] !== $userId) {
            fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Tidak memiliki akses\n");
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke task ini']);
            fclose($logFile);
            exit();
        }
    } elseif ($userRole === 'content_team') {
        $allowedStatusChanges = [
            'draft' => ['waiting_confirmation'],
            'ready_for_review' => ['uploaded', 'revision'],
            'revision' => ['waiting_confirmation'],
            'waiting_confirmation' => ['uploaded'] // Untuk task distribusi dari marketing
        ];
        
        if ($task['created_by'] !== $userId && $task['assigned_to'] !== $userId) {
            fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Tidak memiliki akses\n");
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke task ini']);
            fclose($logFile);
            exit();
        }
    } elseif ($userRole === 'production_team') {
        $allowedStatusChanges = [
            'waiting_confirmation' => ['in_production', 'rejected'],
            'in_production' => ['ready_for_review'],
            'revision' => ['in_production']
        ];
        
        if ($task['assigned_to'] !== $userId) {
            fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Tidak memiliki akses\n");
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke task ini']);
            fclose($logFile);
            exit();
        }
    } elseif ($userRole === 'creative_director') {
        $allowedStatusChanges = [
            'uploaded' => ['completed'],
            'waiting_head_confirmation' => ['waiting_confirmation', 'rejected'] // Untuk task dari marketing
        ];
    }

    // Validasi perubahan status
    if (!isset($allowedStatusChanges[$currentStatus])) {
        fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Perubahan status tidak diizinkan\n");
        echo json_encode(['success' => false, 'message' => 'Perubahan status tidak diizinkan']);
        fclose($logFile);
        exit();
    }

    if (!in_array($newStatus, $allowedStatusChanges[$currentStatus])) {
        fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Perubahan status tidak valid\n");
        echo json_encode(['success' => false, 'message' => 'Perubahan status tidak valid']);
        fclose($logFile);
        exit();
    }

    // Update status task
    $pdo->beginTransaction();

    // Jika status berubah menjadi completed, update completed_at
    if ($newStatus === 'completed') {
        // Berikan poin kepada user yang menyelesaikan task
        if ($task['assigned_to']) {
            $result = calculateAndSavePoints($taskId, $task['assigned_to']);
            fwrite($logFile, date('Y-m-d H:i:s') . " - Points calculation for assigned_to: " . ($result ? "Success" : "Failed") . "\n");
        }
        
        // Berikan poin kepada user yang membuat task
        if ($task['created_by']) {
            $result = calculateAndSavePoints($taskId, $task['created_by']);
            fwrite($logFile, date('Y-m-d H:i:s') . " - Points calculation for created_by: " . ($result ? "Success" : "Failed") . "\n");
        }
        
        // Catat waktu penyelesaian
        $stmt = $pdo->prepare("UPDATE tasks SET status = ?, completed_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $taskId]);
    } else {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $taskId]);
    }

    // Catat perubahan status
    $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by) VALUES (?, ?, ?)");
    $stmt->execute([$taskId, $newStatus, $userId]);

    // TRACKING OTOMATIS: Mulai tracking jika status berubah menjadi in_production
    if ($newStatus === 'in_production' && $userRole === 'production_team') {
        // Cek apakah sudah ada tracking yang aktif
        $stmt = $pdo->prepare("
            SELECT id FROM time_tracking 
            WHERE user_id = ? AND end_time IS NULL
        ");
        $stmt->execute([$userId]);
        $activeTracking = $stmt->fetch();
        
        if ($activeTracking) {
            // Hentikan tracking yang aktif
            $stmt = $pdo->prepare("
                UPDATE time_tracking 
                SET end_time = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$activeTracking['id']]);
            fwrite($logFile, date('Y-m-d H:i:s') . " - Stopped existing tracking: " . $activeTracking['id'] . "\n");
        }
        
        // Mulai tracking baru
        $stmt = $pdo->prepare("
            INSERT INTO time_tracking (task_id, user_id, start_time, notes) 
            VALUES (?, ?, NOW(), 'Auto tracking - status changed to in_production')
        ");
        $stmt->execute([$taskId, $userId]);
        $newTrackingId = $pdo->lastInsertId();
        fwrite($logFile, date('Y-m-d H:i:s') . " - Started new tracking: $newTrackingId for task: $taskId\n");
    }
    
    // TRACKING OTOMATIS: Hentikan tracking jika status berubah menjadi ready_for_review
    if ($currentStatus === 'in_production' && $newStatus === 'ready_for_review' && $userRole === 'production_team') {
        $stmt = $pdo->prepare("
            UPDATE time_tracking 
            SET end_time = NOW() 
            WHERE user_id = ? AND task_id = ? AND end_time IS NULL
        ");
        $stmt->execute([$userId, $taskId]);
        $count = $stmt->rowCount();
        fwrite($logFile, date('Y-m-d H:i:s') . " - Stopped $count tracking for task: $taskId\n");
    }

    // Kirim notifikasi berdasarkan perubahan status
    if ($newStatus === 'waiting_head_confirmation') {
        // Task dari marketing team ke creative director
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'creative_director' LIMIT 1");
        $stmt->execute();
        $recipient = $stmt->fetchColumn();
        $message = "Ada task baru dari tim marketing yang memerlukan persetujuan Anda";
    } elseif ($newStatus === 'waiting_confirmation') {
        if ($currentStatus === 'waiting_head_confirmation') {
            // Task dari creative director ke content team
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'content_team' LIMIT 1");
            $stmt->execute();
            $recipient = $stmt->fetchColumn();
            $message = "Ada task baru dari tim marketing yang telah disetujui oleh Creative Director";
        } else {
            // Task normal dari content team ke production team
            $recipient = $task['assigned_to'];
            $message = "Task menunggu konfirmasi Anda";
        }
    } elseif ($newStatus === 'rejected') {
        if ($currentStatus === 'waiting_head_confirmation') {
            // Task ditolak oleh creative director ke marketing
            $recipient = $task['created_by'];
            $message = "Task Anda ditolak oleh Creative Director";
        } else {
            // Task ditolak oleh production team ke content team
            $recipient = $task['created_by'];
            $message = "Task Anda ditolak oleh tim produksi";
        }
    } elseif ($newStatus === 'ready_for_review') {
        $message = "Task siap direview";
        $recipient = $task['created_by'];
    } elseif ($newStatus === 'revision') {
        $message = "Task memerlukan revisi";
        $recipient = $task['assigned_to'];
    } elseif ($newStatus === 'uploaded') {
        $message = "Task telah diupload, menunggu verifikasi";
        // Kirim ke Creative Director
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'creative_director' LIMIT 1");
        $stmt->execute();
        $recipient = $stmt->fetchColumn();
    } elseif ($newStatus === 'completed') {
        // Notifikasi ke marketing jika task mereka selesai
        // Berikan poin kepada user yang menyelesaikan task
        if ($task['assigned_to']) {
            $result = calculateAndSavePoints($taskId, $task['assigned_to']);
            fwrite($logFile, date('Y-m-d H:i:s') . " - Points calculation for assigned_to: " . ($result ? "Success" : "Failed") . "\n");
        }
        
        // Berikan poin kepada user yang membuat task
        if ($task['created_by']) {
            $result = calculateAndSavePoints($taskId, $task['created_by']);
            fwrite($logFile, date('Y-m-d H:i:s') . " - Points calculation for created_by: " . ($result ? "Success" : "Failed") . "\n");
        }
        
        // Catat waktu penyelesaian
        $stmt = $pdo->prepare("UPDATE tasks SET completed_at = NOW() WHERE id = ?");
        $stmt->execute([$taskId]);
        
        // Notifikasi ke marketing jika task mereka selesai
        $stmt = $pdo->prepare("
            SELECT u.id 
            FROM users u
            JOIN tasks t ON u.id = t.created_by
            WHERE t.id = ? AND u.role = 'marketing_team'
        ");
        $stmt->execute([$taskId]);
        $marketingId = $stmt->fetchColumn();
        
        if ($marketingId) {
            $recipient = $marketingId;
            $message = "Task yang Anda buat telah selesai dan diverifikasi";
        } else {
            $recipient = $task['created_by'];
            $message = "Task telah selesai dan diverifikasi";
        }
    }

    if (isset($message) && isset($recipient)) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $stmt->execute([$recipient, $message, "view_task.php?id=$taskId"]);
    }

    $pdo->commit();
    fwrite($logFile, date('Y-m-d H:i:s') . " - Status updated successfully\n");
    echo json_encode(['success' => true, 'message' => 'Status task berhasil diperbarui']);
} catch (PDOException $e) {
    $pdo->rollBack();
    fwrite($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n");
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status: ' . $e->getMessage()]);
}

fclose($logFile);
?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $adjustedDeadline = isset($_POST['adjusted_deadline']) ? $_POST['adjusted_deadline'] : '';
    
    if (!$taskId) {
        $_SESSION['error'] = "ID Task tidak valid";
        header('Location: tasks.php');
        exit();
    }
    
    try {
        // Verifikasi status task
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND status = 'waiting_head_confirmation'");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            throw new Exception("Task tidak ditemukan atau tidak dalam status menunggu konfirmasi");
        }
        
        $pdo->beginTransaction();
        
        if ($action === 'approve') {
            $assignedTo = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : 0;
            
            if (!$assignedTo) {
                throw new Exception("Harap pilih pelaksana task");
            }
            
            if (empty($adjustedDeadline)) {
                throw new Exception("Harap tentukan deadline untuk task ini");
            }
            
            // Verifikasi pelaksana berdasarkan kategori
            $stmt = $pdo->prepare("
                SELECT u.id, u.role, u.name, u.whatsapp_number, c.name as category_name 
                FROM users u
                JOIN tasks t ON t.id = ?
                JOIN categories c ON t.category_id = c.id
                WHERE u.id = ?
            ");
            $stmt->execute([$taskId, $assignedTo]);
            $executor = $stmt->fetch();
            
            if (!$executor) {
                throw new Exception("Pelaksana tidak valid");
            }
            
            // Validasi role pelaksana sesuai kategori
            $categoryName = $executor['category_name'];
            $executorRole = $executor['role'];
            
            if (($categoryName === 'Distribusi' && $executorRole !== 'content_team') || 
                ($categoryName === 'Produksi' && $executorRole !== 'production_team')) {
                throw new Exception("Pelaksana tidak sesuai dengan kategori task");
            }
            
            // Update status task, pelaksana, dan deadline yang disesuaikan
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET status = 'waiting_confirmation', 
                    assigned_to = ?,
                    deadline = ?
                WHERE id = ?
            ");
            $stmt->execute([$assignedTo, $adjustedDeadline, $taskId]);
            
            // Catat log status dengan informasi deadline yang disesuaikan
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'waiting_confirmation', ?, ?)
            ");
            
            $deadlineNotes = "Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi " . date('d/m/Y', strtotime($adjustedDeadline));
            if (!empty($notes)) {
                $deadlineNotes .= ". Catatan: $notes";
            }
            
            $stmt->execute([
                $taskId, 
                $userId, 
                $deadlineNotes
            ]);
            
            // Kirim notifikasi ke pelaksana
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $assignedTo,
                "Anda mendapat task baru yang perlu dikerjakan dengan deadline " . date('d/m/Y', strtotime($adjustedDeadline)),
                "../" . ($categoryName === 'Distribusi' ? 'content' : 'production') . "/view_task.php?id=$taskId"
            ]);
            
            // Ambil detail task untuk notifikasi WhatsApp
            $taskStmt = $pdo->prepare("SELECT title FROM tasks WHERE id = ?");
            $taskStmt->execute([$taskId]);
            $taskDetail = $taskStmt->fetch();
            
            // Kirim notifikasi WhatsApp ke pelaksana
            if ($executor['whatsapp_number']) {
                require_once '../config/whatsapp.php';
                
                $message = "Halo {$executor['name']},\n\n";
                $message .= "Anda mendapat task baru yang perlu dikerjakan:\n";
                $message .= "Judul: {$taskDetail['title']}\n";
                $message .= "Deadline: " . date('d M Y H:i', strtotime($adjustedDeadline)) . " WIB\n\n";
                $message .= "Silakan login ke dashboard untuk melihat detail task.\n";
                $message .= "http://" . $_SERVER['HTTP_HOST'] . "/creative/";
                
                sendWhatsAppNotification($executor['whatsapp_number'], $message);
            }
            
            $message = "Task berhasil disetujui dan diteruskan ke pelaksana dengan deadline yang disesuaikan";
        } else if ($action === 'reject') {
            if (empty($notes)) {
                throw new Exception("Harap berikan alasan penolakan");
            }
            
            // Update status task
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat log status
            $stmt = $pdo->prepare("
                INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
                VALUES (?, 'rejected', ?, ?)
            ");
            $stmt->execute([
                $taskId, 
                $userId, 
                "Task ditolak oleh Creative Director: $notes"
            ]);
            
            // Kirim notifikasi ke pembuat task
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $task['created_by'],
                "Task Anda ditolak oleh Creative Director",
                "../marketing/view_task.php?id=$taskId"
            ]);
            
            $message = "Task berhasil ditolak";
        } else {
            throw new Exception("Tindakan tidak valid");
        }
        
        $pdo->commit();
        $_SESSION['success'] = $message;
        header('Location: tasks.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: view_task.php?id=' . $taskId);
        exit();
    }
} else {
    header('Location: tasks.php');
    exit();
}
?>
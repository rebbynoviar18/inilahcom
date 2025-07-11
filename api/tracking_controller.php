<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Pastikan user sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$taskId = $_POST['task_id'] ?? 0;
$trackingId = $_POST['tracking_id'] ?? 0;

// Buat log file untuk debugging
$logFile = "../logs/tracking_" . date('Y-m-d') . ".log";
file_put_contents($logFile, date('Y-m-d H:i:s') . " - User: $userId, Action: $action, Task: $taskId\n", FILE_APPEND);

try {
    // Cek apakah user adalah production team
    if ($_SESSION['role'] !== 'production_team' && $_SESSION['role'] !== 'redaksi') {
        echo json_encode(['success' => false, 'message' => 'Hanya production team yang dapat melakukan tracking']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    if ($action === 'start') {
        // Verifikasi task
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$taskId, $userId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Task tidak ditemukan atau Anda tidak memiliki akses']);
            $pdo->rollBack();
            exit();
        }
        
        // Cek apakah sudah ada tracking aktif untuk task ini
        $stmt = $pdo->prepare("
            SELECT id FROM time_tracking 
            WHERE user_id = ? AND task_id = ? AND end_time IS NULL
        ");
        $stmt->execute([$userId, $taskId]);
        $activeTaskTracking = $stmt->fetch();
        
        if ($activeTaskTracking) {
            // Jika sudah ada tracking untuk task ini, beri tahu user
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Task ini sudah dalam tracking']);
            exit();
        }
        
        // Mulai tracking baru tanpa menghentikan tracking lain
        $stmt = $pdo->prepare("
            INSERT INTO time_tracking (task_id, user_id, start_time, notes) 
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([$taskId, $userId, 'Started manually']);
        $newTrackingId = $pdo->lastInsertId();
        
        // Update status task jika belum in_production
        if ($task['status'] !== 'in_production' && $task['status'] !== 'revision') {
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_production' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat perubahan status
            logTaskStatus($taskId, 'in_production', $userId);
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updated task status to in_production\n", FILE_APPEND);
        }
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Started new tracking: $newTrackingId for task: $taskId\n", FILE_APPEND);
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Tracking dimulai', 
            'tracking_id' => $newTrackingId,
            'start_time' => date('Y-m-d H:i:s')
        ]);
    } 
    elseif ($action === 'stop') {
        // Hentikan tracking berdasarkan ID atau task ID
        if ($trackingId > 0) {
            $stmt = $pdo->prepare("
                UPDATE time_tracking 
                SET end_time = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$trackingId, $userId]);
        } elseif ($taskId > 0) {
            $stmt = $pdo->prepare("
                UPDATE time_tracking 
                SET end_time = NOW() 
                WHERE task_id = ? AND user_id = ? AND end_time IS NULL
            ");
            $stmt->execute([$taskId, $userId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE time_tracking 
                SET end_time = NOW() 
                WHERE user_id = ? AND end_time IS NULL
            ");
            $stmt->execute([$userId]);
        }
        
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Stopped $count active tracking(s)\n", FILE_APPEND);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Tracking dihentikan']);
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - No active tracking found\n", FILE_APPEND);
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Tidak ada tracking yang aktif']);
        }
    } 
    elseif ($action === 'status') {
        // Cek status tracking aktif (semua task)
        $stmt = $pdo->prepare("
            SELECT tt.*, t.title 
            FROM time_tracking tt
            JOIN tasks t ON tt.task_id = t.id
            WHERE tt.user_id = ? AND tt.end_time IS NULL
            ORDER BY tt.start_time DESC
        ");
        $stmt->execute([$userId]);
        $activeTrackings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($activeTrackings) {
            // Tambahkan informasi elapsed time untuk setiap tracking
            foreach ($activeTrackings as &$tracking) {
                $tracking['elapsed'] = time_elapsed_string($tracking['start_time'], false);
            }
            
            echo json_encode([
                'success' => true, 
                'activeTrackings' => $activeTrackings
            ]);
        } else {
            echo json_encode(['success' => true, 'activeTrackings' => []]);
        }
        exit(); // Tidak perlu commit/rollback untuk operasi read-only
    }
    else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

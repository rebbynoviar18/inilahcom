<?php
// Pastikan session dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$debug = isset($_GET['debug']) || isset($_POST['debug']);
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan']);
    exit();
}

// Pastikan user sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$taskId = $_POST['task_id'] ?? 0;

// Log untuk debugging
$logFile = fopen("../logs/tracking.log", "a");
fwrite($logFile, date('Y-m-d H:i:s') . " - User: $userId, Action: $action, Task: $taskId\n");

try {
    // Cek apakah user adalah production team
    if ($_SESSION['role'] !== 'production_team' && $_SESSION['role'] !== 'redaksi') {
        fwrite($logFile, date('Y-m-d H:i:s') . " - Error: User bukan production team\n");
        echo json_encode(['success' => false, 'message' => 'Hanya production team yang dapat melakukan tracking']);
        fclose($logFile);
        exit();
    }
    
    $pdo->beginTransaction();
    
    if ($action === 'start') {
        // Verifikasi task
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$taskId, $userId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Task tidak ditemukan atau tidak dimiliki user\n");
            echo json_encode(['success' => false, 'message' => 'Task tidak ditemukan atau Anda tidak memiliki akses']);
            fclose($logFile);
            $pdo->rollBack();
            exit();
        }
        
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
            VALUES (?, ?, NOW(), 'Manual tracking')
        ");
        $stmt->execute([$taskId, $userId]);
        $newTrackingId = $pdo->lastInsertId();
        
        // Update status task jika belum in_production
        if ($task['status'] !== 'in_production') {
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_production' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Catat perubahan status
            $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, status, updated_by) VALUES (?, 'in_production', ?)");
            $stmt->execute([$taskId, $userId]);
            
            fwrite($logFile, date('Y-m-d H:i:s') . " - Updated task status to in_production\n");
        }
        
        fwrite($logFile, date('Y-m-d H:i:s') . " - Started new tracking: $newTrackingId for task: $taskId\n");
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Tracking dimulai', 'tracking_id' => $newTrackingId]);
    } 
    elseif ($action === 'stop') {
        // Hentikan semua tracking yang aktif untuk user
        $stmt = $pdo->prepare("
            UPDATE time_tracking 
            SET end_time = NOW() 
            WHERE user_id = ? AND end_time IS NULL
        ");
        $stmt->execute([$userId]);
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            fwrite($logFile, date('Y-m-d H:i:s') . " - Stopped $count active tracking(s)\n");
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Tracking dihentikan']);
        } else {
            fwrite($logFile, date('Y-m-d H:i:s') . " - No active tracking found\n");
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Tidak ada tracking yang aktif']);
        }
    } 
    else {
        fwrite($logFile, date('Y-m-d H:i:s') . " - Error: Invalid action\n");
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    fwrite($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n");
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

fclose($logFile);
?>
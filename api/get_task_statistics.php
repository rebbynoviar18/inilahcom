<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Periksa login
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$role = getUserRole();

// Set header untuk JSON response
header('Content-Type: application/json');

try {
    $response = [];
    
    // Statistik berdasarkan role
    switch ($role) {
        case 'creative_director':
            // Total task yang menunggu persetujuan
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE status = 'waiting_head_confirmation'
            ");
            $stmt->execute();
            $response['waiting_approval'] = $stmt->fetch()['count'];
            
            // Total task yang sedang dikerjakan
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE status IN ('in_progress', 'in_production')
            ");
            $stmt->execute();
            $response['in_progress'] = $stmt->fetch()['count'];
            
            // Total task yang menunggu verifikasi
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE status = 'uploaded'
            ");
            $stmt->execute();
            $response['waiting_verification'] = $stmt->fetch()['count'];
            
            // Total task yang selesai bulan ini
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE status = 'completed' 
                AND MONTH(completed_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(completed_at) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute();
            $response['completed_this_month'] = $stmt->fetch()['count'];
            
            // Statistik berdasarkan kategori
            $stmt = $pdo->prepare("
                SELECT c.name, COUNT(t.id) as count
                FROM tasks t
                JOIN categories c ON t.category_id = c.id
                GROUP BY c.name
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute();
            $response['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Statistik berdasarkan akun
            $stmt = $pdo->prepare("
                SELECT a.name, COUNT(t.id) as count
                FROM tasks t
                JOIN accounts a ON t.account_id = a.id
                GROUP BY a.name
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute();
            $response['by_account'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'marketing_team':
            // Total task draft
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE created_by = ? AND status = 'draft'
            ");
            $stmt->execute([$userId]);
            $response['draft'] = $stmt->fetch()['count'];
            
            // Total task yang menunggu persetujuan
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE created_by = ? AND status = 'waiting_head_confirmation'
            ");
            $stmt->execute([$userId]);
            $response['waiting_approval'] = $stmt->fetch()['count'];
            
            // Total task yang sedang dikerjakan
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE created_by = ? AND status IN ('in_progress', 'in_production')
            ");
            $stmt->execute([$userId]);
            $response['in_progress'] = $stmt->fetch()['count'];
            
            // Total task yang menunggu review
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE created_by = ? AND status = 'ready_for_review'
            ");
            $stmt->execute([$userId]);
            $response['waiting_review'] = $stmt->fetch()['count'];
            
            // Total task yang selesai bulan ini
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE created_by = ? AND status = 'completed' 
                AND MONTH(completed_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(completed_at) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$userId]);
            $response['completed_this_month'] = $stmt->fetch()['count'];
            break;
            
        case 'content_team':
            // Total task yang menunggu konfirmasi
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE assigned_to = ? AND status = 'waiting_confirmation'
            ");
            $stmt->execute([$userId]);
            $response['waiting_confirmation'] = $stmt->fetch()['count'];
            
            // Total task yang sedang dikerjakan
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE assigned_to = ? AND status IN ('in_progress', 'in_production')
            ");
            $stmt->execute([$userId]);
            $response['in_progress'] = $stmt->fetch()['count'];
            
            // Total task yang memerlukan revisi
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE assigned_to = ? AND status = 'revision'
            ");
            $stmt->execute([$userId]);
            $response['revision'] = $stmt->fetch()['count'];
            
            // Total task yang selesai bulan ini
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM tasks 
                WHERE assigned_to = ? AND status = 'completed' 
                AND MONTH(completed_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(completed_at) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$userId]);
            $response['completed_this_month'] = $stmt->fetch()['count'];
            
            // Waktu pengerjaan rata-rata (dalam jam)
            $stmt = $pdo->prepare("
                SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours
                FROM tasks
                WHERE assigned_to = ? AND status = 'completed'
            ");
            $stmt->execute([$userId]);
            $avgHours = $stmt->fetch()['avg_hours'];
            $response['avg_completion_time'] = $avgHours ? round($avgHours, 1) : 0;
            break;
    }
    
    // Statistik umum untuk semua role
    // Task berdasarkan status
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count
        FROM tasks
        GROUP BY status
    ");
    $stmt->execute();
    $statusStats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $statusStats[$row['status']] = (int)$row['count'];
    }
    $response['status_stats'] = $statusStats;
    
    echo json_encode(['success' => true, 'data' => $response]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

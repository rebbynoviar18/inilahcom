<?php
// File: includes/points_functions.php

require_once __DIR__ . '/../../config/database.php';

function getTaskPoints($team, $category, $taskType) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT points FROM task_point_settings WHERE team = ? AND category = ? AND task_type = ?");
    $stmt->execute([$team, $category, $taskType]);
    $result = $stmt->fetch();
    
    return $result ? $result['points'] : 1.0; // Default 1.0 jika tidak ada pengaturan
}

function getTeamFromRole($role) {
    // Tambahkan log untuk debugging
    error_log("getTeamFromRole called with role: '$role'");
    
    switch ($role) {
        case 'content_team':
            error_log("Returning 'content_team'");
            return 'content_team';
        case 'production_team':
            error_log("Returning 'production_team'");
            return 'production_team';
        case 'marketing_team':
            error_log("Returning 'marketing_team'");
            return 'marketing_team';
        default:
            error_log("Returning null for unknown role: '$role'");
            return null;
    }
}

function calculateAndSavePoints($taskId, $userId) {
    global $pdo;
    
    try {
        // Log untuk debugging
        error_log("Calculating points for task $taskId and user $userId");
        
        // Ambil role user terlebih dahulu
        $userStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $userRole = $userStmt->fetchColumn();
        
        if (!$userRole) {
            error_log("User role not found for user $userId");
            return false;
        }
        
        // Ambil detail task
        $stmt = $pdo->prepare("
            SELECT 
                t.*, 
                c.name as category_name, 
                ct.name as content_type_name
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN content_types ct ON t.content_type_id = ct.id
            WHERE t.id = ?
        ");
        $stmt->execute([$taskId]);
        $taskDetail = $stmt->fetch();
        
        if (!$taskDetail) {
            error_log("Task detail not found for task $taskId");
            return false;
        }
        
        // Cek apakah poin sudah pernah diberikan untuk task ini
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_points WHERE task_id = ? AND user_id = ?");
        $stmt->execute([$taskId, $userId]);
        if ($stmt->fetchColumn() > 0) {
            // Poin sudah pernah diberikan
            error_log("Points already awarded for task $taskId and user $userId");
            return true;
        }
        
        // Tentukan team berdasarkan role user
        $team = getTeamFromRole($userRole);
        $category = $taskDetail['category_name'] ?? 'Default';
        $taskType = $taskDetail['content_type_name'] ?? 'Default';
        
        // Debug info
        error_log("User role: {$userRole}, Team: {$team}, Category: {$category}, Task Type: {$taskType}");
        
        // Ambil pengaturan poin dari tabel task_point_settings
        $points = getTaskPoints($team, $category, $taskType);
        
        // Tambahkan bonus poin berdasarkan jumlah platform untuk task distribusi
        if (in_array($category, ['Distribusi', 'Daily Content', 'Program'])) {
            // Ambil jumlah platform dari task_links
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT platform) FROM task_links WHERE task_id = ?");
            $stmt->execute([$taskId]);
            $platformCount = $stmt->fetchColumn();
            
            // Debug untuk melihat nilai team yang sebenarnya
            error_log("Team value: '$team', Category: '$category', Platform count: $platformCount");
            
            // Hanya berikan bonus platform untuk tim konten
            if ($team === 'content_team' && $platformCount > 0) {
                // Tambahkan 0.5 poin untuk setiap platform
                $platformBonus = $platformCount * 0.5 - 0.5; 
                $points += $platformBonus;
                error_log("Added platform bonus: $platformBonus for {$platformCount} platforms to content team");
            } else {
                error_log("No platform bonus for team: $team (not content_team) or platformCount: $platformCount");
            }
        }
        
        error_log("Points to award: $points");
        
        // Simpan poin
        $stmt = $pdo->prepare("
            INSERT INTO user_points (user_id, task_id, points, earned_at)
            VALUES (?, ?, ?, NOW())
        ");
        $result = $stmt->execute([$userId, $taskId, $points]);
        
        if ($result) {
            error_log("Points saved successfully: $points points for user $userId on task $taskId");
            return true;
        } else {
            error_log("Failed to save points");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Error in calculateAndSavePoints: " . $e->getMessage());
        return false;
    }
}

function getUserPoints($userId, $period = 'all') {
    global $pdo;
    
    try {
        $sql = "SELECT COALESCE(SUM(points), 0) as total FROM user_points WHERE user_id = ?";
        $params = [$userId];
        
        if ($period === 'month') {
            $sql .= " AND earned_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($period === 'week') {
            $sql .= " AND earned_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in getUserPoints: " . $e->getMessage());
        return 0;
    }
}

// Fungsi untuk mendapatkan peringkat pengguna berdasarkan poin
function getUserRankings($role, $period = 'month', $limit = 5) {
    global $pdo;
    
    // Pastikan limit adalah integer
    $limit = (int)$limit;
    
    $timeCondition = "";
    if ($period == 'month') {
        $timeCondition = "AND MONTH(up.earned_at) = MONTH(CURRENT_DATE()) AND YEAR(up.earned_at) = YEAR(CURRENT_DATE())";
    } elseif ($period == 'week') {
        $timeCondition = "AND YEARWEEK(up.earned_at, 1) = YEARWEEK(CURRENT_DATE(), 1)";
    } elseif (preg_match('/^(\d{4})-(\d{2})$/', $period, $matches)) {
        // Format YYYY-MM
        $timeCondition = "AND MONTH(up.earned_at) = {$matches[2]} AND YEAR(up.earned_at) = {$matches[1]}";
    }
    
    $sql = "
        SELECT u.id, u.name, u.profile_photo, 
               COUNT(DISTINCT up.task_id) as tasks_completed, 
               COALESCE(SUM(up.points), 0) as total_points
        FROM users u
        LEFT JOIN user_points up ON u.id = up.user_id $timeCondition
        WHERE u.role = :role
        GROUP BY u.id
        ORDER BY total_points DESC, tasks_completed DESC
        LIMIT :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getDailyPointsTarget($role) {
    global $pdo;
    
    $settingKey = 'daily_points_target_' . ($role === 'production_team' ? 'production' : 'content');
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM target_settings WHERE setting_key = ?");
        $stmt->execute([$settingKey]);
        $result = $stmt->fetchColumn();
        
        return $result ? (float)$result : ($role === 'production_team' ? 20.0 : 15.0);
    } catch (PDOException $e) {
        error_log("Error getting daily points target: " . $e->getMessage());
        return $role === 'production_team' ? 20.0 : 15.0;
    }
}

function getUserDailyPoints($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(points), 0) as total_points
            FROM user_points
            WHERE user_id = ? AND DATE(earned_at) = CURDATE()
        ");
        $stmt->execute([$userId]);
        return (float)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting user daily points: " . $e->getMessage());
        return 0;
    }
}

function getUserTargetProgress($userId, $period = 'daily') {
    global $pdo;
    
    try {
        // Ambil role user untuk menentukan target
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        
        // Tentukan setting key berdasarkan role
        $settingKey = '';
        if ($role === 'content_team') {
            $settingKey = 'daily_points_target_content';
        } elseif ($role === 'production_team') {
            $settingKey = 'daily_points_target_production';
        } else {
            $settingKey = 'daily_points_target_marketing';
        }
        
        // Ambil target dari database
        $stmt = $pdo->prepare("SELECT setting_value FROM target_settings WHERE setting_key = ?");
        $stmt->execute([$settingKey]);
        $targetPoints = $stmt->fetchColumn();
        
        // Jika tidak ada di database, gunakan nilai default
        if (!$targetPoints) {
            if ($role === 'production_team') {
                $targetPoints = 20.0;
            } elseif ($role === 'content_team') {
                $targetPoints = 15.0;
            } else {
                $targetPoints = 10.0;
            }
        }
        
        // Tentukan rentang waktu berdasarkan periode
        $dateRange = '';
        if ($period === 'daily') {
            $dateRange = "AND DATE(earned_at) = CURDATE()";
        } elseif ($period === 'weekly') {
            $dateRange = "AND YEARWEEK(earned_at, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($period === 'monthly') {
            $dateRange = "AND MONTH(earned_at) = MONTH(CURDATE()) AND YEAR(earned_at) = YEAR(CURDATE())";
        }
        
        // Hitung jumlah poin yang diperoleh dalam periode
        $pointsQuery = "SELECT COALESCE(SUM(points), 0) FROM user_points WHERE user_id = ? $dateRange";
        $stmt = $pdo->prepare($pointsQuery);
        $stmt->execute([$userId]);
        $completedPoints = $stmt->fetchColumn();
        
        // Hitung persentase
        $percentage = ($targetPoints > 0) ? min(100, round(($completedPoints / $targetPoints) * 100)) : 0;
        $isAchieved = $completedPoints >= $targetPoints;
        
        return [
            'target' => (float)$targetPoints,
            'completed' => (float)$completedPoints,
            'percentage' => $percentage,
            'is_achieved' => $isAchieved
        ];
    } catch (PDOException $e) {
        error_log("Error in getUserTargetProgress: " . $e->getMessage());
        return [
            'target' => 0,
            'completed' => 0,
            'percentage' => 0,
            'is_achieved' => false
        ];
    }
}

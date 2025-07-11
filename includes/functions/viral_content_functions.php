<?php
// File: includes/viral_content_functions.php

require_once __DIR__ . '/../../config/database.php';

function isDailyViewsTargetAchieved($platform) {
    global $pdo;
    
    try {
        // Ambil target views dari pengaturan
        $target = getViewsTarget($platform, 'daily');
        
        // Cek apakah ada konten yang mencapai target
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM viral_content 
            WHERE platform = ? AND DATE(marked_date) = CURDATE() AND views >= ?
        ");
        $stmt->execute([$platform, $target]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking daily views target: " . $e->getMessage());
        return false;
    }
}

function isWeeklyViewsTargetAchieved($platform) {
    global $pdo;
    
    try {
        // Ambil target views dari pengaturan
        $target = getViewsTarget($platform, 'weekly');
        
        // Mendapatkan tanggal awal dan akhir minggu ini (Senin-Minggu)
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM viral_content 
            WHERE platform = ? AND marked_date BETWEEN ? AND ? AND views >= ?
        ");
        $stmt->execute([$platform, $startOfWeek, $endOfWeek, $target]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking weekly views target: " . $e->getMessage());
        return false;
    }
}

function getDailyViralContent($platform) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT v.*, t.title, t.assigned_to, u.name as assigned_to_name
            FROM viral_content v
            JOIN tasks t ON v.task_id = t.id
            JOIN users u ON t.assigned_to = u.id
            WHERE v.platform = ? AND DATE(v.marked_date) = CURDATE()
            ORDER BY v.views DESC
            LIMIT 1
        ");
        $stmt->execute([$platform]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting daily viral content: " . $e->getMessage());
        return false;
    }
}

function getWeeklyViralContent($platform) {
    global $pdo;
    
    try {
        // Mendapatkan tanggal awal dan akhir minggu ini (Senin-Minggu)
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        $stmt = $pdo->prepare("
            SELECT v.*, t.title, t.assigned_to, u.name as assigned_to_name
            FROM viral_content v
            JOIN tasks t ON v.task_id = t.id
            JOIN users u ON t.assigned_to = u.id
            WHERE v.platform = ? AND v.marked_date BETWEEN ? AND ?
            ORDER BY v.views DESC
            LIMIT 1
        ");
        $stmt->execute([$platform, $startOfWeek, $endOfWeek]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting weekly viral content: " . $e->getMessage());
        return false;
    }
}

function getViewsTarget($platform, $period = 'daily') {
    global $pdo;
    
    $settingKey = "{$period}_views_target_{$platform}";
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM target_settings WHERE setting_key = ?");
        $stmt->execute([$settingKey]);
        $result = $stmt->fetchColumn();
        
        // Nilai default jika tidak ada di database
        if (!$result) {
            if ($platform === 'instagram') {
                return $period === 'daily' ? 5000 : 20000;
            } else { // tiktok
                return $period === 'daily' ? 10000 : 50000;
            }
        }
        
        return (int)$result;
    } catch (PDOException $e) {
        error_log("Error getting views target: " . $e->getMessage());
        // Nilai default
        if ($platform === 'instagram') {
            return $period === 'daily' ? 5000 : 20000;
        } else { // tiktok
            return $period === 'daily' ? 10000 : 50000;
        }
    }
}

function getCollectiveViralTargetProgress($period = 'daily') {
    global $pdo;
    
    try {
        $result = [
            'instagram' => [
                'target' => 0,
                'achieved' => 0,
                'percentage' => 0
            ],
            'tiktok' => [
                'target' => 0,
                'achieved' => 0,
                'percentage' => 0
            ]
        ];
        
        // Ambil target dari database
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM target_settings 
                              WHERE setting_key IN (?, ?)");
        $igKey = "{$period}_views_target_instagram";
        $ttKey = "{$period}_views_target_tiktok";
        $stmt->execute([$igKey, $ttKey]);
        
        $targets = [];
        while ($row = $stmt->fetch()) {
            $targets[$row['setting_key']] = (int)$row['setting_value'];
        }
        
        // Set target dari database atau gunakan default
        $result['instagram']['target'] = $targets[$igKey] ?? ($period === 'daily' ? 1 : 3);
        $result['tiktok']['target'] = $targets[$ttKey] ?? ($period === 'daily' ? 1 : 3);
        
        // Tentukan rentang waktu berdasarkan periode
        $dateCondition = '';
        if ($period === 'daily') {
            $dateCondition = "AND DATE(marked_date) = CURDATE()";
        } elseif ($period === 'weekly') {
            $dateCondition = "AND YEARWEEK(marked_date, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($period === 'monthly') {
            $dateCondition = "AND MONTH(marked_date) = MONTH(CURDATE()) AND YEAR(marked_date) = YEAR(CURDATE())";
        }
        
        // Hitung jumlah konten viral Instagram
        $igMinViews = 50000; // Minimum views untuk dianggap viral
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM viral_content 
                              WHERE platform = 'instagram' AND views >= ? $dateCondition");
        $stmt->execute([$igMinViews]);
        $result['instagram']['achieved'] = (int)$stmt->fetchColumn();
        
        // Hitung jumlah konten viral TikTok
        $ttMinViews = 100000; // Minimum views untuk dianggap viral
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM viral_content 
                              WHERE platform = 'tiktok' AND views >= ? $dateCondition");
        $stmt->execute([$ttMinViews]);
        $result['tiktok']['achieved'] = (int)$stmt->fetchColumn();
        
        // Hitung persentase
        $result['instagram']['percentage'] = ($result['instagram']['target'] > 0) 
            ? min(100, round(($result['instagram']['achieved'] / $result['instagram']['target']) * 100)) 
            : 0;
            
        $result['tiktok']['percentage'] = ($result['tiktok']['target'] > 0) 
            ? min(100, round(($result['tiktok']['achieved'] / $result['tiktok']['target']) * 100)) 
            : 0;
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error in getCollectiveViralTargetProgress: " . $e->getMessage());
        return [
            'instagram' => ['target' => 0, 'achieved' => 0, 'percentage' => 0],
            'tiktok' => ['target' => 0, 'achieved' => 0, 'percentage' => 0]
        ];
    }
}
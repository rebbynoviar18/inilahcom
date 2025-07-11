<?php
// File: includes/user_functions.php

require_once __DIR__ . '/../../config/database.php';

function getUserNameById($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function getUserProfilePhoto($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $photo = $stmt->fetchColumn();
        
        // Use document root to create absolute path
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/creative/uploads/profiles/';
        
        if ($photo && file_exists($uploadDir . $photo)) {
            // Return web path (not filesystem path)
            return '/creative/uploads/profiles/' . $photo;
        }
        
        // Return default avatar if no photo
        return '/creative/assets/images/default-avatar.jpg';
    } catch (PDOException $e) {
        error_log("Error in getUserProfilePhoto: " . $e->getMessage());
        // Return default avatar on error
        return '/creative/assets/images/default-avatar.jpg';
    }
}

function getUserRoleLabel($role) {
    $roleLabels = [
        'creative_director' => 'Creative Director',
        'content_team' => 'Tim Konten',
        'production_team' => 'Tim Produksi',
        'admin' => 'Administrator',
        'marketing_team' => 'Tim Marketing',
        'redaksi' => 'Tim Redaksi',
        'redaktur' => 'Redaktur Pelaksana'
    ];
    
    return $roleLabels[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

function getUserSettings($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Jika tidak ada pengaturan, kembalikan default
        if (!$settings) {
            return [
                'email_notifications' => 1,
                'browser_notifications' => 1,
                'dark_mode' => 0,
                'language' => 'id'
            ];
        }
        
        return $settings;
    } catch (PDOException $e) {
        // Tangani error dengan diam-diam
        error_log("Error in getUserSettings: " . $e->getMessage());
        
        // Kembalikan pengaturan default jika terjadi error
        return [
            'email_notifications' => 1,
            'browser_notifications' => 1,
            'dark_mode' => 0,
            'language' => 'id'
        ];
    }
}

function getUserProfilePhotoWithName($userId, $userName, $imgClass = "rounded-circle me-2", $imgSize = "32") {
    $photoUrl = getUserProfilePhoto($userId);
    return '<div class="d-flex align-items-center">
                <img src="' . $photoUrl . '" alt="' . htmlspecialchars($userName) . '" 
                     class="' . $imgClass . '" width="' . $imgSize . '" height="' . $imgSize . '">
                <span>' . htmlspecialchars($userName) . '</span>
            </div>';
}

function getUsersByRole($roles) {
    global $pdo;
    
    try {
        if (is_array($roles)) {
            $placeholders = str_repeat('?,', count($roles) - 1) . '?';
            $query = "SELECT * FROM users WHERE role IN ($placeholders) ORDER BY name ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute($roles);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? ORDER BY name ASC");
            $stmt->execute([$roles]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getUsersByRole: " . $e->getMessage());
        return [];
    }
}

function isUserOnline($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 1 
            FROM user_sessions 
            WHERE user_id = ? AND last_activity > UNIX_TIMESTAMP() - 300
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ? true : false;
    } catch (Exception $e) {
        return false;
    }
}

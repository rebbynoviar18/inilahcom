<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set headers untuk mencegah caching
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Cek autentikasi
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Optimasi query dengan prepared statement
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.profile_photo, u.role,
               COALESCE(MAX(cm.created_at), '1970-01-01 00:00:00') as last_message_time,
               COALESCE(unread.unread_count, 0) as unread_count
        FROM users u 
        LEFT JOIN chat_messages cm ON (
            (cm.sender_id = u.id AND cm.receiver_id = :user_id) 
            OR (cm.sender_id = :user_id AND cm.receiver_id = u.id)
        )
        LEFT JOIN (
            SELECT sender_id, COUNT(*) as unread_count 
            FROM chat_messages 
            WHERE receiver_id = :user_id AND is_read = 0 
            GROUP BY sender_id
        ) unread ON unread.sender_id = u.id
        WHERE u.id != :user_id
        GROUP BY u.id, u.name, u.profile_photo, u.role, unread.unread_count
        ORDER BY 
            CASE WHEN unread.unread_count > 0 THEN 0 ELSE 1 END,
            last_message_time DESC,
            u.name ASC
    ");
    
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kelompokkan pengguna berdasarkan role dan status online
    $usersByRole = [
        'online' => ['title' => 'Online', 'users' => []],
        'content_team' => ['title' => 'Tim Konten', 'users' => []],
        'production_team' => ['title' => 'Tim Produksi', 'users' => []],
        'marketing_team' => ['title' => 'Tim Marketing', 'users' => []],
        'creative_director' => ['title' => 'Management', 'users' => []],
        'other' => ['title' => 'Lainnya', 'users' => []]
    ];

    $onlineUserIds = [];

    // Kelompokkan pengguna
    foreach ($chatUsers as $user) {
        $isOnline = isUserOnline($user['id']);
        
        if ($isOnline) {
            $usersByRole['online']['users'][] = $user;
            $onlineUserIds[] = $user['id'];
        } else {
            $role = $user['role'] ?? 'other';
            if (!isset($usersByRole[$role])) {
                $role = 'other';
            }
            $usersByRole[$role]['users'][] = $user;
        }
    }

    // Generate HTML dengan optimasi
    $html = '';
    foreach ($usersByRole as $role => $group) {
        if (empty($group['users'])) continue;
        
        $html .= '<div class="chat-role-group">';
        $html .= '<div class="role-title">' . htmlspecialchars($group['title']) . '</div>';
        
        foreach ($group['users'] as $user) {
            $isOnline = isUserOnline($user['id']);
            $profilePhoto = !empty($user['profile_photo']) ? 
                '../uploads/profiles/' . htmlspecialchars($user['profile_photo']) : '';
            
            $html .= '<div class="chat-user" data-user-id="' . (int)$user['id'] . '">';
            
            if ($profilePhoto && file_exists($profilePhoto)) {
                $html .= '<img src="' . $profilePhoto . '" alt="' . htmlspecialchars($user['name']) . '" class="user-avatar" loading="lazy">';
            } else {
                $html .= '<div class="user-avatar-placeholder">' . htmlspecialchars(substr($user['name'], 0, 1)) . '</div>';
            }
            
            $html .= '<div class="user-info">';
            $html .= '<div class="user-name">' . htmlspecialchars($user['name']) . '</div>';
            $html .= '<div class="user-status">';
            $html .= '<span class="status-indicator ' . ($isOnline ? 'online' : 'offline') . '"></span>';
            $html .= '<span class="status-text">' . ($isOnline ? 'Online' : 'Offline') . '</span>';
            $html .= '</div>';
            $html .= '</div>';
            
            if ($user['unread_count'] > 0) {
                $html .= '<span class="unread-badge badge bg-danger">' . (int)$user['unread_count'] . '</span>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    // Jika tidak ada pengguna
    if (empty($html)) {
        $html = '<div style="padding: 20px; text-align: center; color: #666;">Tidak ada pengguna lain</div>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'users' => $chatUsers,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log('Chat users API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan saat memuat data pengguna'
    ]);
}

// Fungsi untuk memeriksa apakah user online
function isUserOnline($userId) {
    global $pdo;
    
    // Cek apakah tabel user_sessions ada
    $checkTable = $pdo->query("SHOW TABLES LIKE 'user_sessions'");
    if ($checkTable->rowCount() == 0) {
        // Jika tidak ada, anggap semua user online
        return true;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT last_activity 
            FROM user_sessions 
            WHERE user_id = ? 
            ORDER BY last_activity DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $session = $stmt->fetch();
        
        if ($session) {
            // User dianggap online jika aktivitas terakhir < 5 menit
            $lastActivity = strtotime($session['last_activity']);
            return (time() - $lastActivity) < 300; // 5 menit
        }
        return false;
    } catch (Exception $e) {
        // Jika error, anggap user online
        return true;
    }
}

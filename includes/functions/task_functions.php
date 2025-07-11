<?php
// File: includes/task_functions.php

require_once __DIR__ . '/../../config/database.php';

function getTaskDetails($taskId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT t.*, 
               c.name as category_name,
               ct.name as content_type_name,
               cp.name as content_pillar_name,
               a.name as account_name,
               uc.name as created_by_name,
               ua.name as assigned_to_name
        FROM tasks t
        JOIN categories c ON t.category_id = c.id
        JOIN content_types ct ON t.content_type_id = ct.id
        JOIN content_pillars cp ON t.content_pillar_id = cp.id
        JOIN accounts a ON t.account_id = a.id
        JOIN users uc ON t.created_by = uc.id
        JOIN users ua ON t.assigned_to = ua.id
        WHERE t.id = ?
    ");
    $stmt->execute([$taskId]);
    return $stmt->fetch();
}

function getTaskStatusHistory($taskId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT l.*, u.name as updated_by_name
        FROM task_status_logs l
        JOIN users u ON l.updated_by = u.id
        WHERE l.task_id = ?
        ORDER BY l.timestamp DESC
    ");
    $stmt->execute([$taskId]);
    return $stmt;
}

function getTaskRevisions($taskId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as revised_by_name
        FROM task_revisions r
        JOIN users u ON r.revised_by = u.id
        WHERE r.task_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$taskId]);
    return $stmt->fetchAll();
}

function getActiveTasksCount($userId, $role) {
    global $pdo;
    
    $query = "SELECT COUNT(*) FROM tasks WHERE ";
    if ($role === 'content_team') {
        $query .= "created_by = ? AND status NOT IN ('completed', 'cancelled')";
    } elseif ($role === 'production_team') {
        $query .= "assigned_to = ? AND status IN ('in_production', 'revision')";
    } else {
        $query .= "status = 'uploaded'";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function verifyTask($taskId, $rating = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get task info for notifications and points
        $taskInfo = $pdo->prepare("
            SELECT created_by, assigned_to FROM tasks WHERE id = ?
        ");
        $taskInfo->execute([$taskId]);
        $task = $taskInfo->fetch();
        
        if (!$task) {
            throw new Exception("Task tidak ditemukan");
        }
        
        // Update task status
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET status = 'completed', 
                is_verified = 1, 
                verified_at = NOW(),
                rating = ?
            WHERE id = ?
        ");
        $stmt->execute([$rating, $taskId]);
        
        // Log status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by, notes)
            VALUES (?, 'completed', ?, 'Task telah diverifikasi')
        ");
        $stmt->execute([$taskId, $_SESSION['user_id']]);
        
        // Tambahkan log untuk debugging
        error_log("Verifying task $taskId, assigned to: " . $task['assigned_to'] . ", created by: " . $task['created_by']);
        
        // Hitung dan simpan poin untuk tim produksi
        if ($task['assigned_to']) {
            $result = calculateAndSavePoints($taskId, $task['assigned_to']);
            error_log("Points calculation for assigned_to user: " . ($result ? "Success" : "Failed"));
        }
        
        // Hitung dan simpan poin untuk tim konten/marketing
        if ($task['created_by']) {
            $result = calculateAndSavePoints($taskId, $task['created_by']);
            error_log("Points calculation for created_by user: " . ($result ? "Success" : "Failed"));
        }
        
        // Send notifications
        $message = $rating ? 
            "Task telah diverifikasi dengan rating $rating ★" : 
            "Task telah diverifikasi";
            
        if ($task['created_by']) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$task['created_by'], $message, "view_task.php?id=$taskId"]);
        }
        
        if ($task['assigned_to'] && $task['assigned_to'] != $task['created_by']) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$task['assigned_to'], $message, "view_task.php?id=$taskId"]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in verifyTask: " . $e->getMessage());
        return false;
    }
}

function getTaskCountByStatus($userId, $status, $role) {
    global $pdo;
    
    $query = "SELECT COUNT(*) FROM tasks WHERE ";
    
    if ($role === 'content_team') {
        $query .= "created_by = ?";
    } elseif ($role === 'production_team') {
        $query .= "assigned_to = ?";
    } else {
        $query .= "1=1";
    }
    
    $query .= " AND status = ?";
    
    $stmt = $pdo->prepare($query);
    
    if ($role === 'creative_director') {
        $stmt->execute([$status]);
    } else {
        $stmt->execute([$userId, $status]);
    }
    
    return $stmt->fetchColumn();
}

function getRecentMarketingTasks($marketingUserId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, title, status FROM tasks WHERE created_by = ? ORDER BY id DESC LIMIT 10;
    ");
    $stmt->execute([$marketingUserId]);
    return $stmt->fetchAll();
}

function addTaskRevision($taskId, $note, $userId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Update status task menjadi 'revision'
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'revision' WHERE id = ?");
        $stmt->execute([$taskId]);
        
        // Log perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by)
            VALUES (?, 'revision', ?)
        ");
        $stmt->execute([$taskId, $userId]);
        
        // Tambahkan catatan revisi
        $stmt = $pdo->prepare("
            INSERT INTO task_revisions (task_id, note, revised_by)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$taskId, $note, $userId]);
        
        // Dapatkan info task untuk notifikasi
        $taskInfo = $pdo->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
        $taskInfo->execute([$taskId]);
        $task = $taskInfo->fetch();
        
        // Kirim notifikasi
        $message = "Task memerlukan revisi. Silakan periksa catatan revisi.";
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$task['assigned_to'], $message, "view_task.php?id=$taskId"]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

function getTaskFileUrl($filePath) {
    if (empty($filePath)) {
        return null;
    }
    
    // Pastikan path dimulai dengan uploads/
    if (strpos($filePath, 'uploads/') !== 0 && strpos($filePath, 'tasks/') === 0) {
        $filePath = 'uploads/' . $filePath;
    }
    
    // Jika path tidak dimulai dengan / atau http, tambahkan base URL
    if (strpos($filePath, '/') !== 0 && strpos($filePath, 'http') !== 0) {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $baseDir = dirname(dirname($_SERVER['PHP_SELF']));
        if ($baseDir == '/' || $baseDir == '') {
            $baseDir = '';
        }
        return $baseUrl . $baseDir . '/' . $filePath;
    }
    
    return $filePath;
}

function saveTaskLink($taskId, $platform, $link, $userId) {
    global $pdo;
    
    try {
        // Cek apakah sudah ada link untuk platform ini
        $stmt = $pdo->prepare("
            SELECT id FROM task_links 
            WHERE task_id = ? AND platform = ?
        ");
        $stmt->execute([$taskId, $platform]);
        $existingLink = $stmt->fetch();
        
        if ($existingLink) {
            // Update link yang sudah ada
            $stmt = $pdo->prepare("
                UPDATE task_links 
                SET link = ?, added_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$link, $userId, $existingLink['id']]);
        } else {
            // Tambahkan link baru
            $stmt = $pdo->prepare("
                INSERT INTO task_links (task_id, platform, link, added_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$taskId, $platform, $link, $userId]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error in saveTaskLink: " . $e->getMessage());
        return false;
    }
}

function getTaskLinks($taskId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT platform, link 
            FROM task_links 
            WHERE task_id = ?
        ");
        $stmt->execute([$taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Jika tabel belum ada, kembalikan array kosong
        if ($e->getCode() == '42S02') {
            return [];
        } else {
            error_log("Error getting task links: " . $e->getMessage());
            return [];
        }
    }
}

function getTaskDistributionPlatforms($taskId) {
    global $pdo;
    
    try {
        // Ambil dari tabel task_links
        $stmt = $pdo->prepare("
            SELECT platform 
            FROM task_links 
            WHERE task_id = ?
        ");
        $stmt->execute([$taskId]);
        $platforms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $platforms;
    } catch (PDOException $e) {
        error_log("Error in getTaskDistributionPlatforms: " . $e->getMessage());
        return [];
    }
}

function isTaskOverdue($deadline) {
    // Jika deadline adalah array, coba ambil nilai 'deadline' dari array tersebut
    if (is_array($deadline)) {
        if (isset($deadline['deadline'])) {
            $deadline = $deadline['deadline'];
        } else {
            // Jika tidak ada kunci 'deadline', kembalikan false
            return false;
        }
    }
    
    // Pastikan deadline adalah string dan tidak kosong
    if (!is_string($deadline) || empty($deadline)) {
        return false;
    }
    
    return strtotime($deadline) < time() && strtotime($deadline) > 0;
}

function getUserRelevantTasks($userId, $role, $filters = []) {
    global $pdo;
    
    try {
        $query = "
            SELECT t.*, 
                c.name as category_name,
                ct.name as content_type_name,
                cp.name as content_pillar_name,
                a.name as account_name,
                uc.name as created_by_name,
                ua.name as assigned_to_name
            FROM tasks t
            JOIN categories c ON t.category_id = c.id
            JOIN content_types ct ON t.content_type_id = ct.id
            JOIN content_pillars cp ON t.content_pillar_id = cp.id
            JOIN accounts a ON t.account_id = a.id
            JOIN users uc ON t.created_by = uc.id
            LEFT JOIN users ua ON t.assigned_to = ua.id
            WHERE ";
        
        // Kondisi berdasarkan role
        if ($role === 'content_team') {
            $query .= "(t.created_by = ? OR t.assigned_to = ?)";
            $params = [$userId, $userId];
        } elseif ($role === 'production_team') {
            $query .= "(t.assigned_to = ?)";
            $params = [$userId];
        } elseif ($role === 'marketing_team') {
            $query .= "(t.created_by = ?)";
            $params = [$userId];
        } else {
            // Admin atau Creative Director bisa melihat semua
            $query .= "1=1";
            $params = [];
        }  // <-- This closing brace was missing
        
        // Tambahkan filter tambahan jika ada
        if (!empty($filters)) {
            foreach ($filters as $field => $value) {
                if ($field === 'status' && is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $query .= " AND t.$field IN ($placeholders)";
                    $params = array_merge($params, $value);
                } else {
                    $query .= " AND t.$field = ?";
                    $params[] = $value;
                }
            }
        }
        
        $query .= " ORDER BY t.deadline ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getUserRelevantTasks: " . $e->getMessage());
        return [];
    }
}

// Tambahkan status baru waiting_redaktur_confirmation

function getStatusLabel($status) {
    $labels = [
        'draft' => 'Draft',
        'waiting_head_confirmation' => 'Menunggu Penugasan',
        'waiting_redaktur_confirmation' => 'Menunggu Penugasan',
        'waiting_confirmation' => 'Menunggu Konfirmasi',
        'rejected' => 'Ditolak',
        'in_production' => 'Dalam Pengerjaan',
        'ready_for_review' => 'Siap Direview',
        'revision' => 'Perlu Revisi',
        'uploaded' => 'Sudah Diupload',
        'completed' => 'Selesai'
    ];
    
    return $labels[$status] ?? $status;
}

function getStatusColor($status) {
    $colors = [
        'draft' => 'secondary',
        'waiting_head_confirmation' => 'purple',
        'waiting_redaktur_confirmation' => 'purple',
        'waiting_confirmation' => 'pink',
        'rejected' => 'danger',
        'in_production' => 'primary',
        'ready_for_review' => 'info',
        'revision' => 'danger',
        'uploaded' => 'info',
        'completed' => 'success'
    ];
    
    return $colors[$status] ?? 'secondary';
}

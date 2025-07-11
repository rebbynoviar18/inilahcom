<?php

// Tambahkan fungsi ini di file functions.php

/**
 * Mendapatkan URL untuk melihat task berdasarkan role user
 * 
 * @param string $role Role user
 * @param int $taskId ID task
 * @return string URL lengkap untuk melihat task
 */
function getTaskViewUrlByRole($role, $taskId) {
    $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/creative/";
    
    switch ($role) {
        case 'content_team':
            return $baseUrl . "content/view_task.php?id=" . $taskId;
        case 'production_team':
            return $baseUrl . "production/view_task.php?id=" . $taskId;
        case 'redaksi':
            return $baseUrl . "redaksi/view_task.php?id=" . $taskId;
        case 'creative_director':
            return $baseUrl . "admin/view_task.php?id=" . $taskId;
        case 'redaktur_pelaksana':
            return $baseUrl . "redaktur/view_task.php?id=" . $taskId;
        case 'marketing_team':
            return $baseUrl . "marketing/view_task.php?id=" . $taskId;
        default:
            return $baseUrl . "view_task.php?id=" . $taskId;
    }
}

/**
 * Mengirim notifikasi WhatsApp untuk task baru
 * 
 * @param int $userId ID user yang akan menerima notifikasi
 * @param int $taskId ID task yang dibuat
 * @param string $taskTitle Judul task (opsional, akan diambil dari database jika tidak disediakan)
 * @param string $deadline Deadline task (opsional, akan diambil dari database jika tidak disediakan)
 * @param string $categoryName Nama kategori task (opsional)
 * @return bool True jika berhasil, false jika gagal
 */
function notifyUserAboutNewTask($userId, $taskId, $taskTitle = '', $deadline = '', $categoryName = '') {
    global $pdo;
    
    // Ambil data user termasuk nomor WhatsApp dan role
    $stmt = $pdo->prepare("SELECT name, whatsapp_number, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['whatsapp_number'])) {
        error_log("User tidak ditemukan atau tidak memiliki nomor WhatsApp: $userId");
        return false;
    }
    
    // Ambil data task dari database jika title atau deadline tidak disediakan
    if (empty($taskTitle) || empty($deadline) || empty($categoryName)) {
        $taskStmt = $pdo->prepare("
            SELECT t.title, t.deadline, c.name as category_name, ct.name as content_type_name
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN content_types ct ON t.content_type_id = ct.id
            WHERE t.id = ?
        ");
        $taskStmt->execute([$taskId]);
        $taskData = $taskStmt->fetch();
        
        if ($taskData) {
            if (empty($taskTitle)) $taskTitle = $taskData['title'];
            if (empty($deadline)) $deadline = $taskData['deadline'];
            if (empty($categoryName)) $categoryName = $taskData['category_name'];
            $contentType = $taskData['content_type_name'] ?? 'Tidak ditentukan';
        } else {
            $contentType = 'Tidak ditentukan';
        }
    } else {
        // Jika semua data disediakan, ambil hanya jenis konten
        $contentStmt = $pdo->prepare("
            SELECT ct.name as content_type_name
            FROM tasks t
            LEFT JOIN content_types ct ON t.content_type_id = ct.id
            WHERE t.id = ?
        ");
        $contentStmt->execute([$taskId]);
        $contentData = $contentStmt->fetch();
        $contentType = $contentData['content_type_name'] ?? 'Tidak ditentukan';
    }
    
    // Format pesan WhatsApp
    $message = "Halo *{$user['name']}*,\n\n";
    $message .= "Kamu memiliki task baru yang perlu dikerjakan:\n\n";
    $message .= "*Judul:*\n{$taskTitle}\n";
    
    // Tambahkan kategori jika tersedia
    if (!empty($categoryName)) {
        $message .= "*Kategori:* {$categoryName}\n";
    }
    
    // Jenis konten selalu ditampilkan
    $message .= "*Jenis Konten:* {$contentType}\n";
    
    $message .= "*Deadline:* " . date('d M Y - H:i', strtotime($deadline)) . " WIB\n\n";
    $message .= "Silakan login ke dashboard untuk melihat detail task.\n";
    $message .= getTaskViewUrlByRole($user['role'], $taskId);
    
    // Kirim notifikasi WhatsApp
    require_once __DIR__ . '/../../config/whatsapp.php';
    return sendWhatsAppNotification($user['whatsapp_number'], $message);
}

/**
 * Mengirim notifikasi WhatsApp ke grup
 * 
 * @param string $message Pesan yang akan dikirim
 * @return bool True jika berhasil, false jika gagal
 */
function sendWhatsAppGroupNotification($message) {
    require_once __DIR__ . '/../../config/whatsapp.php';
    
    // Pastikan konstanta WHATSAPP_GROUP_ID sudah didefinisikan
    if (!defined('WHATSAPP_GROUP_ID') || empty(WHATSAPP_GROUP_ID)) {
        error_log("Error: ID grup WhatsApp tidak dikonfigurasi");
        return false;
    }
    
    $data = [
        'target' => WHATSAPP_GROUP_ID,
        'message' => $message
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => FONNTE_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => [
            "Authorization: " . FONNTE_API_KEY
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("cURL Error #:" . $err);
        return false;
    }

    $result = json_decode($response, true);
    error_log("WhatsApp Group API Response: " . print_r($result, true));
    
    if (isset($result['status']) && $result['status'] === true) {
        return true;
    } else {
        error_log("WhatsApp Group API Error: " . ($result['reason'] ?? 'Unknown error'));
        return false;
    }
}

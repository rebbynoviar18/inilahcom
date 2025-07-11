<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini";
    header('Location: ../index.php');
    exit();
}

$taskId = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];

// Dapatkan detail task
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name,
           ct.name as content_type_name,
           cp.name as content_pillar_name,
           a.name as account_name,
           u1.name as created_by_name,
           u2.name as assigned_to_name
    FROM tasks t
    JOIN categories c ON t.category_id = c.id
    JOIN content_types ct ON t.content_type_id = ct.id
    JOIN content_pillars cp ON t.content_pillar_id = cp.id
    JOIN accounts a ON t.account_id = a.id
    JOIN users u1 ON t.created_by = u1.id
    JOIN users u2 ON t.assigned_to = u2.id
    WHERE t.id = ? AND t.assigned_to = ? AND t.status = 'waiting_confirmation' AND c.name = 'Distribusi'
");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau Anda tidak memiliki akses";
    header('Location: tasks.php');
    exit();
}

// Ambil platform media sosial
$platforms = $pdo->query("SELECT * FROM platforms ORDER BY name")->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Validasi link utama (Instagram)
        if (empty($_POST['instagram_link'])) {
            throw new Exception("Link Instagram wajib diisi");
        }
        
        // Simpan link Instagram
        $stmt = $pdo->prepare("
            INSERT INTO task_links (task_id, platform, link) 
            VALUES (?, 'Instagram', ?)
        ");
        $stmt->execute([$taskId, $_POST['instagram_link']]);
        
        // Simpan link platform lainnya jika ada
        foreach ($platforms as $platform) {
            $platformName = $platform['name'];
            if ($platformName !== 'Instagram' && isset($_POST[$platformName.'_checkbox']) && !empty($_POST[$platformName.'_link'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO task_links (task_id, platform, link) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$taskId, $platformName, $_POST[$platformName.'_link']]);
            }
        }
        
        // Update status task
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'uploaded' WHERE id = ?");
        $stmt->execute([$taskId]);
        
        // Catat perubahan status
        $stmt = $pdo->prepare("
            INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
            VALUES (?, 'uploaded', ?, 'Konten telah diposting')
        ");
        $stmt->execute([$taskId, $userId]);
        
        // Kirim notifikasi ke Creative Director
        $creativeDirectorId = $pdo->query("SELECT id FROM users WHERE role = 'creative_director' LIMIT 1")->fetchColumn();
        
        if ($creativeDirectorId) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, link) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $creativeDirectorId,
                "Task distribusi telah diposting dan menunggu verifikasi",
                "view_task.php?id=$taskId"
            ]);
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "Task berhasil diposting dan menunggu verifikasi Creative Director";
        header('Location: tasks.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}
?>
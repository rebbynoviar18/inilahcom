<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['task_id'])) {
    $_SESSION['error'] = "Permintaan tidak valid";
    header("Location: tasks.php");
    exit();
}

$taskId = $_POST['task_id'];
$instagramLink = trim($_POST['instagram_link'] ?? '');

// Validasi link Instagram
if (empty($instagramLink)) {
    $_SESSION['error'] = "Link Instagram harus diisi";
    header("Location: view_task.php?id=$taskId");
    exit();
}

// Verifikasi kepemilikan task
$stmt = $pdo->prepare("
    SELECT t.*, u.name as assigned_to_name 
    FROM tasks t 
    JOIN users u ON t.assigned_to = u.id 
    WHERE t.id = ? AND (t.assigned_to = ? OR t.created_by = ?)
");
$stmt->execute([$taskId, $userId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = "Task tidak ditemukan atau Anda tidak memiliki akses";
    header("Location: tasks.php");
    exit();
}

// Validasi status task
if ($task['status'] !== 'waiting_confirmation' && $task['status'] !== 'revision') {
    $_SESSION['error'] = "Task tidak dalam status yang tepat untuk upload link";
    header("Location: view_task.php?id=$taskId");
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Simpan link Instagram sebagai link utama
    $stmt = $pdo->prepare("UPDATE tasks SET uploaded_link = ?, status = 'ready_for_review' WHERE id = ?");
    $stmt->execute([$instagramLink, $taskId]);
    
    // Hapus link platform lama jika ada
    $stmt = $pdo->prepare("DELETE FROM distribution_links WHERE task_id = ?");
    $stmt->execute([$taskId]);
    
    // Simpan link Instagram
    $stmt = $pdo->prepare("INSERT INTO distribution_links (task_id, platform, link) VALUES (?, 'instagram', ?)");
    $stmt->execute([$taskId, $instagramLink]);
    
    // Simpan link platform lain jika ada
    $platforms = [
        'tiktok' => $_POST['tiktok_link'] ?? '',
        'facebook' => $_POST['facebook_link'] ?? '',
        'twitter' => $_POST['twitter_link'] ?? '',
        'threads' => $_POST['threads_link'] ?? ''
    ];
    
    foreach ($platforms as $platform => $link) {
        if (!empty($link)) {
            $stmt = $pdo->prepare("INSERT INTO distribution_links (task_id, platform, link) VALUES (?, ?, ?)");
            $stmt->execute([$taskId, $platform, $link]);
        }
    }
    
    // Catat perubahan status
    $stmt = $pdo->prepare("
        INSERT INTO task_status_logs (task_id, status, updated_by, notes) 
        VALUES (?, 'ready_for_review', ?, ?)
    ");
    $stmt->execute([
        $taskId, 
        $userId, 
        $task['status'] === 'revision' ? "Upload revisi link distribusi" : "Upload link distribusi"
    ]);
    
    // Kirim notifikasi ke marketing team
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message, link) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $task['created_by'],
        $task['status'] === 'revision' ? "Revisi link distribusi telah diupload" : "Link distribusi telah diupload",
        "review_distribution_task.php?id=$taskId"
    ]);
    
    $pdo->commit();
    $_SESSION['success'] = $task['status'] === 'revision' ? "Revisi link distribusi berhasil diupload" : "Link distribusi berhasil diupload";
    header("Location: view_task.php?id=$taskId");
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Gagal mengupload link: " . $e->getMessage();
    header("Location: view_task.php?id=$taskId");
    exit();
}

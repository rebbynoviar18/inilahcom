<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Pastikan user sudah login
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];

try {
    // Buat notifikasi test
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message, link, related_to, related_id, is_read)
        VALUES (?, ?, ?, 'test', 0, 0)
    ");
    $stmt->execute([
        $userId,
        "Ini adalah notifikasi test pada " . date('Y-m-d H:i:s'),
        "#",
        0
    ]);
    
    $_SESSION['success'] = "Notifikasi test berhasil dibuat!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal membuat notifikasi: " . $e->getMessage();
}

// Redirect kembali ke halaman sebelumnya
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;

<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Perbaiki status task marketing yang kosong atau salah
$stmt = $pdo->prepare("
    UPDATE tasks t
    JOIN users u ON t.created_by = u.id
    SET t.status = 'waiting_head_confirmation'
    WHERE u.role = 'marketing_team' AND (t.status IS NULL OR t.status = '' OR t.status = 'waiting_confirmation')
");
$stmt->execute();
$updatedCount = $stmt->rowCount();

echo "Berhasil memperbaiki $updatedCount task marketing dengan status waiting_head_confirmation.";
?>
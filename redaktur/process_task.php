
// Setelah task berhasil dibuat dan diassign ke user
if ($stmt->rowCount() > 0) {
    $taskId = $pdo->lastInsertId();
    
    // Jika task diassign ke user tertentu, kirim notifikasi WhatsApp
    if (isset($assignedTo) && $assignedTo > 0) {
        notifyUserAboutNewTask($assignedTo, $taskId, $title, $deadline);
    }
    
    // Redirect atau pesan sukses lainnya
    $_SESSION['success'] = "Task berhasil dibuat";
    header("Location: tasks.php");
    exit();
}

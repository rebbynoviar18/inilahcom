<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    
    // Validasi input
    if (empty($name) || empty($email)) {
        $_SESSION['error'] = "Nama dan email harus diisi";
        header("Location: profile.php");
        exit();
    }
    
    try {
        // Cek apakah email sudah digunakan oleh user lain
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Email sudah digunakan oleh user lain";
            header("Location: profile.php");
            exit();
        }
        
        // Update profile
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, bio = ? WHERE id = ?");
        $stmt->execute([$name, $email, $bio, $userId]);
        
        // Update session
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        
        $_SESSION['success'] = "Profil berhasil diperbarui";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Gagal memperbarui profil: " . $e->getMessage();
    }
    
    header("Location: profile.php");
    exit();
}

// Redirect if accessed directly without POST
header("Location: profile.php");
exit();
?>
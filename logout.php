<?php
// File: c:\xampp\htdocs\creative\logout.php

require_once 'config/database.php';
require_once 'includes/auth.php';

// Set status user menjadi offline
if (isset($_SESSION['user_id'])) {
    // Update last_activity menjadi NULL untuk menandakan user offline
    $stmt = $pdo->prepare("UPDATE users SET last_activity = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Hapus cookie remember token jika ada
if (isset($_COOKIE['remember_token'])) {
    // Hapus token dari database
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    
    // Hapus cookie dengan mengatur waktu kedaluwarsa ke masa lalu
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_phone', '', time() - 3600, '/'); // Ganti dari user_email ke user_phone
}

// Hapus session
session_start();
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>
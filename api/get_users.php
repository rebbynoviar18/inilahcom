<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Pastikan hanya admin yang bisa mengakses
if (!isLoggedIn() || getUserRole() !== 'creative_director') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT id, name, role FROM users ORDER BY name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

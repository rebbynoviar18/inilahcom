<?php
// File: api/get_content_types.php

require_once '../config/database.php';
require_once '../includes/auth.php';

// Periksa login
redirectIfNotLoggedIn();

header('Content-Type: application/json');

$categoryId = $_GET['category_id'] ?? null;

try {
    if ($categoryId === 'all') {
        // Ambil semua tipe konten dengan nama kategori
        $stmt = $pdo->prepare("
            SELECT t.*, c.name as category_name 
            FROM content_types t
            JOIN categories c ON t.category_id = c.id
            ORDER BY c.name, t.name
        ");
        $stmt->execute();
    } else {
        // Ambil tipe konten berdasarkan kategori
        $stmt = $pdo->prepare("
            SELECT t.*, c.name as category_name 
            FROM content_types t
            JOIN categories c ON t.category_id = c.id
            WHERE t.category_id = ?
            ORDER BY t.name
        ");
        $stmt->execute([$categoryId]);
    }
    
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($types);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
<?php
// File: api/get_content_pillars.php

require_once '../config/database.php';
require_once '../includes/auth.php';

// Periksa login
redirectIfNotLoggedIn();

header('Content-Type: application/json');

$categoryId = $_GET['category_id'] ?? null;

try {
    if ($categoryId === 'all') {
        // Ambil semua pilar konten dengan nama kategori
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM content_pillars p
            JOIN categories c ON p.category_id = c.id
            ORDER BY c.name, p.name
        ");
        $stmt->execute();
    } else {
        // Ambil pilar konten berdasarkan kategori
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM content_pillars p
            JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ?
            ORDER BY p.name
        ");
        $stmt->execute([$categoryId]);
    }
    
    $pillars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($pillars);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
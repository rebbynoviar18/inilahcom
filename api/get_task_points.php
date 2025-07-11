<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login
redirectIfNotLoggedIn();

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['team']) || !isset($data['category']) || !isset($data['task_type'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$team = $data['team'];
$category = $data['category'];
$taskType = $data['task_type'];

// Ambil poin dari database
$points = getTaskPoints($team, $category, $taskType);

echo json_encode(['success' => true, 'points' => $points]);

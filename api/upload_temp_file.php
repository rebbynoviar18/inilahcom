<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$file = $_FILES['file'];

// Validate file
$maxSize = 10 * 1024 * 1024; // 10MB
$allowedTypes = [
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
    'application/pdf', 'video/mp4', 'video/quicktime', 'video/x-msvideo',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error']);
    exit();
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large']);
    exit();
}

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed']);
    exit();
}

// Create temp directory if not exists
$tempDir = '../uploads/temp/';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $extension;
$tempPath = $tempDir . $filename;

// Move uploaded file to temp directory
if (move_uploaded_file($file['tmp_name'], $tempPath)) {
    echo json_encode([
        'success' => true,
        'filePath' => $tempPath,
        'fileName' => $file['name'],
        'fileSize' => $file['size']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}
?>
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

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);

// Tambahkan logging untuk debugging
error_log('Received notification data: ' . print_r($data, true));

if (!$data || !isset($data['title']) || !isset($data['message']) || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$title = trim($data['title']);
$message = trim($data['message']);
$userId = $data['user_id'];

if (empty($title) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Judul dan pesan harus diisi']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Tentukan penerima notifikasi
    $recipients = [];
    
    if ($userId === 'all') {
        // Kirim ke semua user
        $stmt = $pdo->prepare("SELECT id FROM users");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recipients[] = $row['id'];
        }
    } elseif ($userId === 'self') {
        // Kirim ke diri sendiri
        $recipients[] = $_SESSION['user_id'];
    } else {
        // Kirim ke user tertentu
        $recipients[] = $userId;
    }
    
    // Buat link untuk notifikasi
    $link = '/creative/shared/notifications.php';
    
    // Masukkan notifikasi ke database
    $insertStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message, link, created_at, is_read)
        VALUES (?, ?, ?, NOW(), 0)
    ");
    
    $successCount = 0;
    foreach ($recipients as $recipient) {
        $insertStmt->execute([$recipient, $message, $link]);
        $successCount++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Berhasil mengirim notifikasi ke $successCount user"
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Periksa apakah user sudah login
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit;
}

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['file_path'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Path file tidak ditemukan']);
    exit;
}

$filePath = $data['file_path'];

// Validasi path file (pastikan hanya file di folder uploads/temp yang bisa dihapus)
if (strpos($filePath, '../uploads/temp/') !== 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Path file tidak valid']);
    exit;
}

// Cek apakah file ada
if (!file_exists($filePath)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
    exit;
}

// Hapus file
if (unlink($filePath)) {
    // Hapus juga dari session jika ada
    if (isset($_SESSION['temp_uploads'])) {
        foreach ($_SESSION['temp_uploads'] as $key => $file) {
            if ($file['path'] === $filePath) {
                unset($_SESSION['temp_uploads'][$key]);
                break;
            }
        }
        // Reindex array
        $_SESSION['temp_uploads'] = array_values($_SESSION['temp_uploads']);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus file']);
}
?>
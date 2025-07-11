<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];

// Get user role for correct redirect
$userRole = getUserRole();
$baseUrl = '';
switch ($userRole) {
    case 'creative_director':
        $baseUrl = '../admin/';
        break;
    case 'content_team':
        $baseUrl = '../content/';
        break;
    case 'production_team':
        $baseUrl = '../production/';
        break;
    default:
        $baseUrl = '../';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek apakah ada file yang diupload
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['photo_error'] = "Tidak ada file yang dipilih";
        header("Location: profile.php");
        exit();
    }

    // Validasi file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    $file = $_FILES['profile_photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['photo_error'] = "Error saat upload file: " . $file['error'];
        header("Location: profile.php");
        exit();
    }

    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['photo_error'] = "Tipe file tidak diizinkan. Hanya JPG, PNG, dan GIF yang diperbolehkan.";
        header("Location: profile.php");
        exit();
    }

    if ($file['size'] > $maxSize) {
        $_SESSION['photo_error'] = "Ukuran file terlalu besar. Maksimal 2MB.";
        header("Location: profile.php");
        exit();
    }

    // Buat direktori jika belum ada
    $uploadDir = '../uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate nama file unik
    $fileName = 'profile_' . $userId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $targetPath = $uploadDir . $fileName;

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Update database
        try {
            // Hapus foto lama jika ada
            $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $oldPhoto = $stmt->fetchColumn();
            
            if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
                unlink($uploadDir . $oldPhoto);
            }
            
            // Update database dengan foto baru
            $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->execute([$fileName, $userId]);
            
            $_SESSION['photo_success'] = "Foto profil berhasil diperbarui";
        } catch (PDOException $e) {
            $_SESSION['photo_error'] = "Error memperbarui foto profil: " . $e->getMessage();
        }
    } else {
        $_SESSION['photo_error'] = "Gagal mengupload file";
    }
}

// Redirect kembali ke halaman profil
header("Location: profile.php");
exit();

/**
 * Mendapatkan pesan error upload yang lebih deskriptif
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "Ukuran file melebihi batas maksimal yang diizinkan oleh server.";
        case UPLOAD_ERR_FORM_SIZE:
            return "Ukuran file melebihi batas maksimal yang diizinkan oleh form.";
        case UPLOAD_ERR_PARTIAL:
            return "File hanya terupload sebagian.";
        case UPLOAD_ERR_NO_FILE:
            return "Tidak ada file yang diupload.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Direktori temporary tidak ditemukan.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Gagal menulis file ke disk.";
        case UPLOAD_ERR_EXTENSION:
            return "Upload dihentikan oleh ekstensi PHP.";
        default:
            return "Terjadi kesalahan yang tidak diketahui.";
    }
}
?>
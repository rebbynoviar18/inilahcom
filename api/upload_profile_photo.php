<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Check if file was uploaded
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/profiles/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Get file info
    $fileName = $_FILES['profile_photo']['name'];
    $fileSize = $_FILES['profile_photo']['size'];
    $fileTmpName = $_FILES['profile_photo']['tmp_name'];
    $fileType = $_FILES['profile_photo']['type'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Validate file extension
    if (!in_array($fileExtension, $allowedExtensions)) {
        $response['message'] = 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.';
        echo json_encode($response);
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        $response['message'] = 'Ukuran file terlalu besar. Maksimal 5MB.';
        echo json_encode($response);
        exit();
    }
    
    // Generate unique filename
    $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFileName;
    
    // Try to upload file
    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        try {
            // Get current profile photo
            $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $oldPhoto = $stmt->fetchColumn();
            
            // Delete old photo if exists
            if ($oldPhoto && file_exists('../uploads/profiles/' . $oldPhoto)) {
                unlink('../uploads/profiles/' . $oldPhoto);
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->execute([$newFileName, $userId]);
            
            $response['success'] = true;
            $response['message'] = 'Foto profil berhasil diperbarui';
            $response['photo_url'] = '../uploads/profiles/' . $newFileName;
        } catch (PDOException $e) {
            $response['message'] = 'Gagal memperbarui foto profil: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Gagal mengupload file. Silakan coba lagi.';
    }
} else {
    $response['message'] = 'Tidak ada file yang diupload atau terjadi kesalahan.';
}

echo json_encode($response);
?>
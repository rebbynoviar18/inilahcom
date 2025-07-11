<?php
// File: api/update_profile.php

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit();
}

session_start();

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

$userId = $_SESSION['user_id'];
$name = trim($_POST['name']);
$email = trim($_POST['email']);

// Validasi input
if (empty($name) || empty($email)) {
  echo json_encode(['success' => false, 'message' => 'Nama dan email harus diisi']);
  exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
  exit();
}

try {
  // Handle profile photo upload if present
  $profilePhotoPath = null;
  
  if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($_FILES['profile_photo']['type'], $allowedTypes)) {
      echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF']);
      exit();
    }
    
    if ($_FILES['profile_photo']['size'] > $maxSize) {
      echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 2MB']);
      exit();
    }
    
    // Create directory if it doesn't exist
    $uploadDir = '../uploads/profiles/';
    if (!file_exists($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $filename = $userId . '_' . time() . '_' . basename($_FILES['profile_photo']['name']);
    $targetPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
      $profilePhotoPath = '/uploads/profiles/' . $filename;
      
      // Delete old profile photo if exists
      $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
      $stmt->execute([$userId]);
      $oldPhoto = $stmt->fetchColumn();
      
      if ($oldPhoto && file_exists('../' . ltrim($oldPhoto, '/'))) {
        unlink('../' . ltrim($oldPhoto, '/'));
      }
    } else {
      echo json_encode(['success' => false, 'message' => 'Gagal mengunggah foto profil']);
      exit();
    }
  }
  
  // Update user profile
  if ($profilePhotoPath) {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_photo = ? WHERE id = ?");
    $stmt->execute([$name, $email, $profilePhotoPath, $userId]);
  } else {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$name, $email, $userId]);
  }
// Di bagian update profil

$whatsappNumber = '+62' . preg_replace('/[^0-9]/', '', $_POST['whatsapp_number']);
$stmt = $pdo->prepare("UPDATE users SET whatsapp_number = ? WHERE id = ?");
$stmt->execute([$whatsappNumber, $userId]);

  
  // Update session data
  $_SESSION['name'] = $name;
  
  echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil: ' . $e->getMessage()]);
}


?>
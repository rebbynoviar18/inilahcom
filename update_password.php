<?php
require_once 'config/database.php';

// Email user yang ingin diupdate passwordnya
$email = 'rebnews@gmail.com';
// Password baru dalam bentuk plaintext
$new_password = 'admin123456';

// Hash password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password di database
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
$result = $stmt->execute([$hashed_password, $email]);

if ($result) {
    echo "Password berhasil diupdate untuk user: $email";
} else {
    echo "Gagal mengupdate password";
}
?>
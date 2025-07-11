<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/functions/whatsapp_functions.php';

// Pesan test
$testMessage = "*[TEST NOTIFIKASI GRUP]*\n\n";
$testMessage .= "Ini adalah pesan test untuk grup WhatsApp.\n";
$testMessage .= "Waktu: " . date('d M Y H:i:s') . "\n\n";
$testMessage .= "Jika Anda menerima pesan ini, berarti konfigurasi grup WhatsApp berhasil.";

// Kirim pesan test
$result = sendWhatsAppGroupNotification($testMessage);

// Tampilkan hasil
echo "<h1>Test Pengiriman Pesan Grup WhatsApp</h1>";
echo "<p>ID Grup: " . htmlspecialchars(WHATSAPP_GROUP_ID) . "</p>";
echo "<p>Pesan: <pre>" . htmlspecialchars($testMessage) . "</pre></p>";
echo "<p>Hasil: " . ($result ? "<span style='color:green'>Berhasil</span>" : "<span style='color:red'>Gagal</span>") . "</p>";

// Tampilkan log error jika ada
$errorLogFile = ini_get('error_log');
if (file_exists($errorLogFile)) {
    $errors = file_get_contents($errorLogFile);
    $recentErrors = substr($errors, -2000); // Ambil 2000 karakter terakhir
    echo "<h2>Log Error Terbaru:</h2>";
    echo "<pre>" . htmlspecialchars($recentErrors) . "</pre>";
}
?>
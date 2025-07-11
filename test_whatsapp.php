<?php
require_once 'config/database.php';
require_once 'config/whatsapp.php';

// Nomor WhatsApp untuk pengujian (ganti dengan nomor Anda)
$testNumber = '+6281214965263'; // Ganti dengan nomor WhatsApp Anda

// Pesan pengujian
$message = "Ini adalah pesan pengujian dari sistem Creative Task Management.\n\nJika Anda menerima pesan ini, berarti konfigurasi WhatsApp API berhasil.";

// Kirim pesan pengujian
$result = sendWhatsAppNotification($testNumber, $message);

if ($result) {
    echo "Pesan berhasil dikirim! Periksa WhatsApp Anda.";
} else {
    echo "Gagal mengirim pesan. Periksa log error untuk detail.";
}
?>
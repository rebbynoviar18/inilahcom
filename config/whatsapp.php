<?php
// Konfigurasi API WhatsApp
define('FONNTE_API_URL', 'https://api.fonnte.com/send');
define('FONNTE_API_KEY', 'BP4po5hKwjPimUJHjfvB'); // Ganti dengan API key Anda

/**
 * Fungsi untuk mengirim notifikasi WhatsApp
 * 
 * @param string $phone Nomor telepon penerima (format: 628xxxxxxxxxx)
 * @param string $message Pesan yang akan dikirim
 * @return bool True jika berhasil, false jika gagal
 */
function sendWhatsAppNotification($phone, $message) {
    // Pastikan nomor telepon valid
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($phone)) {
        error_log("Nomor telepon tidak valid");
        return false;
    }
    
    // Pastikan nomor dimulai dengan kode negara
    if (substr($phone, 0, 2) !== '62') {
        // Jika dimulai dengan 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        } else {
            // Tambahkan 62 di depan
            $phone = '62' . $phone;
        }
    }
    
    $data = [
        'target' => $phone,
        'message' => $message
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => FONNTE_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => [
            "Authorization: " . FONNTE_API_KEY
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("cURL Error #:" . $err);
        return false;
    }

    $result = json_decode($response, true);
    if (isset($result['status']) && $result['status'] === true) {
        return true;
    } else {
        error_log("WhatsApp API Error: " . ($result['reason'] ?? 'Unknown error'));
        return false;
    }
}

// ID grup WhatsApp untuk notifikasi
// Untuk Fonnte, format grup biasanya: 62812345678-1234567890@g.us atau cukup nomor admin grup
define('WHATSAPP_GROUP_ID', '120363418229412628@g.us'); // Ganti dengan nomor admin grup WhatsApp Anda
?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Hanya admin yang boleh menjalankan script ini
if (!isLoggedIn() || getUserRole() !== 'creative_director') {
    header('Location: ../login.php');
    exit;
}

try {
    // Periksa struktur tabel terlebih dahulu
    $checkTable = $pdo->query("SHOW COLUMNS FROM user_sessions");
    $columns = $checkTable->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Struktur Tabel user_sessions:</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Periksa jumlah data di tabel
    $countQuery = $pdo->query("SELECT COUNT(*) FROM user_sessions");
    $totalSessions = $countQuery->fetchColumn();
    echo "<p>Total sesi dalam database: $totalSessions</p>";
    
    // Periksa format last_activity
    $sampleQuery = $pdo->query("SELECT * FROM user_sessions LIMIT 5");
    $sampleData = $sampleQuery->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Contoh Data:</h2>";
    echo "<pre>";
    print_r($sampleData);
    echo "</pre>";
    
    // Sesuaikan query pembersihan berdasarkan struktur tabel
    if (in_array('last_activity', $columns)) {
        // Jika last_activity adalah timestamp UNIX
        if (is_numeric($sampleData[0]['last_activity'] ?? '')) {
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE last_activity < (UNIX_TIMESTAMP() - 86400)");
        } else {
            // Jika last_activity adalah datetime MySQL
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        }
        
        $stmt->execute();
        $deletedRows = $stmt->rowCount();
        echo "<p>Berhasil membersihkan $deletedRows sesi lama.</p>";
    } else {
        echo "<p>Kolom last_activity tidak ditemukan dalam tabel user_sessions.</p>";
    }
    
    // Tambahkan tombol untuk kembali
    echo '<p><a href="../admin/dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a></p>';
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
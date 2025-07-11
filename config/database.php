<?php
// File: config/database.php

// Mulai session di awal file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'creative');

// Buat direktori logs jika belum ada
$logDir = __DIR__ . "/../logs";
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
    // Buat file .htaccess untuk melindungi direktori logs
    file_put_contents($logDir . "/.htaccess", "Deny from all");
}

// Koneksi database
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buat direktori yang diperlukan
    require_once __DIR__ . '/../includes/setup.php';
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Tambahkan kode ini untuk memeriksa struktur tabel task_revisions
function checkTaskRevisionsTable() {
    global $pdo;
    
    try {
        // Periksa apakah tabel task_revisions sudah ada
        $tableExists = $pdo->query("SHOW TABLES LIKE 'task_revisions'")->rowCount() > 0;
        
        if (!$tableExists) {
            // Buat tabel jika belum ada
            $pdo->exec("
                CREATE TABLE task_revisions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    task_id INT NOT NULL,
                    revision_notes TEXT NOT NULL,
                    revised_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (task_id) REFERENCES tasks(id),
                    FOREIGN KEY (revised_by) REFERENCES users(id)
                )
            ");
        }
    } catch (PDOException $e) {
        // Log error
        error_log("Error checking task_revisions table: " . $e->getMessage());
    }
}

// Panggil fungsi ini di bagian bawah file database.php
checkTaskRevisionsTable();

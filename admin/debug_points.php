<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Pastikan hanya admin yang bisa mengakses
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header('Location: ../index.php');
    exit;
}

// Tambahkan di bagian atas file setelah include
echo "<h2>Cek Status Tabel</h2>";
$tables = ["task_point_settings", "user_points"];
foreach ($tables as $table) {
    $exists = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
    echo "<p>Tabel $table: " . ($exists ? "Ada" : "Tidak Ada") . "</p>";
    
    if ($exists) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<p>Jumlah data di $table: $count</p>";
    }
}

// Fungsi untuk menampilkan struktur tabel
function showTableStructure($pdo, $tableName) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        echo "<h3>Struktur Tabel: $tableName</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}

// Fungsi untuk menampilkan isi tabel
function showTableContent($pdo, $tableName, $limit = 10) {
    try {
        $stmt = $pdo->query("SELECT * FROM $tableName LIMIT $limit");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "<p>Tidak ada data di tabel $tableName</p>";
            return;
        }
        
        echo "<h3>Isi Tabel: $tableName (max $limit rows)</h3>";
        echo "<table border='1'><tr>";
        foreach (array_keys($rows[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['calculate'])) {
        $taskId = $_POST['task_id'];
        $userId = $_POST['user_id'];
        
        if (calculateAndSavePoints($taskId, $userId)) {
            echo "<div style='color:green;'>Poin berhasil dihitung dan disimpan!</div>";
        } else {
            echo "<div style='color:red;'>Gagal menghitung atau menyimpan poin.</div>";
        }
    }
    
    if (isset($_POST['add_points'])) {
        $taskId = $_POST['task_id'];
        $userId = $_POST['user_id'];
        $points = $_POST['points'] ?? 1.0;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO user_points (user_id, task_id, points, earned_at) VALUES (?, ?, ?, NOW())");
            if ($stmt->execute([$userId, $taskId, $points])) {
                echo "<div style='color:green;'>Poin berhasil ditambahkan secara manual!</div>";
            } else {
                echo "<div style='color:red;'>Gagal menambahkan poin secara manual.</div>";
            }
        } catch (PDOException $e) {
            echo "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
        }
    }
    
    if (isset($_POST['create_point_settings'])) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS task_point_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    team VARCHAR(50) NOT NULL,
                    category VARCHAR(50) NOT NULL,
                    task_type VARCHAR(50) NOT NULL,
                    points DECIMAL(5,2) NOT NULL DEFAULT 1.0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY team_category_type (team, category, task_type)
                )
            ");
            
            // Tambahkan beberapa pengaturan default
            $defaultSettings = [
                ['production_team', 'Default', 'Default', 1.0],
                ['content_team', 'Default', 'Default', 1.0],
                ['marketing_team', 'Default', 'Default', 1.0],
                ['production_team', 'Konten', 'Video', 2.0],
                ['production_team', 'Konten', 'Foto', 1.5],
                ['content_team', 'Konten', 'Artikel', 1.5],
                ['content_team', 'Distribusi', 'Default', 0.5]
            ];
            
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO task_point_settings (team, category, task_type, points)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
            
            echo "<div style='color:green;'>Tabel task_point_settings berhasil dibuat dengan pengaturan default!</div>";
        } catch (PDOException $e) {
            echo "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
        }
    }

    if (isset($_POST['add_setting'])) {
        try {
            $team = $_POST['team'];
            $category = $_POST['category'];
            $taskType = $_POST['task_type'];
            $points = $_POST['setting_points'];
            
            $stmt = $pdo->prepare("
                INSERT INTO task_point_settings (team, category, task_type, points)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE points = VALUES(points)
            ");
            
            if ($stmt->execute([$team, $category, $taskType, $points])) {
                echo "<div style='color:green;'>Pengaturan poin berhasil ditambahkan!</div>";
            } else {
                echo "<div style='color:red;'>Gagal menambahkan pengaturan poin.</div>";
            }
        } catch (PDOException $e) {
            echo "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

echo "<h1>Debug Point System</h1>";

// Tampilkan struktur tabel
showTableStructure($pdo, "user_points");
showTableStructure($pdo, "task_point_settings");

// Tampilkan isi tabel
showTableContent($pdo, "user_points");
showTableContent($pdo, "task_point_settings");

// Form untuk menghitung dan menambahkan poin secara manual
echo "<h2>Tambahkan Poin Secara Manual</h2>";
echo "<form method='post'>";
echo "<label>Task ID: </label>";
echo "<input type='number' name='task_id' required>";
echo "<br>";
echo "<label>User ID: </label>";
echo "<input type='number' name='user_id' required>";
echo "<br>";
echo "<label>Points (optional): </label>";
echo "<input type='number' name='points' step='0.1' value='1.0'>";
echo "<br>";
echo "<input type='submit' name='calculate' value='Hitung Poin'>";
echo "<input type='submit' name='add_points' value='Tambah Poin Manual'>";
echo "</form>";

// Tampilkan task yang sudah selesai
echo "<h2>Task yang Sudah Selesai</h2>";
$stmt = $pdo->query("
    SELECT t.id, t.title, t.assigned_to, u.name as user_name, t.status
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    WHERE t.status = 'completed'
    ORDER BY t.id DESC
    LIMIT 20
");

echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Assigned To</th><th>Status</th><th>Action</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['title']}</td>";
    echo "<td>{$row['user_name']} (ID: {$row['assigned_to']})</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>
        <form method='post'>
            <input type='hidden' name='task_id' value='{$row['id']}'>
            <input type='hidden' name='user_id' value='{$row['assigned_to']}'>
            <input type='submit' name='calculate' value='Hitung Poin'>
            <input type='number' name='points' step='0.1' value='1.0' style='width:60px'>
            <input type='submit' name='add_points' value='Tambah Manual'>
        </form>
    </td>";
    echo "</tr>";
}
echo "</table>";

// Tampilkan semua task yang sudah selesai tapi belum dapat poin
echo "<h2>Task Selesai Tanpa Poin</h2>";
$stmt = $pdo->query("
    SELECT t.id, t.title, t.assigned_to, u.name as user_name, t.status, 
           t.updated_at as completed_at
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    WHERE t.status = 'completed'
    AND NOT EXISTS (
        SELECT 1 FROM user_points up WHERE up.task_id = t.id AND up.user_id = t.assigned_to
    )
    ORDER BY t.updated_at DESC
");

echo "<table border='1'><tr><th>ID</th><th>Task</th><th>Assigned To</th><th>Completed At</th><th>Action</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['title']}</td>";
    echo "<td>{$row['user_name']} (ID: {$row['assigned_to']})</td>";
    echo "<td>{$row['completed_at']}</td>";
    echo "<td>
        <form method='post'>
            <input type='hidden' name='task_id' value='{$row['id']}'>
            <input type='hidden' name='user_id' value='{$row['assigned_to']}'>
            <input type='submit' name='calculate' value='Hitung Poin'>
            <input type='number' name='points' step='0.1' value='1.0' style='width:60px'>
            <input type='submit' name='add_points' value='Tambah Manual'>
        </form>
    </td>";
    echo "</tr>";
}
echo "</table>";

// Tambahkan script untuk membuat tabel task_point_settings jika belum ada
echo "<h2>Buat Tabel Point Settings</h2>";
echo "<form method='post'>";
echo "<input type='submit' name='create_point_settings' value='Buat Tabel Point Settings'>";
echo "</form>";

// Tambahkan form untuk menambah pengaturan poin baru
echo "<h2>Tambah Pengaturan Poin Baru</h2>";
echo "<form method='post'>";
echo "<label>Team: </label>";
echo "<select name='team' required>";
echo "<option value='production_team'>Production Team</option>";
echo "<option value='content_team'>Content Team</option>";
echo "<option value='marketing_team'>Marketing Team</option>";
echo "</select><br>";

echo "<label>Category: </label>";
echo "<select name='category' required>";
$categories = $pdo->query("SELECT name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
echo "<option value='Default'>Default</option>";
foreach ($categories as $category) {
    echo "<option value='" . htmlspecialchars($category) . "'>" . htmlspecialchars($category) . "</option>";
}
echo "</select><br>";

echo "<label>Task Type: </label>";
echo "<select name='task_type' required>";
$contentTypes = $pdo->query("SELECT name FROM content_types ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
echo "<option value='Default'>Default</option>";
foreach ($contentTypes as $type) {
    echo "<option value='" . htmlspecialchars($type) . "'>" . htmlspecialchars($type) . "</option>";
}
echo "</select><br>";

echo "<label>Points: </label>";
echo "<input type='number' name='setting_points' step='0.1' value='1.0' required><br>";
echo "<input type='submit' name='add_setting' value='Tambah Pengaturan'>";
echo "</form>";

?>

<style>
table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 20px;
}
th, td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}
th {
    background-color: #f2f2f2;
}
form {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    background-color: #f9f9f9;
}
label {
    display: inline-block;
    width: 120px;
    margin-bottom: 10px;
}
input, select {
    margin-bottom: 10px;
    padding: 5px;
}
</style>
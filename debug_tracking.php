<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$userId = $_SESSION['user_id'] ?? 0;
$logFile = "logs/auto_tracking.log";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Time Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Debug Time Tracking</h1>
        
        <div class="card mb-4">
            <div class="card-header">Session Info</div>
            <div class="card-body">
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">Active Tracking</div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT tt.*, t.title 
                        FROM time_tracking tt
                        JOIN tasks t ON tt.task_id = t.id
                        WHERE tt.user_id = ? AND tt.end_time IS NULL
                    ");
                    $stmt->execute([$userId]);
                    $activeTracking = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($activeTracking) {
                        echo "<pre>";
                        print_r($activeTracking);
                        echo "</pre>";
                    } else {
                        echo "<p>No active tracking found.</p>";
                    }
                } catch (Exception $e) {
                    echo "<p>Error: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">Recent Tracking History</div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT tt.*, t.title 
                        FROM time_tracking tt
                        JOIN tasks t ON tt.task_id = t.id
                        WHERE tt.user_id = ?
                        ORDER BY tt.start_time DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$userId]);
                    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($history) > 0) {
                        echo "<table class='table'>";
                        echo "<thead><tr><th>ID</th><th>Task</th><th>Start</th><th>End</th><th>Duration</th></tr></thead>";
                        echo "<tbody>";
                        foreach ($history as $item) {
                            $start = new DateTime($item['start_time']);
                            $end = $item['end_time'] ? new DateTime($item['end_time']) : new DateTime();
                            $duration = $start->diff($end);
                            
                            echo "<tr>";
                            echo "<td>" . $item['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($item['title']) . "</td>";
                            echo "<td>" . $item['start_time'] . "</td>";
                            echo "<td>" . ($item['end_time'] ?? 'Active') . "</td>";
                            echo "<td>" . $duration->format('%H:%I:%S') . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<p>No tracking history found.</p>";
                    }
                } catch (Exception $e) {
                    echo "<p>Error: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">Log File</div>
            <div class="card-body">
                <?php
                if (file_exists($logFile)) {
                    $logs = file($logFile);
                    $logs = array_slice($logs, -50); // Get last 50 lines
                    foreach ($logs as $log) {
                        echo htmlspecialchars($log) . "<br>";
                    }
                } else {
                    echo "<p>Log file does not exist.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
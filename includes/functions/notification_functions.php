<?php
// File: includes/notification_functions.php

require_once __DIR__ . '/../../config/database.php';

function sendNotification($userId, $message, $link) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $message, $link]);
    
    return $stmt->rowCount() > 0;
}

function logTaskStatus($taskId, $newStatus, $userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO task_status_history (task_id, status, changed_by, changed_at)
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$taskId, $newStatus, $userId]);
    } catch (PDOException $e) {
        error_log("Error in logTaskStatus: " . $e->getMessage());
        return false;
    }
}

function checkDeadlines() {
    global $pdo;
    
    // Check tasks that are due in 1 day
    $stmt = $pdo->prepare("
        SELECT t.id, t.title, t.deadline, u.email, u.name 
        FROM tasks t 
        JOIN users u ON t.assigned_to = u.id 
        WHERE t.status NOT IN ('completed', 'cancelled') 
        AND t.deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll();
    
    foreach ($tasks as $task) {
        $message = "Task '" . $task['title'] . "' akan segera mencapai deadline pada " . 
                  date('d M Y H:i', strtotime($task['deadline'])) . ". Segera selesaikan!";
        sendNotification($task['assigned_to'], $message, "view_task.php?id=" . $task['id']);
        
        // In a real system, you would also send an email here
    }
    
    // Mark overdue tasks
    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET status = 'overdue' 
        WHERE status NOT IN ('completed', 'cancelled', 'overdue') 
        AND deadline < NOW()
    ");
    $stmt->execute();
}
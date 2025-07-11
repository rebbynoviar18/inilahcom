<?php
// File: includes/time_tracking_functions.php

require_once __DIR__ . '/../../config/database.php';

function getActiveTrackings($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT tt.*, t.title 
            FROM time_tracking tt
            JOIN tasks t ON tt.task_id = t.id
            WHERE tt.user_id = ? AND tt.end_time IS NULL
            ORDER BY tt.start_time DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getActiveTrackings: " . $e->getMessage());
        return [];
    }
}

function getActiveTracking($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT tt.*, t.title 
            FROM time_tracking tt
            JOIN tasks t ON tt.task_id = t.id
            WHERE tt.user_id = ? AND tt.end_time IS NULL
            ORDER BY tt.start_time DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getActiveTracking: " . $e->getMessage());
        return false;
    }
}

function displayTimeTrackingTimer($startTime) {
    $start = new DateTime($startTime);
    $now = new DateTime();
    $interval = $start->diff($now);
    
    $hours = $interval->h + ($interval->d * 24);
    $minutes = $interval->i;
    $seconds = $interval->s;
    
    return sprintf(
        '<div class="time-tracking-timer" data-start="%s">
            <div style="font-size:40px;" class="timer-display">%02d:%02d:%02d</div>
        </div>',
        $startTime,
        $hours,
        $minutes,
        $seconds
    );
}
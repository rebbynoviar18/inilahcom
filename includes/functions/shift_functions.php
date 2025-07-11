<?php
// File: includes/shift_functions.php

require_once __DIR__ . '/../../config/database.php';

// Fungsi untuk mendapatkan jadwal shift pengguna (menggunakan tabel shifts)
function getUserShifts($userId, $startDate, $endDate) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM shifts 
        WHERE user_id = ? 
        AND shift_date BETWEEN ? AND ?
        ORDER BY shift_date ASC
    ");
    $stmt->execute([$userId, $startDate, $endDate]);
    return $stmt->fetchAll();
}

function getTeamShifts($role, $startDate, $endDate) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, u.name as user_name, u.role
            FROM shifts s
            JOIN users u ON s.user_id = u.id
            WHERE u.role = ? AND s.shift_date BETWEEN ? AND ?
            ORDER BY s.shift_date ASC, u.name ASC
        ");
        $stmt->execute([$role, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getTeamShifts: " . $e->getMessage());
        return [];
    }
}

function getDailyShifts($date, $role = null) {
    global $pdo;
    
    try {
        $query = "
            SELECT s.*, u.name as user_name, u.role
            FROM shifts s
            JOIN users u ON s.user_id = u.id
            WHERE s.shift_date = ?
        ";
        
        $params = [$date];
        
        if ($role) {
            $query .= " AND u.role = ?";
            $params[] = $role;
        }
        
        $query .= " ORDER BY u.role, u.name";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getDailyShifts: " . $e->getMessage());
        return [];
    }
}

function getShiftTypeLabel($shiftType) {
    $labels = [
        'morning' => 'Pagi',
        'afternoon' => 'Siang',
        'evening' => 'Sore',
        'night' => 'Malam',
        'off' => 'Libur'
    ];
    
    return $labels[$shiftType] ?? $shiftType;
}

function getShiftTypeColor($shiftType) {
    $colors = [
        'morning' => 'success',
        'afternoon' => 'warning',
        'evening' => 'info',
        'night' => 'dark',
        'off' => 'secondary'
    ];
    
    return $colors[$shiftType] ?? 'primary';
}

function getUserShiftOnDate($userId, $date) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM shifts 
            WHERE user_id = ? AND shift_date = ?
        ");
        $stmt->execute([$userId, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: false;
    } catch (PDOException $e) {
        error_log("Error in getUserShiftOnDate: " . $e->getMessage());
        return false;
    }
}
<?php
/**
 * Functions related to program scheduling
 */

/**
 * Get all program names from content_pillars table
 */
function getAllPrograms($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM content_pillars 
            WHERE category_id = 2 
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Jika terjadi error, kembalikan array kosong
        return [];
    }
}

/**
 * Get all program schedules
 */
function getAllProgramSchedules($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT ps.*, cp.name as program_name, 
                   u_pic.name as pic_name, u_editor.name as editor_name
            FROM program_schedules ps
            LEFT JOIN content_pillars cp ON ps.program_id = cp.id
            LEFT JOIN users u_pic ON ps.pic_id = u_pic.id
            LEFT JOIN users u_editor ON ps.editor_id = u_editor.id
            ORDER BY FIELD(ps.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Jika tabel belum ada, kembalikan array kosong
        return [];
    }
}

/**
 * Get program schedules by day
 */
function getProgramSchedulesByDay($pdo, $day) {
    $stmt = $pdo->prepare("
        SELECT ps.*, cp.name as program_name, 
               u_pic.name as pic_name, u_editor.name as editor_name 
        FROM program_schedules ps
        LEFT JOIN content_pillars cp ON ps.program_id = cp.id
        LEFT JOIN users u_pic ON ps.pic_id = u_pic.id
        LEFT JOIN users u_editor ON ps.editor_id = u_editor.id
        WHERE ps.day_of_week = ?
        ORDER BY cp.name
    ");
    $stmt->execute([$day]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all content team members
 */
function getAllContentTeamMembers($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM users 
            WHERE role = 'content_team' 
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get all production team members
 */
function getAllProductionTeamMembers($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM users 
            WHERE role = 'production_team' 
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Add new program schedule
 */
function addProgramSchedule($pdo, $programId, $dayOfWeek, $targetCount, $picId, $editorId) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO program_schedules (program_id, day_of_week, target_count, pic_id, editor_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$programId, $dayOfWeek, $targetCount, $picId ?: null, $editorId ?: null]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update program schedule
 */
function updateProgramSchedule($pdo, $id, $programId, $dayOfWeek, $targetCount, $picId, $editorId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE program_schedules 
            SET program_id = ?, day_of_week = ?, target_count = ?, pic_id = ?, editor_id = ?
            WHERE id = ?
        ");
        return $stmt->execute([$programId, $dayOfWeek, $targetCount, $picId ?: null, $editorId ?: null, $id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete program schedule
 */
function deleteProgramSchedule($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM program_schedules WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get program completion status for a specific day
 */
function getProgramCompletionStatus($pdo, $programId, $date) {
    // Get tasks completed for this program on this date
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as completed_count
        FROM tasks t
        WHERE t.content_pillar_id = ? 
        AND t.status = 'completed'
        AND DATE(t.updated_at) = ?
    ");
    $stmt->execute([$programId, $date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['completed_count'] ?? 0;
}

/**
 * Get program schedules for the current week with completion status
 */
function getCurrentWeekProgramSchedules($pdo) {
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $result = [];
    
    // Get current week dates
    $today = new DateTime();
    $currentDayOfWeek = $today->format('N') - 1; // 0 (Monday) to 6 (Sunday)
    $startOfWeek = clone $today;
    $startOfWeek->modify('-' . $currentDayOfWeek . ' days');
    
    foreach ($days as $index => $day) {
        $currentDate = clone $startOfWeek;
        $currentDate->modify('+' . $index . ' days');
        $dateStr = $currentDate->format('Y-m-d');
        
        $schedules = getProgramSchedulesByDay($pdo, $day);
        
        foreach ($schedules as &$schedule) {
            $completedCount = getProgramCompletionStatus($pdo, $schedule['program_id'], $dateStr);
            $schedule['completed_count'] = $completedCount;
            $schedule['is_completed'] = $completedCount >= $schedule['target_count'];
            $schedule['date'] = $dateStr;
        }
        
        $result[$day] = [
            'date' => $dateStr,
            'is_today' => $dateStr === $today->format('Y-m-d'),
            'schedules' => $schedules
        ];
    }
    
    return $result;
}

/**
 * Get day name in Indonesian
 */
function getDayNameIndonesian($dayOfWeek) {
    $days = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    
    return $days[$dayOfWeek] ?? $dayOfWeek;
}
?>
<?php
// File: includes/ui_helper_functions.php

function getStatusBadge($status) {
    if (empty($status)) {
        return '<span class="badge bg-secondary">Draft</span>';
    }
    
    $class = getStatusColor($status);
    $label = getStatusLabel($status);
    
    return '<span class="badge bg-' . $class . '">' . $label . '</span>';
}

function getPriorityBadge($priority) {
    if (empty($priority)) {
        return '<span class="badge bg-secondary">Tidak Ada Prioritas</span>';
    }
    
    $class = getPriorityColor($priority);
    $label = getPriorityLabel($priority);
    
    return '<span class="badge bg-' . $class . '">' . $label . '</span>';
}


function getPriorityLabel($priority) {
    $priorityText = [
        'low' => 'Rendah',
        'medium' => 'Sedang',
        'high' => 'Tinggi',
        'urgent' => 'Urgent'
    ];
    
    return $priorityText[$priority] ?? $priority;
}

function getPriorityColor($priority) {
    $badgeClass = [
        'low' => 'success',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger'
    ];
    
    return $badgeClass[$priority] ?? 'secondary';
}

function getTaskTypeBadge($type) {
    if (empty($type)) {
        return '<span class="badge bg-secondary">Umum</span>';
    }
    
    $typeClasses = [
        'marketing' => 'primary',
        'content' => 'success',
        'production' => 'warning'
    ];
    
    $typeLabels = [
        'marketing' => 'Marketing',
        'content' => 'Content',
        'production' => 'Production'
    ];
    
    $class = $typeClasses[$type] ?? 'secondary';
    $label = $typeLabels[$type] ?? 'Umum';
    
    return '<span class="badge bg-' . $class . '">' . $label . '</span>';
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yang lalu' : 'baru saja';
}

function getPlatformIcon($platform) {
    $platform = strtolower($platform);
    
    $icons = [
        'instagram' => '<i class="fab fa-instagram" title="Instagram"></i>',
        'facebook' => '<i class="fab fa-facebook" title="Facebook"></i>',
        'twitter' => '<i class="fab fa-twitter" title="Twitter/X"></i>',
        'x' => '<i class="fab fa-twitter" title="Twitter/X"></i>',
        'tiktok' => '<i class="fab fa-tiktok" title="TikTok"></i>',
        'threads' => '<i class="fas fa-at" title="Threads"></i>',
        'youtube' => '<i class="fab fa-youtube" title="YouTube"></i>',
        'linkedin' => '<i class="fab fa-linkedin" title="LinkedIn"></i>',
        'pinterest' => '<i class="fab fa-pinterest" title="Pinterest"></i>'
    ];
    
    return $icons[$platform] ?? '<i class="fas fa-globe" title="' . htmlspecialchars($platform) . '"></i>';
}

function displayPlatformIcons($taskId) {
    $platforms = getTaskDistributionPlatforms($taskId);
    $output = '';
    
    if (!empty($platforms)) {
        foreach ($platforms as $platform) {
            $output .= '<span class="me-1">' . getPlatformIcon($platform) . '</span>';
        }
    }
    
    return $output;
}
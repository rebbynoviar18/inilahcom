<?php
// File: includes/functions.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions/auth_functions.php';
require_once __DIR__ . '/functions/task_functions.php';
require_once __DIR__ . '/functions/user_functions.php';
require_once __DIR__ . '/functions/notification_functions.php';
require_once __DIR__ . '/functions/time_tracking_functions.php';
require_once __DIR__ . '/functions/points_functions.php';
require_once __DIR__ . '/functions/shift_functions.php';
require_once __DIR__ . '/functions/viral_content_functions.php';
require_once __DIR__ . '/functions/ui_helper_functions.php';
require_once __DIR__ . '/functions/info_functions.php';
require_once __DIR__ . '/functions/whatsapp_functions.php';

/**
 * Menghapus karakter formatting WhatsApp dari teks
 * 
 * @param string $text Teks yang akan dibersihkan
 * @return string Teks yang sudah dibersihkan
 */
function cleanWhatsAppFormatting($text) {
    return str_replace(['*', '_', '~', '```'], '', $text);
}

function getFileIconClass($extension) {
    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'mp4' => 'fa-file-video',
        'mov' => 'fa-file-video',
        'avi' => 'fa-file-video',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image'
    ];
    
    return $icons[$extension] ?? 'fa-file';
}

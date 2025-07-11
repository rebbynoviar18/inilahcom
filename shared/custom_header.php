<?php
// Simpan path asli
$originalPath = $_SERVER['PHP_SELF'];

// Tentukan base path berdasarkan role
$userRole = getUserRole();
$basePath = '';
switch ($userRole) {
    case 'creative_director':
        $basePath = '../admin/';
        break;
    case 'content_team':
        $basePath = '../content/';
        break;
    case 'production_team':
        $basePath = '../production/';
        break;
    default:
        $basePath = '../';
}

// Kita akan memodifikasi output buffer untuk mengganti path di navigasi
ob_start();

// Include header asli
include '../includes/header.php';

// Ambil output buffer
$header_content = ob_get_clean();

// Ganti semua link di navigasi yang mengarah ke file lokal
$header_content = preg_replace(
    '/<a class="nav-link[^"]*" href="([^":]*)"/i',
    '<a class="nav-link$1" href="' . $basePath . '$1"',
    $header_content
);

// Tampilkan header yang sudah dimodifikasi
echo $header_content;
?>
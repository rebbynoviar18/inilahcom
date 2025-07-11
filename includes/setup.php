<?php
// Buat direktori yang diperlukan jika belum ada
$directories = [
    '../uploads/tasks',
    '../uploads/resources',
    '../logs'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

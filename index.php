<?php
// File: index.php

require_once 'config/database.php';
require_once 'includes/auth.php';

// Jika user sudah login, redirect ke dashboard sesuai role
if (isLoggedIn()) {
    redirectBasedOnRole();
} else {
    // Jika belum login, redirect ke halaman login
    header("Location: login.php");
    exit();
}

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$basePath = '/creative';

switch ($request) {
    case $basePath.'/':
    case $basePath.'/login':
        require 'login.php';
        break;
    case $basePath.'/logout':
        require 'logout.php';
        break;
    case $basePath.'/register':
        require 'register.php';
        break;
    default:
        http_response_code(404);
        require '404.php';
        break;
}

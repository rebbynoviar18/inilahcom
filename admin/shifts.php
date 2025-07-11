<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

// Redirect jika bukan creative director
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Include file shifts dari shared
include '../shared/shifts.php';
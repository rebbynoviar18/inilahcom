<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

// Redirect jika bukan content team
if (getUserRole() !== 'content_team') {
    header("Location: ../index.php");
    exit();
}

// Include file shifts dari shared
include '../shared/shifts.php';
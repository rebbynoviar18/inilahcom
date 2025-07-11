<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

// Redirect jika bukan production team
if (getUserRole() !== 'production_team') {
    header("Location: ../index.php");
    exit();
}

// Include file shifts dari shared
include '../shared/shifts.php';
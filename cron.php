<?php
// File: cron.php

require_once 'config/database.php';
require_once 'includes/functions.php';

// This should be called by a cron job daily
checkDeadlines();

echo "Deadline checks completed at " . date('Y-m-d H:i:s');
?>
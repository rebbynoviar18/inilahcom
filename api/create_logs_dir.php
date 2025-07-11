<?php
$logDir = "../logs";
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
echo json_encode(['success' => true]);
?>
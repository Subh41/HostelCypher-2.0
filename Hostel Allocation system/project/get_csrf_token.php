<?php
session_start();
require_once 'config.php';

// Generate new token if none exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

echo json_encode([
    'token' => $_SESSION['csrf_token']
]);
?>

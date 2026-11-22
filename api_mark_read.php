<?php
// API: POST api_mark_read.php  ->  minamarkahang nabasa na ang lahat ng notification ng user
include "db.php";
include "notify.php";
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    echo json_encode(["success" => false]);
    exit;
}

ensureNotifTable($conn);
$uid = (int)$_SESSION['user_id'];
$st = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$st->bind_param('i', $uid);
$st->execute();
echo json_encode(["success" => true]);
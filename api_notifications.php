<?php
// API: GET api_notifications.php  ->  JSON of the logged-in user's notifications
include "db.php";
include "notify.php";
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

ensureNotifTable($conn);
$uid = (int)$_SESSION['user_id'];

$st = $conn->prepare("SELECT id, message, link, is_read, created_at FROM notifications
                      WHERE user_id = ? ORDER BY id DESC LIMIT 20");
$st->bind_param('i', $uid);
$st->execute();
$res = $st->get_result();

$list = []; $unread = 0;
while ($r = $res->fetch_assoc()) {
    if ((int)$r['is_read'] === 0) $unread++;
    $list[] = $r;
}
echo json_encode(["success" => true, "unread" => $unread, "notifications" => $list]);
<?php
// Helper for notifications. Usage: include "notify.php";

function ensureNotifTable($c) {
    $c->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message VARCHAR(255) NOT NULL,
        link VARCHAR(255) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id, is_read)
    )");
    // If the table is old (no link column), add it
    $chk = $c->query("SHOW COLUMNS FROM notifications LIKE 'link'");
    if ($chk && $chk->num_rows === 0) {
        $c->query("ALTER TABLE notifications ADD COLUMN link VARCHAR(255) NULL AFTER message");
    }
}

// Add a notification using the user_id (with an optional link)
function addNotification($c, $userId, $message, $link = null) {
    try {
        ensureNotifTable($c);
        $st = $c->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        if (!$st) return false;
        $uid = (int)$userId;
        $st->bind_param('iss', $uid, $message, $link);
        return $st->execute();
    } catch (Throwable $e) {
        return false;
    }
}

// Send a notification to the owner of a booking.
// $withLink = true  -> the notification opens pay.php (ticket/barcode)
// $withLink = false -> plain message only (used for the "thank you" message)
function notifyBooking($c, $bookingId, $message, $withLink = true) {
    try {
        $st = $c->prepare("SELECT email FROM bookkings WHERE id = ? LIMIT 1");
        if (!$st) return false;
        $bid = (int)$bookingId;
        $st->bind_param('i', $bid);
        $st->execute();
        $b = $st->get_result()->fetch_assoc();
        if (!$b) return false;

        $st2 = $c->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if (!$st2) return false;
        $st2->bind_param('s', $b['email']);
        $st2->execute();
        $u = $st2->get_result()->fetch_assoc();
        if (!$u) return false;

        return addNotification($c, $u['id'], $message, $withLink ? 'pay.php?id=' . $bid : null);
    } catch (Throwable $e) {
        return false;
    }
}
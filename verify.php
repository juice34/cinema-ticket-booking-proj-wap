<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['admin'])) { header("Location: admin.php"); exit; }

$code = strtoupper(trim($_GET['code'] ?? ''));
$b = null;
if (preg_match('/^BK(\d+)-([A-Z0-9]{10})$/', $code, $m)) {
    $id = (int)$m[1]; $ref = $m[2];
    $s = mysqli_prepare($conn, "SELECT * FROM bookkings WHERE id=? AND payment_ref=? AND status='Confirmed' LIMIT 1");
    mysqli_stmt_bind_param($s, "is", $id, $ref);
    mysqli_stmt_execute($s);
    $b = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Ticket</title>
</head>
<body style="font-family:Arial,sans-serif;max-width:420px;margin:40px auto;padding:0 16px">
<h2>Verify Ticket</h2>
<form method="get">
    <input name="code" value="<?= e($code) ?>" placeholder="I-scan o i-type ang ticket code" autofocus autocomplete="off"
           style="width:100%;padding:10px;font-size:16px;box-sizing:border-box">
</form>
<?php if ($code !== ''): ?>
    <?php if ($b): ?>
        <p style="background:#e6f7e6;color:#064;padding:12px;border-radius:6px;line-height:1.7">
            <b>&#10004; VALID TICKET</b><br>
            <?= e($b['movie']) ?><br>
            Showtime: <?= e($b['showtime']) ?><br>
            Seat: <?= e($b['seat']) ?> (<?= (int)$b['quantity'] ?>)<br>
            Email: <?= e($b['email']) ?>
        </p>
    <?php else: ?>
        <p style="background:#fde8e8;color:#a00;padding:12px;border-radius:6px"><b>&#10008; INVALID TICKET</b></p>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
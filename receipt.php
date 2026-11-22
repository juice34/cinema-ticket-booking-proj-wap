<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$uid = (int)$_SESSION['user_id'];
$email = '';
$u = mysqli_query($conn, "SELECT email FROM users WHERE id = $uid LIMIT 1");
if ($u && $row = mysqli_fetch_assoc($u)) $email = $row['email'];

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM bookkings WHERE id = ? AND email = ? AND status = 'Confirmed' LIMIT 1");
mysqli_stmt_bind_param($stmt, "is", $id, $email);
mysqli_stmt_execute($stmt);
$b = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$b) { die("Receipt not available."); }

$ticketCode = 'BK' . (int)$b['id'] . '-' . $b['payment_ref'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt <?= e($b['payment_ref']) ?></title>
<style>
    body { font-family:"Segoe UI",Arial,sans-serif; color:#333; margin:0; background:#f4f4f4; }
    .receipt { max-width:420px; margin:30px auto; background:#fff; padding:28px; border:1px solid #ddd; border-radius:10px; }
    h1 { margin:0; font-size:22px; text-align:center; }
    .sub { text-align:center; color:#888; font-size:13px; margin-bottom:16px; }
    table { width:100%; border-collapse:collapse; margin:12px 0; }
    td { padding:6px 0; border-bottom:1px dashed #ddd; }
    td:last-child { text-align:right; font-weight:600; }
    .total td { font-size:20px; color:#0033ff; border-bottom:0; }
    .code { text-align:center; margin-top:16px; overflow-x:auto; }
    .btn { display:block; width:420px; max-width:90%; margin:0 auto 30px; text-align:center; padding:12px; background:#0033ff; color:#fff; border:0; border-radius:6px; font-size:16px; cursor:pointer; }
    @media print { body { background:#fff; } .receipt { border:0; margin:0 auto; } .btn { display:none; } }
</style>
</head>
<body>
<div class="receipt">
    <h1>Movie Ticket Booking</h1>
    <div class="sub">Official Receipt / E-Ticket</div>
    <table>
        <tr><td>Movie</td><td><?= e($b['movie']) ?></td></tr>
        <tr><td>Showtime</td><td><?= e($b['showtime']) ?></td></tr>
        <tr><td>Seat(s)</td><td><?= e($b['seat']) ?></td></tr>
        <tr><td>Tickets</td><td><?= (int)$b['quantity'] ?></td></tr>
        <tr><td>Email</td><td><?= e($b['email']) ?></td></tr>
        <tr><td>Payment method</td><td><?= e($b['payment_method']) ?></td></tr>
        <tr><td>Reference no.</td><td><?= e($b['payment_ref']) ?></td></tr>
        <tr class="total"><td>Total paid</td><td>₱<?= number_format($b['total_price'], 2) ?></td></tr>
    </table>
    <div class="code">
        <svg id="barcode"></svg>
        <div style="font-size:12px;color:#888">Ipakita ang barcode na ito sa entrance</div>
    </div>
</div>
<button class="btn" onclick="window.print()">&#11015; Save as PDF / Print</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
JsBarcode("#barcode", <?= json_encode($ticketCode) ?>, { format: "CODE128", width: 2, height: 70, displayValue: true });
window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });
</script>
</body>
</html>
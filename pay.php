<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Manila');

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['user_id'])) { header("Location: index.php"); exit; }

// User's email (to make sure the booking belongs to them)
$uid = (int)$_SESSION['user_id'];
$email = '';
$u = mysqli_query($conn, "SELECT email FROM users WHERE id = $uid LIMIT 1");
if ($u && $row = mysqli_fetch_assoc($u)) $email = $row['email'];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT *, TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS age FROM bookkings WHERE id = ? AND email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "is", $id, $email);
mysqli_stmt_execute($stmt);
$b = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$b) { die("Booking not found. <a href='movies.php'>Back</a>"); }

// Show date (empty for old bookings that have no show_date)
$showDate  = $b['show_date'] ?? '';
$dateLabel = ($showDate !== '' && $showDate !== null) ? date('D, j M Y', strtotime($showDate)) : '';
$showPassed = false;
if ($dateLabel !== '') {
    $tz  = new DateTimeZone('Asia/Manila');
    $sdt = DateTime::createFromFormat('Y-m-d g:i A', $showDate . ' ' . $b['showtime'], $tz);
    $showPassed = ($sdt && $sdt <= new DateTime('now', $tz));
}

// Softer colors for the dark theme
$METHODS = ['GCash' => '#2f6ebf', 'Maya' => '#2f9a74', 'Card' => '#5a6382'];
$error = '';
$expired = ($b['status'] === 'Pending' && (int)$b['age'] >= 10);

// Cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel']) && $b['status'] === 'Pending') {
    $c = mysqli_prepare($conn, "UPDATE bookkings SET status='Cancelled' WHERE id=? AND status='Pending'");
    mysqli_stmt_bind_param($c, "i", $id);
    mysqli_stmt_execute($c);
    header("Location: movies.php"); exit;
}

// Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method']) && $b['status'] === 'Pending' && !$expired) {
    $method = $_POST['method'];

    if ($showPassed) {
        $error = "This showtime has already passed. Please book a different time.";
    } elseif (!isset($METHODS[$method])) {
        $error = "Please choose a payment method.";
    } elseif ($method === 'GCash' || $method === 'Maya') {
        $mobile = preg_replace('/\D/', '', $_POST['mobile'] ?? '');
        if (!preg_match('/^09\d{9}$/', $mobile)) $error = "Enter a valid mobile number (09XXXXXXXXX).";
    } else {
        $num  = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
        $nm   = trim($_POST['card_name'] ?? '');
        $exp  = trim($_POST['card_exp'] ?? '');
        $cvv  = trim($_POST['card_cvv'] ?? '');

        // Luhn check
        $sum = 0; $alt = false;
        for ($i = strlen($num) - 1; $i >= 0; $i--) {
            $d = (int)$num[$i];
            if ($alt) { $d *= 2; if ($d > 9) $d -= 9; }
            $sum += $d; $alt = !$alt;
        }
        $expOk = false;
        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $exp, $m)) {
            $expOk = ((int)('20' . $m[2]) * 100 + (int)$m[1]) >= (int)date('Ym');
        }

        if (strlen($num) < 13 || strlen($num) > 19 || $sum % 10 !== 0) $error = "Invalid card number.";
        elseif ($nm === '')                                           $error = "Enter the name on the card.";
        elseif (!$expOk)                                              $error = "Card is invalid or expired (MM/YY).";
        elseif (!preg_match('/^\d{3,4}$/', $cvv))                     $error = "Invalid CVV.";
    }

    if (!$error) {
        // DEMO / simulated: put the real payment gateway here (e.g. PayMongo) if you want.
        // Card details are not saved.
        // Not yet Confirmed. Wait for admin approval first.
        $ref = strtoupper(substr(md5(uniqid('', true)), 0, 10));
        $up = mysqli_prepare($conn, "UPDATE bookkings SET status='For Approval', payment_method=?, payment_ref=? WHERE id=? AND status='Pending'");
        mysqli_stmt_bind_param($up, "ssi", $method, $ref, $id);
        mysqli_stmt_execute($up);
        include_once "notify.php";
        $when = ($dateLabel !== '') ? " (" . $dateLabel . ", " . $b['showtime'] . ")" : "";
        notifyBooking($conn, $id, "Your payment for " . $b['movie'] . $when . " was received. Waiting for admin approval.");
        header("Location: pay.php?id=" . $id);
        exit;
    }
}

$paid     = ($b['status'] === 'Confirmed');
$waiting  = ($b['status'] === 'For Approval');
$rejected = ($b['status'] === 'Rejected');
// Ticket code for the barcode (id + payment reference)
$ticketCode = $paid ? 'BK' . (int)$b['id'] . '-' . $b['payment_ref'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment</title>
<link rel="stylesheet" href="theme.css">
<script src="theme.js"></script>
<style>
    * { box-sizing:border-box; }
    body { font-family:"Segoe UI",Arial,sans-serif; background:var(--bg); color:var(--text); margin:0; line-height:1.4; }
    a { color:var(--link); }
    a:focus-visible, button:focus-visible, input:focus-visible { outline:2px solid var(--accent); outline-offset:3px; }
    .topbar { padding:14px 28px; background:var(--surface); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; }
    .topbar a { font-weight:600; text-decoration:none; }
    .topbar a:hover { text-decoration:underline; }
    main { max-width:520px; margin:30px auto; padding:0 20px; }
    .card { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:18px; margin-bottom:20px; line-height:1.7; }
    .card h2 { margin:0 0 8px; font-size:20px; }
    .total { font-size:26px; color:var(--accent-text); font-weight:700; }
    .methods { display:flex; gap:10px; margin:10px 0 18px; }
    .methods label { flex:1; margin:0; cursor:pointer; }
    .methods input { display:none; }
    .methods span { display:block; text-align:center; padding:14px 6px; border:2px solid var(--border); background:var(--surface); border-radius:8px; font-weight:700; transition:border-color .15s; }
    .methods label:hover span { border-color:var(--muted); }
    .methods input:checked + span { color:#fff; border-color:transparent; background:var(--c); }
    .field { display:none; }
    .field.show { display:block; }
    .field label { font-weight:600; display:block; margin:10px 0 4px; }
    .field input { width:100%; padding:10px; font-size:16px; background:var(--surface); color:var(--text); border:1px solid var(--border); border-radius:6px; }
    .field input::placeholder { color:var(--placeholder); }
    .row { display:flex; gap:10px; }
    button.pay { background:var(--accent); color:#1a1405; border:0; padding:12px 28px; font-size:18px; font-weight:700; border-radius:6px; cursor:pointer; width:100%; margin-top:18px; }
    button.pay:hover { filter:brightness(1.08); }
    button.cancel { background:none; border:0; color:var(--danger); cursor:pointer; margin-top:12px; font-size:15px; width:100%; }
    button.cancel:hover { text-decoration:underline; }
    .msg { padding:12px; border-radius:6px; margin-bottom:16px; border:1px solid transparent; }
    .err  { background:var(--err-bg); color:var(--err-fg); border-color:var(--err-bd); }
    .ok   { background:var(--ok-bg); color:var(--ok-fg); border-color:var(--ok-bd); }
    .wait { background:var(--wait-bg); color:var(--wait-fg); border-color:var(--wait-bd); }
    .note { font-size:13px; color:var(--muted); text-align:center; margin-top:10px; }
    /* White background on the barcode so it is easy to scan */
    .barcode-box { text-align:center; margin-top:14px; padding-top:14px; border-top:1px dashed var(--border); overflow-x:auto; }
    .barcode-box .bc { display:inline-block; background:#fff; padding:10px 14px; border-radius:8px; }
    .barcode-box small { display:block; color:var(--muted); font-size:13px; margin-top:8px; }
    a.dl { display:block; text-align:center; text-decoration:none; background:var(--accent); color:#1a1405; font-weight:700; padding:12px; font-size:17px; border-radius:6px; margin-bottom:14px; }
    a.dl:hover { filter:brightness(1.08); }
</style>
</head>
<body>
<header class="topbar">
    <strong>Movie Ticket Booking</strong>
    <span><button id="themeBtn" class="theme-btn" type="button" onclick="toggleTheme()"></button><?php include "bell.php"; ?><a href="movies.php">&larr; Back to movies</a></span>
</header>

<main>
<?php if ($paid): ?>
    <div class="msg ok"><b>Approved!</b> Your booking is confirmed.</div>
    <div class="card">
        <h2><?= e($b['movie']) ?></h2>
        <?php if ($dateLabel !== ''): ?>Date: <b><?= e($dateLabel) ?></b><br><?php endif; ?>
        Showtime: <b><?= e($b['showtime']) ?></b><br>
        Seat: <b><?= e($b['seat']) ?></b> (<?= (int)$b['quantity'] ?>)<br>
        Paid via: <b><?= e($b['payment_method']) ?></b><br>
        Reference no.: <b><?= e($b['payment_ref']) ?></b><br>
        Total: <span class="total">₱<?= number_format($b['total_price'], 2) ?></span>

        <div class="barcode-box">
            <div class="bc"><svg id="barcode"></svg></div>
            <small>Show this barcode at the cinema entrance</small>
        </div>
    </div>
    <a class="dl" href="receipt.php?id=<?= (int)$id ?>" target="_blank">&#11015; Download receipt (PDF)</a>
    <a href="movies.php">Back to movies</a>

<?php elseif ($waiting): ?>
    <div class="msg wait">
        <b>Your payment has been received.</b> We're just waiting for admin approval before your ticket is issued. This page will refresh automatically.
    </div>
    <div class="card">
        <h2><?= e($b['movie']) ?></h2>
        <?php if ($dateLabel !== ''): ?>Date: <b><?= e($dateLabel) ?></b><br><?php endif; ?>
        Showtime: <b><?= e($b['showtime']) ?></b><br>
        Seat: <b><?= e($b['seat']) ?></b> (<?= (int)$b['quantity'] ?>)<br>
        Paid via: <b><?= e($b['payment_method']) ?></b><br>
        Total: <span class="total">₱<?= number_format($b['total_price'], 2) ?></span>
    </div>
    <script>setTimeout(function(){ location.reload(); }, 10000);</script>
    <a href="movies.php">Back to movies</a>

<?php elseif ($rejected): ?>
    <div class="msg err">The admin did not approve this payment. Please contact the cinema or book again.</div>
    <a href="movies.php">Back to movies</a>

<?php elseif ($b['status'] === 'Cancelled' || $expired): ?>
    <div class="msg err">This booking has expired or been cancelled. Please book again.</div>
    <a href="movies.php">Back to movies</a>

<?php else: ?>
    <div class="card">
        <h2><?= e($b['movie']) ?></h2>
        <?php if ($dateLabel !== ''): ?>Date: <b><?= e($dateLabel) ?></b><br><?php endif; ?>
        Showtime: <b><?= e($b['showtime']) ?></b><br>
        Seat: <b><?= e($b['seat']) ?></b> (<?= (int)$b['quantity'] ?> ticket)<br>
        Total: <span class="total">₱<?= number_format($b['total_price'], 2) ?></span>
    </div>

    <?php if ($error): ?><div class="msg err"><?= e($error) ?></div><?php endif; ?>

    <form method="post" id="payForm" autocomplete="off">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <b>Choose a payment method</b>
        <div class="methods">
            <?php foreach ($METHODS as $m => $color): ?>
                <label style="--c:<?= $color ?>">
                    <input type="radio" name="method" value="<?= $m ?>" <?= (($_POST['method'] ?? '') === $m) ? 'checked' : '' ?>>
                    <span><?= $m ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="field" id="f-wallet">
            <label for="mobile">Mobile number</label>
            <input type="tel" id="mobile" name="mobile" placeholder="09XXXXXXXXX" maxlength="11" value="<?= e($_POST['mobile'] ?? '') ?>">
        </div>

        <div class="field" id="f-card">
            <label for="card_number">Card number</label>
            <input type="text" id="card_number" name="card_number" inputmode="numeric" placeholder="1234 5678 9012 3456" maxlength="23">
            <label for="card_name">Name on card</label>
            <input type="text" id="card_name" name="card_name" value="<?= e($_POST['card_name'] ?? '') ?>">
            <div class="row">
                <div style="flex:1"><label for="card_exp">Expiry</label>
                    <input type="text" id="card_exp" name="card_exp" placeholder="MM/YY" maxlength="5"></div>
                <div style="flex:1"><label for="card_cvv">CVV</label>
                    <input type="password" id="card_cvv" name="card_cvv" inputmode="numeric" maxlength="4"></div>
            </div>
        </div>

        <button class="pay" type="submit">Pay ₱<?= number_format($b['total_price'], 2) ?></button>
        <p class="note">Your seat is held for 10 minutes while you pay.</p>
    </form>

    <form method="post">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <button class="cancel" name="cancel" value="1" type="submit" onclick="return confirm('Cancel the booking?')">Cancel booking</button>
    </form>
<?php endif; ?>
</main>

<?php if ($paid): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
JsBarcode("#barcode", <?= json_encode($ticketCode) ?>, { format: "CODE128", width: 2, height: 70, displayValue: true });
</script>
<?php elseif (!$waiting && !$rejected): ?>
<script>
var radios = document.querySelectorAll('input[name="method"]');
function showFields() {
    var v = (document.querySelector('input[name="method"]:checked') || {}).value;
    var w = document.getElementById('f-wallet'), c = document.getElementById('f-card');
    if (!w || !c) return;
    w.classList.toggle('show', v === 'GCash' || v === 'Maya');
    c.classList.toggle('show', v === 'Card');
}
radios.forEach(function (r) { r.addEventListener('change', showFields); });
showFields();

var cn = document.getElementById('card_number');
if (cn) cn.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
});
var ce = document.getElementById('card_exp');
if (ce) ce.addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '').slice(0, 4);
    this.value = v.length > 2 ? v.slice(0, 2) + '/' + v.slice(2) : v;
});
</script>
<?php endif; ?>
</body>
</html>
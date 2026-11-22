<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Manila');

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$DEFAULT_PRICE = 250; // used if there is no price column in the movies table
$SHOWTIMES = ['10:00 AM', '1:00 PM', '4:00 PM', '7:00 PM', '10:00 PM'];
$ROWS = ['A', 'B', 'C', 'D', 'E'];
$COLS = 8;
$CAPACITY = count($ROWS) * $COLS; // seats per showtime
$MAX_DAYS = 30;                   // how many days from today can be booked

$tz    = new DateTimeZone('Asia/Manila');
$today = (new DateTime('now', $tz))->format('Y-m-d');
$last  = (new DateTime('now', $tz))->modify("+$MAX_DAYS days")->format('Y-m-d');

$title = $_GET['movie'] ?? ($_POST['movie'] ?? '');

// Get the movie
$stmt = mysqli_prepare($conn, "SELECT * FROM movies WHERE title = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $title);
mysqli_stmt_execute($stmt);
$movie = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$movie) { die("Movie not found. <a href='movies.php'>Back</a>"); }

$PRICE = (isset($movie['price']) && $movie['price'] > 0) ? (float)$movie['price'] : $DEFAULT_PRICE;
$rating = $movie['rating'] ?? 'PG';
$ratingClass = 'r-' . preg_replace('/[^a-z0-9]/', '', strtolower($rating));

$posterPath = '';
if (!empty($movie['poster'])) {
    if (file_exists(__DIR__ . "/uploads/" . $movie['poster']))    $posterPath = "uploads/" . $movie['poster'];
    elseif (file_exists(__DIR__ . "/images/" . $movie['poster'])) $posterPath = "images/" . $movie['poster'];
}

// User's email and name
$email = '';
$uid = (int)$_SESSION['user_id'];
$u = mysqli_query($conn, "SELECT email FROM users WHERE id = $uid LIMIT 1");
if ($u && $row = mysqli_fetch_assoc($u)) $email = $row['email'];
$name = $_SESSION['user_name'] ?? '';

// Make sure the required columns exist in bookkings (added only once)
$needed = [
    'status'         => "VARCHAR(15) NOT NULL DEFAULT 'Confirmed'",
    'created_at'     => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    'payment_method' => "VARCHAR(20) NULL",
    'payment_ref'    => "VARCHAR(30) NULL",
    'show_date'      => "DATE NULL",
];
foreach ($needed as $col => $def) {
    $chk = mysqli_query($conn, "SHOW COLUMNS FROM bookkings LIKE '$col'");
    if ($chk && mysqli_num_rows($chk) === 0) mysqli_query($conn, "ALTER TABLE bookkings ADD COLUMN $col $def");
}

// Has the showtime on that date already passed?
function showtimePassed($date, $showtime, $tz) {
    $dt = DateTime::createFromFormat('Y-m-d g:i A', $date . ' ' . $showtime, $tz);
    return !$dt || $dt <= new DateTime('now', $tz);
}

// Taken = Confirmed, or Pending for less than 10 minutes (the seat is still held while paying)
function takenSeats($conn, $title, $showtime, $date) {
    $taken = [];
    $s = mysqli_prepare($conn, "SELECT seat FROM bookkings WHERE movie = ? AND showtime = ? AND show_date = ?
        AND (status = 'Confirmed' OR (status = 'Pending' AND created_at > (NOW() - INTERVAL 10 MINUTE)))");
    mysqli_stmt_bind_param($s, "sss", $title, $showtime, $date);
    mysqli_stmt_execute($s);
    $res = mysqli_stmt_get_result($s);
    while ($row = mysqli_fetch_assoc($res)) {
        foreach (explode(',', $row['seat']) as $x) $taken[] = trim($x);
    }
    return $taken;
}

// How many seats are taken per date and showtime (for the calendar)
function bookedCounts($conn, $title, $from, $to) {
    $out = [];
    $s = mysqli_prepare($conn, "SELECT show_date, showtime, seat FROM bookkings WHERE movie = ?
        AND show_date BETWEEN ? AND ?
        AND (status = 'Confirmed' OR (status = 'Pending' AND created_at > (NOW() - INTERVAL 10 MINUTE)))");
    mysqli_stmt_bind_param($s, "sss", $title, $from, $to);
    mysqli_stmt_execute($s);
    $res = mysqli_stmt_get_result($s);
    while ($row = mysqli_fetch_assoc($res)) {
        $d = $row['show_date']; $t = $row['showtime'];
        $n = count(array_filter(array_map('trim', explode(',', $row['seat']))));
        $out[$d][$t] = ($out[$d][$t] ?? 0) + $n;
    }
    return $out;
}

function validDate($d) {
    $dt = DateTime::createFromFormat('Y-m-d', (string)$d);
    return $dt && $dt->format('Y-m-d') === $d;
}

if (isset($_GET['taken'])) {
    header('Content-Type: application/json');
    $d = $_GET['date'] ?? '';
    echo json_encode(validDate($d) ? takenSeats($conn, $title, $_GET['taken'], $d) : []);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showDate = $_POST['show_date'] ?? '';
    $showtime = $_POST['showtime'] ?? '';
    $seats = array_filter(array_map('trim', explode(',', $_POST['seats'] ?? '')));

    $valid = [];
    foreach ($ROWS as $rw) for ($c = 1; $c <= $COLS; $c++) $valid[] = $rw . $c;

    if (!validDate($showDate) || $showDate < $today || $showDate > $last) {
        $error = "Choose a date on the calendar.";
    } elseif (!in_array($showtime, $SHOWTIMES, true)) {
        $error = "Choose a showtime.";
    } elseif (showtimePassed($showDate, $showtime, $tz)) {
        $error = "That showtime has already passed. Choose another one.";
    } elseif (!$seats) {
        $error = "Choose at least one seat.";
    } elseif (array_diff($seats, $valid)) {
        $error = "There is an invalid seat.";
    } elseif (array_intersect($seats, takenSeats($conn, $title, $showtime, $showDate))) {
        $error = "Some seats were already booked by someone else. Please choose again.";
    } else {
        $seatStr = implode(',', $seats);
        $quantity = count($seats);
        $total = $quantity * $PRICE;
        // Pending until payment is made
        $ins = mysqli_prepare($conn, "INSERT INTO bookkings (name, email, movie, showtime, seat, quantity, total_price, status, show_date) VALUES (?,?,?,?,?,?,?, 'Pending', ?)");
        mysqli_stmt_bind_param($ins, "sssssids", $name, $email, $title, $showtime, $seatStr, $quantity, $total, $showDate);
        if (mysqli_stmt_execute($ins)) {
            $id = mysqli_insert_id($conn);
            header("Location: pay.php?id=" . $id);
            exit;
        } else {
            $error = "The booking could not be saved. Please try again.";
        }
    }
}

// Data for the calendar
$booked = bookedCounts($conn, $title, $today, $last);
$passedToday = [];
foreach ($SHOWTIMES as $t) if (showtimePassed($today, $t, $tz)) $passedToday[] = $t;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book <?= e($title) ?></title>
<link rel="stylesheet" href="theme.css">
<script src="theme.js"></script>
<style>
    * { box-sizing:border-box; }
    body { font-family:"Segoe UI",Arial,sans-serif; background:var(--bg); color:var(--text); margin:0; line-height:1.4; }
    a:focus-visible, button:focus-visible, select:focus-visible { outline:2px solid var(--accent); outline-offset:3px; }
    .topbar { padding:14px 28px; background:var(--surface); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; }
    .topbar a { color:var(--link); font-weight:600; text-decoration:none; }
    .topbar a:hover { text-decoration:underline; }
    main { max-width:1000px; margin:30px auto; padding:0 20px; display:flex; gap:36px; flex-wrap:wrap; }
    .info { width:260px; }
    .info img { width:100%; border-radius:10px; border:1px solid var(--border); display:block; box-shadow:var(--shadow); }
    .info h1 { font-size:26px; font-weight:600; margin:14px 0 6px; }
    .pg { background:#3a73c4; color:#fff; font-size:13px; font-weight:700; padding:1px 7px; border-radius:4px; vertical-align:middle; }
    .pg.r-r, .pg.r-r18 { background:#b34a4a; }
    .pg.r-g { background:#3f8f6a; }
    .price { font-size:24px; color:var(--accent-text); font-weight:700; margin-top:10px; }
    .price small { font-size:14px; color:var(--muted); font-weight:400; }
    .book { flex:1; min-width:320px; }
    label { font-weight:600; display:block; margin:0 0 6px; }
    select { padding:10px; font-size:16px; width:100%; margin-bottom:18px; background:var(--surface); color:var(--text); border:1px solid var(--border); border-radius:6px; }

    /* ===== CALENDAR ===== */
    .cal { max-width:380px; background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px; margin-bottom:18px; }
    .cal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
    .cal-head strong { font-size:17px; }
    .cal-nav { width:34px; height:34px; border:1px solid var(--border); background:var(--bg); color:var(--text); border-radius:6px; cursor:pointer; font-size:16px; }
    .cal-nav:disabled { opacity:.35; cursor:not-allowed; }
    .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; text-align:center; }
    .cal-dow { font-size:12px; color:var(--muted); font-weight:700; padding:4px 0; }
    .cal-day { aspect-ratio:1; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--text); font:inherit; font-size:15px; font-weight:600; cursor:pointer; padding:0; display:flex; flex-direction:column; align-items:center; justify-content:center; line-height:1.1; }
    .cal-day small { font-size:9px; font-weight:700; letter-spacing:.3px; }
    .cal-day:hover:not(:disabled) { border-color:var(--accent); }
    .cal-day.sel { background:var(--accent); color:#1a1405; border-color:var(--accent); }
    .cal-day:disabled { cursor:not-allowed; opacity:.4; }
    .cal-day.full { text-decoration:line-through; color:var(--err-fg); }
    .cal-day.full small { text-decoration:none; display:block; }
    .cal-blank { visibility:hidden; }
    .cal-picked { margin-top:10px; font-size:14px; color:var(--muted); }
    .cal-picked b { color:var(--accent-text); }

    .screen { background:var(--screen); color:#fff; text-align:center; padding:6px; border-radius:4px 4px 40px 40px; margin:10px 0 20px; letter-spacing:4px; box-shadow:0 8px 24px rgba(79,106,143,.35); }
    .seats { display:grid; grid-template-columns:repeat(<?= $COLS ?>,1fr); gap:10px; max-width:520px; margin:0 auto; }
    .seat { --c:var(--seat); position:relative; aspect-ratio:1; border:0; background:none; padding:0; cursor:pointer; }
    .seat .back { position:absolute; top:0; left:12%; right:12%; height:58%; background:var(--c); border-radius:10px 10px 3px 3px; }
    .seat .cush { position:absolute; bottom:0; left:0; right:0; height:46%; background:var(--c); border-radius:4px 4px 9px 9px; filter:brightness(.88); color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; }
    .seat:hover { --c:var(--seat-hover); }
    .seat.sel { --c:var(--accent); }
    .seat.sel .cush { color:#1a1405; filter:none; }
    .seat.taken { --c:var(--seat-taken); cursor:not-allowed; opacity:.6; }
    .legend { display:flex; gap:18px; justify-content:center; margin:18px 0; font-size:14px; color:var(--muted); flex-wrap:wrap; }
    .legend i { display:inline-block; width:14px; height:14px; border-radius:3px; vertical-align:-2px; margin-right:5px; }
    .legend .l-av { background:var(--seat); } .legend .l-sel { background:var(--accent); } .legend .l-tk { background:var(--seat-taken); }
    .summary { margin:16px 0; font-size:18px; line-height:1.7; }
    .summary b { color:var(--accent-text); }
    button.pay { background:var(--accent); color:#1a1405; border:0; padding:12px 28px; font-size:18px; font-weight:700; border-radius:6px; cursor:pointer; }
    button.pay:hover { filter:brightness(1.08); }
    .msg { padding:12px; border-radius:6px; margin-bottom:16px; }
    .err { background:var(--err-bg); color:var(--err-fg); border:1px solid var(--err-bd); }
</style>
</head>
<body>
<header class="topbar">
    <strong>Movie Ticket Booking</strong>
    <span><button id="themeBtn" class="theme-btn" type="button" onclick="toggleTheme()"></button><a href="movies.php">&larr; Back to movies</a></span>
</header>

<main>
    <aside class="info">
        <?php if ($posterPath): ?><img src="<?= e($posterPath) ?>" alt="<?= e($title) ?> poster"><?php endif; ?>
        <h1><?= e($title) ?> <span class="pg <?= e($ratingClass) ?>"><?= e($rating) ?></span></h1>
        <div class="price">₱<?= number_format($PRICE, 2) ?> <small>/ ticket</small></div>
    </aside>

    <section class="book">
        <?php if ($error): ?><div class="msg err"><?= e($error) ?></div><?php endif; ?>

        <form method="post" id="bookForm">
            <input type="hidden" name="movie" value="<?= e($title) ?>">
            <input type="hidden" name="seats" id="seatsInput">
            <input type="hidden" name="show_date" id="showDateInput" value="<?= e($_POST['show_date'] ?? '') ?>">

            <label>Choose a date</label>
            <div class="cal" id="cal">
                <div class="cal-head">
                    <button type="button" class="cal-nav" id="calPrev" aria-label="Previous month">&#10094;</button>
                    <strong id="calTitle"></strong>
                    <button type="button" class="cal-nav" id="calNext" aria-label="Next month">&#10095;</button>
                </div>
                <div class="cal-grid" id="calGrid"></div>
                <div class="cal-picked">Date: <b id="calPicked">-</b></div>
            </div>

            <label for="showtime">Showtime</label>
            <select name="showtime" id="showtime" required>
                <option value="">-- Select --</option>
                <?php foreach ($SHOWTIMES as $t): ?>
                    <option value="<?= e($t) ?>" data-label="<?= e($t) ?>" <?= (($_GET['showtime'] ?? ($_POST['showtime'] ?? '')) === $t) ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Choose a seat</label>
            <div class="screen">SCREEN</div>
            <div class="seats" id="seatGrid">
                <?php foreach ($ROWS as $rw) for ($c = 1; $c <= $COLS; $c++): ?>
                    <button type="button" class="seat" data-seat="<?= $rw . $c ?>" title="<?= $rw . $c ?>">
                        <span class="back"></span><span class="cush"><?= $rw . $c ?></span>
                    </button>
                <?php endfor; ?>
            </div>
            <div class="legend">
                <span><i class="l-av"></i>Available Seat</span>
                <span><i class="l-sel"></i>Selected Seat</span>
                <span><i class="l-tk"></i>Taken Seat</span>
            </div>

            <div class="summary">
                Date: <b id="sDate">-</b><br>
                Seat: <b id="sSeats">-</b><br>
                Quantity: <b id="sQty">0</b> × ₱<?= number_format($PRICE, 2) ?><br>
                Total: <b>₱<span id="sTotal">0.00</span></b>
            </div>
            <button class="pay" type="submit">Confirm &amp; pay</button>
        </form>
    </section>
</main>

<script>
var PRICE      = <?= json_encode($PRICE) ?>;
var TITLE      = <?= json_encode($title) ?>;
var CAPACITY   = <?= (int)$CAPACITY ?>;
var TODAY      = <?= json_encode($today) ?>;
var LAST       = <?= json_encode($last) ?>;
var SHOWTIMES  = <?= json_encode($SHOWTIMES) ?>;
var BOOKED     = <?= json_encode($booked, JSON_FORCE_OBJECT) ?>;   // {"2026-11-25": {"7:00 PM": 12}}
var PASSED_TODAY = <?= json_encode($passedToday) ?>;                // showtimes that have already passed today

var selected = [];
var selectedDate = '';
var grid = document.getElementById('seatGrid');
var stSel = document.getElementById('showtime');
var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
var DOW = ['Su','Mo','Tu','We','Th','Fr','Sa'];

function pad(n) { return (n < 10 ? '0' : '') + n; }
function ymd(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
function parse(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }

// Is the showtime on this date fully booked?
function timeUnavailable(date, t) {
    if (date === TODAY && PASSED_TODAY.indexOf(t) > -1) return 'Passed';
    var n = (BOOKED[date] && BOOKED[date][t]) ? BOOKED[date][t] : 0;
    if (n >= CAPACITY) return 'Full';
    return '';
}
function dayFull(date) {
    return SHOWTIMES.every(function (t) { return timeUnavailable(date, t) !== ''; });
}

/* ===== Calendar ===== */
var start = parse(TODAY), end = parse(LAST);
var viewY = start.getFullYear(), viewM = start.getMonth();

function renderCal() {
    document.getElementById('calTitle').textContent = MONTHS[viewM] + ' ' + viewY;
    var g = document.getElementById('calGrid');
    g.innerHTML = '';
    DOW.forEach(function (d) { var c = document.createElement('div'); c.className = 'cal-dow'; c.textContent = d; g.appendChild(c); });

    var first = new Date(viewY, viewM, 1).getDay();
    var days  = new Date(viewY, viewM + 1, 0).getDate();
    for (var i = 0; i < first; i++) { var b = document.createElement('div'); b.className = 'cal-blank'; g.appendChild(b); }

    for (var d = 1; d <= days; d++) {
        var key = ymd(viewY, viewM, d);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cal-day';
        btn.textContent = d;
        if (key < TODAY || key > LAST) {
            btn.disabled = true;
        } else if (dayFull(key)) {
            btn.disabled = true;
            btn.classList.add('full');
            btn.innerHTML = d + '<small>FULL</small>';
        } else {
            (function (k) { btn.onclick = function () { pickDate(k); }; })(key);
        }
        if (key === selectedDate) btn.classList.add('sel');
        g.appendChild(btn);
    }
    document.getElementById('calPrev').disabled = (viewY === start.getFullYear() && viewM === start.getMonth());
    document.getElementById('calNext').disabled = (viewY === end.getFullYear() && viewM === end.getMonth());
}

document.getElementById('calPrev').onclick = function () { viewM--; if (viewM < 0) { viewM = 11; viewY--; } renderCal(); };
document.getElementById('calNext').onclick = function () { viewM++; if (viewM > 11) { viewM = 0; viewY++; } renderCal(); };

function prettyDate(s) {
    return parse(s).toLocaleDateString('en-PH', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
}

function pickDate(key) {
    selectedDate = key;
    document.getElementById('showDateInput').value = key;
    document.getElementById('calPicked').textContent = prettyDate(key);
    document.getElementById('sDate').textContent = prettyDate(key);
    // update the showtimes (disable if Full / Passed)
    Array.prototype.forEach.call(stSel.options, function (o) {
        if (!o.value) return;
        var why = timeUnavailable(key, o.value);
        o.disabled = why !== '';
        o.textContent = o.dataset.label + (why ? '  (' + why + ')' : '');
    });
    if (stSel.value && stSel.options[stSel.selectedIndex].disabled) stSel.value = '';
    renderCal();
    reloadSeats();
}

/* ===== Seats ===== */
function refresh() {
    document.getElementById('sSeats').textContent = selected.length ? selected.join(', ') : '-';
    document.getElementById('sQty').textContent = selected.length;
    document.getElementById('sTotal').textContent = (selected.length * PRICE).toFixed(2);
    document.getElementById('seatsInput').value = selected.join(',');
}

grid.addEventListener('click', function (ev) {
    var b = ev.target.closest('.seat');
    if (!b || b.classList.contains('taken')) return;
    var s = b.dataset.seat, i = selected.indexOf(s);
    if (i > -1) { selected.splice(i, 1); b.classList.remove('sel'); }
    else { selected.push(s); b.classList.add('sel'); }
    refresh();
});

function reloadSeats() {
    selected = []; refresh();
    document.querySelectorAll('.seat').forEach(function (b) { b.classList.remove('taken', 'sel'); });
    if (!stSel.value || !selectedDate) return;
    var url = 'book.php?movie=' + encodeURIComponent(TITLE) + '&taken=' + encodeURIComponent(stSel.value) + '&date=' + encodeURIComponent(selectedDate);
    fetch(url).then(function (r) { return r.json(); }).then(function (list) {
        list.forEach(function (s) {
            var el = grid.querySelector('[data-seat="' + s + '"]');
            if (el) el.classList.add('taken');
        });
    });
}

stSel.addEventListener('change', reloadSeats);

document.getElementById('bookForm').addEventListener('submit', function (ev) {
    if (!selectedDate) { ev.preventDefault(); alert('Please choose a date on the calendar first.'); return; }
    if (!selected.length) { ev.preventDefault(); alert('Pick a seat.'); }
});

/* ===== Start: immediately select the first date with availability ===== */
(function () {
    var pre = stSel.value;      // coming from the movie page (if a showtime was selected)
    var dateIn = document.getElementById('showDateInput').value;
    var cand = '', fallback = '';
    if (dateIn && dateIn >= TODAY && dateIn <= LAST && !dayFull(dateIn)) cand = dateIn;
    for (var t = start.getTime(); !cand && t <= end.getTime(); t += 86400000) {
        var dt = new Date(t), key = ymd(dt.getFullYear(), dt.getMonth(), dt.getDate());
        if (dayFull(key)) continue;
        if (!fallback) fallback = key;
        if (!pre || timeUnavailable(key, pre) === '') cand = key;
    }
    cand = cand || fallback;
    renderCal();
    if (cand) {
        var cd = parse(cand); viewY = cd.getFullYear(); viewM = cd.getMonth();
        pickDate(cand);
    }
})();
</script>
</body>
</html>
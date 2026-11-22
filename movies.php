<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$loggedIn  = !empty($_SESSION['user_id']);
$SHOWTIMES = ['10:00 AM', '1:00 PM', '4:00 PM', '7:00 PM', '10:00 PM']; // must match book.php

/* ---- Cinema location (set by the admin in admin.php > Location) ---- */
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v TEXT)");
$loc = [];
$lr = @mysqli_query($conn, "SELECT k,v FROM settings");
if ($lr) while ($r = mysqli_fetch_assoc($lr)) $loc[$r['k']] = $r['v'];
$CINEMA = [
    'name'    => $loc['loc_name']    ?? '',
    'address' => $loc['loc_address'] ?? '',
    'lat'     => $loc['loc_lat']     ?? '',
    'lng'     => $loc['loc_lng']     ?? '',
];
$dest = ($CINEMA['lat'] !== '' && $CINEMA['lng'] !== '') ? $CINEMA['lat'] . ',' . $CINEMA['lng'] : $CINEMA['address'];

function posterPath($file) {
    if (empty($file)) return '';
    if (file_exists(__DIR__ . "/uploads/" . $file))    return "uploads/" . $file;
    if (file_exists(__DIR__ . "/images/" . $file))     return "images/" . $file;
    return '';
}

/* =========================================================
   This single file handles both:
   movies.php          -> list of all movies (with highlight carousel)
   movies.php?id=3     -> details of one movie
   ========================================================= */
$id = (int)($_GET['id'] ?? 0);
$detail = null;
$slides = [];

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM movies WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $m = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$m) { http_response_code(404); die("Movie not found. <a href='movies.php'>Back to movies</a>"); }

    $rating = $m['rating'] ?? 'PG';
    $genres = array_filter(array_map('trim', explode(',', (string)($m['genre'] ?? ''))));
    $story  = trim((string)($m['synopsis'] ?? ''));
    // The text written by the admin is displayed. If it is YYYY-MM-DD (from the old date field), it becomes "14 October 2026".
    $release = trim((string)($m['release_date'] ?? ''));
    if ($release === '0000-00-00') $release = '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $release)) {
        try { $release = (new DateTime($release))->format('j F Y'); } catch (Exception $ex) {}
    }
    $ratingLabels = [
        'G'   => 'General Patronage',
        'PG'  => 'Parental Guidance Recommended',
        'R'   => 'Restricted',
        'R13' => 'Restricted: 13 years old and above',
        'R16' => 'Restricted: 16 years old and above',
        'R18' => 'Restricted: 18 years old and above',
        'X'   => 'Not for public exhibition',
    ];
    $ratingKey = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rating));
    $detail = [
        'title'   => $m['title'],
        'rating'  => $rating,
        'rclass'  => 'r-' . strtolower($ratingKey),
        'rlabel'  => $ratingLabels[$ratingKey] ?? '',
        'isNow'   => (($m['status'] ?? 'now') === 'now'),
        'cast'    => trim((string)($m['cast_members'] ?? '')),
        'runtime' => trim((string)($m['runtime'] ?? '')),
        'genres'  => $genres,
        'paras'   => $story !== '' ? preg_split('/\R{2,}/', $story) : [],
        'release' => $release,
        'price'   => (isset($m['price']) && $m['price'] > 0) ? (float)$m['price'] : 250, // same as book.php
        'poster'  => posterPath($m['poster'] ?? ''),
    ];
    $pageTitle = $m['title'];
} else {
    /* ---- Get the movies from the database (added by the admin) ---- */
    $movies = [];
    $res = @mysqli_query($conn, "SELECT * FROM movies ORDER BY id ASC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $movies[] = [
                'id'     => (int)$row['id'],
                'title'  => $row['title'],
                'rating' => $row['rating'] ?? 'PG',
                'path'   => posterPath($row['poster'] ?? ''),
                'status' => $row['status'] ?? 'now',
                'genre'  => trim((string)($row['genre'] ?? '')),
                'release'=> trim((string)($row['release_date'] ?? '')),
            ];
        }
    }
    $now  = array_filter($movies, fn($m) => $m['status'] === 'now');
    $soon = array_filter($movies, fn($m) => $m['status'] === 'soon');
    $pageTitle = 'Now showing';

    /* ---- HIGHLIGHTS: Top 5 (most bookings) + Coming soon ---- */
    $byId = [];
    foreach ($movies as $mv) $byId[$mv['id']] = $mv;

    $topIds = []; $counts = [];
    $tr = @mysqli_query($conn, "SELECT m.id, COUNT(b.id) c
        FROM movies m JOIN bookkings b ON b.movie = m.title
        WHERE m.status = 'now' AND b.status IN ('Confirmed','Finished')
        GROUP BY m.id ORDER BY c DESC, m.id DESC LIMIT 5");
    if ($tr) while ($r = mysqli_fetch_assoc($tr)) { $topIds[] = (int)$r['id']; $counts[(int)$r['id']] = (int)$r['c']; }
    // if fewer than 5, add other now-showing movies (newest first)
    if (count($topIds) < 5) {
        foreach (array_reverse($now, true) as $mv) {
            if (count($topIds) >= 5) break;
            if (!in_array($mv['id'], $topIds, true)) $topIds[] = $mv['id'];
        }
    }
    $rank = 1;
    foreach ($topIds as $tid) {
        if (!isset($byId[$tid])) continue;
        $mv = $byId[$tid];
        $mv['tag']  = 'TOP ' . $rank . ' &bull; NOW SHOWING';
        $mv['kind'] = 'top';
        $mv['note'] = !empty($counts[$tid]) ? $counts[$tid] . ' booking' . ($counts[$tid] > 1 ? 's' : '') : '';
        $slides[] = $mv; $rank++;
    }
    $n = 0;
    foreach (array_reverse($soon, true) as $mv) {
        if ($n >= 5) break;
        $mv['tag']  = 'COMING SOON';
        $mv['kind'] = 'soon';
        $mv['note'] = '';
        $slides[] = $mv; $n++;
    }
}

function renderMovies($list) {
    if (!$list) { echo '<p class="empty">No movies yet.</p>'; return; }
    echo '<div class="grid">';
    foreach ($list as $m) {
        // Every movie is clickable -> details (no sign-in needed)
        $link = 'movies.php?id=' . (int)$m['id'];
        $ratingClass = 'r-' . preg_replace('/[^a-z0-9]/', '', strtolower($m['rating']));
        echo '<article class="movie"><div class="poster"><a href="' . e($link) . '">';
        if ($m['path'] !== '') {
            echo '<img src="' . e($m['path']) . '" alt="' . e($m['title']) . ' poster">';
        } else {
            echo '<div class="noimg">' . e($m['title']) . '</div>';
        }
        echo '</a>';
        echo '<button class="bookmark" type="button" aria-label="Bookmark" onclick="this.classList.toggle(\'on\')"><svg viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4.5L6 21z"/></svg></button>';
        echo '</div>';
        echo '<h3><a href="' . e($link) . '">' . e($m['title']) . '</a> <span class="pg ' . e($ratingClass) . '">' . e($m['rating']) . '</span></h3>';
        echo '</article>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="theme.css">
<script src="theme.js"></script>
<style>
    * { box-sizing: border-box; }
    body { margin:0; font-family:"Segoe UI",Arial,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; flex-direction:column; line-height:1.4; }
    a { color:var(--link); }
    a:focus-visible, button:focus-visible { outline:2px solid var(--accent); outline-offset:3px; }

    .topbar { display:flex; justify-content:space-between; align-items:center; padding:14px 28px; background:var(--surface); border-bottom:1px solid var(--border); }
    .topbar strong a { color:var(--text); text-decoration:none; margin:0; font-weight:700; }
    .topbar span a { color:var(--link); font-weight:600; text-decoration:none; margin-left:18px; }
    .topbar span a:hover { text-decoration:underline; }

    main { flex:1; max-width:1280px; width:100%; margin:0 auto; padding:36px 28px 60px; }
    main.narrow { max-width:1040px; padding-top:26px; }

    /* ===== HIGHLIGHT CAROUSEL ===== */
    .hl { position:relative; margin:0 0 40px; }
    .hl-view { overflow:hidden; border-radius:14px; }
    .hl-track { display:flex; transition:transform .55s cubic-bezier(.22,.7,.2,1); will-change:transform; }
    .hl-slide { position:relative; flex:0 0 78%; margin:0 1%; height:380px; border-radius:14px; overflow:hidden; display:flex; align-items:center; gap:34px; padding:0 48px;
        text-decoration:none; color:#fff; background:#0b1020; opacity:.4; transform:scale(.96); transition:opacity .45s, transform .45s; }
    .hl-slide.on { opacity:1; transform:none; }
    .hl-bg { position:absolute; inset:-20px; background-size:cover; background-position:center; filter:blur(22px) brightness(.55) saturate(1.2); }
    .hl-shade { position:absolute; inset:0; background:linear-gradient(90deg,rgba(0,0,0,.65) 0%,rgba(0,0,0,.25) 55%,rgba(0,0,0,.55) 100%); }
    .hl-poster { position:relative; z-index:1; flex:0 0 auto; height:300px; aspect-ratio:2/3; border-radius:10px; overflow:hidden; box-shadow:0 14px 34px rgba(0,0,0,.55); background:#1a2238; }
    .hl-poster img { width:100%; height:100%; object-fit:cover; display:block; }
    .hl-poster .noimg { background:#1a2238; color:#94a3b8; font-size:16px; }
    .hl-text { position:relative; z-index:1; min-width:0; }
    .hl-tag { display:inline-block; font-size:13px; font-weight:800; letter-spacing:1.5px; padding:4px 12px; border-radius:4px; background:#1d4ed8; color:#fff; margin-bottom:14px; }
    .hl-slide.soon .hl-tag { background:#f59e0b; color:#1a1405; }
    .hl-title { font-size:clamp(26px,3.6vw,46px); font-weight:800; line-height:1.1; margin:0 0 12px; text-transform:uppercase; text-shadow:0 2px 14px rgba(0,0,0,.6); }
    .hl-meta { font-size:16px; color:#e2e8f0; margin:0 0 6px; }
    .hl-meta .pg { margin-right:8px; }
    .hl-cta { display:inline-block; margin-top:18px; background:#fff; color:#0b1020; font-weight:800; padding:10px 24px; border-radius:999px; }

    .hl-arrow { position:absolute; top:50%; transform:translateY(-50%); z-index:3; width:42px; height:42px; border-radius:50%; border:0; cursor:pointer; background:rgba(0,0,0,.55); color:#fff; font-size:22px; line-height:1; display:flex; align-items:center; justify-content:center; }
    .hl-arrow:hover { background:rgba(0,0,0,.8); }
    .hl-prev { left:6px; } .hl-next { right:6px; }

    .hl-ctrl { position:absolute; left:50%; bottom:14px; transform:translateX(-50%); z-index:3; display:flex; align-items:center; gap:12px; background:rgba(15,23,42,.72); padding:8px 18px; border-radius:999px; backdrop-filter:blur(4px); }
    .hl-pause { background:none; border:0; color:#fff; cursor:pointer; font-size:15px; padding:0; width:18px; }
    .hl-dot { width:10px; height:10px; border-radius:50%; border:0; padding:0; background:#64748b; cursor:pointer; transition:width .3s, background .3s; }
    .hl-dot.on { width:34px; border-radius:999px; background:#fff; }

    @media (max-width:760px) {
        .hl-slide { flex-basis:92%; height:auto; min-height:300px; padding:26px 22px 58px; gap:16px; }
        .hl-poster { height:170px; }
        .hl-arrow { display:none; }
        .hl-title { font-size:24px; }
    }

    /* ===== LIST ===== */
    .tabs { display:flex; border-bottom:2px solid var(--border); width:max-content; max-width:100%; margin:0 auto 36px; }
    .tab { background:none; border:0; font:inherit; font-size:28px; color:var(--muted); padding:6px 22px 12px; cursor:pointer; margin-bottom:-2px; border-bottom:3px solid transparent; transition:color .15s; }
    .tab:hover { color:var(--text); }
    .tab[aria-selected="true"] { color:var(--text); border-bottom-color:var(--accent); }

    .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:34px 22px; }
    .poster { position:relative; aspect-ratio:2/3; border-radius:10px; overflow:hidden; border:1px solid var(--border); background:var(--surface); box-shadow:var(--shadow); }
    .poster a { display:block; height:100%; }
    .poster img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .25s; }
    .poster a:hover img { transform:scale(1.03); }
    .noimg { width:100%; height:100%; display:flex; align-items:center; justify-content:center; text-align:center; padding:16px; background:var(--surface); color:var(--muted); font-size:20px; font-weight:600; }

    .bookmark { position:absolute; top:10px; right:10px; width:34px; height:34px; border:0; border-radius:6px; background:var(--chip); cursor:pointer; padding:0; display:flex; align-items:center; justify-content:center; }
    .bookmark svg { width:20px; height:20px; fill:none; stroke:var(--text); stroke-width:2; }
    .bookmark.on svg { fill:var(--accent-text); stroke:var(--accent-text); }

    .movie h3 { font-size:20px; font-weight:500; margin:14px 0 8px; color:var(--text); }
    .movie h3 a { color:inherit; text-decoration:none; }
    .movie h3 a:hover { color:var(--accent-text); }

    /* Rating badges */
    .pg { background:#3a73c4; color:#fff; font-size:13px; font-weight:700; padding:1px 7px; border-radius:4px; vertical-align:middle; }
    .pg.r-r, .pg.r-r16, .pg.r-r18 { background:#b34a4a; }
    .pg.r-g { background:#3f8f6a; }

    .empty { text-align:center; font-size:20px; color:var(--muted); }
    .hidden { display:none; }

    /* ===== DETAILS ===== */
    .back { display:inline-block; margin-bottom:18px; text-decoration:none; font-weight:600; }
    .back:hover { text-decoration:underline; }
    .detail { display:flex; gap:32px; flex-wrap:wrap; align-items:flex-start; line-height:1.5; }
    .dposter { width:190px; flex:0 0 190px; aspect-ratio:2/3; border-radius:10px; overflow:hidden; border:1px solid var(--border); background:var(--surface); box-shadow:var(--shadow); }
    .dposter img { width:100%; height:100%; object-fit:cover; display:block; }
    .dposter .noimg { font-size:16px; }
    .dmain { flex:1 1 360px; min-width:260px; }
    .dmain h1 { margin:0 0 16px; font-size:30px; font-weight:600; }
    .dmain h4, .dside h4 { margin:0 0 4px; font-size:16px; font-weight:600; }
    .dmain p { margin:0 0 14px; font-size:17px; line-height:1.65; }
    .block { margin-bottom:18px; }
    .dside { flex:0 0 220px; font-size:17px; }
    .dside .block p { margin:0; }
    .chip { display:inline-block; background:var(--border); color:var(--text); font-weight:600; font-size:14px; padding:2px 10px; border-radius:6px; margin:0 6px 6px 0; }

    h2.sh { font-size:30px; font-weight:600; margin:44px 0 14px; }
    .notice { background:var(--wait-bg); color:var(--wait-fg); border:1px solid var(--wait-bd); border-radius:8px; padding:12px 16px; margin-bottom:16px; }
    .notice a { color:inherit; font-weight:700; }
    .times { display:flex; gap:10px; flex-wrap:wrap; }
    .time { display:inline-block; padding:10px 20px; border:2px solid var(--border); background:var(--surface); color:var(--text); border-radius:8px; font-weight:700; text-decoration:none; }
    .time:hover { border-color:var(--accent); color:var(--accent-text); }
    .muted { color:var(--muted); }
    .price { color:var(--accent-text); font-weight:700; font-size:24px; }
    .price small { font-size:14px; color:var(--muted); font-weight:400; }

    /* ===== LOCATION / MAP ===== */
    .loc { display:flex; gap:24px; flex-wrap:wrap; align-items:stretch; }
    .loc-info { flex:0 0 280px; font-size:17px; }
    .loc-info h4 { margin:0 0 4px; font-size:16px; font-weight:600; }
    .loc-info p { margin:0 0 14px; }
    .loc-info input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border); background:var(--surface); color:var(--text); font:inherit; font-size:15px; margin-bottom:10px; }
    .loc-btn { display:block; width:100%; text-align:center; background:var(--accent); color:#fff; font:inherit; font-size:15px; font-weight:700; padding:10px 14px; border-radius:8px; border:0; cursor:pointer; text-decoration:none; margin-bottom:10px; }
    .loc-btn.alt { background:var(--surface); color:var(--text); border:2px solid var(--border); }
    .loc-btn.alt:hover { border-color:var(--accent); }
    .loc-map { flex:1 1 360px; min-height:380px; border:1px solid var(--border); border-radius:10px; overflow:hidden; }
    .loc-map iframe { width:100%; height:100%; min-height:380px; border:0; display:block; }

    footer { background:var(--footer); color:var(--muted); padding:22px 28px; font-size:16px; border-top:1px solid var(--border); }
</style>
</head>
<body>

<header class="topbar">
    <strong><a href="movies.php">Movie Ticket Booking</a></strong>
    <span><button id="themeBtn" class="theme-btn" type="button" onclick="toggleTheme()"></button><?php if ($loggedIn) { include "bell.php"; } ?><?= $loggedIn ? 'Hi, ' . e($_SESSION['user_name']) . ' <a href="logout.php">Log out</a>' : '<a href="index.php">Sign in</a>' ?></span>
</header>

<?php if ($detail): ?>
<main class="narrow">
    <a class="back" href="movies.php">&larr; Back to movies</a>

    <div class="detail">
        <div class="dposter">
            <?php if ($detail['poster']): ?><img src="<?= e($detail['poster']) ?>" alt="<?= e($detail['title']) ?> poster"><?php else: ?><div class="noimg"><?= e($detail['title']) ?></div><?php endif; ?>
        </div>

        <div class="dmain">
            <h1><?= e($detail['title']) ?> <span class="pg <?= e($detail['rclass']) ?>"><?= e($detail['rating']) ?></span></h1>
            <?php if ($detail['cast'] !== ''): ?>
                <div class="block"><h4>Cast</h4><p><?= e($detail['cast']) ?></p></div>
            <?php endif; ?>
            <div class="block">
                <h4>Synopsis</h4>
                <?php if ($detail['paras']): foreach ($detail['paras'] as $p): ?><p><?= nl2br(e(trim($p))) ?></p><?php endforeach; else: ?>
                    <p class="muted">No synopsis yet for this movie.</p>
                <?php endif; ?>
            </div>
        </div>

        <aside class="dside">
            <div class="block"><h4>Ticket price</h4><p class="price">₱<?= number_format($detail['price'], 2) ?> <small>/ ticket</small></p></div>
            <?php if ($detail['runtime'] !== ''): ?><div class="block"><h4>Runtime</h4><p><?= e($detail['runtime']) ?></p></div><?php endif; ?>
            <?php if ($detail['release'] !== ''): ?><div class="block"><h4>Release date</h4><p><?= e($detail['release']) ?></p></div><?php endif; ?>
            <?php if ($detail['genres']): ?><div class="block"><?php foreach ($detail['genres'] as $g) echo '<span class="chip">' . e($g) . '</span>'; ?></div><?php endif; ?>
            <?php if ($detail['rlabel'] !== ''): ?><div class="block"><p><?= e($detail['rlabel']) ?></p></div><?php endif; ?>
        </aside>
    </div>

    <h2 class="sh">Showtimes</h2>
    <?php if (!$detail['isNow']): ?>
        <p class="muted">Coming soon. Showtimes will be available once the movie starts showing.</p>
    <?php else: ?>
        <?php if (!$loggedIn): ?>
            <div class="notice">Please <a href="index.php">sign in</a> first to book a ticket.</div>
        <?php endif; ?>
        <div class="times">
            <?php foreach ($SHOWTIMES as $t): ?>
                <a class="time" href="book.php?movie=<?= urlencode($detail['title']) ?>&amp;showtime=<?= urlencode($t) ?>"><?= e($t) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="sh">Location</h2>
    <?php if ($dest !== ''): ?>
    <div class="loc">
        <div class="loc-info">
            <h4>From</h4>
            <input id="fromLoc" type="text" placeholder="Type your location (e.g. Barangay, City)">
            <button class="loc-btn alt" type="button" id="useGps">&#128205; Use my current location</button>

            <h4>To</h4>
            <input id="toLoc" type="text" value="<?= e(trim($CINEMA['name'] . ($CINEMA['address'] !== '' ? ' - ' . $CINEMA['address'] : ''))) ?>" readonly>

            <button class="loc-btn" type="button" id="goRoute">Show route</button>
            <a class="loc-btn alt" id="gmapsLink" target="_blank" rel="noopener"
               href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($dest) ?>">Open in Google Maps</a>
            <p class="muted" id="locNote" style="font-size:14px"></p>
        </div>
        <div class="loc-map">
            <iframe id="locFrame" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                src="https://maps.google.com/maps?q=<?= urlencode($dest) ?>&z=16&output=embed"
                title="Cinema location map"></iframe>
        </div>
    </div>
    <script>
    (function () {
        var DEST = <?= json_encode($dest) ?>;
        var from = document.getElementById('fromLoc'), frame = document.getElementById('locFrame');
        var link = document.getElementById('gmapsLink'), note = document.getElementById('locNote');
        function route(origin) {
            var o = encodeURIComponent(origin), d = encodeURIComponent(DEST);
            frame.src = 'https://maps.google.com/maps?saddr=' + o + '&daddr=' + d + '&output=embed';
            link.href = 'https://www.google.com/maps/dir/?api=1&origin=' + o + '&destination=' + d + '&travelmode=driving';
        }
        function show() {
            var v = from.value.trim();
            if (!v) { note.textContent = 'Please type your location in "From" first.'; return; }
            note.textContent = ''; route(v);
        }
        document.getElementById('goRoute').onclick = show;
        from.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); show(); } });
        document.getElementById('useGps').onclick = function () {
            if (!navigator.geolocation) { note.textContent = 'Your browser does not support location.'; return; }
            note.textContent = 'Getting your location...';
            navigator.geolocation.getCurrentPosition(function (p) {
                var c = p.coords.latitude.toFixed(6) + ',' + p.coords.longitude.toFixed(6);
                from.value = c; note.textContent = 'Using your current location.'; route(c);
            }, function () {
                note.textContent = 'Could not get your location. Allow location access or type it instead.';
            }, { enableHighAccuracy: true, timeout: 10000 });
        };
    })();
    </script>
    <?php else: ?>
        <p class="muted">The cinema location has not been set yet.</p>
    <?php endif; ?>
</main>

<?php else: ?>
<main>

    <?php if ($slides): ?>
    <!-- ===== HIGHLIGHTS: Top 5 + Coming soon ===== -->
    <section class="hl" id="hl" aria-label="Highlights">
        <div class="hl-view" id="hlView">
            <div class="hl-track" id="hlTrack">
                <?php foreach ($slides as $i => $s):
                    $rc = 'r-' . preg_replace('/[^a-z0-9]/', '', strtolower($s['rating']));
                    $bits = array_filter([$s['genre'], $s['kind']==='soon' && $s['release']!=='' ? 'Release: '.$s['release'] : '', $s['note']]);
                ?>
                <a class="hl-slide <?= $s['kind'] === 'soon' ? 'soon' : '' ?> <?= $i === 0 ? 'on' : '' ?>" href="movies.php?id=<?= (int)$s['id'] ?>">
                    <?php if ($s['path'] !== ''): ?><div class="hl-bg" style="background-image:url('<?= e($s['path']) ?>')"></div><?php endif; ?>
                    <div class="hl-shade"></div>
                    <div class="hl-poster">
                        <?php if ($s['path'] !== ''): ?><img src="<?= e($s['path']) ?>" alt="<?= e($s['title']) ?> poster"><?php else: ?><div class="noimg"><?= e($s['title']) ?></div><?php endif; ?>
                    </div>
                    <div class="hl-text">
                        <span class="hl-tag"><?= $s['tag'] ?></span>
                        <h2 class="hl-title"><?= e($s['title']) ?></h2>
                        <p class="hl-meta"><span class="pg <?= e($rc) ?>"><?= e($s['rating']) ?></span><?= e(implode('  ·  ', $bits)) ?></p>
                        <span class="hl-cta"><?= $s['kind'] === 'soon' ? 'View details' : 'Book now' ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <button class="hl-arrow hl-prev" type="button" id="hlPrev" aria-label="Previous">&#10094;</button>
        <button class="hl-arrow hl-next" type="button" id="hlNext" aria-label="Next">&#10095;</button>
        <div class="hl-ctrl">
            <button class="hl-pause" type="button" id="hlPause" aria-label="Pause">&#10074;&#10074;</button>
            <?php foreach ($slides as $i => $s): ?><button class="hl-dot <?= $i === 0 ? 'on' : '' ?>" type="button" data-i="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button><?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab" id="tab-now"  aria-selected="true"  onclick="switchTab('now')">Now showing</button>
        <button class="tab" id="tab-soon" aria-selected="false" onclick="switchTab('soon')">Coming soon</button>
    </div>
    <section id="panel-now"><?php renderMovies($now); ?></section>
    <section id="panel-soon" class="hidden"><?php renderMovies($soon); ?></section>
</main>
<script>
function switchTab(which) {
    ["now","soon"].forEach(function (t) {
        document.getElementById("panel-" + t).classList.toggle("hidden", t !== which);
        document.getElementById("tab-" + t).setAttribute("aria-selected", t === which);
    });
}

/* ===== Highlight carousel ===== */
(function () {
    var view = document.getElementById('hlView');
    if (!view) return;
    var track  = document.getElementById('hlTrack');
    var slides = Array.prototype.slice.call(track.children);
    var dots   = Array.prototype.slice.call(document.querySelectorAll('.hl-dot'));
    var pauseB = document.getElementById('hlPause');
    var cur = 0, timer = null, playing = true;

    function go(i) {
        cur = (i + slides.length) % slides.length;
        var s = slides[cur];
        var x = (view.clientWidth - s.offsetWidth) / 2 - s.offsetLeft;
        track.style.transform = 'translateX(' + x + 'px)';
        slides.forEach(function (el, k) { el.classList.toggle('on', k === cur); });
        dots.forEach(function (d, k) { d.classList.toggle('on', k === cur); });
    }
    function play()  { stop(); if (playing && slides.length > 1) timer = setInterval(function () { go(cur + 1); }, 5000); }
    function stop()  { if (timer) { clearInterval(timer); timer = null; } }

    document.getElementById('hlPrev').onclick = function () { go(cur - 1); play(); };
    document.getElementById('hlNext').onclick = function () { go(cur + 1); play(); };
    dots.forEach(function (d) { d.onclick = function () { go(+d.dataset.i); play(); }; });
    pauseB.onclick = function () {
        playing = !playing;
        pauseB.innerHTML = playing ? '&#10074;&#10074;' : '&#9654;';
        pauseB.setAttribute('aria-label', playing ? 'Pause' : 'Play');
        play();
    };
    view.addEventListener('mouseenter', stop);
    view.addEventListener('mouseleave', play);

    // swipe on phone
    var sx = null;
    view.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, {passive:true});
    view.addEventListener('touchend', function (e) {
        if (sx === null) return;
        var dx = e.changedTouches[0].clientX - sx; sx = null;
        if (Math.abs(dx) > 40) { go(cur + (dx < 0 ? 1 : -1)); play(); }
    });

    window.addEventListener('resize', function () { go(cur); });
    window.addEventListener('load', function () { go(cur); });
    go(0); play();
})();
</script>
<?php endif; ?>

<footer>Movie Ticket Booking. </footer>
</body>
</html>
<?php
/* ===== CONFIG: adjust if the names in your database are different ===== */
$DB = ['host'=>'localhost','user'=>'root','pass'=>'','name'=>'movie_booking'];
$TB = 'bookkings';          // name of your bookings table (has a typo in phpMyAdmin)
$C  = [
  'id'     => 'id',
  'movie'  => 'movie',
  'seats'  => 'seat',
  'price'  => 'total_price',
  'date'   => 'created_at',
  'status' => 'status',
];
/* ================================================================= */
session_name('cinema_admin');   // separate session for admin (not affected by user login/logout)
session_set_cookie_params(['path'=>'/','httponly'=>true,'samesite'=>'Lax']);
session_start();
mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($DB['host'],$DB['user'],$DB['pass'],$DB['name']);
if ($db->connect_error) die('DB error: '.$db->connect_error);
function q($sql){ global $db; $r=$db->query($sql); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; }
function one($sql){ $r=q($sql); return $r ? array_values($r[0])[0] : 0; }
function e($s){ return htmlspecialchars((string)$s); }

// poster: search in different folders, use wherever the file actually is
function pu($p){
  $p = basename(str_replace('\\','/',trim((string)$p)));
  if ($p==='') return '';
  // admin.php itself serves the image (see ?img= below)
  return '?img='.rawurlencode($p);
}

/* ---- Login / Logout ---- */
$err='';
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['act'])) {
  $st=$db->prepare("SELECT id,name,password,role FROM users WHERE email=? LIMIT 1");
  $st->bind_param('s',$_POST['email']); $st->execute();
  $u=$st->get_result()->fetch_assoc();
  if ($u && $_POST['password']===$u['password'] && $u['role']==='admin') {
    $_SESSION['admin']=$u['name']; header('Location: admin.php'); exit;
  }
  $err='Incorrect email/password, or you are not an admin.';
}
if (empty($_SESSION['admin'])) { ?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Login</title><style>
body{background:#0a0f1c;color:#e5e7eb;font-family:Segoe UI,sans-serif;display:grid;place-items:center;height:100vh;margin:0}
form{background:#111827;padding:32px;border-radius:12px;width:300px;border:1px solid #1f2937}
h2{margin:0 0 16px}h2 span{color:#3b82f6}
input,button{width:100%;padding:10px;margin-bottom:12px;border-radius:8px;border:1px solid #1f2937;background:#0a0f1c;color:#fff;box-sizing:border-box}
button{background:#2563eb;border:0;cursor:pointer}.err{color:#f87171;font-size:13px}
</style></head><body><form method="post">
<h2>CINEMA TICKET <span>ADMIN</span></h2>
<?php if($err) echo "<p class='err'>$err</p>"; ?>
<input name="email" type="email" placeholder="Email" required>
<input name="password" type="password" placeholder="Password" required>
<button>Login</button></form></body></html>
<?php exit; }

/* ---- OMDb (auto-fill) ---- */
$OMDB_KEY = 'cf377f8f';
function http_get($url){
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,
    CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_SSL_VERIFYHOST=>0]); // local XAMPP only
  $b=curl_exec($ch); $c=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
  return ($b!==false && $c===200) ? $b : false;
}
function omdb($p){
  global $OMDB_KEY; $p['apikey']=$OMDB_KEY;
  $raw=http_get('http://www.omdbapi.com/?'.http_build_query($p));
  return $raw ? json_decode($raw,true) : null;
}
function jout($d,$code=200){
  http_response_code($code); header('Content-Type: application/json; charset=utf-8');
  echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}
function oclean($v){ return ($v===null||$v==='N/A') ? '' : trim($v); }
function oRating($r){ $m=['G'=>'G','PG'=>'PG','PG-13'=>'R13','R'=>'R16','NC-17'=>'R18']; return $m[$r]??'PG'; }
function oRuntime($v){
  $v=oclean($v);
  if(!preg_match('/^(\d+)\s*min/i',$v,$m)) return $v;
  $n=(int)$m[1]; return $n>=60 ? intdiv($n,60).'h '.($n%60).'m' : $n.'m';
}
function oRelease($v){
  $v=oclean($v); if($v==='') return '';
  try{ return (new DateTime($v))->format('j F Y'); }catch(Exception $e){ return $v; }
}
function oSavePoster($url,$imdb){
  if($url===''||!preg_match('#^https://[^/]*media-amazon\.com/#',$url)) return '';
  $img=http_get($url);
  if(!$img||!@getimagesizefromstring($img)) return '';
  $dir=__DIR__.'/uploads'; if(!is_dir($dir)) @mkdir($dir,0777,true);
  $f='omdb_'.$imdb.'.jpg';
  return file_put_contents($dir.'/'.$f,$img) ? $f : '';
}
if (isset($_GET['omdb'])) {
  $act=$_GET['omdb'];
  if ($act==='search') {
    $q=trim($_GET['q']??''); if($q==='') jout(['success'=>false,'error'=>'Enter a title'],400);
    $d=omdb(['s'=>$q,'type'=>'movie']);
    if($d===null) jout(['success'=>false,'error'=>'Unable to reach OMDb.'],502);
    if(($d['Response']??'')!=='True') jout(['success'=>false,'error'=>$d['Error']??'No results found'],404);
    $list=[];
    foreach($d['Search'] as $r) $list[]=['imdb'=>$r['imdbID'],'title'=>$r['Title'],'year'=>$r['Year'],'poster'=>oclean($r['Poster']??'')];
    jout(['success'=>true,'results'=>$list]);
  }
  if ($act==='get') {
    $imdb=$_GET['imdb']??'';
    if(!preg_match('/^tt\d+$/',$imdb)) jout(['success'=>false,'error'=>'Invalid ID'],400);
    $d=omdb(['i'=>$imdb,'plot'=>'full']);
    if(!$d||($d['Response']??'')!=='True') jout(['success'=>false,'error'=>'Unable to get the details'],404);
    jout(['success'=>true,'movie'=>[
      'imdb'=>$imdb,
      'title'=>oclean($d['Title']??''),
      'rating'=>oRating($d['Rated']??''),
      'runtime'=>oRuntime($d['Runtime']??''),
      'genre'=>oclean($d['Genre']??''),
      'release'=>oRelease($d['Released']??''),
      'cast'=>oclean($d['Actors']??''),
      'plot'=>oclean($d['Plot']??''),
      'poster'=>oSavePoster(oclean($d['Poster']??''),$imdb), // already downloaded to uploads/
    ]]);
  }
  jout(['success'=>false,'error'=>'Unknown action'],400);
}

/* ---- Image server: admin.php itself reads the poster ---- */
if (isset($_GET['img'])) {
  $n = basename((string)$_GET['img']);
  $f = '';
  foreach ([__DIR__.'/uploads/', __DIR__.'/images/', dirname(__DIR__).'/uploads/', dirname(__DIR__).'/images/'] as $d)
    if (is_file($d.$n)) { $f = $d.$n; break; }
  // if still not found, search all of htdocs
  if (!$f && !empty($_SERVER['DOCUMENT_ROOT']) && is_dir($_SERVER['DOCUMENT_ROOT'])) {
    try {
      $it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'], FilesystemIterator::SKIP_DOTS),
        function($x){ return !$x->isDir() || !in_array($x->getFilename(),['phpmyadmin','node_modules','vendor','.git']); }
      ));
      foreach ($it as $x) if ($x->getFilename()===$n) { $f = $x->getPathname(); break; }
    } catch (Throwable $ex) {}
  }
  if ($f) {
    $mime = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','jfif'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
    $ext = strtolower(pathinfo($f,PATHINFO_EXTENSION));
    header('Content-Type: '.($mime[$ext] ?? 'application/octet-stream'));
    readfile($f); exit;
  }
  header('Content-Type: image/svg+xml');
  echo '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="260"><rect width="100%" height="100%" fill="#1a2238"/>'
     . '<text x="110" y="120" fill="#f87171" font-size="14" text-anchor="middle">FILE NOT FOUND</text>'
     . '<text x="110" y="145" fill="#94a3b8" font-size="11" text-anchor="middle">'.htmlspecialchars($n).'</text></svg>';
  exit;
}

/* ---- Movies table (created/updated automatically) ---- */
$db->query("CREATE TABLE IF NOT EXISTS movies (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(150) NOT NULL, poster VARCHAR(255) DEFAULT NULL)");
// settings table (cinema location)
$db->query("CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v TEXT)");
// add the new columns if they don't exist yet
$need = [
  'rating'  => "VARCHAR(10) NOT NULL DEFAULT 'PG'",
  'status'  => "VARCHAR(10) NOT NULL DEFAULT 'now'",
  'price'   => "DECIMAL(8,2) NOT NULL DEFAULT 250",   // ticket price
  'synopsis'=> "TEXT NULL",                            // synopsis / short story
  'cast_members' => "VARCHAR(255) NULL",               // cast
  'runtime'      => "VARCHAR(20) NULL",                // e.g. 1h 33m
  'release_date' => "VARCHAR(40) NULL",             // text, e.g. 14 October 2026
  'genre'        => "VARCHAR(80) NULL",                // e.g. Drama, Action
];
foreach ($need as $col=>$def) {
  if (!q("SHOW COLUMNS FROM movies LIKE '$col'")) $db->query("ALTER TABLE movies ADD COLUMN $col $def");
}
// if release_date is still DATE (old setup), convert it to text so it can be freely written
$rc = q("SHOW COLUMNS FROM movies LIKE 'release_date'");
if ($rc && stripos($rc[0]['Type'], 'date') === 0) $db->query("ALTER TABLE movies MODIFY release_date VARCHAR(40) NULL");
// status and created_at in bookings
$needB = [
  'status'     => "VARCHAR(15) NOT NULL DEFAULT 'Confirmed'",
  'created_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
];
foreach ($needB as $col=>$def) {
  if (!q("SHOW COLUMNS FROM $TB LIKE '$col'")) $db->query("ALTER TABLE $TB ADD COLUMN $col $def");
}
// insert your original 15 movies if the table is empty (one time only)
if ((int)one("SELECT COUNT(*) FROM movies")===0) {
  $seed = [
    ['Avengers Doomsday','PG','avengers.jpg'],['Starship Troopers','R','starshiptroopers.jpg'],
    ['Spider-Man','PG','spiderman.jpg'],['Fury','PG','fury.jpg'],
    ['Transformers Revenge of the Fallen','PG','Transformers.jpg'],['Shin Godzilla','PG','godzilla.jpg'],
    ['Iron-Man 3','PG','rdj.jpg'],['The Terminator','PG','Terminator.jpg'],
    ['World War Z','PG','zz.jpg'],['The First Slam-Dunk Movie','PG','sl.jpg'],
    ['The Notebook','PG','note.jpg'],['The Truman Show','PG','tr.jpg'],
    ['Space Jam','PG','jordan.jpg'],['The Wolf of the Wall Street','PG','wolf.jpg'],
    ['A Silent Voice','PG','silent.jpg'],
  ];
  $st=$db->prepare("INSERT INTO movies(title,rating,poster) VALUES(?,?,?)");
  foreach ($seed as $s) { $st->bind_param('sss',$s[0],$s[1],$s[2]); $st->execute(); }
}
if (!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads');
function upload(){
  if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error']===0) {
    $ext=strtolower(pathinfo($_FILES['poster']['name'],PATHINFO_EXTENSION));
    if (!in_array($ext,['jpg','jpeg','png','webp','gif','jfif'])) return null;
    $n=uniqid('p_').'.'.$ext;
    move_uploaded_file($_FILES['poster']['tmp_name'],__DIR__.'/uploads/'.$n);
    return $n;
  }
  // if no file was uploaded, use the poster from OMDb
  $o = basename(trim($_POST['omdb_poster'] ?? ''));
  if ($o !== '' && preg_match('/^omdb_tt\d+\.jpg$/', $o) && is_file(__DIR__.'/uploads/'.$o)) return $o;
  return null;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['act'])) {
  $a=$_POST['act']; $id=(int)($_POST['id']??0);
  $title=trim($_POST['title']??''); 
  $rating=trim($_POST['rating']??'PG') ?: 'PG';
  $status=($_POST['status']??'now')==='soon' ? 'soon' : 'now';
  $price=max(0,(float)($_POST['price']??250));
  $synopsis=trim($_POST['synopsis']??'');
  $cast=trim($_POST['cast_members']??'');
  $runtime=trim($_POST['runtime']??'');
  $release=trim($_POST['release_date']??'');
  $genre=trim($_POST['genre']??'');
  if ($a==='add_movie' && $title!=='') {
    $p=upload(); $st=$db->prepare("INSERT INTO movies(title,poster,rating,status,price,synopsis,cast_members,runtime,release_date,genre) VALUES(?,?,?,?,?,?,?,?,?,?)");
    $st->bind_param('ssssdsssss',$title,$p,$rating,$status,$price,$synopsis,$cast,$runtime,$release,$genre); $st->execute();
  }
  if ($a==='edit_movie' && $title!=='') {
    $old=one("SELECT title FROM movies WHERE id=$id");
    $st=$db->prepare("UPDATE movies SET title=?,rating=?,status=?,price=?,synopsis=?,cast_members=?,runtime=?,release_date=?,genre=? WHERE id=?");
    $st->bind_param('sssdsssssi',$title,$rating,$status,$price,$synopsis,$cast,$runtime,$release,$genre,$id); $st->execute();
    if ($p=upload()) { $st=$db->prepare("UPDATE movies SET poster=? WHERE id=?"); $st->bind_param('si',$p,$id); $st->execute(); }
    if ($old!=='' && $old!==$title) {
      $st=$db->prepare("UPDATE $TB SET {$C['movie']}=? WHERE {$C['movie']}=?"); $st->bind_param('ss',$title,$old); $st->execute();
    }
  }
  if ($a==='delete_movie') $db->query("DELETE FROM movies WHERE id=$id");
  // NEW: Approve (Confirmed), Reject (Rejected), Done (Finished)
  if ($a==='set_booking') {
    $s = in_array($_POST['status']??'', ['Confirmed','Finished','Rejected']) ? $_POST['status'] : 'Confirmed';
    $oldS = (string)one("SELECT {$C['status']} FROM $TB WHERE {$C['id']}=".(int)$id);
    $st=$db->prepare("UPDATE $TB SET {$C['status']}=? WHERE {$C['id']}=?");
    $st->bind_param('si',$s,$id); $st->execute();
    // NEW: notification to the user
    require_once __DIR__.'/notify.php';
    $mv = one("SELECT movie FROM $TB WHERE id=".(int)$id);
    if ($s==='Confirmed') notifyBooking($db,$id,"Your booking for $mv has been approved! Your ticket/barcode is now available.");
    if ($s==='Rejected')  notifyBooking($db,$id,"Your payment for $mv was rejected. Please contact the cinema or book again.");
    // thank-you message after the movie (only once, when it becomes Finished)
    if ($s==='Finished' && $oldS!=='Finished') notifyBooking($db,$id,"Thank you for watching $mv! We hope you enjoyed the show. See you again at the cinema.",false);
  }
  // the whole showtime is finished -> all of its seats are free again
  if ($a==='finish_show') {
    require_once __DIR__.'/notify.php';
    $m=$_POST['movie']??''; $t=$_POST['showtime']??''; $d=trim($_POST['show_date']??''); if($d==='') $d=null;
    // get the bookings first, so we know who to thank
    $st=$db->prepare("SELECT {$C['id']} id FROM $TB WHERE {$C['movie']}=? AND showtime=? AND show_date<=>? AND {$C['status']}='Confirmed'");
    $st->bind_param('sss',$m,$t,$d); $st->execute();
    $ids=array_column($st->get_result()->fetch_all(MYSQLI_ASSOC),'id');
    $st=$db->prepare("UPDATE $TB SET {$C['status']}='Finished' WHERE {$C['movie']}=? AND showtime=? AND show_date<=>? AND {$C['status']}='Confirmed'");
    $st->bind_param('sss',$m,$t,$d); $st->execute();
    foreach ($ids as $bid) notifyBooking($db,(int)$bid,"Thank you for watching $m! We hope you enjoyed the show. See you again at the cinema.",false);
  }
  // save the cinema location
  if ($a==='save_location') {
    foreach (['loc_name','loc_address','loc_lat','loc_lng'] as $k) {
      $v=trim($_POST[$k]??'');
      $st=$db->prepare("REPLACE INTO settings(k,v) VALUES(?,?)"); $st->bind_param('ss',$k,$v); $st->execute();
    }
  }
  header('Location: admin.php?page='.urlencode($_POST['back']??'dashboard')); exit;
}

/* ---- Data ---- */
$page = $_GET['page'] ?? 'dashboard';
$b=$C;
$totalBookings = one("SELECT COUNT(*) FROM $TB");
$totalCustomers = one("SELECT COUNT(*) FROM users WHERE role<>'admin'");
$totalMovies = one("SELECT COUNT(*) FROM movies");
$totalSales = one("SELECT COALESCE(SUM({$b['price']}),0) FROM $TB WHERE {$b['status']} IN ('Confirmed','Finished')");
$top = q("SELECT {$b['movie']} m, COUNT(*) c FROM $TB GROUP BY {$b['movie']} ORDER BY c DESC LIMIT 5");
$sales = q("SELECT DATE({$b['date']}) d, SUM({$b['price']}) s FROM $TB WHERE {$b['date']}>=CURDATE()-INTERVAL 6 DAY GROUP BY d ORDER BY d");
$recent = q("SELECT {$b['id']} id,{$b['movie']} movie,name cust,showtime,{$b['seats']} seats,{$b['price']} price,{$b['date']} dt,{$b['status']} st
  FROM $TB ORDER BY {$b['date']} DESC, {$b['id']} DESC LIMIT ".($page==='bookings'?200:5));
$activeShows = $page==='bookings' ? q("SELECT {$b['movie']} movie, showtime, show_date, COUNT(*) n, SUM(quantity) seats FROM $TB WHERE {$b['status']}='Confirmed' GROUP BY {$b['movie']}, showtime, show_date ORDER BY show_date, {$b['movie']}") : [];
// NEW: payments waiting for approval
$approvals = $page==='bookings' ? q("SELECT id,movie,name cust,showtime,seat,total_price,payment_method,payment_ref FROM $TB WHERE status='For Approval' ORDER BY id") : [];
$customers = $page==='customers' ? q("SELECT id,name,email FROM users WHERE role<>'admin' ORDER BY id DESC") : [];
$maxTop = $top ? max(array_column($top,'c')) : 1;
$sumTop = array_sum(array_column($top,'c')) ?: 1;
$nav=['dashboard'=>'Dashboard','movies'=>'Movies','bookings'=>'Bookings','customers'=>'Customers','location'=>'Location'];
$movies = q("SELECT * FROM movies ORDER BY id DESC");
$posters = array_column($movies,'poster','title');
$loc = array_column(q("SELECT k,v FROM settings"),'v','k');
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Cinema Ticket Admin</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
*{box-sizing:border-box}body{margin:0;background:#0a0f1c;color:#e5e7eb;font-family:Segoe UI,sans-serif;display:flex;min-height:100vh}
aside{width:250px;background:#0d1424;padding:20px 10px;border-right:1px solid #151c2e}
.logo{font-weight:700;font-size:18px;padding:0 10px 24px}.logo span{color:#3b82f6;display:block}
aside a{display:block;padding:12px 14px;border-radius:8px;color:#cbd5e1;text-decoration:none;margin-bottom:4px}
aside a.on{background:#1e40af;color:#fff}
main{flex:1;padding:24px 28px}
.top{display:flex;justify-content:space-between;align-items:flex-start}
h1{margin:0}.sub{color:#94a3b8;margin:4px 0 20px}
.grid{display:grid;gap:16px}.g4{grid-template-columns:repeat(4,1fr)}.g2{grid-template-columns:2fr 1fr}
.card{background:#0f1729;border:1px solid #1a2238;border-radius:12px;padding:20px;margin-bottom:16px}
.card h3{margin:0 0 14px;font-weight:600}.stat small{color:#94a3b8}.stat b{display:block;font-size:30px;margin-top:6px}
table{width:100%;border-collapse:collapse;font-size:14px}th{color:#94a3b8;text-align:left;padding:8px;font-weight:500}
td{padding:12px 8px;border-top:1px solid #151c2e}
.badge{padding:3px 10px;border-radius:5px;font-size:12px;background:#064e3b;color:#6ee7b7}.badge.p{background:#713f12;color:#fcd34d}.badge.g{background:#1f2937;color:#9ca3af}
.bar{height:6px;background:#1a2238;border-radius:4px;margin-top:6px}.bar i{display:block;height:100%;background:#3b82f6;border-radius:4px}
.row{margin-bottom:16px;font-size:14px}.row small{color:#94a3b8}
.out{color:#94a3b8;text-decoration:none;font-size:13px}
.inl{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
main input:not([type=hidden]):not([type=checkbox]),main select,main button{padding:8px 10px;border-radius:8px;border:1px solid #1a2238;background:#0a0f1c;color:#e5e7eb;margin-bottom:8px;max-width:100%}
main textarea{padding:8px 10px;border-radius:8px;border:1px solid #1a2238;background:#0a0f1c;color:#e5e7eb;margin-bottom:8px;max-width:100%;font-family:inherit;font-size:14px;resize:vertical}
main form.f input:not([type=hidden]):not([type=checkbox]),main form.f select,main form.f textarea{width:100%}
main button{background:#2563eb;border:0;cursor:pointer}main button.del{background:#991b1b}
main button.fin{background:#047857;margin:0;padding:6px 12px;font-size:13px}main button.undo{background:#374151;margin:0;padding:6px 12px;font-size:13px}
.lbl{display:block;font-size:12px;color:#94a3b8;margin:2px 0 4px}
.pr{color:#6ee7b7;font-weight:600;margin:0 0 10px}
.mv{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
.po{width:100%;height:260px;object-fit:cover;border-radius:8px;margin-bottom:10px}
.noimg{display:grid;place-items:center;background:#1a2238;color:#94a3b8}
.th{width:34px;height:48px;object-fit:cover;border-radius:4px;float:left;margin-right:10px}.row{overflow:hidden}
.trow{display:flex;align-items:center;gap:12px;margin-bottom:16px;font-size:14px}
.trow .th{float:none;margin:0;flex:0 0 40px;width:40px;height:56px;background:#1a2238}
.trow .tc{flex:1;min-width:0}
.trow .tt{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.trow small{display:block;color:#94a3b8;margin-top:2px}
.trow .bar{margin-top:8px}
@media(max-width:900px){.g4,.g2{grid-template-columns:1fr}aside{display:none}}
</style></head><body>
<aside><div class="logo">CINEMA TICKET<span>ADMIN</span></div>
<?php foreach($nav as $k=>$v) echo "<a href='?page=$k' class='".($page===$k?'on':'')."'>$v</a>"; ?>
</aside>
<main>
<div class="top"><div><h1><?=e($nav[$page]??'Dashboard')?></h1><p class="sub">Welcome back, <?=e($_SESSION['admin'])?>! Here's what's happening today.</p></div>
<div style="text-align:right"><?=date('M d, Y H:i')?><br><a class="out" href="?logout=1">Logout</a></div></div>

<?php if($page==='dashboard'): ?>
<div class="grid g4">
<div class="card stat"><small>Total Bookings</small><b><?=$totalBookings?></b></div>
<div class="card stat"><small>Total Customers</small><b><?=$totalCustomers?></b></div>
<div class="card stat"><small>Total Movies</small><b><?=$totalMovies?></b></div>
<div class="card stat"><small>Total Sales</small><b>₱ <?=number_format($totalSales,2)?></b></div>
</div>
<div class="grid g2"><div>
<div class="card"><h3>Sales Overview (Last 7 Days)</h3><canvas id="ch" height="110"></canvas></div>
<?php endif; ?>

<?php if($page==='movies'): ?>
<div class="card"><h3>Add New Movie</h3>
<div class="inl" style="margin-bottom:6px">
  <input id="omq" placeholder="&#128269; Search OMDb (e.g. Inception)" style="flex:1;min-width:220px">
  <button type="button" id="omBtn">Search OMDb</button>
</div>
<div id="omMsg" class="sub" style="margin:0 0 8px"></div>
<div id="omRes" class="inl" style="margin-bottom:12px"></div>
<form method="post" enctype="multipart/form-data" class="inl" id="addForm">
<input type="hidden" name="act" value="add_movie"><input type="hidden" name="back" value="movies">
<input type="hidden" name="omdb_poster" id="omdb_poster" value="">
<input name="title" placeholder="Movie title" required>
<input name="rating" placeholder="Rating (PG, R...)" value="PG" size="8">
<input name="price" type="number" step="0.01" min="0" value="250" placeholder="Price ₱" title="Price per ticket (₱)" style="width:110px">
<select name="status"><option value="now">Now showing</option><option value="soon">Coming soon</option></select>
<input name="genre" placeholder="Genre (e.g. Drama, Action)">
<input name="runtime" placeholder="Runtime (e.g. 1h 33m)" size="14">
<input name="release_date" placeholder="Release date (e.g. 14 October 2026)" size="26">
<input name="cast_members" placeholder="Cast (separate with commas)" style="flex:1 1 100%">
<textarea name="synopsis" rows="3" placeholder="Movie synopsis / short story" style="flex:1 1 100%"></textarea>
<div id="omPrev" style="flex:1 1 100%"></div>
<input type="file" name="poster" accept="image/*"><button>Add</button></form></div>
<div class="mv">
<?php foreach($movies as $m): ?>
<div class="card">
<?php if($m['poster']) echo "<img class='po' src='".e(pu($m['poster']))."'>"; else echo "<div class='po noimg'>No poster</div>"; ?>
<p class="pr">₱ <?=number_format((float)$m['price'],2)?> / ticket</p>
<form method="post" enctype="multipart/form-data" class="f">
<input type="hidden" name="act" value="edit_movie"><input type="hidden" name="back" value="movies"><input type="hidden" name="id" value="<?=$m['id']?>">
<input name="title" value="<?=e($m['title'])?>" required>
<input name="rating" value="<?=e($m['rating'])?>" placeholder="Rating">
<span class="lbl">Price per ticket (₱)</span>
<input name="price" type="number" step="0.01" min="0" value="<?=e($m['price'])?>" required>
<select name="status"><option value="now" <?=$m['status']==='now'?'selected':''?>>Now showing</option><option value="soon" <?=$m['status']==='soon'?'selected':''?>>Coming soon</option></select>
<span class="lbl">Genre (separate with commas)</span>
<input name="genre" value="<?=e($m['genre'] ?? '')?>">
<span class="lbl">Runtime (e.g. 1h 33m)</span>
<input name="runtime" value="<?=e($m['runtime'] ?? '')?>">
<span class="lbl">Release date (e.g. 14 October 2026)</span>
<input name="release_date" value="<?=e($m['release_date'] ?? '')?>">
<span class="lbl">Cast</span>
<input name="cast_members" value="<?=e($m['cast_members'] ?? '')?>">
<span class="lbl">Synopsis / short story</span>
<textarea name="synopsis" rows="5" placeholder="Movie synopsis"><?=e($m['synopsis'] ?? '')?></textarea>
<input type="file" name="poster" accept="image/*">
<button>Save</button></form>
<form method="post" onsubmit="return confirm('Delete this movie?')">
<input type="hidden" name="act" value="delete_movie"><input type="hidden" name="back" value="movies"><input type="hidden" name="id" value="<?=$m['id']?>">
<button class="del">Delete</button></form></div>
<?php endforeach; if(!$movies) echo "<p class='sub'>No Movies, Add a movie </p>"; ?>
</div>
<?php elseif(!in_array($page,['customers','location'])): ?>

<?php if($page==='bookings'): ?>
<div class="card"><h3>Awaiting Approval</h3>
<?php if(!$approvals) echo "<p class='sub' style='margin:0'>No payments waiting.</p>"; else { ?>
<table><tr><th>#</th><th>Movie</th><th>Customer</th><th>Showtime</th><th>Seat</th><th>Total</th><th>Via</th><th>Ref</th><th></th></tr>
<?php foreach($approvals as $a): ?>
<tr><td><?=e($a['id'])?></td><td><?=e($a['movie'])?></td><td><?=e($a['cust'])?></td><td><?=e($a['showtime'])?></td><td><?=e($a['seat'])?></td>
<td>₱ <?=number_format((float)$a['total_price'],2)?></td><td><?=e($a['payment_method'])?></td><td><?=e($a['payment_ref'])?></td>
<td><div class="inl">
<form method="post"><input type="hidden" name="act" value="set_booking"><input type="hidden" name="back" value="bookings"><input type="hidden" name="id" value="<?=e($a['id'])?>"><input type="hidden" name="status" value="Confirmed"><button class="fin">Approve</button></form>
<form method="post" onsubmit="return confirm('Reject this payment?')"><input type="hidden" name="act" value="set_booking"><input type="hidden" name="back" value="bookings"><input type="hidden" name="id" value="<?=e($a['id'])?>"><input type="hidden" name="status" value="Rejected"><button class="del" style="margin:0;padding:6px 12px;font-size:13px">Reject</button></form>
</div></td></tr>
<?php endforeach; ?></table><?php } ?></div>

<div class="card"><h3>Active Shows (still seated)</h3>
<?php if(!$activeShows) echo "<p class='sub' style='margin:0'>No active showtimes right now.</p>"; else { ?>
<table><tr><th>Movie</th><th>Date</th><th>Showtime</th><th>Bookings</th><th>Seats</th><th></th></tr>
<?php foreach($activeShows as $s): ?>
<tr><td><?=e($s['movie'])?></td><td><?=e($s['show_date'] ?: '—')?></td><td><?=e($s['showtime'])?></td><td><?=e($s['n'])?></td><td><?=e($s['seats'])?></td>
<td><form method="post" onsubmit="return confirm('Is this show finished? All seats will become available again and a thank-you message will be sent to the customers.')">
<input type="hidden" name="act" value="finish_show"><input type="hidden" name="back" value="bookings">
<input type="hidden" name="movie" value="<?=e($s['movie'])?>"><input type="hidden" name="showtime" value="<?=e($s['showtime'])?>"><input type="hidden" name="show_date" value="<?=e($s['show_date'])?>">
<button class="fin">Finished — free up the seats</button></form></td></tr>
<?php endforeach; ?></table><?php } ?></div>
<?php endif; ?>

<div class="card"><h3>Recent Bookings</h3><table>
<tr><th>#</th><th>Movie</th><th>Customer</th><th>Showtime</th><th>Seats</th><th>Total</th><th>Date & Time</th><th>Status</th><?php if($page==='bookings') echo '<th></th>'; ?></tr>
<?php foreach($recent as $r): ?>
<tr><td><?=e($r['id'])?></td><td><?=e($r['movie'])?></td><td><?=e($r['cust'])?></td><td><?=e($r['showtime'])?></td><td><?=e($r['seats'])?></td>
<td>₱ <?=number_format((float)$r['price'],2)?></td><td><?=e($r['dt'])?></td>
<td><span class="badge <?=$r['st']==='Confirmed'?'':($r['st']==='For Approval'?'p':'g')?>"><?=e($r['st'])?></span></td>
<?php if($page==='bookings'): ?><td>
<form method="post"><input type="hidden" name="act" value="set_booking"><input type="hidden" name="back" value="bookings"><input type="hidden" name="id" value="<?=e($r['id'])?>">
<?php if($r['st']==='Confirmed'): ?><input type="hidden" name="status" value="Finished"><button class="fin">Done</button>
<?php elseif($r['st']==='Finished'): ?><input type="hidden" name="status" value="Confirmed"><button class="undo">Restore</button>
<?php elseif($r['st']==='Rejected'): ?><input type="hidden" name="status" value="Confirmed"><button class="undo">Approve</button>
<?php else: ?>—<?php endif; ?></form></td><?php endif; ?>
</tr>
<?php endforeach; ?></table></div>

<?php elseif($page==='customers'): ?>
<div class="card"><table><tr><th>ID</th><th>Name</th><th>Email</th></tr>
<?php foreach($customers as $c) echo "<tr><td>".e($c['id'])."</td><td>".e($c['name'])."</td><td>".e($c['email'])."</td></tr>"; ?></table></div>

<?php elseif($page==='location'): ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<div class="card"><h3>Cinema Location</h3>
<p class="sub" style="margin-top:0">Search the address or click the map to place the pin, then Save.</p>
<form method="post" class="f" id="locForm">
<input type="hidden" name="act" value="save_location"><input type="hidden" name="back" value="location">
<span class="lbl">Cinema name</span>
<input name="loc_name" value="<?=e($loc['loc_name'] ?? '')?>" placeholder="e.g. Cinema Ticket SM City SJDM" required>
<span class="lbl">Address</span>
<div class="inl" style="flex-wrap:nowrap">
  <input name="loc_address" value="<?=e($loc['loc_address'] ?? '')?>" placeholder="Street, Barangay, City, Province" required>
  <button type="button" id="locSearch" style="white-space:nowrap">Search address</button>
</div>
<div id="locMsg" class="sub" style="margin:0 0 8px"></div>
<div class="inl" style="flex-wrap:nowrap">
  <div style="flex:1"><span class="lbl">Latitude</span><input name="loc_lat" value="<?=e($loc['loc_lat'] ?? '')?>" readonly></div>
  <div style="flex:1"><span class="lbl">Longitude</span><input name="loc_lng" value="<?=e($loc['loc_lng'] ?? '')?>" readonly></div>
</div>
<div id="lmap" style="height:380px;border-radius:10px;margin-bottom:12px;border:1px solid #1a2238"></div>
<button>Save location</button>
</form></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function(){
  var f=document.getElementById('locForm'), E=f.elements, msg=document.getElementById('locMsg');
  var has=E['loc_lat'].value!=='' && E['loc_lng'].value!=='';
  var lat=has?parseFloat(E['loc_lat'].value):14.8139, lng=has?parseFloat(E['loc_lng'].value):121.0453;
  var map=L.map('lmap').setView([lat,lng],has?16:12);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);
  var mk=null;
  function put(a,b){
    if(mk) mk.setLatLng([a,b]); else mk=L.marker([a,b]).addTo(map);
    E['loc_lat'].value=a.toFixed(6); E['loc_lng'].value=b.toFixed(6);
  }
  if(has) put(lat,lng);
  map.on('click',function(e){ put(e.latlng.lat,e.latlng.lng); });
  document.getElementById('locSearch').onclick=function(){
    var q=E['loc_address'].value.trim(); if(!q) return;
    msg.textContent='Searching...';
    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q='+encodeURIComponent(q))
      .then(function(r){return r.json()}).then(function(d){
        if(!d.length){ msg.textContent='Address not found. Click the map to place the pin instead.'; return; }
        var a=parseFloat(d[0].lat), b=parseFloat(d[0].lon);
        put(a,b); map.setView([a,b],17); msg.textContent='Found. Adjust the pin if needed, then Save.';
      }).catch(function(){ msg.textContent='Search failed. Click the map to place the pin.'; });
  };
  f.addEventListener('submit',function(e){
    if(E['loc_lat'].value===''){ e.preventDefault(); msg.textContent='Please place the pin on the map first.'; }
  });
})();
</script>
<?php endif; ?>

<?php if($page==='dashboard'): ?>
</div><div>
<div class="card"><h3>Top 5 Movies (by Bookings)</h3>
<?php foreach($top as $t): ?><div class="trow">
<?php if(!empty($posters[$t['m']])) echo "<img class='th' src='".e(pu($posters[$t['m']]))."'>"; else echo "<div class='th'></div>"; ?>
<div class="tc"><div class="tt" title="<?=e($t['m'])?>"><?=e($t['m'])?></div>
<small><?=$t['c']?> bookings (<?=round($t['c']/$sumTop*100)?>%)</small>
<div class="bar"><i style="width:<?=round($t['c']/$maxTop*100)?>%"></i></div></div></div><?php endforeach; ?></div>
</div></div>
<script>
new Chart(document.getElementById('ch'),{type:'line',
 data:{labels:<?=json_encode(array_column($sales,'d'))?>,datasets:[{data:<?=json_encode(array_map('floatval',array_column($sales,'s')))?>,
 borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,.15)',fill:true,tension:.3}]},
 options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#94a3b8'},grid:{display:false}},y:{ticks:{color:'#94a3b8'},grid:{color:'#1a2238'}}}}});
</script>
<?php endif; ?>

<?php if($page==='movies'): ?>
<script>
(function(){
  var $=function(i){return document.getElementById(i)};
  var f=$('addForm');
  function setv(name,val){ var el=f.elements[name]; if(el) el.value=val||''; }
  function msg(t){ $('omMsg').textContent=t||''; }

  function search(){
    var q=$('omq').value.trim(); if(!q) return;
    $('omRes').innerHTML=''; msg('Searching...');
    fetch('admin.php?omdb=search&q='+encodeURIComponent(q)).then(function(r){return r.json()}).then(function(d){
      if(!d.success){ msg(d.error); return; }
      msg('Choose a movie:');
      d.results.forEach(function(m){
        var b=document.createElement('button'); b.type='button';
        b.style.cssText='background:#0a0f1c;border:1px solid #1a2238;padding:6px;width:120px;text-align:left';
        if(m.poster){ var i=document.createElement('img'); i.src=m.poster; i.style.cssText='width:100%;height:150px;object-fit:cover;border-radius:4px;display:block'; b.appendChild(i); }
        var t=document.createElement('div'); t.style.cssText='font-size:12px;margin-top:4px'; t.textContent=m.title+' ('+m.year+')'; b.appendChild(t);
        b.onclick=function(){ pick(m.imdb); };
        $('omRes').appendChild(b);
      });
    }).catch(function(){ msg('The request failed.'); });
  }

  function pick(imdb){
    msg('Getting details and poster...');
    fetch('admin.php?omdb=get&imdb='+encodeURIComponent(imdb)).then(function(r){return r.json()}).then(function(d){
      if(!d.success){ msg(d.error); return; }
      var m=d.movie;
      setv('title',m.title); setv('rating',m.rating); setv('genre',m.genre);
      setv('runtime',m.runtime); setv('release_date',m.release);
      setv('cast_members',m.cast); setv('synopsis',m.plot);
      $('omdb_poster').value=m.poster||'';
      $('omPrev').innerHTML = m.poster
        ? '<img src="?img='+encodeURIComponent(m.poster)+'" style="height:160px;border-radius:8px"> <span class="sub">Poster downloaded. Click Add to save.</span>'
        : '<span class="sub">No poster found. You can upload your own.</span>';
      $('omRes').innerHTML=''; msg('Form filled in. Check the price and status, then click Add.');
    }).catch(function(){ msg('The request failed.'); });
  }

  $('omBtn').onclick=search;
  $('omq').addEventListener('keydown',function(e){ if(e.key==='Enter'){ e.preventDefault(); search(); } });
})();
</script>
<?php endif; ?>
</main></body></html>
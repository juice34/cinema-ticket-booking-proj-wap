<?php
include "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// If already logged in, go straight to movies
if (!empty($_SESSION['user_id'])) {
    header("Location: movies.php");
    exit;
}

$message = "";        // signup message
$loginMessage = "";   // login message
$messageType = "";    // "ok" or "err"
$view = "signin";     // signin | signup

/* ===== FORGOT PASSWORD: SETTINGS =====
   $DEMO_SHOW_LINK = true  -> the reset link is shown on screen (for localhost/XAMPP, which cannot send email)
   $DEMO_SHOW_LINK = false -> will try to email the link using mail() */
$DEMO_SHOW_LINK = true;
$EXPIRY_MIN     = 30;   // minutes before the link expires
$MIN_LEN        = 6;    // shortest allowed password

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX(token_hash), INDEX(email)
)");

$fMessage = ""; $fType = ""; $demoLink = ""; $resetEmail = ""; $resetToken = "";

// If there is a token in the link (index.php?token=...), check if it is still valid
$token = (string)($_GET['token'] ?? $_POST['reset_token'] ?? '');
if ($token !== '') {
    $th = hash('sha256', $token);
    $st = mysqli_prepare($conn, "SELECT email FROM password_resets WHERE token_hash = ? AND expires_at > NOW() LIMIT 1");
    mysqli_stmt_bind_param($st, "s", $th);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    if ($row) {
        $resetEmail = $row['email'];
        $resetToken = $token;
        $view = "reset";
    } else {
        $view = "forgot";
        $fMessage = "That reset link is invalid or has expired. Please request a new one.";
        $fType = "err";
    }
}


/* ================= SIGN UP ================= */
if (isset($_POST['signup'])) {
    $view = "signup";

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $plain = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $plain === '') {
        $message = "Please fill in all fields.";
        $messageType = "err";
    } else {
        // Check if the email is already in use
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        $exists = mysqli_stmt_num_rows($check) > 0;
        mysqli_stmt_close($check);

        if ($exists) {
            $message = "That email is already registered.";
            $messageType = "err";
        } else {
            $password = password_hash($plain, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $password);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Account created successfully! You can now sign in.";
                $messageType = "ok";
                $view = "signin";
            } else {
                $message = "Unable to create account. Please try again.";
                $messageType = "err";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

/* ================= FORGOT PASSWORD: request a link ================= */
if (isset($_POST['forgot'])) {
    $view = "forgot";
    $fEmail = trim($_POST['forgot_email'] ?? '');

    if (!filter_var($fEmail, FILTER_VALIDATE_EMAIL)) {
        $fMessage = "Please enter a valid email.";
        $fType = "err";
    } else {
        $st = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($st, "s", $fEmail);
        mysqli_stmt_execute($st);
        $u = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);

        if ($u) {
            $d = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
            mysqli_stmt_bind_param($d, "s", $fEmail);
            mysqli_stmt_execute($d);
            mysqli_stmt_close($d);

            $raw  = bin2hex(random_bytes(32));
            $hash = hash('sha256', $raw);
            $i = mysqli_prepare($conn, "INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL ? MINUTE)");
            mysqli_stmt_bind_param($i, "ssi", $fEmail, $hash, $EXPIRY_MIN);
            mysqli_stmt_execute($i);
            mysqli_stmt_close($i);

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            $link   = $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir . '/' . basename($_SERVER['SCRIPT_NAME']) . '?token=' . $raw;

            if ($DEMO_SHOW_LINK) {
                $demoLink = $link;
            } else {
                $body = "Hello,\n\nWe received a request to reset your password.\nClick the link below (valid for $EXPIRY_MIN minutes):\n\n$link\n\nIf this wasn't you, you can ignore this email.";
                @mail($fEmail, "Reset your password - Movie Ticket Booking", $body, "Content-Type: text/plain; charset=UTF-8");
            }
        }
        // Same message even if the email doesn't exist, so no one can guess who has an account
        $fMessage = "If that email is registered, a password reset link has been sent.";
        $fType = "ok";
    }
}

/* ================= FORGOT PASSWORD: set a new password ================= */
if (isset($_POST['reset']) && $resetEmail !== '') {
    $view = "reset";
    $p1 = $_POST['new_password'] ?? '';
    $p2 = $_POST['new_password2'] ?? '';

    if (strlen($p1) < $MIN_LEN) {
        $fMessage = "Password must be at least $MIN_LEN characters.";
        $fType = "err";
    } elseif ($p1 !== $p2) {
        $fMessage = "The two passwords do not match.";
        $fType = "err";
    } else {
        $newHash = password_hash($p1, PASSWORD_DEFAULT);
        $up = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
        mysqli_stmt_bind_param($up, "ss", $newHash, $resetEmail);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);

        $d = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
        mysqli_stmt_bind_param($d, "s", $resetEmail);
        mysqli_stmt_execute($d);
        mysqli_stmt_close($d);

        $view = "signin";
        $message = "Your password has been changed. You can now sign in.";
        $messageType = "ok";
    }
}

/* ================= LOGIN ================= */
if (isset($_POST['login'])) {
    $view = "signin";

    $email    = trim($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            header("Location: movies.php");
            exit;
        } else {
            $loginMessage = "Incorrect email or password.";
            $messageType = "err";
        }
    } else {
        $loginMessage = "Unable to process login.";
        $messageType = "err";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Movie Ticket Booking</title>
<link rel="stylesheet" href="theme.css">
<script src="theme.js"></script>
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
        color: var(--text);
        background: var(--bg);
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        line-height: 1.4;
    }
    a:focus-visible, button:focus-visible, input:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
    .theme-fixed { position: fixed; top: 14px; right: 14px; margin: 0; background: var(--surface); }

    main { flex: 1; width: 100%; max-width: 440px; margin: 0 auto; padding: 56px 16px 48px; }
    h1 { font-size: 34px; font-weight: 500; margin: 0 0 20px; }

    .card { border: 1px solid var(--border); border-radius: 12px; padding: 28px 30px; background: var(--surface); box-shadow: var(--shadow); }
    label { display: block; font-weight: 600; font-size: 17px; margin-bottom: 8px; }
    .field { margin-bottom: 22px; }
    .input-wrap { position: relative; }
    input[type=text], input[type=email], input[type=password] {
        width: 100%; height: 44px; padding: 0 14px;
        font-size: 16px; font-family: inherit;
        border: 1px solid var(--border); border-radius: 6px;
        background: var(--bg); color: var(--text);
    }
    .input-wrap input { padding-right: 46px; }
    .eye {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        border: 0; background: none; cursor: pointer; padding: 6px; line-height: 0; color: var(--muted);
    }
    .eye:hover { color: var(--text); }
    .eye svg { width: 22px; height: 22px; }

    .forgot { display: block; text-align: right; margin: -12px 0 22px; font-size: 15px; }

    .btn {
        display: block; width: 100%; height: 56px;
        border: 0; border-radius: 8px;
        background: var(--accent); color: #1a1405;
        font-size: 21px; font-weight: 700; font-family: inherit; cursor: pointer;
    }
    .btn:hover { filter: brightness(1.08); }

    .center { text-align: center; }
    .link, .forgot { color: var(--link); font-weight: 600; text-decoration: none; }
    .link { background: none; border: 0; padding: 0; font: inherit; font-weight: 600; cursor: pointer; }
    .link:hover, .forgot:hover { text-decoration: underline; }
    .below { margin-top: 22px; font-size: 17px; }

    .msg { padding: 10px 14px; border-radius: 6px; margin-bottom: 20px; font-size: 15px; border: 1px solid; }
    .msg.ok  { background: var(--ok-bg);  color: var(--ok-fg);  border-color: var(--ok-bd); }
    .msg.err { background: var(--err-bg); color: var(--err-fg); border-color: var(--err-bd); }

    .back {
        display: inline-flex; align-items: center; gap: 12px;
        margin-top: 28px; padding: 12px 22px;
        border: 1px solid var(--border); border-radius: 6px;
        background: var(--surface); color: var(--text);
        font-size: 20px; font-weight: 500; font-family: inherit; cursor: pointer;
    }
    .back:hover { border-color: var(--accent); }
    .back svg { width: 22px; height: 22px; }

    footer { background: var(--footer); color: var(--muted); border-top: 1px solid var(--border); padding: 22px 28px; font-size: 18px; font-weight: 500; }

    .msg.wait { background: var(--wait-bg); color: var(--wait-fg); border-color: var(--wait-bd); word-break: break-all; }
    .msg.wait a { color: inherit; font-weight: 700; }
    .hint { color: var(--muted); margin: 0 0 18px; }

    .hidden { display: none; }
</style>
</head>
<body>

<button id="themeBtn" class="theme-btn theme-fixed" type="button" onclick="toggleTheme()"></button>

<!-- ================= SIGN IN ================= -->
<main id="view-signin" class="<?= $view === 'signin' ? '' : 'hidden' ?>">
    <h1>Sign in to continue</h1>

    <div class="card">
        <?php if ($loginMessage !== ""): ?>
            <div class="msg <?= e($messageType) ?>"><?= e($loginMessage) ?></div>
        <?php elseif ($view === 'signin' && $message !== ""): ?>
            <div class="msg <?= e($messageType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label for="login_email">Email</label>
                <input type="email" id="login_email" name="login_email" required>
            </div>

            <div class="field">
                <label for="login_password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="login_password" name="login_password" required>
                    <button type="button" class="eye" onclick="togglePass('login_password')" aria-label="Show or hide password">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 5C6.5 5 2.7 9 1 12c1.7 3 5.5 7 11 7s9.3-4 11-7c-1.7-3-5.5-7-11-7zm0 11.5A4.5 4.5 0 1 1 12 7.5a4.5 4.5 0 0 1 0 9zm0-7A2.5 2.5 0 1 0 12 14.5 2.5 2.5 0 0 0 12 9.5z"/></svg>
                    </button>
                </div>
            </div>

            <a class="forgot" href="#" onclick="show('forgot'); return false;">Forgot password?</a>

            <button type="submit" name="login" class="btn">Sign in</button>
        </form>

        <p class="center below">Don't have an account?
            <button type="button" class="link" onclick="show('signup')">Sign up</button>
        </p>
    </div>

    <button type="button" class="back" onclick="location.href='movies.php'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>
        Back
    </button>
</main>

<!-- ================= SIGN UP ================= -->
<main id="view-signup" class="<?= $view === 'signup' ? '' : 'hidden' ?>">
    <h1>Create your account</h1>

    <div class="card">
        <?php if ($view === 'signup' && $message !== ""): ?>
            <div class="msg <?= e($messageType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="eye" onclick="togglePass('password')" aria-label="Show or hide password">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 5C6.5 5 2.7 9 1 12c1.7 3 5.5 7 11 7s9.3-4 11-7c-1.7-3-5.5-7-11-7zm0 11.5A4.5 4.5 0 1 1 12 7.5a4.5 4.5 0 0 1 0 9zm0-7A2.5 2.5 0 1 0 12 14.5 2.5 2.5 0 0 0 12 9.5z"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" name="signup" class="btn">Sign up</button>
        </form>

        <p class="center below">Already have an account?
            <button type="button" class="link" onclick="show('signin')">Sign in</button>
        </p>
    </div>

    <button type="button" class="back" onclick="location.href='movies.php'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>
        Back
    </button>
</main>

<!-- ================= FORGOT PASSWORD ================= -->
<main id="view-forgot" class="<?= $view === 'forgot' ? '' : 'hidden' ?>">
    <h1>Forgot your password?</h1>

    <div class="card">
        <p class="hint">Enter the email of your account and we'll give you a link to reset your password.</p>

        <?php if ($view === 'forgot' && $fMessage !== ""): ?>
            <div class="msg <?= e($fType) ?>"><?= e($fMessage) ?></div>
        <?php endif; ?>
        <?php if ($demoLink !== ""): ?>
            <div class="msg wait"><b>Demo mode:</b> localhost can't send emails yet, so here is your link (valid for <?= (int)$EXPIRY_MIN ?> minutes):<br><br><a href="<?= e($demoLink) ?>"><?= e($demoLink) ?></a></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label for="forgot_email">Email</label>
                <input type="email" id="forgot_email" name="forgot_email" required value="<?= e($_POST['forgot_email'] ?? '') ?>">
            </div>
            <button type="submit" name="forgot" class="btn">Send reset link</button>
        </form>

        <p class="center below">Remembered it?
            <button type="button" class="link" onclick="show('signin')">Sign in</button>
        </p>
    </div>
</main>

<!-- ================= NEW PASSWORD ================= -->
<main id="view-reset" class="<?= $view === 'reset' ? '' : 'hidden' ?>">
    <h1>Set a new password</h1>

    <div class="card">
        <p class="hint">For <b><?= e($resetEmail) ?></b></p>

        <?php if ($view === 'reset' && $fMessage !== ""): ?>
            <div class="msg <?= e($fType) ?>"><?= e($fMessage) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="reset_token" value="<?= e($resetToken) ?>">

            <div class="field">
                <label for="new_password">New password</label>
                <div class="input-wrap">
                    <input type="password" id="new_password" name="new_password" required minlength="<?= (int)$MIN_LEN ?>">
                    <button type="button" class="eye" onclick="togglePass('new_password')" aria-label="Show or hide password">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 5C6.5 5 2.7 9 1 12c1.7 3 5.5 7 11 7s9.3-4 11-7c-1.7-3-5.5-7-11-7zm0 11.5A4.5 4.5 0 1 1 12 7.5a4.5 4.5 0 0 1 0 9zm0-7A2.5 2.5 0 1 0 12 14.5 2.5 2.5 0 0 0 12 9.5z"/></svg>
                    </button>
                </div>
            </div>

            <div class="field">
                <label for="new_password2">Repeat password</label>
                <input type="password" id="new_password2" name="new_password2" required minlength="<?= (int)$MIN_LEN ?>">
            </div>

            <button type="submit" name="reset" class="btn">Save new password</button>
        </form>
    </div>
</main>

<footer> </footer>

<script>
function show(name) {
    ["signin", "signup", "forgot", "reset"].forEach(function (v) {
        document.getElementById("view-" + v).classList.toggle("hidden", v !== name);
    });
    window.scrollTo(0, 0);
}
function togglePass(id) {
    var p = document.getElementById(id);
    p.type = p.type === "password" ? "text" : "password";
}
</script>

</body>
</html>
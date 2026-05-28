<?php
/**
 * ONE-TIME SETUP SCRIPT
 * ─────────────────────────────────────────────────────────────────
 * Run this ONCE after importing rooms.sql to create the admin account.
 * DELETE THIS FILE immediately after running it.
 *
 * Access: http://yourdomain.com/setup.php
 * ─────────────────────────────────────────────────────────────────
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Safety: block if admin already exists
$existing = dbFetchOne("SELECT id FROM admin LIMIT 1");
if ($existing) {
    die('<h2 style="color:red;font-family:sans-serif;">⛔ Setup already complete. DELETE this file immediately.</h2>');
}

$done    = false;
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($username) < 3)          $error = 'Username must be at least 3 characters.';
    elseif (strlen($password) < 10)     $error = 'Password must be at least 10 characters.';
    elseif ($password !== $confirm)     $error = 'Passwords do not match.';
    else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        dbInsert('admin', ['username' => $username, 'password' => $hash]);
        $success = "Admin account '{$username}' created. DELETE setup.php NOW.";
        $done    = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hostel Portal – First-Time Setup</title>
<style>
  body { font-family: sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
  .box { background: #fff; border-radius: 10px; box-shadow: 0 4px 24px rgba(0,0,0,.1); padding: 2.5rem; width: 100%; max-width: 400px; }
  h2   { color: #1a3c6e; margin: 0 0 1.5rem; }
  label { display: block; font-size: .875rem; font-weight: 600; margin-bottom: .3rem; }
  input { width: 100%; padding: .65rem .9rem; border: 1.5px solid #cbd5e1; border-radius: 7px; font-size: .95rem; box-sizing: border-box; margin-bottom: 1rem; }
  button { width: 100%; padding: .75rem; background: #1a3c6e; color: #fff; border: none; border-radius: 7px; font-size: 1rem; font-weight: 600; cursor: pointer; }
  .ok  { background: #edfaf4; color: #1a7f4b; padding: 1rem; border-radius: 7px; border-left: 4px solid #1a7f4b; margin-bottom: 1rem; font-weight: 600; }
  .err { background: #fef2f2; color: #b91c1c; padding: 1rem; border-radius: 7px; border-left: 4px solid #b91c1c; margin-bottom: 1rem; }
  .warn { background: #fffbeb; color: #92400e; padding: .75rem 1rem; border-radius: 7px; margin-bottom: 1.25rem; font-size: .88rem; }
</style>
</head>
<body>
<div class="box">
  <h2>🛠 One-Time Setup</h2>
  <div class="warn">⚠️ This page will be disabled once an admin account exists. <strong>Delete setup.php after use.</strong></div>

  <?php if ($error):   ?><div class="err">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="ok">✅ <?= htmlspecialchars($success) ?><br><br><a href="<?= BASE_URL ?>/admin/login.php">Go to Admin Login →</a></div><?php endif; ?>

  <?php if (!$done): ?>
  <form method="post">
    <label for="username">Admin Username</label>
    <input type="text" name="username" id="username" required minlength="3" autocomplete="off">

    <label for="password">Admin Password</label>
    <input type="password" name="password" id="password" required minlength="10" placeholder="Min 10 characters">

    <label for="confirm">Confirm Password</label>
    <input type="password" name="confirm" id="confirm" required placeholder="Repeat password">

    <button type="submit">Create Admin Account</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>

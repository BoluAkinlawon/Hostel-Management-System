<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (isset($_SESSION['matric_number'])) {
    header('Location: /allocation.php');
    exit;
}

$error   = '';
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session token. Please refresh and try again.';
    } else {
        $matric   = strtoupper(trim($_POST['matric'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$matric || !$password) {
            $error = 'Please enter both matric number and password.';
        } else {
            // ── Rate limiting: keyed by IP + matric ─────────────────────────
            $rlKey = 'login_' . ($_SERVER['REMOTE_ADDR'] ?? '') . '_' . $matric;

            if (!rateLimitCheck($rlKey, LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINS)) {
                $error = sprintf(
                    'Too many failed attempts. Please wait %d minutes before trying again.',
                    LOGIN_LOCKOUT_MINS
                );
            } else {
                $user = dbFetchOne(
                    "SELECT id, matric_number, firstname, password FROM users WHERE matric_number = ?",
                    [$matric]
                );

                if ($user && password_verify($password, $user->password)) {
                    // ── Successful login ──────────────────────────────────
                    rateLimitReset($rlKey);
                    session_regenerate_id(true);

                    $_SESSION['user_id']       = $user->id;
                    $_SESSION['matric_number'] = $user->matric_number;
                    $_SESSION['firstname']     = $user->firstname;
                    $_SESSION['last_activity'] = time();

                    rotateCsrf();
                    header('Location: allocation.php');
                    exit;

                } else {
                    // Generic error — do not reveal whether matric exists
                    $remaining = rateLimitRemaining($rlKey, LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINS);
                    $error = $remaining > 0
                        ? "Invalid matric number or password. {$remaining} attempt(s) remaining."
                        : 'Too many failed attempts. Please wait before trying again.';
                }
            }
        }
    }
}

$pageTitle = 'Student Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:400px; margin:2rem auto;">
    <h2>🔐 Student Login</h2>
    <hr>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

        <div class="form-group">
            <label for="matric">Matric Number</label>
            <input type="text" name="matric" id="matric" required
                   autocomplete="username" placeholder="e.g. CSC/2021/001"
                   style="text-transform:uppercase;">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required
                   autocomplete="current-password" placeholder="Enter your password">
        </div>

        <button type="submit" name="submit">Login</button>

        <p class="text-center mt-2 text-muted" style="font-size:.9rem;">
            Don't have an account? <a href="register.php" style="font-weight:600;">Register here</a>
        </p>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

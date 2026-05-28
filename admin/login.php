<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Already logged in?
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $error = 'Please enter both username and password.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            $authenticated = false;

            // Check hashed password first
            if ($admin && password_verify($password, $admin->password)) {
                $authenticated = true;
            } 
            // Seamless migration: if still using old plaintext password
            elseif ($admin && $admin->password === $password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $db->prepare("UPDATE admin SET password = ? WHERE id = ?");
                $upd->execute([$hash, $admin->id]);
                $authenticated = true;
            }

            if ($authenticated) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin->id;
                $_SESSION['admin_username'] = $admin->username;
                header('Location: ' . BASE_URL . '/admin/index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}

$pageTitle = 'Admin Login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 420px; margin: 3rem auto;">
    <h2>🔐 Admin Login</h2>
    <hr>
    
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="admin" required autofocus>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter password" required>
        </div>
        
        <button type="submit">Login to Dashboard</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth_guard.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token. Please refresh.';
    } else {
        $matric   = strtoupper(trim($_POST['matric'] ?? ''));
        $newPass  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($matric === '' || $newPass === '') {
            $error = 'All fields are required.';
        } elseif (strlen($newPass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $user = dbFetchOne("SELECT id FROM users WHERE matric_number = ?", [$matric]);
            if (!$user) {
                $error = 'No student found with that matric number.';
            } else {
                $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                dbQuery("UPDATE users SET password = ? WHERE matric_number = ?", [$hash, $matric]);
                $success = "Password for {$matric} has been reset successfully.";
            }
        }
    }
}

$pageTitle = 'Reset Student Password';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem;">
    <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
    <h2 style="color:var(--primary); margin:0;">🔑 Reset Student Password</h2>
</div>

<div class="card" style="max-width:420px;">
    <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

        <div class="form-group">
            <label for="matric">Student Matric Number</label>
            <input type="text" name="matric" id="matric" required
                   style="text-transform:uppercase;" placeholder="e.g. CSC/2021/001">
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required
                   minlength="8" placeholder="Minimum 8 characters">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required
                   placeholder="Re-enter new password">
        </div>

        <button type="submit">Reset Password</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

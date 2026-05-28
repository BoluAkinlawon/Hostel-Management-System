<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$code = (int)($_GET['code'] ?? 404);
$messages = [
    403 => ['Forbidden',              'You do not have permission to access this page.'],
    404 => ['Page Not Found',         'The page you requested could not be found.'],
    500 => ['Internal Server Error',  'Something went wrong on our end. Please try again later.'],
];
[$title, $message] = $messages[$code] ?? ['Error', 'An unexpected error occurred.'];
http_response_code($code);

$pageTitle = "{$code} – {$title}";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card text-center" style="max-width:480px; margin:4rem auto; padding:3rem 2rem;">
    <div style="font-size:4rem; margin-bottom:.5rem;"><?= $code === 404 ? '🔍' : ($code === 403 ? '🚫' : '⚠️') ?></div>
    <h2 style="font-size:1.8rem;"><?= $code ?></h2>
    <p style="color:var(--text-muted); font-size:1rem; margin:.5rem 0 1.5rem;"><?= htmlspecialchars($message) ?></p>
    <div class="btn-group" style="max-width:280px; margin:0 auto;">
        <a href="<?= BASE_URL ?>/" class="btn">🏠 Home</a>
        <a href="javascript:history.back()" class="btn btn-secondary">← Back</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

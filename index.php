<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

// Redirect logged-in users
if (isset($_SESSION['matric_number'])) {
    header('Location: /allocation.php');
    exit;
}
if (isset($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$pageTitle = 'Welcome';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card text-center" style="max-width:600px; margin: 3rem auto;">
    <div style="font-size:3rem; margin-bottom:1rem;">🏛️</div>
    <h1>Hostel Allocation Portal</h1>
    <p class="text-muted mt-1" style="font-size:1.05rem; line-height:1.7;">
        Secure, automated room allocation for registered students.
        Register with your matric number to get started.
    </p>
    <div class="btn-group mt-3" style="max-width:320px; margin:1.5rem auto 0;">
        <a href="register.php" class="btn">📝 Register</a>
        <a href="login.php" class="btn btn-secondary">🔐 Login</a>
    </div>
</div>

<div class="stats-grid" style="max-width:600px; margin:0 auto;">
    <div class="stat-card">
        <div class="stat-value">18</div>
        <div class="stat-label">Blocks</div>
    </div>
    <div class="stat-card accent">
        <div class="stat-value">24</div>
        <div class="stat-label">Rooms / Block</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value">4</div>
        <div class="stat-label">Students / Room</div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

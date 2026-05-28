<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a class="brand" href="<?= BASE_URL ?>/index.php">🏠 <?= SITE_NAME ?></a>
        <div class="nav-links">
            <?php if (isset($_SESSION['matric_number'])): ?>
                <span class="nav-user">👤 <?= htmlspecialchars($_SESSION['firstname'] ?? '') ?></span>
                <a href="<?= BASE_URL ?>/allocation.php">My Room</a>
                <a href="<?= BASE_URL ?>/logout.php" class="btn-nav-logout">Logout</a>
            <?php elseif (isset($_SESSION['admin_id'])): ?>
                <span class="nav-user">🔐 Admin</span>
                <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
                <a href="<?= BASE_URL ?>/admin/logout.php" class="btn-nav-logout">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php">Login</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn-nav-register">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="main-content">
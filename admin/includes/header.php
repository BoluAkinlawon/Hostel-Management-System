<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?> (Admin)</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a class="brand" href="<?= BASE_URL ?>/admin/index.php">🏠 <?= SITE_NAME ?> · Admin</a>
        <div class="nav-links">
            <span class="nav-user">🔐 <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
            <a href="<?= BASE_URL ?>/admin/index.php?view=dashboard">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/index.php?view=students">Students</a>
            <a href="<?= BASE_URL ?>/admin/index.php?view=allocations">Allocations</a>
            <a href="<?= BASE_URL ?>/admin/index.php?view=rooms">Room Map</a>
            <a href="<?= BASE_URL ?>/admin/logout.php" class="btn-nav-logout">Logout</a>
        </div>
    </div>
</nav>

<main class="main-content">
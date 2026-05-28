<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth_guard.php';

if (!verifyCsrf($_GET['csrf'] ?? '')) {
    flash('error', 'Invalid token.');
    header('Location: /admin/allocations.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    dbQuery("DELETE FROM hostel WHERE id = ?", [$id]);
    flash('success', 'Allocation removed successfully.');
} else {
    flash('error', 'Invalid allocation ID.');
}

header('Location: /admin/allocations.php');
exit;

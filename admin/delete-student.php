<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth_guard.php';

if (!verifyCsrf($_GET['csrf'] ?? '')) {
    flash('error', 'Invalid token.');
    header('Location: /admin/students.php');
    exit;
}

$matric = trim($_GET['matric'] ?? '');
if ($matric === '') {
    flash('error', 'Invalid matric number.');
    header('Location: /admin/students.php');
    exit;
}

try {
    getDB()->beginTransaction();
    dbQuery("DELETE FROM hostel WHERE matric_number = ?", [$matric]);
    dbQuery("DELETE FROM users  WHERE matric_number = ?", [$matric]);
    getDB()->commit();
    flash('success', "Student {$matric} deleted successfully.");
} catch (\PDOException $e) {
    getDB()->rollBack();
    error_log('Delete student error: ' . $e->getMessage());
    flash('error', 'Could not delete student. Please try again.');
}

header('Location: /admin/students.php');
exit;

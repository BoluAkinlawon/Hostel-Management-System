<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . 'login.php');
exit;
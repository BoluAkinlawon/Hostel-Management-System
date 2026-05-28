<?php
declare(strict_types=1);

/**
 * Hostel Allocation System - Configuration
 * FIXED for local development
 */

// ─── Environment ─────────────────────────────────────────────────────────────
define('APP_ENV',  'development');   // CHANGE: 'development' to see errors
define('APP_DEBUG', true);           // CHANGE: true to show errors

// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'rooms');
define('DB_USER',    'root');        // CHANGE: Use root for local development
define('DB_PASS',    '');            // CHANGE: Empty password for XAMPP
define('DB_CHARSET', 'utf8mb4');

// ─── Site ─────────────────────────────────────────────────────────────────────
define('SITE_NAME',   'Hostel Allocation Portal');
define('ADMIN_EMAIL', 'admin@example.com');

// BASE_URL: auto-detects the subfolder
(function () {
    $scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $rootDir    = str_replace('\\', '/', __DIR__);
    $docRoot    = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $base       = rtrim(str_replace($docRoot, '', $rootDir), '/');
    define('BASE_URL', $base);
})();

// ─── Hostel Rules ─────────────────────────────────────────────────────────────
define('ROOM_CAPACITY',      4);
define('TOTAL_BLOCKS',       18);
define('TOTAL_ROOMS',        24);
define('SPECIAL_ROOMS',      [1, 12, 13, 24]);
define('STANDARD_ROOMS_MIN', 2);
define('STANDARD_ROOMS_MAX', 23);

// ─── Security ─────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_LIFETIME',  1800);
define('LOGIN_MAX_ATTEMPTS',  5);
define('LOGIN_LOCKOUT_MINS',  15);

// ─── Session ──────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime',  (string)SESSION_LIFETIME);
    
    if (APP_ENV === 'production') {
        ini_set('session.cookie_secure', '1');
    }
    
    session_start();
}

// Idle timeout enforcement
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['last_activity'] = time();

// ─── Error Reporting ──────────────────────────────────────────────────────────
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ─── CSRF Helpers ─────────────────────────────────────────────────────────────
function generateCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function rotateCsrf(): void {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
}

// ─── Flash Messages ───────────────────────────────────────────────────────────
function flash(string $key, string $message = ''): ?string {
    if ($message !== '') {
        $_SESSION['flash'][$key] = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        return null;
    }
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

// ─── Rate Limiting ────────────────────────────────────────────────────────────
function rateLimitCheck(string $key, int $maxAttempts, int $lockoutMinutes): bool {
    $dir  = sys_get_temp_dir() . '/hostel_rl';
    if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
    $file = $dir . '/' . md5($key) . '.json';
    
    $data = file_exists($file) ? json_decode((string)file_get_contents($file), true) : ['attempts' => 0, 'since' => time()];
    
    if (time() - $data['since'] > $lockoutMinutes * 60) {
        $data = ['attempts' => 0, 'since' => time()];
    }
    
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    $data['attempts']++;
    file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

function rateLimitReset(string $key): void {
    $dir  = sys_get_temp_dir() . '/hostel_rl';
    $file = $dir . '/' . md5($key) . '.json';
    if (file_exists($file)) { unlink($file); }
}

function rateLimitRemaining(string $key, int $maxAttempts, int $lockoutMinutes): int {
    $dir  = sys_get_temp_dir() . '/hostel_rl';
    $file = $dir . '/' . md5($key) . '.json';
    if (!file_exists($file)) { return $maxAttempts; }
    $data = json_decode((string)file_get_contents($file), true);
    if (time() - $data['since'] > $lockoutMinutes * 60) { return $maxAttempts; }
    return max(0, $maxAttempts - $data['attempts']);
}
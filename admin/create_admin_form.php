<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $db = getDB();
        
        // Check if username exists
        $check = $db->prepare("SELECT id FROM admin WHERE username = ?");
        $check->execute([$username]);
        
        if ($check->fetch()) {
            $error = 'Username already exists';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
            $message = "Admin '$username' created successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Admin Account</title>
    <style>
        body { font-family: Arial; background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 10px; width: 350px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 8px; margin: 8px 0 15px; border: 1px solid #ccc; border-radius: 5px; }
        button { background: #1a3c6e; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        button:hover { background: #2e5fa3; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        h2 { margin-top: 0; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Create Admin Account</h2>
        
        <?php if ($message): ?>
            <div class="success">✅ <?= htmlspecialchars($message) ?></div>
            <p><a href="<?= BASE_URL ?>/admin/login.php" style="color: #1a3c6e;">→ Go to Login</a></p>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <label>Username:</label>
            <input type="text" name="username" required>
            
            <label>Password:</label>
            <input type="password" name="password" required>
            
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>
            
            <button type="submit">Create Admin</button>
        </form>
    </div>
</body>
</html>
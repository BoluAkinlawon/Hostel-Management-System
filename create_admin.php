<?php
require_once 'config.php';
require_once 'db.php';

echo "<h2>Create Admin Account</h2>";

try {
    $db = getDB();
    
    // Check if admin already exists
    $check = $db->query("SELECT COUNT(*) as count FROM admin")->fetch();
    
    if ($check->count > 0) {
        echo "<p style='color: orange;'>⚠️ Admin account already exists!</p>";
        echo "<p>Current admins:</p>";
        $admins = $db->query("SELECT id, username, created_at FROM admin")->fetchAll();
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>ID: {$admin->id}, Username: {$admin->username}, Created: {$admin->created_at}</li>";
        }
        echo "</ul>";
    } else {
        // Create new admin
        $password = 'admin123';
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $hash]);
        
        echo "<p style='color: green;'>✅ Admin account created successfully!</p>";
        echo "<p><strong>Username:</strong> admin<br>";
        echo "<strong>Password:</strong> admin123</p>";
    }
    
    echo "<p><a href='" . BASE_URL . "/admin/login.php'>Go to Admin Login →</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
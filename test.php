<?php
// test.php - DELETE AFTER TESTING
require_once 'config.php';
require_once 'db.php';

echo "<h2>Testing Database Connection</h2>";

try {
    $db = getDB();
    echo "<p style='color:green'>✓ Connected to database 'rooms' successfully!</p>";
    
    // Test query
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch();
    echo "<p>✓ Users in database: " . $row['count'] . "</p>";
    
    echo "<h3>Your configuration:</h3>";
    echo "<ul>";
    echo "<li>DB_HOST: " . DB_HOST . "</li>";
    echo "<li>DB_NAME: " . DB_NAME . "</li>";
    echo "<li>DB_USER: " . DB_USER . "</li>";
    echo "<li>BASE_URL: " . BASE_URL . "</li>";
    echo "</ul>";
    
    echo "<p><strong>System is ready!</strong> Go to <a href='" . BASE_URL . "/register.php'>Register Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
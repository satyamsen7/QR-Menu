<?php
session_start();

// Clear session
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Remove session from database
    $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    
    // Clear cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to home page
header('Location: /');
exit();
?> 
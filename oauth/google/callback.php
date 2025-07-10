<?php
session_start();

// Include required files
require_once '../../config/database.php';
require_once '../../config/oauth.php';

// Get the authorization code from Google
$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    // Handle OAuth error
    header('Location: ../../login?error=oauth_error&message=' . urlencode($error));
    exit();
}

if (empty($code)) {
    // No authorization code received
    header('Location: ../../login?error=no_code');
    exit();
}

try {
    // Exchange authorization code for access token
    $token_data = exchangeGoogleCodeForToken($code);
    
    if (!isset($token_data['access_token'])) {
        throw new Exception('Failed to get access token');
    }
    
    // Get user information from Google
    $user_info = getGoogleUserInfo($token_data['access_token']);
    
    if (!isset($user_info['email'])) {
        throw new Exception('Failed to get user email from Google');
    }
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user already exists
    $stmt = $db->prepare("SELECT u.*, v.is_setup_complete FROM users u LEFT JOIN vendors v ON u.id = v.user_id WHERE u.email = ?");
    $stmt->execute([$user_info['email']]);
    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_user) {
        // User exists, log them in
        $_SESSION['user_id'] = $existing_user['id'];
        $_SESSION['user_name'] = $existing_user['name'];
        $_SESSION['setup_complete'] = $existing_user['is_setup_complete'] ?? false;
        
        // Redirect based on setup status
        if (!$existing_user['is_setup_complete']) {
            header('Location: ../../setup');
        } else {
            header('Location: ../../dashboard');
        }
        exit();
    } else {
        // Create new user
        $name = $user_info['name'] ?? $user_info['email'];
        $email = $user_info['email'];
        
        // Generate a random password for OAuth users
        $password = bin2hex(random_bytes(16));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashed_password])) {
            $user_id = $db->lastInsertId();
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['setup_complete'] = false;
            
            // Redirect to setup
            header('Location: ../../setup');
            exit();
        } else {
            throw new Exception('Failed to create user account');
        }
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('Google OAuth Error: ' . $e->getMessage());
    
    // Redirect to login with error
    header('Location: ../../login?error=oauth_failed&message=' . urlencode($e->getMessage()));
    exit();
}
?> 
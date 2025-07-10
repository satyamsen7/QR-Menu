<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/oauth.php';

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
    // Check if Google OAuth is properly configured
    if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID_HERE') {
        throw new Exception('Google OAuth is not properly configured. Please contact the administrator.');
    }
    
    // Exchange authorization code for access token
    $token_data = exchangeGoogleCodeForToken($code);
    
    if (!isset($token_data['access_token'])) {
        throw new Exception('Failed to get access token: ' . ($token_data['error_description'] ?? 'Unknown error'));
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
        $_SESSION['login_method'] = 'google_sso'; // Mark as Google SSO user
        
        // For Google SSO users, create a long-term session (remember me)
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+365 days')); // 1 year session for SSO users
        
        // Clear any existing sessions for this user
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$existing_user['id']]);
        
        // Insert new long-term session
        $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$existing_user['id'], $token, $expires]);
        
        // Set remember me cookie for 1 year
        setcookie('remember_token', $token, time() + (365 * 24 * 60 * 60), '/');
        
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
            $_SESSION['login_method'] = 'google_sso'; // Mark as Google SSO user
            
            // For new Google SSO users, also create a long-term session
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+365 days')); // 1 year session for SSO users
            
            // Insert new long-term session
            $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $token, $expires]);
            
            // Set remember me cookie for 1 year
            setcookie('remember_token', $token, time() + (365 * 24 * 60 * 60), '/');
            
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
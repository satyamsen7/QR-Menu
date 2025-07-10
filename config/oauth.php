<?php
/**
 * OAuth/SSO Configuration
 * 
 * To get your Google OAuth credentials:
 * 1. Go to https://console.developers.google.com/
 * 2. Create a new project or select existing one
 * 3. Enable Google+ API and Google OAuth2 API
 * 4. Go to Credentials
 * 5. Create OAuth 2.0 Client ID
 * 6. Add authorized redirect URIs:
 *    - http://localhost/QR-Menu/oauth/google/callback (for local development)
 *    - https://yourdomain.com/QR-Menu/oauth/google/callback (for production)
 * 7. Copy the Client ID and Client Secret below
 */

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'xxxxxxx');
define('GOOGLE_CLIENT_SECRET', 'xxxxxxx');
define('GOOGLE_REDIRECT_URI', 'http://localhost/QR-Menu/oauth/google/callback'); // Update for production

// Facebook OAuth Configuration (Future Implementation)
define('FACEBOOK_APP_ID', 'YOUR_FACEBOOK_APP_ID_HERE');
define('FACEBOOK_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET_HERE');
define('FACEBOOK_REDIRECT_URI', 'http://localhost/QR-Menu/oauth/facebook/callback');

// GitHub OAuth Configuration (Future Implementation)
define('GITHUB_CLIENT_ID', 'YOUR_GITHUB_CLIENT_ID_HERE');
define('GITHUB_CLIENT_SECRET', 'YOUR_GITHUB_CLIENT_SECRET_HERE');
define('GITHUB_REDIRECT_URI', 'http://localhost/QR-Menu/oauth/github/callback');

// OAuth Helper Functions
function getGoogleAuthUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'scope' => 'email profile',
        'response_type' => 'code',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function getFacebookAuthUrl() {
    $params = [
        'client_id' => FACEBOOK_APP_ID,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'scope' => 'email',
        'response_type' => 'code'
    ];
    
    return 'https://www.facebook.com/v12.0/dialog/oauth?' . http_build_query($params);
}

function getGithubAuthUrl() {
    $params = [
        'client_id' => GITHUB_CLIENT_ID,
        'redirect_uri' => GITHUB_REDIRECT_URI,
        'scope' => 'user:email',
        'response_type' => 'code'
    ];
    
    return 'https://github.com/login/oauth/authorize?' . http_build_query($params);
}

// Google OAuth Token Exchange
function exchangeGoogleCodeForToken($code) {
    $data = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => GOOGLE_REDIRECT_URI
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
    
    return json_decode($result, true);
}

// Get Google User Info
function getGoogleUserInfo($access_token) {
    $url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;
    $result = file_get_contents($url);
    
    return json_decode($result, true);
}

// Facebook OAuth Token Exchange
function exchangeFacebookCodeForToken($code) {
    $data = [
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'code' => $code,
        'redirect_uri' => FACEBOOK_REDIRECT_URI
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents('https://graph.facebook.com/v12.0/oauth/access_token', false, $context);
    
    return json_decode($result, true);
}

// Get Facebook User Info
function getFacebookUserInfo($access_token) {
    $url = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . $access_token;
    $result = file_get_contents($url);
    
    return json_decode($result, true);
}

// GitHub OAuth Token Exchange
function exchangeGithubCodeForToken($code) {
    $data = [
        'client_id' => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code' => $code,
        'redirect_uri' => GITHUB_REDIRECT_URI
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents('https://github.com/login/oauth/access_token', false, $context);
    
    return json_decode($result, true);
}

// Get GitHub User Info
function getGithubUserInfo($access_token) {
    $url = 'https://api.github.com/user';
    $options = [
        'http' => [
            'header' => "Authorization: token $access_token\r\nUser-Agent: QR-Menu-System\r\n",
            'method' => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return json_decode($result, true);
}
?> 
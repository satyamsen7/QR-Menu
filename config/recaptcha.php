<?php
/**
 * Google reCAPTCHA Configuration
 * 
 * To get your reCAPTCHA keys:
 * 1. Go to https://www.google.com/recaptcha/admin
 * 2. Create a new site
 * 3. Choose reCAPTCHA v2 "I'm not a robot" Checkbox
 * 4. Add your domain(s)
 * 5. Copy the Site Key and Secret Key below
 */

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', 'xxx'); // Replace with your Site Key
define('RECAPTCHA_SECRET_KEY', 'xxx'); // Replace with your Secret Key

// reCAPTCHA Verification Function
function verifyRecaptcha($recaptchaResponse) {
    if (empty($recaptchaResponse)) {
        return false;
    }
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    
    return $response['success'] ?? false;
}
?> 
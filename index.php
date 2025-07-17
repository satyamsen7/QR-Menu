<?php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Clean URL routing
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Auto-detect base path
$base_path = dirname($script_name);
$base_path = ($base_path === '/') ? '' : $base_path . '/';

// Remove base path from request URI
$path = str_replace($base_path, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Split path into segments
$segments = explode('/', $path);
$page = $segments[0] ?? 'home';

// Handle dashboard sub-routes
if ($page === 'dashboard' && isset($segments[1])) {
    $page = 'dashboard/' . $segments[1];
}

// Define valid routes
$routes = [
    '' => 'home',
    'home' => 'home',
    'register' => 'register',
    'login' => 'login',
    'forgot-password' => 'forgot-password',
    'reset-password' => 'reset-password',
    'setup' => 'setup',
    'dashboard' => 'dashboard',
    'dashboard/menu' => 'menu-builder',
    'dashboard/qr' => 'qr-generator',
    'profile' => 'profile',
    'terms' => 'terms',
    'logout' => 'logout'
];

// OAuth route handling
if ($page === 'oauth' && isset($segments[1], $segments[2])) {
    $provider = $segments[1];
    $action = $segments[2];

    if ($provider === 'google' && $action === 'callback') {
        require_once 'oauth/google/callback.php';
        exit();
    }
}

// Determine which page to load
$page_to_load = $routes[$page] ?? '404';

// Handle public menus by username (if no valid route match)
if ($page_to_load === '404' && !empty($page) && preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
    $page_to_load = 'public-menu';
    $_GET['username'] = $page;
}

// Protect certain pages (require login)
$protected_pages = ['dashboard', 'menu-builder', 'qr-generator', 'setup', 'profile'];
if (in_array($page_to_load, $protected_pages) && !isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . 'login');
    exit();
}

// Handle logout
if ($page_to_load === 'logout') {
    require_once 'logout.php';
    exit();
}

// Include page content
$page_file = "pages/{$page_to_load}.php";
if (file_exists($page_file)) {
    require_once $page_file;
} else {
    require_once "pages/404.php";
}
?>

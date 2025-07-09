<?php
session_start();

// Clean URL routing
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Auto-detect base path
$base_path = dirname($script_name);
if ($base_path === '/') {
    $base_path = '';
} else {
    $base_path .= '/';
}

// Remove base path from request URI
$path = str_replace($base_path, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Split path into segments
$segments = explode('/', $path);
$page = $segments[0] ?? 'home';

// Handle dashboard routes
if ($page === 'dashboard' && isset($segments[1])) {
    $page = 'dashboard/' . $segments[1];
}

// Define routes
$routes = [
    '' => 'home',
    'home' => 'home',
    'register' => 'register',
    'login' => 'login',
    'setup' => 'setup',
    'dashboard' => 'dashboard',
    'dashboard/menu' => 'menu-builder',
    'dashboard/qr' => 'qr-generator',
    'profile' => 'profile',
    'terms' => 'terms',
    'logout' => 'logout'
];

// Get the page to load
$page_to_load = $routes[$page] ?? '404';

// Special handling for public menu pages (username in URL)
if ($page_to_load === '404' && !empty($page) && preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
    $page_to_load = 'public-menu';
    $_GET['username'] = $page;
}

// Check authentication for protected pages
$protected_pages = ['dashboard', 'menu-builder', 'qr-generator', 'setup', 'profile'];
if (in_array($page_to_load, $protected_pages) && !isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . 'login');
    exit();
}

// Handle logout
if ($page_to_load === 'logout') {
    include 'logout.php';
    exit();
}

// Load the appropriate page
$page_file = "pages/{$page_to_load}.php";
if (file_exists($page_file)) {
    include $page_file;
} else {
    include "pages/404.php";
}
?> 
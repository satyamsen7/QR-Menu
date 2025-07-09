<?php
// Completely standalone logo file - no includes, no routing
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}

// Direct database connection
$host = 'localhost';
$db_name = 'qr_menu_system';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get vendor ID from user ID
    $stmt = $db->prepare("SELECT id FROM vendors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendor) {
        http_response_code(404);
        exit('Vendor not found');
    }

    // Get logo data from database
    $stmt = $db->prepare("SELECT logo_data, logo_type FROM vendors WHERE id = ?");
    $stmt->execute([$vendor['id']]);
    $logo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$logo || !$logo['logo_data']) {
        // Return a default placeholder image
        header('Content-Type: image/svg+xml');
        echo '<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" fill="#f3f4f6"/>
            <text x="50" y="50" font-family="Arial" font-size="12" fill="#9ca3af" text-anchor="middle" dy=".3em">No Logo</text>
        </svg>';
        exit;
    }

    // Set appropriate headers
    header('Content-Type: ' . $logo['logo_type']);
    header('Cache-Control: public, max-age=31536000');
    header('Content-Length: ' . strlen($logo['logo_data']));

    // Output the binary data
    echo $logo['logo_data'];
    
} catch (Exception $e) {
    // Return error placeholder
    header('Content-Type: image/svg+xml');
    echo '<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
        <rect width="100" height="100" fill="#fee2e2"/>
        <text x="50" y="50" font-family="Arial" font-size="10" fill="#dc2626" text-anchor="middle" dy=".3em">Error</text>
    </svg>';
}
exit;
?> 
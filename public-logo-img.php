<?php
// Completely standalone public logo file - no includes, no routing
// Get username from URL parameter
$username = $_GET['username'] ?? '';

if (empty($username)) {
    http_response_code(404);
    exit('Username required');
}

// Direct database connection
$host = 'sql309.infinityfree.com';
$db_name = 'if0_39433696_QR_Menu';
$username_db = 'if0_39433696';
$password = 'vUj3t1ckZKJzmi';

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username_db, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get vendor ID from username
    $stmt = $db->prepare("SELECT id FROM vendors WHERE username = ?");
    $stmt->execute([$username]);
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
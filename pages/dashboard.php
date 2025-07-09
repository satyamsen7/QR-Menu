<?php
$page_title = 'Dashboard - QR Menu System';

// Check if user is logged in and setup is complete
if (!isset($_SESSION['user_id'])) {
    header('Location: /QR-Menu/login');
    exit();
}

if (!$_SESSION['setup_complete']) {
    header('Location: /QR-Menu/setup');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get vendor information
$stmt = $db->prepare("SELECT *, logo_data, logo_type FROM vendors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    header('Location: /QR-Menu/setup');
    exit();
}

// Get QR scan statistics
$stmt = $db->prepare("SELECT COUNT(*) as total_scans FROM qr_scans WHERE vendor_id = ?");
$stmt->execute([$vendor['id']]);
$scan_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent scans (last 7 days)
$stmt = $db->prepare("SELECT COUNT(*) as recent_scans FROM qr_scans WHERE vendor_id = ? AND scanned_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$vendor['id']]);
$recent_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get menu statistics
$stmt = $db->prepare("SELECT 
    (SELECT COUNT(*) FROM menu_categories WHERE vendor_id = ?) as total_categories,
    (SELECT COUNT(*) FROM menu_items mi JOIN menu_categories mc ON mi.category_id = mc.id WHERE mc.vendor_id = ?) as total_items");
$stmt->execute([$vendor['id'], $vendor['id']]);
$menu_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent menu items for preview
$stmt = $db->prepare("SELECT mc.name as category_name, mi.name as item_name, mi.price 
                      FROM menu_items mi 
                      JOIN menu_categories mc ON mi.category_id = mc.id 
                      WHERE mc.vendor_id = ? 
                      ORDER BY mi.created_at DESC 
                      LIMIT 5");
$stmt->execute([$vendor['id']]);
$recent_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
            </h1>
            <p class="mt-2 text-gray-600">
                Manage your digital menu and track customer engagement
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total QR Scans -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-qrcode text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Total QR Scans
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php echo number_format($scan_stats['total_scans']); ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Scans -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Scans (Last 7 Days)
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php echo number_format($recent_stats['recent_scans']); ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Items -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-utensils text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Menu Items
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php echo number_format($menu_stats['total_items']); ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Menu Management -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Menu Management</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Categories: <?php echo $menu_stats['total_categories']; ?></p>
                            <p class="text-sm text-gray-600">Items: <?php echo $menu_stats['total_items']; ?></p>
                        </div>
                        <a href="/QR-Menu/dashboard/menu" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-edit mr-2"></i>Edit Menu
                        </a>
                    </div>
                    
                    <?php if (!empty($recent_items)): ?>
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-gray-700">Recent Items:</p>
                            <?php foreach ($recent_items as $item): ?>
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span><?php echo htmlspecialchars($item['item_name']); ?></span>
                                    <span>₹<?php echo number_format($item['price'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No menu items yet. <a href="/QR-Menu/dashboard/menu" class="text-blue-600 hover:text-blue-500">Create your first menu!</a></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- QR Code -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">QR Code</h3>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="mb-4">
                            <p class="text-sm text-gray-600">Your menu URL:</p>
                            <p class="text-sm font-medium text-blue-600">
                                qr-ss.com/<?php echo htmlspecialchars($vendor['username']); ?>
                            </p>
                        </div>
                        <a href="/QR-Menu/dashboard/qr" 
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-qrcode mr-2"></i>View QR Code
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Business Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Business Details</h4>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($vendor['business_name']); ?></p>
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($vendor['username']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($vendor['address']); ?></p>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Logo</h4>
                        <?php if (!empty($vendor['logo_data'])): ?>
                            <img src="/QR-Menu/logo-img.php" 
                                 alt="Business Logo" 
                                 class="w-20 h-20 object-cover rounded-lg border">
                        <?php else: ?>
                            <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center border">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
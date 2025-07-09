<?php
// Get username from URL
$username = $_GET['username'] ?? '';

if (empty($username)) {
    header('Location: /QR-Menu/');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get vendor information
$stmt = $db->prepare("SELECT *, logo_data, logo_type FROM vendors WHERE username = ?");
$stmt->execute([$username]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    // Vendor not found
    http_response_code(404);
    $page_title = 'Menu Not Found';
    include 'includes/header.php';
    ?>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center">
        <div class="text-center">
            <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Menu Not Found</h1>
            <p class="text-gray-600">The menu you're looking for doesn't exist or has been removed.</p>
            <a href="/QR-Menu/" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                Go Home
            </a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
}

// Track QR scan
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$stmt = $db->prepare("INSERT INTO qr_scans (vendor_id, ip_address, user_agent) VALUES (?, ?, ?)");
$stmt->execute([$vendor['id'], $ip_address, $user_agent]);

// Get menu data
$stmt = $db->prepare("SELECT mc.name as category_name, mc.sort_order as category_order,
                             mi.name as item_name, mi.price, mi.sort_order as item_order
                      FROM menu_categories mc
                      LEFT JOIN menu_items mi ON mc.id = mi.category_id
                      WHERE mc.vendor_id = ?
                      ORDER BY mc.sort_order, mi.sort_order");
$stmt->execute([$vendor['id']]);
$menu_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize menu by categories
$menu_categories = [];
foreach ($menu_data as $row) {
    $category_name = $row['category_name'];
    if (!isset($menu_categories[$category_name])) {
        $menu_categories[$category_name] = [];
    }
    if ($row['item_name']) {
        $menu_categories[$category_name][] = [
            'name' => $row['item_name'],
            'price' => $row['price']
        ];
    }
}

$page_title = $vendor['business_name'] . ' - Digital Menu';
$hide_nav = true; // Hide navigation for public menu
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <div class="flex items-center space-x-4">
                <?php if (!empty($vendor['logo_data'])): ?>
                    <img src="/QR-Menu/public-logo-img.php?username=<?php echo urlencode($vendor['username']); ?>" 
                         alt="<?php echo htmlspecialchars($vendor['business_name']); ?>" 
                         class="w-16 h-16 object-cover rounded-lg">
                <?php else: ?>
                    <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-store text-2xl text-blue-600"></i>
                    </div>
                <?php endif; ?>
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <?php echo htmlspecialchars($vendor['business_name']); ?>
                    </h1>
                    <p class="text-gray-600">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <?php echo htmlspecialchars($vendor['address']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Content -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        <?php if (empty($menu_categories)): ?>
            <!-- Empty Menu State -->
            <div class="text-center py-12">
                <i class="fas fa-utensils text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Menu Coming Soon</h2>
                <p class="text-gray-600">This restaurant is currently setting up their digital menu.</p>
            </div>
        <?php else: ?>
            <!-- Menu Categories -->
            <div class="space-y-8">
                <?php foreach ($menu_categories as $category_name => $items): ?>
                    <div class="bg-white rounded-lg shadow-sm border">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">
                                <?php echo htmlspecialchars($category_name); ?>
                            </h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($items as $item): ?>
                                <div class="px-6 py-4 flex justify-between items-center">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h3>
                                    </div>
                                    <div class="ml-4">
                                        <span class="text-lg font-semibold text-gray-900">
                                            ₹<?php echo number_format($item['price'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="bg-white border-t mt-12">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Powered by <span class="font-medium text-blue-600">QR Menu System</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Scan the QR code to view this menu anytime
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Share Menu Modal -->
<div id="shareModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Share Menu</h3>
                <button onclick="closeShareModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Menu URL</label>
                    <div class="flex">
                        <input type="text" value="<?php echo htmlspecialchars('https://qr-ss.com/' . $vendor['username']); ?>" 
                               class="flex-1 border border-gray-300 rounded-l-md px-3 py-2 text-sm" readonly>
                        <button onclick="copyMenuUrl()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-md text-sm">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex space-x-2">
                    <button onclick="shareOnWhatsApp()" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                    </button>
                    <button onclick="shareOnTelegram()" 
                            class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fab fa-telegram mr-2"></i>Telegram
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<div class="fixed bottom-6 right-6 z-40">
    <button onclick="openShareModal()" 
            class="bg-blue-600 hover:bg-blue-700 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center">
        <i class="fas fa-share-alt text-xl"></i>
    </button>
</div>

<script>
function openShareModal() {
    document.getElementById('shareModal').classList.remove('hidden');
}

function closeShareModal() {
    document.getElementById('shareModal').classList.add('hidden');
}

function copyMenuUrl() {
    const url = '<?php echo htmlspecialchars('https://qr-ss.com/' . $vendor['username']); ?>';
    navigator.clipboard.writeText(url).then(function() {
        // Show success message
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        button.classList.add('bg-green-600');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 2000);
    });
}

function shareOnWhatsApp() {
    const url = encodeURIComponent('<?php echo htmlspecialchars('https://qr-ss.com/' . $vendor['username']); ?>');
    const text = encodeURIComponent('Check out the menu at <?php echo htmlspecialchars($vendor['business_name']); ?>');
    window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
}

function shareOnTelegram() {
    const url = encodeURIComponent('<?php echo htmlspecialchars('https://qr-ss.com/' . $vendor['username']); ?>');
    const text = encodeURIComponent('Check out the menu at <?php echo htmlspecialchars($vendor['business_name']); ?>');
    window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
}

// Close modal when clicking outside
document.getElementById('shareModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeShareModal();
    }
});

// Add smooth scrolling for better UX
document.addEventListener('DOMContentLoaded', function() {
    // Add some animation to menu items
    const menuItems = document.querySelectorAll('.divide-y > div');
    menuItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.classList.add('animate-fade-in');
    });
});

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?> 
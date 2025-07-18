<?php
// Add function to split long words in menu item names
function split_long_words($text) {
    return preg_replace_callback('/\\b\\w{8,}\\b/u', function($matches) {
        $word = $matches[0];
        return substr($word, 0, 6) . '- ' . substr($word, 6);
    }, $text);
}
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
    // Vendor not found - keeping the same elegant error page
    http_response_code(404);
    $page_title = 'Menu Not Found';
    include 'includes/header.php';
    ?>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center">
        <div class="text-center bg-white/10 backdrop-blur-lg rounded-2xl p-12 border border-white/20">
            <div class="w-20 h-20 mx-auto mb-6 bg-amber-500/20 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-3xl text-amber-400"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-4">Menu Not Found</h1>
            <p class="text-slate-300 mb-8">The menu you're looking for doesn't exist or has been removed.</p>
            <a href="/" class="inline-block bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white px-8 py-3 rounded-full font-semibold transition-all duration-300 transform hover:scale-105">
                Return Home
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
                             mi.name as item_name, mi.price_full, mi.price_half, mi.has_half_price, mi.sort_order as item_order
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
            'price_full' => $row['price_full'],
            'price_half' => $row['price_half'],
            'has_half_price' => $row['has_half_price']
        ];
    }
}

$page_title = $vendor['business_name'] . ' - Premium Menu';
$hide_nav = true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        .font-playfair { font-family: 'Playfair Display', serif; }
        .font-inter { font-family: 'Inter', sans-serif; }
        
        .menu-item-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .menu-item-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(245, 158, 11, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .menu-item-hover:hover::before {
            left: 100%;
        }
        
        .menu-item-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .gold-line {
            background: linear-gradient(90deg, transparent 0%, #f59e0b 50%, transparent 100%);
            height: 1px;
        }
        
        .premium-shadow {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.05);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .menu-category {
            background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
            border: 1px solid rgba(203, 213, 225, 0.3);
        }
        
        .menu-item {
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .menu-item:hover {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        
        .price-elegant {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .decorative-border {
            background: linear-gradient(90deg, 
                transparent 0%, 
                #e5e7eb 20%, 
                #f59e0b 50%, 
                #e5e7eb 80%, 
                transparent 100%
            );
            height: 2px;
        }
        
        .category-number {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
    </style>
</head>

<body class="font-inter bg-gradient-to-br from-slate-50 via-white to-amber-50/30 min-h-screen">
    <!-- Keep the same elegant header -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.02"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
        
        <div class="relative max-w-6xl mx-auto px-6 py-16">
            <div class="text-center">
                <div class="mb-8">
                    <?php if (!empty($vendor['logo_data'])): ?>
                        <div class="w-32 h-32 mx-auto mb-6 rounded-full overflow-hidden ring-4 ring-amber-400/30 premium-shadow">
                            <img src="/public-logo-img.php?username=<?php echo urlencode($vendor['username']); ?>"
     alt="<?php echo htmlspecialchars($vendor['business_name']); ?>"
     class="w-full h-full object-cover">

                        </div>
                    <?php else: ?>
                        <div class="w-32 h-32 mx-auto mb-6 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center ring-4 ring-amber-400/30 premium-shadow">
                            <i class="fas fa-utensils text-4xl text-white"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h1 class="font-playfair text-5xl md:text-6xl font-bold text-white mb-4 tracking-wide">
                    <?php echo htmlspecialchars($vendor['business_name']); ?>
                </h1>
                
                <div class="gold-line w-32 mx-auto mb-6"></div>
                
                <p class="text-slate-300 text-lg mb-2 flex items-center justify-center">
                    <i class="fas fa-map-marker-alt mr-3 text-amber-400"></i>
                    <?php echo htmlspecialchars($vendor['address']); ?>
                </p>
                
                <div class="flex flex-wrap justify-center gap-6 text-slate-300">
                    <?php if (!empty($vendor['phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($vendor['phone']); ?>" 
                           class="flex items-center hover:text-amber-400 transition-colors duration-300">
                            <i class="fas fa-phone mr-2"></i>
                            <?php echo htmlspecialchars($vendor['phone']); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($vendor['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($vendor['email']); ?>" 
                           class="flex items-center hover:text-amber-400 transition-colors duration-300">
                            <i class="fas fa-envelope mr-2"></i>
                            <?php echo htmlspecialchars($vendor['email']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CLEAN PREMIUM MENU CONTENT -->
    <div class="relative">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23f59e0b" fill-opacity="0.1"%3E%3Cpath d="M50 50c0-5.5-4.5-10-10-10s-10 4.5-10 10 4.5 10 10 10 10-4.5 10-10zm10 0c0-5.5-4.5-10-10-10s-10 4.5-10 10 4.5 10 10 10 10-4.5 10-10z"/%3E%3C/g%3E%3C/svg%3E')]"></div>
        </div>
        
        <div class="relative max-w-5xl mx-auto px-6 py-20">
            <?php if (empty($menu_categories)): ?>
                <!-- Enhanced Empty Menu State -->
                <div class="text-center py-24">
                    <div class="relative mb-12">
                        <div class="w-32 h-32 mx-auto bg-gradient-to-br from-amber-100 via-amber-50 to-white rounded-full flex items-center justify-center shadow-2xl border border-amber-200">
                            <i class="fas fa-utensils text-4xl text-amber-600"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-star text-white text-sm"></i>
                        </div>
                    </div>
                    <h2 class="font-playfair text-5xl font-bold text-slate-800 mb-6">Menu Coming Soon</h2>
                    <div class="decorative-border w-24 mx-auto mb-6"></div>
                    <p class="text-slate-600 text-xl max-w-2xl mx-auto leading-relaxed">
                        Our menu is being carefully prepared for you.
                    </p>
                    <div class="mt-12 text-amber-600 font-semibold">
                        <i class="fas fa-clock mr-2"></i>
                        Coming Soon
                    </div>
                </div>
            <?php else: ?>
                <!-- Premium Menu Categories -->
                <div class="space-y-20">
                    <?php 
                    $category_count = 1;
                    foreach ($menu_categories as $category_name => $items): 
                    ?>
                        <div class="animate-fade-in-up" style="animation-delay: <?php echo ($category_count - 1) * 0.3; ?>s;">
                            <!-- Elegant Category Header -->
                            <div class="text-center mb-16">
                                <div class="flex items-center justify-center mb-6">
                                    <div class="category-number mr-4">
                                        <?php echo sprintf('%02d', $category_count); ?>
                                    </div>
                                    <div class="flex-1">
                                        <h2 class="font-playfair text-4xl md:text-5xl font-bold text-slate-800 mb-3">
                                            <?php echo htmlspecialchars($category_name); ?>
                                        </h2>
                                        <div class="decorative-border w-32 mx-auto"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Clean Menu Items Grid -->
                            <div class="menu-category rounded-3xl p-8 md:p-12 shadow-2xl">
                                <div class="grid gap-6">
                                    <?php foreach ($items as $index => $item): ?>
                                        <div class="menu-item menu-item-hover rounded-2xl p-6 shadow-lg" 
                                             style="animation-delay: <?php echo ($category_count - 1) * 0.3 + $index * 0.1; ?>s;">
                                            
                                            <div class="flex items-center justify-between">
                                                <!-- Item Name -->
                                                <div class="flex-1 pr-8">
                                                    <h3 class="font-playfair text-2xl md:text-3xl font-semibold text-slate-800 leading-tight break-words">
                                                        <?php echo htmlspecialchars(split_long_words($item['name'])); ?>
                                                    </h3>
                                                    
                                                    <!-- Simple decorative dots -->
                                                    <div class="flex items-center mt-3">
                                                        <div class="w-2 h-2 bg-amber-400 rounded-full mr-2"></div>
                                                        <div class="w-1 h-1 bg-amber-300 rounded-full mr-2"></div>
                                                        <div class="w-1 h-1 bg-amber-200 rounded-full"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Clean Price Display -->
                                                <div class="text-right">
                                                    <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-2xl p-6 border border-amber-200 shadow-lg">
                                                        <div class="font-playfair text-3xl md:text-4xl font-bold price-elegant">
                                                            ₹<?php echo number_format($item['price_full'], 0); ?>
                                                        </div>
                                                        <?php if ($item['has_half_price'] && $item['price_half']): ?>
                                                            <div class="text-sm text-slate-600 mt-2">
                                                                Half: ₹<?php echo number_format($item['price_half'], 0); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Category Footer -->
                                <div class="text-center mt-12 pt-8 border-t border-slate-200">
                                    <div class="inline-flex items-center text-slate-600">
                                        <div class="w-8 h-px bg-amber-400 mr-3"></div>
                                        <i class="fas fa-utensils text-amber-500 mx-3"></i>
                                        <div class="w-8 h-px bg-amber-400 ml-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                    $category_count++;
                    endforeach; 
                    ?>
                </div>
                
                <!-- Simple Menu Footer Message -->
                <div class="text-center mt-20 py-16 bg-gradient-to-r from-amber-50 via-white to-amber-50 rounded-3xl border border-amber-100">
                    <div class="max-w-3xl mx-auto">
                        <h3 class="font-playfair text-3xl font-bold text-slate-800 mb-4">
                            Thank You for Choosing Us
                        </h3>
                        <div class="decorative-border w-24 mx-auto mb-6"></div>
                        <p class="text-slate-600 text-lg leading-relaxed">
                            We look forward to serving you an exceptional dining experience.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Keep the same premium footer -->
    <footer class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center md:text-left">
                    <h3 class="font-playfair text-2xl font-bold mb-6 text-amber-400">
                        <?php echo htmlspecialchars($vendor['business_name']); ?>
                    </h3>
                    <div class="space-y-4 text-slate-300">
                        <p class="flex items-center justify-center md:justify-start">
                            <i class="fas fa-map-marker-alt mr-3 text-amber-400 w-5"></i>
                            <?php echo htmlspecialchars($vendor['address']); ?>
                        </p>
                        <?php if (!empty($vendor['phone'])): ?>
                            <p class="flex items-center justify-center md:justify-start">
                                <i class="fas fa-phone mr-3 text-amber-400 w-5"></i>
                                <?php echo htmlspecialchars($vendor['phone']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($vendor['email'])): ?>
                            <p class="flex items-center justify-center md:justify-start">
                                <i class="fas fa-envelope mr-3 text-amber-400 w-5"></i>
                                <?php echo htmlspecialchars($vendor['email']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center">
                    
                    <div class="space-y-4">
                        <button onclick="openShareModal()" 
                                class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white px-6 py-3 rounded-full font-semibold transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-share-alt mr-2"></i>Share Our Menu
                        </button>
                        <?php if (!empty($vendor['phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($vendor['phone']); ?>" 
                               class="block w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-full font-semibold transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-phone mr-2"></i>Call for Reservations
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center md:text-right">
                    <h3 class="font-playfair text-2xl font-bold mb-6 text-amber-400">Experience</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Quality food served with passion and dedication.
                    </p>
                </div>
            </div>
            
            <div class="border-t border-slate-700 mt-12 pt-8 text-center">
                <div class="gold-line w-32 mx-auto mb-6"></div>
                <p class="text-slate-400 mb-2">
                    Powered by <span class="font-semibold text-amber-400">QR-Menu.42web.io</span>
                </p>
                <p class="text-xs text-slate-500">
                    Scan QR code to access this menu anytime • Digital dining experience
                </p>
            </div>
        </div>
    </footer>

    <!-- Keep the same share modal and floating button -->
    <div id="shareModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="glass-effect rounded-3xl max-w-md w-full p-8 premium-shadow transform transition-all duration-300 scale-95" id="modalContent">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-share-alt text-2xl text-white"></i>
                </div>
                <h3 class="font-playfair text-2xl font-bold text-slate-800 mb-2">Share Our Menu</h3>
                <p class="text-slate-600">Spread the word about our delicious offerings</p>
            </div>
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">Menu URL</label>
                    <div class="flex rounded-full overflow-hidden border border-slate-200">
                        <input type="text" value="<?php echo htmlspecialchars('https://qr-menu.42web.io/' . $vendor['username']); ?>"
                               class="flex-1 px-4 py-3 text-sm bg-slate-50 border-0 focus:outline-none" readonly>
                        <button onclick="copyMenuUrl()"
                                class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white px-6 py-3 font-semibold transition-all duration-300">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="shareOnWhatsApp()"
                            class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-3 rounded-full font-semibold transition-all duration-300 transform hover:scale-105">
                        <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                    </button>
                    <button onclick="shareOnTelegram()"
                            class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-3 rounded-full font-semibold transition-all duration-300 transform hover:scale-105">
                        <i class="fab fa-telegram mr-2"></i>Telegram
                    </button>
                </div>
                
                <button onclick="closeShareModal()" 
                        class="w-full mt-6 text-slate-600 hover:text-slate-800 py-3 font-semibold transition-colors duration-300">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div class="fixed bottom-8 right-8 z-40">
        <button onclick="openShareModal()"
                class="w-16 h-16 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-full premium-shadow flex items-center justify-center transition-all duration-300 transform hover:scale-110">
            <i class="fas fa-share-alt text-xl"></i>
        </button>
    </div>

    <script>
        function openShareModal() {
            const modal = document.getElementById('shareModal');
            const content = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }

        function closeShareModal() {
            const modal = document.getElementById('shareModal');
            const content = document.getElementById('modalContent');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function copyMenuUrl() {
            const url = '<?php echo htmlspecialchars('https://qr-menu.42web.io/' . $vendor['username']); ?>';
            navigator.clipboard.writeText(url).then(function() {
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.remove('from-amber-500', 'to-amber-600');
                button.classList.add('from-green-500', 'to-green-600');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('from-green-500', 'to-green-600');
                    button.classList.add('from-amber-500', 'to-amber-600');
                }, 2000);
            });
        }

        function shareOnWhatsApp() {
            const url = encodeURIComponent('<?php echo htmlspecialchars('https://qr-menu.42web.io/' . $vendor['username']); ?>');
            const text = encodeURIComponent('Check out the menu at <?php echo htmlspecialchars($vendor['business_name']); ?>');
            window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
        }

        function shareOnTelegram() {
            const url = encodeURIComponent('<?php echo htmlspecialchars('https://qr-menu.42web.io/' . $vendor['username']); ?>');
            const text = encodeURIComponent('Check out the menu at <?php echo htmlspecialchars($vendor['business_name']); ?>');
            window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
        }

        document.getElementById('shareModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeShareModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item-hover');
            menuItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
                item.classList.add('animate-fade-in-up');
            });
        });

        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>
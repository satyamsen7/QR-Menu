<?php
$page_title = 'Menu Builder - QR Menu System';

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
$stmt = $db->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    header('Location: /QR-Menu/setup');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_menu') {
        // Clear existing menu
        $stmt = $db->prepare("DELETE FROM menu_items WHERE category_id IN (SELECT id FROM menu_categories WHERE vendor_id = ?)");
        $stmt->execute([$vendor['id']]);
        
        $stmt = $db->prepare("DELETE FROM menu_categories WHERE vendor_id = ?");
        $stmt->execute([$vendor['id']]);
        
        // Save new menu
        $categories = json_decode($_POST['menu_data'], true);
        $sort_order = 1;
        
        foreach ($categories as $category) {
            // Insert category
            $stmt = $db->prepare("INSERT INTO menu_categories (vendor_id, name, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$vendor['id'], $category['name'], $sort_order]);
            $category_id = $db->lastInsertId();
            
            // Insert items
            $item_sort_order = 1;
            foreach ($category['items'] as $item) {
                $stmt = $db->prepare("INSERT INTO menu_items (category_id, name, price, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$category_id, $item['name'], $item['price'], $item_sort_order]);
                $item_sort_order++;
            }
            $sort_order++;
        }
        
        header('Location: /QR-Menu/dashboard/qr');
        exit();
    }
}

// Get existing menu data
$stmt = $db->prepare("SELECT mc.id as category_id, mc.name as category_name, mc.sort_order,
                             mi.id as item_id, mi.name as item_name, mi.price, mi.sort_order as item_sort_order
                      FROM menu_categories mc
                      LEFT JOIN menu_items mi ON mc.id = mi.category_id
                      WHERE mc.vendor_id = ?
                      ORDER BY mc.sort_order, mi.sort_order");
$stmt->execute([$vendor['id']]);
$menu_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize data for JavaScript
$menu_structure = [];
foreach ($menu_data as $row) {
    $category_id = $row['category_id'];
    if (!isset($menu_structure[$category_id])) {
        $menu_structure[$category_id] = [
            'id' => $category_id,
            'name' => $row['category_name'],
            'items' => []
        ];
    }
    if ($row['item_id']) {
        $menu_structure[$category_id]['items'][] = [
            'id' => $row['item_id'],
            'name' => $row['item_name'],
            'price' => $row['price']
        ];
    }
}

$menu_json = json_encode(array_values($menu_structure));

include 'includes/header.php';
?>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Menu Builder</h1>
                    <p class="mt-2 text-gray-600">Create and manage your digital menu</p>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="addCategory()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-plus mr-2"></i>Add Category
                    </button>
                    <button type="button" onclick="saveMenu()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-save mr-2"></i>Save Menu
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu Builder -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Your Menu</h3>
            </div>
            <div class="p-6">
                <form id="menuForm" method="POST">
                    <input type="hidden" name="action" value="save_menu">
                    <input type="hidden" name="menu_data" id="menuData" value="">
                    
                    <div id="menuContainer" class="space-y-6">
                        <!-- Menu categories will be dynamically added here -->
                    </div>
                    
                    <div id="emptyState" class="text-center py-12 <?php echo empty($menu_structure) ? '' : 'hidden'; ?>">
                        <i class="fas fa-utensils text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No menu items yet</h3>
                        <p class="text-gray-600 mb-4">Start by adding your first category and menu items</p>
                        <button type="button" onclick="addCategory()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Your First Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Template for category -->
<template id="categoryTemplate">
    <div class="category-item border border-gray-200 rounded-lg p-4" data-category-id="">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <i class="fas fa-grip-vertical text-gray-400 cursor-move handle"></i>
                <input type="text" class="category-name border border-gray-300 rounded-md px-3 py-2 text-lg font-medium focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Category Name" required>
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" onclick="addItem(this)" 
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                    <i class="fas fa-plus mr-1"></i>Add Item
                </button>
                <button type="button" onclick="removeCategory(this)" 
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                    <i class="fas fa-trash mr-1"></i>Remove
                </button>
            </div>
        </div>
        <div class="category-items space-y-3">
            <!-- Items will be added here -->
        </div>
    </div>
</template>

<!-- Template for item -->
<template id="itemTemplate">
    <div class="item-row flex items-center space-x-3 p-3 bg-gray-50 rounded border" data-item-id="">
        <i class="fas fa-grip-vertical text-gray-400 cursor-move handle"></i>
        <input type="text" class="item-name flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Item Name" required>
        <div class="flex items-center space-x-2">
            <span class="text-gray-600">₹</span>
            <input type="number" class="item-price w-24 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00" step="0.01" min="0" required>
        </div>
        <button type="button" onclick="removeItem(this)" 
                class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm">
            <i class="fas fa-times"></i>
        </button>
    </div>
</template>

<script>
let menuData = <?php echo $menu_json; ?>;
let categoryCounter = 0;
let itemCounter = 0;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize menu with existing data
    if (menuData.length > 0) {
        menuData.forEach(category => {
            addCategory(category);
        });
    }
    
    // Initialize drag and drop
    initializeDragAndDrop();
});

function addCategory(existingCategory = null) {
    const container = document.getElementById('menuContainer');
    const emptyState = document.getElementById('emptyState');
    const template = document.getElementById('categoryTemplate');
    
    // Hide empty state
    emptyState.classList.add('hidden');
    
    // Clone template
    const categoryElement = template.content.cloneNode(true);
    const categoryDiv = categoryElement.querySelector('.category-item');
    categoryDiv.dataset.categoryId = 'cat_' + (++categoryCounter);
    
    // Set category name if provided
    if (existingCategory) {
        categoryDiv.querySelector('.category-name').value = existingCategory.name;
        
        // Add items
        existingCategory.items.forEach(item => {
            addItem(categoryDiv.querySelector('.category-items'), item);
        });
    }
    
    container.appendChild(categoryElement);
    updateMenuData();
}

function addItem(categoryElement, existingItem = null) {
    const itemsContainer = categoryElement.closest('.category-item').querySelector('.category-items');
    const template = document.getElementById('itemTemplate');
    
    // Clone template
    const itemElement = template.content.cloneNode(true);
    const itemDiv = itemElement.querySelector('.item-row');
    itemDiv.dataset.itemId = 'item_' + (++itemCounter);
    
    // Set item data if provided
    if (existingItem) {
        itemDiv.querySelector('.item-name').value = existingItem.name;
        itemDiv.querySelector('.item-price').value = existingItem.price;
    }
    
    itemsContainer.appendChild(itemElement);
    updateMenuData();
}

function removeCategory(button) {
    if (confirm('Are you sure you want to remove this category and all its items?')) {
        button.closest('.category-item').remove();
        updateMenuData();
        
        // Show empty state if no categories
        const container = document.getElementById('menuContainer');
        if (container.children.length === 0) {
            document.getElementById('emptyState').classList.remove('hidden');
        }
    }
}

function removeItem(button) {
    button.closest('.item-row').remove();
    updateMenuData();
}

function updateMenuData() {
    const categories = [];
    document.querySelectorAll('.category-item').forEach(categoryDiv => {
        const category = {
            name: categoryDiv.querySelector('.category-name').value,
            items: []
        };
        
        categoryDiv.querySelectorAll('.item-row').forEach(itemDiv => {
            category.items.push({
                name: itemDiv.querySelector('.item-name').value,
                price: parseFloat(itemDiv.querySelector('.item-price').value) || 0
            });
        });
        
        categories.push(category);
    });
    
    menuData = categories;
    document.getElementById('menuData').value = JSON.stringify(categories);
}

function saveMenu() {
    // Validate form
    const form = document.getElementById('menuForm');
    if (!validateForm('menuForm')) {
        alert('Please fill in all required fields.');
        return;
    }
    
    // Update menu data
    updateMenuData();
    
    // Check if menu is empty
    if (menuData.length === 0 || menuData.every(cat => cat.items.length === 0)) {
        alert('Please add at least one category and item to your menu.');
        return;
    }
    
    // Submit form
    form.submit();
}

function initializeDragAndDrop() {
    // This would implement drag and drop functionality for reordering
    // For now, we'll use a simple implementation
    console.log('Drag and drop initialized');
}

// Auto-save functionality
let autoSaveTimer;
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        updateMenuData();
        console.log('Menu auto-saved');
    }, 2000);
}

// Add event listeners for auto-save
document.addEventListener('input', function(e) {
    if (e.target.matches('.category-name, .item-name, .item-price')) {
        scheduleAutoSave();
    }
});
</script>

<?php include 'includes/footer.php'; ?> 
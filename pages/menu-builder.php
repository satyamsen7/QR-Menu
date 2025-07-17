<?php
$page_title = 'Menu Builder - QR Menu System';

// Check if user is logged in and setup is complete
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit();
}

if (!$_SESSION['setup_complete']) {
    header('Location: /setup');
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
    header('Location: /setup');
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
                $stmt = $db->prepare("INSERT INTO menu_items (category_id, name, price, price_full, price_half, has_half_price, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $category_id, 
                    $item['name'], 
                    $item['price_full'], // Keep old price column for backward compatibility
                    $item['price_full'], 
                    $item['price_half'] ?? null, 
                    !empty($item['price_half']), 
                    $item_sort_order
                ]);
                $item_sort_order++;
            }
            $sort_order++;
        }
        
        header('Location: /dashboard/qr');
        exit();
    }
}

// Get existing menu data
$stmt = $db->prepare("SELECT mc.id as category_id, mc.name as category_name, mc.sort_order,
                             mi.id as item_id, mi.name as item_name, mi.price_full, mi.price_half, mi.has_half_price, mi.sort_order as item_sort_order
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
            'price_full' => $row['price_full'],
            'price_half' => $row['price_half'],
            'has_half_price' => $row['has_half_price']
        ];
    }
}

$menu_json = json_encode(array_values($menu_structure));

include 'includes/header.php';
?>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Menu Builder</h1>
                    <p class="mt-2 text-gray-600 text-base sm:text-lg">Create and manage your digital menu</p>
                </div>
            </div>
        </div>

        <!-- Menu Builder -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Your Menu</h3>
            </div>
            <div class="p-2 sm:p-6">
                <form id="menuForm" method="POST">
                    <input type="hidden" name="action" value="save_menu">
                    <input type="hidden" name="menu_data" id="menuData" value="">
                    
                    <div id="menuContainer" class="space-y-8">
                        <!-- Menu categories will be dynamically added here -->
                    </div>
                    
                    <!-- Add Category Button -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <button type="button" onclick="addCategory()" 
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-base font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Category
                        </button>
                    </div>
                    
                    <div id="emptyState" class="text-center py-16 <?php echo empty($menu_structure) ? '' : 'hidden'; ?>">
                        <i class="fas fa-utensils text-6xl text-gray-300 mb-6"></i>
                        <h3 class="text-xl font-medium text-gray-900 mb-3">No menu items yet</h3>
                        <p class="text-gray-600 mb-6 text-lg">Start by adding your first category and menu items</p>
                        <button type="button" onclick="addCategory()" 
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-base font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Your First Category
                        </button>
                    </div>
                    
                    <!-- Save Button -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex flex-col sm:flex-row justify-end gap-4">
                            <button type="button" onclick="saveMenu()" 
                                    class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg text-base font-medium">
                                <i class="fas fa-save mr-2"></i>Save Menu
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Template for category -->
<template id="categoryTemplate">
    <div class="category-item border border-gray-200 rounded-lg p-4 sm:p-6 draggable-category" data-category-id="">
        <div class="font-semibold mb-2 text-gray-800">Category</div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
            <div class="flex items-center space-x-2 sm:space-x-4">
                <i class="fas fa-grip-vertical text-gray-400 cursor-move handle text-lg drag-handle"></i>
                <input type="text" class="category-name border border-gray-300 rounded-lg px-3 py-2 sm:px-4 sm:py-3 text-lg sm:text-xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full max-w-xs sm:max-w-none" placeholder="Category Name" required>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-3">
                <button type="button" onclick="removeCategory(this)" 
                        class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-2 py-1 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base font-medium">
                    <i class="fas fa-trash mr-2"></i>Remove
                </button>
            </div>
        </div>
        <div class="font-semibold mb-2 text-gray-800">Items</div>
        <div class="category-items space-y-4 sortable-items">
            <!-- Items will be added here -->
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <button type="button" onclick="addItem(this)" 
                    class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-base font-medium">
                <i class="fas fa-plus mr-2"></i>Add Item
            </button>
        </div>
    </div>
</template>

<!-- Template for item -->
<template id="itemTemplate">
    <div class="item-row flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 p-4 bg-gray-50 rounded-lg border border-gray-200 draggable-item" data-item-id="">
        <div class="flex items-center mb-2 sm:mb-0">
            <i class="fas fa-grip-vertical text-gray-400 cursor-move handle text-lg drag-handle"></i>
        </div>
        <input type="text" class="item-name flex-1 border border-gray-300 rounded-lg px-3 py-2 sm:px-4 sm:py-3 text-base sm:text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full" placeholder="Item Name" required>
        
        <!-- Half Price (Optional) -->
        <div class="flex items-center space-x-2 sm:space-x-3">
            <span class="text-gray-600 text-base sm:text-lg font-medium">Half ₹</span>
            <input type="number" class="item-price-half w-full sm:w-32 border border-gray-300 rounded-lg px-3 py-2 sm:px-4 sm:py-3 text-base sm:text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Optional" step="0.01" min="0">
        </div>
        
        <!-- Full Price -->
        <div class="flex items-center space-x-2 sm:space-x-3">
            <span class="text-gray-600 text-base sm:text-lg font-medium">Full ₹</span>
            <input type="number" class="item-price-full w-full sm:w-32 border border-gray-300 rounded-lg px-3 py-2 sm:px-4 sm:py-3 text-base sm:text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0.00" step="0.01" min="0" required>
        </div>
        
        <button type="button" onclick="removeItem(this)" 
                class="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white px-2 py-1 sm:px-3 sm:py-2 rounded-lg text-sm sm:text-base font-medium mt-2 sm:mt-0">
            <i class="fas fa-times mr-1"></i>Remove
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
    } else {
        // Initialize drag and drop for empty container
        initializeDragAndDrop();
    }
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
    
    // Initialize drag and drop for the new category
    const newCategoryItems = categoryDiv.querySelector('.sortable-items');
    if (newCategoryItems) {
        new Sortable(newCategoryItems, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function() {
                updateMenuData();
            }
        });
    }
    
    updateMenuData();
}

function addItem(button, existingItem = null) {
    const categoryElement = button.closest('.category-item');
    const itemsContainer = categoryElement.querySelector('.category-items');
    const template = document.getElementById('itemTemplate');
    
    // Clone template
    const itemElement = template.content.cloneNode(true);
    const itemDiv = itemElement.querySelector('.item-row');
    itemDiv.dataset.itemId = 'item_' + (++itemCounter);
    
    // Set item data if provided
    if (existingItem) {
        itemDiv.querySelector('.item-name').value = existingItem.name;
        itemDiv.querySelector('.item-price-full').value = existingItem.price_full;
        if (existingItem.price_half) {
            itemDiv.querySelector('.item-price-half').value = existingItem.price_half;
        }
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
            const priceHalf = itemDiv.querySelector('.item-price-half').value;
            category.items.push({
                name: itemDiv.querySelector('.item-name').value,
                price_full: parseFloat(itemDiv.querySelector('.item-price-full').value) || 0,
                price_half: priceHalf ? parseFloat(priceHalf) : null
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
    // Initialize Sortable for categories
    const menuContainer = document.getElementById('menuContainer');
    if (menuContainer) {
        new Sortable(menuContainer, {
            animation: 150,
            handle: '.drag-handle',
            draggable: '.draggable-category',
            onEnd: function() {
                updateMenuData();
            }
        });
    }
    
    // Initialize Sortable for items within each category
    document.querySelectorAll('.sortable-items').forEach(container => {
        new Sortable(container, {
            animation: 150,
            handle: '.drag-handle',
            draggable: '.draggable-item',
            onEnd: function() {
                updateMenuData();
            }
        });
    });
}

// Auto-save functionality
let autoSaveTimer;
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        updateMenuData();
    }, 2000);
}

// Add event listeners for auto-save
document.addEventListener('input', function(e) {
    if (e.target.matches('.category-name, .item-name, .item-price-full, .item-price-half')) {
        scheduleAutoSave();
    }
});

// Add hover effects for better desktop experience
document.addEventListener('mouseover', function(e) {
    if (e.target.closest('.category-item')) {
        e.target.closest('.category-item').classList.add('shadow-md');
    }
    if (e.target.closest('.item-row')) {
        e.target.closest('.item-row').classList.add('shadow-sm');
    }
});

document.addEventListener('mouseout', function(e) {
    if (e.target.closest('.category-item')) {
        e.target.closest('.category-item').classList.remove('shadow-md');
    }
    if (e.target.closest('.item-row')) {
        e.target.closest('.item-row').classList.remove('shadow-sm');
    }
});
</script>

<?php include 'includes/footer.php'; ?> 
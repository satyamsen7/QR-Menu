<?php
$page_title = 'Setup Your Business - QR Menu System';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $business_name = trim($_POST['business_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($business_name)) {
        $errors[] = "Business name is required";
    }
    
    if (empty($address)) {
        $errors[] = "Address is required";
    }
    
    // Handle logo upload
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $allowed_types = ['image/png'];
        $max_size = 200 * 1024; // 200 KB
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Logo must be a PNG file";
        } elseif ($file['size'] > $max_size) {
            $errors[] = "Logo size must be less than 200 KB";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/logos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $logo_path = $upload_dir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $logo_path)) {
                $errors[] = "Failed to upload logo";
            }
        }
    }
    
    // Generate username if not provided
    if (empty($username)) {
        $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $business_name));
        $username = $base_username . '_' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    } else {
        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, hyphens, and underscores";
        }
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM vendors WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            if (empty($_POST['username'])) {
                // Regenerate username if auto-generated one exists
                $username = $base_username . '_' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = "Please provide a custom username";
                }
            } else {
                $errors[] = "Username already exists";
            }
        }
    }
    
    // If no errors, create vendor record
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO vendors (user_id, business_name, username, logo_path, address, is_setup_complete) VALUES (?, ?, ?, ?, ?, TRUE)");
        
        if ($stmt->execute([$_SESSION['user_id'], $business_name, $username, $logo_path, $address])) {
            $_SESSION['setup_complete'] = true;
            $_SESSION['vendor_username'] = $username;
            
            header('Location: /QR-Menu/dashboard');
            exit();
        } else {
            $errors[] = "Setup failed. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <i class="fas fa-store text-4xl text-blue-600 mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-900">Setup Your Business</h1>
            <p class="mt-2 text-gray-600">Complete your business profile to get started with your digital menu</p>
        </div>

        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-8">
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded alert">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form id="setupForm" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Business Name -->
                    <div>
                        <label for="business_name" class="block text-sm font-medium text-gray-700">
                            Business Name <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input id="business_name" name="business_name" type="text" required 
                                   value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>"
                                   placeholder="e.g., Tasty Bites Restaurant"
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Logo Upload -->
                    <div>
                        <label for="logo" class="block text-sm font-medium text-gray-700">
                            Business Logo (Optional)
                        </label>
                        <div class="mt-1">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div id="logoPreview" class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300">
                                        <i class="fas fa-image text-gray-400 text-xl"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <input id="logo" name="logo" type="file" accept=".png"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="mt-1 text-xs text-gray-500">PNG files only, max 200 KB</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            Custom Username (Optional)
                        </label>
                        <div class="mt-1">
                            <div class="flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    qr-ss.com/
                                </span>
                                <input id="username" name="username" type="text" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       placeholder="Leave empty for auto-generation"
                                       class="flex-1 appearance-none block w-full px-3 py-2 border border-gray-300 rounded-r-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Letters, numbers, hyphens, and underscores only</p>
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">
                            Business Address <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <textarea id="address" name="address" rows="3" required
                                      placeholder="Enter your complete business address"
                                      class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-check mr-2"></i>Complete Setup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logoPreview');
    
    // Logo preview
    logoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreview.innerHTML = `<img src="${e.target.result}" alt="Logo preview" class="w-full h-full object-cover rounded-lg">`;
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Form validation
    document.getElementById('setupForm').addEventListener('submit', function(e) {
        if (!validateForm('setupForm')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-generate username from business name
    const businessNameInput = document.getElementById('business_name');
    const usernameInput = document.getElementById('username');
    
    businessNameInput.addEventListener('input', function() {
        if (usernameInput.value === '') {
            const baseUsername = this.value.toLowerCase().replace(/[^a-z0-9]/g, '');
            if (baseUsername.length > 0) {
                const randomSuffix = Math.floor(1000 + Math.random() * 9000);
                usernameInput.placeholder = `${baseUsername}_${randomSuffix}`;
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 
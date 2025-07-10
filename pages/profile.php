<?php
$page_title = 'Profile - QR Menu System';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /QR-Menu/login');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Handle form submission for profile updates
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate required fields
        if (empty($name) || empty($email)) {
            $error_message = 'Name and email are required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            try {
                // Check if email is already taken by another user
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $error_message = 'This email address is already registered.';
                } else {
                    // Check if phone is already taken by another user
                    if (!empty($phone)) {
                        $stmt = $db->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
                        $stmt->execute([$phone, $_SESSION['user_id']]);
                        if ($stmt->fetch()) {
                            $error_message = 'This phone number is already registered.';
                        }
                    }
                    
                    if (empty($error_message)) {
                        // Update user information
                        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
                        
                        // Update session
                        $_SESSION['user_name'] = $name;
                        
                        // Handle password change if provided
                        if (!empty($current_password) && !empty($new_password)) {
                            // Verify current password
                            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (password_verify($current_password, $user['password'])) {
                                if ($new_password === $confirm_password) {
                                    if (strlen($new_password) >= 6) {
                                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                                        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                                        $success_message = 'Profile and password updated successfully!';
                                    } else {
                                        $error_message = 'New password must be at least 6 characters long.';
                                    }
                                } else {
                                    $error_message = 'New password and confirm password do not match.';
                                }
                            } else {
                                $error_message = 'Current password is incorrect.';
                            }
                        } else {
                            $success_message = 'Profile updated successfully!';
                        }
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'An error occurred while updating your profile.';
            }
        }
    }
    
    // Handle business information update
    if (isset($_POST['update_business'])) {
        $business_name = trim($_POST['business_name']);
        $username = trim($_POST['username']);
        $address = trim($_POST['address']);
        
        if (empty($business_name) || empty($username) || empty($address)) {
            $error_message = 'All business fields are required.';
        } else {
            try {
                // Check if username is already taken by another vendor
                $stmt = $db->prepare("SELECT id FROM vendors WHERE username = ? AND user_id != ?");
                $stmt->execute([$username, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $error_message = 'This username is already taken.';
                } else {
                    // Update vendor information
                    $stmt = $db->prepare("UPDATE vendors SET business_name = ?, username = ?, address = ? WHERE user_id = ?");
                    $stmt->execute([$business_name, $username, $address, $_SESSION['user_id']]);
                    $success_message = 'Business information updated successfully!';
                }
            } catch (PDOException $e) {
                $error_message = 'An error occurred while updating business information.';
            }
        }
    }
    
    // Handle logo upload
    if (isset($_POST['update_logo']) && isset($_FILES['logo'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 1 * 1024 * 1024; // Reduced to 1MB to avoid max_allowed_packet issues
        
        if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            if (in_array($_FILES['logo']['type'], $allowed_types) && $_FILES['logo']['size'] <= $max_size) {
                // Read the file content
                $logo_data = file_get_contents($_FILES['logo']['tmp_name']);
                $logo_type = $_FILES['logo']['type'];
                
                if ($logo_data !== false) {
                    try {
                        // Update database with logo binary data
                        $stmt = $db->prepare("UPDATE vendors SET logo_data = ?, logo_type = ? WHERE user_id = ?");
                        $stmt->execute([$logo_data, $logo_type, $_SESSION['user_id']]);
                        $success_message = 'Logo updated successfully!';
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'max_allowed_packet') !== false) {
                            $error_message = 'Logo file is too large. Please reduce the image size or compress it. Maximum size: 1MB.';
                        } else {
                            $error_message = 'Database error: ' . $e->getMessage();
                        }
                    }
                } else {
                    $error_message = 'Failed to read logo file.';
                }
            } else {
                $error_message = 'Invalid file type or size. Please upload a JPEG, PNG, or GIF file under 1MB.';
            }
        } else {
            $error_message = 'Error uploading file.';
        }
    }
}

// Get current user information
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get vendor information
$stmt = $db->prepare("SELECT *, logo_data, logo_type FROM vendors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb Navigation -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="/QR-Menu/dashboard" class="hover:text-blue-600">Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-gray-900">Profile Settings</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
            <p class="mt-2 text-gray-600">Manage your personal and business information</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Personal Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-user mr-2"></i>Personal Information
                    </h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="">
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <hr class="my-6">
                            
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" id="current_password" name="current_password" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-sm text-gray-500">Leave blank if you don't want to change password</p>
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" id="new_password" name="new_password" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" name="update_profile" 
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Business Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-store mr-2"></i>Business Information
                    </h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="">
                        <div class="space-y-4">
                            <div>
                                <label for="business_name" class="block text-sm font-medium text-gray-700">Business Name</label>
                                <input type="text" id="business_name" name="business_name" 
                                       value="<?php echo htmlspecialchars($vendor['business_name'] ?? ''); ?>" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    qr-menu.42web.io/
                                    </span>
                                    <input type="text" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($vendor['username'] ?? ''); ?>" 
                                           class="flex-1 min-w-0 block w-full border-gray-300 rounded-none rounded-r-md focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">This will be your menu URL</p>
                            </div>
                            
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">Business Address</label>
                                <textarea id="address" name="address" rows="3" 
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required><?php echo htmlspecialchars($vendor['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" name="update_business" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                                    <i class="fas fa-save mr-2"></i>Update Business Info
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Logo Upload Section -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-image mr-2"></i>Business Logo
                </h3>
            </div>
            <div class="p-6">
                <div class="flex items-center space-x-6">
                    <div class="flex-shrink-0">
                        <?php if (!empty($vendor['logo_data'])): ?>
                            <img src="/QR-Menu/logo-img.php" 
                                 alt="Business Logo" class="h-20 w-20 rounded-lg object-cover border">
                        <?php else: ?>
                            <div class="h-20 w-20 rounded-lg bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="space-y-4">
                                <div>
                                    <label for="logo" class="block text-sm font-medium text-gray-700">Upload Logo</label>
                                    <input type="file" id="logo" name="logo" accept="image/*" 
                                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="mt-1 text-sm text-gray-500">JPEG, PNG, or GIF up to 1MB</p>
                                </div>
                                
                                <button type="submit" name="update_logo" 
                                        class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                                    <i class="fas fa-upload mr-2"></i>Upload Logo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Information -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-info-circle mr-2"></i>Account Information
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Account Created</h4>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Last Updated</h4>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo date('F j, Y', strtotime($user['updated_at'])); ?>
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Account Status</h4>
                        <p class="mt-1 text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Setup Status</h4>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php if ($vendor && $vendor['is_setup_complete']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Complete
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Incomplete
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Dashboard -->
        <div class="mt-8 text-center">
            <a href="/QR-Menu/dashboard" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
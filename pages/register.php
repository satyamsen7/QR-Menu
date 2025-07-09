<?php
$page_title = 'Register - QR Menu System';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $contact_type = $_POST['contact_type'] ?? 'phone';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($contact)) {
        $errors[] = ($contact_type === 'phone' ? 'Phone' : 'Email') . " is required";
    } else {
        if ($contact_type === 'phone') {
            if (!preg_match('/^[0-9]{10,15}$/', $contact)) {
                $errors[] = "Please enter a valid phone number";
            }
        } else {
            if (!filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address";
            }
        }
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!$terms) {
        $errors[] = "You must agree to the Terms and Conditions";
    }
    
    // Check if contact already exists
    if (empty($errors)) {
        $field = $contact_type === 'phone' ? 'phone' : 'email';
        $stmt = $db->prepare("SELECT id FROM users WHERE $field = ?");
        $stmt->execute([$contact]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = ucfirst($contact_type) . " already exists";
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (name, $field, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $contact, $hashed_password])) {
            $user_id = $db->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['setup_complete'] = false;
            
            header('Location: setup');
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <i class="fas fa-qrcode text-4xl text-blue-600 mb-4"></i>
            <h2 class="text-3xl font-bold text-gray-900">Create Your Account</h2>
            <p class="mt-2 text-sm text-gray-600">
                Already have an account? 
                <a href="login" class="font-medium text-blue-600 hover:text-blue-500">
                    Sign in here
                </a>
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <?php if (!empty($errors)): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded alert">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Full Name
                    </label>
                    <div class="mt-1">
                        <input id="name" name="name" type="text" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Contact Type Toggle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Contact Information
                    </label>
                    <div class="flex items-center space-x-4 mb-2">
                        <label class="flex items-center">
                            <input type="radio" name="contact_type" value="phone" 
                                   <?php echo (!isset($_POST['contact_type']) || $_POST['contact_type'] === 'phone') ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Phone</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="contact_type" value="email"
                                   <?php echo (isset($_POST['contact_type']) && $_POST['contact_type'] === 'email') ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Email</span>
                        </label>
                    </div>
                    <div class="mt-1">
                        <input id="contact" name="contact" type="text" required 
                               value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>"
                               placeholder="Enter your phone number or email"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                    </label>
                    <div class="mt-1">
                        <input id="confirm_password" name="confirm_password" type="password" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" required
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="terms" class="ml-2 block text-sm text-gray-900">
                        I agree to the 
                        <a href="terms" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Terms and Conditions</a>
                    </label>
                </div>

                <!-- reCAPTCHA -->
                <div class="flex justify-center">
                    <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
                </div>

                <!-- Register Button -->
                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Or continue with</span>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="button" onclick="googleSignIn()"
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fab fa-google mr-2 text-red-600"></i>Continue with Google
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactTypeRadios = document.querySelectorAll('input[name="contact_type"]');
    const contactInput = document.getElementById('contact');
    
    function updateContactPlaceholder() {
        const selectedType = document.querySelector('input[name="contact_type"]:checked').value;
        if (selectedType === 'phone') {
            contactInput.placeholder = 'Enter your phone number';
            contactInput.type = 'tel';
        } else {
            contactInput.placeholder = 'Enter your email address';
            contactInput.type = 'email';
        }
    }
    
    contactTypeRadios.forEach(radio => {
        radio.addEventListener('change', updateContactPlaceholder);
    });
    
    updateContactPlaceholder();
    
    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        if (!validateForm('registerForm')) {
            e.preventDefault();
            return false;
        }
        
        // Check reCAPTCHA
        const recaptchaResponse = grecaptcha.getResponse();
        if (!recaptchaResponse) {
            e.preventDefault();
            alert('Please complete the reCAPTCHA verification.');
            return false;
        }
    });
});

function googleSignIn() {
    // Google OAuth implementation would go here
    alert('Google Sign-In functionality will be implemented with Google OAuth API');
}
</script>

<?php include 'includes/footer.php'; ?> 
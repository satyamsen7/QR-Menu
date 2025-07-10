<?php
$page_title = 'Login - QR Menu System';

// Include reCAPTCHA configuration
require_once 'config/recaptcha.php';

// Include OAuth configuration
require_once 'config/oauth.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    
    $errors = [];
    
    // Verify reCAPTCHA
    if (!verifyRecaptcha($recaptchaResponse)) {
        $errors[] = "Please complete the reCAPTCHA verification";
    }
    
    // Validation
    if (empty($login)) {
        $errors[] = "Login field is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        // Check if login is email, phone, or username
        $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);
        $is_phone = preg_match('/^[0-9]{10,15}$/', $login);
        
        if ($is_email) {
            $stmt = $db->prepare("SELECT u.*, v.is_setup_complete FROM users u LEFT JOIN vendors v ON u.id = v.user_id WHERE u.email = ?");
        } elseif ($is_phone) {
            $stmt = $db->prepare("SELECT u.*, v.is_setup_complete FROM users u LEFT JOIN vendors v ON u.id = v.user_id WHERE u.phone = ?");
        } else {
            $stmt = $db->prepare("SELECT u.*, v.is_setup_complete FROM users u LEFT JOIN vendors v ON u.id = v.user_id WHERE v.username = ?");
        }
        
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['setup_complete'] = $user['is_setup_complete'] ?? false;
            $_SESSION['login_method'] = 'email_password'; // Mark as regular login user
            
            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires]);
                
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            }
            
            // Redirect based on setup status
            if (!$user['is_setup_complete']) {
                header('Location: setup');
            } else {
                header('Location: dashboard');
            }
            exit();
        } else {
            $errors[] = "Invalid login credentials";
        }
    }
}

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT u.*, v.is_setup_complete FROM users u LEFT JOIN vendors v ON u.id = v.user_id JOIN user_sessions s ON u.id = s.user_id WHERE s.session_token = ? AND s.expires_at > NOW()");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['setup_complete'] = $user['is_setup_complete'] ?? false;
        $_SESSION['login_method'] = 'remember_me'; // Mark as remember me login
        
        if (!$user['is_setup_complete']) {
            header('Location: setup');
        } else {
            header('Location: dashboard');
        }
        exit();
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <i class="fas fa-qrcode text-4xl text-blue-600 mb-4"></i>
            <h2 class="text-3xl font-bold text-gray-900">Sign in to your account</h2>
            <p class="mt-2 text-sm text-gray-600">
                Don't have an account? 
                <a href="register" class="font-medium text-blue-600 hover:text-blue-500">
                    Register here
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

            <form id="loginForm" method="POST" class="space-y-6">
                <!-- Login Field -->
                <div>
                    <label for="login" class="block text-sm font-medium text-gray-700">
                        Email, Phone, or Username
                    </label>
                    <div class="mt-1">
                        <input id="login" name="login" type="text" required 
                               value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>"
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

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot-password" class="font-medium text-blue-600 hover:text-blue-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <!-- reCAPTCHA -->
                <div class="flex justify-center">
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                </div>

                <!-- Sign In Button -->
                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In
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
    // Form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        if (!validateForm('loginForm')) {
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
    // Redirect to Google OAuth
    window.location.href = '<?php echo getGoogleAuthUrl(); ?>';
}
</script>

<?php include 'includes/footer.php'; ?> 
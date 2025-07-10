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
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($is_phone) {
            // For phone numbers, get all users with this phone number
            $stmt = $db->prepare("SELECT u.*, v.is_setup_complete FROM users u LEFT JOIN vendors v ON u.id = v.user_id WHERE u.phone = ?");
            $stmt->execute([$login]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) === 1) {
                $user = $users[0];
            } elseif (count($users) > 1) {
                // Multiple users with same phone number - store them in session for selection
                $_SESSION['phone_users'] = $users;
                header('Location: login?phone_selection=' . urlencode($login));
                exit();
            } else {
                $user = null;
            }
        } else {
            $stmt = $db->prepare("SELECT u.*, v.is_setup_complete FROM users u LEFT JOIN vendors v ON u.id = v.user_id WHERE v.username = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
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

// Handle phone number selection for multiple users
if (isset($_GET['phone_selection']) && isset($_SESSION['phone_users'])) {
    $phone = $_GET['phone_selection'];
    $phone_users = $_SESSION['phone_users'];
    
    // If user selected a specific account
    if (isset($_POST['selected_user_id'])) {
        $selected_user_id = $_POST['selected_user_id'];
        $selected_user = null;
        
        foreach ($phone_users as $user) {
            if ($user['id'] == $selected_user_id) {
                $selected_user = $user;
                break;
            }
        }
        
        if ($selected_user) {
            // Store selected user for password verification
            $_SESSION['selected_user'] = $selected_user;
            unset($_SESSION['phone_users']);
            header('Location: login?verify_password=1');
            exit();
        }
    }
    
    // Show phone selection page
    $page_title = 'Select Account - QR Menu System';
    include 'includes/header.php';
    ?>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <i class="fas fa-users text-4xl text-blue-600 mb-4"></i>
                <h2 class="text-3xl font-bold text-gray-900">Multiple Accounts Found</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Phone number <?php echo htmlspecialchars($phone); ?> is associated with multiple accounts.
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-4">
                            Select the account you want to sign in to:
                        </label>
                        <?php foreach ($phone_users as $user): ?>
                            <div class="flex items-center mb-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <input type="radio" name="selected_user_id" value="<?php echo $user['id']; ?>" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" required>
                                <label class="ml-3 block text-sm text-gray-900">
                                    <div class="font-medium"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div class="text-gray-500 text-xs">
                                        <?php if ($user['email']): ?>
                                            Email: <?php echo htmlspecialchars($user['email']); ?>
                                        <?php endif; ?>
                                        <?php if ($user['is_setup_complete']): ?>
                                            <span class="text-green-600">✓ Setup Complete</span>
                                        <?php else: ?>
                                            <span class="text-orange-600">⚠ Setup Required</span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="window.location.href='login'" 
                                class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Back to Login
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Continue
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
}

// Handle password verification for selected user
if (isset($_GET['verify_password']) && isset($_SESSION['selected_user'])) {
    $selected_user = $_SESSION['selected_user'];
    $errors = []; // Initialize errors array for password verification
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Only validate password for this specific flow
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (!password_verify($password, $selected_user['password'])) {
            $errors[] = "Invalid password";
        }
        
        if (empty($errors)) {
            $_SESSION['user_id'] = $selected_user['id'];
            $_SESSION['user_name'] = $selected_user['name'];
            $_SESSION['setup_complete'] = $selected_user['is_setup_complete'] ?? false;
            $_SESSION['login_method'] = 'email_password';
            
            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $database = new Database();
                $db = $database->getConnection();
                
                $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$selected_user['id'], $token, $expires]);
                
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            }
            
            unset($_SESSION['selected_user']);
            
            // Redirect based on setup status
            if (!$selected_user['is_setup_complete']) {
                header('Location: setup');
            } else {
                header('Location: dashboard');
            }
            exit();
        }
        // If there are errors, they will be displayed below
    }
    
    // Show password verification page
    $page_title = 'Enter Password - QR Menu System';
    include 'includes/header.php';
    ?>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <i class="fas fa-lock text-4xl text-blue-600 mb-4"></i>
                <h2 class="text-3xl font-bold text-gray-900">Enter Password</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Sign in to <?php echo htmlspecialchars($selected_user['name']); ?>'s account
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <?php if (!empty($errors)): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="flex space-x-3">
                        <button type="button" onclick="window.location.href='login'" 
                                class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Back to Login
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Sign In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
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
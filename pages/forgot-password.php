<?php
// forgot-password.php
require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';
require_once __DIR__ . '/../src/Exception.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $stmt = $db->prepare('UPDATE users SET password_reset_token = ? WHERE id = ?');
            $stmt->execute([$token, $user['id']]);
            // Send email
            $base_path = dirname($_SERVER['SCRIPT_NAME']);
            if ($base_path === '/') {
                $base_path = '';
            } else {
                $base_path .= '/';
            }
            $resetLink = 'https://' . $_SERVER['HTTP_HOST'] . $base_path . "reset-password?token=$token";
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'noreply.netbot@gmail.com'; // Your Gmail address
                $mail->Password = 'xxxx'; // App password, not Gmail password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom('YOUR_GMAIL@gmail.com', 'QR Menu System');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "<p>Click the link below to reset your password:</p><p><a href='$resetLink'>$resetLink</a></p>";
                $mail->send();
                $message = 'A password reset link has been sent to your email.';
            } catch (Exception $e) {
                $message = 'Mailer Error: ' . $mail->ErrorInfo;
            }
        } else {
            $message = 'No account found with that email address.';
        }
    } else {
        $message = 'Please enter a valid email address.';
    }
}

$page_title = 'Forgot Password - QR Menu System';
include 'includes/header.php';
?>
<main class="flex items-center justify-center min-h-[70vh] bg-gray-50">
    <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Forgot Password</h1>
        <?php if ($message): ?>
            <div class="mb-4 text-center text-blue-600 font-medium"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md font-semibold">Send Reset Link</button>
        </form>
        <div class="mt-6 text-center">
            <a href="login" class="text-blue-600 hover:underline">Back to Login</a>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html> 
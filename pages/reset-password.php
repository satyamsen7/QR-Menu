<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$show_form = false;
$token = $_GET['token'] ?? '';

if ($token) {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('SELECT id FROM users WHERE password_reset_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $show_form = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if (strlen($password) < 6) {
                $message = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $message = 'Passwords do not match.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE users SET password = ?, password_reset_token = NULL WHERE id = ?');
                $stmt->execute([$hash, $user['id']]);
                $message = 'Your password has been reset. You can now <a href="login" class="text-blue-600 underline">login</a>.';
                $show_form = false;
            }
        }
    } else {
        $message = 'Invalid or expired reset token.';
    }
} else {
    $message = 'No reset token provided.';
}

$page_title = 'Reset Password - QR Menu System';
include 'includes/header.php';
?>
<main class="flex items-center justify-center min-h-[70vh] bg-gray-50">
    <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Reset Password</h1>
        <?php if ($message): ?>
            <div class="mb-4 text-center text-blue-600 font-medium"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($show_form): ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="password" name="password" required minlength="6" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md font-semibold">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?> 
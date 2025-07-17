<?php
$page_title = 'Page Not Found - QR Menu System';
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-8xl text-red-500 mb-4"></i>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-gray-700 mb-2">Page Not Found</h2>
            <p class="text-gray-600 mb-8">The page you're looking for doesn't exist or has been moved.</p>
        </div>
        
        <div class="space-x-4">
            <a href="/" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium">
                <i class="fas fa-home mr-2"></i>Go Home
            </a>
            <a href="/login" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-md font-medium">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
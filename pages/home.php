<?php
$page_title = 'QR Menu System - Digital Menu Solutions';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="gradient-bg text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                Digital Menus Made Simple
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-blue-100">
                Create beautiful digital menus with QR codes for your restaurant, hotel, or food business
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="register" class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition duration-300">
                    <i class="fas fa-rocket mr-2"></i>Get Started Free
                </a>
                <a href="#features" class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-blue-600 transition duration-300">
                    <i class="fas fa-play mr-2"></i>Learn More
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Everything You Need to Go Digital
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Transform your traditional menu into an interactive digital experience that customers love
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="text-center p-6 rounded-lg hover-scale">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-qrcode text-2xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">QR Code Generation</h3>
                <p class="text-gray-600">Generate unique QR codes for your menu with multiple style options and easy download features.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="text-center p-6 rounded-lg hover-scale">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-utensils text-2xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Dynamic Menu Builder</h3>
                <p class="text-gray-600">Create and manage your menu with unlimited categories and items. Update prices and items anytime.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="text-center p-6 rounded-lg hover-scale">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-line text-2xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Analytics & Tracking</h3>
                <p class="text-gray-600">Track QR code scans and get insights into customer engagement with your digital menu.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                How It Works
            </h2>
            <p class="text-xl text-gray-600">
                Get your digital menu up and running in just 3 simple steps
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Step 1 -->
            <div class="text-center">
                <div class="w-20 h-20 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                    1
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Register & Setup</h3>
                <p class="text-gray-600">Create your account and complete the setup wizard with your business details.</p>
            </div>
            
            <!-- Step 2 -->
            <div class="text-center">
                <div class="w-20 h-20 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                    2
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Build Your Menu</h3>
                <p class="text-gray-600">Add categories and items to your menu with prices and descriptions.</p>
            </div>
            
            <!-- Step 3 -->
            <div class="text-center">
                <div class="w-20 h-20 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                    3
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Generate & Share</h3>
                <p class="text-gray-600">Generate your QR code and share it with customers to view your digital menu.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-blue-600">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
            Ready to Go Digital?
        </h2>
        <p class="text-xl text-blue-100 mb-8">
            Join thousands of restaurants already using QR Menu System
        </p>
        <a href="register" class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition duration-300">
            <i class="fas fa-rocket mr-2"></i>Start Your Free Trial
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'QR Menu System'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <!-- jsPDF Library for PDF generation -->
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <!-- html2canvas for capturing QR design as image -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <!-- Sortable.js for drag and drop functionality -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <!-- Custom CSS -->
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .hover-scale { transition: transform 0.2s; }
        .hover-scale:hover { transform: scale(1.02); }
        
        /* Mobile menu styles */
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .mobile-menu.open {
            transform: translateX(0);
        }
        .mobile-menu-overlay {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
            background: rgba(0,0,0,0.5);
        }
        .mobile-menu-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        
        .header-shadow {
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); /* Tailwind shadow-sm */
        }
        .no-shadow {
            box-shadow: none !important;
        }

    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php
    // Auto-detect base path for navigation links
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_path = dirname($script_name);
    if ($base_path === '/') {
        $base_path = '';
    } else {
        $base_path .= '/';
    }
    ?>
    
    <!-- Navigation -->
    <?php if (!isset($hide_nav)): ?>
    <nav id="main-header" class="bg-white border-b relative z-50 header-shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo $base_path; ?>" class="flex items-center space-x-2">
                        <i class="fas fa-qrcode text-xl sm:text-2xl text-blue-600"></i>
                        <span class="text-lg sm:text-xl font-bold text-gray-900">QR Menu</span>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo $base_path; ?>dashboard" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="<?php echo $base_path; ?>dashboard/menu" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-utensils mr-2"></i>Menu
                        </a>
                        <a href="<?php echo $base_path; ?>dashboard/qr" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-qrcode mr-2"></i>QR Code
                        </a>
                        <div class="relative group">
                            <button class="flex items-center text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                                <i class="fas fa-chevron-down ml-1"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <a href="<?php echo $base_path; ?>profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-user-cog mr-2"></i>Profile
                                </a>
                                <a href="<?php echo $base_path; ?>logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $base_path; ?>login" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                        <a href="<?php echo $base_path; ?>register" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-user-plus mr-2"></i>Register
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-700 hover:text-blue-600 p-2 rounded-md transition-colors">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="mobile-menu md:hidden fixed top-16 left-0 w-64 h-[calc(100vh-4rem)] bg-white shadow-lg z-50 border-r border-gray-200">
            <div class="px-4 py-6 space-y-4 bg-white">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $base_path; ?>dashboard" class="block text-gray-700 hover:text-blue-600 px-3 py-3 rounded-md text-base font-medium transition-colors border-b border-gray-100">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="<?php echo $base_path; ?>dashboard/menu" class="block text-gray-700 hover:text-blue-600 px-3 py-3 rounded-md text-base font-medium transition-colors border-b border-gray-100">
                        <i class="fas fa-utensils mr-3"></i>Menu
                    </a>
                    <a href="<?php echo $base_path; ?>dashboard/qr" class="block text-gray-700 hover:text-blue-600 px-3 py-3 rounded-md text-base font-medium transition-colors border-b border-gray-100">
                        <i class="fas fa-qrcode mr-3"></i>QR Code
                    </a>
                    <div class="border-b border-gray-100 pb-4">
                        <div class="text-gray-500 text-sm px-3 py-2">
                            <i class="fas fa-user mr-3"></i><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </div>
                        <a href="<?php echo $base_path; ?>profile" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-user-cog mr-3"></i>Profile
                        </a>
                        <a href="<?php echo $base_path; ?>logout" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-sign-out-alt mr-3"></i>Logout
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>login" class="block text-gray-700 hover:text-blue-600 px-3 py-3 rounded-md text-base font-medium transition-colors border-b border-gray-100">
                        <i class="fas fa-sign-in-alt mr-3"></i>Login
                    </a>
                    <a href="<?php echo $base_path; ?>register" class="block bg-blue-600 hover:bg-blue-700 text-white px-3 py-3 rounded-md text-base font-medium transition-colors">
                        <i class="fas fa-user-plus mr-3"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mobile menu overlay -->
        <div id="mobile-menu-overlay" class="mobile-menu-overlay md:hidden fixed top-16 left-0 w-full h-[calc(100vh-4rem)] bg-black bg-opacity-50 z-40"></div>
    </nav>
    
    <!-- Mobile Menu JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
            const mainHeader = document.getElementById('main-header');
            
            function toggleMobileMenu() {
                mobileMenu.classList.toggle('open');
                mobileMenuOverlay.classList.toggle('open');
                document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
                // Remove shadow from header when sidebar is open
                if (mobileMenu.classList.contains('open')) {
                    mainHeader.classList.add('no-shadow');
                } else {
                    mainHeader.classList.remove('no-shadow');
                }
            }
            
            function closeMobileMenu() {
                mobileMenu.classList.remove('open');
                mobileMenuOverlay.classList.remove('open');
                document.body.style.overflow = '';
                // Restore shadow to header
                mainHeader.classList.remove('no-shadow');
            }
            
            mobileMenuButton.addEventListener('click', toggleMobileMenu);
            mobileMenuOverlay.addEventListener('click', closeMobileMenu);
            
            // Close menu on window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    closeMobileMenu();
                }
            });
            
            // Close menu when clicking on menu items
            const mobileMenuLinks = mobileMenu.querySelectorAll('a');
            mobileMenuLinks.forEach(link => {
                link.addEventListener('click', closeMobileMenu);
            });
        });
    </script>
    <?php endif; ?>
    
    <!-- Main Content --> 
    </main>
    
    <?php
    // Auto-detect base path for footer links
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_path = dirname($script_name);
    if ($base_path === '/') {
        $base_path = '';
    } else {
        $base_path .= '/';
    }
    ?>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Footer for authenticated users -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <h3 class="text-lg font-semibold mb-3 lg:mb-4">QR Menu System</h3>
                        <p class="text-gray-300 text-sm lg:text-base leading-relaxed">Create digital menus with QR codes for your restaurant, hotel, or food business.</p>
                    </div>
                    <div>
                        <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Dashboard</h3>
                        <ul class="space-y-2">
                            <li><a href="<?php echo $base_path; ?>dashboard" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-tachometer-alt mr-2 text-xs"></i>Overview
                            </a></li>
                            <li><a href="<?php echo $base_path; ?>dashboard/menu" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-utensils mr-2 text-xs"></i>Menu Builder
                            </a></li>
                            <li><a href="<?php echo $base_path; ?>dashboard/qr" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-qrcode mr-2 text-xs"></i>QR Code
                            </a></li>
                            <li><a href="<?php echo $base_path; ?>profile" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-user-cog mr-2 text-xs"></i>Profile
                            </a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Support</h3>
                        <ul class="space-y-2">
                            <li><a href="<?php echo $base_path; ?>terms" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-file-contract mr-2 text-xs"></i>Terms & Conditions
                            </a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-question-circle mr-2 text-xs"></i>Help Center
                            </a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-play-circle mr-2 text-xs"></i>Tutorials
                            </a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Contact</h3>
                        <div class="space-y-2">
                            <p class="text-gray-300 text-sm lg:text-base flex items-center">
                                <i class="fas fa-envelope mr-2 text-xs"></i>shyomex.pvt.ltd@gmail.com
                            </p>
                            <p class="text-gray-300 text-sm lg:text-base flex items-center">
                                <i class="fas fa-phone mr-2 text-xs"></i>+91 7580919806
                            </p>
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo $base_path; ?>logout" class="text-red-400 hover:text-red-300 text-sm transition-colors duration-200 flex items-center">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Footer for non-authenticated users -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <h3 class="text-lg font-semibold mb-3 lg:mb-4">QR Menu</h3>
                        <p class="text-gray-300 text-sm lg:text-base leading-relaxed">Create digital menus with QR codes for your restaurant, hotel, or food business.</p>
                    </div>
                    <div>
                        <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="<?php echo $base_path; ?>" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-home mr-2 text-xs"></i>Home
                            </a></li>
                            <li><a href="<?php echo $base_path; ?>register" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-user-plus mr-2 text-xs"></i>Register
                            </a></li>
                            <li><a href="<?php echo $base_path; ?>login" class="text-gray-300 hover:text-white text-sm lg:text-base transition-colors duration-200 flex items-center">
                                <i class="fas fa-sign-in-alt mr-2 text-xs"></i>Login
                            </a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Contact</h3>
                        <div class="space-y-2">
                            <p class="text-gray-300 text-sm lg:text-base flex items-center">
                                <i class="fas fa-envelope mr-2 text-xs"></i>shyomex.pvt.ltd@gmail.com
                            </p>
                            <p class="text-gray-300 text-sm lg:text-base flex items-center">
                                <i class="fas fa-phone mr-2 text-xs"></i>+91 7580919806
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Footer bottom -->
            <div class="border-t border-gray-700 mt-6 lg:mt-8 pt-6 lg:pt-8 text-center">
                <p class="text-gray-300 text-sm lg:text-base">&copy; 2025 QR Menu. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Common JavaScript -->
    <script>
        // Toggle dropdown menu
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownButton = document.querySelector('button[class*="flex items-center text-gray-700"]');
            const dropdownMenu = document.querySelector('.absolute.right-0.mt-2');
            
            if (dropdownButton && dropdownMenu) {
                dropdownButton.addEventListener('click', function() {
                    dropdownMenu.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                        dropdownMenu.classList.add('hidden');
                    }
                });
            }
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return true;
            
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('border-red-500');
                    isValid = false;
                } else {
                    input.classList.remove('border-red-500');
                }
            });
            
            return isValid;
        }
        
        // Show loading spinner
        function showLoading() {
            const spinner = document.createElement('div');
            spinner.id = 'loading-spinner';
            spinner.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            spinner.innerHTML = `
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-2 text-gray-600">Loading...</p>
                </div>
            `;
            document.body.appendChild(spinner);
        }
        
        // Hide loading spinner
        function hideLoading() {
            const spinner = document.getElementById('loading-spinner');
            if (spinner) {
                spinner.remove();
            }
        }
        
        // Password validation for profile page
        function validatePassword() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                    return false;
                } else {
                    confirmPassword.setCustomValidity('');
                    return true;
                }
            }
            return true;
        }
        
        // Initialize password validation on profile page
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                newPassword.addEventListener('input', validatePassword);
                confirmPassword.addEventListener('input', validatePassword);
            }
        });
    </script>
</body>
</html> 
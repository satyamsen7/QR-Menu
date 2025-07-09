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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">QR Menu System</h3>
                    <p class="text-gray-300">Create digital menus with QR codes for your restaurant, hotel, or food business.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="<?php echo $base_path; ?>" class="text-gray-300 hover:text-white">Home</a></li>
                        <li><a href="<?php echo $base_path; ?>register" class="text-gray-300 hover:text-white">Register</a></li>
                        <li><a href="<?php echo $base_path; ?>login" class="text-gray-300 hover:text-white">Login</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <p class="text-gray-300">Email: support@qrmenu.com</p>
                    <p class="text-gray-300">Phone: +1 (555) 123-4567</p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-300">&copy; 2024 QR Menu System. All rights reserved.</p>
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
    </script>
</body>
</html> 
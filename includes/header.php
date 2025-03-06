<nav class="bg-white shadow-lg relative z-50">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Main Navigation Bar -->
        <div class="flex justify-between items-center h-16">
            <a href="/" class="text-2xl font-bold text-purple-600">BENFABRICS</a>
            
            <!-- Mobile menu button -->
            <button id="mobile-menu-button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-purple-600 hover:bg-gray-100 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path class="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path class="close-icon hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Desktop Navigation -->
            <div class="hidden md:block">
                <div class="ml-10 flex items-baseline space-x-4">
                    <a href="/" class="text-gray-800 hover:text-purple-600 px-3 py-2">Home</a>
                    <a href="product.php" class="text-purple-600 px-3 py-2">Products</a>
                    <a href="#contact" class="text-gray-800 hover:text-purple-600 px-3 py-2">Contact</a>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="hidden md:hidden absolute top-16 left-0 right-0 bg-white shadow-lg z-50">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/" class="block text-gray-800 hover:text-purple-600 hover:bg-gray-50 px-3 py-2 rounded-md text-base font-medium">Home</a>
                <a href="product.php" class="block text-purple-600 hover:bg-gray-50 px-3 py-2 rounded-md text-base font-medium">Products</a>
                <a href="#contact" class="block text-gray-800 hover:text-purple-600 hover:bg-gray-50 px-3 py-2 rounded-md text-base font-medium">Contact</a>
            </div>
        </div>
    </div>
</nav>

<!-- Updated JavaScript for mobile menu -->
<script>
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuIcon = mobileMenuButton.querySelector('.menu-icon');
    const closeIcon = mobileMenuButton.querySelector('.close-icon');

    function toggleMenu() {
        const isOpen = mobileMenu.classList.toggle('hidden');
        menuIcon.classList.toggle('hidden');
        closeIcon.classList.toggle('hidden');
    }

    mobileMenuButton.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleMenu();
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target) && !mobileMenu.classList.contains('hidden')) {
            toggleMenu();
        }
    });

    // Prevent menu from closing when clicking inside it
    mobileMenu.addEventListener('click', (e) => {
        e.stopPropagation();
    });
</script>

<!-- Add these styles to ensure mobile menu appears above other content -->
<style>
    .mobile-menu-open {
        overflow: hidden;
    }

    #mobile-menu {
        transition: all 0.3s ease-in-out;
        max-height: calc(100vh - 4rem);
        overflow-y: auto;
    }
</style> 
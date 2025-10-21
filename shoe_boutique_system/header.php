<?php
// includes/header.php
?>
<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center">
                <i class="fas fa-shoe-prints text-2xl text-indigo-600 mr-3"></i>
                <h1 class="text-xl font-semibold text-gray-800">Shoe Boutique</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <div class="relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none"
                    >
                        <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-indigo-600 text-sm"></i>
                        </div>
                        <span><?php echo ucfirst($_SESSION['role']); ?></span>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                    </button>
                    <div 
                        x-show="open"
                        @click.outside="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50"
                        style="display: none;"
                    >
                        <a href="logout.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Add Alpine.js for interactivity -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
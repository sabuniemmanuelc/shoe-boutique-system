<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);

// Determine the correct base path for links
$current_dir = dirname($_SERVER['PHP_SELF']);
$is_root = ($current_dir == '/' || $current_dir == '\\');
?>
<aside class="bg-gray-800 text-white w-64 min-h-screen p-4">
    <nav class="space-y-2">
        <a href="<?php echo $is_root ? 'index.php' : './index.php'; ?>" class="flex items-center space-x-3 px-3 py-2 rounded-lg <?php echo $current_page == 'index.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-chart-line w-5"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if (hasPermission('salesperson')): ?>
        <a href="<?php echo $is_root ? 'pos.php' : './pos.php'; ?>" class="flex items-center space-x-3 px-3 py-2 rounded-lg <?php echo $current_page == 'pos.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-cash-register w-5"></i>
            <span>Point of Sale</span>
        </a>
        <?php endif; ?>
        
       <!-- In sidebar.php -->
<?php if (canAccessModule('inventory', 'view')): ?>
<a href="inventory.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg <?php echo $current_page == 'inventory.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
    <i class="fas fa-boxes w-5"></i>
    <span>Inventory</span>
</a>
<?php endif; ?>
        
        <a href="<?php echo $is_root ? 'orders.php' : './orders.php'; ?>" class="flex items-center space-x-3 px-3 py-2 rounded-lg <?php echo $current_page == 'orders.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-shopping-bag w-5"></i>
            <span>Orders</span>
        </a>
        
        <a href="<?php echo $is_root ? 'collections.php' : './collections.php'; ?>" class="flex items-center space-x-3 px-3 py-2 rounded-lg <?php echo $current_page == 'collections.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-truck-loading w-5"></i>
            <span>Collections</span>
        </a>
        
        <a href="<?php echo $is_root ? 'reports.php' : './reports.php'; ?>" class="flex items-center space-x-3 px-3 py-2 rounded-lg <?php echo $current_page == 'reports.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-chart-bar w-5"></i>
            <span>Reports</span>
        </a>
        
        <?php if (hasPermission('admin')): ?>
        <a href="<?php echo $is_root ? 'users.php' : './users.php'; ?>" class="flex items-center space-x-3 px-3 py-2 rounded-lg <?php echo $current_page == 'users.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-users w-5"></i>
            <span>User Management</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>
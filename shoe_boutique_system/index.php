<?php
// index.php
require_once 'config/db.php';
if (!isLoggedIn()) redirect('login.php');

$db = (new Database())->getConnection();

// Get dashboard statistics
$stats = [];
$today = date('Y-m-d');
$firstDayMonth = date('Y-m-01');
$firstDayWeek = date('Y-m-d', strtotime('monday this week'));

// Total sales today
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$today]);
$stats['sales_today'] = $stmt->fetchColumn();

// Total orders today
$sql = "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$today]);
$stats['orders_today'] = $stmt->fetchColumn();

// Total inventory value
$sql = "SELECT COALESCE(SUM(price * stock_quantity), 0) as value FROM products WHERE is_active = 1";
$stmt = $db->prepare($sql);
$stmt->execute();
$stats['inventory_value'] = $stmt->fetchColumn();

// Low stock items
$sql = "SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level AND is_active = 1";
$stmt = $db->prepare($sql);
$stmt->execute();
$stats['low_stock'] = $stmt->fetchColumn();

// Recent sales for chart
$sql = "SELECT DATE(created_at) as date, SUM(total_amount) as total 
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at) 
        ORDER BY date";
$stmt = $db->prepare($sql);
$stmt->execute();
$sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top selling products
$sql = "SELECT p.name, SUM(oi.quantity) as total_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY p.id, p.name
        ORDER BY total_sold DESC
        LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->execute();
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Shoe Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>
    
    <div class="flex">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-600">Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-shopping-cart text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Sales Today</p>
                            <p class="text-2xl font-bold text-gray-800">K<?php echo number_format($stats['sales_today'], 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-receipt text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Orders Today</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['orders_today']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-boxes text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Inventory Value</p>
                            <p class="text-2xl font-bold text-gray-800">K<?php echo number_format($stats['inventory_value'], 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['low_stock']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Sales Trend (Last 7 Days)</h3>
                    <canvas id="salesChart" height="250"></canvas>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Top Selling Products</h3>
                    <canvas id="productsChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $sql = "SELECT a.*, u.username FROM activity_logs a 
                                    JOIN users u ON a.user_id = u.id 
                                    ORDER BY a.created_at DESC LIMIT 5";
                            $stmt = $db->prepare($sql);
                            $stmt->execute();
                            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($activities as $activity): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $activity['username']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $activity['action']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo $activity['description']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M j', strtotime($item['date'])) . "'"; }, $sales_data)); ?>],
                datasets: [{
                    label: 'Daily Sales ($)',
                    data: [<?php echo implode(',', array_column($sales_data, 'total')); ?>],
                    borderColor: 'rgb(79, 70, 229)',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Products Chart
        const productsCtx = document.getElementById('productsChart').getContext('2d');
        const productsChart = new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['name'] . "'"; }, $top_products)); ?>],
                datasets: [{
                    label: 'Units Sold',
                    data: [<?php echo implode(',', array_column($top_products, 'total_sold')); ?>],
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
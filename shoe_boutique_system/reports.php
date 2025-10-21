<?php
// reports.php
require_once 'config/db.php';
if (!isLoggedIn()) redirect('login.php');

$db = (new Database())->getConnection();

// Set default date range (last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get sales report data
$sql = "SELECT DATE(s.sale_date) as date, SUM(s.total_amount) as daily_sales, COUNT(DISTINCT s.order_id) as orders_count
        FROM sales s 
        WHERE s.sale_date BETWEEN ? AND ?
        GROUP BY DATE(s.sale_date)
        ORDER BY date";
$stmt = $db->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top products
$sql = "SELECT p.name, SUM(s.quantity) as total_sold, SUM(s.total_amount) as revenue
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        WHERE s.sale_date BETWEEN ? AND ?
        GROUP BY p.id, p.name
        ORDER BY total_sold DESC
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sales summary
$sql = "SELECT 
            COUNT(DISTINCT order_id) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            SUM(quantity) as total_units_sold
        FROM sales 
        WHERE sale_date BETWEEN ? AND ?";
$stmt = $db->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get sales by customer type
$sql = "SELECT customer_type, COUNT(*) as order_count, SUM(total_amount) as total_revenue
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY customer_type
        ORDER BY total_revenue DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$sales_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle export requests
if (isset($_GET['export'])) {
    if ($_GET['export'] == 'pdf') {
        // Generate PDF report (simplified version)
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="sales_report_'.$start_date.'_to_'.$end_date.'.pdf"');
        // In a real implementation, you would use dompdf or similar library
        echo "PDF export would be generated here with the sales data.";
        exit;
    } elseif ($_GET['export'] == 'excel') {
        // Generate Excel report (simplified version)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="sales_report_'.$start_date.'_to_'.$end_date.'.xlsx"');
        // In a real implementation, you would use PhpSpreadsheet
        echo "Excel export would be generated here with the sales data.";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Shoe Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>
    
    <div class="flex">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 p-6">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Sales Reports</h1>
                    <p class="text-gray-600">Analyze sales performance and generate reports</p>
                </div>
                <div class="flex space-x-2">
                    <a href="reports.php?export=pdf&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i> Export PDF
                    </a>
                    <a href="reports.php?export=excel&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-file-excel mr-2"></i> Export Excel
                    </a>
                </div>
            </div>
            
            <!-- Date Filter -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-filter mr-2"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-shopping-cart text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                            <p class="text-2xl font-bold text-gray-800">K<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-receipt text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Orders</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $summary['total_orders'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Avg Order Value</p>
                            <p class="text-2xl font-bold text-gray-800">K<?php echo number_format($summary['avg_order_value'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600 mr-4">
                            <i class="fas fa-box text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Units Sold</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $summary['total_units_sold'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Sales Trend</h3>
                    <canvas id="salesTrendChart" height="250"></canvas>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Top Products</h3>
                    <canvas id="topProductsChart" height="250"></canvas>
                </div>
            </div>
            
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h3 class="text-lg font-semibold mb-4">Sales by Customer Type</h3>
    <canvas id="customerTypeChart" height="250"></canvas>
</div>
            
            <!-- Detailed Reports -->
            <div class="grid grid-cols-1 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Daily Sales Report</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($sales_data as $day): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M j, Y', strtotime($day['date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $day['orders_count']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">K<?php echo number_format($day['daily_sales'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Top Selling Products</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units Sold</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $product['name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $product['total_sold']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">K<?php echo number_format($product['revenue'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Sales Trend Chart
        const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
        const salesTrendChart = new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M j', strtotime($item['date'])) . "'"; }, $sales_data)); ?>],
                datasets: [{
                    label: 'Daily Sales ($)',
                    data: [<?php echo implode(',', array_column($sales_data, 'daily_sales')); ?>],
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
        
        // Top Products Chart
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        const topProductsChart = new Chart(topProductsCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['name'] . "'"; }, $top_products)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($top_products, 'revenue')); ?>],
                    backgroundColor: [
                        'rgb(79, 70, 229)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)', 'rgb(139, 92, 246)', 'rgb(14, 165, 233)',
                        'rgb(236, 72, 153)', 'rgb(20, 184, 166)', 'rgb(249, 115, 22)', 'rgb(6, 182, 212)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Customer Type Chart
const customerTypeCtx = document.getElementById('customerTypeChart').getContext('2d');
const customerTypeChart = new Chart(customerTypeCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { 
            return "'" . ucfirst(str_replace('_', ' ', $item['customer_type'])) . "'"; 
        }, $sales_by_type)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($sales_by_type, 'total_revenue')); ?>],
            backgroundColor: [
                '#10B981', '#3B82F6', '#25D366', '#EF4444', '#8B5CF6', '#F59E0B'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            }
        }
    }
});
    </script>
</body>
</html>
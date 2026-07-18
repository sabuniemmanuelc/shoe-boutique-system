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

if (!$summary) {
    $summary = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0, 'total_units_sold' => 0];
}

// Get sales by customer type
$sql = "SELECT 
            o.customer_type, 
            COUNT(DISTINCT o.id) as order_count, 
            SUM(s.total_amount) as total_revenue
        FROM orders o
        INNER JOIN sales s ON o.id = s.order_id
        WHERE s.sale_date BETWEEN ? AND ?
        AND o.customer_type IS NOT NULL
        AND o.customer_type != ''
        GROUP BY o.customer_type
        ORDER BY total_revenue DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$sales_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle export requests
if (isset($_GET['export'])) {
    if ($_GET['export'] == 'pdf') {
        exportPDF($sales_data, $top_products, $summary, $sales_by_type, $start_date, $end_date);
    } elseif ($_GET['export'] == 'excel') {
        exportExcel($sales_data, $top_products, $summary, $sales_by_type, $start_date, $end_date);
    }
}

// Function to export PDF using FPDF
function exportPDF($sales_data, $top_products, $summary, $sales_by_type, $start_date, $end_date) {
    // Check if FPDF exists, if not, use fallback
    $fpdf_path = __DIR__ . '/fpdf.php';
    if (!file_exists($fpdf_path)) {
        // Try libs folder
        $fpdf_path = __DIR__ . '/fpdf19/fpdf.php';
    }
    
    if (file_exists($fpdf_path)) {
        require_once($fpdf_path);
        
        // Create PDF
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Title
        $pdf->Cell(0, 10, 'Shoe Boutique - Sales Report', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, 'Period: ' . date('F d, Y', strtotime($start_date)) . ' to ' . date('F d, Y', strtotime($end_date)), 0, 1, 'C');
        $pdf->Cell(0, 8, 'Generated: ' . date('F d, Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Summary Section
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Summary', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);
        
        // Summary table
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(47, 8, 'Total Revenue:', 1, 0, 'L', true);
        $pdf->Cell(47, 8, 'K' . number_format($summary['total_revenue'], 2), 1, 0, 'R');
        $pdf->Cell(47, 8, 'Total Orders:', 1, 0, 'L', true);
        $pdf->Cell(47, 8, $summary['total_orders'], 1, 1, 'R');
        
        $pdf->Cell(47, 8, 'Avg Order Value:', 1, 0, 'L', true);
        $pdf->Cell(47, 8, 'K' . number_format($summary['avg_order_value'], 2), 1, 0, 'R');
        $pdf->Cell(47, 8, 'Units Sold:', 1, 0, 'L', true);
        $pdf->Cell(47, 8, $summary['total_units_sold'], 1, 1, 'R');
        $pdf->Ln(8);
        
        // Daily Sales
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Daily Sales', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(79, 70, 229);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(80, 8, 'Date', 1, 0, 'L', true);
        $pdf->Cell(55, 8, 'Orders', 1, 0, 'C', true);
        $pdf->Cell(55, 8, 'Sales Amount', 1, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        
        $fill = false;
        foreach ($sales_data as $day) {
            $pdf->Cell(80, 7, date('M d, Y', strtotime($day['date'])), 1, 0, 'L', $fill);
            $pdf->Cell(55, 7, $day['orders_count'], 1, 0, 'C', $fill);
            $pdf->Cell(55, 7, 'K' . number_format($day['daily_sales'], 2), 1, 1, 'R', $fill);
            $fill = !$fill;
        }
        $pdf->Ln(8);
        
        // Top Products
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Top Products', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(79, 70, 229);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(80, 8, 'Product', 1, 0, 'L', true);
        $pdf->Cell(55, 8, 'Units Sold', 1, 0, 'C', true);
        $pdf->Cell(55, 8, 'Revenue', 1, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        
        $fill = false;
        foreach ($top_products as $product) {
            $pdf->Cell(80, 7, htmlspecialchars(substr($product['name'], 0, 30)), 1, 0, 'L', $fill);
            $pdf->Cell(55, 7, $product['total_sold'], 1, 0, 'C', $fill);
            $pdf->Cell(55, 7, 'K' . number_format($product['revenue'], 2), 1, 1, 'R', $fill);
            $fill = !$fill;
        }
        $pdf->Ln(8);
        
        // Customer Types
        if (!empty($sales_by_type) && $sales_by_type[0]['customer_type'] != 'No Data') {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, 'Sales by Customer Type', 0, 1, 'L');
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(79, 70, 229);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(80, 8, 'Customer Type', 1, 0, 'L', true);
            $pdf->Cell(55, 8, 'Orders', 1, 0, 'C', true);
            $pdf->Cell(55, 8, 'Total Revenue', 1, 1, 'R', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 10);
            
            $fill = false;
            foreach ($sales_by_type as $type) {
                $pdf->Cell(80, 7, ucfirst(str_replace('_', ' ', $type['customer_type'])), 1, 0, 'L', $fill);
                $pdf->Cell(55, 7, $type['order_count'], 1, 0, 'C', $fill);
                $pdf->Cell(55, 7, 'K' . number_format($type['total_revenue'], 2), 1, 1, 'R', $fill);
                $fill = !$fill;
            }
        }
        
        // Footer
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, 'Generated from Shoe Boutique System', 0, 1, 'C');
        
        // Output PDF
        $pdf->Output('D', 'sales_report_' . $start_date . '_to_' . $end_date . '.pdf');
        exit;
    } else {
        // Fallback: Generate a proper PDF using HTML2PDF or download as HTML
        // Since FPDF is not available, let's use a simpler approach - download as HTML that can be printed to PDF
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="sales_report_'.$start_date.'_to_'.$end_date.'.html"');
        
        echo generateReportHTML($sales_data, $top_products, $summary, $sales_by_type, $start_date, $end_date);
        echo '<script>
            alert("To save as PDF: Open this file in a browser, then press Ctrl+P and select Save as PDF");
        </script>';
        exit;
    }
}

// Function to generate HTML report (used as fallback)
function generateReportHTML($sales_data, $top_products, $summary, $sales_by_type, $start_date, $end_date) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Sales Report</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            h1 { color: #4f46e5; text-align: center; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
            .report-header { text-align: center; margin-bottom: 30px; }
            .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
            .summary-box { background: #f8fafc; padding: 15px; border-radius: 5px; border: 1px solid #e2e8f0; }
            .summary-box h3 { margin: 0; color: #64748b; font-size: 13px; text-transform: uppercase; }
            .summary-box p { margin: 5px 0 0; font-size: 20px; font-weight: bold; color: #0f172a; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
            th { background-color: #4f46e5; color: white; padding: 10px 12px; text-align: left; font-size: 13px; }
            td { padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 13px; }
            tr:nth-child(even) { background-color: #f8fafc; }
            h2 { color: #1e293b; margin-top: 25px; margin-bottom: 15px; font-size: 18px; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 11px; }
            @media print {
                body { padding: 10px; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <h1>📊 Shoe Boutique - Sales Report</h1>
        <div class="report-header">
            <p><strong>Period:</strong> ' . date('F d, Y', strtotime($start_date)) . ' to ' . date('F d, Y', strtotime($end_date)) . '</p>
            <p><strong>Generated:</strong> ' . date('F d, Y H:i:s') . '</p>
        </div>
        
        <div class="summary-grid">
            <div class="summary-box">
                <h3>Total Revenue</h3>
                <p>K' . number_format($summary['total_revenue'], 2) . '</p>
            </div>
            <div class="summary-box">
                <h3>Total Orders</h3>
                <p>' . $summary['total_orders'] . '</p>
            </div>
            <div class="summary-box">
                <h3>Avg Order Value</h3>
                <p>K' . number_format($summary['avg_order_value'], 2) . '</p>
            </div>
            <div class="summary-box">
                <h3>Units Sold</h3>
                <p>' . $summary['total_units_sold'] . '</p>
            </div>
        </div>
        
        <h2>📈 Daily Sales</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Orders</th>
                    <th style="text-align: right;">Sales Amount</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($sales_data as $day) {
        $html .= '<tr>
                <td>' . date('M d, Y', strtotime($day['date'])) . '</td>
                <td>' . $day['orders_count'] . '</td>
                <td style="text-align: right;">K' . number_format($day['daily_sales'], 2) . '</td>
            </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <h2>🏆 Top Products</h2>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align: center;">Units Sold</th>
                    <th style="text-align: right;">Revenue</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($top_products as $product) {
        $html .= '<tr>
                <td>' . htmlspecialchars($product['name']) . '</td>
                <td style="text-align: center;">' . $product['total_sold'] . '</td>
                <td style="text-align: right;">K' . number_format($product['revenue'], 2) . '</td>
            </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <h2>👥 Sales by Customer Type</h2>
        <table>
            <thead>
                <tr>
                    <th>Customer Type</th>
                    <th style="text-align: center;">Orders</th>
                    <th style="text-align: right;">Total Revenue</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($sales_by_type as $type) {
        $html .= '<tr>
                <td>' . ucfirst(str_replace('_', ' ', $type['customer_type'])) . '</td>
                <td style="text-align: center;">' . $type['order_count'] . '</td>
                <td style="text-align: right;">K' . number_format($type['total_revenue'], 2) . '</td>
            </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div class="footer">
            <p>Generated from Shoe Boutique System • ' . date('Y') . '</p>
            <p><small>Tip: To save as PDF, press Ctrl+P and select "Save as PDF"</small></p>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Function to export Excel
function exportExcel($sales_data, $top_products, $summary, $sales_by_type, $start_date, $end_date) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sales_report_'.$start_date.'_to_'.$end_date.'.xls"');
    header('Cache-Control: max-age=0');
    
    // Create HTML table for Excel
    echo '<html>
    <head>
        <meta charset="UTF-8">
        <style>
            th { background-color: #4f46e5; color: white; font-weight: bold; }
            td, th { border: 1px solid #ddd; padding: 8px; }
        </style>
    </head>
    <body>
        <h2>Shoe Boutique - Sales Report</h2>
        <p>Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)) . '</p>
        <br>';
    
    // Summary Section
    echo '<h3>Summary</h3>
    <table>
        <tr>
            <th>Total Revenue</th>
            <th>Total Orders</th>
            <th>Avg Order Value</th>
            <th>Units Sold</th>
        </tr>
        <tr>
            <td>K' . number_format($summary['total_revenue'], 2) . '</td>
            <td>' . $summary['total_orders'] . '</td>
            <td>K' . number_format($summary['avg_order_value'], 2) . '</td>
            <td>' . $summary['total_units_sold'] . '</td>
        </tr>
    </table>
    <br>';
    
    // Daily Sales
    echo '<h3>Daily Sales</h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Orders</th>
            <th>Sales Amount</th>
        </tr>';
    foreach ($sales_data as $day) {
        echo '<tr>
            <td>' . date('M d, Y', strtotime($day['date'])) . '</td>
            <td>' . $day['orders_count'] . '</td>
            <td>K' . number_format($day['daily_sales'], 2) . '</td>
        </tr>';
    }
    echo '</table>
    <br>';
    
    // Top Products
    echo '<h3>Top Products</h3>
    <table>
        <tr>
            <th>Product</th>
            <th>Units Sold</th>
            <th>Revenue</th>
        </tr>';
    foreach ($top_products as $product) {
        echo '<tr>
            <td>' . htmlspecialchars($product['name']) . '</td>
            <td>' . $product['total_sold'] . '</td>
            <td>K' . number_format($product['revenue'], 2) . '</td>
        </tr>';
    }
    echo '</table>
    <br>';
    
    // Sales by Customer Type
    echo '<h3>Sales by Customer Type</h3>
    <table>
        <tr>
            <th>Customer Type</th>
            <th>Orders</th>
            <th>Total Revenue</th>
        </tr>';
    foreach ($sales_by_type as $type) {
        echo '<tr>
            <td>' . ucfirst(str_replace('_', ' ', $type['customer_type'])) . '</td>
            <td>' . $type['order_count'] . '</td>
            <td>K' . number_format($type['total_revenue'], 2) . '</td>
        </tr>';
    }
    echo '</table>
    
    <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    </body>
    </html>';
    exit;
}

// Prepare data for JavaScript
$chart_labels = json_encode(array_map(function($item) {
    return date('M j', strtotime($item['date']));
}, $sales_data));

$chart_data = json_encode(array_map(function($item) {
    return floatval($item['daily_sales']);
}, $sales_data));

$has_sales_data = !empty($sales_data) && array_sum(array_column($sales_data, 'daily_sales')) > 0;

$product_labels = json_encode(array_map(function($item) {
    return addslashes($item['name']);
}, $top_products));

$product_data = json_encode(array_map(function($item) {
    return floatval($item['revenue']);
}, $top_products));

$has_product_data = !empty($top_products) && array_sum(array_column($top_products, 'revenue')) > 0;

$customer_labels = json_encode(array_map(function($item) {
    return ucfirst(str_replace('_', ' ', $item['customer_type']));
}, $sales_by_type));

$customer_data = json_encode(array_map(function($item) {
    return floatval($item['total_revenue']);
}, $sales_by_type));

$has_customer_data = !empty($sales_by_type) && array_sum(array_column($sales_by_type, 'total_revenue')) > 0;
?>
<!-- HTML continues here - same as before -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Shoe Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
    </style>
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
                    <div class="chart-container">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Top Products</h3>
                    <div class="chart-container">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Sales by Customer Type -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                <h3 class="text-lg font-semibold mb-4">Sales by Customer Type</h3>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="customerTypeChart"></canvas>
                </div>
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
                                <?php if ($has_sales_data): ?>
                                    <?php foreach ($sales_data as $day): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M j, Y', strtotime($day['date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $day['orders_count']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">K<?php echo number_format($day['daily_sales'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">No sales data available for the selected period</td>
                                    </tr>
                                <?php endif; ?>
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
                                <?php if ($has_product_data): ?>
                                    <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $product['total_sold']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">K<?php echo number_format($product['revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">No product data available for the selected period</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Trend Chart
            <?php if ($has_sales_data): ?>
            const salesTrendCtx = document.getElementById('salesTrendChart');
            if (salesTrendCtx) {
                new Chart(salesTrendCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?php echo $chart_labels; ?>,
                        datasets: [{
                            label: 'Daily Sales (K)',
                            data: <?php echo $chart_data; ?>,
                            borderColor: 'rgb(79, 70, 229)',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'K' + value.toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
            
            // Top Products Chart
            <?php if ($has_product_data): ?>
            const topProductsCtx = document.getElementById('topProductsChart');
            if (topProductsCtx) {
                new Chart(topProductsCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo $product_labels; ?>,
                        datasets: [{
                            data: <?php echo $product_data; ?>,
                            backgroundColor: [
                                'rgb(79, 70, 229)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)',
                                'rgb(239, 68, 68)', 'rgb(139, 92, 246)', 'rgb(14, 165, 233)',
                                'rgb(236, 72, 153)', 'rgb(20, 184, 166)', 'rgb(249, 115, 22)', 'rgb(6, 182, 212)'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 12,
                                    padding: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>

            // Customer Type Chart
            <?php if ($has_customer_data): ?>
            const customerTypeCtx = document.getElementById('customerTypeChart');
            if (customerTypeCtx) {
                new Chart(customerTypeCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo $customer_labels; ?>,
                        datasets: [{
                            data: <?php echo $customer_data; ?>,
                            backgroundColor: [
                                '#10B981', '#3B82F6', '#25D366', '#EF4444', '#8B5CF6', '#F59E0B',
                                '#EC4899', '#14B8A6', '#F97316', '#6366F1'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 12,
                                    padding: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>
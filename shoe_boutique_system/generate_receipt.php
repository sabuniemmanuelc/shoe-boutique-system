<?php
// generate_receipt.php
require_once 'config/db.php';
if (!isLoggedIn()) redirect('login.php');

$db = (new Database())->getConnection();

if (!isset($_GET['order_id'])) {
    die('Order ID is required');
}

$order_id = $_GET['order_id'];

// Get order details with receipt information
$sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address,
               u.username as cashier_name, rs.*
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN users u ON o.created_by = u.id
        LEFT JOIN receipt_settings rs ON rs.is_active = 1
        WHERE o.id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found');
}

// Get order items
$sql = "SELECT oi.*, p.name as product_name, p.sku 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate receipt number if not exists
if (empty($order['receipt_number'])) {
    $receipt_number = 'RCPT-' . date('Ymd-') . str_pad($order_id, 6, '0', STR_PAD_LEFT);
    $sql = "UPDATE orders SET receipt_number = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$receipt_number, $order_id]);
    $order['receipt_number'] = $receipt_number;
}

// Generate TPIN if not exists (for demo purposes - in real scenario, this would come from customer)
if (empty($order['tpin'])) {
    $tpin = 'TPIN-' . date('ymd') . str_pad($order_id, 4, '0', STR_PAD_LEFT);
    $sql = "UPDATE orders SET tpin = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$tpin, $order_id]);
    $order['tpin'] = $tpin;
}

logActivity('generate_receipt', "Generated receipt for order #{$order['order_number']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $order['receipt_number']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .receipt-container { box-shadow: none !important; border: none !important; }
        }
        @page { margin: 0; size: auto; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-md mx-auto bg-white receipt-container shadow-lg border border-gray-200 my-8">
        <!-- Receipt Header -->
        <div class="text-center border-b border-gray-300 p-4">
            <h1 class="text-2xl font-bold text-gray-800"><?php echo $order['business_name']; ?></h1>
            <?php if ($order['business_address']): ?>
                <p class="text-sm text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($order['business_address'])); ?></p>
            <?php endif; ?>
            <?php if ($order['business_phone']): ?>
                <p class="text-sm text-gray-600">Tel: <?php echo $order['business_phone']; ?></p>
            <?php endif; ?>
            <?php if ($order['business_email']): ?>
                <p class="text-sm text-gray-600">Email: <?php echo $order['business_email']; ?></p>
            <?php endif; ?>
        </div>

        <!-- Receipt Details -->
        <div class="p-4 space-y-3">
            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                <span class="font-semibold">Receipt No:</span>
                <span class="font-mono"><?php echo $order['receipt_number']; ?></span>
            </div>
            
            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                <span class="font-semibold">Order No:</span>
                <span class="font-mono"><?php echo $order['order_number']; ?></span>
            </div>
            
            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                <span class="font-semibold">Date & Time:</span>
                <span class="text-sm"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
            </div>
            
            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                <span class="font-semibold">TPIN:</span>
                <span class="font-mono text-sm"><?php echo $order['tpin']; ?></span>
            </div>
            
            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                <span class="font-semibold">Cashier:</span>
                <span><?php echo $order['cashier_name']; ?></span>
            </div>

            <!-- Customer Information -->
            <?php if ($order['customer_name']): ?>
            <div class="border-b border-gray-200 pb-2">
                <div class="font-semibold mb-1">Customer:</div>
                <div class="text-sm">
                    <div><?php echo $order['customer_name']; ?></div>
                    <?php if ($order['customer_phone']): ?>
                        <div>Phone: <?php echo $order['customer_phone']; ?></div>
                    <?php endif; ?>
                    <?php if ($order['customer_email']): ?>
                        <div>Email: <?php echo $order['customer_email']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Items Table -->
        <div class="px-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-300">
                        <th class="text-left pb-2">Item</th>
                        <th class="text-right pb-2">Qty</th>
                        <th class="text-right pb-2">Price</th>
                        <th class="text-right pb-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr class="border-b border-gray-200">
                        <td class="py-2">
                            <div class="font-medium"><?php echo $item['product_name']; ?></div>
                            <div class="text-xs text-gray-500">SKU: <?php echo $item['sku']; ?></div>
                        </td>
                        <td class="text-right py-2"><?php echo $item['quantity']; ?></td>
                        <td class="text-right py-2">K<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-right py-2 font-medium">K<?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="p-4 space-y-2 border-t border-gray-300">
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>K<?php echo number_format($order['subtotal_amount'], 2); ?></span>
            </div>
            <div class="flex justify-between">
                <span>Tax (<?php echo $order['tax_rate']; ?>%):</span>
                <span>K<?php echo number_format($order['tax_amount'], 2); ?></span>
            </div>
            <div class="flex justify-between text-lg font-bold border-t border-gray-300 pt-2">
                <span>TOTAL:</span>
                <span>K<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <!-- Tax Information -->
        <div class="p-4 bg-gray-50 border-t border-gray-300">
            <div class="text-center text-sm">
                <div class="font-semibold">Tax Identification Number:</div>
                <div class="font-mono"><?php echo $order['tax_identification_number']; ?></div>
                <div class="mt-2 text-xs">This receipt serves as a tax invoice</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-300 text-center text-xs text-gray-600">
            <?php if ($order['receipt_footer']): ?>
                <p class="mb-2"><?php echo nl2br(htmlspecialchars($order['receipt_footer'])); ?></p>
            <?php endif; ?>
            <p>Generated on: <?php echo date('M j, Y g:i A'); ?></p>
            <p class="mt-2">Thank you for your business!</p>
        </div>
    </div>

    <!-- Print Controls -->
    <div class="max-w-md mx-auto no-print mt-4 flex justify-center space-x-4">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center">
            <i class="fas fa-print mr-2"></i> Print Receipt
        </button>
        <button onclick="window.close()" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 flex items-center">
            <i class="fas fa-times mr-2"></i> Close
        </button>
        <button onclick="downloadReceipt()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 flex items-center">
            <i class="fas fa-download mr-2"></i> Download PDF
        </button>
    </div>

    <script>
        function downloadReceipt() {
            // In a real implementation, you would generate a PDF here
            // For now, we'll just trigger print which can save as PDF
            window.print();
        }

        // Auto-print if requested
        <?php if (isset($_GET['auto_print'])): ?>
        window.onload = function() {
            window.print();
        }
        <?php endif; ?>
    </script>
</body>
</html>
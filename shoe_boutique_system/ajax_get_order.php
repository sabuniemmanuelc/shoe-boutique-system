<?php
// ajax_get_order.php
require_once 'config/db.php';
session_start();
if (!isLoggedIn()) die('Unauthorized');

$db = (new Database())->getConnection();

if (isset($_GET['id'])) {
    $order_id = $_GET['id'];
    
    // Get order details
    $sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address,
                   u.username as created_by_username
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id 
            LEFT JOIN users u ON o.created_by = u.id 
            WHERE o.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get order items
    $sql = "SELECT oi.*, p.name as product_name, p.sku 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($order) {
        ?>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h4 class="font-semibold text-gray-700">Order Information</h4>
                    <p><strong>Order #:</strong> <?php echo $order['order_number']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?php echo $order['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($order['status'] == 'ready' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-700">Customer Information</h4>
                    <p><strong>Name:</strong> <?php echo $order['customer_name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $order['customer_email']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $order['customer_phone']; ?></p>
                    <?php if ($order['address']): ?>
                    <p><strong>Address:</strong> <?php echo $order['address']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <h4 class="font-semibold text-gray-700 mb-2">Order Items</h4>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo $item['product_name']; ?></td>
                            <td class="px-4 py-2 text-sm text-gray-500"><?php echo $item['sku']; ?></td>
                            <td class="px-4 py-2 text-sm text-gray-500"><?php echo $item['quantity']; ?></td>
                            <td class="px-4 py-2 text-sm text-gray-500">K<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">K<?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-sm font-medium text-gray-900 text-right">Subtotal:</td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">K<?php echo number_format($order['total_amount'] / 1.1, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-sm font-medium text-gray-900 text-right">Tax (10%):</td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">K<?php echo number_format($order['total_amount'] * 0.1 / 1.1, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-sm font-medium text-gray-900 text-right">Total:</td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">K<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <?php if ($order['notes']): ?>
            <div>
                <h4 class="font-semibold text-gray-700">Order Notes</h4>
                <p class="text-gray-600"><?php echo $order['notes']; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="flex justify-end">
                <button onclick="closeOrderModal()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Close
                </button>
            </div>
        </div>
        <?php
    } else {
        echo '<p class="text-red-500">Order not found.</p>';
    }
}
?>
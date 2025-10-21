<?php
// orders.php
require_once 'config/db.php';
if (!isLoggedIn()) redirect('login.php');

$db = (new Database())->getConnection();

// Handle status updates
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt->execute([$status, $order_id])) {
        logActivity('update_order_status', "Updated order #$order_id to $status");
        $success = "Order status updated successfully!";
    } else {
        $error = "Failed to update order status.";
    }
}

// Get orders with filters
$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

$sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
               u.username as created_by_username
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN users u ON o.created_by = u.id 
        WHERE 1=1";
        
$params = [];
if ($status_filter) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($search_term) {
    $sql .= " AND (o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
    $search_like = "%$search_term%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Shoe Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>
    
    <div class="flex">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 p-6">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Order Management</h1>
                    <p class="text-gray-600">View and manage customer orders</p>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" 
                            placeholder="Search by order number, customer name or email..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="ready" <?php echo $status_filter == 'ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="collected" <?php echo $status_filter == 'collected' ? 'selected' : ''; ?>>Collected</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                        <a href="orders.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            <i class="fas fa-refresh mr-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">All Orders</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orders as $order): 
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'ready' => 'bg-blue-100 text-blue-800',
                                    'collected' => 'bg-green-100 text-green-800'
                                ];
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $order['order_number']; ?></div>
                                    <div class="text-sm text-gray-500">by <?php echo $order['created_by_username']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $order['customer_name']; ?></div>
                                    <?php if ($order['customer_email']): ?>
                                    <div class="text-sm text-gray-500"><?php echo $order['customer_email']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    K<?php echo number_format($order['total_amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_colors[$order['status']]; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
    <a href="generate_receipt.php?order_id=<?php echo $order['id']; ?>" 
       target="_blank"
       class="text-blue-600 hover:text-blue-900 mr-3">
        <i class="fas fa-receipt"></i> Receipt
    </a>
    <button onclick="openOrderModal(<?php echo $order['id']; ?>)" 
            class="text-indigo-600 hover:text-indigo-900 mr-3">
        <i class="fas fa-eye"></i> View
    </button>
    <?php if (hasPermission('salesperson')): ?>
    <button onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" 
            class="text-green-600 hover:text-green-900">
        <i class="fas fa-edit"></i> Status
    </button>
    <?php endif; ?>
</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Order Details Modal -->
    <div id="orderModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Order Details</h3>
                <div id="orderDetails"></div>
            </div>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Order Status</h3>
                <form method="POST" id="statusForm">
                    <input type="hidden" name="order_id" id="statusOrderId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="statusSelect" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="pending">Pending</option>
                            <option value="ready">Ready for Collection</option>
                            <option value="collected">Collected</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                            Cancel
                        </button>
                        <button type="submit" name="update_status"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openOrderModal(orderId) {
            fetch(`ajax_get_order.php?id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetails').innerHTML = html;
                    document.getElementById('orderModal').classList.remove('hidden');
                });
        }
        
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
        
        function closeOrderModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const orderModal = document.getElementById('orderModal');
            const statusModal = document.getElementById('statusModal');
            if (event.target === orderModal) closeOrderModal();
            if (event.target === statusModal) closeStatusModal();
        }
    </script>
</body>
</html>
<?php
// collections.php
require_once 'config/db.php';
if (!isLoggedIn()) redirect('login.php');

$db = (new Database())->getConnection();

// First, let's update the database schema to include collection details
// Run this SQL once to add the new columns:
/*
ALTER TABLE orders ADD COLUMN collected_by_name VARCHAR(255) NULL AFTER status;
ALTER TABLE orders ADD COLUMN collected_by_phone VARCHAR(20) NULL AFTER collected_by_name;
ALTER TABLE orders ADD COLUMN collected_by_relation VARCHAR(100) NULL AFTER collected_by_phone;
ALTER TABLE orders ADD COLUMN collection_method ENUM('in_store', 'courier', 'delivery') DEFAULT 'in_store' AFTER collected_by_relation;
ALTER TABLE orders ADD COLUMN collection_verified_by INT NULL AFTER collection_method;
ALTER TABLE orders ADD COLUMN collection_notes TEXT NULL AFTER collection_verified_by;
ALTER TABLE orders ADD COLUMN collected_at TIMESTAMP NULL AFTER collection_notes;

-- Add foreign key for verified_by
ALTER TABLE orders ADD CONSTRAINT orders_verified_by_fk FOREIGN KEY (collection_verified_by) REFERENCES users(id);
*/

// Handle status updates for collections
if (isset($_POST['update_collection_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $collected_by_name = $_POST['collected_by_name'] ?? '';
    $collected_by_phone = $_POST['collected_by_phone'] ?? '';
    $collected_by_relation = $_POST['collected_by_relation'] ?? '';
    $collection_method = $_POST['collection_method'] ?? 'in_store';
    $collection_notes = $_POST['collection_notes'] ?? '';
    
    if ($status === 'collected') {
        // For collected status, we need collection details
        if (empty($collected_by_name) && $collection_method === 'in_store') {
            $error = "Collector's name is required when marking as collected.";
        } else {
            $sql = "UPDATE orders SET status = ?, collected_by_name = ?, collected_by_phone = ?, 
                    collected_by_relation = ?, collection_method = ?, collection_notes = ?, 
                    collection_verified_by = ?, collected_at = NOW() 
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([$status, $collected_by_name, $collected_by_phone, $collected_by_relation, 
                              $collection_method, $collection_notes, $_SESSION['user_id'], $order_id])) {
                logActivity('update_collection_status', "Order #$order_id collected by $collected_by_name");
                $success = "Order marked as collected successfully!";
            } else {
                $error = "Failed to update collection status.";
            }
        }
    } else {
        // For other statuses (pending, ready)
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$status, $order_id])) {
            logActivity('update_collection_status', "Updated collection status for order #$order_id to $status");
            
            // If marking as ready, you could trigger notifications here
            if ($status === 'ready') {
                // In a real implementation, you would send SMS/email notifications
                // sendCollectionNotification($order_id);
            }
            
            $success = "Collection status updated successfully!";
        } else {
            $error = "Failed to update collection status.";
        }
    }
}

// Get orders ready for collection or pending
$status_filter = $_GET['status'] ?? 'ready';
$search_term = $_GET['search'] ?? '';

$sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
               u.username as created_by_username, uv.username as verified_by_username,
               GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN users u ON o.created_by = u.id 
        LEFT JOIN users uv ON o.collection_verified_by = uv.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.status IN ('ready', 'pending')";
        
$params = [];
if ($status_filter && $status_filter !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($search_term) {
    $sql .= " AND (o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $search_like = "%$search_term%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$collections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics for the header
$sql = "SELECT status, COUNT(*) as count FROM orders WHERE status IN ('pending', 'ready') GROUP BY status";
$stmt = $db->prepare($sql);
$stmt->execute();
$status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'pending' => 0,
    'ready' => 0,
    'total' => 0
];

foreach ($status_counts as $stat) {
    $stats[$stat['status']] = $stat['count'];
    $stats['total'] += $stat['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collections - Shoe Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>
    
    <div class="flex">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Order Collections</h1>
                <p class="text-gray-600">Manage order collections and customer pickups</p>
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
            
            <!-- Collection Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-gray-100 text-gray-600 mr-4">
                            <i class="fas fa-clipboard-list text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Pending</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending Processing</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['pending']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-box text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Ready for Pickup</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['ready']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-truck-loading text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Collections Today</p>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) FROM orders WHERE status = 'collected' AND DATE(collected_at) = CURDATE()";
                                $stmt = $db->prepare($sql);
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" 
                            placeholder="Search by order number, customer name, email or phone..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="ready" <?php echo $status_filter == 'ready' ? 'selected' : ''; ?>>Ready for Collection</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                        <a href="collections.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            <i class="fas fa-refresh mr-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Collections Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">Order Collections</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($collections as $order): 
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'ready' => 'bg-blue-100 text-blue-800',
                                    'collected' => 'bg-green-100 text-green-800'
                                ];
                                
                                // Truncate product names if too long
                                $product_names = $order['product_names'];
                                if (strlen($product_names) > 50) {
                                    $product_names = substr($product_names, 0, 50) . '...';
                                }
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $order['order_number']; ?></div>
                                    <div class="text-sm text-gray-500">by <?php echo $order['created_by_username']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $order['customer_name']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $order['customer_phone']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $order['customer_email']; ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo $product_names; ?>
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
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
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
                            
                            <?php if (empty($collections)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                                    <p>No orders found for collection.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
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
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Collection Status</h3>
                <form method="POST" id="statusForm">
                    <input type="hidden" name="order_id" id="statusOrderId">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="statusSelect" required onchange="toggleCollectionDetails()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="pending">Pending</option>
                            <option value="ready">Ready for Collection</option>
                            <option value="collected">Collected</option>
                        </select>
                    </div>
                    
                    <!-- Collection Details (shown only when status is 'collected') -->
                    <div id="collectionDetails" class="hidden space-y-4 border-t pt-4 mt-4">
                        <h4 class="font-medium text-gray-700">Collection Verification</h4>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Collection Method</label>
                            <select name="collection_method" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="in_store">In-Store Pickup</option>
                                <option value="courier">Courier Service</option>
                                <option value="delivery">Home Delivery</option>
                            </select>
                        </div>
                        
                        <div id="collectorDetails">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Collected By Name *</label>
                                    <input type="text" name="collected_by_name" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="collected_by_phone" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Relationship to Customer</label>
                                <select name="collected_by_relation" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Select Relationship</option>
                                    <option value="self">Customer Self</option>
                                    <option value="family_member">Family Member</option>
                                    <option value="friend">Friend</option>
                                    <option value="colleague">Colleague</option>
                                    <option value="courier_agent">Courier Agent</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Collection Notes</label>
                            <textarea name="collection_notes" rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                placeholder="Any additional notes about the collection..."></textarea>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                <span class="text-sm font-medium text-yellow-800">Verification Required</span>
                            </div>
                            <p class="text-sm text-yellow-700 mt-1">This collection will be verified by: <strong><?php echo $_SESSION['full_name']; ?></strong></p>
                        </div>
                    </div>
                    
                    <div class="mb-4" id="notificationSection">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notification</label>
                        <div class="flex items-center">
                            <input type="checkbox" id="sendNotification" name="send_notification" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="sendNotification" class="ml-2 text-sm text-gray-700">
                                Send notification to customer when marking as ready
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                            Cancel
                        </button>
                        <button type="submit" name="update_collection_status"
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
            document.getElementById('sendNotification').checked = currentStatus !== 'ready';
            
            // Show/hide collection details based on current status
            toggleCollectionDetails();
            
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function toggleCollectionDetails() {
            const status = document.getElementById('statusSelect').value;
            const collectionDetails = document.getElementById('collectionDetails');
            const notificationSection = document.getElementById('notificationSection');
            const collectorDetails = document.getElementById('collectorDetails');
            
            if (status === 'collected') {
                collectionDetails.classList.remove('hidden');
                notificationSection.classList.add('hidden');
                
                // Show collector details only for in-store pickup
                const method = document.querySelector('select[name="collection_method"]').value;
                toggleCollectorDetails(method);
            } else {
                collectionDetails.classList.add('hidden');
                notificationSection.classList.remove('hidden');
            }
        }
        
        function toggleCollectorDetails(method) {
            const collectorDetails = document.getElementById('collectorDetails');
            if (method === 'in_store') {
                collectorDetails.classList.remove('hidden');
            } else {
                collectorDetails.classList.add('hidden');
            }
        }
        
        // Add event listener for collection method change
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('change', function(e) {
                if (e.target.name === 'collection_method') {
                    toggleCollectorDetails(e.target.value);
                }
            });
        });
        
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
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
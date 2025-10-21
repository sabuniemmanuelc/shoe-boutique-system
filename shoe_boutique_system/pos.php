<?php
// pos.php
require_once 'config/db.php';
if (!isLoggedIn() || !hasPermission('salesperson')) redirect('login.php');

$db = (new Database())->getConnection();

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$customer_id = $_SESSION['customer_id'] ?? null;
$customer_type = $_SESSION['customer_type'] ?? 'walk_in';

// Handle adding to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    
    $sql = "SELECT * FROM products WHERE id = ? AND is_active = 1 AND stock_quantity >= ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id, $quantity]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        if (isset($cart[$product_id])) {
            $cart[$product_id]['quantity'] += $quantity;
        } else {
            $cart[$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'sku' => $product['sku']
            ];
        }
        $_SESSION['cart'] = $cart;
        $success = "Product added to cart!";
    } else {
        $error = "Product not available or insufficient stock.";
    }
}

// Handle removing from cart
if (isset($_GET['remove_from_cart'])) {
    $product_id = $_GET['remove_from_cart'];
    if (isset($cart[$product_id])) {
        unset($cart[$product_id]);
        $_SESSION['cart'] = $cart;
    }
}

// Handle clear customer
if (isset($_GET['clear_customer'])) {
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_type']);
    $customer_id = null;
    $customer_type = 'walk_in';
}

// Handle customer selection with type
if (isset($_POST['select_customer'])) {
    $submitted_customer_id = $_POST['customer_id'];
    
    // Convert "0" to NULL for walk-in customers
    $_SESSION['customer_id'] = (!empty($submitted_customer_id) && $submitted_customer_id != '0') ? $submitted_customer_id : NULL;
    $_SESSION['customer_type'] = $_POST['customer_type'] ?? 'walk_in';
    $customer_id = $_SESSION['customer_id'];
    $customer_type = $_SESSION['customer_type'];
    
    $success = "Customer type set to: " . ucfirst(str_replace('_', ' ', $customer_type));
}

// Handle quick customer creation
if (isset($_POST['quick_customer'])) {
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_type = $_POST['customer_type'];
    
    $sql = "INSERT INTO customers (name, phone, customer_type) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    if ($stmt->execute([$customer_name, $customer_phone, $customer_type])) {
        $new_customer_id = $db->lastInsertId();
        $_SESSION['customer_id'] = $new_customer_id;
        $_SESSION['customer_type'] = $customer_type;
        $customer_id = $new_customer_id;
        $success = "Quick customer created successfully!";
    } else {
        $error = "Failed to create customer.";
    }
}

// Handle checkout with customer type and receipt generation
if (isset($_POST['checkout'])) {
    if (empty($cart)) {
        $error = "Cart is empty!";
    } else {
        try {
            $db->beginTransaction();
            
            // Generate unique order and receipt numbers
            $order_number = 'ORD-' . date('Ymd-His') . rand(100, 999);
            $receipt_number = 'RCPT-' . date('Ymd-') . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $tpin = '1007515019';
            
            // Calculate amounts
            $subtotal = array_sum(array_map(function($item) {
                return $item['price'] * $item['quantity'];
            }, $cart));
            $tax_rate = 10.00; // 10%
            $tax_amount = $subtotal * ($tax_rate / 100);
            $total_amount = $subtotal + $tax_amount;
            
            // Handle customer_id - convert empty string to NULL for walk-in customers
            $customer_id_for_order = (!empty($customer_id) && $customer_id !== '') ? $customer_id : NULL;
            
            // Insert order with receipt information
            $sql = "INSERT INTO orders (order_number, receipt_number, customer_id, total_amount, subtotal_amount, tax_amount, tpin, customer_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$order_number, $receipt_number, $customer_id_for_order, $total_amount, $subtotal, $tax_amount, $tpin, $customer_type, $_SESSION['user_id']]);
            $order_id = $db->lastInsertId();
            
            // Insert order items and update stock
            foreach ($cart as $product_id => $item) {
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $total_price = $item['price'] * $item['quantity'];
                $stmt->execute([$order_id, $product_id, $item['quantity'], $item['price'], $total_price]);
                
                // Update product stock
                $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$item['quantity'], $product_id]);
                
                // Record sale for reporting
                $sql = "INSERT INTO sales (order_id, product_id, quantity, unit_price, total_amount, sale_date) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$order_id, $product_id, $item['quantity'], $item['price'], $total_price, date('Y-m-d')]);
            }
            
            $db->commit();
            
            logActivity('checkout', "Created order #$order_number with receipt #$receipt_number");
            
            // Store order ID for receipt generation
            $_SESSION['last_order_id'] = $order_id;
            
            // Clear cart and customer session
            unset($_SESSION['cart']);
            unset($_SESSION['customer_id']);
            unset($_SESSION['customer_type']);
            
            // Set success message with order details
            $success = "Order completed successfully! Order #: $order_number - Receipt has been generated.";
            $cart = [];
            $customer_id = null;
            $customer_type = 'walk_in';
            
            // Show receipt generation button
            $show_receipt_button = true;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Checkout failed: " . $e->getMessage();
        }
    }
}

// Handle receipt generation after successful checkout
if (isset($_SESSION['last_order_id']) && !isset($show_receipt_button)) {
    $show_receipt_button = true;
}

// Get customer types
$sql = "SELECT * FROM customer_types WHERE is_active = 1 ORDER BY type_name";
$stmt = $db->prepare($sql);
$stmt->execute();
$customer_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products
$sql = "SELECT * FROM products WHERE is_active = 1 AND stock_quantity > 0 ORDER BY name";
$stmt = $db->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customers
$sql = "SELECT * FROM customers ORDER BY name";
$stmt = $db->prepare($sql);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current customer details
$current_customer = null;
if ($customer_id) {
    $sql = "SELECT c.*, ct.type_name, ct.color 
            FROM customers c 
            LEFT JOIN customer_types ct ON c.customer_type = ct.type_name 
            WHERE c.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$customer_id]);
    $current_customer = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - Shoe Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>
    
    <div class="flex">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Point of Sale</h1>
                <p class="text-gray-600">Process sales and manage transactions</p>
            </div>
            
            <?php if (isset($success)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo $success; ?>
        
        <?php if (isset($show_receipt_button) && isset($_SESSION['last_order_id'])): ?>
            <div class="mt-3 flex space-x-2">
                <button onclick="printReceipt(<?php echo $_SESSION['last_order_id']; ?>)" 
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center text-sm">
                    <i class="fas fa-receipt mr-2"></i> Print Receipt
                </button>
                <button onclick="viewReceipt(<?php echo $_SESSION['last_order_id']; ?>)" 
                        class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 flex items-center text-sm">
                    <i class="fas fa-eye mr-2"></i> View Receipt
                </button>
                <button onclick="closeReceiptOptions()" 
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 flex items-center text-sm">
                    <i class="fas fa-times mr-2"></i> Close
                </button>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Products Section -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold mb-4">Select Products</h3>
                        
                        <!-- Customer Selection -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-700 mb-2">Customer Information</h4>
                            
                            <?php if ($current_customer || ($customer_type && $customer_type != 'walk_in')): ?>
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <?php if ($current_customer): ?>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium"><?php echo $current_customer['name']; ?></span>
                                                <?php if ($current_customer['type_name']): ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" 
                                                          style="background-color: <?php echo $current_customer['color']; ?>20; color: <?php echo $current_customer['color']; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $current_customer['type_name'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($current_customer['phone']): ?>
                                                <div class="text-sm text-gray-600"><?php echo $current_customer['phone']; ?></div>
                                            <?php endif; ?>
                                            <?php if ($current_customer['email']): ?>
                                                <div class="text-sm text-gray-600"><?php echo $current_customer['email']; ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">Walk-in Customer</span>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Walk-in
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-600">No customer details required</div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="pos.php?clear_customer=1" class="text-red-600 hover:text-red-800 text-sm">
                                        <i class="fas fa-times"></i> Change
                                    </a>
                                </div>
                            <?php else: ?>
                                <!-- Customer Type Selection -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Type</label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        <?php foreach ($customer_types as $type): ?>
                                            <button type="button" 
                                                    onclick="selectCustomerType('<?php echo $type['type_name']; ?>', '<?php echo $type['color']; ?>')"
                                                    class="p-3 border rounded-lg text-center hover:bg-gray-50 transition-colors customer-type-btn"
                                                    data-type="<?php echo $type['type_name']; ?>">
                                                <div class="w-3 h-3 rounded-full mb-2 mx-auto" style="background-color: <?php echo $type['color']; ?>"></div>
                                                <span class="text-sm font-medium"><?php echo ucfirst(str_replace('_', ' ', $type['type_name'])); ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Customer Selection Forms -->
                                <div id="customerForms" class="space-y-4">
                                    <!-- Existing Customer Form -->
                                    <div id="existingCustomerForm" class="hidden">
                                        <form method="POST" class="flex gap-2">
                                            <select name="customer_id" required class="flex-1 px-3 py-2 border border-gray-300 rounded-md">
                                                <option value="">Select Existing Customer</option>
                                                <?php foreach ($customers as $customer): ?>
                                                <option value="<?php echo $customer['id']; ?>">
                                                    <?php echo $customer['name']; ?> 
                                                    <?php if ($customer['phone']): ?> - <?php echo $customer['phone']; ?><?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="customer_type" id="existingCustomerType">
                                            <button type="submit" name="select_customer" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                                Select
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Quick Customer Form -->
                                    <div id="quickCustomerForm" class="hidden">
                                        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <input type="text" name="customer_name" placeholder="Customer Name" required
                                                class="px-3 py-2 border border-gray-300 rounded-md">
                                            <input type="text" name="customer_phone" placeholder="Phone (Optional)"
                                                class="px-3 py-2 border border-gray-300 rounded-md">
                                            <input type="hidden" name="customer_type" id="quickCustomerType">
                                            <button type="submit" name="quick_customer" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                                Create & Select
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Walk-in Customer (Auto-select) -->
                                    <div id="walkinCustomerInfo" class="hidden">
                                        <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                            <p class="text-blue-700 text-sm">Walk-in customer selected. No additional information needed.</p>
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="customer_id" value="0">
                                                <input type="hidden" name="customer_type" id="walkinCustomerType" value="walk_in">
                                                <button type="submit" name="select_customer" class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">
                                                    Confirm Walk-in
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Products Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($products as $product): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-medium text-gray-800"><?php echo $product['name']; ?></h4>
                                    <span class="text-sm font-semibold text-indigo-600">K<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                                <div class="text-sm text-gray-600 mb-3">
                                    <div>SKU: <?php echo $product['sku']; ?></div>
                                    <div>Size: <?php echo $product['size']; ?> | Color: <?php echo $product['color']; ?></div>
                                    <div class="text-green-600">Stock: <?php echo $product['stock_quantity']; ?></div>
                                </div>
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>"
                                        class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
                                    <button type="submit" name="add_to_cart" 
                                        class="flex-1 bg-green-600 text-white py-1 px-3 rounded text-sm hover:bg-green-700">
                                        Add to Cart
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Cart Section -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
                        <h3 class="text-lg font-semibold mb-4">Shopping Cart</h3>
                        
                        <?php if (empty($cart)): ?>
                            <p class="text-gray-500 text-center py-8">Your cart is empty</p>
                        <?php else: ?>
                            <div class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                                <?php 
                                $subtotal = 0;
                                foreach ($cart as $product_id => $item): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                    <div class="flex-1">
                                        <div class="font-medium text-sm"><?php echo $item['name']; ?></div>
                                        <div class="text-xs text-gray-600">Qty: <?php echo $item['quantity']; ?> × K<?php echo number_format($item['price'], 2); ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium text-sm">K<?php echo number_format($item_total, 2); ?></div>
                                        <a href="pos.php?remove_from_cart=<?php echo $product_id; ?>" 
                                           class="text-red-500 hover:text-red-700 text-xs">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Subtotal:</span>
                                    <span>K<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Tax (4%):</span>
                                    <span>K<?php echo number_format($subtotal * 0.1, 2); ?></span>
                                </div>
                                <div class="flex justify-between font-semibold text-lg border-t border-gray-200 pt-2">
                                    <span>Total:</span>
                                    <span>K<?php echo number_format($subtotal * 1.1, 2); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($customer_type): ?>
                                <div class="mt-4 p-3 bg-gray-50 rounded">
                                    <div class="text-sm text-gray-600">Customer Type:</div>
                                    <div class="font-medium">
                                        <?php 
                                        $type_display = array_column($customer_types, 'type_name', 'type_name');
                                        echo isset($type_display[$customer_type]) ? ucfirst(str_replace('_', ' ', $type_display[$customer_type])) : ucfirst($customer_type);
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="mt-6">
                                <button type="submit" name="checkout" 
                                    class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 font-medium"
                                    <?php echo (!$customer_id && $customer_type != 'walk_in') ? 'disabled' : ''; ?>>
                                    Complete Sale
                                </button>
                                <?php if (!$customer_id && $customer_type != 'walk_in'): ?>
                                <p class="text-red-500 text-sm mt-2 text-center">Please select or create a customer first</p>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let selectedCustomerType = '';
        
        function selectCustomerType(type, color) {
            selectedCustomerType = type;
            
            // Reset all buttons
            document.querySelectorAll('.customer-type-btn').forEach(btn => {
                btn.classList.remove('ring-2', 'ring-offset-2');
                btn.style.borderColor = '';
            });
            
            // Highlight selected button
            const selectedBtn = document.querySelector(`[data-type="${type}"]`);
            selectedBtn.classList.add('ring-2', 'ring-offset-2');
            selectedBtn.style.borderColor = color;
            selectedBtn.style.ringColor = color;
            
            // Show appropriate form
            document.getElementById('customerForms').classList.remove('hidden');
            document.querySelectorAll('#customerForms > div').forEach(div => {
                div.classList.add('hidden');
            });
            
            if (type === 'walk_in') {
                document.getElementById('walkinCustomerInfo').classList.remove('hidden');
                document.getElementById('walkinCustomerType').value = type;
            } else if (type === 'existing') {
                document.getElementById('existingCustomerForm').classList.remove('hidden');
                document.getElementById('existingCustomerType').value = type;
            } else {
                document.getElementById('quickCustomerForm').classList.remove('hidden');
                document.getElementById('quickCustomerType').value = type;
            }
        }
        
        // Receipt functions
        function printReceipt(orderId) {
            const receiptWindow = window.open(`generate_receipt.php?order_id=${orderId}&auto_print=1`, '_blank', 'width=600,height=800');
            
            // Clear the session after generating receipt
            setTimeout(() => {
                fetch('clear_receipt_session.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelector('.bg-green-100').style.display = 'none';
                        }
                    });
            }, 1000);
        }
        
        function viewReceipt(orderId) {
            window.open(`generate_receipt.php?order_id=${orderId}`, '_blank', 'width=600,height=800');
        }
        
        function closeReceiptOptions() {
            // Clear the session and hide the success message
            fetch('clear_receipt_session.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.bg-green-100').style.display = 'none';
                    }
                });
        }
        
        // Auto-select walk-in if no action taken
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                if (!selectedCustomerType && !document.querySelector('.customer-type-btn').classList.contains('ring-2')) {
                    selectCustomerType('walk_in', '#10B981');
                }
            }, 500);
        });
    </script>
</body>
</html>
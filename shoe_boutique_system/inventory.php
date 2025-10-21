<?php
// inventory.php
require_once 'config/db.php';
if (!isLoggedIn() || !canAccessModule('inventory', 'view')) {
    redirect('index.php');
}

$db = (new Database())->getConnection();

// Check specific permissions for actions
$can_add = canAccessModule('inventory', 'add');
$can_edit = canAccessModule('inventory', 'edit');
$can_delete = canAccessModule('inventory', 'delete');

// Handle form submissions
if ($_POST) {
    // ... rest of your code continues exactly as before
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $sku = $_POST['sku'];
        $category_id = $_POST['category_id'];
        $brand_id = $_POST['brand_id'];
        $price = $_POST['price'];
        $cost_price = $_POST['cost_price'];
        $size = $_POST['size'];
        $color = $_POST['color'];
        $stock_quantity = $_POST['stock_quantity'];
        $min_stock_level = $_POST['min_stock_level'];
        
        $sql = "INSERT INTO products (name, description, sku, category_id, brand_id, price, cost_price, size, color, stock_quantity, min_stock_level) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$name, $description, $sku, $category_id, $brand_id, $price, $cost_price, $size, $color, $stock_quantity, $min_stock_level])) {
            logActivity('add_product', "Added product: $name");
            $success = "Product added successfully!";
        } else {
            $error = "Failed to add product.";
        }
    }
    
    if (isset($_POST['update_product'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $sku = $_POST['sku'];
        $category_id = $_POST['category_id'];
        $brand_id = $_POST['brand_id'];
        $price = $_POST['price'];
        $cost_price = $_POST['cost_price'];
        $size = $_POST['size'];
        $color = $_POST['color'];
        $stock_quantity = $_POST['stock_quantity'];
        $min_stock_level = $_POST['min_stock_level'];
        
        $sql = "UPDATE products SET name=?, description=?, sku=?, category_id=?, brand_id=?, price=?, cost_price=?, size=?, color=?, stock_quantity=?, min_stock_level=? WHERE id=?";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$name, $description, $sku, $category_id, $brand_id, $price, $cost_price, $size, $color, $stock_quantity, $min_stock_level, $id])) {
            logActivity('update_product', "Updated product: $name");
            $success = "Product updated successfully!";
        } else {
            $error = "Failed to update product.";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "UPDATE products SET is_active = 0 WHERE id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt->execute([$id])) {
        logActivity('delete_product', "Deleted product ID: $id");
        $success = "Product deleted successfully!";
    } else {
        $error = "Failed to delete product.";
    }
}

// Get categories and brands for dropdowns
$sql = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT * FROM brands ORDER BY name";
$stmt = $db->prepare($sql);
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products
$sql = "SELECT p.*, c.name as category_name, b.name as brand_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE p.is_active = 1 
        ORDER BY p.name";
$stmt = $db->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock items
$sql = "SELECT p.*, c.name as category_name, b.name as brand_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE p.stock_quantity <= p.min_stock_level AND p.is_active = 1 
        ORDER BY p.stock_quantity ASC";
$stmt = $db->prepare($sql);
$stmt->execute();
$low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Shoe Boutique</title>
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
        <h1 class="text-2xl font-bold text-gray-800">Inventory Management</h1>
        <p class="text-gray-600">Manage your shoe inventory and stock levels</p>
    </div>
    <?php if ($can_add): ?>
    <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 flex items-center">
        <i class="fas fa-plus mr-2"></i> Add Product
    </button>
    <?php endif; ?>
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
            
            <!-- Low Stock Alert -->
            <?php if (count($low_stock_items) > 0): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    <h3 class="text-lg font-semibold text-red-800">Low Stock Alert</h3>
                </div>
                <p class="text-red-600 mb-3">The following products are running low on stock:</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    <?php foreach ($low_stock_items as $item): ?>
                    <div class="bg-white p-3 rounded border border-red-200">
                        <div class="font-medium text-gray-800"><?php echo $item['name']; ?></div>
                        <div class="text-sm text-gray-600">Stock: <?php echo $item['stock_quantity']; ?> (Min: <?php echo $item['min_stock_level']; ?>)</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Products Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">All Products</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-shoe-prints text-gray-400"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $product['name']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $product['color']; ?> / <?php echo $product['size']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $product['sku']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $product['category_name']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $product['brand_name']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">K<?php echo number_format($product['price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $product['stock_quantity'] <= $product['min_stock_level'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
    <?php if ($can_edit): ?>
    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
            class="text-indigo-600 hover:text-indigo-900 mr-3">
        <i class="fas fa-edit"></i>
    </button>
    <?php endif; ?>
    
    <?php if ($can_delete): ?>
    <a href="inventory.php?delete=<?php echo $product['id']; ?>" 
       class="text-red-600 hover:text-red-900"
       onclick="return confirm('Are you sure you want to delete this product?')">
        <i class="fas fa-trash"></i>
    </a>
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
    
    <?php if ($can_add || $can_edit): ?>
<!-- Add/Edit Product Modal -->
<div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add New Product</h3>
            
            <form method="POST" id="productForm">
                <input type="hidden" name="id" id="productId">
                <input type="hidden" name="add_product" id="formAction" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                        <input type="text" name="name" id="productName" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input type="text" name="sku" id="productSku" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="productDescription" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" id="productCategory" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                        <select name="brand_id" id="productBrand" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>"><?php echo $brand['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price (K)</label>
                        <input type="number" step="0.01" name="price" id="productPrice" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price (K)</label>
                        <input type="number" step="0.01" name="cost_price" id="productCostPrice" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                        <input type="text" name="size" id="productSize" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <input type="text" name="color" id="productColor" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="productStock" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Stock Level</label>
                        <input type="number" name="min_stock_level" id="productMinStock" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('formAction').name = 'add_product';
            document.getElementById('productModal').classList.remove('hidden');
        }
        
        function openEditModal(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productSku').value = product.sku;
            document.getElementById('productDescription').value = product.description || '';
            document.getElementById('productCategory').value = product.category_id;
            document.getElementById('productBrand').value = product.brand_id;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productCostPrice').value = product.cost_price;
            document.getElementById('productSize').value = product.size;
            document.getElementById('productColor').value = product.color;
            document.getElementById('productStock').value = product.stock_quantity;
            document.getElementById('productMinStock').value = product.min_stock_level;
            
            document.getElementById('formAction').name = 'update_product';
            document.getElementById('productModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
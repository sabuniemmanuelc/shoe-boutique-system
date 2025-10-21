<?php
// users.php
require_once 'config/db.php';
if (!isLoggedIn() || !hasPermission('admin')) redirect('login.php');

$db = (new Database())->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        $role = $_POST['role'];
        $full_name = $_POST['full_name'];
        
        $sql = "INSERT INTO users (username, password, email, role, full_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$username, $password, $email, $role, $full_name])) {
            logActivity('add_user', "Added user: $username");
            $success = "User added successfully!";
        } else {
            $error = "Failed to add user. Username might already exist.";
        }
    }
    
    if (isset($_POST['update_user'])) {
        $id = $_POST['id'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $full_name = $_POST['full_name'];
        $is_active = $_POST['is_active'] ?? 0;
        
        $sql = "UPDATE users SET email=?, role=?, full_name=?, is_active=? WHERE id=?";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$email, $role, $full_name, $is_active, $id])) {
            logActivity('update_user', "Updated user ID: $id");
            $success = "User updated successfully!";
        } else {
            $error = "Failed to update user.";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Prevent deleting own account
    if ($id != $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$id])) {
            logActivity('delete_user', "Deleted user ID: $id");
            $success = "User deleted successfully!";
        } else {
            $error = "Failed to delete user.";
        }
    } else {
        $error = "You cannot delete your own account.";
    }
}

// Get all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Shoe Boutique</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                    <p class="text-gray-600">Manage system users and their permissions</p>
                </div>
                <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 flex items-center">
                    <i class="fas fa-user-plus mr-2"></i> Add User
                </button>
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
            
            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">System Users</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-indigo-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $user['full_name']; ?></div>
                                            <div class="text-sm text-gray-500">@<?php echo $user['username']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['email']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $user['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 
                                               ($user['role'] == 'salesperson' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                       class="text-red-600 hover:text-red-900"
                                       onclick="return confirm('Are you sure you want to delete this user?')">
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
    
    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add New User</h3>
                
                <form method="POST" id="userForm">
                    <input type="hidden" name="id" id="userId">
                    <input type="hidden" name="add_user" id="formAction" value="1">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="full_name" id="userFullName" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="username" id="userUsername" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <div id="passwordField">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" id="userPassword" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" id="userEmail" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select name="role" id="userRole" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="viewer">Viewer</option>
                                <option value="salesperson">Salesperson</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        
                        <div id="statusField" class="hidden">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" id="userIsActive" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Active User</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">
                            Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('userForm').reset();
            document.getElementById('formAction').name = 'add_user';
            document.getElementById('passwordField').classList.remove('hidden');
            document.getElementById('statusField').classList.add('hidden');
            document.getElementById('userModal').classList.remove('hidden');
        }
        
        function openEditModal(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('userFullName').value = user.full_name;
            document.getElementById('userUsername').value = user.username;
            document.getElementById('userUsername').readOnly = true;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userIsActive').checked = user.is_active == 1;
            
            document.getElementById('formAction').name = 'update_user';
            document.getElementById('passwordField').classList.add('hidden');
            document.getElementById('statusField').classList.remove('hidden');
            document.getElementById('userModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
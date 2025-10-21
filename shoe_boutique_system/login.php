<?php
// login.php
require_once 'config/db.php';

$db = (new Database())->getConnection();

$error = '';
if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Debug: Check if we're getting the POST data
    error_log("Login attempt: username=$username");
    
    $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Check if user was found
    if ($user) {
        error_log("User found: " . $user['username']);
        error_log("Stored hash: " . $user['password']);
        error_log("Password verify result: " . (password_verify($password, $user['password']) ? 'true' : 'false'));
    } else {
        error_log("User not found or inactive");
    }
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        logActivity('login', 'User logged in successfully');
        
        // Debug: Check session variables
        error_log("Session set: user_id=" . $_SESSION['user_id']);
        
        redirect('index.php');
    } else {
        $error = 'Invalid username or password';
        error_log("Login failed for user: $username");
    }
}

// Check if there's a timeout message
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired due to inactivity. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shoe Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-96">
        <div class="text-center mb-8">
            <i class="fas fa-shoe-prints text-4xl text-indigo-600 mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-800">Shoe Boutique</h1>
            <p class="text-gray-600">Inventory & Order Management</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                    Username
                </label>
                <input type="text" id="username" name="username" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            
            <button type="submit" 
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-200">
                Sign In
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>Try: admin / password</p>
            <p class="mt-2">
                <a href="reset_password.php" class="text-indigo-600 hover:text-indigo-800">
                    Reset admin password
                </a>
            </p>
        </div>
    </div>
</body>
</html>
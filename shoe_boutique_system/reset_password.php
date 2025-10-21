<?php
// reset_password.php
require_once 'config/db.php';

$db = (new Database())->getConnection();

// Simple security check - you might want to remove this file after use
$allowed = true; // Set to false in production

if ($allowed && $_POST) {
    $username = $_POST['username'] ?? 'admin';
    $new_password = $_POST['new_password'] ?? 'admin123';
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE users SET password = ? WHERE username = ?";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute([$hashed_password, $username])) {
        $message = "Password reset successfully!<br>Username: $username<br>New Password: $new_password";
        $success = true;
    } else {
        $message = "Password reset failed!";
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Shoe Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Reset Admin Password</h1>
        
        <?php if (isset($message)): ?>
            <div class="<?php echo $success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> p-4 rounded mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" value="admin" 
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                <input type="text" name="new_password" value="admin123" 
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            
            <button type="submit" 
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Reset Password
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="login.php" class="text-indigo-600 hover:text-indigo-800">Back to Login</a>
        </div>
        
        <div class="mt-6 p-4 bg-yellow-100 rounded text-sm">
            <strong>Security Note:</strong> Delete this file after use in production environment.
        </div>
    </div>
</body>
</html>
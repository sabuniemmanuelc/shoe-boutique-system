<?php
// config/db.php - SIMPLE VERSION

class Database {
    private $host = "localhost";
    private $db_name = "shoe_boutique";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Currency configuration
define('CURRENCY', 'K');
define('CURRENCY_SYMBOL', 'K');

// Start session
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function hasPermission($required_role) {
    if (!isset($_SESSION['role'])) return false;
    
    $role_hierarchy = ['viewer' => 1, 'salesperson' => 2, 'admin' => 3];
    $user_level = $role_hierarchy[$_SESSION['role']] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 0;
    
    return $user_level >= $required_level;
}

function logActivity($action, $description = '') {
    global $db;
    if (isset($_SESSION['user_id'])) {
        $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $action, $description, $_SERVER['REMOTE_ADDR']]);
    }
}

function canAccessModule($module, $action = 'view') {
    if (!isset($_SESSION['role'])) return false;
    
    $permissions = [
        'admin' => [
            'inventory' => ['view', 'add', 'edit', 'delete', 'manage'],
            'pos' => ['view', 'process'],
            'orders' => ['view', 'manage', 'update_status'],
            'collections' => ['view', 'manage'],
            'reports' => ['view', 'export'],
            'users' => ['view', 'add', 'edit', 'delete']
        ],
        'salesperson' => [
            'inventory' => ['view'], // Can only view inventory
            'pos' => ['view', 'process'],
            'orders' => ['view', 'update_status'],
            'collections' => ['view', 'update_status'],
            'reports' => ['view'], // Can view but not export
            'users' => [] // No access to user management
        ],
        'viewer' => [
            'inventory' => ['view'],
            'pos' => [],
            'orders' => ['view'],
            'collections' => ['view'],
            'reports' => ['view'],
            'users' => []
        ]
    ];
    
    return isset($permissions[$_SESSION['role']][$module]) && 
           in_array($action, $permissions[$_SESSION['role']][$module]);
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
?>
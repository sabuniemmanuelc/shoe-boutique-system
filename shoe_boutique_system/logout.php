<?php
// logout.php
require_once 'config/db.php';
session_start();
logActivity('logout', 'User logged out');
session_destroy();
redirect('login.php');
?>
<?php
// clear_receipt_session.php
require_once 'config/db.php';
session_start();

if (isset($_SESSION['last_order_id'])) {
    unset($_SESSION['last_order_id']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
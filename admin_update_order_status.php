<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once 'db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    // Validate inputs
    if ($order_id <= 0 || !in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        $_SESSION['error'] = 'Invalid order status update request';
        header("Location: admin_orders.php");
        exit;
    }

    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = ?, notes = CONCAT(IFNULL(notes, ''), ?) WHERE id = ?");
    $combined_notes = $notes ? "\n\n" . date('Y-m-d H:i:s') . " - Status changed to " . ucfirst($status) . ": " . $notes : '';
    $stmt->bind_param('ssi', $status, $combined_notes, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Order status updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update order status';
    }

    // Redirect back to order details or orders list
    $redirect = isset($_POST['redirect_to_detail']) && $_POST['redirect_to_detail'] ? "admin_order_details.php?id=$order_id" : "admin_orders.php";
    header("Location: $redirect");
    exit;
} else {
    header('Location: admin_orders.php');
    exit;
}
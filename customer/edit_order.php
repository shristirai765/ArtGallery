<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id']);
    $quantity = intval($_POST['quantity']);
    $address = trim($_POST['address']);
    $user_id = $_SESSION['id'];
    
    $errors = [];
    
    // Validation
    if ($quantity < 1) $errors[] = "Quantity must be at least 1";
    if (empty($address)) $errors[] = "Address is required";
    
    if (empty($errors)) {
        // Check if order exists, belongs to user, and is pending
        $check = $conn->query("
            SELECT o.*, a.price as artwork_price 
            FROM orders o
            JOIN artworks a ON o.artwork_id = a.id
            WHERE o.id = $order_id 
            AND o.user_id = '$user_id' 
            AND o.status = 'Pending'
        ");
        
        if ($check && $check->num_rows > 0) {
            $order = $check->fetch_assoc();
            $new_total = $order['artwork_price'] * $quantity;
            
            // Update order - using 'id' as the primary key
            $stmt = $conn->prepare("
                UPDATE orders 
                SET quantity = ?, total_price = ?, address = ? 
                WHERE id = ? AND user_id = ?
            ");
            
            if ($stmt) {
                $stmt->bind_param("idsii", $quantity, $new_total, $address, $order_id, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Order #$order_id has been updated successfully.";
                    header("Location: orders.php");
                    exit();
                } else {
                    $errors[] = "Failed to update order: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
        } else {
            $errors[] = "Order not found or cannot be edited.";
        }
    }
    
    // If there are errors, redirect back with error message
    $_SESSION['error_message'] = implode(", ", $errors);
    header("Location: orders.php");
    exit();
}

// If not POST, redirect
header("Location: orders.php");
exit();
?>
<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$order_id = intval($_GET['order_id']);
$amount = floatval($_GET['amount']);
$user_id = $_SESSION['id'];

// Verify order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'Pending'");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Invalid order.");
}

// eSewa Configuration
$merchant_code = "EPAYTEST";
$secret_key = "8gBm/:&EnhH.1/q";
$transaction_uuid = uniqid() . '_' . $order_id;
$total_amount = $amount * 100;
$product_code = $merchant_code;

// Get base URL
$base_url = "http://localhost/MonetArtGallery/customer";
$success_url = $base_url . "/esewa_callback.php?status=success&order_id={$order_id}&user_id={$user_id}";
$failure_url = $base_url . "/esewa_callback.php?status=failed&order_id={$order_id}&user_id={$user_id}";

// Generate signature
$message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

// Build the redirect URL with all parameters
$esewa_url = "https://rc-epay.esewa.com.np/api/epay/main/v2/form?";
$params = http_build_query([
    'amount' => $total_amount,
    'tax_amount' => 0,
    'total_amount' => $total_amount,
    'transaction_uuid' => $transaction_uuid,
    'product_code' => $product_code,
    'product_service_charge' => 0,
    'product_delivery_charge' => 0,
    'success_url' => $success_url,
    'failure_url' => $failure_url,
    'signed_field_names' => 'total_amount,transaction_uuid,product_code',
    'signature' => $signature
]);

// Redirect directly
header("Location: " . $esewa_url);
exit();
?>
<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Get order details
if (!isset($_GET['order_id']) || !isset($_GET['amount'])) {
    die("Order ID and amount required.");
}

$order_id = intval($_GET['order_id']);
$amount = floatval($_GET['amount']);
$user_id = $_SESSION['id'];

// Verify order belongs to user and is pending
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ? AND status = 'Pending'
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Invalid order or already processed.");
}

// eSewa Configuration - TEST MODE
$merchant_code = "EPAYTEST";
$secret_key = "8gBm/:&EnhH.1/q";

// Generate a unique transaction UUID
$timestamp = date('YmdHis');
$random = bin2hex(random_bytes(8));
$transaction_uuid = $timestamp . '_' . $random . '_' . $order_id;

$timestamp = date('YmdHis');
$random = bin2hex(random_bytes(8));
$transaction_uuid = $timestamp . '_' . $random . '_' . $order_id;

// echo "<pre>";
// echo "Transaction UUID: " . $transaction_uuid;
// exit();

$stmt = $conn->prepare("
INSERT INTO payments
(order_id, transaction_uuid, amount, payment_method, status)
VALUES (?, ?, ?, 'eSewa', 'Pending')
");

$stmt->bind_param(
    "isd",
    $order_id,
    $transaction_uuid,
    $amount
);

$stmt->execute();

// Prepare eSewa fields - eSewa expects amount in Rupees
$total_amount = number_format($amount, 2, '.', '');
$product_code = $merchant_code;

// Get the base URL - use 127.0.0.1
$base_url = "http://127.0.0.1/MonetArtGallery/customer";

// Callback URLs
$success_url = $base_url . "/esewa_callback.php";
$failure_url = $base_url . "/esewa_callback.php";

// Generate HMAC signature
$message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

// Store in session
$_SESSION['esewa_order_id'] = $order_id;
$_SESSION['esewa_amount'] = $amount;
$_SESSION['esewa_transaction_uuid'] = $transaction_uuid;
$_SESSION['esewa_user_id'] = $user_id;

// echo "<pre>";
// echo $transaction_uuid;
// exit();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to eSewa...</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background: #f5efe9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 12px 28px rgba(44,75,90,.12);
            text-align: center;
            max-width: 480px;
            width: 100%;
        }
        .loader {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #2c4b5a;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 25px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            color: #2c4b5a;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            margin-bottom: 8px;
        }
        .amount {
            font-size: 28px;
            color: #c9a87c;
            font-weight: 700;
        }
        .order-id {
            color: #888;
            font-size: 14px;
            margin-top: 10px;
        }
        .btn-manual {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: .3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-manual:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .security-badge {
            margin-top: 20px;
            font-size: 13px;
            color: #999;
        }
        .security-badge i {
            color: #4CAF50;
            margin-right: 5px;
        }
        .test-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 14px;
            text-align: left;
        }
        .test-credentials strong {
            color: #2c4b5a;
        }
        .test-credentials code {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .btn-group .btn {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-group .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-group .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            text-align: left;
            margin-top: 15px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="loader"></div>
        <h2>Redirecting to eSewa</h2>
        <p>You will be redirected to eSewa's secure payment page.</p>
        <p>Amount: <span class="amount">Rs <?php echo number_format($amount, 2); ?></span></p>
        <div class="order-id">Order #<?php echo $order_id; ?></div>
        
        <div class="test-credentials">
            <strong><i class="fas fa-info-circle"></i> Test Credentials:</strong><br>
            eSewa ID: <code>9806800003</code> (or 9806800001, 9806800002)<br>
            Password: <code>Nepal@123</code><br>
            Mobile PIN: <code>1234</code>
        </div>
        
        
        <form id="esewaForm" action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
            <input type="hidden" name="amount" value="<?php echo $total_amount; ?>">
            <input type="hidden" name="tax_amount" value="0">
            <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
            <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
            <input type="hidden" name="product_code" value="<?php echo $product_code; ?>">
            <input type="hidden" name="product_service_charge" value="0">
            <input type="hidden" name="product_delivery_charge" value="0">
            <input type="hidden" name="success_url" value="<?php echo $success_url; ?>">
            <input type="hidden" name="failure_url" value="<?php echo $failure_url; ?>">
            <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
            <input type="hidden" name="signature" value="<?php echo $signature; ?>">
            
            <button type="submit" class="btn-manual">
                <i class="fas fa-credit-card"></i> Proceed to eSewa
            </button>
        </form>

        <div class="btn-group">
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="security-badge">
            <i class="fas fa-lock"></i> Secured connection
        </div>
    </div>

    <script>
        let formSubmitted = false;
        
        document.getElementById('esewaForm').addEventListener('submit', function() {
            if (formSubmitted) {
                return false;
            }
            formSubmitted = true;
        });
        
        setTimeout(function() {
            if (!formSubmitted) {
                formSubmitted = true;
                document.getElementById('esewaForm').submit();
            }
        }, 2000);
    </script>
</body>
</html>
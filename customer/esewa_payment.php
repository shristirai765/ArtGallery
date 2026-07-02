<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

if (empty($order_id) || $amount <= 0) {
    header("Location: cart.php");
    exit();
}

// Check if we should use simulation mode
$use_simulation = true; // Set to false when eSewa server is working

// eSewa v2 Test Credentials
$merchant_id = 'EPAYTEST';
$secret_key = '8gBm/:&EnhH.1/q';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . "://" . $host . "/MonetArtGallery";

$success_url = $base_url . "/customer/esewa_success.php";
$failure_url = $base_url . "/customer/esewa_failure.php";

$signature = hash_hmac('sha256', $merchant_id . '|' . $order_id . '|' . $amount . '|' . $success_url . '|' . $failure_url, $secret_key);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSewa Payment | Monet's Atelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --monet-deep: #2c4b5a;
            --monet-gold: #c9a87c;
            --bg: #f5efe9;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Quicksand', sans-serif;
        }
        body {
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .payment-container {
            background: white;
            padding: 50px;
            border-radius: 25px;
            box-shadow: 0 12px 28px rgba(44,75,90,.12);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .payment-container .icon {
            font-size: 80px;
            color: #27ae60;
            margin-bottom: 20px;
        }
        .payment-container h1 {
            color: var(--monet-deep);
            margin-bottom: 10px;
        }
        .payment-container p {
            color: #666;
            margin-bottom: 10px;
        }
        .payment-container .amount {
            font-size: 32px;
            font-weight: 700;
            color: var(--monet-gold);
            margin: 20px 0;
        }
        .payment-container .order-id {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .payment-container .btn {
            display: inline-block;
            padding: 14px 40px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: .3s;
            width: 100%;
            margin-bottom: 10px;
        }
        .payment-container .btn:hover {
            background: #219150;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        .payment-container .btn-danger {
            background: #e74c3c;
        }
        .payment-container .btn-danger:hover {
            background: #c0392b;
        }
        .payment-container .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        .payment-container .cancel-btn {
            display: inline-block;
            margin-top: 15px;
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
        }
        .payment-container .cancel-btn:hover {
            text-decoration: underline;
        }
        .test-badge {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .simulation-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .loading-spinner {
            display: none;
            margin: 15px 0;
        }
        .loading-spinner i {
            font-size: 30px;
            color: var(--monet-gold);
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        .warning-box i {
            color: #ffc107;
            margin-right: 8px;
        }
        .warning-box p {
            font-size: 14px;
            color: #856404;
            margin: 5px 0;
        }
        .divider {
            border: none;
            border-top: 2px solid #f0f0f0;
            margin: 25px 0;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <div class="test-badge">
        <i class="fas fa-flask"></i> TEST MODE
    </div>
    <div class="simulation-badge">
        <i class="fas fa-code"></i> SIMULATION
    </div>

    <div class="icon">
        <i class="fas fa-credit-card"></i>
    </div>

    <h1>Pay with eSewa</h1>
    <p>Complete your payment using eSewa.</p>

    <div class="warning-box">
        <i class="fas fa-exclamation-triangle"></i>
        <p><strong>eSewa Test Server Currently Unavailable</strong></p>
        <p>Using simulation mode for testing. Click "Simulate Payment" to complete the order.</p>
    </div>

    <div class="amount">Rs <?php echo number_format($amount, 2); ?></div>
    <div class="order-id">Order ID: <?php echo htmlspecialchars($order_id); ?></div>

    <div class="loading-spinner" id="loadingSpinner">
        <i class="fas fa-spinner"></i>
        <p style="margin-top:10px;color:#888;">Processing...</p>
    </div>

    <!-- eSewa v2 Form (Real) -->
    <form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST" id="esewaForm">
        <input type="hidden" name="tAmt" value="<?php echo $amount; ?>">
        <input type="hidden" name="amt" value="<?php echo $amount; ?>">
        <input type="hidden" name="txAmt" value="0">
        <input type="hidden" name="psc" value="0">
        <input type="hidden" name="pdc" value="0">
        <input type="hidden" name="scd" value="<?php echo $merchant_id; ?>">
        <input type="hidden" name="pid" value="<?php echo htmlspecialchars($order_id); ?>">
        <input type="hidden" name="su" value="<?php echo $success_url; ?>">
        <input type="hidden" name="fu" value="<?php echo $failure_url; ?>">
        <input type="hidden" name="signature" value="<?php echo $signature; ?>">

        <button type="submit" class="btn" id="payBtn">
            <i class="fas fa-check-circle"></i> Pay with eSewa
        </button>
    </form>

    <hr class="divider">

    <!-- Simulation Form -->
    <form action="esewa_simulation.php" method="POST">
        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
        <input type="hidden" name="amount" value="<?php echo $amount; ?>">
        
        <button type="submit" name="simulate_success" class="btn" style="background:#2c4b5a;">
            <i class="fas fa-play"></i> Simulate Payment (Success)
        </button>
        
        <button type="submit" name="simulate_failure" class="btn btn-danger">
            <i class="fas fa-times"></i> Simulate Payment (Failure)
        </button>
    </form>

    <a href="cart.php" class="cancel-btn">
        <i class="fas fa-arrow-left"></i> Cancel Payment
    </a>

    <div style="margin-top:20px;font-size:12px;color:#aaa;">
        <i class="fas fa-info-circle"></i> 
        Test eSewa ID: 9806800001-5 | Password: Nepal@123<br>
        <i class="fas fa-warning" style="color:#f39c12;"></i> 
        eSewa test server may be temporarily unavailable
    </div>
</div>

<script>
    document.getElementById('esewaForm').addEventListener('submit', function() {
        document.getElementById('payBtn').disabled = true;
        document.getElementById('payBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
        document.getElementById('loadingSpinner').style.display = 'block';
    });
</script>

</body>
</html>
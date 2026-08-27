<?php
// Simple eSewa Test Page
$merchant_code = "EPAYTEST";
$secret_key = "8gBm/:&EnhH.1/q";
$transaction_uuid = uniqid() . '_test';
$product_code = "TEST_123";
$total_amount = 100; // Rs 1.00 in paisa

// Generate signature
$signed_field_names = "total_amount,transaction_uuid,product_code";
$message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

$base_url = "http://localhost/monets_atelier/user";
?>
<!DOCTYPE html>
<html>
<head>
    <title>eSewa Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .test-box { background: #f5f5f5; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .btn { background: #4CAF50; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #45a049; }
        pre { background: #333; color: #fff; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>eSewa Test Payment</h1>
    <div class="test-box">
        <p><strong>Test Credentials:</strong></p>
        <p>Merchant Code: <code>EPAYTEST</code></p>
        <p>Secret Key: <code>8gBm/:&EnhH.1/q</code></p>
        <p>Amount: <strong>Rs 1.00</strong> (for testing)</p>
    </div>

    <form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
        <input type="hidden" name="amount" value="<?php echo $total_amount; ?>">
        <input type="hidden" name="tax_amount" value="0">
        <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
        <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
        <input type="hidden" name="product_code" value="<?php echo $product_code; ?>">
        <input type="hidden" name="product_service_charge" value="0">
        <input type="hidden" name="product_delivery_charge" value="0">
        <input type="hidden" name="success_url" value="<?php echo $base_url; ?>/esewa_test_callback.php?status=success">
        <input type="hidden" name="failure_url" value="<?php echo $base_url; ?>/esewa_test_callback.php?status=failed">
        <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
        <input type="hidden" name="signature" value="<?php echo $signature; ?>">
        
        <button type="submit" class="btn">Pay Rs 1.00 with eSewa</button>
    </form>

    <h3>Debug Info:</h3>
    <pre>
Transaction UUID: <?php echo $transaction_uuid; ?>
Product Code: <?php echo $product_code; ?>
Total Amount (Paisa): <?php echo $total_amount; ?>
Signature: <?php echo $signature; ?>
Message: <?php echo $message; ?>
    </pre>
</body>
</html>
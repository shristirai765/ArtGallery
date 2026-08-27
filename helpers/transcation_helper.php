<?php
// helpers/transaction_helper.php

function generateUniqueTransactionId($conn) {
    // Generate a unique transaction ID
    $timestamp = date('YmdHis');
    $random = bin2hex(random_bytes(8));
    $transaction_uuid = 'TX_' . $timestamp . '_' . $random;
    
    // Check if it already exists in database
    $check = $conn->query("SELECT id FROM esewa_transactions WHERE transaction_uuid = '$transaction_uuid'");
    if ($check && $check->num_rows > 0) {
        // If exists, regenerate
        return generateUniqueTransactionId($conn);
    }
    
    return $transaction_uuid;
}
?>
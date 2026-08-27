<?php
session_start();
require_once "../config/db.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!isset($_GET['data'])) {
    $_SESSION['error_message'] = "Invalid payment response.";
    header("Location: orders.php");
    exit();
}

$response = json_decode(base64_decode($_GET['data']), true);

if (!$response) {
    $_SESSION['error_message'] = "Unable to decode eSewa response.";
    header("Location: orders.php");
    exit();
}

$status = $response['status'] ?? '';
$transaction_uuid = $response['transaction_uuid'] ?? '';
$total_amount = $response['total_amount'] ?? '';
$product_code = $response['product_code'] ?? '';
$transaction_code = $response['transaction_code'] ?? '';

/*
transaction_uuid looks like

20260704133316_d41615e94a100910_27

Last part = order_id
*/

$parts = explode("_", $transaction_uuid);

$order_id = intval(end($parts));

if ($order_id <= 0) {
    $_SESSION['error_message'] = "Invalid Order ID.";
    header("Location: orders.php");
    exit();
}

/*
=====================================
VERIFY PAYMENT WITH eSEWA
=====================================
*/

$url = "https://rc.esewa.com.np/api/epay/transaction/status/?" .
        http_build_query([
            "product_code" => $product_code,
            "total_amount" => $total_amount,
            "transaction_uuid" => $transaction_uuid
        ]);

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$verify = json_decode($result, true);

$transaction_code = $verify['ref_id'] ?? '';


if (
    isset($verify['status']) &&
    $verify['status'] == "COMPLETE"
) {

    // Prevent duplicate updates
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id=?");
    $stmt->bind_param("i",$order_id);
    $stmt->execute();

    $order = $stmt->get_result()->fetch_assoc();

    if ($order && $order['status'] != 'Accepted') {

        $update = $conn->prepare("
            UPDATE orders
            SET status='Accepted'
            WHERE id=?
        ");

        $update->bind_param("i",$order_id);
        $update->execute();
         $update->close();

        $stmt = $conn->prepare("
            UPDATE payments
            SET
                transaction_code = ?,
                status = 'Completed'
            WHERE transaction_uuid = ?
            ");

            $stmt->bind_param(
                "ss",
                $transaction_code,
                $transaction_uuid
            );

            $stmt->execute();
            $stmt->close();
    }

$_SESSION['success_message'] = "Payment completed successfully! Your order has been confirmed.";

header("Location: orders.php");

exit();

}

$_SESSION['error_message'] = "Payment verification failed.";

header("Location: orders.php");

exit();

?>
<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$user = $_SESSION['id'];

// Get cart items
$cartItems = $conn->query("
    SELECT 
        cart.id as cart_id,
        cart.quantity,
        artworks.id as artwork_id,
        artworks.title,
        artworks.price,
        artworks.image,
        artworks.artist_id,
        users.username as artist_name
    FROM cart
    JOIN artworks ON cart.artwork_id = artworks.id
    JOIN users ON artworks.artist_id = users.id
    WHERE cart.user_id = '$user'
");

$total = 0;
$items = [];
while ($item = $cartItems->fetch_assoc()) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
    $items[] = $item;
}

// If cart is empty, redirect to gallery
if (empty($items)) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if (isset($_POST['place_order'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];
    
    $errors = [];
    
    // Validation
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert each item as a separate order
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    user_id, 
                    artwork_id, 
                    artist_id, 
                    quantity, 
                    total_price, 
                    status, 
                    order_date, 
                    payment_method, 
                    address, 
                    full_name, 
                    email, 
                    phone
                ) VALUES (?, ?, ?, ?, ?, 'Pending', NOW(), ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $orderIds = [];
            
            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                
                $stmt->bind_param(
                    "iiiddsssss", 
                    $user,                          
                    $item['artwork_id'],            
                    $item['artist_id'],             
                    $item['quantity'],              
                    $subtotal,                      
                    $payment_method,                
                    $address,                       
                    $full_name,                     
                    $email,                         
                    $phone                          
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert order: " . $stmt->error);
                }
                
                $orderIds[] = $conn->insert_id;
            }
            
            $stmt->close();
            
            // Get the first order ID
            $firstOrderId = $orderIds[0];
            
            // Clear cart only after successful order creation
            $clearCart = $conn->query("DELETE FROM cart WHERE user_id = '$user'");
            
            if (!$clearCart) {
                throw new Exception("Failed to clear cart: " . $conn->error);
            }
            
            // Commit transaction
            $conn->commit();
            
         // If eSewa payment, redirect to eSewa
        if ($payment_method == 'esewa') {
            // Store order info in session for eSewa
            $_SESSION['esewa_order_id'] = $firstOrderId;
            $_SESSION['esewa_amount'] = $total;
            
            // Redirect to eSewa payment page (in customer folder)
            header("Location: esewa_payment.php?order_id=" . urlencode($firstOrderId) . "&amount=" . $total);
            exit();
        }
            
            // If COD, redirect to success
            $_SESSION['order_success'] = "Order placed successfully!";
            header("Location: order_success.php?order_id=" . urlencode($firstOrderId) . "&payment=cod");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Failed to place order: " . $e->getMessage();
            error_log("Order Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Monet's Atelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --monet-deep: #2c4b5a;
            --monet-gold: #c9a87c;
            --bg: #f5efe9;
            --shadow: 0 12px 28px rgba(44,75,90,.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Quicksand', sans-serif;
        }

        body {
            background: var(--bg);
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 25px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--monet-deep);
        }

        .logo i {
            color: var(--monet-gold);
        }

        .nav {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav a {
            text-decoration: none;
            color: var(--monet-deep);
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 10px;
            transition: .3s;
        }

        .nav a:hover {
            background: var(--monet-deep);
            color: white;
        }

        .nav .active {
            background: var(--monet-deep);
            color: white;
        }

        .logout-btn {
            background: #c0392b;
            color: white !important;
        }

        .logout-btn:hover {
            background: #a93226 !important;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .form-section h2 {
            color: var(--monet-deep);
            margin-bottom: 20px;
        }

        .form-section h2 i {
            color: var(--monet-gold);
            margin-right: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--monet-deep);
            margin-bottom: 6px;
            font-size: 14px;
        }

        .form-group label i {
            color: var(--monet-gold);
            margin-right: 5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            font-size: 15px;
            transition: .3s;
            font-family: 'Quicksand', sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--monet-deep);
            box-shadow: 0 0 0 3px rgba(44, 75, 90, 0.1);
        }

        .form-group textarea {
            height: 80px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .summary-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            align-self: start;
            position: sticky;
            top: 20px;
        }

        .summary-section h2 {
            color: var(--monet-deep);
            margin-bottom: 20px;
            font-size: 20px;
        }

        .summary-section h2 i {
            color: var(--monet-gold);
            margin-right: 8px;
        }

        .summary-item {
            display: flex;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }

        .summary-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
        }

        .summary-item .item-details {
            flex: 1;
        }

        .summary-item .item-details h4 {
            color: var(--monet-deep);
            font-size: 14px;
        }

        .summary-item .item-details p {
            color: #888;
            font-size: 13px;
        }

        .summary-item .item-price {
            color: var(--monet-gold);
            font-weight: 700;
            font-size: 14px;
        }

        .summary-total {
            padding: 15px 0;
            border-top: 2px solid #e8e8e8;
            margin-top: 10px;
        }

        .summary-total .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 15px;
        }

        .summary-total .total-row.grand-total {
            font-size: 20px;
            font-weight: 700;
            color: var(--monet-deep);
            border-top: 2px solid var(--monet-gold);
            padding-top: 12px;
            margin-top: 8px;
        }

        .summary-total .total-row.grand-total .amount {
            color: var(--monet-gold);
        }

        .payment-methods {
            margin: 15px 0;
        }

        .payment-methods label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            cursor: pointer;
            transition: .3s;
            margin-bottom: 10px;
        }

        .payment-methods label:hover {
            border-color: var(--monet-gold);
            background: #faf8f6;
        }

        .payment-methods label input[type="radio"] {
            accent-color: var(--monet-deep);
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .payment-methods label .payment-icon {
            font-size: 24px;
            color: var(--monet-gold);
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }

        .payment-methods label .payment-name {
            font-weight: 600;
            color: var(--monet-deep);
            flex: 1;
        }

        .payment-methods label .payment-desc {
            font-size: 12px;
            color: #888;
            margin-left: auto;
            flex-shrink: 0;
        }

        .payment-methods label:has(input[type="radio"]:checked) {
            border-color: var(--monet-deep);
            background: #f0f5f8;
            box-shadow: 0 0 0 3px rgba(44, 75, 90, 0.1);
        }

        .place-order-btn {
            width: 100%;
            padding: 14px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: .3s;
            margin-top: 10px;
        }

        .place-order-btn:hover {
            background: #219150;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        .error-message ul {
            list-style: none;
            padding: 0;
        }

        .error-message li {
            padding: 3px 0;
        }

        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .summary-section {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .nav {
                justify-content: center;
            }

            .nav a {
                font-size: 12px;
                padding: 8px 12px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .container {
                width: 95%;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <div class="logo">
            <i class="fas fa-palette"></i> Monet's Atelier
        </div>
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
            <a href="orders.php"><i class="fas fa-box-open"></i> Orders</a>
            <a href="messages.php"><i class="fas fa-comments"></i> Messages</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <h2 style="color:var(--monet-deep);margin-bottom:25px;">
        <i class="fas fa-credit-card"></i> Checkout
    </h2>

    <div class="checkout-grid">

        <div class="form-section">
            <h2><i class="fas fa-user"></i> Shipping Details</h2>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" name="full_name" placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" name="phone" placeholder="Enter phone number" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> City</label>
                        <input type="text" name="city" placeholder="Enter your city">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-pin"></i> Address</label>
                    <textarea name="address" placeholder="Enter your full address" required></textarea>
                </div>

                <div class="payment-methods">
                    <label>
                        <input type="radio" name="payment_method" value="cod" checked>
                        <span class="payment-icon"><i class="fas fa-money-bill-wave"></i></span>
                        <span class="payment-name">Cash on Delivery</span>
                        <span class="payment-desc">Pay when you receive</span>
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="esewa">
                        <span class="payment-icon">
                            <svg width="28" height="28" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="100" cy="100" r="90" fill="#4CAF50"/>
                                <text x="100" y="135" font-family="Arial, sans-serif" font-size="110" 
                                      font-weight="bold" fill="white" text-anchor="middle" dominant-baseline="middle">
                                    E
                                </text>
                                <text x="100" y="175" font-family="Arial, sans-serif" font-size="24" 
                                      fill="white" text-anchor="middle" font-weight="bold" opacity="0.9">
                                    sewa
                                </text>
                            </svg>
                        </span>
                        <span class="payment-name">
                            <span style="color:#4CAF50;font-weight:700;">e</span><span style="font-weight:600;">Sewa</span>
                        </span>
                        <span class="payment-desc">Pay with eSewa</span>
                    </label>
                </div>

                <button type="submit" name="place_order" class="place-order-btn">
                    <i class="fas fa-check-circle"></i> Place Order
                </button>
            </form>
        </div>

        <div class="summary-section">
            <h2><i class="fas fa-receipt"></i> Order Summary</h2>

            <?php foreach ($items as $item): ?>
                <div class="summary-item">
                    <?php if (!empty($item['image']) && file_exists("../uploads/" . $item['image'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <?php else: ?>
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Crect width='60' height='60' fill='%23eee'/%3E%3C/svg%3E" alt="No image">
                    <?php endif; ?>
                    <div class="item-details">
                        <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                        <p>Qty: <?php echo $item['quantity']; ?></p>
                    </div>
                    <div class="item-price">Rs <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
            <?php endforeach; ?>

            <div class="summary-total">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>Rs <?php echo number_format($total, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="total-row grand-total">
                    <span>Grand Total</span>
                    <span class="amount">Rs <?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>

    </div>

</div>

</body>
</html>
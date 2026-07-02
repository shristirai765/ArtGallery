<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$user = $_SESSION['id'];

/* ---------- Navigation Counts ---------- */
$cartCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM cart
    WHERE user_id = '$user'
")->fetch_assoc()['total'];

$orderCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE user_id = '$user'
")->fetch_assoc()['total'] ?? 0;

$messageCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM messages
    WHERE receiver_id = '$user' AND is_read = 0
")->fetch_assoc()['total'] ?? 0;

/* ======================================
   REMOVE ITEM FROM CART (via AJAX)
====================================== */
if (isset($_GET['ajax_remove'])) {
    $cartId = (int) $_GET['ajax_remove'];
    $conn->query("
        DELETE FROM cart
        WHERE id = '$cartId'
        AND user_id = '$user'
    ");
    
    // Get updated cart count
    $newCount = $conn->query("
        SELECT COUNT(*) AS total
        FROM cart
        WHERE user_id = '$user'
    ")->fetch_assoc()['total'];
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'count' => $newCount]);
    exit();
}

/* ======================================
   UPDATE QUANTITY (via AJAX)
====================================== */
if (isset($_POST['ajax_update_qty'])) {
    $cartId = (int) $_POST['cart_id'];
    $quantity = max(1, (int) $_POST['quantity']);
    
    $conn->query("
        UPDATE cart
        SET quantity = '$quantity'
        WHERE id = '$cartId'
        AND user_id = '$user'
    ");
    
    // Get updated subtotal and total
    $cartItems = $conn->query("
        SELECT
            cart.id,
            cart.quantity,
            artworks.price
        FROM cart
        INNER JOIN artworks ON cart.artwork_id = artworks.id
        WHERE cart.user_id = '$user'
    ");
    
    $total = 0;
    $newSubtotal = 0;
    while ($item = $cartItems->fetch_assoc()) {
        if ($item['id'] == $cartId) {
            $newSubtotal = $item['price'] * $quantity;
        }
        $total += $item['price'] * $item['quantity'];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'subtotal' => number_format($newSubtotal, 2),
        'total' => number_format($total, 2)
    ]);
    exit();
}

/* ======================================
   GET CART ITEMS
====================================== */
$cart = $conn->query("
    SELECT
        cart.id,
        cart.quantity,
        artworks.id AS artwork_id,
        artworks.title,
        artworks.price,
        artworks.image,
        artworks.artist_id,
        users.username AS artist_name
    FROM cart
    INNER JOIN artworks ON cart.artwork_id = artworks.id
    INNER JOIN users ON artworks.artist_id = users.id
    WHERE cart.user_id = '$user'
    ORDER BY cart.id DESC
");

$total = 0;
$itemCount = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | Monet's Atelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --monet-deep: #2c4b5a;
            --monet-gold: #c9a87c;
            --monet-lily: #7fa3a8;
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
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
        }

        /* ===========================
           HEADER
        =========================== */
        .header {
            background: white;
            padding: 18px 30px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: var(--shadow);
            margin-bottom: 35px;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: var(--monet-deep);
        }

        .logo i {
            color: var(--monet-gold);
            margin-right: 8px;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--monet-deep);
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s;
        }

        .nav a:hover {
            background: var(--monet-deep);
            color: white;
        }

        .nav a.active {
            background: var(--monet-deep);
            color: white;
        }

        .badge {
            display: none !important;
        }

        .logout-btn {
            background: #e74c3c;
            color: white !important;
        }

        .logout-btn:hover {
            background: #c0392b !important;
        }

        /* ===========================
           PAGE HEADER
        =========================== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h2 {
            color: var(--monet-deep);
            font-size: 28px;
        }

        .page-header .item-count {
            color: #888;
            font-size: 16px;
        }

        /* ===========================
           CART CARD
        =========================== */
        .cart-item {
            display: flex;
            gap: 25px;
            background: white;
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            transition: .3s;
            align-items: center;
        }

        .cart-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(44,75,90,.15);
        }

        .cart-item .artwork-image {
            width: 180px;
            height: 150px;
            object-fit: cover;
            border-radius: 15px;
            flex-shrink: 0;
        }

        .cart-item .artwork-image-placeholder {
            width: 180px;
            height: 150px;
            background: #f0ece8;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 40px;
            flex-shrink: 0;
        }

        .cart-item .info {
            flex: 1;
        }

        .cart-item .info h3 {
            color: var(--monet-deep);
            font-size: 20px;
            margin-bottom: 8px;
        }

        .cart-item .info .artist {
            color: #777;
            font-size: 15px;
            margin-bottom: 10px;
        }

        .cart-item .info .artist i {
            color: var(--monet-gold);
            margin-right: 5px;
        }

        .cart-item .info .price {
            color: var(--monet-gold);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .cart-item .info .subtotal {
            font-size: 16px;
            color: var(--monet-deep);
            font-weight: 600;
        }

        .cart-item .info .subtotal span {
            color: var(--monet-gold);
        }

        /* Quantity Controls */
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 12px 0;
        }

        .quantity-control label {
            font-weight: 600;
            color: var(--monet-deep);
        }

        .quantity-control .qty-input {
            display: flex;
            align-items: center;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            overflow: hidden;
        }

        .quantity-control .qty-input button {
            background: #f5f5f5;
            border: none;
            padding: 6px 14px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
            color: var(--monet-deep);
            transition: .2s;
        }

        .quantity-control .qty-input button:hover {
            background: var(--monet-deep);
            color: white;
        }

        .quantity-control .qty-input input {
            width: 50px;
            text-align: center;
            border: none;
            border-left: 1px solid #e8e8e8;
            border-right: 1px solid #e8e8e8;
            padding: 6px 0;
            font-weight: 600;
            font-size: 16px;
        }

        .quantity-control .qty-input input:focus {
            outline: none;
        }

        .quantity-control .update-btn {
            background: var(--monet-deep);
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
        }

        .quantity-control .update-btn:hover {
            background: #203845;
        }

        .cart-item .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .cart-item .actions a {
            text-decoration: none;
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cart-item .actions a:hover {
            transform: translateY(-2px);
        }

        .chat-btn {
            background: #3498db;
        }

        .chat-btn:hover {
            background: #2c80b4;
        }

        .remove-btn {
            background: #e74c3c;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        .remove-btn:hover {
            background: #c0392b;
        }

        /* ===========================
           SUMMARY
        =========================== */
        .summary {
            background: white;
            padding: 30px 35px;
            border-radius: 20px;
            margin-top: 20px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .summary .total-label {
            font-size: 18px;
            color: #888;
        }

        .summary .total-amount {
            font-size: 32px;
            font-weight: 700;
            color: var(--monet-gold);
        }

        .summary .checkout-btn {
            background: #27ae60;
            color: white;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 700;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .summary .checkout-btn:hover {
            background: #219150;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        /* ===========================
           EMPTY CART
        =========================== */
        .empty {
            background: white;
            padding: 70px;
            text-align: center;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .empty i {
            font-size: 70px;
            color: var(--monet-gold);
            margin-bottom: 20px;
            display: block;
        }

        .empty h2 {
            color: var(--monet-deep);
            margin-bottom: 10px;
        }

        .empty p {
            color: #888;
        }

        .empty .back-btn {
            display: inline-block;
            margin-top: 25px;
            text-decoration: none;
            background: var(--monet-deep);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s;
        }

        .empty .back-btn:hover {
            background: #203845;
            transform: translateY(-2px);
        }

        /* ===========================
           TOAST NOTIFICATION
        =========================== */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #27ae60;
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }

        .toast.error {
            background: #e74c3c;
        }

        .toast.info {
            background: #3498db;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* ===========================
           RESPONSIVE
        =========================== */
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

            .cart-item {
                flex-direction: column;
                align-items: stretch;
            }

            .cart-item .artwork-image,
            .cart-item .artwork-image-placeholder {
                width: 100%;
                height: 200px;
            }

            .summary {
                flex-direction: column;
                text-align: center;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .container {
                width: 95%;
            }

            .quantity-control {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .cart-item .actions {
                flex-direction: column;
            }

            .cart-item .actions a {
                justify-content: center;
            }

            .summary .total-amount {
                font-size: 24px;
            }

            .empty {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="header">
        <div class="logo">
            <i class="fas fa-palette"></i>
            Monet's Atelier
        </div>
        <div class="nav">
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="cart.php" class="active">
                <i class="fas fa-shopping-cart"></i> Cart
            </a>
            <a href="orders.php">
                <i class="fas fa-box-open"></i> Orders
            </a>
            <a href="messages.php">
                <i class="fas fa-comments"></i> Messages
            </a>
            <a href="profile.php">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-shopping-cart"></i> My Cart</h2>
        <span class="item-count" id="itemCount">
            <?php if ($cart->num_rows > 0): ?>
                <?php echo $cart->num_rows; ?> item<?php echo $cart->num_rows > 1 ? 's' : ''; ?> in your cart
            <?php endif; ?>
        </span>
    </div>

    <!-- Cart Items -->
    <div id="cartItems">
        <?php if ($cart->num_rows > 0): ?>
            <?php while ($item = $cart->fetch_assoc()): 
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
                $itemCount++;
            ?>
                <div class="cart-item" data-cart-id="<?php echo $item['id']; ?>">
                    <?php if (!empty($item['image']) && file_exists("../uploads/" . $item['image'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             class="artwork-image">
                    <?php else: ?>
                        <div class="artwork-image-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>

                    <div class="info">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="artist">
                            <i class="fas fa-user"></i> Artist: <?php echo htmlspecialchars($item['artist_name']); ?>
                        </p>
                        <p class="price">Rs <?php echo number_format($item['price'], 2); ?></p>

                        <!-- Quantity Control -->
                        <div class="quantity-control">
                            <label>Quantity:</label>
                            <div class="qty-input">
                                <button type="button" class="qty-btn" data-action="decrement">−</button>
                                <input type="number" class="qty-input-field" value="<?php echo $item['quantity']; ?>" min="1" max="99" data-cart-id="<?php echo $item['id']; ?>">
                                <button type="button" class="qty-btn" data-action="increment">+</button>
                            </div>
                            <button type="button" class="update-btn update-qty-btn" data-cart-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-sync"></i> Update
                            </button>
                        </div>

                        <p class="subtotal">
                            Subtotal: <span class="item-subtotal" data-cart-id="<?php echo $item['id']; ?>">Rs <?php echo number_format($subtotal, 2); ?></span>
                        </p>

                        <div class="actions">
                            <a href="chat.php?artist=<?php echo $item['artist_id']; ?>&art=<?php echo $item['artwork_id']; ?>" class="chat-btn">
                                <i class="fas fa-comments"></i> Contact Artist
                            </a>
                            <button class="remove-btn" data-cart-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Summary -->
            <div class="summary">
                <div>
                    <span class="total-label">Grand Total</span>
                    <div class="total-amount" id="grandTotal">Rs <?php echo number_format($total, 2); ?></div>
                </div>
                <a href="checkout.php" class="checkout-btn">
                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                </a>
            </div>

        <?php else: ?>
            <!-- Empty Cart -->
            <div class="empty">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty!</h2>
                <p>Browse our gallery and add your favorite artworks to your cart.</p>
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Toast Notification -->
<div id="toast" class="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Updated!</span>
</div>

<script>
    // Toast function
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.textContent = message;
        toast.className = 'toast';
        if (type === 'error') toast.classList.add('error');
        if (type === 'info') toast.classList.add('info');
        toast.style.display = 'flex';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }

    // Quantity increment/decrement
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.qty-input').querySelector('.qty-input-field');
            let val = parseInt(input.value) || 1;
            if (this.dataset.action === 'increment') {
                val = Math.min(val + 1, 99);
            } else {
                val = Math.max(val - 1, 1);
            }
            input.value = val;
        });
    });

    // Update quantity via AJAX
    document.querySelectorAll('.update-qty-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartId = this.dataset.cartId;
            const input = this.closest('.quantity-control').querySelector('.qty-input-field');
            const quantity = parseInt(input.value) || 1;
            
            if (quantity < 1) {
                showToast('Quantity must be at least 1', 'error');
                return;
            }
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax_update_qty=1&cart_id=' + cartId + '&quantity=' + quantity
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update subtotal
                    const subtotalSpan = document.querySelector('.item-subtotal[data-cart-id="' + cartId + '"]');
                    if (subtotalSpan) {
                        subtotalSpan.textContent = 'Rs ' + data.subtotal;
                    }
                    
                    // Update grand total
                    const grandTotal = document.getElementById('grandTotal');
                    if (grandTotal) {
                        grandTotal.textContent = 'Rs ' + data.total;
                    }
                    
                    showToast('Quantity updated successfully!');
                }
            })
            .catch(error => {
                showToast('Error updating quantity', 'error');
                console.error('Error:', error);
            })
            .finally(() => {
                this.innerHTML = '<i class="fas fa-sync"></i> Update';
                this.disabled = false;
            });
        });
    });

    // Remove item via AJAX
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartId = this.dataset.cartId;
            
            if (!confirm('Remove this artwork from your cart?')) return;
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            fetch('cart.php?ajax_remove=' + cartId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the cart item from DOM
                    const cartItem = document.querySelector('.cart-item[data-cart-id="' + cartId + '"]');
                    if (cartItem) {
                        cartItem.style.transition = 'opacity 0.3s';
                        cartItem.style.opacity = '0';
                        setTimeout(() => {
                            cartItem.remove();
                            
                            // Update item count
                            const itemCount = document.getElementById('itemCount');
                            if (itemCount) {
                                const count = data.count;
                                if (count > 0) {
                                    itemCount.textContent = count + ' item' + (count > 1 ? 's' : '') + ' in your cart';
                                } else {
                                    itemCount.textContent = '';
                                    // Show empty cart
                                    location.reload();
                                }
                            }
                            
                            // Update grand total
                            const grandTotal = document.getElementById('grandTotal');
                            if (grandTotal) {
                                // Recalculate total from remaining items
                                let newTotal = 0;
                                document.querySelectorAll('.cart-item').forEach(item => {
                                    const priceText = item.querySelector('.price').textContent.replace('Rs ', '').replace(/,/g, '');
                                    const qty = parseInt(item.querySelector('.qty-input-field').value);
                                    newTotal += parseFloat(priceText) * qty;
                                });
                                grandTotal.textContent = 'Rs ' + newTotal.toFixed(2);
                            }
                            
                            showToast('Item removed from cart!');
                        }, 300);
                    }
                }
            })
            .catch(error => {
                showToast('Error removing item', 'error');
                console.error('Error:', error);
                this.innerHTML = '<i class="fas fa-trash"></i> Remove';
                this.disabled = false;
            });
        });
    });

    // Auto-update quantity when Enter key is pressed
    document.querySelectorAll('.qty-input-field').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const updateBtn = this.closest('.quantity-control').querySelector('.update-qty-btn');
                if (updateBtn) {
                    updateBtn.click();
                }
            }
        });
    });
</script>

</body>
</html>
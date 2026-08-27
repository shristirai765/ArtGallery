<?php

session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

$user = (int)$_SESSION['id'];

if (!isset($_GET['artist'])) {
    die("Artist not found.");
}

$artist = (int)$_GET['artist'];


// ================================
// GET ARTIST INFO
// ================================

$stmt = $conn->prepare("
    SELECT username, profile_image, last_activity
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $artist);
$stmt->execute();

$artistInfo = $stmt->get_result()->fetch_assoc();

if (!$artistInfo) {
    die("Artist not found.");
}

$artistName = $artistInfo['username'];
$artistProfileImage = $artistInfo['profile_image'] ?? null;


// ================================
// ARTIST ONLINE STATUS
// ================================

$lastActivity = strtotime($artistInfo['last_activity'] ?? '');

$isOnline = (
    $lastActivity &&
    (time() - $lastActivity) < (5 * 60)
);


// ================================
// GET CUSTOMER INFO
// ================================

$stmt = $conn->prepare("
    SELECT username, profile_image
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user);
$stmt->execute();

$userInfo = $stmt->get_result()->fetch_assoc();

$userProfileImage = $userInfo['profile_image'] ?? null;


// ================================
// SEND MESSAGE
// ================================

if (isset($_POST['send'])) {

    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {

        $stmt = $conn->prepare("
            INSERT INTO messages
            (sender_id, receiver_id, artwork_id, message, is_read, created_at)
            VALUES (?, ?, NULL, ?, 0, NOW())
        ");

        $stmt->bind_param(
            "iis",
            $user,
            $artist,
            $message
        );

        if (!$stmt->execute()) {
            die("Message could not be sent: " . $stmt->error);
        }

        header("Location: chat.php?artist=" . $artist);
        exit();
    }
}


// ================================
// GET PREVIOUS MESSAGES
// ================================

$stmt = $conn->prepare("
    SELECT
        id,
        sender_id,
        receiver_id,
        message,
        is_read,
        created_at
    FROM messages
    WHERE
        (sender_id = ? AND receiver_id = ?)
        OR
        (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC, id ASC
");

$stmt->bind_param(
    "iiii",
    $user,
    $artist,
    $artist,
    $user
);

$stmt->execute();

$messages = $stmt->get_result();


// ================================
// MARK MESSAGES AS READ
// ================================

$stmt = $conn->prepare("
    UPDATE messages
    SET is_read = 1
    WHERE sender_id = ?
    AND receiver_id = ?
");

$stmt->bind_param("ii", $artist, $user);
$stmt->execute();


// ================================
// COUNTS
// ================================

$cartCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM cart
    WHERE user_id = '$user'
")->fetch_assoc()['total'];

$orderCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE user_id = '$user'
")->fetch_assoc()['total'];

$messageCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM messages
    WHERE receiver_id = '$user'
    AND is_read = 0
")->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($artistName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep: #2c4b5a;
            --gold: #c9a87c;
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
        }

        .container {
            width: 90%;
            max-width: 900px;
            margin: 20px auto;
        }

        /* Navigation */
        .header {
            background: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--deep);
        }

        .logo i {
            color: var(--gold);
        }

        .nav {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav a {
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            color: var(--deep);
            font-weight: 600;
            transition: .3s;
        }

        .nav a:hover,
        .nav .active {
            background: var(--deep);
            color: white;
        }

        /* Badge completely hidden */
        .badge {
            display: none !important;
        }

        .logout {
            background: #c0392b;
            color: white!important;
        }

        .logout:hover {
            background: #a93226!important;
        }

        /* Chat Box */
        .chat-box {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
        }

        .messages {
            height: 500px;
            overflow-y: auto;
            background: #f7f7f7;
            padding: 25px;
            display: flex;
            flex-direction: column;
        }

        .date-divider {
            text-align: center;
            margin: 20px 0;
            color: #888;
            font-size: 13px;
            font-weight: bold;
            position: relative;
        }

        .date-divider::before,
        .date-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: #e0e0e0;
        }

        .date-divider::before {
            left: 0;
        }

        .date-divider::after {
            right: 0;
        }

        .message {
            display: flex;
            flex-direction: column;
            max-width: 75%;
            margin: 8px 0;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sent {
            margin-left: auto;
            align-items: flex-end;
        }

        .received {
            margin-right: auto;
            align-items: flex-start;
        }

        /* Message with avatar */
        .message-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            max-width: 100%;
        }

        .received .message-wrapper {
            flex-direction: row;
        }

        .sent .message-wrapper {
            flex-direction: row-reverse;
        }

        .msg-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid var(--gold);
        }

        .msg-avatar-placeholder {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--deep);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            flex-shrink: 0;
            border: 2px solid var(--gold);
        }

        .bubble-container {
            display: flex;
            flex-direction: column;
            max-width: 85%;
        }

        .bubble {
            padding: 10px 16px;
            border-radius: 18px;
            word-wrap: break-word;
            line-height: 1.5;
        }

        .sent .bubble {
            background: #2c4b5a;
            color: white;
            border-radius: 18px 18px 4px 18px;
        }

        .received .bubble {
            background: white;
            color: #333;
            border-radius: 18px 18px 18px 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .message-text {
            word-wrap: break-word;
            white-space: pre-wrap;
        }

        .time {
            margin-top: 4px;
            font-size: 11px;
            color: #888;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .sent .time {
            justify-content: flex-end;
            padding-right: 42px;
        }

        .received .time {
            justify-content: flex-start;
            padding-left: 42px;
        }

        .time .sender {
            font-weight: 600;
            color: #666;
        }

        .time .separator {
            color: #ccc;
        }

        .time .timestamp {
            color: #999;
        }

        .message-form textarea:focus {
            outline: none;
        }

        .message-form {
            background: white;
            border-top: 1px solid #eee;
            padding: 18px 20px;
        }

        .input-group {
            width: 100%;
            display: flex;
            align-items: flex-end;
            gap: 12px;
        }

        .message-form textarea {
            height: 55px;
            border-radius: 30px;
            border: 1px solid #ddd;
            padding: 16px 20px;
            flex: 1;
            resize: none;
            font-family: 'Quicksand', sans-serif;
        }

        .send-btn {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #2c4b5a;
            color: white;
            transition: .25s;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
        }

        .send-btn:hover {
            transform: scale(1.08);
            background: #203845;
        }

        .send-btn:disabled {
            opacity: .7;
            cursor: not-allowed;
            transform: none;
        }

        .empty-chat {
            display: none;
            text-align: center;
            color: #999;
            margin: 40px 0;
        }

        .empty-chat i {
            font-size: 70px;
            color: #c9a87c;
            margin-bottom: 20px;
        }

        .chat-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .chat-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--gold);
            flex-shrink: 0;
        }

        .chat-avatar-placeholder {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: var(--deep);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
            flex-shrink: 0;
            border: 3px solid var(--gold);
        }

        .header-info h3 {
            color: #2c4b5a;
            margin-bottom: 5px;
        }

        .online {
            color: #27ae60;
            font-size: 14px;
        }

        .online i {
            font-size: 10px;
            margin-right: 5px;
        }

        .offline {
            color: #999;
            font-size: 14px;
        }

        .offline i {
            font-size: 10px;
            margin-right: 5px;
        }

        /* Scrollbar */
        .messages::-webkit-scrollbar {
            width: 8px;
        }

        .messages::-webkit-scrollbar-thumb {
            background: #c9a87c;
            border-radius: 10px;
        }

        .messages::-webkit-scrollbar-track {
            background: #f3f3f3;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 70px;
            color: #c9a87c;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #666;
        }

        .empty-state p {
            color: #aaa;
        }

        /* Responsive */
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
            
            .message {
                max-width: 90%;
            }
            
            .container {
                width: 95%;
            }

            .chat-avatar {
                width: 45px;
                height: 45px;
            }

            .chat-avatar-placeholder {
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Navigation -->
    <div class="header">
        <div class="logo">
            <i class="fas fa-palette"></i>
            Monet's Atelier
        </div>
        <div class="nav">
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="cart.php">
                <i class="fas fa-shopping-cart"></i> Cart
            </a>
            <a href="orders.php">
                <i class="fas fa-box-open"></i> Orders
            </a>
            <a href="messages.php" class="active">
                <i class="fas fa-comments"></i> Messages
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="../logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <h2 style="margin-bottom:20px;color:#2c4b5a;">
        <i class="fas fa-comments"></i>
        Chat with <?php echo htmlspecialchars($artistName); ?>
    </h2>

    <div class="chat-box">
        <!-- Chat Header -->
        <div class="chat-header">
            <?php if (!empty($artistProfileImage) && file_exists("../uploads/profile/" . $artistProfileImage)): ?>
                <img class="chat-avatar" src="../uploads/profile/<?php echo htmlspecialchars($artistProfileImage); ?>" 
                     alt="<?php echo htmlspecialchars($artistName); ?>">
            <?php else: ?>
                <div class="chat-avatar-placeholder">
                    <?php echo strtoupper(substr($artistName, 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div class="header-info">
                <h3><?php echo htmlspecialchars($artistName); ?></h3>
                <span class="<?php echo $isOnline ? 'online' : 'offline'; ?>">
                    <i class="fas fa-circle"></i> 
                    <?php echo $isOnline ? 'Online' : 'Offline'; ?>
                </span>
            </div>
        </div>

        <!-- Messages -->
        <div class="messages" id="chatMessages"></div>

        <!-- Empty State -->
        <div class="empty-chat">
            <i class="fas fa-comments"></i>
            <h3>Start the conversation</h3>
            <p>Send your first message to the artist.</p>
        </div>

        <!-- Message Form -->
        <form id="chatForm" class="message-form" method="POST">
            <input type="hidden" name="send" value="1">
            <div class="input-group">
                <textarea
                    name="message"
                    placeholder="Type a message..."
                    required></textarea>
                <button type="submit" class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
        let lastMessages = "";
        
        function loadMessages() {
            const box = document.getElementById("chatMessages");
            const isNearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 80;
            
                fetch("load_messages.php?artist=<?php echo $artist; ?>")
                .then(res => res.text())
                .then(data => {
                    if (data !== lastMessages) {
                        box.innerHTML = data;
                        lastMessages = data;
                    }
                    
                    if (isNearBottom) {
                        box.scrollTop = box.scrollHeight;
                    }
                    
                    document.querySelector(".empty-chat").style.display = 
                        data.trim() === "" ? "block" : "none";
                })
                .catch(err => console.error('Error loading messages:', err));
        }
        
        // Load chat when page opens
        loadMessages();
        
        // Auto-scroll to bottom
        setTimeout(() => {
            const box = document.getElementById("chatMessages");
            box.scrollTop = box.scrollHeight;
        }, 500);
        
        // Refresh every 2 seconds
        setInterval(loadMessages, 2000);
    </script>
</body>
</html>
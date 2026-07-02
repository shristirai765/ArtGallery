<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$user = $_SESSION['id'];

/* -----------------------------
   Navigation Counts
------------------------------ */

$cartCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM cart
    WHERE user_id='$user'
")->fetch_assoc()['total'];

$orderCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE user_id='$user'
")->fetch_assoc()['total'];
$messageCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM messages
    WHERE receiver_id='$user' AND is_read = 0
")->fetch_assoc()['total'];

/* -----------------------------
   Get Conversations with Profile Images
------------------------------ */

$conversations = $conn->query("
    SELECT
        users.id,
        users.username,
        users.profile_image,
        users.last_activity,
        MAX(messages.created_at) AS last_message
    FROM messages
    JOIN users
    ON IF(messages.sender_id='$user',
          messages.receiver_id,
          messages.sender_id)=users.id
    WHERE messages.sender_id='$user'
    OR messages.receiver_id='$user'
    GROUP BY users.id
    ORDER BY last_message DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Monet's Atelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep: #2c4b5a;
            --gold: #c9a87c;
            --bg: #f5efe9;
            --shadow: 0 10px 25px rgba(0,0,0,.08);
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
            max-width: 1400px;
            margin: 20px auto;
        }

        /* Header */
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
            font-size: 28px;
            font-weight: 700;
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
            color: var(--deep);
            padding: 10px 16px;
            border-radius: 10px;
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
            color: white !important;
        }

        .logout:hover {
            background: #a93226 !important;
        }

        .page-title {
            color: var(--deep);
            margin-bottom: 25px;
        }

        /* Conversation Card */
        .message-card {
            background: white;
            padding: 20px 25px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            box-shadow: var(--shadow);
            transition: .3s;
            gap: 15px;
        }

        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(44,75,90,.15);
        }

        .message-card .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            min-width: 0;
        }

        .message-card .avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .message-card .avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold);
            background: var(--deep);
        }

        .message-card .avatar-placeholder {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: var(--deep);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 22px;
            font-weight: bold;
            border: 2px solid var(--gold);
        }

        .message-card .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .message-card .status-dot.online {
            background: #27ae60;
        }

        .message-card .status-dot.offline {
            background: #ccc;
        }

        .message-card .user-details {
            flex: 1;
            min-width: 0;
        }

        .message-card .user-details h2 {
            color: var(--deep);
            font-size: 18px;
            margin-bottom: 3px;
        }

        .message-card .user-details h2 .online-text {
            font-size: 12px;
            color: #27ae60;
            font-weight: 400;
            margin-left: 5px;
        }

        .message-card .user-details .last-msg {
            color: #888;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            max-width: 300px;
        }

        .message-card .user-details .last-msg i {
            color: var(--gold);
            margin-right: 5px;
        }

        .message-card .user-details .last-msg .time {
            color: #aaa;
            font-size: 12px;
            margin-left: 8px;
        }

        .message-card .unread-badge {
            background: #e74c3c;
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .open-btn {
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            font-size: 14px;
        }

        .open-btn:hover {
            background: #2d89c7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .empty {
            background: white;
            padding: 80px 40px;
            text-align: center;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .empty i {
            font-size: 70px;
            color: #c9a87c;
            margin-bottom: 20px;
            display: block;
        }

        .empty h2 {
            color: var(--deep);
            margin-bottom: 10px;
        }

        .empty p {
            color: #666;
        }

        .empty .browse-btn {
            display: inline-block;
            margin-top: 25px;
            background: var(--deep);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: .3s;
        }

        .empty .browse-btn:hover {
            background: #203845;
            transform: translateY(-2px);
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

            .message-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                padding: 18px;
            }

            .message-card .user-info {
                flex-direction: column;
                text-align: center;
            }

            .message-card .user-details .last-msg {
                max-width: 100%;
            }

            .container {
                width: 95%;
            }

            .message-card .avatar,
            .message-card .avatar-placeholder {
                width: 50px;
                height: 50px;
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            .open-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="container">

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

    <h1 class="page-title">
        <i class="fas fa-comments"></i>
        Messages
    </h1>

    <?php if ($conversations->num_rows > 0): ?>
        <?php while ($chat = $conversations->fetch_assoc()): 
            // Check online status
            $lastActivity = strtotime($chat['last_activity'] ?? '');
            $currentTime = time();
            $isOnline = ($lastActivity && ($currentTime - $lastActivity) < (5 * 60));
            
            // Get last message preview
            $lastMsgQuery = $conn->prepare("
                SELECT message FROM messages 
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at DESC LIMIT 1
            ");
            $lastMsgQuery->bind_param("iiii", $user, $chat['id'], $chat['id'], $user);
            $lastMsgQuery->execute();
            $lastMsgResult = $lastMsgQuery->get_result();
            $lastMsg = $lastMsgResult->fetch_assoc();
        ?>
            <div class="message-card">
                <div class="user-info">
                    <div class="avatar-wrapper">
                        <?php if (!empty($chat['profile_image']) && file_exists("../uploads/profile/" . $chat['profile_image'])): ?>
                            <img class="avatar" src="../uploads/profile/<?php echo htmlspecialchars($chat['profile_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($chat['username']); ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($chat['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <span class="status-dot <?php echo $isOnline ? 'online' : 'offline'; ?>"></span>
                    </div>
                    <div class="user-details">
                        <h2>
                            <?php echo htmlspecialchars($chat['username']); ?>
                            <?php if ($isOnline): ?>
                                <span class="online-text">● Online</span>
                            <?php endif; ?>
                        </h2>
                        <span class="last-msg">
                            <?php if ($lastMsg): ?>
                                <i class="fas fa-comment"></i>
                                <?php echo htmlspecialchars(substr($lastMsg['message'], 0, 60)) . (strlen($lastMsg['message']) > 60 ? '...' : ''); ?>
                            <?php else: ?>
                                <i class="fas fa-comment"></i>
                                No messages yet
                            <?php endif; ?>
                            <span class="time">
                                <?php echo date("d M Y h:i A", strtotime($chat['last_message'])); ?>
                            </span>
                        </span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:center;">
                    <?php 
                    // Get unread count for this conversation
                    $unreadCountQuery = $conn->prepare("
                        SELECT COUNT(*) as unread
                        FROM messages
                        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
                    ");
                    $unreadCountQuery->bind_param("ii", $chat['id'], $user);
                    $unreadCountQuery->execute();
                    $unreadCountResult = $unreadCountQuery->get_result();
                    $unreadData = $unreadCountResult->fetch_assoc();
                    $unreadCount = $unreadData['unread'] ?? 0;
                    ?>
                    <?php if ($unreadCount > 0): ?>
                        <span class="unread-badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                    <a class="open-btn" href="chat.php?artist=<?php echo $chat['id']; ?>">
                        <i class="fas fa-paper-plane"></i> Open Chat
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty">
            <i class="fas fa-comments"></i>
            <h2>No conversations yet.</h2>
            <p>Start chatting with an artist from an artwork page.</p>
            <a href="dashboard.php" class="browse-btn">
                <i class="fas fa-image"></i> Browse Artworks
            </a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
<?php
// Remove the include line - it's already handled in chat.php
// include "update_activity.php"; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

$loggedUser = (int)$_SESSION['id'];

// Update user's last activity
$update = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$update->bind_param("i", $loggedUser);
$update->execute();

// Get all users that the logged user has chatted with
// with unread message counts and profile images
$query = "
    SELECT 
        u.id,
        u.username,
        u.profile_image,
        COUNT(m.id) as total_messages,
        SUM(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 ELSE 0 END) as unread_count,
        MAX(m.created_at) as last_message_time
    FROM users u
    LEFT JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?)
        OR (m.receiver_id = u.id AND m.sender_id = ?)
    WHERE u.id != ?
    GROUP BY u.id
    HAVING total_messages > 0
    ORDER BY last_message_time DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $loggedUser, $loggedUser, $loggedUser, $loggedUser);
$stmt->execute();
$result = $stmt->get_result();

// Get other navigation counts - only what artists need
$orderCount = $conn->query("SELECT COUNT(*) total FROM orders WHERE artist_id = $loggedUser")->fetch_assoc()['total'];
$artworkCount = $conn->query("SELECT COUNT(*) total FROM artworks WHERE artist_id = $loggedUser")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Artist Dashboard</title>
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
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav a:hover,
        .nav .active {
            background: var(--deep);
            color: white;
        }
        
        /* Badges completely hidden */
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
        
        .messages-list {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
        }
        
        .message-item {
            display: flex;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            transition: .2s;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        
        .message-item:hover {
            background: #f8f5f0;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            margin-right: 15px;
            border: 2px solid var(--gold);
            background: var(--deep);
        }
        
        .message-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--deep);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            font-weight: bold;
            flex-shrink: 0;
            margin-right: 15px;
            border: 2px solid var(--gold);
        }
        
        .message-info {
            flex: 1;
            min-width: 0;
        }
        
        .message-info h3 {
            font-size: 16px;
            color: var(--deep);
            margin-bottom: 3px;
        }
        
        .message-info .last-msg {
            font-size: 14px;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            max-width: 300px;
        }
        
        .message-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
            flex-shrink: 0;
            margin-left: 10px;
        }
        
        .unread-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .message-time {
            font-size: 12px;
            color: #aaa;
        }
        
        .no-messages {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-messages i {
            font-size: 70px;
            color: #c9a87c;
            margin-bottom: 20px;
            display: block;
        }
        
        .no-messages h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .no-messages p {
            color: #aaa;
        }
        
        /* Online Status Indicator */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
        }
        
        .status-indicator.online {
            background: #27ae60;
        }
        
        .status-indicator.offline {
            background: #ccc;
        }
        
        .message-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
            margin-right: 15px;
        }
        
        .message-avatar-wrapper .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .message-avatar-wrapper .status-dot.online {
            background: #27ae60;
        }
        
        .message-avatar-wrapper .status-dot.offline {
            background: #ccc;
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
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .message-item {
                padding: 15px;
            }
            
            .message-avatar {
                width: 40px;
                height: 40px;
                margin-right: 10px;
            }
            
            .message-avatar-placeholder {
                width: 40px;
                height: 40px;
                font-size: 16px;
                margin-right: 10px;
            }
            
            .message-info .last-msg {
                max-width: 120px;
            }
            
            .message-meta {
                margin-left: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-paint-brush"></i> Monet's Atelier
            </div>
            <div class="nav">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="orders.php">
                    <i class="fas fa-box"></i> Orders
                </a>
                <a href="messages.php" class="active">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="artworks.php">
                    <i class="fas fa-paint-brush"></i> My Artworks
                </a>
                <a href="add_artwork.php">
                    <i class="fas fa-plus-circle"></i> Add Artwork
                </a>
                <a href="profile.php">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <h2 style="margin-bottom:20px;color:#2c4b5a;">
            <i class="fas fa-comments"></i>
            Conversations
        </h2>
        
        <div class="messages-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    // Check if user is online
                    $onlineCheck = $conn->prepare("SELECT last_activity FROM users WHERE id = ?");
                    $onlineCheck->bind_param("i", $row['id']);
                    $onlineCheck->execute();
                    $onlineResult = $onlineCheck->get_result();
                    $onlineData = $onlineResult->fetch_assoc();
                    
                    $lastActivity = strtotime($onlineData['last_activity'] ?? '');
                    $currentTime = time();
                    $isOnline = ($lastActivity && ($currentTime - $lastActivity) < (5 * 60));
                    ?>
                    
                    <a href="chat.php?user=<?php echo $row['id']; ?>" class="message-item">
                        <div class="message-avatar-wrapper">
                            <?php if (!empty($row['profile_image']) && file_exists("../uploads/profile/" . $row['profile_image'])): ?>
                                <img class="message-avatar" src="../uploads/profile/<?php echo htmlspecialchars($row['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['username']); ?>">
                            <?php else: ?>
                                <div class="message-avatar-placeholder">
                                    <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <span class="status-dot <?php echo $isOnline ? 'online' : 'offline'; ?>"></span>
                        </div>
                        <div class="message-info">
                            <h3>
                                <?php echo htmlspecialchars($row['username']); ?>
                                <?php if ($isOnline): ?>
                                    <span style="font-size:12px;color:#27ae60;font-weight:400;">● Online</span>
                                <?php endif; ?>
                            </h3>
                            <span class="last-msg">
                                <?php 
                                // Get the last message for preview
                                $lastMsgQuery = $conn->prepare("
                                    SELECT message FROM messages 
                                    WHERE (sender_id = ? AND receiver_id = ?) 
                                       OR (sender_id = ? AND receiver_id = ?)
                                    ORDER BY created_at DESC LIMIT 1
                                ");
                                $lastMsgQuery->bind_param("iiii", $loggedUser, $row['id'], $row['id'], $loggedUser);
                                $lastMsgQuery->execute();
                                $lastMsgResult = $lastMsgQuery->get_result();
                                if ($lastMsg = $lastMsgResult->fetch_assoc()) {
                                    echo htmlspecialchars(substr($lastMsg['message'], 0, 50)) . (strlen($lastMsg['message']) > 50 ? '...' : '');
                                } else {
                                    echo 'No messages yet';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="message-meta">
                            <?php if ($row['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $row['unread_count']; ?></span>
                            <?php endif; ?>
                            <span class="message-time">
                                <?php 
                                if ($row['last_message_time']) {
                                    echo date('M j, g:i A', strtotime($row['last_message_time']));
                                }
                                ?>
                            </span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-messages">
                    <i class="fas fa-comments"></i>
                    <h3>No conversations yet</h3>
                    <p>Start chatting with your customers!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
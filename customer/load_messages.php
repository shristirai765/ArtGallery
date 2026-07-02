<?php

session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    exit();
}

include "../config/db.php";

$user = $_SESSION['id'];
$artist = isset($_GET['artist']) ? (int)$_GET['artist'] : 0;
$artwork = isset($_GET['art']) ? (int)$_GET['art'] : 0;

if ($artist == 0) {
    exit();
}

// Mark messages as read when viewed
$markRead = $conn->prepare("
    UPDATE messages 
    SET is_read = 1 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
");
$markRead->bind_param("ii", $artist, $user);
$markRead->execute();

// Get messages between user and artist with profile images
$stmt = $conn->prepare("
    SELECT 
        m.*,
        u.username,
        u.profile_image,
        DATE_FORMAT(m.created_at, '%Y-%m-%d') as msg_date,
        DATE_FORMAT(m.created_at, '%h:%i %p') as msg_time
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.id ASC
");
$stmt->bind_param("iiii", $user, $artist, $artist, $user);
$stmt->execute();
$result = $stmt->get_result();

$currentDate = "";
$output = "";

while ($msg = $result->fetch_assoc()) {
    $messageDate = $msg['msg_date'];
    $messageTime = $msg['msg_time'];
    
    // Add date divider
    if ($messageDate != $currentDate) {
        $output .= '<div class="date-divider">';
        
        if ($messageDate == date("Y-m-d")) {
            $output .= "Today";
        } elseif ($messageDate == date("Y-m-d", strtotime("-1 day"))) {
            $output .= "Yesterday";
        } else {
            $output .= date("d M Y", strtotime($messageDate));
        }
        
        $output .= '</div>';
        $currentDate = $messageDate;
    }
    
    // Determine if message is sent or received
    $isSent = ($msg['sender_id'] == $user);
    $messageClass = $isSent ? 'sent' : 'received';
    $senderName = $isSent ? 'You' : htmlspecialchars($msg['username']);
    $profileImage = $msg['profile_image'] ?? null;
    
    // Build avatar HTML
    $avatarHtml = '';
    if (!empty($profileImage) && file_exists("../uploads/profile/" . $profileImage)) {
        $avatarHtml = '<img class="msg-avatar" src="../uploads/profile/' . htmlspecialchars($profileImage) . '" alt="' . $senderName . '">';
    } else {
        $avatarHtml = '<div class="msg-avatar-placeholder">' . strtoupper(substr($senderName, 0, 1)) . '</div>';
    }
    
    // Build message HTML
    $output .= '
        <div class="message ' . $messageClass . '">
            <div class="message-wrapper">
                ' . $avatarHtml . '
                <div class="bubble-container">
                    <div class="bubble">
                        <div class="message-text">' . nl2br(htmlspecialchars($msg['message'])) . '</div>
                    </div>
                    <div class="time">
                        <span class="sender">' . $senderName . '</span>
                        <span class="separator">•</span>
                        <span class="timestamp">' . $messageTime . '</span>
                    </div>
                </div>
            </div>
        </div>
    ';
}

// If no messages
if ($output === '') {
    $output = '<div class="empty-state">
        <i class="fas fa-comments"></i>
        <h3>No messages yet</h3>
        <p>Start the conversation with the artist.</p>
    </div>';
}

echo $output;
?>
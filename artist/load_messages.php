<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    exit();
}

include "../config/db.php";

$loggedUser = (int)$_SESSION['id'];
$otherUser = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;

if ($otherUser === 0) {
    exit('Invalid user');
}

// Mark messages as read when they're loaded
$markRead = $conn->prepare("
    UPDATE messages 
    SET is_read = 1 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
");
$markRead->bind_param("ii", $otherUser, $loggedUser);
$markRead->execute();

// Get messages between the two users with profile images
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

$stmt->bind_param("iiii", $loggedUser, $otherUser, $otherUser, $loggedUser);
$stmt->execute();
$result = $stmt->get_result();

$lastDate = '';
$output = '';

while ($row = $result->fetch_assoc()) {
    // Add date divider
    $currentDate = $row['msg_date'];
    if ($lastDate != $currentDate) {
        $lastDate = $currentDate;
        $formattedDate = date('F j, Y', strtotime($currentDate));
        $output .= '<div class="date-divider">' . $formattedDate . '</div>';
    }
    
    $isSent = ($row['sender_id'] == $loggedUser);
    $messageClass = $isSent ? 'sent' : 'received';
    $senderName = $isSent ? 'You' : htmlspecialchars($row['username']);
    $profileImage = $row['profile_image'] ?? null;
    
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
                        <div class="message-text">' . nl2br(htmlspecialchars($row['message'])) . '</div>
                    </div>
                    <div class="time">
                        <span class="sender">' . $senderName . '</span>
                        <span class="separator">•</span>
                        <span class="timestamp">' . htmlspecialchars($row['msg_time']) . '</span>
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
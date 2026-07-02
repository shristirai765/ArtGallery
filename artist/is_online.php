<?php
// is_online.php - Helper function to check online status

function isUserOnline($conn, $userId, $timeout = 5) {
    $stmt = $conn->prepare("
        SELECT last_activity 
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $lastActivity = strtotime($row['last_activity']);
        $currentTime = time();
        $difference = $currentTime - $lastActivity;
        
        // User is online if their last activity was within the last X minutes
        return ($difference < ($timeout * 60));
    }
    
    return false;
}
?>
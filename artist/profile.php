<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'artist') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$user = $_SESSION['id'];
$message = '';
$error = '';

// Get user data
$userData = $conn->query("
    SELECT id, username, email, password, role, bio, profile_image, last_activity
    FROM users
    WHERE id = '$user'
")->fetch_assoc();

if (!$userData) {
    header("Location: ../logout.php");
    exit();
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $bio = trim($_POST['bio']);
    $email = trim($_POST['email']);
    
    $errors = [];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Check if email already exists for another user
    $emailCheck = $conn->query("
        SELECT id FROM users 
        WHERE email = '$email' AND id != '$user'
    ");
    if ($emailCheck->num_rows > 0) {
        $errors[] = "Email already in use by another account.";
    }
    
    if (empty($errors)) {
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET bio = ?, email = ?
            WHERE id = ?
        ");
        $updateStmt->bind_param("ssi", $bio, $email, $user);
        
        if ($updateStmt->execute()) {
            $message = "Profile updated successfully!";
            // Refresh user data
            $userData = $conn->query("
                SELECT id, username, email, password, role, bio, profile_image, last_activity
                FROM users
                WHERE id = '$user'
            ")->fetch_assoc();
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Verify current password
    if (!password_verify($current_password, $userData['password'])) {
        $errors[] = "Current password is incorrect.";
    }
    
    if (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updatePass = $conn->prepare("
            UPDATE users SET password = ? WHERE id = ?
        ");
        $updatePass->bind_param("si", $hashed_password, $user);
        
        if ($updatePass->execute()) {
            $message = "Password changed successfully!";
            // Refresh user data
            $userData = $conn->query("
                SELECT id, username, email, password, role, bio, profile_image, last_activity
                FROM users
                WHERE id = '$user'
            ")->fetch_assoc();
        } else {
            $error = "Failed to change password. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Handle profile image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
        $error = "Only JPEG, PNG, GIF, and WEBP images are allowed.";
    } elseif ($_FILES['profile_image']['size'] > $maxSize) {
        $error = "Image size must be less than 2MB.";
    } else {
        $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user . '_' . time() . '.' . $extension;
        $targetPath = "../uploads/profile/" . $filename;
        
        // Create directory if it doesn't exist
        if (!is_dir("../uploads/profile")) {
            mkdir("../uploads/profile", 0777, true);
        }
        
        // Delete old profile image if exists
        if (!empty($userData['profile_image']) && file_exists("../uploads/profile/" . $userData['profile_image'])) {
            unlink("../uploads/profile/" . $userData['profile_image']);
        }
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            $updateImg = $conn->prepare("
                UPDATE users SET profile_image = ? WHERE id = ?
            ");
            $updateImg->bind_param("si", $filename, $user);
            
            if ($updateImg->execute()) {
                $message = "Profile picture updated successfully!";
                $userData['profile_image'] = $filename;
            } else {
                $error = "Failed to update profile picture.";
            }
        } else {
            $error = "Failed to upload image.";
        }
    }
}

// Navigation counts
$orderCount = $conn->query("SELECT COUNT(*) total FROM orders WHERE artist_id = $user")->fetch_assoc()['total'] ?? 0;
$messageCount = $conn->query("
    SELECT COUNT(*) total 
    FROM messages 
    WHERE receiver_id = $user AND is_read = 0
")->fetch_assoc()['total'] ?? 0;
$artworkCount = $conn->query("SELECT COUNT(*) total FROM artworks WHERE artist_id = $user")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile | Monet's Atelier</title>
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
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 1200px;
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

        .badge {
            display: none !important;
        }

        .logout-btn {
            background: #c0392b;
            color: white !important;
        }

        .logout-btn:hover {
            background: #a93226 !important;
        }

        /* Profile Layout */
        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 25px;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
            text-align: center;
            align-self: start;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--monet-gold);
            margin: 0 auto 15px;
            background: #f0ece8;
        }

        .profile-image-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 4px solid var(--monet-gold);
            font-size: 60px;
            color: var(--monet-gold);
        }

        .profile-name {
            color: var(--monet-deep);
            font-size: 22px;
            margin-bottom: 5px;
        }

        .profile-username {
            color: #888;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .profile-role {
            display: inline-block;
            background: var(--monet-gold);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .profile-status {
            color: #888;
            font-size: 13px;
        }

        .profile-status i {
            color: var(--monet-gold);
        }

        .profile-status .online {
            color: #27ae60;
        }

        .profile-status .offline {
            color: #999;
        }

        .upload-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            background: var(--monet-deep);
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: .3s;
            border: none;
        }

        .upload-btn:hover {
            background: #203845;
        }

        .upload-btn input[type="file"] {
            display: none;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .form-card h2 {
            color: var(--monet-deep);
            margin-bottom: 20px;
            font-size: 22px;
        }

        .form-card h2 i {
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
            width: 18px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            font-size: 15px;
            transition: .3s;
            font-family: 'Quicksand', sans-serif;
            background: #fafafa;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--monet-lily);
            background: white;
            box-shadow: 0 0 0 3px rgba(127, 163, 168, 0.1);
        }

        .form-group input:disabled {
            background: #f0f0f0;
            cursor: not-allowed;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .form-group .hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
            display: block;
        }

        .btn-submit {
            background: var(--monet-deep);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #203845;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 75, 90, 0.3);
        }

        .btn-submit i {
            color: var(--monet-gold);
        }

        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-success i {
            font-size: 18px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-error i {
            font-size: 18px;
        }

        .divider {
            border: none;
            border-top: 2px solid #f0f0f0;
            margin: 25px 0;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .profile-grid {
                grid-template-columns: 1fr;
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

            .container {
                width: 95%;
            }

            .profile-image,
            .profile-image-placeholder {
                width: 120px;
                height: 120px;
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
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="orders.php">
                <i class="fas fa-box"></i> Orders
            </a>
            <a href="messages.php">
                <i class="fas fa-comments"></i> Messages
            </a>
            <a href="artworks.php">
                <i class="fas fa-paint-brush"></i> My Artworks
            </a>
            <a href="add_artwork.php">
                <i class="fas fa-plus-circle"></i> Add Artwork
            </a>
            <a href="profile.php" class="active">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Profile Grid -->
    <div class="profile-grid">

        <!-- Profile Card -->
        <div class="profile-card">
            <?php if (!empty($userData['profile_image']) && file_exists("../uploads/profile/" . $userData['profile_image'])): ?>
                <img class="profile-image" src="../uploads/profile/<?php echo htmlspecialchars($userData['profile_image']); ?>" 
                     alt="<?php echo htmlspecialchars($userData['username']); ?>">
            <?php else: ?>
                <div class="profile-image-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>

            <h2 class="profile-name"><?php echo htmlspecialchars($userData['username']); ?></h2>
            <p class="profile-username">@<?php echo htmlspecialchars($userData['username']); ?></p>
            <span class="profile-role"><?php echo ucfirst($userData['role'] ?? 'Artist'); ?></span>

            <?php
            // Check if user is online (within last 5 minutes)
            $lastActivity = strtotime($userData['last_activity'] ?? '');
            $currentTime = time();
            $isOnline = ($lastActivity && ($currentTime - $lastActivity) < (5 * 60));
            ?>
            <p class="profile-status">
                <i class="fas fa-circle <?php echo $isOnline ? 'online' : 'offline'; ?>"></i>
                <?php echo $isOnline ? 'Online' : 'Offline'; ?>
            </p>

            <form method="POST" enctype="multipart/form-data">
                <label class="upload-btn">
                    <i class="fas fa-camera"></i> Change Photo
                    <input type="file" name="profile_image" accept="image/*" onchange="this.form.submit()">
                </label>
            </form>
        </div>

        <!-- Edit Profile Form -->
        <div class="form-card">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                    <span class="hint">Username cannot be changed</span>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" 
                           value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Bio</label>
                    <textarea name="bio" placeholder="Tell us about yourself as an artist..."><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                    <span class="hint">Share your artistic journey and inspirations</span>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Role</label>
                    <input type="text" value="<?php echo ucfirst($userData['role'] ?? 'Artist'); ?>" disabled>
                    <span class="hint">Your account type cannot be changed</span>
                </div>

                <button type="submit" name="update_profile" class="btn-submit">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>

            <hr class="divider">

            <!-- Change Password -->
            <h2><i class="fas fa-key"></i> Change Password</h2>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> New Password</label>
                    <input type="password" name="new_password" placeholder="Enter new password (min 6 characters)" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                </div>

                <button type="submit" name="change_password" class="btn-submit">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
</script>

</body>
</html>
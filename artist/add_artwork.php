<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'artist') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Update user's last activity
$update = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$update->bind_param("i", $_SESSION['id']);
$update->execute();

$success = '';
$error = '';

if (isset($_POST['submit'])) {
    $artist_id = $_SESSION['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $medium = trim($_POST['medium']);
    $genre = trim($_POST['genre']);
    $price = (float)$_POST['price'];
    
    $errors = [];
    
    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($medium)) {
        $errors[] = "Medium is required";
    }
    if (empty($genre)) {
        $errors[] = "Genre is required";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than 0";
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $errors[] = "Only JPEG, PNG, GIF, and WEBP images are allowed";
        }
        
        if ($_FILES['image']['size'] > $maxSize) {
            $errors[] = "Image size must be less than 5MB";
        }
        
        if (empty($errors)) {
            $image = $_FILES['image']['name'];
            $tmp = $_FILES['image']['tmp_name'];
            
            // Create unique filename
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            $filename = time() . "_" . uniqid() . "." . $extension;
            
            // Create uploads directory if it doesn't exist
            if (!is_dir("../uploads")) {
                mkdir("../uploads", 0777, true);
            }
            
            if (move_uploaded_file($tmp, "../uploads/" . $filename)) {
                // Insert into database
                $stmt = $conn->prepare("
                    INSERT INTO artworks (artist_id, title, description, medium, genre, price, image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->bind_param(
                    "issssds",
                    $artist_id,
                    $title,
                    $description,
                    $medium,
                    $genre,
                    $price,
                    $filename
                );
                
                if ($stmt->execute()) {
                    $success = "Artwork uploaded successfully!";
                    
                    // Clear form fields after success
                    $title = $description = $medium = $genre = $price = '';
                } else {
                    $errors[] = "Failed to save artwork. Please try again.";
                }
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    } else {
        $errors[] = "Please select an image";
    }
    
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}

// Get navigation counts for artist
$loggedUser = (int)$_SESSION['id'];
$orderCount = $conn->query("SELECT COUNT(*) total FROM orders WHERE artist_id = $loggedUser")->fetch_assoc()['total'];
$messageCount = $conn->query("
    SELECT COUNT(*) total 
    FROM messages 
    WHERE receiver_id = $loggedUser AND is_read = 0
")->fetch_assoc()['total'];
$artworkCount = $conn->query("SELECT COUNT(*) total FROM artworks WHERE artist_id = $loggedUser")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Artwork | ArtHub Artist</title>
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
            max-width: 900px;
            margin: 40px auto;
        }

        /* Navigation */
        .header-nav {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
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
            padding: 8px 14px;
            border-radius: 10px;
            color: var(--monet-deep);
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
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
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }

        .badge.hidden {
            display: none;
        }

        .logout {
            background: #c0392b;
            color: white !important;
        }

        .logout:hover {
            background: #a93226 !important;
        }

        /* Main Card */
        .card {
            background: white;
            border-radius: 25px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            padding: 35px;
            text-align: center;
        }

        .header .icon {
            font-size: 3rem;
            color: var(--monet-gold);
            margin-bottom: 10px;
        }

        .header h1 {
            color: var(--monet-deep);
            margin-bottom: 10px;
            font-size: 28px;
        }

        .header p {
            color: #5f7078;
            font-size: 16px;
        }

        .form-area {
            padding: 35px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--monet-deep);
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            font-size: 15px;
            transition: .3s;
            font-family: 'Quicksand', sans-serif;
            background: white;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--monet-lily);
            box-shadow: 0 0 0 3px rgba(127, 163, 168, 0.1);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .file-input-wrapper {
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 12px;
            cursor: pointer;
            transition: .3s;
            background: #fafafa;
            text-align: center;
        }

        .file-input-wrapper:hover {
            border-color: var(--monet-gold);
            background: #f8f4ef;
        }

        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 10px 0;
            border: none;
            cursor: pointer;
        }

        .file-input-wrapper .file-hint {
            font-size: 13px;
            color: #888;
            margin-top: 5px;
        }

        .price-input-wrapper {
            position: relative;
        }

        .price-input-wrapper .currency-prefix {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: var(--monet-deep);
            font-size: 16px;
        }

        .price-input-wrapper input {
            padding-left: 45px !important;
        }

        .actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .submit-btn {
            flex: 1;
            background: var(--monet-deep);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 150px;
        }

        .submit-btn:hover {
            background: #203845;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 75, 90, 0.3);
        }

        .back-btn {
            flex: 1;
            text-align: center;
            text-decoration: none;
            background: #7f8c8d;
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 150px;
        }

        .back-btn:hover {
            background: #6b7a7b;
            transform: translateY(-2px);
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #28a745;
        }

        .success i {
            font-size: 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #dc3545;
        }

        .error i {
            font-size: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-nav {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .nav {
                justify-content: center;
            }

            .nav a {
                font-size: 12px;
                padding: 6px 10px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            .submit-btn,
            .back-btn {
                width: 100%;
            }

            .container {
                width: 95%;
                margin: 20px auto;
            }

            .header h1 {
                font-size: 22px;
            }

            .form-area {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Navigation -->
    <div class="header-nav">
        <div class="logo">
            <i class="fas fa-paint-brush"></i> Monet's Atelier
        </div>
        <div class="nav">
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="orders.php">
                <i class="fas fa-box"></i> Orders
                <span class="badge <?php echo $orderCount == 0 ? 'hidden' : ''; ?>">
                    <?php echo $orderCount; ?>
                </span>
            </a>
            <a href="messages.php">
                <i class="fas fa-envelope"></i> Messages
                <span class="badge <?php echo $messageCount == 0 ? 'hidden' : ''; ?>">
                    <?php echo $messageCount; ?>
                </span>
            </a>
            <a href="artworks.php">
                <i class="fas fa-paint-brush"></i> My Artworks
                <!-- Badge removed from here on add artwork page -->
            </a>
            <a href="add_artwork.php" class="active">
                <i class="fas fa-plus-circle"></i> Add Artwork
            </a>
            <a href="../logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card">

        <div class="header">
            <div class="icon">
                <i class="fas fa-palette"></i>
            </div>
            <h1>Upload New Artwork</h1>
            <p>Share your creativity with collectors and art lovers.</p>
        </div>

        <div class="form-area">

            <?php if (!empty($success)): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Artwork Title <span class="required">*</span></label>
                    <input
                        type="text"
                        name="title"
                        placeholder="Enter artwork title"
                        value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                        required
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Medium <span class="required">*</span></label>
                        <input
                            type="text"
                            name="medium"
                            placeholder="e.g., Oil, Acrylic, Digital"
                            value="<?php echo isset($medium) ? htmlspecialchars($medium) : ''; ?>"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label>Genre <span class="required">*</span></label>
                        <input
                            type="text"
                            name="genre"
                            placeholder="e.g., Abstract, Portrait, Landscape"
                            value="<?php echo isset($genre) ? htmlspecialchars($genre) : ''; ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea
                        name="description"
                        placeholder="Describe your artwork..."
                    ><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Price <span class="required">*</span></label>
                    <div class="price-input-wrapper">
                        <span class="currency-prefix">Rs</span>
                        <input
                            type="number"
                            name="price"
                            min="0"
                            step="0.01"
                            placeholder="Enter price in Rupees"
                            value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Artwork Image <span class="required">*</span></label>
                    <div class="file-input-wrapper">
                        <input
                            type="file"
                            name="image"
                            accept="image/*"
                            required
                        >
                        <div class="file-hint">
                            <i class="fas fa-info-circle"></i>
                            Supported: JPEG, PNG, GIF, WEBP (Max 5MB)
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >
                        <i class="fas fa-upload"></i>
                        Upload Artwork
                    </button>

                    <a
                        href="dashboard.php"
                        class="back-btn"
                    >
                        <i class="fas fa-arrow-left"></i>
                        Dashboard
                    </a>
                </div>

            </form>

        </div>

    </div>

</div>

<script>
    // Auto-hide success/error messages after 5 seconds
    setTimeout(function() {
        const messages = document.querySelectorAll('.success, .error');
        messages.forEach(msg => {
            msg.style.transition = 'opacity 0.5s';
            msg.style.opacity = '0';
            setTimeout(() => {
                msg.style.display = 'none';
            }, 500);
        });
    }, 5000);
</script>

</body>
</html>
<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    // Prevent admin from deleting themselves
    if ($userId != $_SESSION['id']) {
        $conn->query("DELETE FROM users WHERE id = '$userId'");
        header("Location: users.php?deleted=1");
        exit();
    } else {
        header("Location: users.php?error=1");
        exit();
    }
}

// Handle role update
if (isset($_POST['update_role']) && isset($_POST['user_id']) && isset($_POST['role'])) {
    $userId = (int)$_POST['user_id'];
    $role = $_POST['role'];
    
    // Prevent admin from changing their own role
    if ($userId != $_SESSION['id']) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $userId);
        $stmt->execute();
        header("Location: users.php?updated=1");
        exit();
    }
}

// Get all users
$users = $conn->query("
    SELECT id, username, email, role, last_activity 
    FROM users 
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --monet-deep: #2c4b5a;
            --monet-gold: #c9a87c;
            --bg: #f5efe9;
            --shadow: 0 12px 28px rgba(44,75,90,.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Quicksand', sans-serif; }
        body { background: var(--bg); }

        .container { width: 95%; max-width: 1200px; margin: 20px auto; }

        /* Header - Same as homepage with palette logo */
        .header-nav {
            margin-top: 20px;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            padding: 20px 35px;
            border-radius: 60px 20px 60px 20px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: var(--monet-deep);
        }

        .logo i {
            color: var(--monet-gold);
        }

        .logo small {
            font-size: 0.8rem;
            font-weight: 400;
            color: #7f8c8d;
            display: block;
        }

        .nav {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav a {
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 40px;
            color: var(--monet-deep);
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            background: rgba(255,255,255,0.5);
        }

        .nav a:hover {
            background: var(--monet-deep);
            color: white;
            transform: translateY(-2px);
        }

        .nav .active {
            background: var(--monet-deep);
            color: white;
        }

        .logout-btn {
            background: #c0392b !important;
            color: white !important;
        }

        .logout-btn:hover {
            background: #a93226 !important;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h2 { color: var(--monet-deep); }

        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th {
            background: #f8f5f0;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--monet-deep);
        }

        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        table tr:hover td { background: #faf8f5; }

        .role-badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .role-admin { background: #ffebee; color: #e74c3c; }
        .role-artist { background: #e3f2fd; color: #3498db; }
        .role-user { background: #e8f5e9; color: #27ae60; }

        .role-select {
            padding: 5px 10px;
            border-radius: 20px;
            border: 1px solid #ddd;
            font-size: 12px;
            cursor: pointer;
            font-family: 'Quicksand', sans-serif;
        }

        .btn {
            padding: 5px 12px;
            border-radius: 15px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: .3s;
            font-family: 'Quicksand', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit { background: #e3f2fd; color: #1976d2; }
        .btn-edit:hover { background: #bbdefb; }
        .btn-delete { background: #ffebee; color: #d32f2f; }
        .btn-delete:hover { background: #ffcdd2; }
        .btn-update { background: var(--monet-deep); color: white; }
        .btn-update:hover { background: #203845; }

        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        /* Footer */
        footer {
            margin-top: 40px;
            text-align: center;
            padding: 25px;
            border-top: 1px solid #ddd;
            color: #617680;
        }

        footer i {
            color: var(--monet-gold);
        }

        @media (max-width: 768px) {
            .header-nav {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
                padding: 20px;
                border-radius: 30px;
            }

            .logo {
                font-size: 1.5rem;
                text-align: center;
            }

            .nav {
                justify-content: center;
            }

            .nav a {
                font-size: 12px;
                padding: 8px 14px;
            }

            table { font-size: 12px; }
            table th, table td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header-nav">
        <div class="logo">
            <i class="fas fa-palette"></i>
            Monet's Atelier
            <small>Admin Panel</small>
        </div>
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="users.php" class="active"><i class="fas fa-users"></i> Users</a>
            <a href="artworks.php"><i class="fas fa-paint-brush"></i> Artworks</a>
            <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="page-header">
        <h2><i class="fas fa-users"></i> Manage Users</h2>
        <span style="color:#888;font-size:14px;">Total: <?php echo $users->num_rows; ?> users</span>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> User deleted successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> User role updated successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Cannot delete your own account!</div>
    <?php endif; ?>

    <div class="table-container">
        <?php if ($users->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Last Activity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['id'] == $_SESSION['id']): ?>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                <?php else: ?>
                                    <form method="POST" style="display:flex;gap:5px;align-items:center;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role" class="role-select">
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="artist" <?php echo $user['role'] == 'artist' ? 'selected' : ''; ?>>Artist</option>
                                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Customer</option>
                                        </select>
                                        <button type="submit" name="update_role" class="btn btn-update">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['last_activity'] ? date('M j, Y g:i A', strtotime($user['last_activity'])) : 'Never'; ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['id']): ?>
                                    <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Delete this user? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#888;font-size:12px;">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No users found.</div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <footer>
        <i class="fas fa-seedling"></i>
        Inspired by Monet • Admin Panel • Manage with Ease
    </footer>

</div>

</body>
</html>
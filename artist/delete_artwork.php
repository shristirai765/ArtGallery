<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'artist') {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

$artist_id = $_SESSION['id'];

if (!isset($_GET['id'])) {
    header("Location: artist_dashboard.php");
    exit();
}

$artwork_id = (int)$_GET['id'];

/* Get artwork details */

$stmt = $conn->prepare("
    SELECT image
    FROM artworks
    WHERE id = ?
    AND artist_id = ?
");

$stmt->bind_param("ii", $artwork_id, $artist_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}

$artwork = $result->fetch_assoc();

/* Delete image file */

$imagePath = "../uploads/" . $artwork['image'];

if (!empty($artwork['image']) && file_exists($imagePath)) {
    unlink($imagePath);
}

/* Delete artwork */

$stmt = $conn->prepare("
    DELETE FROM artworks
    WHERE id = ?
    AND artist_id = ?
");

$stmt->bind_param("ii", $artwork_id, $artist_id);
$stmt->execute();

/* Optional: Remove related cart items */

$conn->query("
    DELETE FROM cart
    WHERE artwork_id = '$artwork_id'
");

/* Optional: Remove related messages */

$conn->query("
    DELETE FROM messages
    WHERE artwork_id = '$artwork_id'
");

/* Optional: Remove related orders
   Uncomment only if you want deleting an artwork
   to also delete its orders.

$conn->query("
    DELETE FROM orders
    WHERE artwork_id = '$artwork_id'
");
*/

header("Location: artist_dashboard.php?deleted=1");
exit();
?>
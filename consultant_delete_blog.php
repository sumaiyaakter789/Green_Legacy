<?php
require_once 'db_connect.php';
session_start();

// Check if consultant is logged in
if (!isset($_SESSION['consultant_logged_in'])) {
    header("Location: consultant_login.php");
    exit();
}

// Check if blog ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: consultant_blogs.php");
    exit();
}

$blog_id = $_GET['id'];
$consultant_id = $_SESSION['consultant_id'];

// Get blog details to delete image (only if owned by this consultant)
$stmt = $conn->prepare("SELECT featured_image FROM blogs WHERE id = ? AND author_id = ?");
$stmt->bind_param("ii", $blog_id, $consultant_id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

// Delete blog (only if owned by this consultant)
$stmt = $conn->prepare("DELETE FROM blogs WHERE id = ? AND author_id = ?");
$stmt->bind_param("ii", $blog_id, $consultant_id);

if ($stmt->execute()) {
    // Delete featured image if exists
    if ($blog && $blog['featured_image'] && file_exists($blog['featured_image'])) {
        unlink($blog['featured_image']);
    }
    $_SESSION['success_msg'] = "Blog deleted successfully!";
} else {
    $_SESSION['error_msg'] = "Error deleting blog: " . $conn->error;
}

header("Location: consultant_blogs.php");
exit();
?>
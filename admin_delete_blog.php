<?php
require_once 'db_connect.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if blog ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_blogs.php");
    exit();
}

$blog_id = $_GET['id'];

// Get blog details to delete image
$stmt = $conn->prepare("SELECT featured_image FROM blogs WHERE id = ?");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

// Delete blog
$stmt = $conn->prepare("DELETE FROM blogs WHERE id = ?");
$stmt->bind_param("i", $blog_id);

if ($stmt->execute()) {
    // Delete featured image if exists
    if ($blog && $blog['featured_image'] && file_exists($blog['featured_image'])) {
        unlink($blog['featured_image']);
    }
    $_SESSION['success_msg'] = "Blog deleted successfully!";
} else {
    $_SESSION['error_msg'] = "Error deleting blog: " . $conn->error;
}

header("Location: admin_blogs.php");
exit();
?>
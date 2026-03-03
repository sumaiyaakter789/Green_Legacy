<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to submit a review";
    header("Location: login.php?redirect=" . urlencode($_SERVER['HTTP_REFERER']));
    exit;
}

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
    $_SESSION['error'] = "Invalid request";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Validate inputs
$product_id = (int)$_POST['product_id'];
$user_id = (int)$_SESSION['user_id'];
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$title = trim($_POST['title'] ?? '');
$comment = trim($_POST['comment'] ?? '');

// Validate rating
if ($rating < 1 || $rating > 5) {
    $_SESSION['error'] = "Please select a rating between 1 and 5 stars";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Validate title and comment
if (empty($title) || empty($comment)) {
    $_SESSION['error'] = "Please fill in all required fields";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Check if user already reviewed this product
$stmt = $conn->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
$stmt->bind_param('ii', $product_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "You've already reviewed this product";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Insert new review
$stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, title, comment, is_approved) VALUES (?, ?, ?, ?, ?, ?)");
$is_approved = 1;
$stmt->bind_param('iiissi', $product_id, $user_id, $rating, $title, $comment, $is_approved);

if ($stmt->execute()) {
    // Update product review stats if review is approved
    if ($is_approved) {
        updateProductReviewStats($conn, $product_id);
    }
    
    $_SESSION['success'] = "Thank you for your review!";
} else {
    $_SESSION['error'] = "Failed to submit review. Please try again.";
}

$stmt->close();
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;

function updateProductReviewStats($conn, $product_id) {
    // Calculate new average and count
    $stmt = $conn->prepare("SELECT COUNT(*) as count, AVG(rating) as average FROM product_reviews WHERE product_id = ? AND is_approved = TRUE");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Update product
    $stmt = $conn->prepare("UPDATE products SET average_rating = ?, review_count = ? WHERE id = ?");
    $stmt->bind_param('dii', $stats['average'], $stats['count'], $product_id);
    $stmt->execute();
    $stmt->close();
}
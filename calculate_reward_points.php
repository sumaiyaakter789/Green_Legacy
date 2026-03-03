<?php
require_once 'db_connect.php';

function calculateRewardPoints($user_id, $conn) {
    $total_points = 0;
    
    // 1. Check if user is new (this should be handled in signup.php)
    // Points for joining are added during signup, not calculated here
    
    // 2. Points for completed appointments
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_points += $row['count'] * 30;
    
    // 3. Points for completed plant exchanges
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM exchange_offers WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_points += $row['count'] * 50;
    
    // 4. Points for posting in community forum
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM forum_posts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        $total_points += 10;
    }
    
    // 5. Points for newsletter subscription
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE email = ? AND active = 1");
    $stmt->bind_param("s", $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        $total_points += 10;
    }
    
    // 6. Points for successful orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_points += $row['count'] * 25;
    
    // 7. Points for product reviews
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_reviews WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_points += $row['count'] * 5;
    
    // Update the user's reward points in the database
    $stmt = $conn->prepare("UPDATE users SET reward_point = ? WHERE id = ?");
    $stmt->bind_param("ii", $total_points, $user_id);
    $stmt->execute();
    
    return $total_points;
}
?>
<?php
session_start();
require_once 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Get notice ID
if (!isset($_GET['id'])) {
    header('Location: admin_notices.php');
    exit;
}

$noticeId = (int)$_GET['id'];

// Delete notice
$query = "DELETE FROM notices WHERE notice_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $noticeId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['success'] = 'Notice deleted successfully!';
} else {
    $_SESSION['error'] = 'Failed to delete notice.';
}

header('Location: admin_notices.php');
exit;
?>
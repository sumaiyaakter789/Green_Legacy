<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark notifications as read when page loads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE recipient_id = ? AND is_read = FALSE");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $_SESSION['success'] = "Notifications marked as read";
    header("Location: notifications.php");
    exit;
}

// Get all notifications for the user
$stmt = $conn->prepare("
    SELECT n.*, 
           u.firstname, u.lastname, u.profile_pic,
           fp.title as post_title, fp.id as post_id,
           COALESCE(pc.content, '') as comment_content,
           COALESCE(pr.reaction_type, '') as reaction_type
    FROM notifications n
    LEFT JOIN users u ON n.sender_id = u.id
    LEFT JOIN forum_posts fp ON n.post_id = fp.id
    LEFT JOIN post_comments pc ON n.comment_id = pc.id
    LEFT JOIN post_reactions pr ON n.reaction_id = pr.id
    WHERE n.recipient_id = ?
    ORDER BY n.created_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count unread notifications
$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE recipient_id = ? AND is_read = FALSE");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread'];

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Green Legacy</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .notification-item.unread {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
        }
        .reaction-icon {
            color: #ef4444;
        }
        .avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto mt-8 px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
                <div class="flex items-center space-x-4">
                    <?php if ($unread_count > 0): ?>
                        <form method="post">
                            <button type="submit" name="mark_read" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                Mark All as Read
                            </button>
                        </form>
                    <?php endif; ?>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                        <?= $unread_count ?> unread
                    </span>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($notifications)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-bell-slash text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">No notifications yet</h3>
                    <p class="text-gray-500 mt-2">When you receive notifications, they'll appear here.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="notification-item p-4 hover:bg-gray-50 <?= !$notification['is_read'] ? 'unread' : '' ?>">
                                <div class="flex items-start">
                                    <img src="<?= htmlspecialchars($notification['profile_pic'] ?? 'default-user.png') ?>" 
                                         alt="<?= htmlspecialchars($notification['firstname']) ?>" 
                                         class="avatar rounded-full mr-3">
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between">
                                            <p class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($notification['firstname'] . ' ' . $notification['lastname']) ?>
                                            </p>
                                            <span class="text-xs text-gray-500">
                                                <?= date('M j, g:i a', strtotime($notification['created_at'])) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mt-1 text-sm text-gray-600">
                                            <?php switch ($notification['type']):
                                                case 'comment': ?>
                                                    <p>Commented on your post: 
                                                        <a href="post.php?id=<?= $notification['post_id'] ?>" class="text-green-600 hover:underline">
                                                            <?= htmlspecialchars($notification['post_title']) ?>
                                                        </a>
                                                    </p>
                                                    <?php if (!empty($notification['comment_content'])): ?>
                                                        <div class="mt-2 bg-gray-50 p-3 rounded-lg">
                                                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($notification['comment_content'])) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php break; ?>
                                                
                                                <?php case 'reply': ?>
                                                    <p>Replied to your comment on: 
                                                        <a href="post.php?id=<?= $notification['post_id'] ?>" class="text-green-600 hover:underline">
                                                            <?= htmlspecialchars($notification['post_title']) ?>
                                                        </a>
                                                    </p>
                                                    <?php if (!empty($notification['comment_content'])): ?>
                                                        <div class="mt-2 bg-gray-50 p-3 rounded-lg">
                                                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($notification['comment_content'])) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php break; ?>
                                                
                                                <?php case 'reaction_post': ?>
                                                    <p>
                                                        <i class="fas fa-heart reaction-icon mr-1"></i>
                                                        Reacted to your post: 
                                                        <a href="post.php?id=<?= $notification['post_id'] ?>" class="text-green-600 hover:underline">
                                                            <?= htmlspecialchars($notification['post_title']) ?>
                                                        </a>
                                                    </p>
                                                    <?php break; ?>
                                                
                                                <?php case 'reaction_comment': ?>
                                                    <p>
                                                        <i class="fas fa-heart reaction-icon mr-1"></i>
                                                        Reacted to your comment on: 
                                                        <a href="post.php?id=<?= $notification['post_id'] ?>" class="text-green-600 hover:underline">
                                                            <?= htmlspecialchars($notification['post_title']) ?>
                                                        </a>
                                                    </p>
                                                    <?php break; ?>
                                                
                                                <?php case 'mention': ?>
                                                    <p>Mentioned you in a comment on: 
                                                        <a href="post.php?id=<?= $notification['post_id'] ?>" class="text-green-600 hover:underline">
                                                            <?= htmlspecialchars($notification['post_title']) ?>
                                                        </a>
                                                    </p>
                                                    <?php if (!empty($notification['comment_content'])): ?>
                                                        <div class="mt-2 bg-gray-50 p-3 rounded-lg">
                                                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($notification['comment_content'])) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php break; ?>
                                            <?php endswitch; ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
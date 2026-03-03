<?php
require_once 'db_connect.php';
require_once 'navbar.php';

if (!isset($_GET['id'])) {
    header('Location: forum.php');
    exit;
}

$post_id = (int)$_GET['id'];

// Get the post details
$post_query = "SELECT fp.*, u.firstname, u.lastname, u.profile_pic 
               FROM forum_posts fp
               JOIN users u ON fp.user_id = u.id
               WHERE fp.id = ? AND fp.is_published = TRUE";
$stmt = $conn->prepare($post_query);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header('Location: forum.php');
    exit;
}

// Get reaction count for this post
$reaction_query = "SELECT reaction_type, COUNT(*) as count 
                   FROM post_reactions 
                   WHERE post_id = ? 
                   GROUP BY reaction_type";
$stmt = $conn->prepare($reaction_query);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$reactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get comments for this post with replies
$comments_query = "SELECT pc.*, u.firstname, u.lastname, u.profile_pic,
                  (SELECT COUNT(*) FROM comment_reactions cr WHERE cr.comment_id = pc.id) as reaction_count
                  FROM post_comments pc
                  JOIN users u ON pc.user_id = u.id
                  WHERE pc.post_id = ? AND pc.parent_id IS NULL
                  ORDER BY pc.created_at DESC";
$stmt = $conn->prepare($comments_query);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all replies for these comments
if (!empty($comments)) {
    $comment_ids = array_column($comments, 'id');
    $placeholders = implode(',', array_fill(0, count($comment_ids), '?'));
    
    $replies_query = "SELECT pc.*, u.firstname, u.lastname, u.profile_pic,
                     (SELECT COUNT(*) FROM comment_reactions cr WHERE cr.comment_id = pc.id) as reaction_count
                     FROM post_comments pc
                     JOIN users u ON pc.user_id = u.id
                     WHERE pc.parent_id IN ($placeholders)
                     ORDER BY pc.created_at ASC";
    $stmt = $conn->prepare($replies_query);
    $stmt->bind_param(str_repeat('i', count($comment_ids)), ...$comment_ids);
    $stmt->execute();
    $replies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Organize replies by parent comment
    $replies_by_parent = [];
    foreach ($replies as $reply) {
        $replies_by_parent[$reply['parent_id']][] = $reply;
    }
} else {
    $replies_by_parent = [];
}

// Check if the current user has reacted to this post
$user_reacted = false;
$user_reaction_type = null;
if (isset($_SESSION['user_id'])) {
    $reaction_check = "SELECT reaction_type FROM post_reactions 
                      WHERE user_id = ? AND post_id = ?";
    $stmt = $conn->prepare($reaction_check);
    $stmt->bind_param('ii', $_SESSION['user_id'], $post_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_reacted = true;
        $user_reaction_type = $result->fetch_assoc()['reaction_type'];
    }
}

// Check if user has saved this post
$is_saved = false;
if (isset($_SESSION['user_id'])) {
    $saved_query = "SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ?";
    $stmt = $conn->prepare($saved_query);
    $stmt->bind_param('ii', $_SESSION['user_id'], $post_id);
    $stmt->execute();
    $is_saved = $stmt->get_result()->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> | Green Legacy</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .comment-box {
            transition: all 0.3s ease;
        }
        .comment-box:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
        }
        .reply-form {
            display: none;
        }
        .edit-form {
            display: none;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto mt-8 px-4 py-8">
        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Post Header -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="<?= htmlspecialchars($post['profile_pic'] ?? 'default-user.png') ?>"
                            alt="<?= htmlspecialchars($post['firstname']) ?>"
                            class="user-avatar rounded-full mr-3">
                        <div>
                            <h3 class="font-medium"><?= htmlspecialchars($post['firstname'] . ' ' . $post['lastname']) ?></h3>
                            <p class="text-xs text-gray-500">
                                <?= date('M j, Y \a\t g:i a', strtotime($post['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center space-x-3">
                            <form method="post" action="handle_save.php">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" class="text-gray-500 hover:text-yellow-500">
                                    <i class="fas <?= $is_saved ? 'fa-bookmark text-yellow-500' : 'fa-bookmark' ?>"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Post Content -->
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-4"><?= $post['title'] ?></h1>
                <div class="prose max-w-none text-gray-700 mb-6">
                    <?= nl2br($post['content']) ?>
                </div>

                <?php if ($post['image_path']): ?>
                    <div class="mt-4 rounded-lg overflow-hidden">
                        <img src="<?= htmlspecialchars($post['image_path']) ?>"
                            alt="<?= htmlspecialchars($post['title']) ?>"
                            class="w-full">
                    </div>
                <?php endif; ?>
            </div>

            <!-- Post Reactions -->
            <div class="px-6 py-3 bg-gray-50 border-t border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <?php foreach ($reactions as $reaction): ?>
                            <span class="text-sm text-gray-600">
                                <?= $reaction['count'] ?>
                                <i class="fas fa-<?= $reaction['reaction_type'] === 'like' ? 'heart' : $reaction['reaction_type'] ?>"></i>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex space-x-2">
                            <form method="post" action="handle_reaction.php">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <input type="hidden" name="reaction_type" value="like">
                                <button type="submit" class="<?= $user_reacted ? 'text-red-500' : 'text-gray-500' ?> hover:text-red-500">
                                    <i class="fas fa-heart"></i> Like
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Comments</h2>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="post" action="handle_comment.php" class="mb-6">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <div class="mb-3">
                            <textarea name="content" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                placeholder="Add a comment..." required></textarea>
                        </div>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                            Post Comment
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (empty($comments)): ?>
                    <p class="text-gray-500 text-center py-4">No comments yet. Be the first to comment!</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-box p-4 rounded-lg border border-gray-100" id="comment-<?= $comment['id'] ?>">
                                <div class="flex items-start">
                                    <img src="<?= htmlspecialchars($comment['profile_pic'] ?? 'default-user.png') ?>"
                                        alt="<?= htmlspecialchars($comment['firstname']) ?>"
                                        class="user-avatar rounded-full mr-3">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-medium"><?= htmlspecialchars($comment['firstname'] . ' ' . $comment['lastname']) ?></h4>
                                            <span class="text-xs text-gray-500">
                                                <?= date('M j, Y \a\t g:i a', strtotime($comment['created_at'])) ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Comment Content (display or edit form) -->
                                        <div class="comment-content mt-1 mb-2">
                                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                        </div>
                                        
                                        <!-- Edit Form (hidden by default) -->
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                                            <form method="post" action="handle_comment.php" class="edit-form mt-2">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                <textarea name="content" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded"
                                                    required><?= htmlspecialchars($comment['content']) ?></textarea>
                                                <div class="mt-2 space-x-2">
                                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                                        Update
                                                    </button>
                                                    <button type="button" class="cancel-edit bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Comment Actions -->
                                        <div class="flex items-center space-x-4">
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <form method="post" action="handle_comment_reaction.php">
                                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                    <input type="hidden" name="reaction_type" value="like">
                                                    <button type="submit" class="text-gray-500 hover:text-red-500 text-sm">
                                                        <i class="fas fa-heart"></i> <?= $comment['reaction_count'] ?>
                                                    </button>
                                                </form>
                                                
                                                <button class="reply-btn text-gray-500 hover:text-green-600 text-sm">
                                                    <i class="fas fa-reply"></i> Reply
                                                </button>
                                                
                                                <?php if ($_SESSION['user_id'] == $comment['user_id']): ?>
                                                    <button class="edit-btn text-gray-500 hover:text-blue-600 text-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    
                                                    <form method="post" action="handle_comment.php" class="inline">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                        <button type="submit" class="text-gray-500 hover:text-red-600 text-sm" 
                                                            onclick="return confirm('Are you sure you want to delete this comment?')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-gray-500 text-sm">
                                                    <i class="fas fa-heart"></i> <?= $comment['reaction_count'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Reply Form (hidden by default) -->
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <form method="post" action="handle_comment.php" class="reply-form mt-3">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                                                <textarea name="content" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded"
                                                    placeholder="Write a reply..." required></textarea>
                                                <div class="mt-2 space-x-2">
                                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                                        Post Reply
                                                    </button>
                                                    <button type="button" class="cancel-reply bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Replies Section -->
                                <?php if (!empty($replies_by_parent[$comment['id']])): ?>
                                    <div class="mt-4 ml-10 pl-4 border-l-2 border-gray-200 space-y-3">
                                        <?php foreach ($replies_by_parent[$comment['id']] as $reply): ?>
                                            <div class="comment-box p-3 rounded-lg border border-gray-100" id="comment-<?= $reply['id'] ?>">
                                                <div class="flex items-start">
                                                    <img src="<?= htmlspecialchars($reply['profile_pic'] ?? 'default-user.png') ?>"
                                                        alt="<?= htmlspecialchars($reply['firstname']) ?>"
                                                        class="user-avatar rounded-full mr-3">
                                                    <div class="flex-1">
                                                        <div class="flex items-center justify-between">
                                                            <h4 class="font-medium"><?= htmlspecialchars($reply['firstname'] . ' ' . $reply['lastname']) ?></h4>
                                                            <span class="text-xs text-gray-500">
                                                                <?= date('M j, Y \a\t g:i a', strtotime($reply['created_at'])) ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <!-- Reply Content -->
                                                        <div class="comment-content mt-1 mb-2">
                                                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                                        </div>
                                                        
                                                        <!-- Edit Form for Reply -->
                                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $reply['user_id']): ?>
                                                            <form method="post" action="handle_comment.php" class="edit-form mt-2">
                                                                <input type="hidden" name="action" value="edit">
                                                                <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                                <textarea name="content" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded"
                                                                    required><?= htmlspecialchars($reply['content']) ?></textarea>
                                                                <div class="mt-2 space-x-2">
                                                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                                                        Update
                                                                    </button>
                                                                    <button type="button" class="cancel-edit bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm">
                                                                        Cancel
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Reply Actions -->
                                                        <div class="flex items-center space-x-4">
                                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                                <form method="post" action="handle_comment_reaction.php">
                                                                    <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                                    <input type="hidden" name="reaction_type" value="like">
                                                                    <button type="submit" class="text-gray-500 hover:text-red-500 text-sm">
                                                                        <i class="fas fa-heart"></i> <?= $reply['reaction_count'] ?>
                                                                    </button>
                                                                </form>
                                                                
                                                                <?php if ($_SESSION['user_id'] == $reply['user_id']): ?>
                                                                    <button class="edit-btn text-gray-500 hover:text-blue-600 text-sm">
                                                                        <i class="fas fa-edit"></i> Edit
                                                                    </button>
                                                                    
                                                                    <form method="post" action="handle_comment.php" class="inline">
                                                                        <input type="hidden" name="action" value="delete">
                                                                        <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                                        <button type="submit" class="text-gray-500 hover:text-red-600 text-sm" 
                                                                            onclick="return confirm('Are you sure you want to delete this reply?')">
                                                                            <i class="fas fa-trash"></i> Delete
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-gray-500 text-sm">
                                                                    <i class="fas fa-heart"></i> <?= $reply['reaction_count'] ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Toggle reply forms
        document.querySelectorAll('.reply-btn').forEach(button => {
            button.addEventListener('click', function() {
                const commentBox = this.closest('.comment-box');
                const replyForm = commentBox.querySelector('.reply-form');
                const isHidden = replyForm.style.display === 'none';
                
                // Hide all other reply forms first
                document.querySelectorAll('.reply-form').forEach(form => {
                    form.style.display = 'none';
                });
                
                // Toggle this one
                replyForm.style.display = isHidden ? 'block' : 'none';
            });
        });

        // Cancel reply
        document.querySelectorAll('.cancel-reply').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.reply-form').style.display = 'none';
            });
        });

        // Toggle edit forms
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const commentBox = this.closest('.comment-box');
                const contentDiv = commentBox.querySelector('.comment-content');
                const editForm = commentBox.querySelector('.edit-form');
                
                // Hide all other edit forms first
                document.querySelectorAll('.edit-form').forEach(form => {
                    form.style.display = 'none';
                });
                document.querySelectorAll('.comment-content').forEach(content => {
                    content.style.display = 'block';
                });
                
                // Toggle this one
                contentDiv.style.display = 'none';
                editForm.style.display = 'block';
            });
        });

        // Cancel edit
        document.querySelectorAll('.cancel-edit').forEach(button => {
            button.addEventListener('click', function() {
                const commentBox = this.closest('.comment-box');
                const contentDiv = commentBox.querySelector('.comment-content');
                const editForm = commentBox.querySelector('.edit-form');
                
                contentDiv.style.display = 'block';
                editForm.style.display = 'none';
            });
        });
    </script>
</body>
</html>
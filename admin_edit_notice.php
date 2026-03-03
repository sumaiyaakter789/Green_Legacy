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

// Get notice details
$query = "SELECT * FROM notices WHERE notice_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $noticeId);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();

if (!$notice) {
    header('Location: admin_notices.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    
    // Update notice
    $query = "UPDATE notices SET 
              title = ?, 
              content = ?, 
              is_published = ?, 
              published_at = CASE 
                WHEN is_published = 0 AND ? = 1 THEN NOW() 
                WHEN is_published = 1 AND ? = 1 THEN published_at 
                ELSE NULL 
              END,
              updated_at = NOW()
              WHERE notice_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssiiii', $title, $content, $isPublished, $isPublished, $isPublished, $noticeId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = 'Notice updated successfully!';
        header('Location: admin_notices.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to update notice. Please try again.';
    }
}
?>

<?php include 'admin_sidebar.php'; ?>
<style> .main-content {
    margin-left: 280px;
}
</style>
<div class="main-content p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Notice</h1>
        <a href="admin_notices.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
            Back to Notices
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST">
            <div class="mb-4">
                <label for="title" class="block text-gray-700 mb-2">Title</label>
                <input type="text" id="title" name="title" required
                       value="<?= htmlspecialchars($notice['title']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="mb-4">
                <label for="content" class="block text-gray-700 mb-2">Content</label>
                <textarea id="content" name="content" rows="10" required
                          class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($notice['content']) ?></textarea>
            </div>
            
            <div class="mb-4 flex items-center">
                <input type="checkbox" id="is_published" name="is_published" 
                       <?= $notice['is_published'] ? 'checked' : '' ?> class="mr-2">
                <label for="is_published" class="text-gray-700">Publish this notice</label>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Update Notice
            </button>
        </form>
    </div>
</div>
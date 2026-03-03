<?php
session_start();
require_once 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $adminId = $_SESSION['admin_id'];
    
    // Insert new notice
    $query = "INSERT INTO notices (title, content, admin_id, is_published, published_at)
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $publishedAt = $isPublished ? date('Y-m-d H:i:s') : null;
    $stmt->bind_param('ssiis', $title, $content, $adminId, $isPublished, $publishedAt);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = 'Notice added successfully!';
        header('Location: admin_notices.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to add notice. Please try again.';
    }
}

// Get all notices for listing
$query = "SELECT n.*, a.username as author 
          FROM notices n
          JOIN admins a ON n.admin_id = a.admin_id
          ORDER BY n.created_at DESC";
$result = $conn->query($query);
$notices = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'admin_sidebar.php'; ?>
<style> .main-content {
    margin-left: 280px;
}
</style>
<div class="main-content p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Notices</h1>
    </div>

    <!-- Add New Notice Form -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Add New Notice</h2>
        <form method="POST">
            <div class="mb-4">
                <label for="title" class="block text-gray-700 mb-2">Title</label>
                <input type="text" id="title" name="title" required
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="mb-4">
                <label for="content" class="block text-gray-700 mb-2">Content</label>
                <textarea id="content" name="content" rows="6" required
                          class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="mb-4 flex items-center">
                <input type="checkbox" id="is_published" name="is_published" class="mr-2">
                <label for="is_published" class="text-gray-700">Publish immediately</label>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Save Notice
            </button>
        </form>
    </div>

    <!-- Notices List -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($notices as $notice): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="notice_details.php?id=<?= $notice['notice_id'] ?>" class="text-green-600 hover:text-green-800">
                                    <?= htmlspecialchars($notice['title']) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($notice['author']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($notice['is_published']): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Published</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= date('M j, Y', strtotime($notice['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex justify-end space-x-2">
                                    <a href="admin_edit_notice.php?id=<?= $notice['notice_id'] ?>" class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="admin_delete_notice.php?id=<?= $notice['notice_id'] ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this notice?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
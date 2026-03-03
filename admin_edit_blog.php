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

// Get blog details
$stmt = $conn->prepare("SELECT * FROM blogs WHERE id = ?");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

if (!$blog) {
    header("Location: admin_blogs.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $tags = $_POST['tags'];
    $meta_description = $_POST['meta_description'];
    $status = $_POST['status'];
    
    // Handle image upload
    $image_path = $blog['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $target_dir = "uploads/blogs/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
        $filename = 'blog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
        $target_file = $target_dir . $filename;
        
        // Check if image file is valid
        $check = getimagesize($_FILES['featured_image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                // Delete old image if exists
                if ($image_path && file_exists($image_path)) {
                    unlink($image_path);
                }
                $image_path = $target_file;
            }
        }
    }
    
    // Handle status change
    $approved_by = $blog['approved_by'];
    $published_at = $blog['published_at'];
    
    if ($status == 'published' && $blog['status'] != 'published') {
        $approved_by = $_SESSION['admin_id'];
        $published_at = date('Y-m-d H:i:s'); // Get current datetime as string
    }
    
    // Update blog
    $stmt = $conn->prepare("UPDATE blogs SET 
        title = ?, content = ?, tags = ?, meta_description = ?, 
        featured_image = ?, status = ?, approved_by = ?, published_at = ?
        WHERE id = ?");
    
    $stmt->bind_param("ssssssisi", $title, $content, $tags, $meta_description, 
        $image_path, $status, $approved_by, $published_at, $blog_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Blog updated successfully!";
        header("Location: admin_blogs.php");
        exit();
    } else {
        $_SESSION['error_msg'] = "Error updating blog: " . $conn->error;
    }
}

?>

<?php include 'admin_sidebar.php'; ?>
<style>
    .main-content {
        margin-left: 280px;
    }
</style>
<div class="main-content">
    <div class="ml-0 lg:ml-[280px] min-h-screen bg-gray-50">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Edit Blog Post</h1>
                <a href="admin_blogs.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Blogs
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                </div>
            <?php endif; ?>

            <!-- Blog Form -->
            <div class="bg-white rounded-lg shadow overflow-hidden p-6">
                <form action="admin_edit_blog.php?id=<?php echo $blog_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title*</label>
                        <input type="text" id="title" name="title" required
                            value="<?php echo htmlspecialchars($blog['title']); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Content*</label>
                        <textarea id="content" name="content" rows="15" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"><?php echo htmlspecialchars($blog['content']); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags (comma separated)</label>
                            <input type="text" id="tags" name="tags"
                                value="<?php echo htmlspecialchars($blog['tags']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                placeholder="gardening, plants, tips">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status*</label>
                            <select id="status" name="status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="draft" <?php echo $blog['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="pending" <?php echo $blog['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="published" <?php echo $blog['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="rejected" <?php echo $blog['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                        <div class="flex items-center">
                            <div class="flex-1">
                                <input type="file" id="featured_image" name="featured_image" accept="image/*"
                                    class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-green-50 file:text-green-700
                                        hover:file:bg-green-100">
                            </div>
                            <?php if ($blog['featured_image'] && file_exists($blog['featured_image'])): ?>
                                <div class="ml-4">
                                    <img src="<?php echo htmlspecialchars($blog['featured_image']); ?>" 
                                        alt="Current featured image" class="h-16 w-16 object-cover rounded-md">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description (for SEO)</label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"><?php echo htmlspecialchars($blog['meta_description']); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">Brief description for search engines (150-160 characters recommended)</p>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" onclick="if(confirm('Are you sure you want to delete this blog post?')) { window.location.href='admin_delete_blog.php?id=<?php echo $blog_id; ?>'; }"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Delete Blog
                        </button>
                        <button type="submit"
                            class="px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Update Blog Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
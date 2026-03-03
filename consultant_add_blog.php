<?php
require_once 'db_connect.php';
session_start();

// Check if consultant is logged in
if (!isset($_SESSION['consultant_logged_in'])) {
    header("Location: consultant_login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author_id = $_SESSION['consultant_id'];
    $tags = $_POST['tags'];
    $meta_description = $_POST['meta_description'];
    
    // Handle image upload
    $image_path = '';
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
                $image_path = $target_file;
            }
        }
    }
    
    // Insert into database (consultant posts need approval)
    $stmt = $conn->prepare("INSERT INTO blogs 
        (title, content, author_id, tags, meta_description, featured_image, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssisss", $title, $content, $author_id, $tags, $meta_description, $image_path);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Blog submitted for approval!";
        header("Location: consultant_blogs.php");
        exit();
    } else {
        $_SESSION['error_msg'] = "Error submitting blog: " . $conn->error;
    }
}
?>

<?php include 'consultant_sidebar.php'; ?>
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
                <h1 class="text-3xl font-bold text-gray-800">Add New Blog Post</h1>
                <a href="consultant_blogs.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
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
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                As a consultant, your blog posts require approval from an admin before being published.
                            </p>
                        </div>
                    </div>
                </div>

                <form action="consultant_add_blog.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title*</label>
                        <input type="text" id="title" name="title" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Content*</label>
                        <textarea id="content" name="content" rows="15" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags (comma separated)</label>
                            <input type="text" id="tags" name="tags"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                placeholder="gardening, plants, tips">
                        </div>

                        <div>
                            <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                            <input type="file" id="featured_image" name="featured_image" accept="image/*"
                                class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-green-50 file:text-green-700
                                    hover:file:bg-green-100">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description (for SEO)</label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"></textarea>
                        <p class="mt-1 text-sm text-gray-500">Brief description for search engines (150-160 characters recommended)</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Submit for Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
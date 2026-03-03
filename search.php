<?php
require_once 'db_connect.php';

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if (!empty($searchTerm)) {
    // Search products
    $productQuery = "SELECT p.id, p.name, p.description, pi.image_path 
                     FROM products p
                     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                     WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.is_active = 1";
    $stmt = $conn->prepare($productQuery);
    $likeTerm = "%$searchTerm%";
    $stmt->bind_param('ss', $likeTerm, $likeTerm);
    $stmt->execute();
    $productResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($productResults as $product) {
        $results[] = [
            'type' => 'product',
            'id' => $product['id'],
            'title' => $product['name'],
            'description' => substr(strip_tags($product['description']), 0, 150),
            'image' => $product['image_path'] ?? 'default-product.jpg',
            'url' => 'product.php?id=' . $product['id']
        ];
    }

    // Search forum posts
    $forumQuery = "SELECT id, title, content FROM forum_posts 
                   WHERE (title LIKE ? OR content LIKE ?) AND is_published = 1";
    $stmt = $conn->prepare($forumQuery);
    $stmt->bind_param('ss', $likeTerm, $likeTerm);
    $stmt->execute();
    $forumResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($forumResults as $post) {
        $results[] = [
            'type' => 'forum',
            'id' => $post['id'],
            'title' => $post['title'],
            'description' => substr(strip_tags($post['content']), 0, 150),
            'image' => 'forum-icon.png',
            'url' => 'post.php?id=' . $post['id']
        ];
    }

    // Search blogs
    $blogQuery = "SELECT id, title, content FROM blogs 
                  WHERE (title LIKE ? OR content LIKE ?) AND status = 'published'";
    $stmt = $conn->prepare($blogQuery);
    $stmt->bind_param('ss', $likeTerm, $likeTerm);
    $stmt->execute();
    $blogResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($blogResults as $blog) {
        $results[] = [
            'type' => 'blog',
            'id' => $blog['id'],
            'title' => $blog['title'],
            'description' => substr(strip_tags($blog['content']), 0, 150),
            'image' => 'blog-icon.png',
            'url' => 'blog_details.php?id=' . $blog['id']
        ];
    }

    // Search notices
    $noticeQuery = "SELECT notice_id as id, title, content FROM notices 
                    WHERE (title LIKE ? OR content LIKE ?) AND is_published = 1";
    $stmt = $conn->prepare($noticeQuery);
    $stmt->bind_param('ss', $likeTerm, $likeTerm);
    $stmt->execute();
    $noticeResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($noticeResults as $notice) {
        $results[] = [
            'type' => 'notice',
            'id' => $notice['id'],
            'title' => $notice['title'],
            'description' => substr(strip_tags($notice['content']), 0, 150),
            'image' => 'notice-icon.png',
            'url' => 'notice_details.php?id=' . $notice['id']
        ];
    }
}
?>

<?php include 'navbar.php'; ?>

<div class="container mx-auto mt-8 px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Search Results for "<?= htmlspecialchars($searchTerm) ?>"</h1>
    
    <?php if (empty($searchTerm)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-600">Please enter a search term</p>
        </div>
    <?php elseif (empty($results)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-600">No results found for "<?= htmlspecialchars($searchTerm) ?>"</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($results as $item): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="h-48 overflow-hidden">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-4">
                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded 
                            <?= $item['type'] === 'product' ? 'bg-green-100 text-green-800' : 
                               ($item['type'] === 'forum' ? 'bg-blue-100 text-blue-800' : 
                               ($item['type'] === 'blog' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800')) ?>">
                            <?= ucfirst($item['type']) ?>
                        </span>
                        <h3 class="text-lg font-semibold mt-2 mb-1">
                            <a href="<?= htmlspecialchars($item['url']) ?>" class="hover:text-green-600">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm mb-3">
                            <?= htmlspecialchars($item['description']) ?>...
                        </p>
                        <a href="<?= htmlspecialchars($item['url']) ?>" class="text-green-600 hover:text-green-800 font-medium text-sm">
                            View Details &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
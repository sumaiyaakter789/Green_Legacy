<?php
require_once 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$user_id = $logged_in ? $_SESSION['user_id'] : null;

// Get all tool categories
$tool_categories = [];
$stmt = $conn->prepare("SELECT id, name, slug FROM categories WHERE product_type = 'tool' AND parent_id IS NULL");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tool_categories[$row['id']] = $row;
}

// Get featured tools
$featured_tools = [];
$stmt = $conn->prepare("SELECT p.*, pi.image_path 
                        FROM products p 
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                        WHERE p.product_type = 'tool' AND p.is_featured = 1 AND p.is_active = 1
                        LIMIT 6");
$stmt->execute();
$featured_tools = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all tools grouped by category
$tools_by_category = [];
foreach ($tool_categories as $category_id => $category) {
    $stmt = $conn->prepare("SELECT p.*, pi.image_path 
                           FROM products p 
                           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                           WHERE p.category_id = ? AND p.product_type = 'tool' AND p.is_active = 1
                           ORDER BY p.is_featured DESC, p.name ASC");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $tools = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($tools)) {
        $tools_by_category[$category['name']] = $tools;
    }
}

// Handle search if search parameter is present
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $stmt = $conn->prepare("SELECT p.*, pi.image_path 
                           FROM products p 
                           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                           WHERE p.product_type = 'tool' AND p.is_active = 1 
                           AND (p.name LIKE ? OR p.description LIKE ?)
                           ORDER BY p.is_featured DESC, p.name ASC");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<?php include 'navbar.php'; ?>

<div class="container mx-auto px-4 py-8 mt-16">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Gardening Tools & Equipment</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                High-quality tools to make your gardening experience easier and more enjoyable
            </p>
        </div>

        <!-- Search Bar -->
        <div class="mb-8">
            <form method="GET" action="tools.php" class="flex">
                <input type="text" name="search" placeholder="Search tools..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                       class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-r-lg transition duration-200">
                    Search
                </button>
            </form>
        </div>

        <?php if (!empty($search_results)): ?>
            <!-- Search Results Section -->
            <div class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Search Results for "<?php echo htmlspecialchars($_GET['search']); ?>"</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($search_results as $tool): ?>
                        <?php
                        $image_path = 'uploads/products/' . basename($tool['image_path']);
                        $final_path = file_exists($image_path) ? $image_path : 'images/default-tool.jpg';
                        ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <a href="product.php?id=<?php echo $tool['id']; ?>">
                                <img src="<?php echo htmlspecialchars($final_path); ?>" 
                                     alt="<?php echo htmlspecialchars($tool['name']); ?>" 
                                     class="w-full h-48 object-cover">
                                <div class="p-4">
                                    <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($tool['name']); ?></h3>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-green-600 font-medium">
                                            <?php if ($tool['discount_price']): ?>
                                                <span class="text-gray-500 line-through mr-2">$<?php echo number_format($tool['price'], 2); ?></span>
                                                $<?php echo number_format($tool['discount_price'], 2); ?>
                                            <?php else: ?>
                                                $<?php echo number_format($tool['price'], 2); ?>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($tool['is_featured']): ?>
                                            <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($search_results)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No tools found</h3>
                        <p class="mt-1 text-gray-500">Try adjusting your search or filter to find what you're looking for.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Featured Tools Section -->
            <?php if (!empty($featured_tools)): ?>
                <div class="mb-12">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Featured Tools</h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($featured_tools as $tool): ?>
                            <?php
                            $image_path = 'uploads/products/' . basename($tool['image_path']);
                            $final_path = file_exists($image_path) ? $image_path : 'images/default-tool.jpg';
                            ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                                <a href="product.php?id=<?php echo $tool['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($final_path); ?>" 
                                         alt="<?php echo htmlspecialchars($tool['name']); ?>" 
                                         class="w-full h-48 object-cover">
                                    <div class="p-4">
                                        <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($tool['name']); ?></h3>
                                        <div class="flex justify-between items-center mt-2">
                                            <span class="text-green-600 font-medium">
                                                <?php if ($tool['discount_price']): ?>
                                                    <span class="text-gray-500 line-through mr-2">$<?php echo number_format($tool['price'], 2); ?></span>
                                                    $<?php echo number_format($tool['discount_price'], 2); ?>
                                                <?php else: ?>
                                                    $<?php echo number_format($tool['price'], 2); ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Featured</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tools by Category -->
            <?php foreach ($tools_by_category as $category_name => $tools): ?>
                <div class="mb-12">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo htmlspecialchars($category_name); ?></h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($tools as $tool): ?>
                            <?php
                            $image_path = 'uploads/products/' . basename($tool['image_path']);
                            $final_path = file_exists($image_path) ? $image_path : 'images/default-tool.jpg';
                            ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                                <a href="product.php?id=<?php echo $tool['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($final_path); ?>" 
                                         alt="<?php echo htmlspecialchars($tool['name']); ?>" 
                                         class="w-full h-48 object-cover">
                                    <div class="p-4">
                                        <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($tool['name']); ?></h3>
                                        <div class="flex justify-between items-center mt-2">
                                            <span class="text-green-600 font-medium">
                                                <?php if ($tool['discount_price']): ?>
                                                    <span class="text-gray-500 line-through mr-2">$<?php echo number_format($tool['price'], 2); ?></span>
                                                    $<?php echo number_format($tool['discount_price'], 2); ?>
                                                <?php else: ?>
                                                    $<?php echo number_format($tool['price'], 2); ?>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($tool['is_featured']): ?>
                                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="bg-green-50 rounded-xl p-8 text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Need Help Choosing Tools?</h2>
            <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
                Our gardening experts can help you select the perfect tools for your needs and budget.
            </p>
            <a href="contact.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition duration-200">
                Contact Us
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
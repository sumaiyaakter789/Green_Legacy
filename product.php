<?php
require_once 'db_connect.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: shop.php');
    exit;
}

$product_id = (int)$_GET['id'];

// Get product details
$product = [];
$images = [];
$attributes = [];
$category = [];
$related_products = [];

// Main product query
$stmt = $conn->prepare("
    SELECT p.*, 
           pi.quantity as stock,
           c.name as category_name,
           c.id as category_id
    FROM products p
    JOIN product_inventory pi ON p.id = pi.product_id
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Get product images
$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$images = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get product attributes
$stmt = $conn->prepare("SELECT * FROM product_attributes WHERE product_id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$attributes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get category details
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param('i', $product['category_id']);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

// Get related products (same category, excluding current product)
$stmt = $conn->prepare("
    SELECT p.*, 
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    JOIN product_inventory pi ON p.id = pi.product_id
    WHERE p.category_id = ? 
    AND p.id != ? 
    AND p.is_active = 1 
    AND pi.quantity > 0
    ORDER BY RAND()
    LIMIT 4
");
$stmt->bind_param('ii', $product['category_id'], $product_id);
$stmt->execute();
$result = $stmt->get_result();
$related_products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Get approved reviews count
$sql = "SELECT COUNT(*) as count FROM product_reviews WHERE product_id = $product_id AND is_approved = 1";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$reviewCount = $row['count'];


?>

<?php include 'navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Green Legacy</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #2E8B57;
            --light-green: #a8e6cf;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-plant {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-tool {
            background-color: #e0f2fe;
            color: #1e40af;
        }

        .badge-pesticide {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-fertilizer {
            background-color: #ede9fe;
            color: #5b21b6;
        }

        .badge-accessory {
            background-color: #fce7f3;
            color: #9d174d;
        }

        .badge-featured {
            background-color: #f3e8ff;
            color: #7e22ce;
        }

        .badge-discount {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .badge-new {
            background-color: #bfdbfe;
            color: #1e40af;
        }

        .badge-bestseller {
            background-color: #fbcfe8;
            color: #9d174d;
        }

        .badge-organic {
            background-color: #bbf7d0;
            color: #166534;
        }

        .badge-limited {
            background-color: #fef08a;
            color: #854d0e;
        }

        .gallery-thumbnail {
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .gallery-thumbnail:hover,
        .gallery-thumbnail.active {
            border-color: var(--primary-green);
        }

        .attribute-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem 0;
        }

        .attribute-item:last-child {
            border-bottom: none;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-button {
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
        }

        .tab-button:hover,
        .tab-button.active {
            color: var(--primary-green);
            border-bottom-color: var(--primary-green);
        }

        .related-product-card {
            transition: all 0.3s ease;
        }

        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <!-- Breadcrumb Navigation -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto mt-10">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-green-600">
                            <i class="fas fa-home mr-2"></i>
                            Home
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400"></i>
                            <a href="shop.php" class="ml-1 text-sm font-medium text-gray-700 hover:text-green-600 md:ml-2">Shop</a>
                        </div>
                    </li>
                    <?php if ($category): ?>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400"></i>
                                <a href="shop.php?category=<?= $category['id'] ?>" class="ml-1 text-sm font-medium text-gray-700 hover:text-green-600 md:ml-2">
                                    <?= htmlspecialchars($category['name']) ?>
                                </a>
                            </div>
                        </li>
                    <?php endif; ?>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400"></i>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2"><?= htmlspecialchars($product['name']) ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Main Product Section -->
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Gallery -->
            <div class="product-gallery">
                <!-- Main Image -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-4">
                    <img id="mainImage"
                        src="<?= !empty($images[0]['image_path']) ? 'uploads/products/' . htmlspecialchars($images[0]['image_path']) : 'images/placeholder-product.jpg' ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        class="w-full h-auto max-h-96 object-contain">
                </div>

                <!-- Thumbnails -->
                <?php if (count($images) > 1): ?>
                    <div class="grid grid-cols-4 gap-2">
                        <?php foreach ($images as $index => $image): ?>
                            <button class="gallery-thumbnail <?= $index === 0 ? 'active' : '' ?>"
                                onclick="changeMainImage('<?= 'uploads/products/' . htmlspecialchars($image['image_path']) ?>', this)">
                                <img src="<?= 'uploads/products/' . htmlspecialchars($image['image_path']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    class="w-full h-20 object-cover">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Details -->
            <div class="product-details">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($product['name']) ?></h1>
                        <div class="flex items-center space-x-2 mb-3">
                            <span class="badge badge-<?= $product['product_type'] ?>">
                                <?= ucfirst($product['product_type']) ?>
                            </span>
                            <?php if ($product['is_featured']): ?>
                                <span class="badge badge-featured">
                                    <i class="fas fa-star mr-1"></i> Featured
                                </span>
                            <?php endif; ?>
                            <?php if ($product['discount_price']): ?>
                                <span class="badge badge-discount">
                                    <i class="fas fa-tag mr-1"></i> On Sale
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <button class="text-gray-400 hover:text-red-500">
                            <i class="far fa-heart text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Price -->
                <div class="my-4">
                    <?php if ($product['discount_price']): ?>
                        <div class="flex items-center">
                            <span class="text-3xl font-bold text-green-600 mr-3">$<?= number_format($product['discount_price'], 2) ?></span>
                            <span class="text-xl text-gray-500 line-through">$<?= number_format($product['price'], 2) ?></span>
                            <?php
                            $discountPercent = round(($product['price'] - $product['discount_price']) / $product['price'] * 100);
                            ?>
                            <span class="ml-3 bg-red-100 text-red-800 text-sm font-semibold px-2 py-1 rounded">
                                Save <?= $discountPercent ?>%
                            </span>
                        </div>
                    <?php else: ?>
                        <span class="text-3xl font-bold text-green-600">$<?= number_format($product['price'], 2) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Stock Status -->
                <div class="mb-6">
                    <span class="text-sm <?= $product['stock'] > 5 ? 'text-green-600' : ($product['stock'] > 0 ? 'text-yellow-600' : 'text-red-600') ?>">
                        <i class="fas fa-<?= $product['stock'] > 0 ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                        <?= $product['stock'] > 5 ? 'In Stock' : ($product['stock'] > 0 ? 'Low Stock - Only ' . $product['stock'] . ' left' : 'Out of Stock') ?>
                    </span>
                </div>

                <!-- Short Description -->
                <div class="mb-6">
                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>

                <!-- Product Attributes -->
                <?php if (!empty($attributes)): ?>
                    <div class="mb-6">
                        <h3 class="font-semibold text-lg mb-3">Key Features</h3>
                        <ul class="space-y-1">
                            <?php foreach ($attributes as $attr): ?>
                                <li class="flex">
                                    <span class="text-gray-500 mr-2">•</span>
                                    <span><strong><?= htmlspecialchars($attr['attribute_key']) ?>:</strong> <?= htmlspecialchars($attr['attribute_value']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Add to Cart -->
                <div class="mb-8">
                    <form id="addToCartForm" class="flex items-center space-x-4">
                        <div class="flex items-center border border-gray-300 rounded-md overflow-hidden">
                            <button type="button" id="decrementQty" class="px-3 py-2 bg-gray-100 text-gray-600 hover:bg-gray-200">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>"
                                class="w-16 text-center border-0 focus:ring-0" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                            <button type="button" id="incrementQty" class="px-3 py-2 bg-gray-100 text-gray-600 hover:bg-gray-200">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button type="submit" id="addToCartBtn"
                            class="flex-1 bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition font-medium
                    <?= $product['stock'] <= 0 ? 'opacity-50 cursor-not-allowed' : '' ?>"
                            <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                        </button>
                    </form>
                </div>

                <!-- Product Meta -->
                <div class="border-t border-gray-200 pt-4">
                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                        <div>
                            <span class="font-medium">SKU:</span> <?= htmlspecialchars($product['sku']) ?>
                        </div>
                        <div>
                            <span class="font-medium">Category:</span>
                            <?= $category ? htmlspecialchars($category['name']) : 'N/A' ?>
                        </div>
                        <div>
                            <span class="font-medium">Availability:</span>
                            <?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                        </div>
                        <div>
                            <span class="font-medium">Product Type:</span>
                            <?= ucfirst($product['product_type']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Tabs -->
        <div class="mt-12">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button class="tab-button py-4 px-1 font-medium text-sm border-b-2 active"
                        data-tab="description">
                        Description
                    </button>
                    <button class="tab-button py-4 px-1 font-medium text-sm border-b-2"
                        data-tab="additional-info">
                        Additional Information
                    </button>
                    <button class="tab-button py-4 px-1 font-medium text-sm border-b-2"
                        data-tab="reviews">
                        Reviews (<?php echo $reviewCount; ?>)
                    </button>
                </nav>
            </div>

            <div class="py-6">
                <!-- Description Tab -->
                <div id="description-tab" class="tab-content active">
                    <div class="prose max-w-none">
                        <h3>Product Details</h3>
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

                        <?php if ($product['product_type'] === 'plant'): ?>
                            <h3 class="mt-6">Plant Care Guide</h3>
                            <h4>Light Requirements</h4>
                            <p>This plant thrives in bright, indirect light but can tolerate some shade. Avoid direct sunlight which can scorch the leaves.</p>

                            <h4>Watering</h4>
                            <p>Water when the top inch of soil feels dry. Reduce watering in winter months. Overwatering can lead to root rot.</p>

                            <h4>Temperature & Humidity</h4>
                            <p>Prefers temperatures between 18-24°C (65-75°F) and moderate humidity. Mist leaves occasionally in dry environments.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Additional Info Tab -->
                <div id="additional-info-tab" class="tab-content">
                    <div class="space-y-4">
                        <?php if (!empty($attributes)): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($attributes as $attr): ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 w-1/3">
                                                <?= htmlspecialchars($attr['attribute_key']) ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                <?= htmlspecialchars($attr['attribute_value']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-gray-600">No additional information available for this product.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reviews Tab -->
                <div id="reviews-tab" class="tab-content">
                    <?php
                    // Get reviews for this product
                    $reviews = [];
                    $average_rating = 0;
                    $review_count = 0;

                    $stmt = $conn->prepare("
        SELECT r.*, u.firstname, u.lastname, u.profile_pic 
        FROM product_reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ? AND r.is_approved = TRUE
        ORDER BY r.created_at DESC
    ");
                    $stmt->bind_param('i', $product_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $reviews = $result->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();

                    // Get review stats
                    $stmt = $conn->prepare("SELECT COUNT(*) as count, AVG(rating) as average FROM product_reviews WHERE product_id = ? AND is_approved = TRUE");
                    $stmt->bind_param('i', $product_id);
                    $stmt->execute();
                    $stats = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    $average_rating = round($stats['average'] ?? 0, 1);
                    $review_count = $stats['count'] ?? 0;

                    // Check if current user has already reviewed this product
                    $user_has_reviewed = false;
                    $user_review = null;
                    if (isset($_SESSION['user_id'])) {
                        $stmt = $conn->prepare("SELECT * FROM product_reviews WHERE product_id = ? AND user_id = ?");
                        $stmt->bind_param('ii', $product_id, $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user_review = $result->fetch_assoc();
                        $user_has_reviewed = ($user_review !== null);
                        $stmt->close();
                    }
                    ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Review Summary -->
                        <div class="lg:col-span-1">
                            <div class="bg-white p-6 rounded-lg shadow-sm">
                                <h3 class="text-xl font-bold mb-4">Customer Reviews</h3>

                                <div class="flex items-center mb-4">
                                    <div class="flex items-center mr-4">
                                        <?php
                                        $full_stars = floor($average_rating);
                                        $has_half_star = ($average_rating - $full_stars) >= 0.5;

                                        for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $full_stars): ?>
                                                <i class="fas fa-star text-yellow-400 text-2xl"></i>
                                            <?php elseif ($has_half_star && $i == $full_stars + 1): ?>
                                                <i class="fas fa-star-half-alt text-yellow-400 text-2xl"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-yellow-400 text-2xl"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-2xl font-bold"><?= $average_rating ?></span>
                                    <span class="text-gray-500 ml-1">out of 5</span>
                                </div>

                                <p class="text-gray-600 mb-6"><?= $review_count ?> customer review<?= $review_count != 1 ? 's' : '' ?></p>

                                <!-- Rating Breakdown -->
                                <div class="space-y-2 mb-6">
                                    <?php for ($i = 5; $i >= 1; $i--):
                                        // Get count of reviews for this star rating
                                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_reviews WHERE product_id = ? AND rating = ? AND is_approved = TRUE");
                                        $stmt->bind_param('ii', $product_id, $i);
                                        $stmt->execute();
                                        $count = $stmt->get_result()->fetch_assoc()['count'];
                                        $stmt->close();

                                        $percentage = $review_count > 0 ? round(($count / $review_count) * 100) : 0;
                                    ?>
                                        <div class="flex items-center">
                                            <span class="w-8 text-gray-600"><?= $i ?> star</span>
                                            <div class="flex-1 mx-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-yellow-400" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                            <span class="w-8 text-gray-600 text-right"><?= $percentage ?>%</span>
                                        </div>
                                    <?php endfor; ?>
                                </div>

                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <?php if (!$user_has_reviewed): ?>
                                        <button id="writeReviewBtn" class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition duration-200">
                                            <i class="fas fa-edit mr-2"></i> Write a Review
                                        </button>
                                    <?php else: ?>
                                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 text-center">
                                            <i class="fas fa-check-circle text-blue-500 text-xl mb-2"></i>
                                            <p class="text-blue-800 font-medium">You've already reviewed this product</p>
                                            <?php if ($user_review && !$user_review['is_approved']): ?>
                                                <p class="text-blue-600 text-sm mt-1">Your review is pending approval</p>
                                            <?php else: ?>
                                                <div class="flex justify-center mt-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star text-<?= $i <= $user_review['rating'] ? 'yellow-400' : 'gray-300' ?> text-sm"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                                        class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition duration-200 block text-center">
                                        <i class="fas fa-sign-in-alt mr-2"></i> Login to Write Review
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Reviews List -->
                        <div class="lg:col-span-2">
                            <?php if (!empty($reviews)): ?>
                                <div class="space-y-6">
                                    <?php foreach ($reviews as $review):
                                        // Get helpful votes count and user's vote
                                        $stmt = $conn->prepare("
                            SELECT 
                                SUM(is_helpful = 1) as helpful_count,
                                SUM(is_helpful = 0) as not_helpful_count
                            FROM review_votes 
                            WHERE review_id = ?
                        ");
                                        $stmt->bind_param('i', $review['id']);
                                        $stmt->execute();
                                        $votes = $stmt->get_result()->fetch_assoc();
                                        $stmt->close();

                                        $helpful_count = $votes['helpful_count'] ?? 0;
                                        $not_helpful_count = $votes['not_helpful_count'] ?? 0;

                                        // Check if current user has voted on this review
                                        $user_vote = null;
                                        if (isset($_SESSION['user_id'])) {
                                            $stmt = $conn->prepare("SELECT is_helpful FROM review_votes WHERE review_id = ? AND user_id = ?");
                                            $stmt->bind_param('ii', $review['id'], $_SESSION['user_id']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $user_vote = $result->fetch_assoc();
                                            $stmt->close();
                                        }
                                    ?>
                                        <div class="bg-white p-6 rounded-lg shadow-sm">
                                            <div class="flex items-start mb-4">
                                                <?php if (!empty($review['profile_pic']) && file_exists('uploads/profiles/' . $review['profile_pic'])): ?>
                                                    <img src="uploads/profiles/<?= htmlspecialchars($review['profile_pic']) ?>"
                                                        alt="<?= htmlspecialchars($review['firstname'] . ' ' . $review['lastname']) ?>"
                                                        class="w-12 h-12 rounded-full object-cover mr-4">
                                                <?php else: ?>
                                                    <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 mr-4">
                                                        <i class="fas fa-user text-xl"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <div>
                                                    <h4 class="font-semibold"><?= htmlspecialchars($review['firstname'] . ' ' . $review['lastname']) ?></h4>
                                                    <div class="flex items-center mb-1">
                                                        <div class="flex mr-2">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="fas fa-star text-<?= $i <= $review['rating'] ? 'yellow-400' : 'gray-300' ?> text-sm"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <span class="text-gray-500 text-sm">
                                                            <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <h5 class="font-medium text-lg mb-2"><?= htmlspecialchars($review['title']) ?></h5>
                                            <p class="text-gray-700 mb-4"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>

                                            <div class="flex items-center text-sm text-gray-500">
                                                <span class="mr-4">Was this review helpful?</span>
                                                <button class="review-vote-btn mr-2 px-2 py-1 rounded <?= ($user_vote && $user_vote['is_helpful'] == 1) ? 'bg-green-100 text-green-700' : 'hover:bg-gray-100' ?>"
                                                    data-review-id="<?= $review['id'] ?>" data-helpful="1"
                                                    <?= ($user_vote) ? 'disabled' : '' ?>>
                                                    <i class="far fa-thumbs-up mr-1"></i> Yes (<?= $helpful_count ?>)
                                                </button>
                                                <button class="review-vote-btn px-2 py-1 rounded <?= ($user_vote && $user_vote['is_helpful'] == 0) ? 'bg-red-100 text-red-700' : 'hover:bg-gray-100' ?>"
                                                    data-review-id="<?= $review['id'] ?>" data-helpful="0"
                                                    <?= ($user_vote) ? 'disabled' : '' ?>>
                                                    <i class="far fa-thumbs-down mr-1"></i> No (<?= $not_helpful_count ?>)
                                                </button>

                                                <?php if ($user_vote): ?>
                                                    <span class="ml-2 text-xs text-gray-500">You've already voted</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="bg-white p-8 rounded-lg shadow-sm text-center">
                                    <i class="fas fa-comment-alt text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-xl font-medium text-gray-600">No reviews yet</h3>
                                    <p class="text-gray-500 mt-2">Be the first to review this product</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Review Form Modal -->
                    <?php if (isset($_SESSION['user_id']) && !$user_has_reviewed): ?>
                        <div id="reviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
                                <div class="p-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Write a Review</h3>
                                        <button id="closeReviewModal" class="text-gray-500 hover:text-gray-700">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    <form id="reviewForm" method="POST" action="submit_review.php">
                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">

                                        <div class="mb-4">
                                            <label class="block text-gray-700 mb-2">Rating *</label>
                                            <div class="rating-stars flex">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <button type="button" class="star-rating-btn text-2xl mr-1 focus:outline-none" data-rating="<?= $i ?>">
                                                        <i class="far fa-star text-yellow-400"></i>
                                                    </button>
                                                <?php endfor; ?>
                                            </div>
                                            <input type="hidden" name="rating" id="ratingInput" required>
                                            <p class="text-red-500 text-sm mt-1 hidden" id="ratingError">Please select a rating</p>
                                        </div>

                                        <div class="mb-4">
                                            <label for="reviewTitle" class="block text-gray-700 mb-2">Title *</label>
                                            <input type="text" id="reviewTitle" name="title"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                placeholder="Summarize your experience" required>
                                        </div>

                                        <div class="mb-4">
                                            <label for="reviewComment" class="block text-gray-700 mb-2">Review *</label>
                                            <textarea id="reviewComment" name="comment" rows="4"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                placeholder="Share details about your experience with this product" required></textarea>
                                        </div>

                                        <div class="flex justify-end space-x-3">
                                            <button type="button" id="cancelReview" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition duration-200">
                                                Cancel
                                            </button>
                                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-200">
                                                Submit Review
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Related Products -->
                <?php if (!empty($related_products)): ?>
                    <div class="mt-12">
                        <h2 class="text-2xl font-bold mb-6">You May Also Like</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                            <?php foreach ($related_products as $related): ?>
                                <div class="related-product-card bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                                    <a href="product.php?id=<?= $related['id'] ?>">
                                        <img src="<?= !empty($related['primary_image']) ? 'uploads/products/' . htmlspecialchars($related['primary_image']) : 'images/placeholder-product.jpg' ?>"
                                            alt="<?= htmlspecialchars($related['name']) ?>"
                                            class="w-full h-48 object-cover">
                                    </a>
                                    <div class="p-4">
                                        <a href="product.php?id=<?= $related['id'] ?>" class="font-medium hover:text-green-600">
                                            <?= htmlspecialchars($related['name']) ?>
                                        </a>
                                        <div class="mt-2">
                                            <?php if (isset($related['discount_price'])): ?>
                                                <span class="text-green-600 font-bold">$<?= number_format($related['discount_price'], 2) ?></span>
                                                <span class="text-sm text-gray-400 line-through ml-1">$<?= number_format($related['price'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-green-600 font-bold">$<?= number_format($related['price'], 2) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <script>
                // Gallery image switching
                function changeMainImage(src, element) {
                    document.getElementById('mainImage').src = src;
                    document.querySelectorAll('.gallery-thumbnail').forEach(thumb => {
                        thumb.classList.remove('active');
                    });
                    element.classList.add('active');
                }

                // Quantity controls
                document.getElementById('incrementQty').addEventListener('click', function() {
                    const input = document.getElementById('quantity');
                    const max = parseInt(input.max);
                    if (input.value < max) {
                        input.value = parseInt(input.value) + 1;
                    }
                });

                document.getElementById('decrementQty').addEventListener('click', function() {
                    const input = document.getElementById('quantity');
                    if (input.value > 1) {
                        input.value = parseInt(input.value) - 1;
                    }
                });

                // Tab switching
                document.querySelectorAll('.tab-button').forEach(button => {
                    button.addEventListener('click', function() {
                        const tabId = this.getAttribute('data-tab');

                        // Update active tab button
                        document.querySelectorAll('.tab-button').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');

                        // Show corresponding tab content
                        document.querySelectorAll('.tab-content').forEach(content => {
                            content.classList.remove('active');
                        });
                        document.getElementById(tabId + '-tab').classList.add('active');
                    });
                });

                // Add to cart form submission
                document.getElementById('addToCartForm').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const productId = <?= $product['id'] ?>;
                    const quantity = document.getElementById('quantity').value;
                    const addToCartBtn = document.getElementById('addToCartBtn');

                    if (<?= $product['stock'] ?> <= 0) {
                        alert('This product is out of stock');
                        return;
                    }

                    // Show loading state
                    const originalText = addToCartBtn.innerHTML;
                    addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
                    addToCartBtn.disabled = true;

                    // Make AJAX request
                    fetch('ajax_add_to_cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `product_id=${productId}&quantity=${quantity}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update ALL cart count elements in the page
                                document.querySelectorAll('.cart-count').forEach(el => {
                                    el.textContent = data.cart_count;
                                    el.classList.remove('hidden');
                                });

                                // Show success message
                                const toast = document.createElement('div');
                                toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-md shadow-lg flex items-center';
                                toast.innerHTML = `
              <i class="fas fa-check-circle mr-2"></i>
              <span>Item added to cart</span>
            `;
                                document.body.appendChild(toast);

                                // Remove toast after 3 seconds
                                setTimeout(() => {
                                    toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                                    setTimeout(() => toast.remove(), 300);
                                }, 3000);
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while adding to cart');
                        })
                        .finally(() => {
                            // Restore button state
                            addToCartBtn.innerHTML = originalText;
                            addToCartBtn.disabled = false;
                        });
                });

                // Review modal handling
                document.getElementById('writeReviewBtn').addEventListener('click', function() {
                    document.getElementById('reviewModal').classList.remove('hidden');
                });

                document.getElementById('closeReviewModal').addEventListener('click', function() {
                    document.getElementById('reviewModal').classList.add('hidden');
                });

                document.getElementById('cancelReview').addEventListener('click', function() {
                    document.getElementById('reviewModal').classList.add('hidden');
                });

                // Star rating selection
                document.querySelectorAll('.star-rating-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const rating = parseInt(this.getAttribute('data-rating'));
                        document.getElementById('ratingInput').value = rating;

                        // Update star display
                        document.querySelectorAll('.star-rating-btn').forEach((btn, index) => {
                            const starIcon = btn.querySelector('i');
                            if (index < rating) {
                                starIcon.classList.remove('far');
                                starIcon.classList.add('fas');
                            } else {
                                starIcon.classList.remove('fas');
                                starIcon.classList.add('far');
                            }
                        });
                    });
                });

                // Review voting
                document.querySelectorAll('.review-vote-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const reviewId = this.getAttribute('data-review-id');
                        const isHelpful = this.getAttribute('data-helpful') === '1';
                        const button = this;

                        // Check if user is logged in
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                            return;
                        <?php endif; ?>

                        // Make AJAX request
                        fetch('ajax_review_vote.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `review_id=${reviewId}&is_helpful=${isHelpful ? 1 : 0}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update button counts
                                    const yesBtn = document.querySelector(`.review-vote-btn[data-review-id="${reviewId}"][data-helpful="1"]`);
                                    const noBtn = document.querySelector(`.review-vote-btn[data-review-id="${reviewId}"][data-helpful="0"]`);

                                    if (isHelpful) {
                                        yesBtn.innerHTML = `<i class="far fa-thumbs-up mr-1"></i> Yes (${data.helpful_count})`;
                                    } else {
                                        noBtn.innerHTML = `<i class="far fa-thumbs-down mr-1"></i> No (${data.not_helpful_count})`;
                                    }

                                    // Show success message
                                    const toast = document.createElement('div');
                                    toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-md shadow-lg flex items-center';
                                    toast.innerHTML = `<i class="fas fa-check-circle mr-2"></i><span>Thank you for your feedback!</span>`;
                                    document.body.appendChild(toast);

                                    setTimeout(() => {
                                        toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                                        setTimeout(() => toast.remove(), 300);
                                    }, 3000);
                                } else {
                                    alert(data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while submitting your vote');
                            });
                    });
                });
            </script>
</body>

</html>
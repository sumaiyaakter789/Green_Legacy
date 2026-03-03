<?php
// Start session and check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// Database connection
require_once 'db_connect.php';

// Initialize variables
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$productType = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build the query with filters
$query = "SELECT p.*, c.name as category_name, pi.quantity 
          FROM products p
          JOIN categories c ON p.category_id = c.id
          JOIN product_inventory pi ON p.id = pi.product_id
          WHERE 1=1";

$params = [];
$types = '';

// Apply search filter
if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

// Apply category filter
if (!empty($category) && $category !== 'all') {
    $query .= " AND p.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

// Apply product type filter
if (!empty($productType) && $productType !== 'all') {
    $query .= " AND p.product_type = ?";
    $params[] = $productType;
    $types .= 's';
}

// Apply status filter
if (!empty($status)) {
    if ($status === 'active') {
        $query .= " AND p.is_active = 1";
    } elseif ($status === 'inactive') {
        $query .= " AND p.is_active = 0";
    } elseif ($status === 'outofstock') {
        $query .= " AND pi.quantity <= 0";
    } elseif ($status === 'lowstock') {
        $query .= " AND pi.quantity > 0 AND pi.quantity <= pi.low_stock_threshold";
    }
}

// Apply sorting
switch ($sort) {
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'stock_asc':
        $query .= " ORDER BY pi.quantity ASC";
        break;
    case 'stock_desc':
        $query .= " ORDER BY pi.quantity DESC";
        break;
    default: // newest
        $query .= " ORDER BY p.created_at DESC";
        break;
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all categories for filter dropdown
$categories = [];
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

?>

<?php include 'admin_sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Management - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin-left: 280px; /* Match sidebar width */
    }
    
    @media (max-width: 1024px) {
      body {
        margin-left: 0;
      }
    }
    
    .content-container {
      padding: 2rem;
    }
    
    /* Status badges */
    .badge-active {
      background-color: #d1fae5;
      color: #065f46;
    }
    
    .badge-inactive {
      background-color: #fee2e2;
      color: #b91c1c;
    }
    
    .badge-outofstock {
      background-color: #fee2e2;
      color: #b91c1c;
    }
    
    .badge-lowstock {
      background-color: #fef3c7;
      color: #92400e;
    }
    
    .badge-instock {
      background-color: #d1fae5;
      color: #065f46;
    }
    
    /* Type badges */
    .badge-type {
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
    
    /* Table styles */
    .table-container {
      overflow-x: auto;
    }
    
    .product-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .product-table th, .product-table td {
      padding: 0.75rem 1rem;
      text-align: left;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .product-table th {
      background-color: #f9fafb;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
      color: #6b7280;
    }
    
    .product-table tr:hover {
      background-color: #f9fafb;
    }
    
    /* Action buttons */
    .action-btn {
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.875rem;
      transition: all 0.2s;
    }
    
    .edit-btn {
      background-color: #e0f2fe;
      color: #0369a1;
    }
    
    .edit-btn:hover {
      background-color: #bae6fd;
    }
    
    .delete-btn {
      background-color: #fee2e2;
      color: #b91c1c;
    }
    
    .delete-btn:hover {
      background-color: #fecaca;
    }
    
    /* Filter styles */
    .filter-container {
      background-color: #f9fafb;
      border-radius: 0.5rem;
      padding: 1rem;
      margin-bottom: 1.5rem;
    }
    
    .filter-group {
      margin-bottom: 1rem;
    }
    
    /* Alert messages */
    .alert {
      padding: 0.75rem 1rem;
      border-radius: 0.375rem;
      margin-bottom: 1rem;
    }
    
    .alert-success {
      background-color: #d1fae5;
      color: #065f46;
    }
    
    .alert-error {
      background-color: #fee2e2;
      color: #b91c1c;
    }
    
    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 1.5rem;
    }
    
    .page-item {
      margin: 0 0.25rem;
    }
    
    .page-link {
      display: block;
      padding: 0.5rem 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 0.25rem;
      color: #374151;
      text-decoration: none;
    }
    
    .page-link:hover {
      background-color: #f3f4f6;
    }
    
    .page-item.active .page-link {
      background-color: #10b981;
      color: white;
      border-color: #10b981;
    }
    
    .page-item.disabled .page-link {
      color: #9ca3af;
      pointer-events: none;
      background-color: #f9fafb;
    }
  </style>
</head>
<body>
  <div class="content-container">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Product Management</h1>
      <a href="admin_add_product.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center">
        <i class="fas fa-plus mr-2"></i> Add New Product
      </a>
    </div>
    
    <!-- Display messages -->
    <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
        <?php 
          echo $_SESSION['message']; 
          unset($_SESSION['message']);
          unset($_SESSION['message_type']);
        ?>
      </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="filter-container">
      <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="filter-group">
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                 class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500" 
                 placeholder="Search products...">
        </div>
        
        <!-- Category -->
        <div class="filter-group">
          <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
            <option value="all">All Categories</option>
            <?php foreach ($categories as $categoryItem): ?>
              <option value="<?php echo $categoryItem['id']; ?>" <?php echo $category == $categoryItem['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($categoryItem['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <!-- Product Type -->
        <div class="filter-group">
          <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Product Type</label>
          <select id="type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
            <option value="all" <?php echo $productType === 'all' ? 'selected' : ''; ?>>All Types</option>
            <option value="plant" <?php echo $productType === 'plant' ? 'selected' : ''; ?>>Plants</option>
            <option value="tool" <?php echo $productType === 'tool' ? 'selected' : ''; ?>>Tools</option>
            <option value="pesticide" <?php echo $productType === 'pesticide' ? 'selected' : ''; ?>>Pesticides</option>
            <option value="fertilizer" <?php echo $productType === 'fertilizer' ? 'selected' : ''; ?>>Fertilizers</option>
            <option value="accessory" <?php echo $productType === 'accessory' ? 'selected' : ''; ?>>Accessories</option>
          </select>
        </div>
        
        <!-- Status -->
        <div class="filter-group">
          <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
            <option value="all" <?php echo empty($status) || $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            <option value="outofstock" <?php echo $status === 'outofstock' ? 'selected' : ''; ?>>Out of Stock</option>
            <option value="lowstock" <?php echo $status === 'lowstock' ? 'selected' : ''; ?>>Low Stock</option>
          </select>
        </div>
        
        <!-- Sort -->
        <div class="filter-group">
          <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
          <select id="sort" name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
            <option value="stock_asc" <?php echo $sort === 'stock_asc' ? 'selected' : ''; ?>>Stock (Low to High)</option>
            <option value="stock_desc" <?php echo $sort === 'stock_desc' ? 'selected' : ''; ?>>Stock (High to Low)</option>
          </select>
        </div>
        
        <!-- Filter buttons -->
        <div class="filter-group md:col-span-4 flex justify-between">
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
            Apply Filters
          </button>
          <a href="admin_products.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md">
            Reset Filters
          </a>
        </div>
      </form>
    </div>
    
    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden table-container">
      <table class="product-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Product</th>
            <th>Category</th>
            <th>Type</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-gray-500">No products found matching your criteria</td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $product): ?>
              <?php
              // Determine stock status
              if ($product['quantity'] <= 0) {
                  $stockStatus = 'Out of Stock';
                  $stockClass = 'badge-outofstock';
              } elseif ($product['quantity'] <= 5) { // Assuming low_stock_threshold is 5
                  $stockStatus = 'Low Stock';
                  $stockClass = 'badge-lowstock';
              } else {
                  $stockStatus = 'In Stock';
                  $stockClass = 'badge-instock';
              }
              
              // Determine product status
              $statusClass = $product['is_active'] ? 'badge-active' : 'badge-inactive';
              $statusText = $product['is_active'] ? 'Active' : 'Inactive';
              
              // Determine product type class
              $typeClass = 'badge-' . $product['product_type'];
              ?>
              <tr>
                <td><?php echo $product['id']; ?></td>
                <td>
                  <div class="font-medium"><?php echo htmlspecialchars($product['name']); ?></div>
                  <div class="text-sm text-gray-500"><?php echo htmlspecialchars($product['sku']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td>
                  <span class="badge-type <?php echo $typeClass; ?>">
                    <?php echo ucfirst($product['product_type']); ?>
                  </span>
                </td>
                <td>
                  <div class="font-medium">$<?php echo number_format($product['price'], 2); ?></div>
                  <?php if ($product['discount_price']): ?>
                    <div class="text-sm text-green-600">
                      <s>$<?php echo number_format($product['price'], 2); ?></s> 
                      $<?php echo number_format($product['discount_price'], 2); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="<?php echo $stockClass; ?> px-2 py-1 rounded-full text-xs font-medium">
                    <?php echo $stockStatus; ?> (<?php echo $product['quantity']; ?>)
                  </span>
                </td>
                <td>
                  <span class="<?php echo $statusClass; ?> px-2 py-1 rounded-full text-xs font-medium">
                    <?php echo $statusText; ?>
                  </span>
                  <?php if ($product['is_featured']): ?>
                    <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-medium ml-1">
                      Featured
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="flex space-x-2">
                    <a href="admin_edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn edit-btn">
                      <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <a href="admin_delete_product.php?id=<?php echo $product['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this product?')">
                      <i class="fas fa-trash-alt mr-1"></i> Delete
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <div class="pagination">
      <ul class="flex">
        <li class="page-item disabled">
          <a class="page-link" href="#" tabindex="-1">Previous</a>
        </li>
        <li class="page-item active">
          <a class="page-link" href="#">1</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">2</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">3</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">Next</a>
        </li>
      </ul>
    </div>
  </div>
  
  <script>
    // Toggle product status
    document.addEventListener('DOMContentLoaded', function() {
      // You can add JavaScript functionality here if needed
      // For example, quick status toggles or inline editing
    });
  </script>
</body>
</html>
<?php
require_once 'db_connect.php';

// Pagination configuration
$perPage = 9; // Multiple of 3 for consistent rows
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Get total notices count
$totalQuery = "SELECT COUNT(*) as total FROM notices WHERE is_published = 1";
$totalResult = $conn->query($totalQuery);
$total = $totalResult->fetch_assoc()['total'];
$pages = ceil($total / $perPage);

// Get notices for current page
$query = "SELECT n.*, a.username as author 
          FROM notices n
          JOIN admins a ON n.admin_id = a.admin_id
          WHERE n.is_published = 1
          ORDER BY n.published_at DESC
          LIMIT $start, $perPage";
$result = $conn->query($query);
$notices = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'navbar.php'; ?>

<div class="container mx-auto mt-8 px-4 py-8">
    <h1 class="text-3xl font-bold text-green-700 mb-8 text-center">Latest Notices</h1>
    
    <?php if (empty($notices)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-600">No notices available at the moment.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($notices as $notice): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 flex flex-col h-full">
                    <div class="p-6 flex-grow">
                        <h2 class="text-xl font-semibold text-gray-800 mb-2">
                            <a href="notice_details.php?id=<?= $notice['notice_id'] ?>" class="hover:text-green-600">
                                <?= htmlspecialchars($notice['title']) ?>
                            </a>
                        </h2>
                        <p class="text-gray-500 text-sm mb-4">
                            Published on <?= date('F j, Y', strtotime($notice['published_at'])) ?> by Admin
                        </p>
                        <p class="text-gray-600 mb-4">
                            <?= substr(strip_tags($notice['content']), 0, 150) ?>...
                        </p>
                    </div>
                    <div class="px-6 pb-4">
                        <a href="notice_details.php?id=<?= $notice['notice_id'] ?>" class="text-green-600 hover:text-green-800 font-medium">
                            Read more &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100">
                            &larr; Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i == $page ? 'bg-green-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-100' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
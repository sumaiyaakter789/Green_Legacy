<?php 
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header('Location: notices.php');
    exit;
}

$noticeId = (int)$_GET['id'];
$query = "SELECT n.*, a.username as author 
          FROM notices n
          JOIN admins a ON n.admin_id = a.admin_id
          WHERE n.notice_id = ? AND n.is_published = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $noticeId);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();

if (!$notice) {
    header('Location: notices.php');
    exit;
}

// 🔹 Fetch other notices (excluding current one)
$relatedQuery = "SELECT notice_id, title, published_at 
                 FROM notices 
                 WHERE is_published = 1 AND notice_id != ? 
                 ORDER BY published_at DESC 
                 LIMIT 8";
$relatedStmt = $conn->prepare($relatedQuery);
$relatedStmt->bind_param('i', $noticeId);
$relatedStmt->execute();
$relatedResult = $relatedStmt->get_result();
$otherNotices = $relatedResult->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'navbar.php'; ?>

<div class="container mx-auto px-4 mt-8 py-8 max-w-7xl">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Notice Details -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 md:p-8">
                    <!-- Logo -->
                    <div class="flex justify-center mb-6">
                        <img src="lg-logo.png" alt="Green Legacy Logo" class="h-40 w-auto">
                    </div>
                    
                    <h1 class="text-3xl font-bold text-gray-800 mb-4">
                        <?= htmlspecialchars($notice['title']) ?>
                    </h1>
                    
                    <div class="flex items-center text-gray-500 text-sm mb-6">
                        <span>Published on <?= date('F j, Y', strtotime($notice['published_at'])) ?></span>
                        <span class="mx-2">•</span>
                        <span>By Admin</span>
                    </div>
                    
                    <div class="prose max-w-none text-gray-700">
                        <?= nl2br(htmlspecialchars($notice['content'])) ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-8">
                <a href="notices.php" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    &larr; Back to Notices
                </a>
            </div>
        </div>
        
        <!-- Right: Sidebar Other Notices -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Other Notices</h2>
                
                <?php if (!empty($otherNotices)): ?>
                    <ul class="space-y-4">
                        <?php foreach ($otherNotices as $other): ?>
                            <li>
                                <a href="notice_details.php?id=<?= $other['notice_id'] ?>" 
                                   class="block border-b pb-2 hover:text-green-700">
                                    <h3 class="font-medium">
                                        <?= htmlspecialchars($other['title']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($other['published_at'])) ?>
                                    </p>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500">No other notices available.</p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include 'footer.php'; ?>

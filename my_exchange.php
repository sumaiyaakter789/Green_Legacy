<?php
require_once 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Fetch all exchange offers by this user
$exchange_offers = [];
$stmt = $conn->prepare("SELECT e.*, 
                      (SELECT COUNT(*) FROM exchange_interests WHERE exchange_id = e.id) as interest_count
                      FROM exchange_offers e
                      WHERE e.user_id = ?
                      ORDER BY 
                        CASE e.status 
                            WHEN 'pending' THEN 1
                            WHEN 'approved' THEN 2
                            WHEN 'completed' THEN 3
                            WHEN 'rejected' THEN 4
                        END,
                        e.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $exchange_offers[] = $row;
}

// Count exchanges by status
$exchange_counts = [
    'pending' => 0,
    'approved' => 0,
    'completed' => 0,
    'rejected' => 0
];

foreach ($exchange_offers as $offer) {
    $exchange_counts[$offer['status']]++;
}

// Get accepted interests for completed exchanges
$accepted_interests = [];
if (!empty($exchange_offers)) {
    $exchange_ids = array_column($exchange_offers, 'id');
    $placeholders = implode(',', array_fill(0, count($exchange_ids), '?'));
    
    $stmt = $conn->prepare("SELECT ei.*, u.firstname, u.lastname, u.profile_pic 
                          FROM exchange_interests ei
                          JOIN users u ON ei.interested_user_id = u.id
                          WHERE ei.exchange_id IN ($placeholders) AND ei.status = 'accepted'");
    $stmt->bind_param(str_repeat('i', count($exchange_ids)), ...$exchange_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $accepted_interests[$row['exchange_id']] = $row;
    }
}
?>

<?php include 'navbar.php'; ?>

<div class="container mx-auto mt-10 px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Exchanges</h1>
            <a href="create_exchange.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-plus mr-2"></i> Create New Exchange
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Pending</p>
                        <h3 class="text-xl font-bold text-gray-700"><?php echo $exchange_counts['pending']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Approved</p>
                        <h3 class="text-xl font-bold text-gray-700"><?php echo $exchange_counts['approved']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-purple-100 text-purple-600 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Completed</p>
                        <h3 class="text-xl font-bold text-gray-700"><?php echo $exchange_counts['completed']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-red-100 text-red-600 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Rejected</p>
                        <h3 class="text-xl font-bold text-gray-700"><?php echo $exchange_counts['rejected']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exchange Offers Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Looking For</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interests</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($exchange_offers)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    You haven't created any exchange offers yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exchange_offers as $offer): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if ($offer['images']): ?>
                                                <?php 
                                                    $images = explode(',', $offer['images']);
                                                    $firstImage = $images[0];
                                                ?>
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars($firstImage); ?>" alt="<?php echo htmlspecialchars($offer['plant_name']); ?>">
                                                </div>
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($offer['plant_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($offer['plant_type']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php if ($offer['exchange_type'] == 'with_money'): ?>
                                                $<?php echo number_format($offer['expected_amount'], 2); ?>
                                                <?php if ($offer['expected_plant']): ?>
                                                    or <?php echo htmlspecialchars($offer['expected_plant']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($offer['expected_plant'] ?? 'Any plant'); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $offer['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                                                   ($offer['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($offer['status'] == 'completed' ? 'bg-purple-100 text-purple-800' : 'bg-red-100 text-red-800')); ?>">
                                            <?php echo ucfirst($offer['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($offer['status'] == 'completed' && isset($accepted_interests[$offer['id']])): ?>
                                            <div class="flex items-center">
                                                <img class="h-6 w-6 rounded-full object-cover mr-2" src="<?php echo htmlspecialchars($accepted_interests[$offer['id']]['profile_pic'] ?? 'default-user.png'); ?>" alt="Accepted user">
                                                <span><?php echo htmlspecialchars($accepted_interests[$offer['id']]['firstname'] . ' ' . $accepted_interests[$offer['id']]['lastname']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <?php echo $offer['interest_count']; ?> interest(s)
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($offer['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <i class="text-green-600 mr-3">View Only</i>
                                        <?php if ($offer['status'] == 'pending' || $offer['status'] == 'approved'): ?>
                                            <a href="exchange_detail.php?id=<?php echo $offer['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">View</a>
                                            <a href="edit_exchange.php?id=<?php echo $offer['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
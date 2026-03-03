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

// Fetch order count
$order_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$order_count = $row['count'];

// Fetch wishlist count
$wishlist_count = 0;

// Fetch exchange counts
$exchange_counts = [
    'active' => 0,
    'completed' => 0
];

// Active exchanges (approved but not completed)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM exchange_offers WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$exchange_counts['active'] = $row['count'];

// Completed exchanges
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM exchange_offers WHERE user_id = ? AND status = 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$exchange_counts['completed'] = $row['count'];

// Fetch recent orders
$recent_orders = [];
$stmt = $conn->prepare("SELECT id, order_number, status, total_amount, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_orders[] = $row;
}
?>

<?php include 'navbar.php'; ?>

<div class="container mx-auto mt-10 px-2 py-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($user['firstname']); ?>!</h1>
            <p class="text-gray-600">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500">Total Orders</p>
                        <h3 class="text-2xl font-bold text-gray-700"><?php echo $order_count; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500">Active Exchanges</p>
                        <h3 class="text-2xl font-bold text-gray-700"><?php echo $exchange_counts['active']; ?></h3>
                        <p class="text-sm text-gray-500">Completed: <?php echo $exchange_counts['completed']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Reward Points Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500">Reward Points</p>
                        <h3 class="text-2xl font-bold text-gray-700"><?php echo $user['reward_point']; ?></h3>
                        <p class="text-sm text-gray-500">Earn more points for activities</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500">Location</p>
                        <h3 class="text-lg font-bold text-gray-700">
                            <?php
                            if ($user['latitude'] && $user['longitude']) {
                                echo "Saved (" . round($user['latitude'], 4) . ", " . round($user['longitude'], 4) . ")";
                            } else {
                                echo "Not set";
                            }
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">Recent Orders</h2>
                <a href="orders.php" class="text-green-600 hover:text-green-800">View All</a>
            </div>

            <?php if (empty($recent_orders)): ?>
                <p class="text-gray-500 py-4">You haven't placed any orders yet.</p>
                <a href="shop.php" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                    Start Shopping
                </a>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $order['order_number']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : ($order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-green-600 hover:text-green-900">View</a>
                                        <span>|</span>
                                        <a href="generate_receipt.php?order_id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900">Download Receipt</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Appointments Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">Your Appointments</h2>
                <a href="appointments.php" class="text-green-600 hover:text-green-800">View All</a>
            </div>

            <?php
            // Fetch upcoming appointments
            $upcoming_appointments = [];
            $stmt = $conn->prepare("SELECT a.*, ad.username as consultant_name 
                                  FROM appointments a 
                                  JOIN admins ad ON a.consultant_id = ad.admin_id 
                                  WHERE a.user_id = ? AND a.date >= CURDATE() 
                                  ORDER BY a.date ASC, a.time_slot ASC LIMIT 3");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $upcoming_appointments[] = $row;
            }
            ?>

            <?php if (empty($upcoming_appointments)): ?>
                <div class="text-center py-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">No upcoming appointments</h3>
                    <p class="mt-1 text-sm text-gray-500">Book a consultation with our gardening experts.</p>
                    <div class="mt-6">
                        <a href="book_appointment.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Book Appointment
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consultant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // Define time slots
                            $time_slots = [
                                '9:00 AM - 10:00 AM',
                                '10:00 AM - 11:00 AM',
                                '11:00 AM - 12:00 PM',
                                '1:00 PM - 2:00 PM',
                                '2:00 PM - 3:00 PM',
                                '3:00 PM - 4:00 PM',
                                '4:00 PM - 5:00 PM'
                            ];

                            foreach ($upcoming_appointments as $appointment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['consultant_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($appointment['date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $time_slots[$appointment['time_slot']]; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $appointment['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : ($appointment['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : ($appointment['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')); ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <a href="appointment_details.php?id=<?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900">View</a>
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <span>|</span>
                                            <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-red-600 hover:text-red-900">Cancel</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Forum Posts Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">Recent Forum Posts</h2>
                <a href="my_posts.php" class="text-green-600 hover:text-green-800">View All</a>
            </div>

            <?php
            // Fetch user's recent forum posts
            $recent_posts = [];
            $stmt = $conn->prepare("SELECT fp.id, fp.title, fp.created_at, 
                          COUNT(DISTINCT pr.id) as reaction_count,
                          COUNT(DISTINCT pc.id) as comment_count
                          FROM forum_posts fp
                          LEFT JOIN post_reactions pr ON fp.id = pr.post_id
                          LEFT JOIN post_comments pc ON fp.id = pc.post_id
                          WHERE fp.user_id = ?
                          GROUP BY fp.id
                          ORDER BY fp.created_at DESC LIMIT 3");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $recent_posts[] = $row;
            }
            ?>

            <?php if (empty($recent_posts)): ?>
                <div class="text-center py-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">No forum posts yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Share your plant experiences with the community.</p>
                    <div class="mt-6">
                        <a href="forum.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Create Post
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_posts as $post): ?>
                        <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                            <a href="forum.php#post-<?php echo $post['id']; ?>" class="block hover:bg-gray-50 p-2 rounded">
                                <h3 class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <div class="flex justify-between items-center mt-1">
                                    <p class="text-sm text-gray-500">Posted on <?php echo date('M j, Y', strtotime($post['created_at'])); ?></p>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                            </svg>
                                            <?php echo $post['reaction_count']; ?>
                                        </span>
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <?php echo $post['comment_count']; ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>


        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="profile.php" class="bg-white rounded-lg shadow-md p-4 flex items-center justify-center hover:bg-gray-50 transition-colors duration-200">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-700">Profile Settings</span>
                </div>
            </a>

            <a href="my_exchange.php" class="bg-white rounded-lg shadow-md p-4 flex items-center justify-center hover:bg-gray-50 transition-colors duration-200">
                <div class="text-center">
                    <!-- Exchange Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582M20 20v-5h-.581M4 9a9 9 0 0115.418-3.36M20 15a9 9 0 01-15.418 3.36" />
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-700">My Exchange</span>
                </div>
            </a>

            <a href="logout.php" class="bg-white rounded-lg shadow-md p-4 flex items-center justify-center hover:bg-gray-50 transition-colors duration-200">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-700">Logout</span>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
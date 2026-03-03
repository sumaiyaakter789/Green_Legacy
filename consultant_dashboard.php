<?php
require_once 'db_connect.php';

// Check consultant authentication
session_start();
if (!isset($_SESSION['consultant_logged_in']) || $_SESSION['consultant_logged_in'] !== true) {
    header('Location: consultant_login.php');
    exit;
}

$consultant_id = $_SESSION['consultant_id'];

// Get consultant details
$consultant = [];
try {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->bind_param("i", $consultant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $consultant = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching consultant details: " . $e->getMessage());
}

// Handle status updates that trigger fee creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($appointment_id && $status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Update appointment status
            $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND consultant_id = ?");
            $stmt->bind_param("sii", $status, $appointment_id, $consultant_id);
            $stmt->execute();

            // If status changed to 'completed', create a fee record
            if ($status === 'completed') {
                // Check if fee already exists for this appointment
                $check_stmt = $conn->prepare("SELECT fee_id FROM consultant_fee WHERE appointment_id = ?");
                $check_stmt->bind_param("i", $appointment_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    // Create new fee record
                    $fee_stmt = $conn->prepare("INSERT INTO consultant_fee (consultant_id, appointment_id, amount) VALUES (?, ?, 10.00)");
                    $fee_stmt->bind_param("ii", $consultant_id, $appointment_id);
                    $fee_stmt->execute();
                    $fee_stmt->close();
                }
                
                $check_stmt->close();
            }

            $conn->commit();
            $_SESSION['success_message'] = "Appointment status updated successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error updating appointment: " . $e->getMessage());
            $_SESSION['error_message'] = "Error updating appointment status. Please try again.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid appointment data provided.";
    }

    header("Location: consultant_dashboard.php");
    exit;
}

// Get consultant's earnings statistics
$earnings = [
    'total_earned' => 0,
    'pending_payment' => 0,
    'paid' => 0,
    'completed_appointments' => 0
];

try {
    // Total earned (all time)
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM consultant_fee WHERE consultant_id = ?");
    $stmt->bind_param("i", $consultant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $earnings['total_earned'] = $row['total'] ?? 0;
    $stmt->close();

    // Pending payments
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM consultant_fee WHERE consultant_id = ? AND payment_status = 'pending'");
    $stmt->bind_param("i", $consultant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $earnings['pending_payment'] = $row['total'] ?? 0;
    $stmt->close();

    // Paid amount
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM consultant_fee WHERE consultant_id = ? AND payment_status = 'paid'");
    $stmt->bind_param("i", $consultant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $earnings['paid'] = $row['total'] ?? 0;
    $stmt->close();

    // Completed appointments count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE consultant_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $consultant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $earnings['completed_appointments'] = $row['total'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching earnings data: " . $e->getMessage());
}

// Get recent completed appointments with fees
$completed_appointments = [];
try {
    $stmt = $conn->prepare("
        SELECT a.id, a.date, a.time_slot, 
               u.firstname, u.lastname, 
               f.amount, f.payment_status, f.created_at as fee_date
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN consultant_fee f ON a.id = f.appointment_id
        WHERE a.consultant_id = ? AND a.status = 'completed'
        ORDER BY a.date DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $consultant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $completed_appointments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching completed appointments: " . $e->getMessage());
}

// Time slots configuration
$time_slots = [
    '09:00 - 10:00',
    '10:00 - 11:00',
    '11:00 - 12:00',
    '13:00 - 14:00',
    '14:00 - 15:00',
    '15:00 - 16:00',
    '16:00 - 17:00'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include 'consultant_sidebar.php'; ?>

    <div class="main-content">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Consultant Dashboard</h1>
            <div class="text-sm text-gray-500">
                <i class="far fa-calendar-alt mr-1"></i>
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Welcome back, <?php echo htmlspecialchars($consultant['username']); ?>!</h2>
            <p class="text-gray-600">Here's an overview of your appointments and earnings.</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex justify-between items-center">
                    <span><?php echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']); ?></span>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <div class="flex justify-between items-center">
                    <span><?php echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']); ?></span>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-red-700 hover:text-red-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Earnings Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Earnings Card -->
            <div class="bg-white rounded-lg shadow overflow-hidden card">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm font-medium">Total Earnings</h3>
                            <p class="text-2xl font-semibold text-gray-800">$<?php echo number_format($earnings['total_earned'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Payments Card -->
            <div class="bg-white rounded-lg shadow overflow-hidden card">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm font-medium">Pending Payments</h3>
                            <p class="text-2xl font-semibold text-gray-800">$<?php echo number_format($earnings['pending_payment'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paid Amount Card -->
            <div class="bg-white rounded-lg shadow overflow-hidden card">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm font-medium">Paid Amount</h3>
                            <p class="text-2xl font-semibold text-gray-800">$<?php echo number_format($earnings['paid'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Appointments Card -->
            <div class="bg-white rounded-lg shadow overflow-hidden card">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm font-medium">Completed Appointments</h3>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $earnings['completed_appointments']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Completed Appointments -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Recent Completed Appointments</h2>
                <p class="text-sm text-gray-500">Appointments marked as completed with associated fees</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($completed_appointments)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No completed appointments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($completed_appointments as $appointment): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo date('M j, Y', strtotime($appointment['date'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['firstname'] . ' ' . $appointment['lastname']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $time_slots[$appointment['time_slot']]; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">$<?php echo number_format($appointment['amount'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo 'status-' . $appointment['payment_status']; ?>">
                                            <?php echo ucfirst($appointment['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($appointment['fee_date'])); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 text-right">
                <a href="consultant_schedule.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    View all appointments
                </a>
            </div>
        </div>

        <!-- Upcoming Appointments (Optional) -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Upcoming Appointments</h2>
                <p class="text-sm text-gray-500">Your next 5 upcoming appointments</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        // Get upcoming appointments
                        $upcoming_appointments = [];
                        try {
                            $stmt = $conn->prepare("
                                SELECT a.id, a.date, a.time_slot, a.status,
                                       u.firstname, u.lastname
                                FROM appointments a
                                JOIN users u ON a.user_id = u.id
                                WHERE a.consultant_id = ? AND a.date >= CURDATE() AND a.status != 'completed'
                                ORDER BY a.date ASC, a.time_slot ASC
                                LIMIT 5
                            ");
                            $stmt->bind_param("i", $consultant_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $upcoming_appointments = $result->fetch_all(MYSQLI_ASSOC);
                            $stmt->close();
                        } catch (Exception $e) {
                            error_log("Error fetching upcoming appointments: " . $e->getMessage());
                        }
                        ?>

                        <?php if (empty($upcoming_appointments)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No upcoming appointments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo date('M j, Y', strtotime($appointment['date'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['firstname'] . ' ' . $appointment['lastname']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $time_slots[$appointment['time_slot']]; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo 'status-' . $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="post" class="inline-flex">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // You can add JavaScript for charts or other interactive elements here
        document.addEventListener('DOMContentLoaded', function() {
            // Example chart (you can customize this)
            const ctx = document.createElement('canvas');
            ctx.id = 'earningsChart';
            document.querySelector('.main-content').appendChild(ctx);
            
            const earningsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Total Earnings', 'Pending Payments', 'Paid Amount'],
                    datasets: [{
                        label: 'Earnings Overview',
                        data: [
                            <?php echo $earnings['total_earned']; ?>,
                            <?php echo $earnings['pending_payment']; ?>,
                            <?php echo $earnings['paid']; ?>
                        ],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.2)',
                            'rgba(245, 158, 11, 0.2)',
                            'rgba(59, 130, 246, 0.2)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(59, 130, 246, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>
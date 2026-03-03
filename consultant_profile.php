<?php
require_once 'db_connect.php';

// Check consultant authentication
session_start();
if (!isset($_SESSION['consultant_logged_in']) || $_SESSION['consultant_logged_in'] !== true) {
    header('Location: consultant_login.php');
    exit;
}

$consultant_id = $_SESSION['consultant_id'];

// Get consultant data
try {
    $stmt = $conn->prepare("SELECT username, email, created_at FROM admins WHERE admin_id = ?");
    $stmt->bind_param("i", $consultant_id);
    $stmt->execute();
    $consultant = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    error_log("Error fetching consultant data: " . $e->getMessage());
    $consultant = [];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    }
    
    // Check if password is being changed
    $password_changed = !empty($current_password) || !empty($new_password) || !empty($confirm_password);
    
    if ($password_changed) {
        // Verify current password
        try {
            $stmt = $conn->prepare("SELECT password FROM admins WHERE admin_id = ?");
            $stmt->bind_param("i", $consultant_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!password_verify($current_password, $result['password'])) {
                $errors['current_password'] = 'Current password is incorrect';
            }
        } catch (Exception $e) {
            $errors['database'] = 'Error verifying password';
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }
        
        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        try {
            if ($password_changed) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET username = ?, password = ? WHERE admin_id = ?");
                $stmt->bind_param("ssi", $username, $hashed_password, $consultant_id);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE admin_id = ?");
                $stmt->bind_param("si", $username, $consultant_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['consultant_username'] = $username;
                $_SESSION['success_message'] = "Profile updated successfully!";
                header("Location: consultant_profile.php");
                exit;
            } else {
                $errors['database'] = "Error updating profile";
            }
        } catch (Exception $e) {
            $errors['database'] = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Consultant Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }
        .profile-card {
            transition: all 0.3s ease;
        }
        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'consultant_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
            <div class="text-sm text-gray-500">
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($errors['database'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $errors['database']; unset($errors['database']); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Information -->
            <div class="lg:col-span-2">
                <div class="profile-card bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Profile Information</h2>
                    <form method="post">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($consultant['username']); ?>" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <?php if (isset($errors['username'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['username']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($consultant['email']); ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-100" readonly>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" name="current_password" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <?php if (isset($errors['current_password'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['current_password']; ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500 mt-1">Required only if changing password</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" name="new_password" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <?php if (isset($errors['new_password'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['new_password']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <input type="password" name="confirm_password" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['confirm_password']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-6 text-right">
                            <button type="submit" name="update_profile" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Information -->
            <div>
                <div class="profile-card bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Account Information</h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Role</p>
                            <p class="text-gray-800">Consultant</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Member Since</p>
                            <p class="text-gray-800"><?php echo date('M j, Y', strtotime($consultant['created_at'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Last Login</p>
                            <p class="text-gray-800"><?php echo date('M j, Y H:i'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Photo -->
                <div class="profile-card bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Profile Photo</h2>
                    <div class="flex flex-col items-center">
                        <div class="w-32 h-32 rounded-full bg-gray-200 mb-4 flex items-center justify-center overflow-hidden">
                            <i class="fas fa-user text-4xl text-gray-400"></i>
                        </div>
                        <div class="text-center">
                            <button class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 mb-2">
                                Upload New Photo
                            </button>
                            <p class="text-xs text-gray-500">JPG, GIF or PNG. Max size 2MB</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('consultantSidebar').classList.toggle('open');
        });
    </script>
</body>
</html>
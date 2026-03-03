<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if message ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_messages.php");
    exit();
}

$message_id = $_GET['id'];

// Fetch the message
$stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->bind_param("i", $message_id);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();

// If message not found
if (!$message) {
    header("Location: admin_messages.php");
    exit();
}

// Mark message as read
$update_stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE id = ?");
$update_stmt->bind_param("i", $message_id);
$update_stmt->execute();

// Handle email reply
$reply_sent = false;
$reply_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $reply_subject = trim($_POST['subject']);
    $reply_message = trim($_POST['message']);
    
    if (!empty($reply_subject) && !empty($reply_message)) {
        // Include PHPMailer files
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        require_once 'PHPMailer/src/Exception.php';
        
        // Use fully-qualified class names instead of 'use' inside a block
        // Create a new PHPMailer instance
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'info.afnan27@gmail.com';
            $mail->Password = 'rokp jusi apxx wjkn';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom('info.afnan27@gmail.com', 'Admin');
            $mail->addAddress($message['email'], $message['name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $reply_subject;
            $mail->Body = nl2br(htmlspecialchars($reply_message));
            $mail->AltBody = $reply_message; // Plain text version
            
            $mail->send();
            $reply_sent = true;
        
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $reply_error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $reply_error = "Please fill in both subject and message fields.";
    }
}

require_once 'admin_sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="ml-0 lg:ml-64 p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Message Details</h1>
                <a href="admin_messages.php" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Messages
                </a>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if ($reply_sent): ?>
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"> Your reply has been sent successfully.</span>
                </div>
            <?php elseif (!empty($reply_error)): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"> <?php echo htmlspecialchars($reply_error); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">From</h3>
                        <p class="mt-1 text-lg font-medium text-gray-900"><?php echo htmlspecialchars($message['name']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Email</h3>
                        <p class="mt-1 text-lg font-medium text-gray-900"><?php echo htmlspecialchars($message['email']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Date</h3>
                        <p class="mt-1 text-lg font-medium text-gray-900">
                            <?php echo date('F j, Y \a\t g:i a', strtotime($message['created_at'])); ?>
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                        <span class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            <?php echo $message['is_read'] ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800'; ?>">
                            <?php echo $message['is_read'] ? 'Read' : 'New'; ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($message['subject'])): ?>
                <div class="mb-8">
                    <h3 class="text-sm font-medium text-gray-500">Subject</h3>
                    <p class="mt-1 text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($message['subject']); ?></p>
                </div>
                <?php endif; ?>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Message</h3>
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <p class="text-gray-800 whitespace-pre-line"><?php echo htmlspecialchars($message['message']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Reply Form -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Send Reply</h2>
                
                <form method="POST" action="">
                    <div class="mb-6">
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" 
                               id="subject" 
                               name="subject" 
                               value="Re: <?php echo htmlspecialchars($message['subject'] ?? 'Your message'); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                               required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Your Reply</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            rows="8"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                            placeholder="Type your reply message here..."
                            required></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" 
                                onclick="location.href='admin_messages.php'" 
                                class="px-6 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                name="send_reply"
                                class="px-6 py-3 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-paper-plane mr-2"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="mt-6 flex justify-end space-x-4">
                <a href="admin_delete_message.php?id=<?php echo $message['id']; ?>" 
                   class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                   onclick="return confirm('Are you sure you want to delete this message?');">
                    <i class="fas fa-trash mr-2"></i> Delete Message
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-resize textarea
        const textarea = document.getElementById('message');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>
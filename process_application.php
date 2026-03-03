<?php
require_once 'db_connect.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $cover_letter = trim($_POST['cover_letter'] ?? '');
    
    // Check if required fields are filled
    if (empty($job_id) || empty($name) || empty($email)) {
        die(json_encode(['success' => false, 'message' => 'Please fill all required fields']));
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die(json_encode(['success' => false, 'message' => 'Please enter a valid email address']));
    }
    
    // Handle file upload
    $resume_path = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['resume']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            die(json_encode(['success' => false, 'message' => 'Only PDF and Word documents are allowed']));
        }
        
        $upload_dir = 'uploads/resumes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
        $file_name = 'resume_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $target_path)) {
            $resume_path = $target_path;
        }
    }
    
    // Get job title for email
    $job_title = '';
    $stmt = $conn->prepare("SELECT title FROM jobs WHERE id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $job_title = $row['title'];
    }
    $stmt->close();
    
    // Insert application into database
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("INSERT INTO job_applications (job_id, applicant_name, applicant_email, applicant_phone, cover_letter, resume_path) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $job_id, $name, $email, $phone, $cover_letter, $resume_path);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Send confirmation email
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username  = 'info.afnan27@gmail.com';
                $mail->Password   = 'rokp jusi apxx wjkn';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                
                // Recipients
                $mail->setFrom('info.afnan27@gmail.com', 'Green Legacy Hiring Team');
                $mail->addAddress($email, $name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Application Received - ' . $job_title;
                
                $mail->Body = "
                    <p>Dear $name,</p>
                    <p>Thank you for applying for the position of <strong>$job_title</strong> with Green Legacy.</p>
                    <p>We have successfully received your application and our hiring team will review it carefully.</p>
                    <p>Here's a summary of your application:</p>
                    <ul>
                        <li><strong>Position:</strong> $job_title</li>
                        <li><strong>Application Date:</strong> " . date('F j, Y') . "</li>
                        " . ($phone ? "<li><strong>Phone:</strong> $phone</li>" : "") . "
                    </ul>
                    <p>We appreciate the time and effort you've invested in applying with us. If your qualifications match our requirements, we'll contact you for the next steps in the hiring process.</p>
                    <p>Please note that due to the volume of applications we receive, we may not be able to respond to each applicant individually.</p>
                    <p>Best regards,<br>Green Legacy Hiring Team</p>
                ";
                
                $mail->AltBody = "Dear $name,\n\nThank you for applying for the position of $job_title with Green Legacy.\n\nWe have successfully received your application and our hiring team will review it carefully.\n\nApplication Details:\n- Position: $job_title\n- Application Date: " . date('F j, Y') . "\n" . ($phone ? "- Phone: $phone\n" : "") . "\nWe appreciate the time and effort you've invested in applying with us. If your qualifications match our requirements, we'll contact you for the next steps in the hiring process.\n\nPlease note that due to the volume of applications we receive, we may not be able to respond to each applicant individually.\n\nBest regards,\nGreen Legacy Hiring Team";
                
                $mail->send();
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Application submitted successfully! A confirmation email has been sent to your email address.']);
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Email sending failed: " . $mail->ErrorInfo);
                echo json_encode(['success' => false, 'message' => 'Application submitted but confirmation email could not be sent.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit application']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    header("Location: careers.php");
    exit;
}
?>
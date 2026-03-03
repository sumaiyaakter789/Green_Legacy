<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests are allowed']);
    exit;
}

if (!isset($_FILES['plantImage']) || $_FILES['plantImage']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No image file uploaded or upload error']);
    exit;
}

$uploadedFile = $_FILES['plantImage'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
if (!in_array($uploadedFile['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, JPEG, PNG allowed']);
    exit;
}

if ($uploadedFile['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File size too large. Maximum 5MB allowed']);
    exit;
}

try {
    $uploadDir = __DIR__ . '/../uploads/disease_detection/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
    $filename = 'plant_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        throw new Exception('Failed to save uploaded file');
    }

    $result = callDiseaseDetectionAPI($filePath);

    // Return success response
    echo json_encode([
        'success' => true,
        'plant_type' => $result['plant_type'],
        'plant_confidence' => $result['plant_confidence'],
        'disease_name' => $result['disease_name'],
        'disease_confidence' => $result['disease_confidence'],
        'severity' => $result['severity'],
        'severity_confidence' => $result['severity_confidence'],
        'is_healthy' => $result['is_healthy'],
        'image_url' => '/uploads/disease_detection/' . $filename
    ]);

} catch (Exception $e) {
    error_log("Disease detection error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Analysis failed: ' . $e->getMessage()]);
}

function callDiseaseDetectionAPI($imagePath) {
    $pythonScript = __DIR__ . '/../python/disease_detector.py';
    
    if (!file_exists($pythonScript)) {
        throw new Exception('Disease detection service not available');
    }

    $pythonExecutable = 'python';
    
    $command = escapeshellarg($pythonExecutable) . " " . escapeshellarg($pythonScript) . " " . escapeshellarg($imagePath);
    
    $output = shell_exec($command . ' 2>&1');
    
    if ($output === null) {
        throw new Exception('Failed to execute disease detection');
    }

    error_log("Full Python output: " . $output);

    $lines = explode("\n", trim($output));
    $jsonOutput = '';
    
    // Find the last line that contains valid JSON
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $line = trim($lines[$i]);
        if (!empty($line)) {
            // Check if this line looks like JSON
            if (strpos($line, '{') === 0 && strpos($line, '}') === strlen($line) - 1) {
                $jsonOutput = $line;
                break;
            }
        }
    }

    if (empty($jsonOutput)) {
        preg_match('/\{.*\}/s', $output, $matches);
        if (!empty($matches)) {
            $jsonOutput = $matches[0];
        }
    }

    if (empty($jsonOutput)) {
        throw new Exception('No valid JSON response found from disease detection. Raw output: ' . substr($output, 0, 500));
    }

    $result = json_decode($jsonOutput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from disease detection: ' . json_last_error_msg() . '. Raw: ' . substr($jsonOutput, 0, 200));
    }

    if (!isset($result['success']) || !$result['success']) {
        throw new Exception($result['error'] ?? 'Unknown error from disease detection');
    }

    return $result;
}
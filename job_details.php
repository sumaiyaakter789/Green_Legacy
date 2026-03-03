<?php
require_once 'db_connect.php';
include 'navbar.php';

if (!isset($_GET['id'])) {
    header('Location: careers.php');
    exit;
}

$job_id = (int)$_GET['id'];

// Get job details
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND is_active = TRUE AND (deadline IS NULL OR deadline >= CURDATE())");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();

if (!$job) {
    echo "<script>alert('Job not found or no longer active.'); window.location.href = 'careers.php';</script>";
    exit;
}

// Increment view count
$conn->query("UPDATE jobs SET views = views + 1 WHERE id = $job_id");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - Green Legacy</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .job-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .full-time {
            background-color: #d1fae5;
            color: #065f46;
        }

        .part-time {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .contract {
            background-color: #ede9fe;
            color: #5b21b6;
        }

        .internship {
            background-color: #fef3c7;
            color: #92400e;
        }

        .temporary {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto mt-2 px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="flex text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="index.php" class="inline-flex items-center text-gray-500 hover:text-green-600">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
                            <a href="careers.php" class="text-gray-500 hover:text-green-600">Careers</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
                            <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($job['title']); ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                            <div class="flex items-center text-gray-600 mb-4">
                                <i class="fas fa-building mr-2"></i>
                                <span><?php echo htmlspecialchars($job['department']); ?></span>
                                <span class="mx-2">•</span>
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <span><?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                        </div>
                        <span class="job-type <?php echo strtolower(str_replace('-', '', $job['job_type'])); ?>">
                            <?php echo htmlspecialchars($job['job_type']); ?>
                        </span>
                    </div>

                    <?php if ($job['salary_range']): ?>
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <h3 class="font-semibold text-gray-800 mb-2">Salary Range</h3>
                            <p class="text-gray-700"><?php echo htmlspecialchars($job['salary_range']); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="prose max-w-none mb-8">
                        <h3 class="text-xl font-semibold text-gray-800 mb-3">Job Description</h3>
                        <?php echo $job['description']; ?>
                    </div>

                    <div class="prose max-w-none mb-8">
                        <h3 class="text-xl font-semibold text-gray-800 mb-3">Responsibilities</h3>
                        <?php echo $job['responsibilities']; ?>
                    </div>

                    <div class="prose max-w-none mb-8">
                        <h3 class="text-xl font-semibold text-gray-800 mb-3">Requirements</h3>
                        <?php echo $job['requirements']; ?>
                    </div>

                    <?php if ($job['benefits']): ?>
                        <div class="prose max-w-none mb-8">
                            <h3 class="text-xl font-semibold text-gray-800 mb-3">Benefits</h3>
                            <?php echo $job['benefits']; ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-col sm:flex-row justify-between items-center mt-8 border-t border-gray-200 pt-6">
                        <div class="mb-4 sm:mb-0">
                            <p class="text-gray-600">
                                <span class="font-medium">Posted:</span> <?php echo date('M d, Y', strtotime($job['posted_at'])); ?>
                                <?php if ($job['deadline']): ?>
                                    <span class="mx-2">•</span>
                                    <span class="font-medium">Deadline:</span> <?php echo date('M d, Y', strtotime($job['deadline'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <a href="#apply" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                            Apply Now
                        </a>
                    </div>
                </div>
            </div>

            <!-- Application Form -->
            <div id="apply" class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Apply for This Position</h2>

                    <form id="jobApplicationForm" class="space-y-6" enctype="multipart/form-data">
                        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input type="text" id="name" name="name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input type="email" id="email" name="email" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="tel" id="phone" name="phone"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label for="resume" class="block text-sm font-medium text-gray-700 mb-1">Resume (PDF/DOC) *</label>
                                <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <p class="mt-1 text-xs text-gray-500">Max file size: 5MB</p>
                            </div>
                        </div>
                        <div>
                            <label for="cover_letter" class="block text-sm font-medium text-gray-700 mb-1">Cover Letter</label>
                            <textarea id="cover_letter" name="cover_letter" rows="5"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="agree" name="agree" required
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="agree" class="ml-2 block text-sm text-gray-700">
                                I agree to the processing of my personal data for recruitment purposes *
                            </label>
                        </div>
                        <div>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                                Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.getElementById('jobApplicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const form = e.target;
            const formData = new FormData(form);
            
            // Validate file size
            const resumeInput = document.getElementById('resume');
            if (resumeInput.files[0] && resumeInput.files[0].size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            // Submit via AJAX
            fetch('process_application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert(data.message);
                    form.reset();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Application';
            });
        });
    </script>
</body>
</html>
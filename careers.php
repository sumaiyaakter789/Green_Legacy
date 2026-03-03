<?php
require_once 'db_connect.php';
include 'navbar.php';

// Get all active jobs
$jobs = [];
$stmt = $conn->prepare("SELECT id, title, department, location, job_type, salary_range, posted_at FROM jobs WHERE is_active = TRUE AND (deadline IS NULL OR deadline >= CURDATE()) ORDER BY posted_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers - Green Legacy</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .job-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-left-color: #2E8B57;
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
    <div class="container mx-auto mt-4 px-4 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Join Our Team</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Help us grow the <b><i>Green Legacy</i></b> while growing your career
            </p>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No current openings</h3>
                <p class="mt-1 text-gray-500">We don't have any job openings at the moment. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <span class="job-type <?php echo strtolower(str_replace('-', '', $job['job_type'])); ?>">
                                    <?php echo htmlspecialchars($job['job_type']); ?>
                                </span>
                            </div>
                            <div class="flex items-center text-gray-600 mb-3">
                                <i class="fas fa-building mr-2"></i>
                                <span><?php echo htmlspecialchars($job['department']); ?></span>
                            </div>
                            <div class="flex items-center text-gray-600 mb-3">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <span><?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                            <?php if ($job['salary_range']): ?>
                                <div class="flex items-center text-gray-600 mb-4">
                                    <i class="fas fa-money-bill-wave mr-2"></i>
                                    <span><?php echo htmlspecialchars($job['salary_range']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-sm text-gray-500">
                                    Posted <?php echo date('M d, Y', strtotime($job['posted_at'])); ?>
                                </span>
                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="text-green-600 hover:text-green-800 font-medium">
                                    See Details <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
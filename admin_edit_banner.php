<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once 'db_connect.php';

// Get banner details
$banner = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $banner = $result->fetch_assoc();

    if (!$banner) {
        $_SESSION['error'] = "Banner not found";
        header("Location: admin_banners.php");
        exit;
    }

    // Format dates for display
    $banner['start_date'] = $banner['start_date'] ? date('Y-m-d', strtotime($banner['start_date'])) : '';
    $banner['end_date'] = $banner['end_date'] ? date('Y-m-d', strtotime($banner['end_date'])) : '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['banner_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $link = $_POST['link'];
    $button_text = $_POST['button_text'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $position = (int)$_POST['position'];
    $start_date = $_POST['start_date'] ? $_POST['start_date'] . ' 00:00:00' : NULL;
    $end_date = $_POST['end_date'] ? $_POST['end_date'] . ' 23:59:59' : NULL;

    // Check if new image was uploaded
    if ($_FILES['image']['size'] > 0) {
        $target_dir = "uploads/banners/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error'] = "File is not an image.";
            header("Location: admin_edit_banner.php?id=$id");
            exit;
        }

        $filename = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Delete old image
            $stmt = $conn->prepare("SELECT image_path FROM banners WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old_image = $result->fetch_assoc()['image_path'];
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }

            $image_path = $target_file;
            $stmt = $conn->prepare("UPDATE banners SET title=?, description=?, image_path=?, link=?, button_text=?, is_active=?, position=?, start_date=?, end_date=? WHERE id=?");
            $stmt->bind_param("sssssiissi", $title, $description, $image_path, $link, $button_text, $is_active, $position, $start_date, $end_date, $id);
        } else {
            $_SESSION['error'] = "Sorry, there was an error uploading your file.";
            header("Location: admin_edit_banner.php?id=$id");
            exit;
        }
    } else {
        $stmt = $conn->prepare("UPDATE banners SET title=?, description=?, link=?, button_text=?, is_active=?, position=?, start_date=?, end_date=? WHERE id=?");
        $stmt->bind_param("ssssiissi", $title, $description, $link, $button_text, $is_active, $position, $start_date, $end_date, $id);
    }

    $stmt->execute();
    $_SESSION['message'] = "Banner updated successfully!";
    header("Location: admin_banners.php");
    exit;
}
?>

<?php include 'admin_sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Banner</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin-left: 280px;
        }
    </style>
</head>
<body>
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Edit Banner</h1>
            <a href="admin_banners.php" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i> Back to Banners
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= $_SESSION['error'] ?></span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if ($banner): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <form method="POST" action="admin_edit_banner.php" enctype="multipart/form-data">
                    <input type="hidden" name="banner_id" value="<?= $banner['id'] ?>">

                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Title *</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($banner['title']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded"><?= htmlspecialchars($banner['description']) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Current Image</label>
                        <img src="<?= htmlspecialchars($banner['image_path']) ?>" alt="Current Banner" class="h-32 w-full object-cover rounded mb-2">
                        <label class="block text-gray-700 mb-2">Change Image (leave blank to keep current)</label>
                        <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Link URL (optional)</label>
                            <input type="url" name="link" value="<?= htmlspecialchars($banner['link']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Button Text (optional)</label>
                            <input type="text" name="button_text" value="<?= htmlspecialchars($banner['button_text']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Position (order of display)</label>
                        <input type="number" name="position" value="<?= htmlspecialchars($banner['position']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Start Date (optional)</label>
                            <input type="date" name="start_date" value="<?= htmlspecialchars($banner['start_date']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">End Date (optional)</label>
                            <input type="date" name="end_date" value="<?= htmlspecialchars($banner['end_date']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                        </div>
                    </div>

                    <div class="mb-4 flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" <?= $banner['is_active'] ? 'checked' : '' ?> class="mr-2">
                        <label for="is_active" class="text-gray-700">Active</label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i> Update Banner
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
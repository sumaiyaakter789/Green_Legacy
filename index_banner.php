<?php
require_once 'db_connect.php';

// Fetch active banners ordered by position and date range
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT * FROM banners WHERE is_active = 1 AND (start_date IS NULL OR start_date <= ?) AND (end_date IS NULL OR end_date >= ?) ORDER BY position ASC, start_date ASC");
$stmt->bind_param("ss", $now, $now);
$stmt->execute();
$result = $stmt->get_result();
$banners = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php if (!empty($banners)): ?>
    <div class="relative w-full mt-8 overflow-hidden">
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php foreach ($banners as $banner): ?>
                    <div class="swiper-slide relative">
                        <img src="<?= htmlspecialchars($banner['image_path']) ?>" alt="Banner Image" class="w-full h-[600px] object-cover">
                        <div class="absolute inset-0 bg-black bg-opacity-40 flex flex-col justify-center items-center text-center text-white px-6">
                            <h2 class="text-4xl font-bold mb-2">
                                <?= htmlspecialchars($banner['title']) ?>
                            </h2>
                            <p class="text-xl mb-4">
                                <?= htmlspecialchars($banner['description']) ?>
                            </p>
                            <?php if (!empty($banner['link']) && !empty($banner['button_text'])): ?>
                                <a href="<?= htmlspecialchars($banner['link']) ?>" class="absolute" style="margin-left: 980px; margin-top: 60px; background-color: darkgreen; border-radius: 40px; padding: 10px 20px; color: white; text-decoration: none;">
                                    <?= htmlspecialchars($banner['button_text']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Swiper navigation buttons -->
            <div class="swiper-button-next text-white"></div>
            <div class="swiper-button-prev text-white"></div>
        </div>
    </div>

    <!-- Swiper.js CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

    <!-- Swiper Initialization -->
    <script>
        const swiper = new Swiper('.swiper-container', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    </script>
<?php endif; ?>

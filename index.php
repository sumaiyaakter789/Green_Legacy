<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include 'navbar.php';
include 'preloader.php';
include 'index_banner.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Legacy - Cultivate Your Green Paradise</title>
    <style>
        /* Custom scrollbar for chat messages */
        #chatMessages::-webkit-scrollbar {
            width: 6px;
        }

        #chatMessages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #chatMessages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        #chatMessages::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Landing page specific styles */
        .feature-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(46, 204, 113, 0.1);
        }

        .cta-button:hover {
            background: linear-gradient(to right, #27ae60, #2ecc71);
            box-shadow: 0 4px 6px rgba(46, 204, 113, 0.2);
        }

        .customer-logos-slider {
            display: flex;
            width: 200%;
            animation: slide 20s linear infinite;
        }

        @keyframes slide {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .customer-logos-slider img {
            filter: grayscale(100%);
            transition: all 0.3s ease;
            max-height: 80px;
            width: auto;
        }

        .customer-logos-slider img:hover {
            filter: grayscale(0%);
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <!-- Main Content -->
    <main class="relative overflow-hidden">
        <!-- Features Section -->
        <section class="py-16 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">What We Offer</h2>
                <p class="text-lg text-center text-gray-600 max-w-3xl mx-auto mb-12">
                    Everything you need to start and grow your green sanctuary
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Plant Shop -->
                    <a href="shop.php" class="feature-card bg-white rounded-xl p-6 border border-green-100 flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-leaf text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Plant Shop</h3>
                        <p class="text-gray-600">Discover rare and beautiful plants for your home or garden</p>
                        <span class="mt-4 text-green-600 font-medium flex items-center">
                            Explore plants <i class="fas fa-arrow-right ml-2 text-sm"></i>
                        </span>
                    </a>

                    <!-- Gardening Tools -->
                    <a href="tools.php" class="feature-card bg-white rounded-xl p-6 border border-green-100 flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-tools text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Gardening Tools</h3>
                        <p class="text-gray-600">Premium quality tools to make gardening effortless</p>
                        <span class="mt-4 text-green-600 font-medium flex items-center">
                            Browse tools <i class="fas fa-arrow-right ml-2 text-sm"></i>
                        </span>
                    </a>

                    <!-- Knowledge Base -->
                    <a href="blogs.php" class="feature-card bg-white rounded-xl p-6 border border-green-100 flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-book-open text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Knowledge Base</h3>
                        <p class="text-gray-600">Learn from experts with our comprehensive guides</p>
                        <span class="mt-4 text-green-600 font-medium flex items-center">
                            Learn more <i class="fas fa-arrow-right ml-2 text-sm"></i>
                        </span>
                    </a>

                    <!-- Plant Exchange -->
                    <a href="exchange.php" class="feature-card bg-white rounded-xl p-6 border border-green-100 flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-exchange-alt text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Plant Exchange</h3>
                        <p class="text-gray-600">Trade plants with our community of enthusiasts</p>
                        <span class="mt-4 text-green-600 font-medium flex items-center">
                            Join exchange <i class="fas fa-arrow-right ml-2 text-sm"></i>
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Popular Plants Section -->
        <section class="py-16 bg-green-50 relative">
            <div class="plant-bg absolute inset-0"></div>
            <div class="container mx-auto px-4 relative">
                <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">Popular Plants</h2>
                <p class="text-lg text-center text-gray-600 max-w-3xl mx-auto mb-12">
                    Explore our most sought-after green companions
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Medicinal Plants -->
                    <div class="bg-white rounded-xl overflow-hidden shadow-md">
                        <img src="images/plants/medicinal-aloe.jpg" alt="Medicinal Plants" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Medicinal Plants Collection</h3>
                            <p class="text-gray-600 mb-4">Healing plants like Aloe Vera and Holy Basil</p>
                            <a href="shop.php?category=1" class="text-green-600 font-medium flex items-center">
                                Browse Medicinal Plants <i class="fas fa-arrow-right ml-2 text-sm"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Succulents & Cacti -->
                    <div class="bg-white rounded-xl overflow-hidden shadow-md">
                        <img src="images/plants/succulent-mix.jpg" alt="Succulents and Cacti" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Succulents & Cacti</h3>
                            <p class="text-gray-600 mb-4">Low-maintenance beauties perfect for busy plant parents</p>
                            <a href="shop.php?category=4" class="text-green-600 font-medium flex items-center">
                                View Succulents <i class="fas fa-arrow-right ml-2 text-sm"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Aromatic Herbs -->
                    <div class="bg-white rounded-xl overflow-hidden shadow-md">
                        <img src="images/plants/herb-garden.jpg" alt="Aromatic Herbs" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Aromatic Herb Garden</h3>
                            <p class="text-gray-600 mb-4">Fragrant herbs for cooking and wellness</p>
                            <a href="shop.php?category=2" class="text-green-600 font-medium flex items-center">
                                Explore Herbs <i class="fas fa-arrow-right ml-2 text-sm"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Indoor Foliage -->
                    <div class="bg-white rounded-xl overflow-hidden shadow-md">
                        <img src="images/plants/indoor-foliage.jpg" alt="Indoor Plants" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Air-Purifying Indoor Plants</h3>
                            <p class="text-gray-600 mb-4">Clean your air naturally with these leafy companions</p>
                            <a href="shop.php?category=3" class="text-green-600 font-medium flex items-center">
                                View Indoor Plants <i class="fas fa-arrow-right ml-2 text-sm"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Flowering Shrubs -->
                    <div class="bg-white rounded-xl overflow-hidden shadow-md">
                        <img src="images/plants/flowering-shrub.jpg" alt="Flowering Shrubs" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Flowering Shrubs</h3>
                            <p class="text-gray-600 mb-4">Add vibrant colors to your outdoor space</p>
                            <a href="shop.php?category=7" class="text-green-600 font-medium flex items-center">
                                Browse Shrubs <i class="fas fa-arrow-right ml-2 text-sm"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Pollinator Plants -->
                    <div class="bg-white rounded-xl overflow-hidden shadow-md">
                        <img src="images/plants/pollinator-garden.jpg" alt="Pollinator Plants" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Pollinator-Friendly Plants</h3>
                            <p class="text-gray-600 mb-4">Attract butterflies and bees to your garden</p>
                            <a href="shop.php?category=21" class="text-green-600 font-medium flex items-center">
                                Help the Ecosystem <i class="fas fa-arrow-right ml-2 text-sm"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <a href="shop.php" class="cta-button inline-block px-8 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-full font-medium shadow-lg">
                        View All Plant Categories
                    </a>
                </div>
            </div>
        </section>

        <!-- Guides Section -->
        <section class="py-16 bg-white">
            <div class="container mx-auto px-4">
                <div class="flex flex-col lg:flex-row items-center">
                    <div class="lg:w-1/2 mb-8 lg:mb-0 lg:pr-12">
                        <img src="" alt="Gardening Guide" class="rounded-lg shadow-md w-full">
                    </div>
                    <div class="lg:w-1/2">
                        <h2 class="text-3xl font-bold text-gray-800 mb-4">Beginner's Gardening Guide</h2>
                        <p class="text-lg text-gray-600 mb-6">
                            New to gardening? Our comprehensive guides will help you get started with everything from
                            selecting the right plants to maintaining a thriving garden.
                        </p>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Step-by-step tutorials for beginners</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Seasonal planting calendars</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Troubleshooting plant problems</span>
                            </li>
                        </ul>
                        <a href="guides.php" class="cta-button inline-block px-6 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg font-medium">
                            Explore Guides
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Community Section -->
        <section class="py-16 bg-green-800 text-white">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold mb-4">Join Our Green Community</h2>
                <p class="text-xl max-w-3xl mx-auto mb-8">
                    Connect with thousands of plant lovers, share your gardening journey, and get expert advice
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <div class="bg-green-700 bg-opacity-50 p-6 rounded-lg">
                        <div class="text-4xl mb-4">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">10,000+ Members</h3>
                        <p>Active community of plant enthusiasts</p>
                    </div>

                    <div class="bg-green-700 bg-opacity-50 p-6 rounded-lg">
                        <div class="text-4xl mb-4">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Expert Advice</h3>
                        <p>Get answers from experienced gardeners</p>
                    </div>

                    <div class="bg-green-700 bg-opacity-50 p-6 rounded-lg">
                        <div class="text-4xl mb-4">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Monthly Events</h3>
                        <p>Workshops, plant swaps, and more</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="forum.php" class="px-6 py-3 bg-white text-green-800 rounded-lg font-medium hover:bg-gray-100 transition">
                        Visit Forum
                    </a>
                    <a href="signup.php" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-medium transition">
                        Join Now
                    </a>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="py-16 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">What Our Community Says</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Testimonial 1 -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <div class="flex items-center mb-4">
                            <img src="default-user.png" alt="Sarah J." class="w-12 h-12 rounded-full mr-4">
                            <div>
                                <h4 class="font-semibold">Sarah J.</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600">
                            "The beginner's guide helped me transform my balcony into a green oasis. My plants have never been happier!"
                        </p>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <div class="flex items-center mb-4">
                            <img src="default-user.png" alt="Michael T." class="w-12 h-12 rounded-full mr-4">
                            <div>
                                <h4 class="font-semibold">Michael T.</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600">
                            "Found the perfect rare monstera through the plant exchange. The community here is amazing!"
                        </p>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <div class="flex items-center mb-4">
                            <img src="default-user.png" alt="Priya K." class="w-12 h-12 rounded-full mr-4">
                            <div>
                                <h4 class="font-semibold">Priya K.</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600">
                            "The quality of plants I received was exceptional. My home feels so much more alive now."
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Customers -->
        <section class="py-16 bg-gray-50 overflow-hidden">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Trusted By</h2>

                <div class="relative w-full overflow-hidden">
                    <!-- Slider Container -->
                    <div class="customer-logos-slider flex">
                        <!-- Logo Set (repeated for seamless looping) -->
                        <div class="flex items-center min-w-max space-x-16 px-4">
                            <img src="images/customers/home-depot.png" alt="Home Depot" class="h-10 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/miracle-gro.png" alt="Miracle-Gro" class="h-12 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/bhg.png" alt="Better Homes & Gardens" class="h-14 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/burpee.png" alt="Burpee Seeds" class="h-10 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/eden-project.png" alt="Eden Project" class="h-16 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/urban-outfitters.png" alt="Urban Outfitters" class="h-12 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/usda-organic.png" alt="USDA Organic" class="h-14 opacity-80 hover:opacity-100 transition">
                        </div>
                        <!-- Duplicate Set -->
                        <div class="flex items-center min-w-max space-x-16 px-4">
                            <img src="images/customers/home-depot.png" alt="Home Depot" class="h-10 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/miracle-gro.png" alt="Miracle-Gro" class="h-12 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/bhg.png" alt="Better Homes & Gardens" class="h-14 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/burpee.png" alt="Burpee Seeds" class="h-10 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/eden-project.png" alt="Eden Project" class="h-16 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/urban-outfitters.png" alt="Urban Outfitters" class="h-12 opacity-80 hover:opacity-100 transition">
                            <img src="images/customers/usda-organic.png" alt="USDA Organic" class="h-14 opacity-80 hover:opacity-100 transition">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.js"></script>
    <?php include 'chat.php'; ?>
</body>

</html>
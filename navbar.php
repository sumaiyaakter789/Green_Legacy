<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];

// Get the current file name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Legacy</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-green: #2E8B57;
      --light-green: #a8e6cf;
      --gradient-bg: linear-gradient(135deg, #a8e6cf, #dcedc1, #ffd3b6, #ffaaa5);
    }

    body {
      font-family: 'Poppins', sans-serif;
      padding-top: 120px;
    }

    .top-bar {
      background: var(--gradient-bg);
      background-size: 400% 400%;
      animation: gradientFlow 15s ease infinite;
    }

    .nav-bar {
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 2px 15px rgba(46, 204, 113, 0.2);
      margin-top: 10px;
    }

    .nav-link {
      position: relative;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .nav-link:hover {
      color: var(--primary-green);
    }

    .nav-link::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--primary-green);
      transition: width 0.3s ease;
    }

    .nav-link:hover::after {
      width: 100%;
    }

    .logo-hover:hover {
      transform: scale(1.15);
      transition: transform 0.1s ease;
    }

    .cart-hover:hover {
      transform: scale(1.2);
      transition: transform 0.1s ease;
    }

    .get-started-hover:hover {
      transform: scale(1.08);
      transition: transform 0.1s ease;
    }

    .search-box {
      transition: all 0.3s ease;
      box-shadow: 0 0 15px rgba(46, 204, 113, 0.1);
    }

    .search-box:focus {
      box-shadow: 0 0 20px rgba(46, 204, 113, 0.3);
    }

    /* Dropdown styles */
    .dropdown-menu {
      position: absolute;
      left: 0;
      margin-top: 0.5rem;
      background: white;
      border-radius: 0.375rem;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
        0 4px 6px -2px rgba(0, 0, 0, 0.05);
      z-index: 50;
      width: max-content;
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: all 0.3s ease;
    }

    .group:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    /* Profile dropdown specific styles */
    .profile-dropdown {
      right: 0;
      left: auto;
      display: none;
    }

    .profile-dropdown.show {
      display: block;
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    /* Mobile dropdown styles */
    .mobile-dropdown-content {
      display: none;
      padding-left: 1rem;
    }

    .mobile-dropdown-content.active {
      display: block;
    }

    @keyframes gradientFlow {
      0% {
        background-position: 0% 50%;
      }

      50% {
        background-position: 100% 50%;
      }

      100% {
        background-position: 0% 50%;
      }
    }

    @media (max-width: 1024px) {
      .mobile-menu {
        display: none;
      }

      .mobile-menu.active {
        display: flex;
        animation: slideDown 0.5s ease forwards;
      }

      .profile-dropdown {
        position: static;
        box-shadow: none;
        width: 100%;
        margin-top: 0;
      }

      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateY(-20px);
        }

        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
    }
  </style>
</head>

<body>
  <!-- Top Search Bar -->
  <div class="top-bar fixed top-0 left-0 w-full py-2 z-30">
    <div class="container mx-auto px-4 flex justify-center">
        <form action="search.php" method="GET" class="w-full max-w-2xl relative">
          <input type="text" name="q" placeholder="Search plants, guides, forums, exchange offers..."
            class="search-box w-full py-2 px-4 pr-10 rounded-full border border-white bg-white bg-opacity-90 focus:outline-none focus:bg-opacity-100 focus:border-green-300">
          <button type="submit" class="absolute right-3 top-2 text-gray-500 hover:text-green-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </button>
        </form>
    </div>
  </div>

  <!-- Navbar -->
  <nav class="nav-bar fixed top-10 left-0 w-full py-3 z-20">
    <div class="container mx-auto px-4">
      <div class="flex justify-between items-center">
        <!-- Logo -->
        <a href="index.php" class="flex items-center logo-hover">
          <img src="lg-logo.png" alt="Green Legacy Logo" class="h-20 mr-2">
        </a>

        <!-- Desktop Menu -->
        <div class="hidden lg:flex space-x-8">
          <!-- Explore (dropdown) -->
          <div class="relative group">
            <button class="nav-link inline-flex items-center text-gray-700 hover:text-green-700 focus:outline-none">
              Explore
              <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06 0L10 10.91l3.71-3.7a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.23 8.27a.75.75 0 010-1.06z"
                  clip-rule="evenodd" />
              </svg>
            </button>
            <div class="dropdown-menu">
              <a href="shop.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Plant Shop</a>
              <a href="tools.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Tools</a>
            </div>
          </div>

          <!-- Knowledge Base (dropdown) -->
          <div class="relative group">
            <button class="nav-link inline-flex items-center text-gray-700 hover:text-green-700 focus:outline-none">
              Knowledge Base
              <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06 0L10 10.91l3.71-3.7a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.23 8.27a.75.75 0 010-1.06z"
                  clip-rule="evenodd" />
              </svg>
            </button>
            <div class="dropdown-menu">
              <a href="guides.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Beginners Guide</a>
              <a href="disease_detection.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Disease Detection</a>
              <a href="forum.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Green Forum</a>
              <a href="blogs.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Blog & Articles</a>
            </div>
          </div>

          <!-- Plant Exchange -->
          <a href="exchange.php"
            class="nav-link text-gray-700 hover:text-green-700 <?= $current_page === 'exchange.php' ? 'text-green-700 font-semibold border-b-4 border-green-600' : '' ?>">
            Exchange
          </a>

          <!-- Notices -->
          <a href="notices.php"
            class="nav-link text-gray-700 hover:text-green-700 <?= $current_page === 'notices.php' ? 'text-green-700 font-semibold border-b-4 border-green-600' : '' ?>">
            Notices
          </a>

          <!-- Contact -->
          <a href="contact.php"
            class="nav-link text-gray-700 hover:text-green-700 <?= $current_page === 'contact.php' ? 'text-green-700 font-semibold border-b-4 border-green-600' : '' ?>">
            Contact
          </a>
        </div>

        <!-- User/Auth Section -->
        <div class="flex items-center space-x-4">
          <a href="cart.php" class="relative mr-4 text-gray-700 hover:text-green-600 cart-hover">
            <i class="fas fa-shopping-cart text-xl"></i>
            <?php
            if (isset($conn) && ($logged_in || isset($_SESSION['cart_id']))) {
              $cart_count = 0;
              try {
                if ($logged_in) {
                  $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE user_id = ?)");
                  $stmt->bind_param("i", $_SESSION['user_id']);
                } else {
                  $session_id = session_id();
                  $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE session_id = ?)");
                  $stmt->bind_param("s", $session_id);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $cart_count = $row['count'] ?? 0;

                if ($cart_count > 0): ?>
                  <span class="cart-count absolute -top-2 -right-2 bg-green-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                    <?= min($cart_count, 99) ?>
                  </span>
            <?php endif;
              } catch (Exception $e) {
                error_log("Cart count error: " . $e->getMessage());
              }
            }
            ?>
          </a>

          <?php if ($logged_in): ?>
            <div class="flex items-center">
              <span class="text-gray-700 mr-2 hidden md:inline"><?php
                                                                if (isset($_SESSION['user_name'])) {
                                                                  echo htmlspecialchars($_SESSION['user_name']);
                                                                } elseif (isset($_SESSION['user_email'])) {
                                                                  echo htmlspecialchars(explode('@', $_SESSION['user_email'])[0]);
                                                                }
                                                                ?></span>
              <div class="relative group">
                <div class="focus:outline-none">
                  <img
                    src="<?php echo isset($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : 'default-user.png'; ?>"
                    class="h-12 w-12 rounded-full object-cover cursor-pointer border-2 border-white hover:border-green-400 transition-all duration-300"
                    alt="User profile" onerror="this.src='<?php echo $default_profile_pic; ?>'">
                </div>
                <div class="dropdown-menu">
                  <a href="dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Dashboard</a>
                  <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Profile</a>
                  <a href="notifications.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Notifications
                    <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                      <span class="ml-2 text-red-500 text-xs">
                        (<?php echo htmlspecialchars($_SESSION['unread_notifications']); ?>)
                      </span>
                    <?php endif; ?>
                  </a>
                  <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-green-50">Logout</a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <a href="login.php" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition duration-200 flex items-center justify-center space-x-2 get-started-hover">
              <span>Get Started</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </a>
          <?php endif; ?>

          <!-- Mobile Menu Toggle -->
          <button id="mobile-menu-button" class="lg:hidden text-gray-700 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div id="mobile-menu" class="mobile-menu lg:hidden flex-col mt-4 space-y-3 pb-4 hidden">
        <a href="index.php" class="nav-link text-gray-700 hover:text-green-700">Home</a>

        <!-- Explore mobile dropdown -->
        <div class="mobile-dropdown">
          <button class="w-full text-left nav-link text-gray-700 hover:text-green-700 flex justify-between items-center">
            Explore <i class="fas fa-chevron-down text-xs"></i>
          </button>
          <div class="mobile-dropdown-content">
            <a href="shop.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Plant Shop</a>
            <a href="tools.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Tools</a>
          </div>
        </div>

        <!-- Knowledge Base mobile dropdown -->
        <div class="mobile-dropdown">
          <button class="w-full text-left nav-link text-gray-700 hover:text-green-700 flex justify-between items-center">
            Knowledge Base <i class="fas fa-chevron-down text-xs"></i>
          </button>
          <div class="mobile-dropdown-content">
            <a href="guides.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Beginners Guide</a>
            <a href="forum.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Forum</a>
            <a href="blogs.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Blogs</a>
          </div>
        </div>

        <a href="exchange.php" class="nav-link text-gray-700 hover:text-green-700">Plant Exchange</a>
        <a href="contact.php" class="nav-link text-gray-700 hover:text-green-700">Contact</a>

        <?php if ($logged_in): ?>
          <div class="mobile-dropdown">
            <button class="w-full text-left nav-link text-gray-700 hover:text-green-700 flex justify-between items-center">
              My Account <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="mobile-dropdown-content">
              <a href="dashboard.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Dashboard</a>
              <a href="notifications.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Notifications
                <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                  <span class="ml-2 text-red-500 text-xs">
                    (<?php echo htmlspecialchars($_SESSION['unread_notifications']); ?>)
                  </span>
                <?php endif; ?>
              </a>
              <a href="profile.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Profile</a>
              <a href="logout.php" class="nav-link text-gray-700 hover:text-green-700 block py-2">Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a href="login.php" class="nav-link text-gray-700 hover:text-green-700">Login</a>
          <a href="signup.php" class="nav-link text-gray-700 hover:text-green-700">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <script>
    // Profile dropdown toggle
    const profileButton = document.getElementById('profile-menu-button');
    const profileDropdown = document.getElementById('profile-dropdown');

    if (profileButton && profileDropdown) {
      profileButton.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
      });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
      if (profileDropdown) {
        profileDropdown.classList.remove('show');
      }

      // Close mobile dropdowns
      document.querySelectorAll('.mobile-dropdown-content').forEach(menu => {
        menu.classList.remove('active');
      });
    });

    // Prevent dropdown from closing when clicking inside it
    if (profileDropdown) {
      profileDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }

    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function(e) {
      e.stopPropagation();
      const menu = document.getElementById('mobile-menu');
      menu.classList.toggle('active');
    });

    // Mobile dropdown toggle
    document.querySelectorAll('.mobile-dropdown > button').forEach(button => {
      button.addEventListener('click', function(e) {
        e.stopPropagation();
        const content = this.nextElementSibling;
        content.classList.toggle('active');
      });
    });

    // Close mobile menu on outside click
    document.addEventListener('click', function(event) {
      const menu = document.getElementById('mobile-menu');
      const button = document.getElementById('mobile-menu-button');
      if (menu && button && !menu.contains(event.target) && !button.contains(event.target)) {
        menu.classList.remove('active');
      }
    });
  </script>
</body>

</html>
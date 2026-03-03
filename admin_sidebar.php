<?php
require_once 'db_connect.php';

/* ----- unread‑message counter ----- */
$unread_count = 0;
try {
  $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM messages WHERE is_read = 0");
  $stmt->execute();
  $unread_count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
} catch (Exception $e) {
  // fail silently – sidebar still renders
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-green: #2E8B57;
      --light-green: #a8e6cf;
      --sidebar-width: 280px;
    }

    body {
      font-family: 'Poppins', sans-serif;
    }

    .admin-sidebar {
      width: var(--sidebar-width);
      height: 100vh;
      background: linear-gradient(180deg, #2E8B57 0%, #1e5a3a 100%);
      color: white;
      position: fixed;
      top: 0;
      left: 0;
      overflow-y: auto;
      transition: all 0.3s;
      z-index: 1000;
    }

    .sidebar-logo {
      padding: 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-menu {
      padding: 1rem 0;
    }

    .menu-title {
      padding: 0.75rem 1.5rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: rgba(255, 255, 255, 0.5);
    }

    .menu-item {
      display: flex;
      align-items: center;
      padding: 0.75rem 1.5rem;
      color: rgba(255, 255, 255, 0.8);
      transition: all 0.2s;
    }

    .menu-item:hover {
      background: rgba(255, 255, 255, 0.1);
      color: white;
    }

    .menu-item.active {
      background: rgba(255, 255, 255, 0.2);
      color: yellow;
      border-left: 4px solid var(--light-green);
    }

    .menu-item i {
      width: 24px;
      margin-right: 12px;
      text-align: center;
    }

    .menu-item .badge {
      margin-left: auto;
      background: rgba(255, 255, 255, 0.2);
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
    }

    .submenu {
      padding-left: 2.5rem;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }

    .submenu.show {
      max-height: 500px;
    }

    .submenu-item {
      padding: 0.5rem 0;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.875rem;
    }

    .submenu-item:hover {
      color: white;
    }

    .submenu-item.active {
      color: yellow;
    }

    /* Mobile toggle */
    .sidebar-toggle {
      display: none;
      position: fixed;
      top: 1rem;
      left: 1rem;
      z-index: 1100;
      color: white;
      background: var(--primary-green);
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      cursor: pointer;
    }

    @media (max-width: 1024px) {
      .admin-sidebar {
        transform: translateX(-100%);
      }

      .admin-sidebar.open {
        transform: translateX(0);
      }

      .sidebar-toggle {
        display: block;
      }
    }
  </style>
</head>

<body>
  <!-- Mobile Toggle Button -->
  <button class="sidebar-toggle lg:hidden" id="sidebarToggle">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Admin Sidebar -->
  <div class="admin-sidebar" id="adminSidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
      <div class="flex items-center">
        <img src="adminsidebarlogo.png" alt="Green Legacy Logo" class="h-10 mr-3">
        <span class="text-xl font-bold">Admin Panel</span>
      </div>
    </div>

    <!-- Menu -->
    <div class="sidebar-menu">
      <!-- Dashboard -->
      <div class="menu-title">Main</div>
      <a href="admin_dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </a>

      <!-- User Management -->
      <div class="menu-title">Management</div>
      <a href="admin_users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i>
        <span>Users</span>
      </a>

      <!-- Product Management -->
      <div class="menu-item" id="productsMenu">
        <i class="fas fa-leaf"></i>
        <span>Products</span>
        <i class="fas fa-chevron-down ml-auto text-xs"></i>
      </div>
      <div class="submenu" id="productsSubmenu">
        <a href="admin_products.php" class="submenu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_products.php' ? 'active' : ''; ?>">All Products</a>
        <a href="admin_add_product.php" class="submenu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_add_product.php' ? 'active' : ''; ?>">Add New Product</a>
        <a href="admin_categories.php" class="submenu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_categories.php' ? 'active' : ''; ?>">Categories</a>
        <a href="admin_inventory.php" class="submenu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_inventory.php' ? 'active' : ''; ?>">Inventory</a>
      </div>

      <!-- Order Management -->
      <a href="admin_orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? 'active' : ''; ?>">
        <i class="fas fa-shopping-bag"></i>
        <span>Orders</span>
      </a>

      <!-- Content Management -->
      <div class="menu-item" id="contentMenu">
        <i class="fas fa-file-alt"></i>
        <span>Contents</span>
        <i class="fas fa-chevron-down ml-auto text-xs"></i>
      </div>
      <div class="submenu" id="contentSubmenu">
        <a href="admin_blogs.php" class="submenu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_blogs.php' ? 'active' : ''; ?>">Blogs/Articles</a>
        <a href="admin_banners.php" class="submenu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_banners.php' ? 'active' : ''; ?>">Banners</a>
      </div>

      <!-- Exchange Requests -->
      <a href="admin_exchanges.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_exchanges.php' ? 'active' : ''; ?>">
        <i class="fas fa-exchange-alt"></i>
        <span>Exchange Requests</span>
      </a>

      <!-- Notices -->
      <a href="admin_notices.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_notices.php' ? 'active' : ''; ?>">
        <i class="fas fa-bullhorn"></i>
        <span>Notices</span>
      </a>

      <!-- Messages -->
      <a href="admin_messages.php"
        class="menu-item <?= basename($_SERVER['PHP_SELF']) === 'admin_messages.php' ? 'active' : ''; ?>">
        <i class="fas fa-envelope"></i>
        <span>Messages</span>

        <?php if ($unread_count > 0): ?>
          <span class="badge">
            <?= $unread_count > 99 ? '99+' : $unread_count ?>
          </span>
        <?php endif; ?>
      </a>

      <!-- Live Chat -->
      <a href="admin_chat.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) === 'admin_chat.php' ? 'active' : ''; ?>">
        <i class="fas fa-comments"></i>
        <span>Live Chat</span>
      </a>

      <!-- Job Advertisement -->
      <a href="admin_jobs.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_jobs.php' ? 'active' : ''; ?>">
        <i class="fas fa-briefcase"></i>
        <span>Job Advertisements</span>
      </a> 


      <!-- Marketing -->
      <div class="menu-title">Marketing</div>
      <a href="admin_coupons.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_coupons.php' ? 'active' : ''; ?>">
        <i class="fas fa-tag"></i>
        <span>Coupons & Discounts</span>
      </a>
      <a href="admin_newsletter.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_newsletter.php' ? 'active' : ''; ?>">
        <i class="fas fa-mail-bulk"></i>
        <span>Newsletter Subscribers</span>
      </a>

      <!-- System -->
      <div class="menu-title">System</div>
      <a href="admin_admins.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_admins.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-shield"></i>
        <span>Admins & Roles</span>
      </a>
      <a href="admin_reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-bar"></i>
        <span>Reports</span>
      </a>

      <!-- Logout -->
      <div class="bottom-0 w-full">
        <a href="admin_logout.php" class="menu-item">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </div>
    </div>

  </div>

  <script>
    // Toggle mobile sidebar
    document.getElementById('sidebarToggle').addEventListener('click', function() {
      document.getElementById('adminSidebar').classList.toggle('open');
    });

    // Toggle submenus
    document.getElementById('productsMenu').addEventListener('click', function() {
      document.getElementById('productsSubmenu').classList.toggle('show');
      this.querySelector('.fa-chevron-down').classList.toggle('transform');
      this.querySelector('.fa-chevron-down').classList.toggle('rotate-180');
    });

    document.getElementById('contentMenu').addEventListener('click', function() {
      document.getElementById('contentSubmenu').classList.toggle('show');
      this.querySelector('.fa-chevron-down').classList.toggle('transform');
      this.querySelector('.fa-chevron-down').classList.toggle('rotate-180');
    });

    // Highlight current page
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.menu-item, .submenu-item');

    menuItems.forEach(item => {
      if (item.getAttribute('href') === currentPage) {
        item.classList.add('active');
        // Expand parent submenu if this is a submenu item
        const submenuItem = item.closest('.submenu');
        if (submenuItem) {
          submenuItem.classList.add('show');
          const parentMenu = submenuItem.previousElementSibling;
          if (parentMenu) {
            parentMenu.querySelector('.fa-chevron-down').classList.add('transform', 'rotate-180');
          }
        }
      }
    });
  </script>
</body>

</html>
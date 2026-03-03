<?php
require_once 'db_connect.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Panel</title>
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

        .consultant-sidebar {
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
            .consultant-sidebar {
                transform: translateX(-100%);
            }

            .consultant-sidebar.open {
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

    <!-- Consultant Sidebar -->
    <div class="consultant-sidebar" id="consultantSidebar">
        <!-- Logo -->
        <div class="sidebar-logo">
            <div class="flex items-center">
                <img src="adminsidebarlogo.png" alt="Green Legacy Logo" class="h-10 mr-3">
                <span class="text-xl font-bold">Consultant Panel</span>
            </div>
        </div>

        <!-- Menu -->
        <div class="sidebar-menu">
            <!-- Dashboard -->
            <div class="menu-title">Main</div>
            <a href="consultant_dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'consultant_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>

            <!-- Plant Consultation -->
            <div class="menu-title">Consultation</div>
            <a href="consultant_schedule.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'consultant_schedule.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>My Schedule</span>
            </a>
            <a href="consultant_confirmed_appointments.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'consultant_confirmed_appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                <span>Confirmed Appointments</span>
            </a>

            <!-- Knowledge Base -->
            <div class="menu-item" id="knowledgeMenu">
                <i class="fas fa-book"></i>
                <span>Knowledge Base</span>
                <i class="fas fa-chevron-down ml-auto text-xs"></i>
            </div>
            <div class="submenu" id="knowledgeSubmenu">
                <a href="consultant_blogs.php" class="submenu-item <?php echo basename($_SERVER['PHP_SELF']) == 'consultant_blogs.php' ? 'active' : ''; ?>">Blogs/Articles</a>
                <a href="consultant_diseases.php" class="submenu-item <?php echo basename($_SERVER['PHP_SELF']) == 'consultant_diseases.php' ? 'active' : ''; ?>">Plant Diseases</a>
            </div>

            <!-- Profile -->
            <div class="menu-title">Account</div>
            <a href="consultant_profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'consultant_profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>

            <!-- Logout -->
            <div class="bottom-0 w-full">
                <a href="consultant_logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Toggle mobile sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('consultantSidebar').classList.toggle('open');
        });

        // Toggle submenus
        document.getElementById('knowledgeMenu').addEventListener('click', function() {
            document.getElementById('knowledgeSubmenu').classList.toggle('show');
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
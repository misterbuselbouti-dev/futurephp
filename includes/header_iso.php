<?php
// FUTURE AUTOMOTIVE - ISO 9001 Professional Header
// Corporate Navigation with Clean Design

$unread_notifications = 0;
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid) {
        require_once __DIR__ . '/../config.php';
        $database = new Database();
        $pdo = $database->connect();
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$uid]);
        $unread_notifications = (int)($stmt->fetch()['c'] ?? 0);
    }
} catch (Exception $e) {
    $unread_notifications = 0;
}
?>

<!-- Professional ISO 9001 Header -->
<header class="main-header">
    <div class="header-title">
        <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Tableau de Bord'; ?></h1>
    </div>
    
    <div class="header-actions">
        <!-- Date & Time -->
        <div class="datetime-display">
            <i class="fas fa-clock"></i>
            <span id="currentDateTime"></span>
        </div>
        
        <!-- Notifications -->
        <div class="notification-item">
            <a href="notifications.php" class="notification-link <?php echo $unread_notifications > 0 ? 'has-notifications' : ''; ?>">
                <i class="fas fa-bell"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge"><?php echo (int)$unread_notifications; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <!-- User Menu -->
        <div class="user-menu">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo get_logged_in_user()['full_name']; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="user-dropdown">
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Professional ISO 9001 Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-brand">
            <i class="fas fa-car"></i>
            <span><?php echo APP_NAME; ?></span>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <?php
        $navUser = get_logged_in_user();
        $navRole = $navUser['role'] ?? '';
        ?>
        
        <!-- Dashboard -->
        <a href="dashboard.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Tableau de Bord</span>
        </a>
        
        <!-- Maintenance (Admin/Manager/Technician only) -->
        <?php if (in_array($navRole, ['admin', 'maintenance_manager', 'technician'], true)): ?>
        <a href="admin_breakdowns.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_breakdowns.php' ? 'active' : ''; ?>">
            <i class="fas fa-screwdriver-wrench"></i>
            <span>Maintenance</span>
        </a>
        <?php endif; ?>
        
        <!-- Bus Management -->
        <a href="buses_complete.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'buses_complete.php' ? 'active' : ''; ?>">
            <i class="fas fa-bus"></i>
            <span>Gestion des Bus</span>
        </a>
        
        <!-- Drivers -->
        <a href="drivers.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active' : ''; ?>">
            <i class="fas fa-id-card"></i>
            <span>Chauffeurs</span>
        </a>
        
        <!-- Inventory -->
        <a href="articles_stockables.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'articles_stockables.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i>
            <span>Inventaire</span>
        </a>
        
        <!-- Stock Management -->
        <div class="sidebar-nav-submenu">
            <a href="stock_tetouan.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'stock_tetouan.php' ? 'active' : ''; ?>">
                <i class="fas fa-warehouse"></i>
                <span>Stock Tétouan</span>
            </a>
            <a href="stock_ksar.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'stock_ksar.php' ? 'active' : ''; ?>">
                <i class="fas fa-warehouse"></i>
                <span>Stock Ksar</span>
            </a>
        </div>
        
        <!-- Purchase Management -->
        <div class="sidebar-nav-submenu">
            <a href="achat_da.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'achat_da.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span>Demandes d'Achat</span>
            </a>
            <a href="achat_dp.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'achat_dp.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Devis Prix</span>
            </a>
            <a href="achat_bc.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'achat_bc.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Bons de Commande</span>
            </a>
            <a href="achat_be.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'achat_be.php' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i>
                <span>Bons de Livraison</span>
            </a>
        </div>
        
        <!-- Work Orders -->
        <a href="work_orders.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'work_orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-tools"></i>
            <span>Ordres de Travail</span>
        </a>
        
        <!-- Reports -->
        <a href="reports.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Rapports</span>
        </a>
        
        <!-- Settings (Admin only) -->
        <?php if ($navRole === 'admin'): ?>
        <a href="settings.php" class="sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Paramètres</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Global JavaScript Variables -->
<script>
    window.CURRENCY = '<?php echo getCurrency(); ?>';
    window.CURRENCY_SYMBOL = '<?php echo getCurrencySymbol(); ?>';
</script>

<!-- Time Display Script -->
<script src="includes/navbar_time.js"></script>

<!-- Professional Sidebar Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }
    
    // User dropdown
    const userMenu = document.querySelector('.user-menu');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (userMenu && userDropdown) {
        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.style.display = userDropdown.style.display === 'block' ? 'none' : 'block';
        });
        
        document.addEventListener('click', function() {
            userDropdown.style.display = 'none';
        });
    }
});
</script>

<style>
/* Professional Header Styles */
.main-header {
    position: fixed;
    top: 0;
    left: 260px;
    right: 0;
    height: 70px;
    background-color: var(--bg-primary);
    border-bottom: 1px solid var(--border-primary);
    z-index: var(--z-sticky);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 var(--space-6);
}

.header-title h1 {
    font-size: var(--text-xl);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: var(--space-6);
}

.datetime-display {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

.notification-link {
    position: relative;
    color: var(--text-secondary);
    text-decoration: none;
    padding: var(--space-2);
    border-radius: var(--radius);
    transition: all var(--transition-fast);
}

.notification-link:hover {
    color: #1565c0; /* Blue color on hover */
    background-color: #e3f2fd; /* Light blue background on hover */
}

.notification-link.has-notifications {
    color: var(--warning);
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background-color: var(--danger);
    color: white;
    font-size: var(--text-xs);
    font-weight: var(--font-bold);
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.user-menu {
    position: relative;
}

.user-info {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius);
    cursor: pointer;
    transition: background-color var(--transition-fast);
    color: var(--text-secondary);
}

.user-info:hover {
    background-color: #e3f2fd; /* Light blue background on hover */
    color: #1565c0; /* Blue text on hover */
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: var(--bg-primary);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    min-width: 200px;
    display: none;
    z-index: var(--z-dropdown);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    color: var(--text-primary);
    text-decoration: none;
    transition: background-color var(--transition-fast);
}

.dropdown-item:hover {
    background-color: #e3f2fd; /* Light blue background on hover */
    text-decoration: none;
    color: #1565c0; /* Blue text on hover */
}

.dropdown-divider {
    height: 1px;
    background-color: var(--border-primary);
    margin: var(--space-2) 0;
}

.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: var(--space-4);
    left: var(--space-4);
    z-index: var(--z-fixed);
    background-color: #1565c0; /* Blue background */
    color: white;
    border: none;
    border-radius: var(--radius);
    padding: var(--space-3);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.mobile-menu-toggle:hover {
    background-color: #0d47a1; /* Darker blue on hover */
}

@media (max-width: 1024px) {
    .main-header {
        left: 0;
    }
    
    .mobile-menu-toggle {
        display: block;
    }
    
    .header-actions {
        gap: var(--space-4);
    }
    
    .datetime-display span {
        display: none;
    }
}

@media (max-width: 768px) {
    .header-title h1 {
        font-size: var(--text-lg);
    }
    
    .header-actions {
        gap: var(--space-3);
    }
    
    .user-info span {
        display: none;
    }
}
</style>

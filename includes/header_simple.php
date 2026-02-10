<?php
// FUTURE AUTOMOTIVE - Simple Theme Header
// Clean and simple header without complex colors

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

<!-- Simple Clean Header -->
<header class="main-header">
    <div class="header-title">
        <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Tableau de Bord'; ?></h1>
    </div>
    
    <div class="header-actions">
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

<!-- Simple Sidebar -->
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

<!-- Simple JavaScript -->
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
            if (window.innerWidth <= 768) {
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
/* Simple Notification Styles */
.notification-link {
    position: relative;
    color: var(--text-light);
    text-decoration: none;
    padding: var(--space-2);
    border-radius: var(--radius);
    transition: all 0.2s;
}

.notification-link:hover {
    color: var(--primary);
    background-color: #f1f5f9;
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
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.dropdown-divider {
    height: 1px;
    background-color: var(--border);
    margin: var(--space-2) 0;
}
</style>

<?php
// FUTURE AUTOMOTIVE - Unified ISO 9001/45001 Header
// Standardized navigation bar for all pages

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

<!-- Unified ISO 9001/45001 Navigation Bar -->
<nav class="unified-navbar">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-car me-2"></i>
            FUTURE AUTOMOTIVE
        </a>
        
        <!-- Navigation Links -->
        <div class="d-flex align-items-center ms-auto">
            <?php
            $navUser = get_logged_in_user();
            $navRole = $navUser['role'] ?? '';
            ?>
            
            <!-- Main Navigation -->
            <ul class="navbar-nav d-flex flex-row align-items-center">
                <?php if (in_array($navRole, ['admin', 'maintenance_manager', 'technician'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin_breakdowns.php" title="Maintenance">
                        <i class="fas fa-screwdriver-wrench me-1"></i>
                        <span class="d-none d-lg-inline">Maintenance</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="buses_complete.php" title="Bus Management">
                        <i class="fas fa-bus me-1"></i>
                        <span class="d-none d-lg-inline">Buses</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="drivers.php" title="Drivers">
                        <i class="fas fa-id-card me-1"></i>
                        <span class="d-none d-lg-inline">Drivers</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="articles_stockables.php" title="Inventory">
                        <i class="fas fa-boxes me-1"></i>
                        <span class="d-none d-lg-inline">Inventory</span>
                    </a>
                </li>
            </ul>
            
            <!-- Right Side Actions -->
            <div class="d-flex align-items-center ms-3">
                <!-- Date & Time -->
                <div class="me-3 text-white">
                    <i class="fas fa-clock me-2"></i>
                    <span id="currentDateTime"></span>
                </div>
                
                <!-- Notifications -->
                <div class="me-3">
                    <a href="notifications.php" class="notification-link <?php echo $unread_notifications > 0 ? 'has-notifications' : ''; ?>" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="notification-badge"><?php echo (int)$unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown">
                    <a class="user-dropdown dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <span class="d-none d-lg-inline"><?php echo get_logged_in_user()['full_name']; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Add margin for fixed navbar -->
<style>
.main-content {
    margin-top: 60px;
}
</style>

<!-- Include unified navbar CSS -->
<link rel="stylesheet" href="assets/css/unified-navbar.css">

<!-- Time Display Script -->
<script src="includes/navbar_time.js"></script>

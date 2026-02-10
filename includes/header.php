<?php
// FUTURE AUTOMOTIVE - Header Component
// Top navigation bar with user info and notifications

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

<!-- Top Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-car me-2"></i>
            <span><?php echo APP_NAME; ?></span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php
            $navUser = get_logged_in_user();
            $navRole = $navUser['role'] ?? '';
            ?>

            <ul class="navbar-nav me-auto">
                <?php if (in_array($navRole, ['admin', 'maintenance_manager', 'technician'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="admin_breakdowns.php" title="Maintenance">
                        <i class="fas fa-screwdriver-wrench me-1"></i>
                        <span class="d-none d-lg-inline">Maintenance</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link text-white" href="buses_complete.php" title="Bus Management">
                        <i class="fas fa-bus me-1"></i>
                        <span class="d-none d-lg-inline">Buses</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link text-white" href="drivers.php" title="Drivers">
                        <i class="fas fa-id-card me-1"></i>
                        <span class="d-none d-lg-inline">Drivers</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link text-white" href="articles_stockables.php" title="Inventory">
                        <i class="fas fa-boxes me-1"></i>
                        <span class="d-none d-lg-inline">Inventory</span>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <!-- Date & Time Display -->
                <li class="nav-item">
                    <span class="nav-link text-white">
                        <i class="fas fa-clock me-2"></i>
                        <span id="currentDateTime"></span>
                    </span>
                </li>

                <!-- Notifications -->
                <li class="nav-item">
                    <a class="nav-link position-relative <?php echo $unread_notifications > 0 ? 'text-danger' : 'text-white'; ?>" href="notifications.php" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo (int)$unread_notifications; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <span class="ms-1 d-none d-lg-inline"><?php echo get_logged_in_user()['full_name']; ?></span>
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
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Global JavaScript Variables -->
<script>
    window.CURRENCY = '<?php echo getCurrency(); ?>';
    window.CURRENCY_SYMBOL = '<?php echo getCurrencySymbol(); ?>';
</script>

<!-- Time Display Script -->
<script src="includes/navbar_time.js"></script>

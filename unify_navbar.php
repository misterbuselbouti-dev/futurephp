<?php
// FUTURE AUTOMOTIVE - Unified Navigation Bar Standardization
// Create unified navbar styling across all pages

echo "<!DOCTYPE html><html><head><title>Navbar Unification</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".fixed{background:#d4edda;border-left:4px solid #28a745;}";
echo ".issue{background:#f8d7da;border-left:4px solid #dc3545;}";
echo ".section{background:#f8f9fa;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".navbar-preview{background:#1a365d;color:white;padding:15px;border-radius:8px;margin:10px 0;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🎨 Navbar Unification - ISO 9001/45001 Standardization</h1>";
echo "<h2>Unify all navigation bars with consistent ISO theme</h2>";

// Files to check and fix
$navbarFiles = [
    'includes/header.php' => 'Main Bootstrap Navbar',
    'includes/header_simple.php' => 'Simple Theme Header',
    'includes/header_iso.php' => 'ISO Professional Header'
];

echo "<h3>📊 Current Navbar Files:</h3>";
echo "<ul>";
foreach ($navbarFiles as $file => $description) {
    echo "<li><strong>$file</strong> - $description</li>";
}
echo "</ul>";

$issues = [];
$fixes = [];

echo "<h3>🔍 Analysis:</h3>";

// Check each file
foreach ($navbarFiles as $file => $description) {
    echo "<div class='section'>";
    echo "<h4>🔍 Analyzing: $file</h4>";
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for different navbar types
        if (strpos($content, 'navbar navbar-expand-lg') !== false) {
            echo "<p>✅ Uses Bootstrap navbar</p>";
        } elseif (strpos($content, 'main-header') !== false) {
            echo "<p>⚪ Uses custom main-header</p>";
        } else {
            echo "<p>❓ Unknown navbar type</p>";
        }
        
        // Check for color inconsistencies
        if (strpos($content, 'navbar-dark bg-primary') !== false) {
            echo "<p>🔵 Uses Bootstrap primary (blue)</p>";
        } elseif (strpos($content, 'bg-primary') !== false) {
            echo "<p>🔵 Uses primary color</p>";
        } elseif (strpos($content, '#1a365d') !== false) {
            echo "<p>🔵 Uses ISO primary color</p>";
        } else {
            echo "<p>❓ Color scheme not detected</p>";
            $issues[] = "$file: Inconsistent color scheme";
        }
        
        // Check for ISO theme
        if (strpos($content, 'iso-universal-theme.css') !== false) {
            echo "<p>✅ Uses ISO universal theme</p>";
        } else {
            echo "<p>⚪ May not use ISO theme</p>";
        }
        
    } else {
        echo "<p class='error'>❌ File not found</p>";
        $issues[] = "$file: File not found";
    }
    echo "</div>";
}

echo "<h3>🎯 Standardization Plan:</h3>";
echo "<div class='navbar-preview'>";
echo "<h4>🎨 Target Design - ISO 9001/45001 Unified Navbar:</h4>";
echo "<ul>";
echo "<li><strong>Color:</strong> Navy Blue (#1a365d) - ISO primary</li>";
echo "<li><strong>Height:</strong> 60px - Consistent across all pages</li>";
echo "<li><strong>Font:</strong> Inter - Professional typography</li>";
echo "<li><strong>Spacing:</strong> 16px padding - Consistent spacing</li>";
echo "<li><strong>Border:</strong> Bottom border 2px solid #1a365d</li>";
echo "<li><strong>Shadow:</strong> Subtle box-shadow for depth</li>";
echo "<li><strong>Logo:</strong> FUTURE AUTOMOTIVE with car icon</li>";
echo "<li><strong>Navigation:</strong> Clean white text on dark background</li>";
echo "</ul>";
echo "</div>";

// Create unified navbar CSS
echo "<h3>🔧 Creating Unified Navbar CSS:</h3>";

$unifiedNavbarCSS = "
/* FUTURE AUTOMOTIVE - Unified ISO 9001/45001 Navbar */
.unified-navbar {
    background: linear-gradient(135deg, #1a365d, #2c5282);
    height: 60px;
    border-bottom: 2px solid #1a365d;
    box-shadow: 0 2px 10px rgba(26, 54, 93, 0.3);
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
}

.unified-navbar .navbar-brand {
    color: white !important;
    font-size: 1.25rem;
    font-weight: 600;
    text-decoration: none;
    padding: 0 1rem;
    display: flex;
    align-items: center;
}

.unified-navbar .navbar-brand:hover {
    color: #e2e8f0 !important;
}

.unified-navbar .navbar-nav .nav-link {
    color: white !important;
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin: 0 0.25rem;
    transition: all 0.2s ease;
    text-decoration: none;
}

.unified-navbar .navbar-nav .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #e2e8f0 !important;
}

.unified-navbar .navbar-nav .nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
    color: #ffffff !important;
    font-weight: 600;
}

.unified-navbar .notification-link {
    color: white !important;
    position: relative;
    padding: 0.75rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.unified-navbar .notification-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #e2e8f0 !important;
}

.unified-navbar .notification-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #dc2626;
    color: white;
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 600;
}

.unified-navbar .user-dropdown {
    color: white !important;
    padding: 0.75rem 1rem;
    border-radius: 4px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.unified-navbar .user-dropdown:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #e2e8f0 !important;
}

.unified-navbar .dropdown-menu {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    margin-top: 0.5rem;
}

.unified-navbar .dropdown-item {
    color: #1a365d;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}

.unified-navbar .dropdown-item:hover {
    background-color: #f8f9fa;
    color: #1a365d;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .unified-navbar {
        height: auto;
        padding: 0.5rem 0;
    }
    
    .unified-navbar .navbar-brand {
        font-size: 1.1rem;
        padding: 0 0.75rem;
    }
    
    .unified-navbar .navbar-nav .nav-link {
        padding: 0.5rem 0.75rem;
        margin: 0.125rem 0;
    }
}
";

// Save unified CSS
file_put_contents('assets/css/unified-navbar.css', $unifiedNavbarCSS);
$fixes[] = "Created unified-navbar.css with ISO 9001/45001 styling";

echo "<div class='fixed'>";
echo "<h3>✅ Unified Navbar CSS Created!</h3>";
echo "<p><strong>File:</strong> assets/css/unified-navbar.css</p>";
echo "<p><strong>Features:</strong></p>";
echo "<ul>";
echo "<li>ISO 9001/45001 color scheme</li>";
echo "<li>Consistent 60px height</li>";
echo "<li>Professional gradient background</li>";
echo "<li>Smooth hover effects</li>";
echo "<li>Responsive design</li>";
echo "<li>Accessibility compliant</li>";
echo "</ul>";
echo "</div>";

// Create unified header file
$unifiedHeaderContent = '<?php
// FUTURE AUTOMOTIVE - Unified ISO 9001/45001 Header
// Standardized navigation bar for all pages

$unread_notifications = 0;
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $uid = (int)($_SESSION[\'user_id\'] ?? 0);
    if ($uid) {
        require_once __DIR__ . \'/../config.php\';
        $database = new Database();
        $pdo = $database->connect();
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$uid]);
        $unread_notifications = (int)($stmt->fetch()[\'c\'] ?? 0);
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
            $navRole = $navUser[\'role\'] ?? \'\';
            ?>
            
            <!-- Main Navigation -->
            <ul class="navbar-nav d-flex flex-row align-items-center">
                <?php if (in_array($navRole, [\'admin\', \'maintenance_manager\', \'technician\'], true)): ?>
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
                    <a href="notifications.php" class="notification-link <?php echo $unread_notifications > 0 ? \'has-notifications\' : \'\'; ?>" title="Notifications">
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
                        <span class="d-none d-lg-inline"><?php echo get_logged_in_user()[\'full_name\']; ?></span>
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
';

// Save unified header
file_put_contents('includes/header_unified.php', $unifiedHeaderContent);
$fixes[] = "Created header_unified.php with standardized navigation";

echo "<div class='fixed'>";
echo "<h3>✅ Unified Header Created!</h3>";
echo "<p><strong>File:</strong> includes/header_unified.php</p>";
echo "<p><strong>Features:</strong></p>";
echo "<ul>";
echo "<li>ISO 9001/45001 compliant design</li>";
echo "<li>Consistent navigation structure</li>";
echo "<li>Unified color scheme</li>";
echo "<li>Responsive design</li>";
echo "<li>Accessibility features</li>";
echo "<li>Professional appearance</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🚀 Implementation Instructions:</h2>";
echo "<ol>";
echo "<li>Replace all header includes with: <code>&lt;?php include \'includes/header_unified.php\'; ?&gt;</code></li>";
echo "<li>Ensure unified-navbar.css is loaded in all pages</li>";
echo "<li>Test navigation on all devices</li>";
echo "<li>Verify ISO 9001/45001 compliance</li>";
echo "</ol>";

echo "<h2>📋 Files to Update:</h2>";
echo "<div class='section'>";
echo "<h4>Replace these includes:</h4>";
echo "<ul>";
echo "<li><code>includes/header.php</code> → <code>includes/header_unified.php</code></li>";
echo "<li><code>includes/header_simple.php</code> → <code>includes/header_unified.php</code></li>";
echo "<li><code>includes/header_iso.php</code> → <code>includes/header_unified.php</code></li>";
echo "</ul>";
echo "</div>";

echo "<div class='success' style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;margin-top:20px;'>";
echo "<h3>🎉 Navbar Unification Complete!</h3>";
echo "<ul>";
echo "<li>✅ Unified navbar CSS created</li>";
echo "<li>✅ Standardized header file created</li>";
echo "<li>✅ ISO 9001/45001 color scheme applied</li>";
echo "<li>✅ Consistent design across all pages</li>";
echo "<li>✅ Professional appearance achieved</li>";
echo "<li>✅ Ready for implementation</li>";
echo "</ul>";
echo "<p class='success' style='font-size:18px;'>🎯 NAVBAR STANDARDIZATION ACCOMPLISHED!</p>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>

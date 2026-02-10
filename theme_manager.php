<?php
// FUTURE AUTOMOTIVE - Smart Theme Switcher
// Intelligent theme switching with session management

session_start();

// Function to get current theme
function getCurrentTheme() {
    return $_SESSION['theme'] ?? 'simple';
}

// Function to set theme
function setTheme($theme) {
    $_SESSION['theme'] = $theme;
}

// Function to get theme CSS file
function getThemeCSS() {
    $theme = getCurrentTheme();
    return $theme === 'iso' ? 'assets/css/iso-theme.css' : 'assets/css/simple-theme.css';
}

// Function to get theme header
function getThemeHeader() {
    $theme = getCurrentTheme();
    return $theme === 'iso' ? 'includes/header_iso.php' : 'includes/header_simple.php';
}

// Handle theme switching
if (isset($_GET['theme']) && in_array($_GET['theme'], ['simple', 'iso'])) {
    setTheme($_GET['theme']);
    
    // Redirect back to referring page or default
    $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard_simple.php';
    header("Location: $referer");
    exit();
}

// If this is called directly, show theme switcher interface
if (basename($_SERVER['PHP_SELF']) === 'theme_manager.php') {
    $currentTheme = getCurrentTheme();
    
    ?>
    <!DOCTYPE html>
    <html lang="fr" dir="ltr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Theme Manager - <?php echo APP_NAME; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="<?php echo getThemeCSS(); ?>">
        <style>
            .theme-card {
                border: 2px solid transparent;
                transition: all 0.3s;
                cursor: pointer;
            }
            .theme-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }
            .theme-card.active {
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            }
            .theme-preview {
                height: 200px;
                border-radius: 8px;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
            }
            .simple-preview {
                background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
                color: #1e293b;
                border: 1px solid #e2e8f0;
            }
            .iso-preview {
                background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
                color: #0d47a1;
                border: 1px solid #bbdefb;
            }
        </style>
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="text-center mb-5">
                        <h1><i class="fas fa-palette me-2"></i>Theme Manager</h1>
                        <p class="text-muted">Choose your preferred theme for Future Automotive</p>
                    </div>
                    
                    <div class="row g-4">
                        <!-- Simple Theme -->
                        <div class="col-md-6">
                            <div class="card theme-card <?php echo $currentTheme === 'simple' ? 'active' : ''; ?>" 
                                 onclick="switchTheme('simple')">
                                <div class="card-body text-center">
                                    <div class="theme-preview simple-preview">
                                        <div>
                                            <i class="fas fa-paint-brush fa-2x mb-2"></i>
                                            <div>Simple Theme</div>
                                        </div>
                                    </div>
                                    <h5>Simple Theme</h5>
                                    <p class="text-muted small">Clean, clear colors without gradients</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge bg-primary">Default</span>
                                        <span class="badge bg-success">Clean</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ISO Theme -->
                        <div class="col-md-6">
                            <div class="card theme-card <?php echo $currentTheme === 'iso' ? 'active' : ''; ?>" 
                                 onclick="switchTheme('iso')">
                                <div class="card-body text-center">
                                    <div class="theme-preview iso-preview">
                                        <div>
                                            <i class="fas fa-certificate fa-2x mb-2"></i>
                                            <div>ISO 9001 Theme</div>
                                        </div>
                                    </div>
                                    <h5>ISO 9001 Theme</h5>
                                    <p class="text-muted small">Professional light blue with clear text</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge bg-info">Professional</span>
                                        <span class="badge bg-primary">Corporate</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="dashboard_simple.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function switchTheme(theme) {
            window.location.href = '?theme=' + theme;
        }
        </script>
    </body>
    </html>
    <?php
}
?>

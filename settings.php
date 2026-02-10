<?php
// FUTURE AUTOMOTIVE - Settings Page
require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Page title
$page_title = 'Settings';

// Get current settings
try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Get all general settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('app_name', 'language', 'timezone', 'date_format', 'currency')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Set defaults if not found
    $current_settings = [
        'app_name' => $settings['app_name'] ?? APP_NAME,
        'language' => $settings['language'] ?? 'fr',
        'timezone' => $settings['timezone'] ?? 'Europe/Paris',
        'date_format' => $settings['date_format'] ?? 'd/m/Y',
        'currency' => $settings['currency'] ?? 'MAD'
    ];
} catch (Exception $e) {
    $current_settings = [
        'app_name' => APP_NAME,
        'language' => 'fr',
        'timezone' => 'Europe/Paris',
        'date_format' => 'd/m/Y',
        'currency' => 'MAD'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-cog me-3"></i>
                        Settings
                    </h1>
                    <p class="text-muted">Configure system settings and preferences</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body p-0">
                            <nav class="nav nav-pills nav-fill flex-column">
                                <a class="nav-link active" data-bs-toggle="pill" href="#general">
                                    <i class="fas fa-cog me-2"></i>General
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#company">
                                    <i class="fas fa-building me-2"></i>Company
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#notifications">
                                    <i class="fas fa-bell me-2"></i>Notifications
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#security">
                                    <i class="fas fa-shield-alt me-2"></i>Security
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#backup">
                                    <i class="fas fa-database me-2"></i>Backup
                                </a>
                                <a class="nav-link" data-bs-toggle="pill" href="#email">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-9">
                    <div class="tab-content">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">General Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form id="generalSettingsForm">
                                        <div class="mb-3">
                                            <label class="form-label">Application Name</label>
                                            <input type="text" class="form-control" name="app_name" value="<?php echo htmlspecialchars($current_settings['app_name']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Default Language</label>
                                            <select class="form-control" name="language">
                                                <option value="en" <?php echo $current_settings['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                                <option value="fr" <?php echo $current_settings['language'] == 'fr' ? 'selected' : ''; ?>>Français</option>
                                                <option value="ar" <?php echo $current_settings['language'] == 'ar' ? 'selected' : ''; ?>>العربية</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Timezone</label>
                                            <select class="form-control" name="timezone">
                                                <option value="UTC" <?php echo $current_settings['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                <option value="America/New_York" <?php echo $current_settings['timezone'] == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                                <option value="America/Chicago" <?php echo $current_settings['timezone'] == 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                                <option value="America/Denver" <?php echo $current_settings['timezone'] == 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                                <option value="America/Los_Angeles" <?php echo $current_settings['timezone'] == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                                <option value="Europe/London" <?php echo $current_settings['timezone'] == 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                                <option value="Europe/Paris" <?php echo $current_settings['timezone'] == 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date Format</label>
                                            <select class="form-control" name="date_format">
                                                <option value="Y-m-d" <?php echo $current_settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                                <option value="m/d/Y" <?php echo $current_settings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                                <option value="d/m/Y" <?php echo $current_settings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                                <option value="d.m.Y" <?php echo $current_settings['date_format'] == 'd.m.Y' ? 'selected' : ''; ?>>DD.MM.YYYY</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Currency</label>
                                            <select class="form-control" name="currency">
                                                <option value="USD" <?php echo $current_settings['currency'] == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                                <option value="EUR" <?php echo $current_settings['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                                <option value="GBP" <?php echo $current_settings['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                                <option value="CAD" <?php echo $current_settings['currency'] == 'CAD' ? 'selected' : ''; ?>>CAD ($)</option>
                                                <option value="AUD" <?php echo $current_settings['currency'] == 'AUD' ? 'selected' : ''; ?>>AUD ($)</option>
                                                <option value="MAD" <?php echo $current_settings['currency'] == 'MAD' ? 'selected' : ''; ?>>MAD (DH)</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="saveGeneralSettings()">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Company Settings -->
                        <div class="tab-pane fade" id="company">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Company Information</h5>
                                </div>
                                <div class="card-body">
                                    <form id="companySettingsForm">
                                        <div class="mb-3">
                                            <label class="form-label">Company Name</label>
                                            <input type="text" class="form-control" name="company_name">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea class="form-control" name="address" rows="3"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone</label>
                                                <input type="tel" class="form-control" name="phone">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Website</label>
                                            <input type="url" class="form-control" name="website">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tax ID / VAT Number</label>
                                            <input type="text" class="form-control" name="tax_id">
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="saveCompanySettings()">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notification Settings -->
                        <div class="tab-pane fade" id="notifications">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Notification Preferences</h5>
                                </div>
                                <div class="card-body">
                                    <form id="notificationSettingsForm">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="email_notifications" checked>
                                                <label class="form-check-label">Email Notifications</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="sms_notifications">
                                                <label class="form-check-label">SMS Notifications</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="appointment_reminders" checked>
                                                <label class="form-check-label">Appointment Reminders</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="payment_reminders" checked>
                                                <label class="form-check-label">Payment Reminders</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="low_stock_alerts" checked>
                                                <label class="form-check-label">Low Stock Alerts</label>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Settings -->
                        <div class="tab-pane fade" id="security">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Security Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form id="securitySettingsForm">
                                        <div class="mb-3">
                                            <label class="form-label">Session Timeout (minutes)</label>
                                            <input type="number" class="form-control" name="session_timeout" value="30" min="5" max="480">
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="require_2fa">
                                                <label class="form-check-label">Require Two-Factor Authentication</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="password_complexity" checked>
                                                <label class="form-check-label">Enforce Password Complexity</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password Expiry (days)</label>
                                            <input type="number" class="form-control" name="password_expiry" value="90" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="login_notifications" checked>
                                                <label class="form-check-label">Login Attempt Notifications</label>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="saveSecuritySettings()">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Backup Settings -->
                        <div class="tab-pane fade" id="backup">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Backup Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form id="backupSettingsForm">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_backup" checked>
                                                <label class="form-check-label">Automatic Backups</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Backup Frequency</label>
                                            <select class="form-control" name="backup_frequency">
                                                <option value="daily">Daily</option>
                                                <option value="weekly" selected>Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Retention Period (days)</label>
                                            <input type="number" class="form-control" name="retention_period" value="30" min="1" max="365">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Backup Location</label>
                                            <select class="form-control" name="backup_location">
                                                <option value="local">Local Server</option>
                                                <option value="cloud">Cloud Storage</option>
                                                <option value="both">Both</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-success" onclick="createBackup()">
                                                <i class="fas fa-download me-2"></i>Create Backup Now
                                            </button>
                                            <button type="button" class="btn btn-outline-primary ms-2" onclick="restoreBackup()">
                                                <i class="fas fa-upload me-2"></i>Restore Backup
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="saveBackupSettings()">
                                            <i class="fas fa-save me-2"></i>Save Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Settings -->
                        <div class="tab-pane fade" id="email">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Email Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <form id="emailSettingsForm">
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Server</label>
                                            <input type="text" class="form-control" name="smtp_server" placeholder="smtp.example.com">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SMTP Port</label>
                                                <input type="number" class="form-control" name="smtp_port" value="587">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Encryption</label>
                                                <select class="form-control" name="encryption">
                                                    <option value="none">None</option>
                                                    <option value="ssl">SSL</option>
                                                    <option value="tls" selected>TLS</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Username</label>
                                            <input type="email" class="form-control" name="smtp_username">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" name="smtp_password">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">From Email</label>
                                            <input type="email" class="form-control" name="from_email">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">From Name</label>
                                            <input type="text" class="form-control" name="from_name">
                                        </div>
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-outline-info" onclick="testEmail()">
                                                <i class="fas fa-paper-plane me-2"></i>Send Test Email
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="saveEmailSettings()">
                                            <i class="fas fa-save me-2"></i>Save Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function saveGeneralSettings() {
            const form = document.getElementById('generalSettingsForm');
            const formData = new FormData(form);
            
            fetch('api/settings/save_general.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم حفظ الإعدادات العامة بنجاح!', 'success');
                    
                    // Update app name in the header if changed
                    if (data.settings.app_name) {
                        const appNameElements = document.querySelectorAll('.navbar-brand span');
                        appNameElements.forEach(el => {
                            el.textContent = data.settings.app_name;
                        });
                    }
                } else {
                    showAlert('خطأ: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('خطأ أثناء حفظ الإعدادات', 'danger');
            });
        }
        
        function saveCompanySettings() {
            alert('Company settings save functionality to be implemented');
        }
        
        function saveNotificationSettings() {
            alert('Notification settings save functionality to be implemented');
        }
        
        function saveSecuritySettings() {
            alert('Security settings save functionality to be implemented');
        }
        
        function saveBackupSettings() {
            alert('Backup settings save functionality to be implemented');
        }
        
        function createBackup() {
            alert('Backup creation functionality to be implemented');
        }
        
        function restoreBackup() {
            alert('Backup restore functionality to be implemented');
        }
        
        function saveEmailSettings() {
            alert('Email settings save functionality to be implemented');
        }
        
        function testEmail() {
            alert('Test email functionality to be implemented');
        }
        
        function showAlert(message, type) {
            // Create alert element
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert at the top of the main content
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>

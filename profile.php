<?php
// FUTURE AUTOMOTIVE - User Profile Page
require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Get current user info (placeholder - would get from session/database)
$full_name = $_SESSION['full_name'] ?? 'Admin User';
$email = $_SESSION['email'] ?? 'admin@example.com';

// Page title
$page_title = 'Profile';
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
                        <i class="fas fa-user-circle me-3"></i>
                        My Profile
                    </h1>
                    <p class="text-muted">Manage your personal information and account settings</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <img src="https://via.placeholder.com/150x150?text=Avatar" alt="Profile Avatar" class="rounded-circle" width="150" height="150">
                            </div>
                            <h4><?php echo htmlspecialchars($full_name); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($email); ?></p>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" onclick="changeAvatar()">
                                    <i class="fas fa-camera me-2"></i>Change Avatar
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteAvatar()">
                                    <i class="fas fa-trash me-2"></i>Remove Avatar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Quick Stats</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Member Since</span>
                                <strong>Jan 2024</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Last Login</span>
                                <strong>Today</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Role</span>
                                <strong>Administrator</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Status</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button">
                                <i class="fas fa-user me-2"></i>Personal Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button">
                                <i class="fas fa-lock me-2"></i>Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences" type="button">
                                <i class="fas fa-cog me-2"></i>Preferences
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button">
                                <i class="fas fa-history me-2"></i>Activity
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="profileTabContent">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade show active" id="personal">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Personal Information</h5>
                                </div>
                                <div class="card-body">
                                    <form id="personalInfoForm">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">First Name</label>
                                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars(explode(' ', $full_name)[0]); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars(explode(' ', $full_name)[1] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" placeholder="+1 (555) 123-4567">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" name="date_of_birth">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea class="form-control" name="address" rows="3" placeholder="123 Main St, City, State 12345"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="savePersonalInfo()">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Security Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form id="securityForm">
                                        <h6 class="mb-3">Change Password</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" required>
                                            <div class="form-text">Password must be at least 8 characters long</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        <hr>
                                        <h6 class="mb-3">Two-Factor Authentication</h6>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_2fa">
                                                <label class="form-check-label">Enable Two-Factor Authentication</label>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="updateSecurity()">
                                            <i class="fas fa-save me-2"></i>Update Security
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preferences Tab -->
                        <div class="tab-pane fade" id="preferences">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">User Preferences</h5>
                                </div>
                                <div class="card-body">
                                    <form id="preferencesForm">
                                        <div class="mb-3">
                                            <label class="form-label">Language</label>
                                            <select class="form-control" name="language">
                                                <option value="en" selected>English</option>
                                                <option value="fr">Français</option>
                                                <option value="ar">العربية</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Timezone</label>
                                            <select class="form-control" name="timezone">
                                                <option value="UTC">UTC</option>
                                                <option value="America/New_York">Eastern Time</option>
                                                <option value="America/Chicago">Central Time</option>
                                                <option value="America/Denver">Mountain Time</option>
                                                <option value="America/Los_Angeles">Pacific Time</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date Format</label>
                                            <select class="form-control" name="date_format">
                                                <option value="Y-m-d">YYYY-MM-DD</option>
                                                <option value="m/d/Y">MM/DD/YYYY</option>
                                                <option value="d/m/Y">DD/MM/YYYY</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="email_notifications" checked>
                                                <label class="form-check-label">Email Notifications</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="desktop_notifications">
                                                <label class="form-check-label">Desktop Notifications</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="dark_mode">
                                                <label class="form-check-label">Dark Mode</label>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="savePreferences()">
                                            <i class="fas fa-save me-2"></i>Save Preferences
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Activity Tab -->
                        <div class="tab-pane fade" id="activity">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-primary"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">Logged in</h6>
                                                <p class="text-muted mb-0">Today at 9:30 AM</p>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">Updated profile</h6>
                                                <p class="text-muted mb-0">Yesterday at 2:15 PM</p>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-warning"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">Password changed</h6>
                                                <p class="text-muted mb-0">3 days ago</p>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-info"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">Created work order</h6>
                                                <p class="text-muted mb-0">1 week ago</p>
                                            </div>
                                        </div>
                                    </div>
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
        function changeAvatar() {
            alert('Avatar change functionality to be implemented');
        }
        
        function deleteAvatar() {
            if (confirm('Are you sure you want to remove your avatar?')) {
                alert('Avatar deletion functionality to be implemented');
            }
        }
        
        function savePersonalInfo() {
            alert('Personal information save functionality to be implemented');
        }
        
        function updateSecurity() {
            alert('Security update functionality to be implemented');
        }
        
        function savePreferences() {
            alert('Preferences save functionality to be implemented');
        }
    </script>
    
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-marker {
            position: absolute;
            left: -25px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
        }
    </style>
</body>
</html>

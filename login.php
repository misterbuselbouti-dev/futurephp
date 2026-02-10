<?php
// FUTURE AUTOMOTIVE - Fixed Original Login Page
// Modified to work with email and password_verify

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'driver') {
        redirect('driver_login.php');
    } else {
        redirect('dashboard.php');
    }
    exit;
}

// Handle login form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs.';
    } else {
        try {
            // Connect to database directly
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Try to find user by email first (most common)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // If not found by email, try username (for backward compatibility)
            if (!$user) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($user) {
                // Check password (supports multiple formats)
                $password_valid = false;
                
                // Try password_verify first (for hashed passwords)
                if (password_verify($password, $user['password'])) {
                    $password_valid = true;
                }
                // Try direct comparison (for plain text passwords)
                elseif ($password === $user['password']) {
                    $password_valid = true;
                    // Update to hashed password for security
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->execute([$hashed_password, $user['id']]);
                }
                // Try MD5 (for old passwords)
                elseif (md5($password) === $user['password']) {
                    $password_valid = true;
                    // Update to hashed password for security
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->execute([$hashed_password, $user['id']]);
                }

                if ($password_valid) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user'] = $user;
                    $_SESSION['username'] = $user['username'] ?? $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_login'] = date('Y-m-d H:i:s');
                    
                    // Update last login in database
                    $updateStmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Redirect based on role
                    $role = $user['role'] ?? '';
                    if ($role === 'driver') {
                        redirect('driver_login.php');
                    } else {
                        $redirect_url = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                        unset($_SESSION['redirect_after_login']);
                        redirect($redirect_url);
                    }
                    exit;
                } else {
                    $error_message = 'Nom d\'utilisateur ou mot de passe incorrect.';
                }
            } else {
                $error_message = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
        } catch (Exception $e) {
            $error_message = 'Erreur de connexion. Veuillez réessayer plus tard.';
            // Log error for debugging
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Future Automotive</title>
    <meta name="description" content="Page de connexion Future Automotive">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <!-- ISO 9001 Professional Theme -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/iso-theme.css">
    <link rel="stylesheet" href="assets/css/iso-components.css">
    <link rel="stylesheet" href="assets/css/iso-bootstrap.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-primary);
        }
        
        .login-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-8);
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .brand-logo {
            font-size: var(--text-3xl);
            font-weight: var(--font-bold);
            color: var(--primary);
            text-align: center;
            margin-bottom: var(--space-4);
        }
        
        .brand-text {
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: var(--space-6);
        }
        
        .form-control {
            border: 1px solid var(--border-primary);
            border-radius: var(--radius);
            padding: var(--space-3);
            font-size: var(--text-base);
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 54, 93, 0.25);
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: var(--radius);
            padding: var(--space-3);
            font-weight: var(--font-medium);
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .alert {
            border-radius: var(--radius);
            border: none;
            color: white;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        .feature-list li {
            padding: 8px 0;
            opacity: 0.9;
        }
        .feature-list i {
            margin-right: 10px;
            color: var(--secondary-color);
        }
        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
            .login-right {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="brand-logo">
                <i class="fas fa-car me-2"></i>
                Future Automotive
            </div>
            <div class="brand-text">
                Système de gestion ISO 9001
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>Email ou Nom d'utilisateur
                    </label>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Entrez votre email ou nom d'utilisateur" required
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Mot de passe
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Entrez votre mot de passe" required>
                </div>
                
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Se souvenir de moi
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Se connecter
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Connexion sécurisée ISO 9001
                </small>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
            
            // Clear error messages on input
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    const alerts = document.querySelectorAll('.alert');
                    alerts.forEach(alert => alert.remove());
                });
            });
        });
    </script>
</body>
</html>

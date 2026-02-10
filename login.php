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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #f59e0b;
            --dark-color: #1f2937;
            --light-bg: #f8fafc;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            min-height: 500px;
        }
        .login-left {
            background: linear-gradient(135deg, var(--primary-color), #2563eb);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .login-right {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .brand-logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .brand-text {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(30, 58, 138, 0.25);
        }
        .btn-login {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-login:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 58, 138, 0.3);
        }
        .back-home {
            color: white;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }
        .back-home:hover {
            opacity: 1;
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
        <div class="row g-0 h-100">
            <!-- Left Side - Branding -->
            <div class="col-md-5">
                <div class="login-left">
                    <div class="brand-logo">
                        <i class="fas fa-car me-2"></i>
                        Future Automotive
                    </div>
                    <div class="brand-text">
                        Votre système de gestion de garage professionnel
                    </div>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Gestion des clients</li>
                        <li><i class="fas fa-check-circle"></i> Suivi des véhicules</li>
                        <li><i class="fas fa-check-circle"></i> Ordres de travail</li>
                        <li><i class="fas fa-check-circle"></i> Facturation</li>
                        <li><i class="fas fa-check-circle"></i> Inventaire</li>
                    </ul>
                    <a href="landing.php" class="back-home">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour à l'accueil
                    </a>
                </div>
            </div>
            
            <!-- Right Side - Login Form -->
            <div class="col-md-7">
                <div class="login-right">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-dark">Connexion</h2>
                        <p class="text-muted">Accédez à votre espace de travail</p>
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
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-2"></i>Email ou Nom d'utilisateur
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Entrez votre email ou nom d'utilisateur" required
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Mot de passe
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Entrez votre mot de passe" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-login mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Se connecter
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Connexion sécurisée avec chiffrement SSL
                        </small>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="landing.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>
                            Retour à l'accueil
                        </a>
                    </div>
                </div>
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

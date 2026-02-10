<?php
require_once 'config.php';
require_once 'includes/functions.php';

// If driver is already logged in, redirect to portal
if (isset($_SESSION['driver_id'])) {
    header('Location: driver_portal.php');
    exit;
}

$page_title = 'Connexion Chauffeur';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driverId = (int)($_POST['driver_id'] ?? 0);
    $pin = trim($_POST['pin'] ?? '');
    
    if (empty($driverId) || empty($pin)) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        try {
            $database = new Database();
            $pdo = $database->connect();
            
            // Search by numero_conducteur first, then by id if not found
            $stmt = $pdo->prepare("SELECT * FROM drivers WHERE (numero_conducteur = ? OR id = ?) AND is_active = 1");
            $stmt->execute([$driverId, $driverId]);
            $driver = $stmt->fetch();
            
            if ($driver && ($driver['pin_code'] ?? '0000') === $pin) {
                // Set driver session
                $_SESSION['driver_id'] = $driver['id'];
                $_SESSION['driver_name'] = ($driver['prenom'] ?? '') . ' ' . ($driver['nom'] ?? '') ?: ($driver['name'] ?? 'Chauffeur ' . $driver['id']);
                
                // Update last login
                $pdo->prepare("UPDATE drivers SET last_login = NOW() WHERE id = ?")->execute([$driver['id']]);
                
                header('Location: driver_portal.php');
                exit;
            } else {
                $error_message = 'N° Conducteur ou PIN incorrect';
            }
        } catch (Exception $e) {
            $error_message = 'Erreur de connexion';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.8rem;
            border: 1px solid #e0e0e0;
            font-size: 1rem;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-id-card fa-3x mb-3"></i>
            <h2>Connexion Chauffeur</h2>
        </div>
        <div class="login-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">N° Conducteur</label>
                    <input type="number" class="form-control" name="driver_id" required autofocus placeholder="Ex: 1001, 1002...">
                    <small class="text-muted">Votre N° Conducteur (visible dans la liste des chauffeurs)</small>
                </div>
                <div class="mb-4">
                    <label class="form-label">Code PIN</label>
                    <input type="password" class="form-control" name="pin" required inputmode="numeric" pattern="\d{4,8}" maxlength="8" placeholder="4 à 8 chiffres">
                    <small class="text-muted">PIN par défaut: 0000</small>
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <strong>Pour vous connecter:</strong><br>
                    1. Utilisez votre N° Conducteur<br>
                    2. PIN par défaut: 0000<br>
                    3. Demandez à l'administration si besoin
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

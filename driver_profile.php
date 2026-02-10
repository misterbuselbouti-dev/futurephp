<?php
require_once 'config.php';
require_once 'includes/functions.php';

require_login();
$user = get_logged_in_user();
$role = $user['role'] ?? '';
if ($role !== 'driver') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

$page_title = 'Mon Compte';

$database = new Database();
$pdo = $database->connect();

// Load driver info
$driver = null;
$driverId = null;
try {
    $stmt = $pdo->prepare("SELECT driver_id FROM users WHERE id = ?");
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch();
    $driverId = isset($row['driver_id']) ? (int)$row['driver_id'] : null;

    if ($driverId) {
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch();
    }
} catch (Exception $e) {
    $driver = null;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'change_pin') {
        $currentPin = $_POST['current_pin'] ?? '';
        $newPin = $_POST['new_pin'] ?? '';
        $confirmPin = $_POST['confirm_pin'] ?? '';

        if (empty($currentPin) || empty($newPin) || empty($confirmPin)) {
            $error_message = 'Tous les champs sont obligatoires.';
        } elseif ($currentPin !== $user['password']) {
            $error_message = 'PIN actuel incorrect.';
        } elseif (!ctype_digit($newPin) || strlen($newPin) < 4 || strlen($newPin) > 8) {
            $error_message = 'Le nouveau PIN doit être composé de 4 à 8 chiffres.';
        } elseif ($newPin !== $confirmPin) {
            $error_message = 'La confirmation ne correspond pas.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newPin, (int)$user['id']]);
            $success_message = 'PIN modifié avec succès.';
            // Update session user
            $_SESSION['user']['password'] = $newPin;
        }
    }
}

$driverName = '-';
if ($driver) {
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasNomPrenom = in_array('nom', $cols) && in_array('prenom', $cols);
    if ($hasNomPrenom) {
        $driverName = trim(($driver['prenom'] ?? '') . ' ' . ($driver['nom'] ?? '')) ?: '-';
    } else {
        $driverName = $driver['name'] ?? '-';
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .mobile-app {
            max-width: 400px;
            margin: 2rem auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .mobile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .mobile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .mobile-body { padding: 2rem; }
        .mobile-btn {
            border-radius: 15px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            border: none;
            transition: all 0.3s;
        }
        .mobile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .mobile-btn-primary { background: #667eea; color: white; }
        .mobile-btn-success { background: #28a745; color: white; }
        .form-control, .form-label {
            border-radius: 10px;
            font-size: 1rem;
        }
        .form-control {
            padding: 0.8rem 1rem;
            border: 1px solid #e0e0e0;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .alert {
            border-radius: 10px;
            border: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="mobile-app">
        <div class="mobile-header">
            <div class="mobile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h4 class="mb-1"><?php echo htmlspecialchars($driverName); ?></h4>
            <p class="mb-0 opacity-75"><?php echo htmlspecialchars($user['username']); ?></p>
        </div>
        
        <div class="mobile-body">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="change_pin">
                <h5 class="mb-4 text-center">🔐 Changer mon PIN</h5>
                
                <div class="mb-3">
                    <label class="form-label">PIN actuel</label>
                    <input type="password" class="form-control" name="current_pin" required inputmode="numeric" pattern="\d{4,8}" maxlength="8" placeholder="••••">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nouveau PIN</label>
                    <input type="password" class="form-control" name="new_pin" required inputmode="numeric" pattern="\d{4,8}" maxlength="8" placeholder="4 à 8 chiffres">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Confirmer le PIN</label>
                    <input type="password" class="form-control" name="confirm_pin" required inputmode="numeric" pattern="\d{4,8}" maxlength="8" placeholder="••••">
                </div>
                
                <button type="submit" class="btn mobile-btn mobile-btn-primary w-100">
                    <i class="fas fa-key me-2"></i>Changer le PIN
                </button>
            </form>

            <hr class="my-4">

            <a href="driver_breakdown_new.php" class="btn mobile-btn mobile-btn-success w-100">
                <i class="fas fa-triangle-exclamation me-2"></i>Déclarer un incident
            </a>

            <div class="text-center mt-4">
                <a href="logout.php" class="text-muted text-decoration-none">
                    <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

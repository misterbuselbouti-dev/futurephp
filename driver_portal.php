<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Check if driver is logged in
if (!isset($_SESSION['driver_id'])) {
    header('Location: driver_login.php');
    exit;
}

$page_title = 'Portail Chauffeur';
$driverId = (int)$_SESSION['driver_id'];
$success_message = '';
$error_message = '';

$database = new Database();
$pdo = $database->connect();

// Load driver info
$driver = null;
$assignedBus = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);
    $driver = $stmt->fetch();
    
    if ($driver) {
        $stmt = $pdo->prepare("SELECT * FROM buses WHERE driver_id = ?");
        $stmt->execute([$driverId]);
        $assignedBus = $stmt->fetch();
    }
} catch (Exception $e) {
    $error_message = 'Erreur de chargement';
}

// Handle PIN change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'change_pin') {
    $currentPin = $_POST['current_pin'] ?? '';
    $newPin = $_POST['new_pin'] ?? '';
    $confirmPin = $_POST['confirm_pin'] ?? '';
    
    if (empty($currentPin) || empty($newPin) || empty($confirmPin)) {
        $error_message = 'Tous les champs sont obligatoires';
    } elseif ($currentPin !== ($driver['pin_code'] ?? '0000')) {
        $error_message = 'PIN actuel incorrect';
    } elseif (!ctype_digit($newPin) || strlen($newPin) < 4 || strlen($newPin) > 8) {
        $error_message = 'Le PIN doit être composé de 4 à 8 chiffres';
    } elseif ($newPin !== $confirmPin) {
        $error_message = 'La confirmation ne correspond pas';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE drivers SET pin_code = ? WHERE id = ?");
            $stmt->execute([$newPin, $driverId]);
            $success_message = 'PIN modifié avec succès';
            // Update session
            $driver['pin_code'] = $newPin;
        } catch (Exception $e) {
            $error_message = 'Erreur lors de la modification';
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['driver_id']);
    unset($_SESSION['driver_name']);
    header('Location: driver_login.php');
    exit;
}

$driverName = $_SESSION['driver_name'] ?? 'Chauffeur';
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
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: white;
        }
        .mobile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .mobile-btn-primary { background: #667eea; }
        .mobile-btn-success { background: #28a745; }
        .mobile-btn-warning { background: #ffc107; color: #333; }
        .mobile-btn-danger { background: #dc3545; }
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
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
            <p class="mb-0 opacity-75">ID: <?php echo $driverId; ?></p>
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

            <!-- Driver Info -->
            <div class="info-card">
                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Informations</h6>
                <div class="small">
                    <div><strong>Bus assigné:</strong> <?php echo $assignedBus ? htmlspecialchars($assignedBus['bus_number']) : 'Aucun'; ?></div>
                    <div><strong>Taux horaire:</strong> <?php echo number_format($driver['taux_horaire'] ?? 15.48, 2); ?> MAD</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h5 class="mb-3">Actions Rapides</h5>
            
            <a href="driver_breakdown_new.php" class="mobile-btn mobile-btn-danger">
                <i class="fas fa-triangle-exclamation me-2"></i>
                Déclarer un incident
            </a>

            <a href="driver_portal.php?edit_pin=1" class="mobile-btn mobile-btn-warning">
                <i class="fas fa-key me-2"></i>
                Changer mon PIN
            </a>

            <a href="driver_portal.php?logout=1" class="mobile-btn mobile-btn-danger">
                <i class="fas fa-sign-out-alt me-2"></i>
                Déconnexion
            </a>

            <!-- PIN Change Form (shown when edit_pin=1) -->
            <?php if (isset($_GET['edit_pin'])): ?>
                <hr class="my-4">
                <h5 class="mb-3">🔐 Changer mon PIN</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="change_pin">
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
                    <button type="submit" class="mobile-btn mobile-btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                    <a href="driver_portal.php" class="mobile-btn mobile-btn-secondary" style="background: #6c757d;">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Check if driver is logged in
if (!isset($_SESSION['driver_id'])) {
    header('Location: driver_login.php');
    exit;
}

$page_title = 'Déclarer un incident';
$driverId = (int)$_SESSION['driver_id'];
$error_message = '';

$database = new Database();
$conn = $database->connect();

// Load driver and bus info
$driver = null;
$driverBusId = null;
try {
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);
    $driver = $stmt->fetch();
    
    if ($driver) {
        $driverBusId = $driver['bus_id'];
    }
} catch (Exception $e) {
    $error_message = 'Erreur de chargement';
}

// Load buses
$buses = [];
try {
    if ($driverBusId) {
        $stmt = $conn->prepare("SELECT id, bus_number, license_plate, make, model FROM buses WHERE id = ?");
        $stmt->execute([$driverBusId]);
        $buses = $stmt->fetchAll();
    } else {
        $stmt = $conn->query("SELECT id, bus_number, license_plate, make, model FROM buses WHERE status = 'active' ORDER BY bus_number");
        $buses = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $buses = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'submit') {
    $busId = (int)($_POST['bus_id'] ?? 0);
    $kilometrage = (int)($_POST['mileage'] ?? 0);
    $category = sanitize_input($_POST['category'] ?? '');
    $urgency = sanitize_input($_POST['urgency'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $audioData = $_POST['audio_file'] ?? '';
    
    if ($busId && $kilometrage && $category && $urgency) {
        try {
            // Generate report reference
            $reportRef = 'BRK-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            
            // Save audio file if provided
            $audioPath = null;
            if ($audioData) {
                // Convert base64 to file
                $audioData = str_replace('data:audio/wav;base64,', '', $audioData);
                $audioData = base64_decode($audioData);
                if ($audioData) {
                    $filename = 'audio_' . $driverId . '_' . time() . '.wav';
                    $filepath = __DIR__ . '/uploads/breakdowns_audio/' . $filename;
                    if (!is_dir(__DIR__ . '/uploads/breakdowns_audio')) {
                        mkdir(__DIR__ . '/uploads/breakdowns_audio', 0755, true);
                    }
                    file_put_contents($filepath, $audioData);
                    $audioPath = 'uploads/breakdowns_audio/' . $filename;
                }
            }
            
            // Map category values
            $categoryMap = [
                'Moteur' => 'mecanique',
                'Freins' => 'mecanique',
                'Pneus' => 'mecanique',
                'Électricité' => 'electricite',
                'Climatisation' => 'electricite',
                'Carrosserie' => 'carrosserie',
                'Autre' => 'mecanique'
            ];
            $dbCategory = $categoryMap[$category] ?? 'mecanique';
            
            // Map urgency values
            $urgencyMap = [
                'low' => 'normal',
                'medium' => 'normal',
                'high' => 'urgent',
                'critical' => 'urgent'
            ];
            $dbUrgency = $urgencyMap[$urgency] ?? 'normal';
            
            $stmt = $conn->prepare("
                INSERT INTO breakdown_reports (
                    report_ref, created_by_user_id, driver_id, bus_id, kilometrage, 
                    category, urgency, message_text, audio_path, status
                ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, 'nouveau')
            ");
            $stmt->execute([
                $reportRef,
                $driverId,
                $busId,
                $kilometrage,
                $dbCategory,
                $dbUrgency,
                $description,
                $audioPath
            ]);
            
            $_SESSION['message'] = 'Incident signalé avec succès';
            header('Location: driver_portal.php');
            exit;
        } catch (Exception $e) {
            $error_message = 'Erreur lors de la soumission: ' . $e->getMessage();
        }
    } else {
        $error_message = 'Veuillez remplir tous les champs obligatoires';
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        .mobile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            color: #dc3545;
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
        .mobile-btn-danger { background: #dc3545; color: white; }
        .form-control, .form-label, .form-select {
            border-radius: 10px;
            font-size: 1rem;
        }
        .form-control, .form-select {
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
        .audio-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .back-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="mobile-app">
        <div class="mobile-header">
            <a href="driver_portal.php" class="back-btn">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
            <div class="mobile-avatar">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h4 class="mb-1">Déclarer un Incident</h4>
            <p class="mb-0 opacity-75">Signaler un problème</p>
        </div>
        
        <div class="mobile-body">
            <?php if (!empty($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit">
                
                <div class="mb-3">
                    <label class="form-label">Bus</label>
                    <select class="form-select" name="bus_id" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($buses as $bus): ?>
                        <option value="<?php echo $bus['id']; ?>" <?php echo ($bus['id'] == $driverBusId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bus['bus_number'] . ' - ' . ($bus['make'] ?? '') . ' ' . ($bus['model'] ?? '')); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kilométrage</label>
                    <input type="number" class="form-control" name="mileage" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catégorie</label>
                    <select class="form-select" name="category" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="Moteur">Moteur</option>
                        <option value="Freins">Freins</option>
                        <option value="Pneus">Pneus</option>
                        <option value="Électricité">Électricité</option>
                        <option value="Climatisation">Climatisation</option>
                        <option value="Carrosserie">Carrosserie</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Urgence</label>
                    <select class="form-select" name="urgency" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="low">Basse</option>
                        <option value="medium">Moyenne</option>
                        <option value="high">Élevée</option>
                        <option value="critical">Critique</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Décrivez le problème..."></textarea>
                </div>

                <div class="audio-section">
                    <label class="form-label">Message Audio (Optionnel)</label>
                    <div class="d-flex align-items-center mb-2">
                        <button type="button" id="recordBtn" class="btn btn-danger btn-sm me-2">
                            <i class="fas fa-microphone me-1"></i> Enregistrer
                        </button>
                        <button type="button" id="stopBtn" class="btn btn-secondary btn-sm me-2" style="display:none;">
                            <i class="fas fa-stop me-1"></i> Arrêter
                        </button>
                        <button type="button" id="playBtn" class="btn btn-info btn-sm me-2" style="display:none;">
                            <i class="fas fa-play me-1"></i> Écouter
                        </button>
                        <button type="button" id="deleteBtn" class="btn btn-warning btn-sm" style="display:none;">
                            <i class="fas fa-trash me-1"></i> Supprimer
                        </button>
                    </div>
                    <audio id="audioPlayer" controls style="display:none; width: 100%;"></audio>
                    <input type="hidden" name="audio_file" id="audioFile">
                    <div id="recordingStatus" class="small text-muted"></div>
                </div>

                <button type="submit" class="mobile-btn mobile-btn-danger w-100">
                    <i class="fas fa-paper-plane me-2"></i> Envoyer le Signalement
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let mediaRecorder;
        let audioChunks = [];
        let audioBlob;

        const recordBtn = document.getElementById('recordBtn');
        const stopBtn = document.getElementById('stopBtn');
        const playBtn = document.getElementById('playBtn');
        const deleteBtn = document.getElementById('deleteBtn');
        const audioPlayer = document.getElementById('audioPlayer');
        const audioFile = document.getElementById('audioFile');
        const recordingStatus = document.getElementById('recordingStatus');

        recordBtn.addEventListener('click', async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    audioPlayer.src = audioUrl;
                    audioPlayer.style.display = 'block';
                    playBtn.style.display = 'inline-block';
                    deleteBtn.style.display = 'inline-block';
                    
                    // Convert to base64 for form submission
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        audioFile.value = reader.result;
                    };
                    reader.readAsDataURL(audioBlob);
                };

                mediaRecorder.start();
                recordBtn.style.display = 'none';
                stopBtn.style.display = 'inline-block';
                recordingStatus.textContent = 'Enregistrement en cours...';
                recordingStatus.className = 'small text-danger';
            } catch (err) {
                alert('Microphone non accessible: ' + err.message);
            }
        });

        stopBtn.addEventListener('click', () => {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
            recordBtn.style.display = 'inline-block';
            stopBtn.style.display = 'none';
            recordingStatus.textContent = 'Enregistrement terminé';
            recordingStatus.className = 'small text-success';
        });

        playBtn.addEventListener('click', () => {
            audioPlayer.play();
        });

        deleteBtn.addEventListener('click', () => {
            audioPlayer.style.display = 'none';
            playBtn.style.display = 'none';
            deleteBtn.style.display = 'none';
            audioFile.value = '';
            recordingStatus.textContent = '';
        });
    </script>
</body>
</html>

<?php
// FUTURE AUTOMOTIVE - Enhanced Landing Page
// Professional landing page with ISO 9001/45001 theme

require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in
$is_logged_in = is_logged_in();
$user = $is_logged_in ? get_logged_in_user() : null;
$full_name = $user['full_name'] ?? 'Guest';
$role = $user['role'] ?? 'guest';

// If user is logged in, redirect to dashboard
if ($is_logged_in && in_array($role, ['admin', 'maintenance_manager', 'driver', 'user'])) {
    header("Location: dashboard.php");
    exit();
}

// Otherwise, show enhanced landing page
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FUTURE AUTOMOTIVE - Système de Gestion de Flotte</title>
    
    <!-- ISO 9001/45001 Theme -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/iso-universal-theme.css">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            font-weight: 400;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .feature-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: 1px solid var(--border);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-light);
            font-weight: 500;
        }
        
        .cta-section {
            background: var(--bg-light);
            padding: 4rem 0;
            text-align: center;
        }
        
        .btn-hero {
            padding: 1rem 3rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: var(--radius);
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .iso-badges {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .iso-badge {
            background: white;
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            border: 2px solid var(--primary);
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="iso-badges">
                <div class="iso-badge">ISO 9001</div>
                <div class="iso-badge">ISO 45001</div>
            </div>
            <h1 class="hero-title">FUTURE AUTOMOTIVE</h1>
            <p class="hero-subtitle">Système de Gestion de Flotte Professionnel</p>
            <p class="lead mb-4">Solution complète pour la gestion des bus, maintenance, et achats</p>
            <div>
                <a href="login.php" class="btn-hero btn-light">
                    <i class="fas fa-sign-in-alt me-2"></i>Se Connecter
                </a>
                <a href="#features" class="btn-hero btn-outline-light">
                    <i class="fas fa-info-circle me-2"></i>En Savoir Plus
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Fonctionnalités Principales</h2>
                <p class="lead text-muted">Outils complets pour une gestion efficace de votre flotte</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bus"></i>
                        </div>
                        <h4>Gestion de Flotte</h4>
                        <p class="text-muted">Suivi complet des bus, maintenance, et affectations</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <h4>Maintenance</h4>
                        <p class="text-muted">Planification et suivi des opérations de maintenance</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h4>Achats</h4>
                        <p class="text-muted">Gestion des demandes d'achat et commandes</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Personnel</h4>
                        <p class="text-muted">Gestion des chauffeurs et techniciens</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Rapports</h4>
                        <p class="text-muted">Analyse et rapports détaillés</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Audit & Sécurité</h4>
                        <p class="text-muted">Suivi des activités et contrôle d'accès</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Performance & Fiabilité</h2>
                <p class="lead text-muted">Système certifié et éprouvé</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">Disponibilité</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Support</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">500+</div>
                    <div class="stat-label">Véhicules Gérés</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">ISO</div>
                    <div class="stat-label">Certifié</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="text-center">
                <h2 class="display-4 fw-bold mb-4">Prêt à Optimiser Votre Flotte?</h2>
                <p class="lead mb-4">Rejoignez les entreprises qui font confiance à FUTURE AUTOMOTIVE</p>
                <div>
                    <a href="login.php" class="btn-hero btn-primary">
                        <i class="fas fa-rocket me-2"></i>Commencer Maintenant
                    </a>
                    <a href="mailto:info@futureautomotive.com" class="btn-hero btn-outline-primary">
                        <i class="fas fa-envelope me-2"></i>Nous Contacter
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>FUTURE AUTOMOTIVE</h5>
                    <p class="text-muted">Système de gestion de flotte certifié ISO 9001/45001</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="text-muted mb-0">&copy; 2024 FUTURE AUTOMOTIVE. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
?>

<?php
// FUTURE AUTOMOTIVE - Final Database Configuration
// Correct settings based on phpMyAdmin access

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'u442210176_Futur2');
if (!defined('DB_USER')) define('DB_USER', 'u442210176_Futur2');
if (!defined('DB_PASS')) define('DB_PASS', '12Abdou12');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Application Settings (define only once)
if (!defined('APP_NAME')) {
    define('APP_NAME', 'FUTURE AUTOMOTIVE');
    define('DEFAULT_LANGUAGE', 'fr');
    define('SUPPORTED_LANGUAGES', 'fr,ar');
    define('LANG', 'fr');
    define('DIR', 'ltr');
    define('APP_VERSION', '1.0.0');
    define('APP_URL', 'https://www.futureautomotive.net');
}

// Session Settings (only if session not started)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 0 for localhost
}

// Timezone
date_default_timezone_set('Europe/Paris');

// Error reporting based on environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Create database connection
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn;

    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (PDOException $e) {
            // Log the actual error for debugging
            error_log("Database connection failed: " . $e->getMessage());
            
            // Return a user-friendly error
            throw new Exception("Database connection failed. Please check your database settings.");
        }
    }
}

// Security Headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Include functions (only if not already included)
if (!function_exists('translate')) {
    require_once 'includes/functions.php';
}

// Load settings from database - call this function when needed, not on include
function loadSettings() {
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $database = new Database();
            $db = $database->connect();
            $stmt = $db->query("SELECT setting_key, value FROM settings");
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['value'];
            }
        } catch (Exception $e) {
            // Use defaults if database error
        }
    }
    return $settings;
}

// Get currency setting - always MAD (Moroccan Dirham)
function getCurrency() {
    return 'MAD';
}

function getCurrencySymbol() {
    return 'د.م.';
}

// Define currency constant - always MAD
if (!defined('CURRENCY')) {
    define('CURRENCY', 'MAD');
}
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'DH');
}

// Language Settings

// Translation function (only if not already defined)
if (!function_exists('translate')) {
    function translate($key) {
        $translations = [
            'fr' => [
                'Dashboard' => 'Tableau de bord',
                'Customers' => 'Clients',
                'Vehicles' => 'Véhicules',
                'Appointments' => 'Rendez-vous',
                'Work Orders' => 'Ordres de travail',
                'Employees' => 'Employés',
                'Inventory' => 'Inventaire',
                'Invoices' => 'Factures',
                'Notifications' => 'Notifications',
                'Reports' => 'Rapports',
                'Settings' => 'Paramètres',
                'Logout' => 'Déconnexion',
                'Login' => 'Connexion',
                'Username' => 'Nom d\'utilisateur',
                'Password' => 'Mot de passe',
                'Remember me' => 'Se souvenir de moi',
                'Garage Management System' => 'Système de gestion de garage',
                'Total Customers' => 'Total des clients',
                'Cars in Repair' => 'Voitures en réparation',
                'Total Revenue' => 'Revenu total',
                'Monthly Orders' => 'Commandes mensuelles',
                'Today\'s Appointments' => 'Rendez-vous d\'aujourd\'hui',
                'Active Employees' => 'Employés actifs',
                'New Appointment' => 'Nouveau rendez-vous',
                'Add Customer' => 'Ajouter un client',
                'Add Vehicle' => 'Ajouter un véhicule',
                'New Work Order' => 'Nouvel ordre de travail',
                'Add Part' => 'Ajouter une pièce',
                'Quick Actions' => 'Actions rapides',
                'System Status' => 'État du système',
                'Database' => 'Base de données',
                'Connected' => 'Connecté',
                'Storage' => 'Stockage',
                'Available' => 'Disponible',
                'Last Backup' => 'Dernière sauvegarde',
                'Today\'s Activity' => 'Activité d\'aujourd\'hui',
                'New Orders' => 'Nouvelles commandes',
                'Completed Jobs' => 'Travaux terminés',
                'Pending Invoices' => 'Factures en attente',
                'Low Inventory Alert' => 'Alerte de stock faible',
                'You have' => 'Vous avez',
                'items that need restocking' => 'articles qui nécessitent un réapprovisionnement',
                'Name' => 'Nom',
                'Email' => 'Email',
                'Phone' => 'Téléphone',
                'Address' => 'Adresse',
                'Description' => 'Description',
                'Price' => 'Prix',
                'Quantity' => 'Quantité',
                'Status' => 'Statut',
                'Date' => 'Date',
                'Time' => 'Heure',
                'Actions' => 'Actions',
                'Edit' => 'Modifier',
                'Delete' => 'Supprimer',
                'View' => 'Voir',
                'Save' => 'Enregistrer',
                'Cancel' => 'Annuler',
                'Add' => 'Ajouter',
                'Update' => 'Mettre à jour',
                'Search' => 'Rechercher',
                'Filter' => 'Filtrer',
                'Export' => 'Exporter',
                'Print' => 'Imprimer',
                'Close' => 'Fermer',
                'Open' => 'Ouvrir',
                'Submit' => 'Soumettre',
                'Reset' => 'Réinitialiser',
                'Clear' => 'Effacer',
                'Select' => 'Sélectionner',
                'All' => 'Tout',
                'None' => 'Aucun',
                'Active' => 'Actif',
                'Inactive' => 'Inactif',
                'Pending' => 'En attente',
                'Completed' => 'Terminé',
                'Cancelled' => 'Annulé',
                'Approved' => 'Approuvé',
                'Rejected' => 'Rejeté',
                'Paid' => 'Payé',
                'Unpaid' => 'Non payé',
                'Total' => 'Total',
                'Subtotal' => 'Sous-total',
                'Tax' => 'Taxe',
                'Discount' => 'Remise',
                'Notes' => 'Notes',
                'Details' => 'Détails',
                'Information' => 'Information',
                'Settings' => 'Paramètres',
                'Profile' => 'Profil',
                'Dashboard' => 'Tableau de bord',
                'Home' => 'Accueil',
                'Back' => 'Retour',
                'Next' => 'Suivant',
                'Previous' => 'Précédent',
                'First' => 'Premier',
                'Last' => 'Dernier',
                'Page' => 'Page',
                'of' => 'de',
                'results' => 'résultats',
                'No results found' => 'Aucun résultat trouvé',
                'Loading...' => 'Chargement...',
                'Error' => 'Erreur',
                'Success' => 'Succès',
                'Warning' => 'Avertissement',
                'Info' => 'Info',
                'Confirm' => 'Confirmer',
                'Yes' => 'Oui',
                'No' => 'Non',
                'OK' => 'OK',
                'Customer' => 'Client',
                'Vehicle' => 'Véhicule',
                'Car' => 'Voiture',
                'Make' => 'Marque',
                'Model' => 'Modèle',
                'Year' => 'Année',
                'Color' => 'Couleur',
                'Plate Number' => 'Numéro de plaque',
                'VIN Number' => 'Numéro VIN',
                'Appointment' => 'Rendez-vous',
                'Work Order' => 'Ordre de travail',
                'Invoice' => 'Facture',
                'Part' => 'Pièce',
                'Supplier' => 'Fournisseur',
                'Employee' => 'Employé',
                'Mechanic' => 'Mécanicien',
                'Service' => 'Service',
                'Repair' => 'Réparation',
                'Maintenance' => 'Maintenance',
                'Cost' => 'Coût',
                'Amount' => 'Montant',
                'Balance' => 'Solde',
                'Payment' => 'Paiement',
                'Method' => 'Méthode',
                'Reference' => 'Référence',
                'Type' => 'Type',
                'Category' => 'Catégorie',
                'Brand' => 'Marque',
                'Location' => 'Emplacement',
                'Created' => 'Créé',
                'Updated' => 'Mis à jour',
                'Created at' => 'Créé le',
                'Updated at' => 'Mis à jour le',
                'Start date' => 'Date de début',
                'End date' => 'Date de fin',
                'Duration' => 'Durée',
                'Priority' => 'Priorité',
                'Low' => 'Bas',
                'Medium' => 'Moyen',
                'High' => 'Élevé',
                'Critical' => 'Critique',
                'Available' => 'Disponible',
                'Unavailable' => 'Indisponible',
                'In stock' => 'En stock',
                'Out of stock' => 'En rupture de stock',
                'Low stock' => 'Stock faible',
                'Minimum quantity' => 'Quantité minimale',
                'Unit price' => 'Prix unitaire',
                'Total price' => 'Prix total',
                'Order number' => 'Numéro de commande',
                'Invoice number' => 'Numéro de facture',
                'Customer name' => 'Nom du client',
                'Customer email' => 'Email du client',
                'Customer phone' => 'Téléphone du client',
                'Problem description' => 'Description du problème',
                'Solution' => 'Solution',
                'Diagnosis' => 'Diagnostic',
                'Recommendations' => 'Recommandations',
                'Labor cost' => 'Coût de la main d\'œuvre',
                'Parts cost' => 'Coût des pièces',
                'Additional notes' => 'Notes additionnelles',
                'Warranty' => 'Garantie',
                'Insurance' => 'Assurance',
                'Registration' => 'Immatriculation',
                'Inspection' => 'Inspection',
                'Test drive' => 'Essai routier',
                'Delivery' => 'Livraison',
                'Pickup' => 'Enlèvement',
                'Drop off' => 'Dépôt',
                'Schedule' => 'Planning',
                'Calendar' => 'Calendrier',
                'Today' => 'Aujourd\'hui',
                'Tomorrow' => 'Demain',
                'Yesterday' => 'Hier',
                'This week' => 'Cette semaine',
                'This month' => 'Ce mois',
                'This year' => 'Cette année',
                'Last week' => 'La semaine dernière',
                'Last month' => 'Le mois dernier',
                'Last year' => 'L\'année dernière',
                'Next week' => 'La semaine prochaine',
                'Next month' => 'Le mois prochain',
                'Next year' => 'L\'année prochaine'
            ],
            'ar' => [
                'Dashboard' => 'لوحة التحكم',
                'Customers' => 'العملاء',
                'Vehicles' => 'المركبات',
                'Appointments' => 'المواعيد',
                'Work Orders' => 'أوامر العمل',
                'Employees' => 'الموظفون',
                'Inventory' => 'المخزون',
                'Invoices' => 'الفواتير',
                'Notifications' => 'الإشعارات',
                'Reports' => 'التقارير',
                'Settings' => 'الإعدادات',
                'Logout' => 'تسجيل الخروج',
                'Login' => 'تسجيل الدخول',
                'Username' => 'اسم المستخدم',
                'Password' => 'كلمة المرور',
                'Remember me' => 'تذكرني',
                'Garage Management System' => 'نظام إدارة الورشة',
                'Total Customers' => 'إجمالي العملاء',
                'Cars in Repair' => 'السيارات قيد الإصلاح',
                'Total Revenue' => 'إجمالي الإيرادات',
                'Monthly Orders' => 'الأوامر الشهرية',
                'Today\'s Appointments' => 'مواعيد اليوم',
                'Active Employees' => 'الموظفون النشطون',
                'New Appointment' => 'موعد جديد',
                'Add Customer' => 'إضافة عميل',
                'Add Vehicle' => 'إضافة مركبة',
                'New Work Order' => 'أمر عمل جديد',
                'Add Part' => 'إضافة قطعة غيار',
                'Quick Actions' => 'إجراءات سريعة',
                'System Status' => 'حالة النظام',
                'Database' => 'قاعدة البيانات',
                'Connected' => 'متصل',
                'Storage' => 'التخزين',
                'Available' => 'متاح',
                'Last Backup' => 'آخر نسخة احتياطي',
                'Today\'s Activity' => 'نشاط اليوم',
                'New Orders' => 'أوامر جديدة',
                'Completed Jobs' => 'أعمال مكتملة',
                'Pending Invoices' => 'فواتير معلقة',
                'Low Inventory Alert' => 'تنبيه انخفاض المخزون',
                'You have' => 'لديك',
                'items that need restocking' => 'عناصر تحتاج لإعادة تخزين',
                'Name' => 'الاسم',
                'Email' => 'البريد الإلكتروني',
                'Phone' => 'الهاتف',
                'Address' => 'العنوان',
                'Description' => 'الوصف',
                'Price' => 'السعر',
                'Quantity' => 'الكمية',
                'Status' => 'الحالة',
                'Date' => 'التاريخ',
                'Time' => 'الوقت',
                'Actions' => 'الإجراءات',
                'Edit' => 'تعديل',
                'Delete' => 'حذف',
                'View' => 'عرض',
                'Save' => 'حفظ',
                'Cancel' => 'إلغاء',
                'Add' => 'إضافة',
                'Update' => 'تحديث',
                'Search' => 'بحث',
                'Filter' => 'تصفية',
                'Export' => 'تصدير',
                'Print' => 'طباعة',
                'Close' => 'إغلاق',
                'Open' => 'فتح',
                'Submit' => 'إرسال',
                'Reset' => 'إعادة تعيين',
                'Clear' => 'مسح',
                'Select' => 'اختر',
                'All' => 'الكل',
                'None' => 'لا شيء',
                'Active' => 'نشط',
                'Inactive' => 'غير نشط',
                'Pending' => 'معلق',
                'Completed' => 'مكتمل',
                'Cancelled' => 'ملغي',
                'Approved' => 'موافق عليه',
                'Rejected' => 'مرفوض',
                'Paid' => 'مدفوع',
                'Unpaid' => 'غير مدفوع',
                'Total' => 'الإجمالي',
                'Subtotal' => 'المجموع الفرعي',
                'Tax' => 'الضريبة',
                'Discount' => 'خصم',
                'Notes' => 'ملاحظات',
                'Details' => 'التفاصيل',
                'Information' => 'معلومات',
                'Settings' => 'الإعدادات',
                'Profile' => 'الملف الشخصي',
                'Dashboard' => 'لوحة التحكم',
                'Home' => 'الرئيسية',
                'Back' => 'رجوع',
                'Next' => 'التالي',
                'Previous' => 'السابق',
                'First' => 'الأول',
                'Last' => 'الأخير',
                'Page' => 'صفحة',
                'of' => 'من',
                'results' => 'نتائج',
                'No results found' => 'لم يتم العثور على نتائج',
                'Loading...' => 'جاري التحميل...',
                'Error' => 'خطأ',
                'Success' => 'نجح',
                'Warning' => 'تحذير',
                'Info' => 'معلومات',
                'Confirm' => 'تأكيد',
                'Yes' => 'نعم',
                'No' => 'لا',
                'OK' => 'موافق',
                'Customer' => 'العميل',
                'Vehicle' => 'المركبة',
                'Car' => 'سيارة',
                'Make' => 'الشركة المصنعة',
                'Model' => 'الموديل',
                'Year' => 'السنة',
                'Color' => 'اللون',
                'Plate Number' => 'رقم اللوحة',
                'VIN Number' => 'رقم الشاسيه',
                'Appointment' => 'موعد',
                'Work Order' => 'أمر عمل',
                'Invoice' => 'فاتورة',
                'Part' => 'قطعة غيار',
                'Supplier' => 'المورد',
                'Employee' => 'الموظف',
                'Mechanic' => 'الميكانيكي',
                'Service' => 'الخدمة',
                'Repair' => 'إصلاح',
                'Maintenance' => 'صيانة',
                'Cost' => 'التكلفة',
                'Amount' => 'المبلغ',
                'Balance' => 'الرصيد',
                'Payment' => 'الدفع',
                'Method' => 'الطريقة',
                'Reference' => 'المرجع',
                'Type' => 'النوع',
                'Category' => 'الفئة',
                'Brand' => 'العلامة التجارية',
                'Location' => 'الموقع',
                'Created' => 'تم الإنشاء',
                'Updated' => 'تم التحديث',
                'Created at' => 'تم الإنشاء في',
                'Updated at' => 'تم التحديث في',
                'Start date' => 'تاريخ البدء',
                'End date' => 'تاريخ الانتهاء',
                'Duration' => 'المدة',
                'Priority' => 'الأولوية',
                'Low' => 'منخفض',
                'Medium' => 'متوسط',
                'High' => 'مرتفع',
                'Critical' => 'حرج',
                'Available' => 'متاح',
                'Unavailable' => 'غير متاح',
                'In stock' => 'متوفر',
                'Out of stock' => 'نفد من المخزون',
                'Low stock' => 'مخزون منخفض',
                'Minimum quantity' => 'الكمية الدنيا',
                'Unit price' => 'سعر الوحدة',
                'Total price' => 'السعر الإجمالي',
                'Order number' => 'رقم الأمر',
                'Invoice number' => 'رقم الفاتورة',
                'Customer name' => 'اسم العميل',
                'Customer email' => 'بريد العميل',
                'Customer phone' => 'هاتف العميل',
                'Problem description' => 'وصف المشكلة',
                'Solution' => 'الحل',
                'Diagnosis' => 'التشخيص',
                'Recommendations' => 'التوصيات',
                'Labor cost' => 'تكلفة العمالة',
                'Parts cost' => 'تكلفة القطع',
                'Additional notes' => 'ملاحظات إضافية',
                'Warranty' => 'الضمان',
                'Insurance' => 'التأمين',
                'Registration' => 'التسجيل',
                'Inspection' => 'الفحص',
                'Test drive' => 'تجربة قيادة',
                'Delivery' => 'التسليم',
                'Pickup' => 'الاستلام',
                'Drop off' => 'التسليم',
                'Schedule' => 'الجدولة',
                'Calendar' => 'التقويم',
                'Today' => 'اليوم',
                'Tomorrow' => 'غداً',
                'Yesterday' => 'أمس',
                'This week' => 'هذا الأسبوع',
                'This month' => 'هذا الشهر',
                'This year' => 'هذا العام',
                'Last week' => 'الأسبوع الماضي',
                'Last month' => 'الشهر الماضي',
                'Last year' => 'العام الماضي',
                'Next week' => 'الأسبوع القادم',
                'Next month' => 'الشهر القادم',
                'Next year' => 'العام القادم'
            ]
        ];
        
        return $translations[LANG][$key] ?? $key;
    }
}

// User authentication functions (only if not already defined)
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('get_logged_in_user')) {
    function get_logged_in_user() {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            redirect('login.php');
        }
    }
}

if (!function_exists('sanitize')) {
    function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = 'MAD') {
        // Validate input
        if (!is_numeric($amount)) {
            $amount = 0;
        }
        
        // Always use Moroccan Dirham
        return number_format((float)$amount, 2, '.', ',') . ' DH';
    }
}

if (!function_exists('getStatusClass')) {
    function getStatusClass($status) {
        $statusClasses = [
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'draft' => 'secondary',
            'sent' => 'primary',
            'paid' => 'success',
            'overdue' => 'danger'
        ];
        
        return $statusClasses[$status] ?? 'secondary';
    }

    // Format date function
    function formatDate($date, $format = 'd/m/Y') {
        if (!$date) return '-';
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
}
?>

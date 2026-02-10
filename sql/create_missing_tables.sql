-- FUTURE AUTOMOTIVE - Create Missing Tables
-- إنشاء الجداول الناقصة للنظام

-- Create pan_issues table
CREATE TABLE IF NOT EXISTS pan_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pan_code VARCHAR(20) UNIQUE NOT NULL,
    label_fr VARCHAR(255) NOT NULL,
    label_ar VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pan_code (pan_code),
    INDEX idx_category (category),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample PAN issues
INSERT IGNORE INTO pan_issues (pan_code, label_fr, label_ar, description, category, priority) VALUES
('PAN001', 'Moteur surchauffé', 'محرك سخان', 'Température du moteur supérieure à la normale', 'Moteur', 'high'),
('PAN002', 'Freinage défaillant', 'فرامل معطل', 'Système de freinage ne fonctionne pas correctement', 'Freinage', 'critical'),
('PAN003', 'Pneu crevé', 'إطار منفجر', 'Pneu avant droit crevé ou endommagé', 'Pneumatique', 'medium'),
('PAN004', 'Batterie faible', 'بطارية ضعيفة', 'Tension de la batterie inférieure à 12V', 'Électrique', 'medium'),
('PAN005', 'Fuite d\'huile', 'تسرب زيت', 'Perte d\'huile moteur visible', 'Moteur', 'high'),
('PAN006', 'Éclairage défectueux', 'إنارة معطلة', 'Phares ou feux stop ne fonctionnent pas', 'Éclairage', 'low'),
('PAN007', 'Direction assistée', 'توجيه معاون', 'Direction difficile à manœuvrer', 'Direction', 'medium'),
('PAN008', 'Climatisation', 'تكييف', 'Système de climatisation ne fonctionne pas', 'Climatisation', 'low'),
('PAN009', 'Transmission', 'ناقل الحركة', 'Problème de changement de vitesses', 'Transmission', 'high'),
('PAN010', 'Suspension', 'تعليق', 'Bruit ou vibration anormaux de la suspension', 'Suspension', 'medium'),
('PAN011', 'Système électrique', 'نظام كهربائي', 'Problème électrique général', 'Électrique', 'medium'),
('PAN012', 'Échappement', 'عادم', 'Bruit excessif ou fumée du pot d\'échappement', 'Échappement', 'high'),
('PAN013', 'Turbo', 'توربو', 'Perte de puissance du turbo', 'Moteur', 'high'),
('PAN014', 'Refroidissement', 'تبريد', 'Surchauffe du moteur', 'Refroidissement', 'critical'),
('PAN015', 'Alarme', 'إنذار', 'Témoin d\'alarme allumé', 'Électronique', 'medium'),
('PAN016', 'Essence/Gazole', 'وقود', 'Problème d\'alimentation en carburant', 'Carburant', 'high'),
('PAN017', 'Portes', 'أبواب', 'Problème de fermeture ou d\'ouverture des portes', 'Carrosserie', 'low'),
('PAN018', 'Vitres', 'زجاج', 'Essuie-glaces ou lève-vitres défectueux', 'Carrosserie', 'low'),
('PAN019', 'Sièges', 'مقاعد', 'Problème avec les sièges ou ceintures', 'Intérieur', 'low'),
('PAN020', 'Tableau de bord', 'لوحة القيادة', 'Témoins ou commandes du tableau de bord', 'Électronique', 'medium');

-- Create breakdown_reports table if it doesn't exist
CREATE TABLE IF NOT EXISTS breakdown_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    driver_id INT NOT NULL,
    breakdown_date DATETIME NOT NULL,
    location VARCHAR(255),
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('reported', 'assigned', 'in_progress', 'resolved', 'closed') DEFAULT 'reported',
    assigned_to INT,
    pan_issue_id INT,
    report_ref VARCHAR(50) UNIQUE,
    category VARCHAR(100),
    urgency VARCHAR(50),
    kilometrage INT,
    message_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (pan_issue_id) REFERENCES pan_issues(id) ON DELETE SET NULL,
    INDEX idx_bus_id (bus_id),
    INDEX idx_driver_id (driver_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_breakdown_date (breakdown_date),
    INDEX idx_pan_issue_id (pan_issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Show results
SELECT 
    'Missing tables created successfully' as status,
    (SELECT COUNT(*) as count FROM pan_issues) as pan_issues_count,
    (SELECT COUNT(*) as count FROM breakdown_reports) as breakdown_reports_count,
    'System is now complete' as message;

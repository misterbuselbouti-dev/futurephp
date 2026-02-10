-- FUTURE AUTOMOTIVE - Check and Fix Drivers Table
-- فحص وإصلاح جدول السائقين

-- Check if drivers table exists
SELECT 'Checking drivers table...' as status;

-- Show table structure if it exists
SHOW TABLES LIKE 'drivers';

-- If table exists, show structure and data
SELECT 'Table structure:' as info;
DESCRIBE drivers;

-- Count drivers
SELECT 'Driver count:' as info;
SELECT COUNT(*) as total_drivers FROM drivers;

-- Show sample data if exists
SELECT 'Sample driver data:' as info;
SELECT * FROM drivers LIMIT 3;

-- If no drivers or table doesn't exist, create/fix it
-- Create drivers table if it doesn't exist
CREATE TABLE IF NOT EXISTS drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    name VARCHAR(200),
    numero_conducteur VARCHAR(50) UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(100),
    cin VARCHAR(20) UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    pin_code VARCHAR(8) DEFAULT '0000',
    bus_id INT,
    taux_horaire DECIMAL(10,2) DEFAULT 15.48,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero_conducteur (numero_conducteur),
    INDEX idx_cin (cin),
    INDEX idx_is_active (is_active),
    INDEX idx_bus_id (bus_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add sample drivers if table is empty
INSERT IGNORE INTO drivers (nom, prenom, numero_conducteur, phone, email, cin, is_active, pin_code, taux_horaire) VALUES
('ALAMI', 'Mohammed', 'DR-001', '0661234567', 'm.alami@future.ma', 'AB123456', 1, '1234', 15.48),
('BENANI', 'Ahmed', 'DR-002', '0662345678', 'a.benani@future.ma', 'CD234567', 1, '5678', 16.50),
('CHAKIR', 'Youssef', 'DR-003', '0663456789', 'y.chakir@future.ma', 'EF345678', 1, '9012', 14.75),
('DAHMANI', 'Omar', 'DR-004', '0664567890', 'o.dahmani@future.ma', 'GH456789', 1, '3456', 17.25),
('EL IDRISSI', 'Karim', 'DR-005', '0665678901', 'k.elidrissi@future.ma', 'IJ567890', 1, '7890', 15.48);

-- Final check
SELECT 'Final driver count:' as status;
SELECT COUNT(*) as total_drivers FROM drivers;

SELECT 'Sample data after fix:' as status;
SELECT id, nom, prenom, numero_conducteur, phone, email, cin, is_active, taux_horaire FROM drivers LIMIT 5;

-- FUTURE AUTOMOTIVE - Complete System Setup
-- إعداد نظام قاعدة البيانات الكامل

-- Disable foreign key checks for setup
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS breakdown_reports;
DROP TABLE IF EXISTS breakdown_assignments;
DROP TABLE IF EXISTS bus_work_orders;
DROP TABLE IF EXISTS driver_breakdown_new;
DROP TABLE IF EXISTS maintenance_schedules;
DROP TABLE IF EXISTS fuel_consumption;
DROP TABLE IF EXISTS bus_expenses;
DROP TABLE IF EXISTS breakdown_parts;
DROP TABLE IF EXISTS pan_issues;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS articles_stockables;
DROP TABLE IF EXISTS stock_by_region;
DROP TABLE IF EXISTS regions;
DROP TABLE IF EXISTS buses;
DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS articles_catalogue;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create tables in correct order

-- 1. Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    role ENUM('admin', 'maintenance_manager', 'agent', 'technician', 'driver') DEFAULT 'agent',
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Drivers table
CREATE TABLE drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    numero_conducteur VARCHAR(50) UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(100),
    cin VARCHAR(20) UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    pin_code VARCHAR(8) DEFAULT '0000',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero_conducteur (numero_conducteur),
    INDEX idx_cin (cin),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Buses table
CREATE TABLE buses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_number VARCHAR(20) NOT NULL UNIQUE,
    license_plate VARCHAR(20) NOT NULL UNIQUE,
    category ENUM('Bus', 'Minibus') NOT NULL DEFAULT 'Minibus',
    make VARCHAR(50),
    model VARCHAR(50),
    year INT,
    capacity INT,
    puissance_fiscale INT COMMENT 'Puissance fiscale CV',
    status ENUM('active', 'inactive', 'maintenance', 'retired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bus_number (bus_number),
    INDEX idx_license_plate (license_plate),
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Articles Catalogue table
CREATE TABLE articles_catalogue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_article VARCHAR(50) UNIQUE NOT NULL,
    designation VARCHAR(255) NOT NULL,
    categorie VARCHAR(100) DEFAULT 'Divers',
    prix_unitaire DECIMAL(15,2) DEFAULT 0.00,
    stock_ksar DECIMAL(10,2) DEFAULT 0.00,
    stock_tetouan DECIMAL(10,2) DEFAULT 0.00,
    stock_actuel DECIMAL(10,2) DEFAULT 0.00,
    stock_minimal DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code_article (code_article),
    INDEX idx_categorie (categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Maintenance related tables
CREATE TABLE maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    driver_id INT,
    maintenance_type VARCHAR(100) NOT NULL,
    scheduled_date DATE NOT NULL,
    completed_date DATE,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    INDEX idx_bus_id (bus_id),
    INDEX idx_driver_id (driver_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Breakdown reports table
CREATE TABLE breakdown_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    driver_id INT NOT NULL,
    breakdown_date DATETIME NOT NULL,
    location VARCHAR(255),
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('reported', 'assigned', 'in_progress', 'resolved', 'closed') DEFAULT 'reported',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_bus_id (bus_id),
    INDEX idx_driver_id (driver_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_breakdown_date (breakdown_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Work orders table
CREATE TABLE work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    technician_id INT NOT NULL,
    work_description TEXT NOT NULL,
    parts_used TEXT,
    labor_hours DECIMAL(5,2),
    labor_cost DECIMAL(10,2),
    parts_cost DECIMAL(10,2),
    total_cost DECIMAL(10,2),
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_breakdown_id (breakdown_id),
    INDEX idx_technician_id (technician_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial data

-- Insert admin user
INSERT INTO users (username, password, full_name, email, role, phone) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@future.ma', 'admin', '0660000000');

-- Insert sample drivers
INSERT INTO drivers (nom, prenom, numero_conducteur, phone, email, cin, is_active, pin_code) VALUES
('ALAMI', 'Mohammed', 'DR-001', '0661234567', 'm.alami@future.ma', 'AB123456', 1, '1234'),
('BENANI', 'Ahmed', 'DR-002', '0662345678', 'a.benani@future.ma', 'CD234567', 1, '5678'),
('CHAKIR', 'Youssef', 'DR-003', '0663456789', 'y.chakir@future.ma', 'EF345678', 1, '9012'),
('DAHMANI', 'Omar', 'DR-004', '0664567890', 'o.dahmani@future.ma', 'GH456789', 1, '3456'),
('EL IDRISSI', 'Karim', 'DR-005', '0665678901', 'k.elidrissi@future.ma', 'IJ567890', 1, '7890');

-- Insert sample buses
INSERT INTO buses (bus_number, license_plate, category, make, model, year, capacity, puissance_fiscale, status) VALUES
-- Buses
('BUS-001', '12345-A-45', 'Bus', 'Mercedes', 'Tourismo', 2019, 55, 15, 'active'),
('BUS-002', '67890-B-12', 'Bus', 'Mercedes', 'Tourismo', 2020, 55, 15, 'active'),
('BUS-003', '11111-C-78', 'Bus', 'Iveco', 'Crossway', 2018, 50, 14, 'active'),
('BUS-004', '22222-D-34', 'Bus', 'Iveco', 'Crossway', 2021, 50, 14, 'active'),
('BUS-005', '33333-E-56', 'Bus', 'Mercedes', 'Tourismo', 2022, 60, 16, 'active'),
-- Minibuses
('MINI-001', '99999-L-45', 'Minibus', 'Mercedes', 'Sprinter', 2020, 30, 8, 'active'),
('MINI-002', '10101-M-67', 'Minibus', 'Mercedes', 'Sprinter', 2021, 30, 8, 'active'),
('MINI-003', '20202-N-89', 'Minibus', 'Iveco', 'Daily', 2019, 25, 7, 'active'),
('MINI-004', '30303-P-01', 'Minibus', 'Mercedes', 'Sprinter', 2022, 35, 9, 'active'),
('MINI-005', '40404-Q-23', 'Minibus', 'Renault', 'Master', 2020, 22, 6, 'active');

-- Insert sample articles
INSERT INTO articles_catalogue (code_article, designation, categorie, prix_unitaire, stock_ksar, stock_tetouan, stock_minimal) VALUES
('FILT-001', 'Filtre à huile', 'Filtres', 85.00, 50.00, 0.00, 5),
('FILT-002', 'Filtre à air', 'Filtres', 45.00, 30.00, 0.00, 8),
('PIEC-001', 'Plaquettes de frein', 'Freinage', 120.00, 20.00, 0.00, 4),
('LIQ-001', 'Huile moteur 15W40', 'Liquides', 12.50, 80.00, 0.00, 20),
('ACC-001', 'Essuie-glace avant', 'Accessoires', 35.00, 22.00, 0.00, 8),
('PIEC-002', 'Disques de frein', 'Freinage', 180.00, 15.00, 0.00, 3),
('LIQ-002', 'Liquide de refroidissement', 'Liquides', 18.00, 60.00, 0.00, 15),
('ACC-002', 'Batterie 12V', 'Accessoires', 320.00, 16.00, 0.00, 4),
('PIEC-003', 'Courroie de distribution', 'Moteur', 150.00, 8.00, 0.00, 2),
('LIQ-003', 'Liquide de frein DOT4', 'Liquides', 22.00, 45.00, 0.00, 10);

-- Update stock calculations
UPDATE articles_catalogue SET stock_actuel = stock_ksar + stock_tetouan;

-- Create views for better data access
CREATE VIEW v_buses_summary AS
SELECT
    category,
    COUNT(*) as total_count,
    SUM(capacity) as total_capacity,
    AVG(puissance_fiscale) as avg_puissance,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count
FROM buses
GROUP BY category
ORDER BY category;

CREATE VIEW v_articles_summary AS
SELECT
    categorie,
    COUNT(*) as total_articles,
    SUM(stock_actuel) as total_stock,
    SUM(stock_minimal) as total_minimal,
    COUNT(CASE WHEN stock_actuel <= stock_minimal THEN 1 END) as low_stock_count
FROM articles_catalogue
GROUP BY categorie
ORDER BY categorie;

-- Show completion message
SELECT '✅ COMPLETE SYSTEM SETUP FINISHED' as status,
       'All tables created with sample data' as message,
       COUNT(*) as total_tables
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name IN ('users', 'drivers', 'buses', 'articles_catalogue', 'notifications', 'maintenance_schedules', 'breakdown_reports', 'work_orders');

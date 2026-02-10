-- FUTURE AUTOMOTIVE - Database Setup Script (ROOT USER)
-- Create database and tables for the breakdown management system

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS u442210176_Futur2;

-- Use the database
USE u442210176_Futur2;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'agent', 'driver') DEFAULT 'driver',
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create buses table
CREATE TABLE IF NOT EXISTS buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_number VARCHAR(50) NOT NULL UNIQUE,
    license_plate VARCHAR(20) NOT NULL,
    status ENUM('active', 'maintenance', 'out_of_service') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create drivers table
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create breakdown_reports table
CREATE TABLE IF NOT EXISTS breakdown_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_ref VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    category VARCHAR(100),
    urgency ENUM('urgent', 'normal', 'low') DEFAULT 'normal',
    status ENUM('nouveau', 'assigne', 'en_cours', 'termine', 'annule') DEFAULT 'nouveau',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    driver_id INT,
    bus_id INT,
    created_by_user_id INT,
    updated_by_user_id INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create breakdown_assignments table
CREATE TABLE IF NOT EXISTS breakdown_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    assigned_to_user_id INT NOT NULL,
    assigned_by_user_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    work_status ENUM('pending', 'in_progress', 'paused', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    actual_hours DECIMAL(5,2) DEFAULT 0,
    total_cost DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (report_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create breakdown_work_items table
CREATE TABLE IF NOT EXISTS breakdown_work_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    assignment_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity_used DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_cost DECIMAL(10,2) NULL,
    total_cost DECIMAL(10,2) NULL,
    notes TEXT,
    added_by_user_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES breakdown_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE RESTRICT,
    FOREIGN KEY (added_by_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create breakdown_time_logs table
CREATE TABLE IF NOT EXISTS breakdown_time_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    assignment_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type ENUM('start', 'pause', 'resume', 'end') NOT NULL,
    notes TEXT,
    created_by_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES breakdown_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create breakdown_audit_log table
CREATE TABLE IF NOT EXISTS breakdown_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    assignment_id INT NULL,
    action_type VARCHAR(100) NOT NULL,
    field_name VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    performed_by_user_id INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT,
    INDEX idx_breakdown_id (breakdown_id),
    INDEX idx_action_type (action_type),
    INDEX idx_performed_by_user_id (performed_by_user_id),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create articles_catalogue table
CREATE TABLE IF NOT EXISTS articles_catalogue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL UNIQUE,
    designation VARCHAR(255) NOT NULL,
    description TEXT,
    unite VARCHAR(20) DEFAULT 'unité',
    stock_actuel DECIMAL(10,2) DEFAULT 0,
    stock_minimal DECIMAL(10,2) DEFAULT 0,
    prix_achat DECIMAL(10,2) DEFAULT 0,
    prix_vente DECIMAL(10,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO users (full_name, email, password, role, is_active, created_at, updated_at) VALUES 
('Admin User', 'admin@futureautomotive.net', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW(), NOW()),
('Technicien 1', 'tech@futureautomotive.net', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician', 1, NOW(), NOW()),
('Agent 1', 'agent@futureautomotive.net', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 1, NOW(), NOW()),
('Driver 1', 'driver@futureautomotive.net', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver', 1, NOW(), NOW());

INSERT INTO buses (bus_number, license_plate, status, created_at, updated_at) VALUES 
('BUS-001', '123-ABC-456', 'active', NOW(), NOW()),
('BUS-002', '456-DEF-789', 'active', NOW(), NOW()),
('BUS-003', '789-XYZ-012', 'maintenance', NOW(), NOW());

INSERT INTO drivers (nom, prenom, telephone, is_active, created_at, updated_at) VALUES 
('Doe', 'John', '0612345678', 1, NOW(), NOW()),
('Smith', 'Jane', '0612345678', 1, NOW(), NOW()),
('Brown', 'Charlie', '0612345678', 1, NOW(), NOW());

INSERT INTO breakdown_reports (report_ref, description, category, urgency, status, created_at, updated_at, driver_id, bus_id, created_by_user_id, updated_by_user_id) VALUES 
('BRK-001', 'Test breakdown', 'urgent', 'nouveau', NOW(), NOW(), 1, 1, 1, 1),
('BRK-002', 'Engine problem', 'normal', 'nouveau', NOW(), 2, 2, 2, 1),
('BRK-003', 'Brake failure', 'urgent', 'en_cours', NOW(), 3, 3, 3, 1);

INSERT INTO breakdown_assignments (report_id, assigned_to_user_id, assigned_by_user_id, assigned_at) VALUES 
(1, 1, 1, NOW()),
(2, 1, 1, NOW()),
(3, 1, 1, NOW());

-- Success message
SELECT '✅ Database setup completed successfully!' as message;

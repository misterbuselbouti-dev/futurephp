-- FUTURE AUTOMOTIVE - Workshop Management Tables
-- إنشاء جداول إدارة الورشة

-- Create work_orders table
CREATE TABLE IF NOT EXISTS work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_ot VARCHAR(50) UNIQUE NOT NULL,
    bus_id INT NOT NULL,
    technician_id INT NOT NULL,
    work_description TEXT NOT NULL,
    work_type VARCHAR(100) DEFAULT 'Maintenance',
    priority ENUM('Faible', 'Normal', 'Urgent', 'Très Urgent') DEFAULT 'Normal',
    estimated_hours DECIMAL(5,2) DEFAULT 0,
    actual_hours DECIMAL(5,2) DEFAULT 0,
    status ENUM('En attente', 'En cours', 'En pause', 'Terminé', 'Annulé') DEFAULT 'En attente',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_ref_ot (ref_ot),
    INDEX idx_bus_id (bus_id),
    INDEX idx_technician_id (technician_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create work_order_parts table
CREATE TABLE IF NOT EXISTS work_order_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    ref_article VARCHAR(50) NOT NULL,
    designation VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    total_cost DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    
    INDEX idx_work_order_id (work_order_id),
    INDEX idx_ref_article (ref_article)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create work_order_timeline table
CREATE TABLE IF NOT EXISTS work_order_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    performed_by INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_work_order_id (work_order_id),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT IGNORE INTO work_orders (
    ref_ot, bus_id, technician_id, work_description, work_type, priority, estimated_hours, status, created_by
) VALUES 
('OT-20250209-001', 1, 1, 'Changement huile moteur et filtres', 'Maintenance', 'Normal', 2.5, 'Terminé', 1),
('OT-20250209-002', 2, 1, 'Réparation frein avant', 'Réparation', 'Urgent', 3.0, 'En cours', 1),
('OT-20250209-003', 3, 2, 'Inspection climatisation', 'Inspection', 'Faible', 1.0, 'En attente', 1);

INSERT IGNORE INTO work_order_parts (
    work_order_id, ref_article, designation, quantity, unit_cost, total_cost, notes
) VALUES 
(1, 'HUILE-001', 'Huile moteur 15W40', 5, 25.00, 125.00, 'Huile de qualité'),
(1, 'FILT-001', 'Filtre à huile', 1, 85.00, 85.00, 'Filtre original'),
(2, 'PLAQ-001', 'Plaquettes de frein avant', 2, 120.00, 240.00, 'Plaquettes haute performance');

INSERT IGNORE INTO work_order_timeline (
    work_order_id, action, description, performed_by
) VALUES 
(1, 'Création', 'Ordre de travail créé', 1),
(1, 'Début', 'Début des travaux', 1),
(1, 'Fin', 'Travaux terminés avec succès', 1),
(2, 'Création', 'Ordre de travail créé', 1),
(2, 'Début', 'Début des travaux', 1);

-- Show results
SELECT 
    '✅ Workshop tables created successfully' as status,
    (SELECT COUNT(*) as count FROM work_orders) as work_orders_count,
    (SELECT COUNT(*) as count FROM work_order_parts) as parts_count,
    (SELECT COUNT(*) as count FROM work_order_timeline) as timeline_count,
    'Workshop management system ready!' as message;

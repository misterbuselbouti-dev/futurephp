-- FUTURE AUTOMOTIVE - Fix Duplicate Data Issues
-- Clean up and insert proper sample data

-- Clean up existing data
DELETE FROM breakdown_assignments;
DELETE FROM breakdown_reports;
DELETE FROM drivers;
DELETE FROM buses;
DELETE FROM users;

-- Reset auto-increment values
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE buses AUTO_INCREMENT = 1;
ALTER TABLE drivers AUTO_INCREMENT = 1;
ALTER TABLE breakdown_reports AUTO_INCREMENT = 1;
ALTER TABLE breakdown_assignments AUTO_INCREMENT = 1;

-- Insert sample data with proper structure
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
SELECT '✅ Database data fixed successfully!' as message;

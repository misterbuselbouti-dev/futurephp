-- FUTURE AUTOMOTIVE - Add Sample Data
-- إضافة بيانات نموذجية للسائقين والموظفين

-- Check and add sample drivers if table is empty
INSERT IGNORE INTO drivers (nom, prenom, numero_conducteur, phone, email, cin, is_active, pin_code) VALUES
('ALAMI', 'Mohammed', 'DR-001', '0661234567', 'm.alami@future.ma', 'AB123456', 1, '1234'),
('BENANI', 'Ahmed', 'DR-002', '0662345678', 'a.benani@future.ma', 'CD234567', 1, '5678'),
('CHAKIR', 'Youssef', 'DR-003', '0663456789', 'y.chakir@future.ma', 'EF345678', 1, '9012'),
('DAHMANI', 'Omar', 'DR-004', '0664567890', 'o.dahmani@future.ma', 'GH456789', 1, '3456'),
('EL IDRISSI', 'Karim', 'DR-005', '0665678901', 'k.elidrissi@future.ma', 'IJ567890', 1, '7890'),
('KAMALI', 'Fatima', 'DR-006', '0666789012', 'f.kamali@future.ma', 'KL678901', 1, '2468'),
('MOUSSATI', 'Rachid', 'DR-007', '0667890123', 'r.moussati@future.ma', 'MN789012', 1, '1357'),
('SADI', 'Amina', 'DR-008', '0668901234', 'a.sadi@future.ma', 'SD890123', 1, '8642'),
('BENSLIMANE', 'Youssef', 'DR-009', '0669012345', 'y.benslimane@future.ma', 'BS901234', 1, '9753'),
('ALAOUI', 'Khadija', 'DR-010', '0660123456', 'k.alaoui@future.ma', 'AL012345', 1, '1597');

-- Check and add sample users if table is empty
INSERT IGNORE INTO users (username, password, full_name, email, role, phone, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@future.ma', 'admin', '0660000000', 1),
('maintenance', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maintenance Manager', 'maintenance@future.ma', 'maintenance_manager', '0661111111', 1),
('technician1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Technician One', 'tech1@future.ma', 'technician', '0662222222', 1),
('technician2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Technician Two', 'tech2@future.ma', 'technician', '0663333333', 1),
('agent1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agent One', 'agent1@future.ma', 'agent', '0664444444', 1),
('agent2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agent Two', 'agent2@future.ma', 'agent', '0665555555', 1);

-- Add sample notifications
INSERT IGNORE INTO notifications (user_id, title, message, type, is_read) VALUES
(1, 'Welcome to FUTURE AUTOMOTIVE', 'System is ready for use. All modules are functional.', 'success', 0),
(1, 'Sample Data Added', 'Sample drivers and users have been added to the system.', 'info', 0),
(2, 'Maintenance Schedule', 'Please check the maintenance schedule for this week.', 'warning', 0),
(3, 'New Work Order', 'You have been assigned to a new work order.', 'info', 0),
(4, 'System Update', 'System has been updated with new features.', 'success', 1);

-- Show results
SELECT 
    'Sample Data Added Successfully' as status,
    (SELECT COUNT(*) FROM drivers) as total_drivers,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM notifications WHERE is_read = 0) as unread_notifications,
    'Ready for use!' as message;

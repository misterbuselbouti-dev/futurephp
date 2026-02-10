-- FUTURE AUTOMOTIVE - Additional Sample Data
-- بيانات إضافية للنظام

-- Additional users
INSERT INTO users (username, password, full_name, email, role, phone) VALUES
('maintenance', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maintenance Manager', 'maintenance@future.ma', 'maintenance_manager', '0661111111'),
('technician1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Technician One', 'tech1@future.ma', 'technician', '0662222222'),
('agent1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agent One', 'agent1@future.ma', 'agent', '0663333333');

-- Additional drivers
INSERT INTO drivers (nom, prenom, numero_conducteur, phone, email, cin, is_active, pin_code) VALUES
('KAMALI', 'Fatima', 'DR-006', '0666789012', 'f.kamali@future.ma', 'KL678901', 1, '2468'),
('MOUSSATI', 'Rachid', 'DR-007', '0667890123', 'r.moussati@future.ma', 'MN789012', 1, '1357'),
('SADI', 'Amina', 'DR-008', '0668901234', 'a.sadi@future.ma', 'SD890123', 1, '8642'),
('BENSLIMANE', 'Youssef', 'DR-009', '0669012345', 'y.benslimane@future.ma', 'BS901234', 1, '9753'),
('ALAOUI', 'Khadija', 'DR-010', '0660123456', 'k.alaoui@future.ma', 'AL012345', 1, '1597');

-- Additional buses
INSERT INTO buses (bus_number, license_plate, category, make, model, year, capacity, puissance_fiscale, status) VALUES
-- Additional Buses
('BUS-006', '44444-F-67', 'Bus', 'Mercedes', 'Tourismo', 2021, 58, 15, 'active'),
('BUS-007', '55555-G-89', 'Bus', 'Iveco', 'Crossway', 2020, 52, 14, 'active'),
('BUS-008', '66666-H-90', 'Bus', 'Mercedes', 'Tourismo', 2019, 56, 15, 'maintenance'),
('BUS-009', '77777-J-01', 'Bus', 'Iveco', 'Crossway', 2022, 54, 14, 'active'),
('BUS-010', '88888-K-23', 'Bus', 'Mercedes', 'Tourismo', 2020, 62, 16, 'active'),
-- Additional Minibuses
('MINI-006', '11111-L-34', 'Minibus', 'Mercedes', 'Sprinter', 2021, 33, 8, 'active'),
('MINI-007', '22222-M-56', 'Minibus', 'Iveco', 'Daily', 2020, 27, 7, 'active'),
('MINI-008', '33333-N-78', 'Minibus', 'Renault', 'Master', 2022, 29, 6, 'active'),
('MINI-009', '44444-P-90', 'Minibus', 'Mercedes', 'Sprinter', 2019, 31, 8, 'active'),
('MINI-010', '55555-Q-12', 'Minibus', 'Iveco', 'Daily', 2021, 26, 7, 'maintenance');

-- Additional articles
INSERT INTO articles_catalogue (code_article, designation, categorie, prix_unitaire, stock_ksar, stock_tetouan, stock_minimal) VALUES
('PIEC-004', 'Joint de culasse', 'Moteur', 250.00, 12.00, 0.00, 3),
('PIEC-005', 'Bougie de préchauffage', 'Moteur', 35.00, 40.00, 0.00, 10),
('PIEC-006', 'Courroie de distribution', 'Moteur', 150.00, 8.00, 0.00, 2),
('PIEC-007', 'Pompe à eau', 'Refroidissement', 220.00, 10.00, 0.00, 2),
('PIEC-008', 'Radiateur', 'Refroidissement', 450.00, 6.00, 0.00, 1),
('PIEC-009', 'Thermostat', 'Refroidissement', 25.00, 35.00, 0.00, 8),
('PIEC-010', 'Amortisseur avant', 'Suspension', 180.00, 14.00, 0.00, 3),
('PIEC-011', 'Amortisseur arrière', 'Suspension', 180.00, 14.00, 0.00, 3),
('PIEC-012', 'Ressort à lames', 'Suspension', 280.00, 8.00, 0.00, 2),
('PIEC-013', 'Batterie 12V 100Ah', 'Électrique', 320.00, 16.00, 0.00, 4),
('PIEC-014', 'Alternateur 24V', 'Électrique', 450.00, 7.00, 0.00, 1),
('PIEC-015', 'Démarreur 24V', 'Électrique', 380.00, 9.00, 0.00, 2),
('ACC-003', 'Phare principal', 'Éclairage', 45.00, 25.00, 0.00, 6),
('ACC-004', 'Feu stop', 'Éclairage', 35.00, 30.00, 0.00, 8),
('ACC-005', 'Clignotant', 'Éclairage', 25.00, 40.00, 0.00, 10),
('LIQ-004', 'Antigel', 'Liquides', 25.00, 50.00, 0.00, 12),
('LIQ-005', 'Additif carburant', 'Liquides', 15.00, 30.00, 0.00, 8),
('LIQ-006', 'Graissage', 'Liquides', 8.00, 100.00, 0.00, 20);

-- Update stock calculations
UPDATE articles_catalogue SET stock_actuel = stock_ksar + stock_tetouan;

-- Sample maintenance schedules
INSERT INTO maintenance_schedules (bus_id, driver_id, maintenance_type, scheduled_date, status, notes) VALUES
(1, 1, 'Oil Change', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 'scheduled', 'Regular oil change service'),
(2, 2, 'Tire Rotation', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY), 'scheduled', 'Rotate and balance tires'),
(3, 3, 'Brake Inspection', DATE_ADD(CURRENT_DATE, INTERVAL 21 DAY), 'scheduled', 'Check brake pads and discs'),
(4, 4, 'General Inspection', DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 'scheduled', 'Complete vehicle inspection'),
(5, 5, 'Air Filter Replacement', DATE_ADD(CURRENT_DATE, INTERVAL 45 DAY), 'scheduled', 'Replace air filter element');

-- Sample breakdown reports
INSERT INTO breakdown_reports (bus_id, driver_id, breakdown_date, location, description, severity, status) VALUES
(1, 1, NOW(), 'Casablanca', 'Engine overheating issue', 'medium', 'reported'),
(2, 2, DATE_SUB(NOW(), INTERVAL 2 HOUR), 'Rabat', 'Flat tire - rear left', 'low', 'assigned'),
(3, 3, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Tangier', 'Brake failure warning', 'high', 'in_progress'),
(4, 4, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Marrakech', 'Battery not charging', 'medium', 'resolved'),
(5, 5, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Fez', 'Transmission slipping', 'high', 'closed');

-- Sample work orders
INSERT INTO work_orders (breakdown_id, technician_id, work_description, parts_used, labor_hours, labor_cost, parts_cost, total_cost, status) VALUES
(4, 2, 'Replace alternator and test charging system', 'Alternator 24V, Electrical connectors', 3.5, 350.00, 450.00, 800.00, 'completed'),
(5, 2, 'Transmission fluid change and inspection', 'Transmission fluid, Filter kit', 4.0, 400.00, 120.00, 520.00, 'completed'),
(2, 3, 'Replace flat tire and check all tires', 'Tire 315/80R22.5, Valve stem', 1.5, 150.00, 280.00, 430.00, 'in_progress');

-- Sample notifications
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(1, 'New Breakdown Report', 'Bus BUS-001 has reported an engine overheating issue', 'warning', 0),
(2, 'Work Order Assigned', 'You have been assigned to breakdown report #4', 'info', 0),
(1, 'Maintenance Reminder', 'Bus BUS-002 is scheduled for tire rotation in 7 days', 'info', 1),
(3, 'Low Stock Alert', 'Article PIEC-008 (Radiateur) is below minimum stock level', 'warning', 0),
(2, 'Breakdown Resolved', 'Breakdown report #4 has been marked as resolved', 'success', 1);

-- Update some buses to different statuses
UPDATE buses SET status = 'maintenance' WHERE bus_number IN ('BUS-008', 'MINI-010');
UPDATE buses SET status = 'inactive' WHERE bus_number IN ('BUS-006');

-- Final statistics
SELECT 
    'Final Statistics' as type,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM drivers) as total_drivers,
    (SELECT COUNT(*) FROM buses) as total_buses,
    (SELECT COUNT(*) FROM articles_catalogue) as total_articles,
    (SELECT COUNT(*) FROM breakdown_reports) as total_breakdowns,
    (SELECT COUNT(*) FROM maintenance_schedules) as total_schedules,
    (SELECT COUNT(*) FROM notifications WHERE is_read = 0) as unread_notifications;

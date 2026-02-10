-- QUICK FIX - Resolve foreign key constraint violations
-- حل سريع لمشاكل المفاتيح الخارجية

-- Step 1: Check if we have breakdown_reports
SELECT 'Checking breakdown_reports...' as status;
SELECT COUNT(*) as breakdown_count FROM breakdown_reports;

-- Step 2: If no breakdown_reports, create one
INSERT IGNORE INTO breakdown_reports (
    report_ref, driver_id, bus_id, kilometrage, category, urgency, description, status
) VALUES (
    'BRK-QUICKFIX-001', 1, 1, 50000, 'mecanique', 'urgent', 'Quick fix breakdown report', 'nouveau'
);

-- Step 3: Check if we have users
SELECT 'Checking users...' as status;
SELECT COUNT(*) as user_count FROM users;

-- Step 4: If no users, create one
INSERT IGNORE INTO users (username, email, password, role, is_active) 
VALUES ('admin', 'admin@quickfix.com', 'admin123', 'admin', 1);

-- Step 5: Clean up invalid assignments
DELETE ba FROM breakdown_assignments ba
LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.id
WHERE br.id IS NULL;

-- Step 6: Clean up invalid user assignments
DELETE ba FROM breakdown_assignments ba
LEFT JOIN users u ON ba.assigned_to = u.id
WHERE u.id IS NULL;

DELETE ba FROM breakdown_assignments ba
LEFT JOIN users u ON ba.assigned_by = u.id
WHERE u.id IS NULL;

-- Step 7: Add a valid assignment if table is empty
INSERT IGNORE INTO breakdown_assignments (
    breakdown_id, assigned_to, assigned_by, status, notes
) 
SELECT 
    (SELECT MIN(id) FROM breakdown_reports),
    (SELECT MIN(id) FROM users),
    (SELECT MIN(id) FROM users),
    'assigned',
    'Quick fix assignment'
WHERE (SELECT COUNT(*) FROM breakdown_assignments) = 0;

-- Step 8: Show results
SELECT 'Final check...' as status;
SELECT COUNT(*) as final_breakdown_count FROM breakdown_reports;
SELECT COUNT(*) as final_user_count FROM users;
SELECT COUNT(*) as final_assignment_count FROM breakdown_assignments;

-- Step 9: Show sample data
SELECT * FROM breakdown_reports LIMIT 1;
SELECT * FROM users LIMIT 1;
SELECT * FROM breakdown_assignments LIMIT 1;

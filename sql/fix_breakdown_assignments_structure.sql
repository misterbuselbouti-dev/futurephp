-- FUTURE AUTOMOTIVE - Fix breakdown_assignments table structure
-- إصلاح هيكل جدول breakdown_assignments لمطابقة الاستخدام في الكود

-- Current table structure has:
-- breakdown_id, assigned_to, assigned_by

-- But PHP code uses:
-- report_id, assigned_to_user_id, assigned_by_user_id

-- Option 1: Add the columns the PHP code expects
ALTER TABLE breakdown_assignments ADD COLUMN report_id INT AFTER breakdown_id;
ALTER TABLE breakdown_assignments ADD COLUMN assigned_to_user_id INT AFTER assigned_to;
ALTER TABLE breakdown_assignments ADD COLUMN assigned_by_user_id INT AFTER assigned_by;

-- Add started_at column if it doesn't exist (used in technician_breakdowns.php line 60)
ALTER TABLE breakdown_assignments ADD COLUMN started_at TIMESTAMP NULL DEFAULT NULL AFTER assigned_at;

-- Option 2: Or rename existing columns to match PHP code
-- ALTER TABLE breakdown_assignments CHANGE breakdown_id report_id INT NOT NULL;
-- ALTER TABLE breakdown_assignments CHANGE assigned_to assigned_to_user_id INT NOT NULL;
-- ALTER TABLE breakdown_assignments CHANGE assigned_by assigned_by_user_id INT NOT NULL;

-- For now, let's add the new columns and populate them
UPDATE breakdown_assignments SET report_id = breakdown_id WHERE report_id IS NULL;
UPDATE breakdown_assignments SET assigned_to_user_id = assigned_to WHERE assigned_to_user_id IS NULL;
UPDATE breakdown_assignments SET assigned_by_user_id = assigned_by WHERE assigned_by_user_id IS NULL;

-- Show updated structure
DESCRIBE breakdown_assignments;

-- Test the queries from PHP files
SELECT 'Testing PHP queries:' as status;

-- Test 1: From technician_breakdowns.php line 43
SELECT id FROM breakdown_assignments WHERE report_id = 1 AND assigned_to_user_id = 1;

-- Test 2: From technician_breakdowns.php line 49
INSERT INTO breakdown_assignments (report_id, assigned_to_user_id, assigned_by_user_id, assigned_at) 
VALUES (999, 1, 1, NOW());

-- Test 3: From technician_breakdowns.php line 60
SELECT id, started_at FROM breakdown_assignments WHERE report_id = 1 AND assigned_to_user_id = 1;

-- Test 4: From admin_breakdown_view.php line 121
SELECT * FROM breakdown_assignments WHERE report_id = 1 ORDER BY assigned_at DESC LIMIT 1;

-- Clean up test data
DELETE FROM breakdown_assignments WHERE report_id = 999;

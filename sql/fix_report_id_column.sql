-- FUTURE AUTOMOTIVE - Fix report_id column references
-- إصلاح مراجع عمود report_id

-- Check if there's a report_id column in breakdown_reports
-- Usually this should be 'id' not 'report_id'

-- Option 1: Add report_id column as alias to id
ALTER TABLE breakdown_reports ADD COLUMN report_id INT GENERATED ALWAYS AS (id) STORED;

-- Option 2: If you prefer to rename the id column to report_id
-- ALTER TABLE breakdown_reports CHANGE id report_id INT AUTO_INCREMENT PRIMARY KEY;

-- Show table structure to verify
DESCRIBE breakdown_reports;

-- Test queries that might use report_id
SELECT 'Testing queries with report_id:' as status;

-- Test 1: Simple query with report_id
SELECT * FROM breakdown_reports WHERE report_id = 1 LIMIT 1;

-- Test 2: Join with breakdown_assignments using report_id
SELECT ba.*, br.report_ref 
FROM breakdown_assignments ba
LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.report_id
LIMIT 3;

-- If the above queries work, then report_id column is properly set up

-- Alternative: If you want to remove the generated column and fix the queries instead
-- ALTER TABLE breakdown_reports DROP COLUMN report_id;

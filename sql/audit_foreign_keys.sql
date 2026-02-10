-- FUTURE AUTOMOTIVE - Audit Foreign Key Constraints Verification
-- Comprehensive check of all foreign key relationships in the breakdown management system

-- Check if all required tables exist
SELECT 'Checking table existence...' as status;

-- Tables that should exist
SELECT 'breakdown_reports' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'breakdown_reports') as exists
UNION ALL
SELECT 'breakdown_assignments' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'breakdown_assignments') as exists
UNION ALL
SELECT 'breakdown_work_items' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'breakdown_work_items') as exists
UNION ALL
SELECT 'breakdown_time_logs' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'breakdown_time_logs') as exists
UNION ALL
SELECT 'breakdown_audit_log' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'breakdown_audit_log') as exists
UNION ALL
SELECT 'users' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users') as exists
UNION ALL
SELECT 'articles_catalogue' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'articles_catalogue') as exists
UNION ALL
SELECT 'buses' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'buses') as exists
UNION ALL
SELECT 'drivers' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'drivers') as exists
UNION ALL
SELECT 'notifications' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'notifications') as exists
UNION ALL
SELECT 'pan_issues' as table_name, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pan_issues') as exists;

-- Check foreign key constraints
SELECT 'Checking foreign key constraints...' as status;

SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE,
    UPDATE_RULE
FROM 
    information_schema.KEY_COLUMN_USAGE 
WHERE 
    TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('breakdown_reports', 'breakdown_assignments', 'breakdown_work_items', 'breakdown_time_logs', 'breakdown_audit_log')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- Check specific relationships
SELECT 'Checking specific relationships...' as status;

-- 1. breakdown_assignments -> breakdown_reports
SELECT 'breakdown_assignments -> breakdown_reports' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_assignments'
               AND constraint_name = 'breakdown_assignments_ibfk_1'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_assignments;

-- 2. breakdown_assignments -> users (assigned_to)
SELECT 'breakdown_assignments -> users (assigned_to)' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_assignments'
               AND constraint_name LIKE '%assigned_to%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_assignments;

-- 3. breakdown_assignments -> users (assigned_by)
SELECT 'breakdown_assignments -> users (assigned_by)' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_assignments'
               AND constraint_name LIKE '%assigned_by%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_assignments;

-- 4. breakdown_work_items -> breakdown_reports
SELECT 'breakdown_work_items -> breakdown_reports' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_work_items'
               AND constraint_name LIKE '%breakdown_reports%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_work_items;

-- 5. breakdown_work_items -> breakdown_assignments
SELECT 'breakdown_work_items -> breakdown_assignments' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_work_items'
               AND constraint_name LIKE '%breakdown_assignments%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_work_items;

-- 6. breakdown_work_items -> articles_catalogue
SELECT 'breakdown_work_items -> articles_catalogue' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_work_items'
               AND constraint_name LIKE '%articles_catalogue%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_work_items;

-- 7. breakdown_work_items -> users (added_by)
SELECT 'breakdown_work_items -> users (added_by)' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_work_items'
               AND constraint_name LIKE '%added_by_user_id%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_work_items;

-- 8. breakdown_time_logs -> breakdown_reports
SELECT 'breakdown_time_logs -> breakdown_reports' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_time_logs'
               AND constraint_name LIKE '%breakdown_reports%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_time_logs;

-- 9. breakdown_time_logs -> breakdown_assignments
SELECT 'breakdown_time_logs -> breakdown_assignments' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_time_logs'
               AND constraint_name LIKE '%breakdown_assignments%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_time_logs;

-- 10. breakdown_time_logs -> users (user_id)
SELECT 'breakdown_time_logs -> users (user_id)' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_time_logs'
               AND constraint_name LIKE '%user_id%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_time_logs;

-- 11. breakdown_time_logs -> users (created_by)
SELECT 'breakdown_time_logs -> users (created_by)' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_time_logs'
               AND constraint_name LIKE '%created_by_user_id%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_time_logs;

-- 12. breakdown_audit_log -> breakdown_reports
SELECT 'breakdown_audit_log -> breakdown_reports' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_audit_log'
               AND constraint_name LIKE '%breakdown_reports%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_audit_log;

-- 13. breakdown_audit_log -> breakdown_assignments
SELECT 'breakdown_audit_log -> breakdown_assignments' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_audit_log'
               AND constraint_name LIKE '%breakdown_assignments%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_audit_log;

-- 14. breakdown_audit_log -> users (performed_by)
SELECT 'breakdown_audit_log -> users (performed_by)' as relationship,
       COUNT(*) as records,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'breakdown_audit_log'
               AND constraint_name LIKE '%performed_by_user_id%'
           ) THEN 'OK'
           ELSE 'MISSING'
       END as fk_status
FROM breakdown_audit_log;

-- Check data integrity
SELECT 'Checking data integrity...' as status;

-- 1. Orphaned assignments (breakdown_id not in breakdown_reports)
SELECT 'Orphaned assignments' as issue_type,
       COUNT(*) as count
FROM breakdown_assignments ba
LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.id
WHERE br.id IS NULL;

-- 2. Orphaned work items (breakdown_id not in breakdown_reports)
SELECT 'Orphaned work items' as issue_type,
       COUNT(*) as count
FROM breakdown_work_items bwi
LEFT JOIN breakdown_reports br ON bwi.breakdown_id = br.id
WHERE br.id IS NULL;

-- 3. Orphaned work items (assignment_id not in breakdown_assignments)
SELECT 'Orphaned work items (assignment)' as issue_type,
       COUNT(*) as count
FROM breakdown_work_items bwi
LEFT JOIN breakdown_assignments ba ON bwi.assignment_id = ba.id
WHERE ba.id IS NULL;

-- 4. Orphaned time logs (breakdown_id not in breakdown_reports)
SELECT 'Orphaned time logs (breakdown_id)' as issue_type,
       COUNT(*) as count
FROM breakdown_time_logs tl
LEFT JOIN breakdown_reports br ON tl.breakdown_id = br.id
WHERE br.id IS NULL;

-- 5. Orphaned time logs (assignment_id not in breakdown_assignments)
SELECT 'Orphaned time logs (assignment)' as issue_type,
       COUNT(*) as count
FROM breakdown_time_logs tl
LEFT JOIN breakdown_assignments ba ON tl.assignment_id = SELECT id FROM breakdown_assignments WHERE id = tl.assignment_id
WHERE ba.id IS NULL;

-- 6. Orphaned audit logs (breakdown_id not in breakdown_reports)
SELECT 'Orphaned audit logs (breakdown_id)' as issue_type,
       COUNT(*) as count
FROM breakdown_audit_log bal
LEFT JOIN breakdown_reports br ON bal.breakdown_id = br.id
WHERE br.id IS NULL;

-- 7. Orphaned audit logs (assignment_id not in breakdown_assignments)
SELECT 'Orphaned audit logs (assignment)' as issue_type,
       COUNT(*) as count
FROM breakdown_audit_log bal
LEFT JOIN breakdown_assignments ba ON bal.assignment_id = ba.id
WHERE bal.assignment_id IS NOT NULL AND ba.id IS NULL;

-- 8. Invalid user references
SELECT 'Invalid user references' as issue_type,
       COUNT(*) as count
FROM breakdown_assignments ba
LEFT JOIN users u ON ba.assigned_to_user_id = u.id
WHERE ba.assigned_to_user_id IS NOT NULL AND u.id IS NULL;

SELECT 'Invalid user references' as issue_type,
       COUNT(*) as count
FROM breakdown_assignments ba
LEFT JOIN users u ON ba.assigned_by_user_id = u.id
WHERE ba.assigned_by_user_id IS NOT NULL AND u.id IS NULL;

-- 9. Invalid article references
SELECT 'Invalid article references' as issue_type,
       COUNT(*) as count
FROM breakdown_work_items bwi
LEFT JOIN articles_catalogue ac ON bwi.article_id = ac.id
WHERE bwi.article_id IS NOT NULL AND ac.id IS NULL;

-- 10. Missing required data
SELECT 'Missing required data' as issue_type,
       COUNT(*) as count
FROM breakdown_reports br
WHERE 
    br.report_ref IS NULL OR 
    br.created_by_user_id IS NULL OR
    br.driver_id IS NULL OR
    br.bus_id IS NULL OR
    br.status IS NULL;

SELECT 'Missing required data (assignments)' as issue_type,
       COUNT(*) as count
FROM breakdown_assignments ba
WHERE 
    ba.assigned_to_user_id IS NULL OR
    ba.assigned_by_user_id IS NULL;

SELECT 'Missing required data (work items)' as issue_type,
       COUNT(*) as count
FROM breakdown_work_items bwi
WHERE 
    bwi.quantity_used IS NULL OR 
    bwi.quantity_used <= 0 OR
    bwi.unit_cost IS NULL OR
    bwi.added_by_user_id IS NULL;

SELECT 'Missing required data (time logs)' as issue_type,
       COUNT(*) as count
FROM breakdown_time_logs tl
WHERE 
    tl.user_id IS NULL OR
    tl.created_by_user_id IS NULL OR
    tl.action_type IS NULL;

SELECT 'Missing required data (audit logs)' as issue_type,
       COUNT(*) as count
FROM breakdown_audit_log bal
WHERE 
    bal.performed_by_user_id IS NULL OR
    bal.action_type IS NULL;

-- Summary report
SELECT 'AUDIT SUMMARY' as section,
       'All checks completed' as message;

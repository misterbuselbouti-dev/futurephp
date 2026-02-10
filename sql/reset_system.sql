-- FUTURE AUTOMOTIVE - Reset System (Manual Table Drops)
-- إعادة تعيين النظام (حذف يدوي للجداول)

-- Manual table deletion (no information_schema access)
DROP TABLE IF EXISTS work_orders;
DROP TABLE IF EXISTS breakdown_reports;
DROP TABLE IF EXISTS maintenance_schedules;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS articles_catalogue;
DROP TABLE IF EXISTS buses;
DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS users;

-- Wait a moment for tables to be fully dropped
SELECT 'Tables dropped, waiting...' as status, SLEEP(1) as wait;

-- Now recreate everything using the simple setup
-- (You should run simple_system_setup.sql after this)

SELECT '✅ RESET COMPLETED' as status,
       'Now run simple_system_setup.sql to recreate everything' as message;

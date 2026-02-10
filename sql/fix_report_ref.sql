-- FUTURE AUTOMOTIVE - Fix report_ref column in breakdown_reports table
-- إصلاح عمود report_ref في جدول breakdown_reports

-- Check if report_ref column exists, add it if it doesn't
SET @dbname = DATABASE();
SET @tablename = 'breakdown_reports';
SET @columnname = 'report_ref';

-- Check if column exists
SELECT COUNT(*) INTO @column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = @tablename 
  AND COLUMN_NAME = @columnname;

-- Add column if it doesn't exist
SET @sql = IF(@column_exists > 0, 
    'SELECT ''report_ref column already exists'' as message',
    'ALTER TABLE breakdown_reports ADD COLUMN report_ref VARCHAR(50) UNIQUE AFTER created_at'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show result
SELECT 'Column check completed' as status;

-- Show table structure
DESCRIBE breakdown_reports;

-- Test the problematic INSERT
SELECT 'Testing INSERT with report_ref:' as status;

-- Generate a sample report_ref
SET @report_ref = CONCAT('BRK-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 9999) + 1, 4, '0'));
SELECT @report_ref as sample_report_ref;

-- Test INSERT with report_ref
INSERT INTO breakdown_reports (
    report_ref, created_by_user_id, driver_id, bus_id, kilometrage, 
    category, urgency, message_text, status
) VALUES (
    @report_ref, NULL, 1, 1, 50000, 
    'mecanique', 'urgent', 'Test incident', 'nouveau'
);

SELECT 'Test INSERT completed' as status;

-- Verify the insertion
SELECT * FROM breakdown_reports WHERE report_ref = @report_ref;

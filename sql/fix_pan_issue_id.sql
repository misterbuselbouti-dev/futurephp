-- FUTURE AUTOMOTIVE - Fix pan_issue_id column in breakdown_reports table
-- إصلاح عمود pan_issue_id في جدول breakdown_reports

-- Check if pan_issue_id column exists, add it if it doesn't
SET @dbname = DATABASE();
SET @tablename = 'breakdown_reports';
SET @columnname = 'pan_issue_id';

-- Check if column exists
SELECT COUNT(*) INTO @column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = @tablename 
  AND COLUMN_NAME = @columnname;

-- Add column if it doesn't exist
SET @sql = IF(@column_exists > 0, 
    'SELECT ''pan_issue_id column already exists'' as message',
    'ALTER TABLE breakdown_reports ADD COLUMN pan_issue_id INT AFTER description'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Also check and add message_text column if it doesn't exist
SET @columnname = 'message_text';
SELECT COUNT(*) INTO @message_text_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = @tablename 
  AND COLUMN_NAME = @columnname;

SET @sql = IF(@message_text_exists > 0, 
    'SELECT ''message_text column already exists'' as message',
    'ALTER TABLE breakdown_reports ADD COLUMN message_text TEXT AFTER description'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if pan_issues table exists
SET @pan_issues_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'pan_issues');

SET @sql = IF(@pan_issues_exists > 0,
    'ALTER TABLE breakdown_reports ADD CONSTRAINT fk_breakdown_pan_issues FOREIGN KEY (pan_issue_id) REFERENCES pan_issues(id) ON DELETE SET NULL',
    'SELECT ''pan_issues table does not exist, skipping foreign key'' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show result
SELECT 'Column check completed' as status;

-- Show table structure
DESCRIBE breakdown_reports;

-- Show pan_issues table structure if it exists
SET @pan_issues_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'pan_issues');

SET @sql = IF(@pan_issues_exists > 0,
    'DESCRIBE pan_issues',
    'SELECT ''pan_issues table does not exist'' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Test the problematic query
SELECT 'Testing the problematic query:' as status;

SELECT br.*, b.bus_number, b.license_plate, 
       CONCAT(d.prenom, ' ', d.nom) AS driver_name, d.phone AS driver_phone,
       pi.pan_code, pi.label_fr
FROM breakdown_reports br
LEFT JOIN buses b ON br.bus_id = b.id
LEFT JOIN drivers d ON br.driver_id = d.id
LEFT JOIN pan_issues pi ON br.pan_issue_id = pi.id
ORDER BY br.created_at DESC
LIMIT 3;

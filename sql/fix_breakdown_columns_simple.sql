-- FUTURE AUTOMOTIVE - Fix breakdown_reports columns (simple version)
-- إصلاح أعمدة breakdown_reports (نسخة بسيطة بدون information_schema)

-- Add report_ref column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN report_ref VARCHAR(50) UNIQUE AFTER created_at;

-- Add created_by_user_id column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN created_by_user_id INT AFTER report_ref;

-- Add kilometrage column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN kilometrage INT AFTER bus_id;

-- Add message_text column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN message_text TEXT AFTER description;

-- Add category column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN category VARCHAR(100) AFTER message_text;

-- Add urgency column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN urgency VARCHAR(50) AFTER category;

-- Add status column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN status VARCHAR(50) DEFAULT 'nouveau' AFTER urgency;

-- Add pan_issue_id column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN pan_issue_id INT AFTER status;

-- Add audio_path column if it doesn't exist
ALTER TABLE breakdown_reports ADD COLUMN audio_path VARCHAR(255) AFTER pan_issue_id;

-- Show table structure after adding columns
DESCRIBE breakdown_reports;

-- Test the INSERT query
SELECT 'Testing INSERT query:' as status;

-- Generate a sample report_ref
SELECT CONCAT('BRK-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 9999) + 1, 4, '0')) as sample_report_ref;

-- Test INSERT with all required columns
INSERT INTO breakdown_reports (
    report_ref, created_by_user_id, driver_id, bus_id, kilometrage, 
    category, urgency, message_text, status, audio_path
) VALUES (
    'BRK-TEST-001', NULL, 1, 1, 50000, 
    'mecanique', 'urgent', 'Test incident', 'nouveau', NULL
);

SELECT 'Test INSERT completed' as status;

-- Verify the insertion
SELECT * FROM breakdown_reports WHERE report_ref = 'BRK-TEST-001';

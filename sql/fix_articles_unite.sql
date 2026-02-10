-- FUTURE AUTOMOTIVE - Fix Articles Catalogue unite Column
-- إصلاح عمود unite في جدول articles_catalogue

-- Check if unite column exists, remove it if it does
SET @dbname = DATABASE();
SET @tablename = 'articles_catalogue';
SET @columnname = 'unite';

-- Drop the column if it exists
SET @sql = CONCAT('ALTER TABLE ', @tablename, ' DROP COLUMN IF EXISTS ', @columnname);
SET @sql = REPLACE(@sql, 'DROP COLUMN IF EXISTS ', 'DROP COLUMN ');
SET @sql = REPLACE(@sql, 'DROP COLUMN IF EXISTS ', 'DROP COLUMN ');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show current table structure
SHOW COLUMNS FROM articles_catalogue;

-- Show results
SELECT 
    'Articles catalogue table fixed successfully' as status,
    (SELECT COUNT(*) as count FROM articles_catalogue) as total_articles,
    (SELECT COUNT(*) as count FROM articles_catalogue WHERE stock_actuel <= stock_minimal) as low_stock_articles,
    'Removed unite column - using standardized piece unit' as message;

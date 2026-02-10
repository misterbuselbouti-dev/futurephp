-- FUTURE AUTOMOTIVE - Instructions for fixing ref_ot field
-- تعليمات لإصلاح حقل ref_ot

-- ⚠️ تنبيه: لا تنفذ جملة CONCAT بشكل مباشر في phpMyAdmin
-- ⚠️ Warning: Do not execute CONCAT statement directly in phpMyAdmin

-- الطريقة الصحيحة 1: استخدام أداة الإصلاح التلقائي
-- Method 1: Use the automatic fix tool
-- افتح الرابط التالي في المتصفح:
-- Open this link in your browser:
-- https://futureautomotive.net/fix_ref_ot.php

-- الطريقة الصحيحة 2: إضافة عمود مُولد (MySQL 5.7+)
-- Method 2: Add generated column (MySQL 5.7+)
ALTER TABLE work_orders 
ADD COLUMN ref_ot VARCHAR(20) GENERATED ALWAYS AS (
    CONCAT('OT-', YEAR(created_at), '-', LPAD(id, 4, '0'))
) STORED;

-- الطريقة الصحيحة 3: إضافة عمود عادي وتحديثه (لإصدارات MySQL الأقدم)
-- Method 3: Add regular column and update (for older MySQL versions)
ALTER TABLE work_orders ADD COLUMN ref_ot VARCHAR(20);
UPDATE work_orders SET ref_ot = CONCAT('OT-', YEAR(created_at), '-', LPAD(id, 4, '0'));
ALTER TABLE work_orders ADD UNIQUE INDEX idx_ref_ot (ref_ot);

-- الطريقة الصحيحة 4: إنشاء view بديلاً من تعديل الجدول
-- Method 4: Create view instead of modifying table
CREATE OR REPLACE VIEW work_orders_with_ref AS
SELECT 
    wo.*,
    CONCAT('OT-', YEAR(wo.created_at), '-', LPAD(wo.id, 4, '0')) as ref_ot
FROM work_orders wo;

-- ⚠️ ملاحظات هامة:
-- ⚠️ Important Notes:
-- 1. لا تنفذ جملة CONCAT بمفردها - يجب أن تكون جزءاً من استعلام SELECT أو ALTER
-- 2. استخدم fix_ref_ot.php للحل التلقائي والآمن
-- 3. إذا لم ينجح الحل التلقائي، استخدم الطريقة 2 أو 3
-- 4. الطريقة 4 آمنة إذا لم تكن تريد تعديل الجدول الأصلي

-- ✅ الحل الموصى به:
-- ✅ Recommended Solution:
-- استخدم fix_ref_ot.php - سيقوم بالإصلاح تلقائياً وبشكل آمن
-- Use fix_ref_ot.php - it will fix automatically and safely

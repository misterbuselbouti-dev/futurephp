-- FUTURE AUTOMOTIVE - Add ref_ot column to work_orders table
-- إضافة عمود المرجع إلى جدول أوامر العمل

-- Option 1: Add generated column (MySQL 5.7+)
ALTER TABLE work_orders 
ADD COLUMN ref_ot VARCHAR(20) GENERATED ALWAYS AS (
    CONCAT('OT-', YEAR(created_at), '-', LPAD(id, 4, '0'))
) STORED;

-- Option 2: Add regular column and update existing records (for older MySQL versions)
-- ALTER TABLE work_orders ADD COLUMN ref_ot VARCHAR(20);
-- UPDATE work_orders SET ref_ot = CONCAT('OT-', YEAR(created_at), '-', LPAD(id, 4, '0'));
-- ALTER TABLE work_orders ADD UNIQUE INDEX idx_ref_ot (ref_ot);

-- Option 3: Create a view instead (if you can't modify the table)
CREATE OR REPLACE VIEW work_orders_with_ref AS
SELECT 
    wo.*,
    CONCAT('OT-', YEAR(wo.created_at), '-', LPAD(wo.id, 4, '0')) as ref_ot
FROM work_orders wo;

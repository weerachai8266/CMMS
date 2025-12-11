-- ===================================================
-- ลบคอลัมน์ history_type ออกจากตาราง mt_machine_history
-- เนื่องจากใช้ document_no แทนในการระบุประเภทงาน
-- ===================================================

-- ลบ index ที่เกี่ยวข้องก่อน
ALTER TABLE mt_machine_history DROP INDEX IF EXISTS idx_history_type;

-- ลบคอลัมน์ history_type
ALTER TABLE mt_machine_history DROP COLUMN IF EXISTS history_type;

-- ปรับ comment ของ document_no
ALTER TABLE mt_machine_history 
MODIFY COLUMN document_no VARCHAR(50) COMMENT 'เลขที่เอกสาร / ประเภทงาน (repair, pm, calibration)';

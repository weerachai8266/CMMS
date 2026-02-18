-- เพิ่มคอลัมน์สำหรับติดตาม user ที่สร้างและแก้ไขข้อมูล Master Data
-- สำหรับตาราง: mt_companies, mt_branches, mt_divisions, mt_departments, mt_issues

-- 1. ตาราง mt_companies
ALTER TABLE mt_companies 
ADD COLUMN created_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้สร้างข้อมูล',
ADD COLUMN updated_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้แก้ไขข้อมูลล่าสุด';

-- 2. ตาราง mt_branches
ALTER TABLE mt_branches 
ADD COLUMN created_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้สร้างข้อมูล',
ADD COLUMN updated_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้แก้ไขข้อมูลล่าสุด';

-- 3. ตาราง mt_divisions
ALTER TABLE mt_divisions 
ADD COLUMN created_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้สร้างข้อมูล',
ADD COLUMN updated_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้แก้ไขข้อมูลล่าสุด';

-- 4. ตาราง mt_departments
ALTER TABLE mt_departments 
ADD COLUMN created_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้สร้างข้อมูล',
ADD COLUMN updated_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้แก้ไขข้อมูลล่าสุด';

-- 5. ตาราง mt_issues
ALTER TABLE mt_issues 
ADD COLUMN created_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้สร้างข้อมูล',
ADD COLUMN updated_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้แก้ไขข้อมูลล่าสุด';
-- ตรวจสอบว่าเพิ่มคอลัมน์สำเร็จ
SELECT 
    'mt_companies' as table_name,
    COLUMN_NAME, 
    DATA_TYPE, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'mt_companies' 
    AND COLUMN_NAME IN ('created_by', 'updated_by')

UNION ALL

SELECT 
    'mt_branches' as table_name,
    COLUMN_NAME, 
    DATA_TYPE, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'mt_branches' 
    AND COLUMN_NAME IN ('created_by', 'updated_by')

UNION ALL

SELECT 
    'mt_divisions' as table_name,
    COLUMN_NAME, 
    DATA_TYPE, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'mt_divisions' 
    AND COLUMN_NAME IN ('created_by', 'updated_by')

UNION ALL

SELECT 
    'mt_departments' as table_name,
    COLUMN_NAME, 
    DATA_TYPE, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'mt_departments' 
    AND COLUMN_NAME IN ('created_by', 'updated_by')

UNION ALL

SELECT 
    'mt_issues' as table_name,
    COLUMN_NAME, 
    DATA_TYPE, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'mt_issues' 
    AND COLUMN_NAME IN ('created_by', 'updated_by');

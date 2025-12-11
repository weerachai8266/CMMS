-- ===================================================
-- ตาราง: mt_machine_history
-- วัตถุประสงค์: เก็บประวัติการซ่อม PM Calibration ของเครื่องจักร
-- ===================================================

CREATE TABLE IF NOT EXISTS mt_machine_history (
  id INT PRIMARY KEY AUTO_INCREMENT,
  
  -- เชื่อมโยงกับเครื่องจักร
  machine_id INT COMMENT 'ID ของเครื่องจักร',
  machine_code VARCHAR(50) COMMENT 'รหัสเครื่องจักร',
  machine_name VARCHAR(255) COMMENT 'ชื่อเครื่องจักร',
  
  -- ข้อมูลพื้นฐาน
  document_no VARCHAR(50) COMMENT 'เลขที่เอกสาร / ประเภทงาน (repair, pm, calibration)',
  work_date DATE COMMENT 'วันที่แจ้ง/วันที่ทำงาน',
  start_date DATE COMMENT 'วันที่เริ่มดำเนินการ',
  completed_date DATE COMMENT 'วันที่เสร็จสิ้น',
  
  -- รายละเอียดงาน
  issue_description TEXT COMMENT 'อาการเสีย/ปัญหา/รายละเอียมการซ่อม',
  solution_description TEXT COMMENT 'วิธีแก้ไข/การซ่อม/รายการปรับสินและลิ้งส์',
  parts_used TEXT COMMENT 'รายการอะไหล่ที่ใช้/รหัสอะไหล่',
  
  -- เวลาและค่าใช้จ่าย
  work_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'เวลาปฏิบัติงาน (ชั่วโมง)',
  downtime_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'เวลาหยุดเครื่อง (ชั่วโมง)',
  labor_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'ค่าแรง',
  parts_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'ค่าอะไหล่',
  other_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'ค่าใช้จ่ายอื่นๆ',
  total_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'ค่าใช้จ่ายรวม',
  
  -- ผู้เกี่ยวข้อง
  reported_by VARCHAR(100) COMMENT 'ผู้แจ้ง',
  handled_by VARCHAR(100) COMMENT 'ผู้รับผิดชอบ/ช่าง',
  approved_by VARCHAR(100) COMMENT 'ผู้อนุมัติ',
  
  -- สถานะและความสำคัญ
  status VARCHAR(20) DEFAULT 'completed' COMMENT 'สถานะ: pending, in-progress, completed, cancelled',
  priority VARCHAR(20) COMMENT 'ความเร่งด่วน: low, normal, high, urgent',
  
  -- หมายเหตุและไฟล์แนบ
  note TEXT COMMENT 'หมายเหตุ',
  attachments TEXT COMMENT 'ไฟล์แนบ (JSON)',
  
  -- ข้อมูลเพิ่มเติม
  branch VARCHAR(100) COMMENT 'สาขา',
  department VARCHAR(100) COMMENT 'แผนก',
  
  -- Timestamp
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by VARCHAR(100) COMMENT 'ผู้สร้างรายการ',
  
  -- Indexes
  INDEX idx_machine_id (machine_id),
  INDEX idx_machine_code (machine_code),
  INDEX idx_document_no (document_no),
  INDEX idx_work_date (work_date),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประวัติการซ่อมและบำรุงรักษาเครื่องจักร';

-- ===================================================
-- ข้อมูลตัวอย่าง (ถ้าต้องการ)
-- ===================================================

-- INSERT INTO mt_machine_history 
-- (machine_code, machine_name, document_no, history_type, work_date, completed_date, 
--  issue_description, solution_description, parts_used, work_hours, total_cost, 
--  reported_by, handled_by, status)
-- VALUES 
-- ('PM-001', 'Vacuum Pump #1', 'REP-2024-001', 'repair', '2024-04-06', '2024-04-06',
--  'ยึดเครื่อง Vacuum', 'เปลี่ยนสก 10 mm แกก', 'SPU-10', 1.00, 7490.00,
--  'John Doe', 'เอมก้า', 'completed');

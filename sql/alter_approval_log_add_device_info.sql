-- เพิ่มคอลัมน์สำหรับเก็บข้อมูลอุปกรณ์ที่ใช้อนุมัติ/ปฏิเสธ
-- วัตถุประสงค์: เพื่อตรวจสอบความปลอดภัยและดูว่ามีการอนุมัติผิดปกติหรือไม่
-- วันที่: 2025-12-19

ALTER TABLE mt_approval_log 
ADD COLUMN device_type VARCHAR(20) DEFAULT NULL COMMENT 'ประเภทอุปกรณ์: desktop, mobile, tablet' AFTER reason,
ADD COLUMN browser VARCHAR(100) DEFAULT NULL COMMENT 'Browser: Chrome, Firefox, Safari, etc.' AFTER device_type,
ADD COLUMN os VARCHAR(100) DEFAULT NULL COMMENT 'Operating System: Windows, iOS, Android, etc.' AFTER browser,
ADD COLUMN ip_address VARCHAR(50) DEFAULT NULL COMMENT 'IP Address ของผู้อนุมัติ/ปฏิเสธ' AFTER os,
ADD INDEX idx_ip_address (ip_address),
ADD INDEX idx_device_type (device_type);

-- ตรวจสอบการเปลี่ยนแปลง
DESCRIBE mt_approval_log;

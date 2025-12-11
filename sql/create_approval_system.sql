-- สร้างตารางบันทึกประวัติการอนุมัติ/ปฏิเสธ
CREATE TABLE mt_approval_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL COMMENT 'รหัสใบแจ้งซ่อม',
    approver VARCHAR(100) NOT NULL COMMENT 'ผู้อนุมัติ/ปฏิเสธ',
    action ENUM('approved', 'rejected') NOT NULL COMMENT 'การดำเนินการ',
    reason TEXT COMMENT 'เหตุผล (กรณีปฏิเสธ)',
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่-เวลาที่อนุมัติ/ปฏิเสธ',
    FOREIGN KEY (repair_id) REFERENCES mt_repair(id) ON DELETE CASCADE,
    INDEX idx_repair_id (repair_id),
    INDEX idx_approver (approver)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประวัติการอนุมัติ';

-- เพิ่ม fields ใน mt_repair สำหรับเก็บข้อมูลการอนุมัติ
ALTER TABLE mt_repair 
ADD COLUMN approver VARCHAR(100) DEFAULT NULL COMMENT 'ผู้อนุมัติ',
ADD COLUMN approved_at DATETIME DEFAULT NULL COMMENT 'วันที่อนุมัติ',
ADD COLUMN reject_reason TEXT DEFAULT NULL COMMENT 'เหตุผลที่ไม่อนุมัติ';

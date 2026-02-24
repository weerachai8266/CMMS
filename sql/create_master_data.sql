-- ตารางสำหรับจัดการข้อมูลพื้นฐาน (Master Data)
-- สร้างเมื่อ: 2025-11-20
-- โครงสร้างแบบง่าย: ID, ชื่อ, สถานะ

-- ตารางบริษัท
CREATE TABLE IF NOT EXISTS mt_companies (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'ชื่อบริษัท',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=ใช้งาน, 0=ไม่ใช้งาน',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางบริษัท';

-- ตารางสาขา
CREATE TABLE IF NOT EXISTS mt_branches (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'ชื่อสาขา',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=ใช้งาน, 0=ไม่ใช้งาน',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางสาขา';

-- ตารางฝ่าย
CREATE TABLE IF NOT EXISTS mt_divisions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'ชื่อฝ่าย',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=ใช้งาน, 0=ไม่ใช้งาน',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางฝ่าย';

-- ตารางหน่วยงาน/แผนก
CREATE TABLE IF NOT EXISTS mt_departments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'ชื่อหน่วยงาน',
    group_id INT NULL COMMENT 'รหัสกลุ่ม',
    group_name VARCHAR(255) NULL COMMENT 'ชื่อกลุ่ม',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=ใช้งาน, 0=ไม่ใช้งาน',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางหน่วยงาน/แผนก';

-- ข้อมูลเริ่มต้น
INSERT INTO mt_companies (name) VALUES
('ชัยวัฒนา แทนเนอรี่ กรุ๊ป')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO mt_branches (name) VALUES
('ACP'),
('ASP'),
('DC'),
('FUR'),
('CENTER')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO mt_divisions (name) VALUES
('ผลิต'),
('คุณภาพ'),
('สโตร์'),
('ขนส่ง')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO mt_departments (name) VALUES
('ผลิต'),
('คุณภาพ'),
('สโตร์'),
('ขนส่ง')
ON DUPLICATE KEY UPDATE name=VALUES(name);

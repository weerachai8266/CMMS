-- สร้างตาราง mt_users สำหรับเก็บข้อมูล User
CREATE TABLE IF NOT EXISTS mt_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'ชื่อผู้ใช้ (ห้ามซ้ำ)',
    password VARCHAR(255) NOT NULL COMMENT 'รหัสผ่าน (เข้ารหัสแล้ว)',
    full_name VARCHAR(100) NOT NULL COMMENT 'ชื่อ-นามสกุล',
    email VARCHAR(100) DEFAULT NULL COMMENT 'อีเมล',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
    
    -- ระดับสิทธิ์
    role ENUM('admin', 'manager', 'technician', 'viewer') NOT NULL DEFAULT 'technician' COMMENT 'บทบาท',
    permissions TEXT DEFAULT NULL COMMENT 'สิทธิ์เพิ่มเติม (JSON)',
    
    -- ข้อมูลหน่วยงาน
    employee_id VARCHAR(50) DEFAULT NULL COMMENT 'รหัสพนักงาน',
    department VARCHAR(100) DEFAULT NULL COMMENT 'แผนก',
    branch VARCHAR(100) DEFAULT NULL COMMENT 'สาขา',
    position VARCHAR(100) DEFAULT NULL COMMENT 'ตำแหน่งงาน',
    
    -- สถานะและการติดตาม
    is_active TINYINT(1) DEFAULT 1 COMMENT 'สถานะใช้งาน (1=ใช้งาน, 0=ปิด)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
    created_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้สร้าง',
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไขล่าสุด',
    updated_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้แก้ไขล่าสุด',
    last_login DATETIME DEFAULT NULL COMMENT 'Login ล่าสุด',
    
    -- Security
    login_attempts INT DEFAULT 0 COMMENT 'จำนวนครั้งพยายาม login',
    locked_until DATETIME DEFAULT NULL COMMENT 'ล็อคจนถึงเวลา',
    password_reset_token VARCHAR(255) DEFAULT NULL COMMENT 'Token สำหรับรีเซ็ตรหัส',
    password_reset_expires DATETIME DEFAULT NULL COMMENT 'Token หมดอายุ',
    
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บข้อมูล User';

-- Insert ข้อมูล User เริ่มต้น 3 คน
-- รหัสผ่านทั้งหมดใช้ password_hash() ของ PHP (ต้องรันผ่าน PHP)
-- admin123 -> $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- tech123 -> $2y$10$O4KpkEVrR1Qr2bJf2RZK8.zDc8FwNs8VzDdQGPqQJzQJDQaI6PdJG
-- mt123 -> $2y$10$XQfL6QGv1f0v.pqy5z8ZWOwK4LzK5F8xHZH9jg5H0H1H2H3H4H5H6

INSERT INTO mt_users (
    username, 
    password, 
    full_name, 
    email, 
    role, 
    employee_id, 
    position,
    is_active,
    created_by
) VALUES 
(
    'admin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'ผู้ดูแลระบบ',
    'admin@company.com',
    'admin',
    'EMP001',
    'System Administrator',
    1,
    'system'
),
(
    'technician', 
    '$2y$10$O4KpkEVrR1Qr2bJf2RZK8.zDc8FwNs8VzDdQGPqQJzQJDQaI6PdJG', 
    'ช่างซ่อมบำรุง',
    'technician@company.com',
    'technician',
    'EMP002',
    'Maintenance Technician',
    1,
    'system'
),
(
    'maintenance', 
    '$2y$10$XQfL6QGv1f0v.pqy5z8ZWOwK4LzK5F8xHZH9jg5H0H1H2H3H4H5H6', 
    'หัวหน้าซ่อมบำรุง',
    'maintenance@company.com',
    'manager',
    'EMP003',
    'Maintenance Manager',
    1,
    'system'
);

-- ตรวจสอบข้อมูล User
SELECT 
    id,
    username,
    full_name,
    role,
    employee_id,
    position,
    is_active,
    created_at
FROM mt_users
ORDER BY id;

-- หมายเหตุ: รหัสผ่านที่เข้ารหัสไว้ด้านบนเป็นตัวอย่างเท่านั้น
-- ในการใช้งานจริง ควรสร้างรหัสผ่านใหม่ด้วย password_hash() ผ่าน PHP
